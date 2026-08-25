<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingPromotion;
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
use App\Services\HotelPolicyService;
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

        if ($issues->where('status', 'pending')->contains('workflow_status', 'awaiting_housekeeping')) {
            return redirect()->route('admin.room-issues.index')
                ->with('warning', 'Buồng phòng chưa kiểm tra xong tất cả phòng trong lần báo sự cố này.');
        }

        // Chỉ dùng để hiển thị dự kiến trước khi quản lý bấm gửi.
        // Chỉ sự cố đã được buồng phòng xác nhận mới được dò/giữ phòng thay thế.
        $automaticProposals = collect();
        $usedTargetRoomIds = [];
        foreach ($issues->where('status', 'pending') as $issue) {
            if (!in_array($issue->workflow_status, ['housekeeping_verified', 'proposal_ready', 'guest_requested_change'], true)) {
                continue;
            }

            $proposal = $this->proposalService->resolveAutomaticProposal($issue, $booking, $usedTargetRoomIds);
            $automaticProposals->put($issue->id, $proposal);
            if ($proposal['room']) {
                $usedTargetRoomIds[] = (int) $proposal['room']->id;
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
     * Hệ thống ưu tiên phòng cùng hạng rồi hạng cao hơn. Chỉ cho giữ nguyên
     * và sửa tại phòng khi buồng phòng đã xác nhận có thể sửa ngay tại phòng.
     * Nếu lỗi thật nhưng không thể sửa tại chỗ thì khách bắt buộc phải chuyển.
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
            'resolution_preference' => ['nullable', 'array'],
            'resolution_preference.*' => ['nullable', Rule::in(['auto', 'repair_only'])],
            'admin_note_draft' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use ($leader, $issues, $data) {
                $booking = Booking::whereKey($leader->booking_id)->lockForUpdate()->firstOrFail();
                $holdMinutes = max(5, (int) app(HotelPolicyService::class)
                    ->forBooking($booking, 'room_issue.proposal_hold_minutes', 30));
                if (!in_array($booking->status, ['checked_in', 'inspection_requested'], true)) {
                    throw new \RuntimeException('Booking không còn ở trạng thái đang lưu trú.');
                }
                $booking->load([
                    'bookingRooms.room.category',
                    'bookingPromotions',
                    'serviceItems',
                ]);

                $issues = $issues->where('status', 'pending')->values();
                if ($issues->isEmpty()) {
                    throw new \RuntimeException('Không còn sự cố nào đang chờ xử lý.');
                }
                if ($issues->contains('workflow_status', 'awaiting_housekeeping')) {
                    throw new \RuntimeException('Buồng phòng chưa kiểm tra xong tất cả phòng.');
                }
                if ($issues->contains('workflow_status', 'housekeeping_not_found')) {
                    throw new \RuntimeException('Có phòng buồng phòng không phát hiện sự cố. Hãy từ chối/đóng phiếu đó trước khi lập phương án cho các phòng còn lại.');
                }
                if ($issues->contains(fn ($issue) => !in_array($issue->workflow_status, [
                    'housekeeping_verified', 'proposal_ready', 'guest_requested_change',
                ], true))) {
                    throw new \RuntimeException('Trạng thái sự cố đã thay đổi. Hãy tải lại trang trước khi gửi phương án.');
                }

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
                // và làm mới hạn theo policy; nếu hết hạn mới tự dò phòng khác.
                $proposalResult = $this->proposalService->prepareGroup(
                    $booking,
                    $issues,
                    Auth::id(),
                    'waiting_guest_confirmation',
                    true
                );

                foreach ($issues as $issue) {
                    $preference = (string) data_get($data, 'resolution_preference.' . $issue->id, 'auto');
                    if ($preference === 'repair_only') {
                        if (!$issue->housekeeping_can_repair_in_room) {
                            throw new \RuntimeException('Buồng phòng chưa xác nhận có thể sửa tại phòng ' . ($issue->currentRoom?->room_number ?? $issue->current_room_id) . '.');
                        }

                        RoomIssueRoomHold::where('room_issue_request_id', $issue->id)
                            ->whereNull('released_at')
                            ->update([
                                'released_at' => now('Asia/Ho_Chi_Minh'),
                                'release_reason' => 'Quản lý chọn sửa tại phòng',
                            ]);

                        // prepareGroup() vừa có thể cập nhật phương án đổi phòng trực tiếp trong DB.
                        // Dùng query update thay vì model $issue đang stale để chắc chắn xóa sạch
                        // phòng đề xuất khi quản lý chuyển sang phương án sửa tại phòng.
                        RoomIssueRequest::whereKey($issue->id)->update([
                            'proposed_resolution_type' => 'repair_only',
                            'proposed_room_id' => null,
                            'proposed_room_category_id' => null,
                            'proposal_note' => 'Buồng phòng xác nhận có thể sửa tại phòng. Quản lý chọn phương án giữ nguyên phòng và sửa gấp.',
                            'proposal_created_by' => Auth::id(),
                            'proposal_created_at' => now('Asia/Ho_Chi_Minh'),
                            'proposal_expires_at' => null,
                            'workflow_status' => 'waiting_guest_confirmation',
                        ]);
                        $issue->refresh();
                    }
                }

                foreach ($issues as $issue) {
                    $issue->update([
                        'promotion_codes' => $codesByIssue->get((int) $issue->id, collect())->all(),
                        'admin_note' => array_key_exists('admin_note_draft', $data)
                            ? (trim((string) ($data['admin_note_draft'] ?? '')) ?: null)
                            : $issue->admin_note,
                    ]);
                }

                $issues->each->refresh();
                $issues->load(['currentRoom', 'proposedRoom.category']);
                $proposalLogs = $issues->map(function (RoomIssueRequest $issue) {
                    $text = 'phòng ' . ($issue->currentRoom?->room_number ?? $issue->current_room_id)
                        . ': ' . $this->resolutionLabel($issue->proposed_resolution_type);
                    if ($issue->proposedRoom) {
                        $text .= ' sang phòng ' . $issue->proposedRoom->room_number;
                    }
                    return $text;
                })->implode('; ');

                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => Auth::id(),
                    'action' => 'room_issue_auto_proposal_created',
                    'description' => 'Gửi lại các phương án khả dụng cho lễ tân: '
                        . $proposalLogs
                        . '. Nếu có đổi phòng, phòng thay thế được giữ ' . $holdMinutes . ' phút đến '
                        . $proposalResult['expires_at']->format('d/m/Y H:i')
                        . '; phương án sửa tại phòng không giữ phòng khác. Mã bù đắp theo phòng: '
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

    public function reject(Request $request, RoomIssueRequest $roomIssueRequest)
    {
        $this->guardManager();
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do từ chối/đóng yêu cầu.',
            'reason.min' => 'Lý do cần có ít nhất 5 ký tự.',
        ]);

        try {
            $redirectIssue = DB::transaction(function () use ($roomIssueRequest, $data) {
                $issue = RoomIssueRequest::whereKey($roomIssueRequest->id)->lockForUpdate()->firstOrFail();
                if ($issue->status !== 'pending') {
                    throw new \RuntimeException('Yêu cầu này đã được xử lý.');
                }
                if ($issue->workflow_status !== 'housekeeping_not_found') {
                    throw new \RuntimeException(
                        'Chỉ được đóng yêu cầu khi buồng phòng kết luận không phát hiện sự cố. '
                        . 'Nếu đã xác nhận có lỗi, khách sạn phải xử lý bằng sửa tại phòng khi có thể hoặc chuyển phòng cho khách.'
                    );
                }

                RoomIssueRoomHold::where('room_issue_request_id', $issue->id)
                    ->whereNull('released_at')
                    ->update([
                        'released_at' => now('Asia/Ho_Chi_Minh'),
                        'release_reason' => 'Quản lý từ chối/đóng yêu cầu sự cố',
                    ]);

                $issue->update([
                    'status' => 'rejected',
                    'workflow_status' => 'rejected',
                    'admin_note' => trim($data['reason']),
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now('Asia/Ho_Chi_Minh'),
                    'proposal_expires_at' => null,
                ]);

                BookingLog::create([
                    'booking_id' => $issue->booking_id,
                    'user_id' => Auth::id(),
                    'action' => 'manager_rejected_room_issue',
                    'description' => 'Quản lý từ chối/đóng sự cố phòng '
                        . ($issue->currentRoom?->room_number ?? $issue->current_room_id)
                        . '. Lý do: ' . trim($data['reason']),
                ]);

                return RoomIssueRequest::query()
                    ->where('group_uuid', $issue->group_uuid)
                    ->where('status', 'pending')
                    ->orderBy('id')
                    ->first();
            });

            Realtime::booking($roomIssueRequest->booking_id, 'room_issue_rejected', false);

            if ($redirectIssue) {
                return redirect()->route('admin.room-issues.show', $redirectIssue)
                    ->with('success', 'Đã từ chối/đóng yêu cầu. Các phòng còn lại trong nhóm vẫn tiếp tục xử lý.');
            }

            return redirect()->route('admin.room-issues.index')
                ->with('success', 'Đã từ chối/đóng yêu cầu sự cố.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Không thể từ chối yêu cầu: ' . $e->getMessage());
        }
    }

    public function receptionistShow(Booking $booking)
    {
        $this->guardReceptionist($booking);

        $latestIssue = $booking->roomIssueRequests()
            ->where('status', 'pending')
            ->where('workflow_status', 'waiting_guest_confirmation')
            ->whereNotNull('group_uuid')
            ->latest('id')
            ->first();

        if (!$latestIssue) {
            return redirect()->route('admin.bookings.show', $booking)
                ->with('info', 'Không còn phương án sự cố nào đang chờ trao đổi với khách.');
        }

        $issues = $booking->roomIssueRequests()
            ->with(['currentRoom.category', 'proposedRoom.category', 'approvedRoom.category'])
            ->where('group_uuid', $latestIssue->group_uuid)
            ->where('status', 'pending')
            ->where('workflow_status', 'waiting_guest_confirmation')
            ->orderBy('id')
            ->get();

        if ($issues->isEmpty()) {
            return redirect()->route('admin.bookings.show', $booking)
                ->with('info', 'Phương án sự cố đã được xử lý.');
        }

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
            ->where('status', 'pending')
            ->where('workflow_status', 'waiting_guest_confirmation')
            ->latest('id')
            ->first();

        if (!$latestWaitingIssue) {
            return redirect()->route('admin.bookings.show', $booking)
                ->with('info', 'Không còn phương án nào đang chờ khách xác nhận.');
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
                    $allowedChoices = [];
                    if ($issue->housekeeping_can_repair_in_room) {
                        $allowedChoices[] = 'repair_only';
                    }
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
                                . ' đã hết thời hạn giữ phòng. Vui lòng nhờ quản lý tạo lại phương án.');
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

            return redirect()->route('admin.bookings.show', $booking)
                ->with('success', 'Đã ghi nhận lựa chọn của khách. Quản lý đã nhận thông báo cập nhật mới.');
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
        $issues = $issues->where('status', 'pending')->values();
        if ($issues->isEmpty()) {
            return back()->with('error', 'Không còn sự cố nào đang chờ xác nhận cuối.');
        }
        $leader = $issues->first();
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
                // Giá nâng hạng do sự cố đã được khách sạn chịu bằng cách giữ nguyên đơn giá
                // khách đã chốt. Mã ở đây chỉ là hỗ trợ/bồi thường bổ sung nếu quản lý chọn.
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
                        'promotion_codes' => $codes->all(),
                        'admin_note' => $data['admin_note'],
                        'workflow_status' => 'approved',
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now('Asia/Ho_Chi_Minh'),
                    ]);

                    $promotionLogs[] = 'phòng '
                        . ($issue->currentRoom?->room_number ?? $issue->current_room_id)
                        . ': ' . ($codes->isEmpty() ? 'không áp mã bổ sung' : $codes->implode(', '));
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

            return redirect()->route('admin.bookings.show', $leader->booking_id)
                ->with('success', 'Đã thực hiện toàn bộ phương án sự cố và cập nhật phòng cho booking.');
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

        if ($resolution === 'repair_only' && !$issue->housekeeping_can_repair_in_room) {
            throw new \RuntimeException(
                'Phòng ' . $oldRoom->room_number
                . ' đã được buồng phòng xác nhận có lỗi nhưng không thể sửa ngay tại phòng; khách bắt buộc phải chuyển phòng.'
            );
        }

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
                    . ' đã hết thời hạn giữ phòng. Hãy tạo lại phương án và trao đổi lại với khách.');
            }

            $newRoom = Room::with('category')->lockForUpdate()->findOrFail($hold->room_id);
            $checkInDay = Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->startOfDay();
            $checkOutDay = Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->startOfDay();
            $changeDay = now('Asia/Ho_Chi_Minh')->startOfDay();
            $totalNightCount = max(1, $checkInDay->diffInDays($checkOutDay));
            $elapsedNightCount = min($totalNightCount, max(0, $checkInDay->diffInDays($changeDay)));
            $remainingNightCount = max(0, $totalNightCount - $elapsedNightCount);
            $oldPrice = (float) $bookingRoom->price_at_booking;
            $marketNewPrice = (float) ($newRoom->category?->price ?? $oldPrice);
            $isUpgrade = (int) $newRoom->room_category_id !== (int) $oldRoom->room_category_id
                && $marketNewPrice > $oldPrice;

            // Sự cố phía khách sạn không được biến thành một lần upsell bắt khách trả tiền.
            // booking_room tiếp tục giữ đúng đơn giá khách đã chốt; giá thị trường của
            // phòng thay thế chỉ được lưu ở lịch sử đổi phòng để có thể đối chiếu.
            $bookingRoom->update([
                'room_id' => $newRoom->id,
                'price_at_booking' => $oldPrice,
                'surcharge_reason' => substr(
                    trim(($bookingRoom->surcharge_reason ? $bookingRoom->surcharge_reason . ' | ' : '')
                        . 'Đổi phòng do sự cố: ' . $note
                        . ($isUpgrade ? ' | Khách sạn nâng hạng miễn phí, giữ nguyên đơn giá đã chốt.' : '')),
                    0,
                    255
                ),
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
                'new_room_price' => $marketNewPrice,
                'night_count' => $remainingNightCount,
                'price_difference_total' => 0,
                'change_source' => 'incident',
                'reason' => $note . ($isUpgrade ? ' | Nâng hạng miễn phí do lỗi/sự cố phía khách sạn.' : ''),
                'changed_by' => Auth::id(),
            ]);

            // Đây là chênh lệch khách phải trả, không phải chênh giá niêm yết.
            $newPrice = $oldPrice;

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

        if ($resolution === 'repair_only') {
            // Khách vẫn ở nguyên phòng trong lúc buồng phòng/kỹ thuật xử lý.
            // Không được chuyển phòng sang maintenance vì trạng thái đó sẽ làm sai
            // công suất và các màn hình theo dõi phòng đang có khách.
            $oldRoom->update([
                'status' => 'occupied',
                'status_from' => null,
                'status_until' => null,
                'note' => 'Sửa gấp khi khách vẫn đang ở: ' . $issue->issue_description,
            ]);
        } else {
            $oldRoom->update([
                'status' => 'maintenance',
                'status_from' => now('Asia/Ho_Chi_Minh'),
                'status_until' => null,
                'note' => 'Khách đã chuyển phòng, cần sửa: ' . $issue->issue_description,
            ]);
        }

        $issue->update([
            'status' => $newRoom ? 'approved' : 'repair_only',
            'resolution_type' => $resolution === 'repair_only' ? 'no_room' : $resolution,
            'approved_room_id' => $newRoom?->id,
            'approved_room_category_id' => $newRoom?->room_category_id,
            'price_difference_per_night' => 0,
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
            'housekeepingVerifier',
        ])->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('id')->get();

        abort_if($issues->isEmpty(), 404);

        return [$issues->first(), $issues];
    }

    private function availablePromotions(Booking $booking, $issues)
    {
        $booking->loadMissing(['bookingPromotions', 'serviceItems', 'customer']);

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

        // Ở luồng bồi thường sự cố không giới hạn số lượng mã theo nhóm loại.
        // Tuy nhiên một mã mà chính khách này đã thực sự dùng ở booking trước
        // sẽ không được cấp lại. Nhận diện khách ưu tiên customer_id, sau đó
        // đối chiếu snapshot CCCD / email / điện thoại để không lách bằng tài khoản khác.
        $usedCodes = $booking->bookingPromotions
            ->pluck('code_snapshot')
            ->merge(collect($issues)->flatMap(fn ($issue) => $issue->promotion_codes ?? []))
            ->merge($this->historicalPromotionCodesForCustomer($booking))
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->unique();

        return app(PromotionService::class)
            ->availablePromotions($context, 'admin')
            ->reject(fn ($promotion) => $usedCodes->contains(strtoupper(trim((string) $promotion->code))))
            ->values();
    }

    private function historicalPromotionCodesForCustomer(Booking $booking)
    {
        $customerId = $booking->customer_id ? (int) $booking->customer_id : null;
        $cccd = strtoupper(trim((string) ($booking->customer_cccd_snapshot ?: $booking->customer?->cccd)));
        $email = mb_strtolower(trim((string) ($booking->customer_email_snapshot ?: $booking->customer?->email)));
        $phone = preg_replace('/\D+/', '', (string) ($booking->customer_phone_snapshot ?: $booking->customer?->phone));

        if (!$customerId && $cccd === '' && $email === '' && $phone === '') {
            return collect();
        }

        return BookingPromotion::query()
            ->select('booking_promotions.code_snapshot')
            ->join('bookings', 'bookings.id', '=', 'booking_promotions.booking_id')
            ->whereNull('bookings.deleted_at')
            // Booking đã hủy không được xem là khách đã hưởng ưu đãi.
            ->where('bookings.status', '!=', 'cancelled')
            ->where(function ($query) use ($customerId, $cccd, $email, $phone) {
                $first = true;

                if ($customerId) {
                    $query->where('bookings.customer_id', $customerId);
                    $first = false;
                }

                if ($cccd !== '') {
                    $method = $first ? 'whereRaw' : 'orWhereRaw';
                    $query->{$method}("UPPER(TRIM(COALESCE(bookings.customer_cccd_snapshot, ''))) = ?", [$cccd]);
                    $first = false;
                }

                if ($email !== '') {
                    $method = $first ? 'whereRaw' : 'orWhereRaw';
                    $query->{$method}("LOWER(TRIM(COALESCE(bookings.customer_email_snapshot, ''))) = ?", [$email]);
                    $first = false;
                }

                if ($phone !== '') {
                    $method = $first ? 'whereRaw' : 'orWhereRaw';
                    $query->{$method}(
                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(bookings.customer_phone_snapshot, ''), ' ', ''), '-', ''), '.', ''), '(', ''), ')', '') = ?",
                        [$phone]
                    );
                }
            })
            ->pluck('booking_promotions.code_snapshot');
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
        abort_unless(
            $booking->canBeHandledBy(Auth::user()),
            403,
            'Bạn không được phân công xử lý booking này.'
        );
    }
}
