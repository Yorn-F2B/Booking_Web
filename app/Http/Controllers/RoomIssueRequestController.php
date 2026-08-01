<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Customer;
use App\Models\RoomIssueAttachment;
use App\Models\RoomIssueRequest;
use App\Services\RoomIssueProposalService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RoomIssueRequestController extends Controller
{
    public function __construct(
        private readonly RoomIssueProposalService $proposalService
    ) {
    }

    public function store(Request $request, Booking $booking)
    {
        $customer = Auth::user()?->customer;
        abort_unless($customer && (int) $booking->customer_id === (int) $customer->id, 403);

        if (!$this->bookingCanReportIssue($booking)) {
            return back()->with('error', 'Chỉ có thể báo sự cố khi booking đang ở trạng thái đã nhận phòng.');
        }

        try {
            $createdCount = $this->createRoomIssueRequests(
                $request,
                $booking,
                $customer,
                Auth::id(),
                'Tài khoản khách hàng'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Không thể gửi yêu cầu sự cố: ' . $e->getMessage());
        }

        return back()->with(
            'success',
            "Đã gửi 1 phiếu sự cố gồm {$createdCount} phòng tới quản lý."
        );
    }

    public function emailForm(Request $request, Booking $booking)
    {
        abort_unless($this->bookingCanReportIssue($booking), 410, 'Biểu mẫu chỉ dùng khi booking đang lưu trú.');

        $booking->loadMissing([
            'customer',
            'roomCategory',
            'bookingRooms.room.category',
        ]);

        $activeIssueRoomIds = $this->activeIssueRoomIds($booking);
        $canSubmitAnyRoom = $booking->bookingRooms
            ->filter(fn ($bookingRoom) => $bookingRoom->room)
            ->contains(fn ($bookingRoom) => !$activeIssueRoomIds->contains((int) $bookingRoom->room_id));

        return view('guest-room-issues.form', compact(
            'booking',
            'activeIssueRoomIds',
            'canSubmitAnyRoom'
        ));
    }

    public function storeFromEmail(Request $request, Booking $booking)
    {
        if (!$this->bookingCanReportIssue($booking)) {
            return back()->with('error', 'Biểu mẫu đã hết hiệu lực vì booking không còn ở trạng thái đang lưu trú.');
        }

        $booking->loadMissing('customer');
        $customer = $booking->customer;

        if (!$customer) {
            return back()->with('error', 'Booking không còn thông tin khách hàng để ghi nhận sự cố.');
        }

        try {
            $createdCount = $this->createRoomIssueRequests(
                $request,
                $booking,
                $customer,
                null,
                'Biểu mẫu qua email do lễ tân gửi'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Không thể gửi yêu cầu sự cố: ' . $e->getMessage());
        }

        return back()->with(
            'success',
            "Đã gửi thành công 1 phiếu sự cố gồm {$createdCount} phòng tới quản lý."
        );
    }

    private function createRoomIssueRequests(
        Request $request,
        Booking $booking,
        Customer $customer,
        ?int $userId,
        string $submissionSource
    ): int {
        $booking->loadMissing('bookingRooms.room.category');

        $selectedRoomIds = collect((array) $request->input('selected_room_ids', []))
            ->map(fn ($roomId) => (int) $roomId)
            ->filter(fn ($roomId) => $roomId > 0)
            ->values();

        $rules = [
            'selected_room_ids' => ['required', 'array', 'min:1'],
            'selected_room_ids.*' => ['required', 'integer', 'distinct', 'exists:rooms,id'],
            'issues' => ['required', 'array'],
        ];

        foreach ($selectedRoomIds->unique() as $roomId) {
            $rules["issues.{$roomId}.description"] = ['required', 'string', 'min:10', 'max:2000'];
            $rules["issues.{$roomId}.images"] = ['nullable', 'array', 'max:5'];
            $rules["issues.{$roomId}.images.*"] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:6144'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'selected_room_ids.required' => 'Vui lòng chọn ít nhất một phòng gặp sự cố.',
            'selected_room_ids.min' => 'Vui lòng chọn ít nhất một phòng gặp sự cố.',
            'selected_room_ids.*.distinct' => 'Danh sách phòng bị trùng.',
            'issues.*.description.required' => 'Vui lòng mô tả sự cố của từng phòng đã chọn.',
            'issues.*.description.min' => 'Mô tả sự cố của mỗi phòng cần có ít nhất 10 ký tự.',
            'issues.*.images.max' => 'Mỗi phòng chỉ được tải tối đa 5 ảnh.',
            'issues.*.images.*.image' => 'Minh chứng phải là ảnh hợp lệ.',
            'issues.*.images.*.mimes' => 'Ảnh minh chứng chỉ nhận JPG, JPEG, PNG hoặc WEBP.',
            'issues.*.images.*.max' => 'Mỗi ảnh không được vượt quá 6MB.',
        ]);

        $validated = $validator->validate();
        $selectedRoomIds = collect($validated['selected_room_ids'])
            ->map(fn ($roomId) => (int) $roomId)
            ->unique()
            ->values();

        $bookingRooms = $booking->bookingRooms
            ->filter(fn ($bookingRoom) => $bookingRoom->room)
            ->keyBy(fn ($bookingRoom) => (int) $bookingRoom->room_id);

        $invalidRoomIds = $selectedRoomIds->reject(fn ($roomId) => $bookingRooms->has($roomId));
        if ($invalidRoomIds->isNotEmpty()) {
            $validator->errors()->add('selected_room_ids', 'Có phòng được chọn không thuộc booking này.');
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $activeIssueRoomIds = $this->activeIssueRoomIds($booking);
        $duplicatedRoomIds = $selectedRoomIds->filter(fn ($roomId) => $activeIssueRoomIds->contains($roomId));
        if ($duplicatedRoomIds->isNotEmpty()) {
            $roomNumbers = $bookingRooms
                ->only($duplicatedRoomIds->all())
                ->pluck('room.room_number')
                ->filter()
                ->implode(', ');

            $validator->errors()->add(
                'selected_room_ids',
                'Phòng ' . ($roomNumbers ?: $duplicatedRoomIds->implode(', ')) . ' đang có yêu cầu chưa hoàn tất.'
            );

            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $groupUuid = (string) Str::uuid();

        DB::transaction(function () use (
            $request,
            $booking,
            $customer,
            $userId,
            $submissionSource,
            $selectedRoomIds,
            $bookingRooms,
            $validated,
            $groupUuid
        ) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if (!$this->bookingCanReportIssue($lockedBooking)) {
                throw new \RuntimeException('Booking không còn ở trạng thái đang lưu trú.');
            }

            $nowActiveRoomIds = $this->activeIssueRoomIds($lockedBooking);
            $conflictedRoomIds = $selectedRoomIds->filter(fn ($roomId) => $nowActiveRoomIds->contains($roomId));
            if ($conflictedRoomIds->isNotEmpty()) {
                throw new \RuntimeException('Một trong các phòng vừa được gửi yêu cầu sự cố khác. Vui lòng tải lại form.');
            }

            foreach ($selectedRoomIds as $roomId) {
                $bookingRoom = $bookingRooms->get($roomId);
                $description = trim((string) data_get($validated, "issues.{$roomId}.description"));

                $issue = RoomIssueRequest::create([
                    'booking_id' => $booking->id,
                    'customer_id' => $customer->id,
                    'current_room_id' => $bookingRoom->room_id,
                    'current_room_category_id' => $bookingRoom->room->room_category_id,
                    'issue_description' => $description,
                    'status' => 'pending',
                    'group_uuid' => $groupUuid,
                    'workflow_status' => 'pending',
                ]);

                $images = $request->file("issues.{$roomId}.images", []);
                foreach (is_array($images) ? $images : [$images] as $image) {
                    if (!$image) {
                        continue;
                    }

                    $path = $image->store(
                        'room-issue-evidence/' . $booking->id . '/' . $issue->id,
                        'public'
                    );

                    RoomIssueAttachment::create([
                        'room_issue_request_id' => $issue->id,
                        'path' => $path,
                        'original_name' => $image->getClientOriginalName(),
                        'mime_type' => $image->getMimeType(),
                        'size_bytes' => $image->getSize(),
                    ]);
                }

                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => $userId,
                    'action' => 'room_issue_requested',
                    'description' => $submissionSource
                        . ' báo sự cố tại phòng ' . $bookingRoom->room->room_number
                        . ' và gửi yêu cầu xử lý. Nội dung: ' . $description,
                ]);
            }

            // Giữ phòng thay thế ngay khi khách vừa báo sự cố, không đợi quản lý
            // mở phiếu hoặc gửi phương án sang lễ tân. Mỗi phòng trong nhóm được
            // hệ thống chọn độc lập theo thứ tự cùng hạng -> nâng hạng -> sửa gấp.
            $createdIssues = RoomIssueRequest::where('group_uuid', $groupUuid)
                ->with(['currentRoom.category', 'currentCategory', 'proposedRoom.category'])
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            $lockedBooking->loadMissing(['bookingRooms.room.category']);
            $proposalResult = $this->proposalService->prepareGroup(
                $lockedBooking,
                $createdIssues,
                $userId,
                'proposal_ready',
                true
            );

            $proposalSummary = collect($proposalResult['items'])
                ->map(function (array $item) {
                    $text = 'phòng ' . ($item['current_room_number'] ?: $item['issue_id'])
                        . ': ' . $item['label'];

                    if ($item['target_room_number']) {
                        $text .= ' sang phòng ' . $item['target_room_number'];
                    }

                    return $text;
                })
                ->implode('; ');

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => $userId,
                'action' => 'room_issue_proposal_reserved_immediately',
                'description' => 'Hệ thống lập phương án ngay khi nhận báo cáo: '
                    . $proposalSummary
                    . '. Các phòng thay thế được giữ 30 phút kể từ thời điểm khách báo sự cố.',
            ]);
        });

        return $selectedRoomIds->count();
    }

    private function bookingCanReportIssue(Booking $booking): bool
    {
        return $booking->status === 'checked_in' && (bool) $booking->actual_check_in;
    }

    private function activeIssueRoomIds(Booking $booking): Collection
    {
        return $booking->roomIssueRequests()
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere(function ($repairQuery) {
                        $repairQuery->whereIn('status', ['approved', 'repair_only'])
                            ->where('repair_status', 'waiting');
                    });
            })
            ->pluck('current_room_id')
            ->map(fn ($roomId) => (int) $roomId)
            ->unique()
            ->values();
    }
}
