<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingRoom;
use App\Models\BookingRoomChange;
use App\Models\Room;
use App\Models\RoomActionLog;
use App\Models\RoomIssueRequest;
use App\Models\RoomIssueRoomHold;
use App\Services\BookingPromotionApplicationService;
use App\Services\BookingRepricingService;
use App\Services\RoomIssueProposalService;
use App\Services\PromotionService;
use App\Support\Realtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoomIssueGroupController extends Controller
{
    public function __construct(
        private readonly RoomIssueProposalService $proposalService
    ) {
    }

    public function show(RoomIssueRequest $roomIssueRequest)
    {
        $this->guardManager();
        [$leader, $issues] = $this->loadGroup($roomIssueRequest);
        $booking = $leader->booking;

        // Chỉ dùng để hiển thị dự kiến trước khi quản lý bấm gửi.
        // Khi bấm gửi, backend sẽ kiểm tra lại và chọn lại trong transaction.
        $automaticProposals = collect();
        if (in_array($leader->workflow_status, ['pending', 'proposal_ready', 'guest_requested_change'], true)) {
            $usedTargetRoomIds = [];
            foreach ($issues as $issue) {
                $proposal = $this->proposalService->resolveAutomaticProposal($issue, $booking, $usedTargetRoomIds);
                $automaticProposals->put($issue->id, $proposal);
                if ($proposal['room']) {
                    $usedTargetRoomIds[] = (int) $proposal['room']->id;
                }
            }
        }

        $promotions = $this->availablePromotions($booking, $issues);

        return view('admin.pages.room-issues.group-show', compact(
            'leader',
            'issues',
            'booking',
            'automaticProposals',
            'promotions'
        ));
    }

    /**
     * Hệ thống tự lập phương án theo đúng thứ tự nghiệp vụ:
     * 1) phòng cùng hạng; 2) hạng cao hơn gần nhất; 3) giữ nguyên và sửa gấp.
     * Quản lý không phải tự dò/chọn phòng. Thời gian giữ luôn là 30 phút.
     */
    public function saveProposal(Request $request, RoomIssueRequest $roomIssueRequest)
    {
        $this->guardManager();
        [$leader, $issues] = $this->loadGroup($roomIssueRequest, true);

        $data = $request->validate([
            'issue_promotion_codes_present' => ['nullable', 'boolean'],
            'issue_promotion_codes' => ['nullable', 'array'],
            'issue_promotion_codes.*' => ['nullable', 'array'],
            'issue_promotion_codes.*.*' => ['string', 'max:50'],
            'admin_note_draft' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use ($leader, $issues, $data) {
                $booking = Booking::whereKey($leader->booking_id)->lockForUpdate()->firstOrFail();
                if (!in_array($booking->status, ['checked_in', 'inspection_requested'], true)) {
                    throw new \RuntimeException('Booking không còn ở trạng thái đang lưu trú.');
                }
                $booking->load([
                    'bookingRooms.room.category',
                    'bookingPromotions',
                    'serviceItems',
                ]);

                $availableCodes = $this->availablePromotions($booking, $issues)
                    ->pluck('code')
                    ->map(fn ($code) => strtoupper(trim((string) $code)));
                $codesByIssue = collect();

                foreach ($issues as $issue) {
                    $existingCodes = collect($issue->promotion_codes ?? []);
                    $selectedCodes = (!empty($data['issue_promotion_codes_present'])
                        ? collect(data_get($data, 'issue_promotion_codes.' . $issue->id, []))
                        : collect())
                        ->merge($existingCodes);

                    $selectedCodes = $selectedCodes
                        ->map(fn ($code) => strtoupper(trim((string) $code)))
                        ->filter()
                        ->unique()
                        ->values();

                    $invalidCodes = $selectedCodes->diff($availableCodes->merge($existingCodes)->unique());
                    if ($invalidCodes->isNotEmpty()) {
                        throw new \RuntimeException(
                            'Phòng ' . ($issue->currentRoom?->room_number ?? $issue->current_room_id)
                            . ' có mã không còn đủ điều kiện: ' . $invalidCodes->implode(', ') . '.'
                        );
                    }

                    $codesByIssue->put((int) $issue->id, $selectedCodes);
                }

                // Không xóa phương án đổi phòng chỉ vì khách đã từng chọn sửa gấp.
                // Nếu phòng đang được giữ còn hiệu lực, hệ thống giữ nguyên phòng đó
                // và làm mới hạn 30 phút; nếu hết hạn mới tự dò phòng khác.
                $proposalResult = $this->proposalService->prepareGroup(
                    $booking,
                    $issues,
                    Auth::id(),
                    'waiting_guest_confirmation',
                    true
                );

                foreach ($issues as $issue) {
                    $issue->update([
                        'promotion_codes' => json_encode(
                            $codesByIssue->get((int) $issue->id, collect())->all(),
                            JSON_UNESCAPED_UNICODE
                        ),
                        'admin_note' => array_key_exists('admin_note_draft', $data)
                            ? (trim((string) ($data['admin_note_draft'] ?? '')) ?: null)
                            : $issue->admin_note,
                    ]);
                }

                $proposalLogs = collect($proposalResult['items'])
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
                    'user_id' => Auth::id(),
                    'action' => 'room_issue_auto_proposal_created',
                    'description' => 'Gửi lại các phương án khả dụng cho lễ tân: '
                        . $proposalLogs
                        . '. Phòng thay thế được giữ/làm mới hạn giữ 30 phút đến '
                        . $proposalResult['expires_at']->format('d/m/Y H:i')
                        . '. Mã bù đắp theo phòng: '
                        . $issues->map(function ($issue) use ($codesByIssue) {
                            $codes = $codesByIssue->get((int) $issue->id, collect());
                            return 'phòng ' . ($issue->currentRoom?->room_number ?? $issue->current_room_id)
                                . ': ' . ($codes->isEmpty() ? 'không chọn' : $codes->implode(', '));
                        })->implode('; ') . '.',
                ]);
            });

            Realtime::booking($leader->booking_id, 'room_issue_proposal_sent', false);

            return redirect()->route('admin.room-issues.show', $leader)
                ->with('success', 'Đã gửi phương án mới cho lễ tân. Các mã hỗ trợ đã chọn được khóa theo lần gửi này.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Không thể gửi phương án: ' . $e->getMessage());
        }
    }

    public function receptionistShow(Booking $booking)
    {
        $this->guardReceptionist($booking);

        $latestIssue = $booking->roomIssueRequests()
            ->whereNotNull('group_uuid')
            ->latest('id')
            ->firstOrFail();

        $issues = $booking->roomIssueRequests()
            ->with(['currentRoom.category', 'proposedRoom.category', 'approvedRoom.category'])
            ->where('group_uuid', $latestIssue->group_uuid)
            ->orderBy('id')
            ->get();

        $leader = $issues->first();

        return view('admin.pages.bookings.room-issue-proposal', compact('booking', 'issues', 'leader'));
    }

    public function receptionistRespond(Request $request, Booking $booking)
    {
        $this->guardReceptionist($booking);

        $baseData = $request->validate([
            'response' => ['required', Rule::in(['accepted'])],
            'response_note' => ['nullable', 'string', 'max:2000'],
            'items' => ['nullable', 'array'],
        ]);

        $latestWaitingIssue = $booking->roomIssueRequests()
            ->where('workflow_status', 'waiting_guest_confirmation')
            ->latest('id')
            ->first();

        if (!$latestWaitingIssue) {
            return back()->with('error', 'Không còn phương án nào đang chờ khách xác nhận.');
        }

        $groupUuid = $latestWaitingIssue->group_uuid;

        try {
            DB::transaction(function () use ($booking, $request, $baseData, $groupUuid) {
                $issues = RoomIssueRequest::where('booking_id', $booking->id)
                    ->where('group_uuid', $groupUuid)
                    ->where('workflow_status', 'waiting_guest_confirmation')
                    ->with(['currentRoom', 'proposedRoom.category'])
                    ->lockForUpdate()
                    ->orderBy('id')
                    ->get();

                if ($issues->isEmpty()) {
                    throw new \RuntimeException('Phương án đã được người khác xử lý hoặc đã hết hiệu lực.');
                }

                $now = now('Asia/Ho_Chi_Minh');

                $choiceLogs = [];
                foreach ($issues as $issue) {
                    $choice = (string) data_get($request->input('items', []), $issue->id . '.choice', '');
                    $allowedChoices = ['repair_only'];
                    if (in_array($issue->proposed_resolution_type, ['same_category', 'upgrade_category'], true)
                        && $issue->proposed_room_id) {
                        $allowedChoices[] = $issue->proposed_resolution_type;
                    }

                    if (!in_array($choice, $allowedChoices, true)) {
                        throw new \RuntimeException('Vui lòng chọn phương án hợp lệ cho phòng '
                            . ($issue->currentRoom?->room_number ?? $issue->current_room_id) . '.');
                    }

                    if ($choice !== 'repair_only') {
                        $hold = RoomIssueRoomHold::where('group_uuid', $groupUuid)
                            ->where('room_issue_request_id', $issue->id)
                            ->where('room_id', $issue->proposed_room_id)
                            ->whereNull('released_at')
                            ->where('expires_at', '>', $now)
                            ->lockForUpdate()
                            ->first();

                        if (!$hold) {
                            throw new \RuntimeException('Phòng giữ cho phòng '
                                . ($issue->currentRoom?->room_number ?? $issue->current_room_id)
                                . ' đã hết 30 phút. Vui lòng nhờ quản lý tạo lại phương án.');
                        }
                    }

                    $issue->update([
                        'workflow_status' => 'guest_accepted',
                        'guest_response' => 'accepted',
                        'guest_selected_resolution_type' => $choice,
                        'guest_response_note' => $baseData['response_note'] ?? null,
                        'guest_responded_by' => Auth::id(),
                        'guest_responded_at' => $now,
                    ]);

                    $choiceLogs[] = 'phòng '
                        . ($issue->currentRoom?->room_number ?? $issue->current_room_id)
                        . ': ' . $this->resolutionLabel($choice)
                        . ($choice !== 'repair_only' && $issue->proposedRoom
                            ? ' sang phòng ' . $issue->proposedRoom->room_number
                            : '');
                }

                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => Auth::id(),
                    'action' => 'guest_selected_room_issue_resolutions',
                    'description' => 'Lễ tân ghi nhận lựa chọn của khách: ' . implode('; ', $choiceLogs)
                        . (trim((string) ($baseData['response_note'] ?? '')) !== ''
                            ? '. Ghi chú: ' . $baseData['response_note']
                            : '.'),
                ]);
            });

            Realtime::booking($booking, 'room_issue_guest_updated', false);

            return back()->with(
                'success',
                'Đã ghi nhận lựa chọn của khách. Quản lý đã nhận thông báo cập nhật mới.'
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Không thể ghi nhận phản hồi: ' . $e->getMessage());
        }
    }

    public function finalize(
        Request $request,
        RoomIssueRequest $roomIssueRequest,
        BookingPromotionApplicationService $promotionApplication,
        BookingRepricingService $repricingService
    ) {
        $this->guardManager();

        $data = $request->validate([
            'issue_promotion_codes_present' => ['nullable', 'boolean'],
            'issue_promotion_codes' => ['nullable', 'array'],
            'issue_promotion_codes.*' => ['nullable', 'array'],
            'issue_promotion_codes.*.*' => ['string', 'max:50'],
            'admin_note' => ['required', 'string', 'max:2000'],
            'guest_response_snapshot' => ['required', 'string', 'max:40'],
        ]);

        [$leader, $issues] = $this->loadGroup($roomIssueRequest, true);
        $latestGuestResponseAt = optional($issues->max('guest_responded_at'))->format('Y-m-d H:i:s');
        if (($data['guest_response_snapshot'] ?? '') !== (string) $latestGuestResponseAt) {
            return back()->with('error', 'Lễ tân hoặc khách vừa cập nhật lựa chọn sau khi bạn mở trang. Hãy tải lại và xem nội dung mới trước khi xác nhận.');
        }
        if ($issues->contains(fn ($issue) => $issue->workflow_status !== 'guest_accepted'
            || !$issue->guest_selected_resolution_type)) {
            return back()->with('error', 'Chỉ được xác nhận cuối sau khi lễ tân ghi nhận lựa chọn của khách cho từng phòng.');
        }

        try {
            DB::transaction(function () use ($leader, $issues, $data, $promotionApplication, $repricingService) {
                $booking = Booking::whereKey($leader->booking_id)->lockForUpdate()->firstOrFail();
                if (!in_array($booking->status, ['checked_in', 'inspection_requested'], true)) {
                    throw new \RuntimeException('Booking không còn ở trạng thái đang lưu trú.');
                }

                $booking->load([
                    'bookingRooms.room.category',
                    'bookingPromotions',
                    'serviceItems',
                    'roomChanges',
                ]);

                $logs = [];
                $promotionLogs = [];
                $pendingPromotionApplications = [];
                $availableCodes = $this->availablePromotions($booking, $issues)
                    ->pluck('code')
                    ->map(fn ($code) => strtoupper(trim((string) $code)));

                // Giai đoạn 1: thực hiện thay đổi phòng và ghi đúng giá phòng mới.
                // Chưa áp mã ngay để có thể tính lại toàn bộ booking một lần sau khi
                // tất cả phòng đã được đổi, tránh một phòng làm sai tổng của phòng khác.
                foreach ($issues as $issue) {
                    $processed = $this->processIssue($issue, $booking, $data['admin_note']);
                    $logs[] = $processed['log'];

                    $existingCodes = collect($issue->promotion_codes ?? []);
                    $codes = (!empty($data['issue_promotion_codes_present'])
                        ? collect(data_get($data, 'issue_promotion_codes.' . $issue->id, []))
                        : collect())
                        ->merge($existingCodes);
                    $codes = $codes
                        ->map(fn ($code) => strtoupper(trim((string) $code)))
                        ->filter()
                        ->unique()
                        ->values();

                    $invalidCodes = $codes->diff($availableCodes->merge($existingCodes)->unique());
                    if ($invalidCodes->isNotEmpty()) {
                        throw new \RuntimeException(
                            'Phòng ' . ($issue->currentRoom?->room_number ?? $issue->current_room_id)
                            . ' có mã không còn đủ điều kiện: ' . $invalidCodes->implode(', ') . '.'
                        );
                    }

                    $pendingPromotionApplications[] = [
                        'issue' => $issue,
                        'booking_room_id' => (int) $processed['booking_room_id'],
                        'codes' => $codes,
                    ];
                }

                // Giai đoạn 2: tính lại tiền phòng, dịch vụ và toàn bộ mã đang có.
                // Mã toàn booking hoặc mã của một phòng bị mất điều kiện sau khi đổi
                // phòng/hạng sẽ được gỡ đúng phạm vi trước khi áp mã sự cố mới.
                $booking->refresh()->load([
                    'bookingRooms.room.category',
                    'bookingPromotions.promotion.serviceOffers.service',
                    'bookingPromotions.promotion.roomUpgradeOffers',
                    'bookingPromotions.serviceOffers',
                    'bookingPromotions.roomUpgradeOffers.offer',
                    'serviceItems.service',
                    'payments',
                    'customer',
                    'guests',
                    'roomChanges',
                ]);

                $oneNightRoomTotal = (float) $booking->bookingRooms->sum(
                    fn (BookingRoom $room) => (float) $room->price_at_booking
                );
                $repricingPreview = $repricingService->preview(
                    $booking,
                    Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh'),
                    Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh'),
                    $oneNightRoomTotal
                );
                $repricingService->apply($booking, $repricingPreview);

                // Giai đoạn 3: áp mã bù đắp riêng cho từng phòng lỗi. Chênh lệch
                // giá phòng đã tồn tại thật; mã nâng hạng/giảm tiền quyết định phần
                // khách sạn hỗ trợ, phần còn lại khách phải thanh toán.
                foreach ($pendingPromotionApplications as $application) {
                    /** @var RoomIssueRequest $issue */
                    $issue = $application['issue'];
                    $codes = $application['codes'];
                    $bookingRoom = BookingRoom::with('room.category')
                        ->whereKey($application['booking_room_id'])
                        ->where('booking_id', $booking->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($codes->isNotEmpty()) {
                        $booking->refresh();
                        $booking->unsetRelation('roomChanges');
                        $booking->unsetRelation('bookingPromotions');
                        $booking->unsetRelation('serviceItems');
                        $promotionApplication->apply(
                            $booking,
                            $codes->all(),
                            $data['admin_note'],
                            Auth::id(),
                            true,
                            $bookingRoom,
                            $issue
                        );
                    }

                    $issue->update([
                        'promotion_codes' => json_encode($codes->all(), JSON_UNESCAPED_UNICODE),
                        'admin_note' => $data['admin_note'],
                        'workflow_status' => 'approved',
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now('Asia/Ho_Chi_Minh'),
                    ]);

                    $promotionLogs[] = 'phòng '
                        . ($issue->currentRoom?->room_number ?? $issue->current_room_id)
                        . ': ' . ($codes->isEmpty() ? 'không áp mã, khách chịu chênh lệch nếu có' : $codes->implode(', '));
                }

                RoomIssueRoomHold::where('group_uuid', $leader->group_uuid)
                    ->whereNull('released_at')
                    ->update([
                        'released_at' => now('Asia/Ho_Chi_Minh'),
                        'release_reason' => 'Đã xác nhận và thực hiện phương án',
                    ]);

                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => Auth::id(),
                    'action' => 'manager_finalized_room_issue_group',
                    'description' => 'Quản lý xác nhận cuối phương án sự cố: ' . implode('; ', $logs)
                        . '. Mã theo từng phòng: ' . implode('; ', $promotionLogs) . '.',
                ]);
            });

            Realtime::booking($leader->booking_id, 'room_issue_finalized', false);

            return redirect()->route('admin.room-issues.show', $leader)
                ->with('success', 'Đã thực hiện toàn bộ phương án. Buồng phòng nhận công việc riêng cho từng phòng.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Không thể xác nhận cuối: ' . $e->getMessage());
        }
    }

    private function processIssue(RoomIssueRequest $issue, Booking $booking, string $note): array
    {
        $issue = RoomIssueRequest::whereKey($issue->id)->lockForUpdate()->firstOrFail();
        $issue->load(['currentRoom.category', 'proposedRoom.category']);

        $oldRoom = Room::with('category')->lockForUpdate()->findOrFail($issue->current_room_id);
        $bookingRoom = BookingRoom::where('booking_id', $booking->id)
            ->where('room_id', $oldRoom->id)
            ->lockForUpdate()
            ->first();

        if (!$bookingRoom) {
            throw new \RuntimeException('Phòng ' . $oldRoom->room_number . ' không còn thuộc booking.');
        }

        $resolution = $issue->guest_selected_resolution_type;
        $newRoom = null;

        if ($resolution !== 'repair_only') {
            if ($resolution !== $issue->proposed_resolution_type || !$issue->proposed_room_id) {
                throw new \RuntimeException('Phương án phòng ' . $oldRoom->room_number . ' không còn khớp với lựa chọn của khách.');
            }

            $hold = RoomIssueRoomHold::where('group_uuid', $issue->group_uuid)
                ->where('room_issue_request_id', $issue->id)
                ->where('room_id', $issue->proposed_room_id)
                ->whereNull('released_at')
                ->where('expires_at', '>', now('Asia/Ho_Chi_Minh'))
                ->lockForUpdate()
                ->first();

            if (!$hold) {
                throw new \RuntimeException('Phòng giữ cho ' . $oldRoom->room_number
                    . ' đã hết 30 phút. Hãy tạo lại phương án và trao đổi lại với khách.');
            }

            $newRoom = Room::with('category')->lockForUpdate()->findOrFail($hold->room_id);
            $remainingStart = now('Asia/Ho_Chi_Minh')->startOfDay();
            $remainingEnd = Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->startOfDay();
            $nightCount = max(1, $remainingStart->diffInDays($remainingEnd));
            $oldPrice = (float) $bookingRoom->price_at_booking;
            $newPrice = (float) ($newRoom->category?->price ?? $bookingRoom->price_at_booking);
            $difference = max(0, ($newPrice - $oldPrice) * $nightCount);

            $bookingRoom->update([
                'room_id' => $newRoom->id,
                // Luôn ghi giá phòng/hạng mới. Phần chênh lệch chỉ được bù khi
                // quản lý chọn mã hỗ trợ phù hợp; không tự âm thầm giữ giá cũ.
                'price_at_booking' => $newPrice,
                'surcharge_reason' => 'Đổi phòng do sự cố: ' . $note,
            ]);

            BookingRoomChange::create([
                'booking_id' => $booking->id,
                'booking_room_id' => $bookingRoom->id,
                'room_issue_request_id' => $issue->id,
                'old_room_id' => $oldRoom->id,
                'new_room_id' => $newRoom->id,
                'old_room_category_id' => $oldRoom->room_category_id,
                'new_room_category_id' => $newRoom->room_category_id,
                'old_room_price' => $oldPrice,
                'new_room_price' => $newPrice,
                'night_count' => $nightCount,
                'price_difference_total' => $difference,
                'change_source' => 'incident',
                'reason' => $note,
                'changed_by' => Auth::id(),
            ]);

            $newRoom->update([
                'status' => 'occupied',
                'status_from' => null,
                'status_until' => null,
            ]);

            if ((int) $booking->room_quantity === 1) {
                $booking->update(['room_category_id' => $newRoom->room_category_id]);
            }

            $text = $resolution === 'same_category'
                ? 'phòng ' . $oldRoom->room_number . ' đổi cùng hạng sang phòng ' . $newRoom->room_number
                : 'phòng ' . $oldRoom->room_number . ' nâng hạng sang phòng ' . $newRoom->room_number
                    . ' (' . ($newRoom->category?->name ?? '---') . ')';
        } else {
            $text = 'phòng ' . $oldRoom->room_number . ' giữ nguyên và sửa gấp';
        }

        $oldRoom->update([
            'status' => 'maintenance',
            'status_from' => now('Asia/Ho_Chi_Minh'),
            'status_until' => null,
            'note' => ($resolution === 'repair_only'
                ? 'Sửa gấp khi khách vẫn đang ở: '
                : 'Khách đã chuyển phòng, cần sửa: ')
                . $issue->issue_description,
        ]);

        $issue->update([
            'status' => $newRoom ? 'approved' : 'repair_only',
            'resolution_type' => $resolution === 'repair_only' ? 'no_room' : $resolution,
            'approved_room_id' => $newRoom?->id,
            'approved_room_category_id' => $newRoom?->room_category_id,
            'price_difference_per_night' => $newRoom
                ? max(0, (float) ($newRoom->category?->price ?? 0) - (float) ($oldRoom->category?->price ?? 0))
                : 0,
            'repair_status' => 'waiting',
        ]);

        RoomActionLog::create([
            'room_id' => $oldRoom->id,
            'user_id' => Auth::id(),
            'action_type' => 'maintenance_support',
            'action_time' => now('Asia/Ho_Chi_Minh'),
            'note' => 'Booking ' . $booking->booking_code . '; ' . $text
                . '. Nội dung: ' . $issue->issue_description,
        ]);

        return [
            'log' => $text,
            'booking_room_id' => (int) $bookingRoom->id,
        ];
    }

    private function loadGroup(RoomIssueRequest $request, bool $lock = false): array
    {
        $query = RoomIssueRequest::query()
            ->where('group_uuid', $request->group_uuid ?: $request->id);

        if (!$request->group_uuid) {
            $query->whereKey($request->id);
        }
        if ($lock) {
            $query->lockForUpdate();
        }

        $issues = $query->with([
            'booking.customer',
            'booking.bookingRooms.room.category',
            'booking.bookingPromotions',
            'booking.serviceItems',
            'currentRoom.category',
            'currentCategory',
            'proposedRoom.category',
            'approvedRoom.category',
            'attachments',
            'reviewer',
            'proposalCreator',
            'guestResponder',
        ])->orderBy('id')->get();

        abort_if($issues->isEmpty(), 404);

        return [$issues->first(), $issues];
    }

    private function availablePromotions(Booking $booking, $issues)
    {
        $booking->loadMissing(['bookingPromotions', 'serviceItems']);

        $discount = (float) ($booking->discount_amount ?? 0);
        $subtotal = (float) ($booking->subtotal_amount ?? 0);
        if ($subtotal <= 0) {
            $subtotal = (float) $booking->estimated_total + $discount;
        }

        $nightCount = max(
            1,
            Carbon::parse($booking->check_in_date)
                ->diffInDays(Carbon::parse($booking->check_out_date))
        );

        $context = [
            'booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'customer_email' => $booking->customer_email_snapshot ?: $booking->customer?->email,
            'customer_phone' => $booking->customer_phone_snapshot ?: $booking->customer?->phone,
            'customer_cccd' => $booking->customer_cccd_snapshot ?: $booking->customer?->cccd,
            'subtotal_amount' => $subtotal,
            'service_items' => $booking->serviceItems->toArray(),
            'check_in_at' => $booking->check_in_at,
            'check_out_at' => $booking->check_out_at,
            'night_count' => $nightCount,
            'room_quantity' => $booking->room_quantity,
        ];

        // Mã đã được áp hoặc đã gắn vào bất kỳ phương án sự cố nào của nhóm
        // không được chọn lại ở lần gửi sau. Mã cũ vẫn được hiển thị như mã đã khóa.
        $usedCodes = $booking->bookingPromotions
            ->pluck('code_snapshot')
            ->merge(collect($issues)->flatMap(fn ($issue) => $issue->promotion_codes ?? []))
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->unique();

        return app(PromotionService::class)
            ->availablePromotions($context, 'admin')
            ->reject(fn ($promotion) => $usedCodes->contains(strtoupper($promotion->code)))
            ->values();
    }

    private function resolutionLabel(?string $resolution): string
    {
        return match ($resolution) {
            'same_category' => 'đổi phòng cùng hạng',
            'upgrade_category' => 'nâng hạng miễn phí',
            'repair_only' => 'giữ nguyên phòng và sửa gấp',
            default => 'chưa chọn',
        };
    }

    private function guardManager(): void
    {
        abort_unless(in_array(Auth::user()?->role, ['super_admin', 'manager'], true), 403);
    }

    private function guardReceptionist(Booking $booking): void
    {
        $role = Auth::user()?->role;
        abort_unless(in_array($role, [
            'super_admin',
            'manager',
            'receptionist_lead',
            'receptionist',
        ], true), 403);
    }
}
