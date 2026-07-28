<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingRoom;
use App\Models\BookingRoomChange;
use App\Models\Promotion;
use App\Models\PromotionRoomUpgradeOffer;
use App\Models\Room;
use App\Models\RoomActionLog;
use App\Models\RoomIssueAttachment;
use App\Models\RoomIssueRequest;
use App\Services\BookingPromotionApplicationService;
use App\Services\PromotionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RoomIssueRequestController extends Controller
{
    public function index(Request $request)
    {
        $this->guardManager();

        $status = $request->query('status', 'pending');
        $search = trim((string) $request->query('search', ''));

        $representativeIds = RoomIssueRequest::query()
            ->selectRaw('MIN(id)')
            ->groupBy('group_uuid');

        $query = RoomIssueRequest::query()
            ->whereIn('id', $representativeIds)
            ->with(['booking.customer', 'currentRoom.category', 'approvedRoom.category', 'reviewer']);

        if ($status !== 'all') {
            if ($status === 'pending') {
                $query->whereIn('workflow_status', ['pending','proposal_ready','waiting_guest_confirmation','guest_accepted','guest_requested_change']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('booking', fn ($b) => $b->where('booking_code', 'like', "%{$search}%"))
                    ->orWhereHas('currentRoom', fn ($r) => $r->where('room_number', 'like', "%{$search}%"))
                    ->orWhere('issue_description', 'like', "%{$search}%");
            });
        }

        $issues = $query->latest()->paginate(15)->withQueryString();
        $pendingCount = RoomIssueRequest::query()->whereIn('id', $representativeIds)->whereIn('workflow_status', ['pending','proposal_ready','waiting_guest_confirmation','guest_accepted','guest_requested_change'])->count();

        return view('admin.pages.room-issues.index', compact('issues', 'pendingCount', 'status', 'search'));
    }

    public function show(RoomIssueRequest $roomIssueRequest)
    {
        $this->guardManager();

        $roomIssueRequest->load([
            'booking.customer',
            'booking.bookingRooms.room.category',
            'booking.bookingPromotions',
            'booking.serviceItems',
            'currentRoom.category',
            'currentCategory',
            'approvedRoom.category',
            'reviewer',
            'repairCompleter',
            'attachments',
            'roomChanges.oldRoom.category',
            'roomChanges.newRoom.category',
        ]);

        $proposal = $roomIssueRequest->status === 'pending'
            ? $this->resolveProposal($roomIssueRequest)
            : [
                'type' => $roomIssueRequest->resolution_type ?: 'no_room',
                'room' => $roomIssueRequest->approvedRoom,
                'label' => match ($roomIssueRequest->resolution_type) {
                    'same_category' => 'Đổi phòng cùng hạng',
                    'upgrade_category' => 'Đổi hạng phòng',
                    default => 'Không còn phòng để đổi',
                },
                'description' => $roomIssueRequest->admin_note,
            ];
        $booking = $roomIssueRequest->booking;
        $currentDiscount = (float) ($booking->discount_amount ?? 0);
        $subtotalAmount = (float) ($booking->subtotal_amount ?? 0);
        if ($subtotalAmount <= 0) {
            $subtotalAmount = (float) $booking->estimated_total + $currentDiscount;
        }

        $nightCount = max(
            1,
            Carbon::parse($booking->check_in_date, 'Asia/Ho_Chi_Minh')
                ->diffInDays(Carbon::parse($booking->check_out_date, 'Asia/Ho_Chi_Minh'))
        );

        $promotionContext = [
            'customer_id' => $booking->customer_id,
            'customer_email' => $booking->customer_email_snapshot ?: $booking->customer?->email,
            'customer_phone' => $booking->customer_phone_snapshot ?: $booking->customer?->phone,
            'customer_cccd' => $booking->customer_cccd_snapshot ?: $booking->customer?->cccd,
            'subtotal_amount' => $subtotalAmount,
            'service_items' => $booking->serviceItems
                ->reject(fn ($item) => in_array($item->billing_status, ['unused', 'cancelled'], true))
                ->map(fn ($item) => [
                    'service_id' => $item->service_id,
                    'name' => $item->name,
                    'type' => $item->type,
                    'unit_price' => (float) $item->unit_price,
                    'quantity' => (int) $item->quantity,
                    'used_quantity' => (int) $item->used_quantity,
                    'billing_status' => $item->billing_status,
                    'total' => (float) $item->total,
                    'note' => $item->note,
                ])->values()->all(),
            'check_in_at' => $booking->check_in_at,
            'check_out_at' => $booking->check_out_at,
            'night_count' => $nightCount,
            'room_quantity' => $booking->room_quantity,
        ];

        $promotionService = app(PromotionService::class);
        $usedCodes = $booking->bookingPromotions
            ->pluck('code_snapshot')
            ->map(fn ($code) => strtoupper(trim((string) $code)));

        $promotions = $promotionService->availablePromotions($promotionContext, 'admin')
            ->reject(fn ($promotion) => $usedCodes->contains(strtoupper($promotion->code)))
            ->filter(function ($promotion) use ($proposal, $roomIssueRequest, $promotionContext, $promotionService) {
                $isUpgradeOnly = $promotion->roomUpgradeOffers->isNotEmpty()
                    && (float) $promotion->discount_value <= 0
                    && $promotion->serviceOffers->isEmpty();

                if (!$isUpgradeOnly) {
                    return true;
                }

                if (!$proposal['room'] || $proposal['type'] !== 'upgrade_category') {
                    return false;
                }

                $result = $promotionService->findRoomUpgradeOffer(
                    $promotion->code,
                    (int) $roomIssueRequest->current_room_category_id,
                    (int) $proposal['room']->room_category_id,
                    PromotionRoomUpgradeOffer::KIND_INCIDENT_SUPPORT,
                    $promotionContext,
                    'admin',
                    'Bù đắp do sự cố phòng'
                );

                return (bool) ($result['ok'] ?? false);
            })
            ->values();

        return view('admin.pages.room-issues.show', compact('roomIssueRequest', 'proposal', 'promotions'));
    }

    public function approve(
        Request $request,
        RoomIssueRequest $roomIssueRequest,
        BookingPromotionApplicationService $promotionApplication
    ) {
        $this->guardManager();

        $data = $request->validate([
            'promotion_codes' => ['nullable', 'array'],
            'promotion_codes.*' => ['string', 'max:50'],
            'admin_note' => ['required', 'string', 'max:2000'],
        ], [
            'admin_note.required' => 'Vui lòng nhập ghi chú xử lý và nội dung phản hồi khách.',
        ]);

        try {
            DB::transaction(function () use ($roomIssueRequest, $data, $promotionApplication) {
                $issue = RoomIssueRequest::whereKey($roomIssueRequest->id)->lockForUpdate()->firstOrFail();
                if ($issue->status !== 'pending') {
                    throw new \RuntimeException('Yêu cầu sự cố này đã được xử lý.');
                }

                $issue->load(['booking.bookingRooms.room.category', 'currentRoom.category', 'currentCategory']);
                $booking = Booking::whereKey($issue->booking_id)->lockForUpdate()->firstOrFail();
                if ($booking->status !== 'checked_in' || !$booking->actual_check_in) {
                    throw new \RuntimeException('Booking không còn ở trạng thái đang lưu trú nên không thể tự động đổi phòng do sự cố.');
                }
                $booking->load(['bookingRooms.room.category', 'bookingPromotions', 'serviceItems', 'roomChanges']);

                $proposal = $this->resolveProposal($issue);
                $oldRoom = Room::with('category')->lockForUpdate()->findOrFail($issue->current_room_id);
                $bookingRoom = BookingRoom::where('booking_id', $booking->id)
                    ->where('room_id', $oldRoom->id)
                    ->lockForUpdate()
                    ->first();

                if (!$bookingRoom) {
                    throw new \RuntimeException('Phòng khách báo không còn thuộc booking hiện tại. Hãy tải lại yêu cầu.');
                }

                $resolution = $proposal['type'];
                $newRoom = $proposal['room'];
                $priceDifference = 0;
                $logText = '';

                if ($newRoom) {
                    $newRoom = Room::with('category')->lockForUpdate()->findOrFail($newRoom->id);
                    $stillAvailable = Room::whereKey($newRoom->id)
                        ->whereNotIn('id', $booking->bookingRooms->pluck('room_id')->all())
                        ->availableForPeriod(now('Asia/Ho_Chi_Minh'), $booking->check_out_at, $booking->id)
                        ->exists();

                    if (!$stillAvailable) {
                        throw new \RuntimeException('Phòng hệ thống đề xuất vừa được sử dụng. Vui lòng tải lại để hệ thống chọn phương án mới.');
                    }

                    $remainingStart = now('Asia/Ho_Chi_Minh')->startOfDay();
                    $remainingEnd = Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->startOfDay();
                    $nightCount = max(1, $remainingStart->diffInDays($remainingEnd));
                    $oldPrice = (float) ($oldRoom->category?->price ?? $bookingRoom->price_at_booking);
                    $newPrice = (float) ($newRoom->category?->price ?? $bookingRoom->price_at_booking);
                    $priceDifference = max(0, ($newPrice - $oldPrice) * $nightCount);

                    // Sự cố do khách sạn: nếu phải nâng hạng thì ghi nhận giá trị hạng mới
                    // và bù toàn bộ phần chênh để tổng khách phải trả không tăng.
                    $bookingRoom->update([
                        'room_id' => $newRoom->id,
                        'price_at_booking' => $newPrice,
                        'surcharge_reason' => 'Quản lý duyệt đổi phòng do sự cố: ' . $data['admin_note'],
                    ]);

                    if ($priceDifference > 0) {
                        $currentDiscount = (float) ($booking->discount_amount ?? 0);
                        $currentSubtotal = (float) ($booking->subtotal_amount ?? 0);
                        if ($currentSubtotal <= 0) {
                            $currentSubtotal = (float) $booking->estimated_total + $currentDiscount;
                        }
                        $booking->update([
                            'subtotal_amount' => $currentSubtotal + $priceDifference,
                            'discount_amount' => $currentDiscount + $priceDifference,
                            // Net total giữ nguyên: khách sạn chịu toàn bộ chênh do lỗi phòng.
                            'estimated_total' => (float) $booking->estimated_total,
                        ]);
                    }

                    $oldRoom->update([
                        'status' => 'maintenance',
                        'status_from' => now(),
                        'status_until' => null,
                        'note' => 'Phòng có sự cố do khách đang lưu trú báo: ' . $issue->issue_description,
                    ]);
                    $newRoom->update([
                        'status' => $booking->status === 'checked_in' ? 'occupied' : 'reserved',
                        'status_from' => null,
                        'status_until' => null,
                    ]);

                    if ((int) $booking->room_quantity === 1) {
                        $booking->update(['room_category_id' => $newRoom->room_category_id]);
                    }

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
                        'price_difference_total' => $priceDifference,
                        'change_source' => 'incident',
                        'reason' => $data['admin_note'],
                        'changed_by' => Auth::id(),
                    ]);

                    $logText = $resolution === 'same_category'
                        ? 'đổi phòng cùng hạng từ phòng ' . $oldRoom->room_number . ' sang phòng ' . $newRoom->room_number
                        : 'đổi hạng miễn phí từ phòng ' . $oldRoom->room_number . ' (' . ($oldRoom->category?->name ?? '---') . ') sang phòng ' . $newRoom->room_number . ' (' . ($newRoom->category?->name ?? '---') . ')';
                } else {
                    // Không còn phòng thay thế: khách vẫn ở phòng hiện tại, gửi việc sửa gấp cho buồng phòng.
                    $oldRoom->update([
                        'status' => 'maintenance',
                        'status_from' => now(),
                        'status_until' => null,
                        'note' => 'Cần khắc phục gấp khi khách vẫn đang ở: ' . $issue->issue_description,
                    ]);
                    $logText = 'không còn phòng phù hợp để đổi; giữ nguyên phòng ' . $oldRoom->room_number . ' và chuyển buồng phòng sửa gấp';
                }

                $codes = collect($data['promotion_codes'] ?? [])
                    ->map(fn ($code) => strtoupper(trim((string) $code)))
                    ->filter()->unique()->values();

                $issue->update([
                    'status' => $newRoom ? 'approved' : 'repair_only',
                    'resolution_type' => $resolution,
                    'approved_room_id' => $newRoom?->id,
                    'approved_room_category_id' => $newRoom?->room_category_id,
                    'price_difference_per_night' => $newRoom
                        ? max(0, (float) ($newRoom->category?->price ?? 0) - (float) ($oldRoom->category?->price ?? 0))
                        : 0,
                    'promotion_codes' => $codes->all(),
                    'admin_note' => $data['admin_note'],
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                    'repair_status' => 'waiting',
                ]);

                if ($codes->isNotEmpty()) {
                    $booking->unsetRelation('roomChanges');
                    $promotionApplication->apply($booking, $codes->all(), $data['admin_note'], Auth::id(), true);
                }

                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => Auth::id(),
                    'action' => 'manager_approved_room_issue',
                    'description' => 'Quản lý đã phê duyệt sự cố: ' . $logText
                        . '. Mã bù đắp: ' . ($codes->isNotEmpty() ? $codes->implode(', ') : 'không áp dụng')
                        . '. Ghi chú: ' . $data['admin_note'],
                ]);

                RoomActionLog::create([
                    'room_id' => $oldRoom->id,
                    'user_id' => Auth::id(),
                    'action_type' => 'maintenance_support',
                    'action_time' => now(),
                    'note' => 'Quản lý yêu cầu buồng phòng đến khắc phục nhanh. Booking '
                        . $booking->booking_code . '. Nội dung: ' . $issue->issue_description,
                ]);
            });

            return redirect()->route('admin.room-issues.show', $roomIssueRequest)
                ->with('success', 'Đã duyệt sự cố. Hệ thống đã tự xử lý phòng, cập nhật booking, ghi lịch sử và gửi việc sửa phòng.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Không thể duyệt sự cố: ' . $e->getMessage());
        }
    }

    public function attachment(RoomIssueAttachment $attachment)
    {
        $this->guardIssueViewer($attachment->request?->booking);

        if (!Storage::disk('public')->exists($attachment->path)) {
            abort(404, 'Ảnh sự cố không còn tồn tại trong storage/app/public.');
        }

        return Storage::disk('public')->response(
            $attachment->path,
            $attachment->original_name ?: basename($attachment->path),
            ['Content-Type' => $attachment->mime_type ?: 'image/jpeg']
        );
    }

    public function repairs(Request $request)
    {
        $this->guardHousekeeping();

        $status = $request->query('status', 'waiting');
        $query = RoomIssueRequest::with([
            'booking', 'currentRoom.category', 'approvedRoom.category', 'attachments', 'reviewer', 'repairCompleter',
        ])->whereIn('status', ['approved', 'repair_only']);

        if ($status !== 'all') {
            $query->where('repair_status', $status);
        }

        $issues = $query->orderByRaw("CASE WHEN repair_status = 'waiting' THEN 0 ELSE 1 END")
            ->latest('reviewed_at')->paginate(15)->withQueryString();

        return view('admin.pages.room-repairs.index', compact('issues', 'status'));
    }

    public function repairShow(RoomIssueRequest $roomIssueRequest)
    {
        $this->guardHousekeeping();
        abort_unless(in_array($roomIssueRequest->status, ['approved', 'repair_only'], true), 404);

        $roomIssueRequest->load([
            'booking', 'currentRoom.category', 'approvedRoom.category', 'attachments',
            'reviewer', 'repairCompleter',
        ]);

        return view('admin.pages.room-repairs.show', compact('roomIssueRequest'));
    }

    public function completeRepair(Request $request, RoomIssueRequest $roomIssueRequest)
    {
        $this->guardHousekeeping();

        $data = $request->validate([
            'repair_note' => ['required', 'string', 'max:2000'],
        ], ['repair_note.required' => 'Vui lòng ghi nội dung đã sửa.']);

        if (!in_array($roomIssueRequest->status, ['approved', 'repair_only'], true)) {
            return back()->with('error', 'Yêu cầu chưa được quản lý duyệt.');
        }
        if ($roomIssueRequest->repair_status === 'completed') {
            return back()->with('error', 'Phòng này đã được xác nhận sửa xong.');
        }

        DB::transaction(function () use ($roomIssueRequest, $data) {
            $issue = RoomIssueRequest::whereKey($roomIssueRequest->id)->lockForUpdate()->firstOrFail();
            $issue->load('booking.bookingRooms');
            $oldRoom = Room::lockForUpdate()->findOrFail($issue->current_room_id);

            $guestStillUsesOldRoom = $issue->booking
                && in_array($issue->booking->status, ['checked_in', 'inspection_requested'], true)
                && $issue->booking->bookingRooms->contains('room_id', $oldRoom->id);

            $nextStatus = $guestStillUsesOldRoom ? 'occupied' : 'available';
            $oldRoom->update([
                'status' => $nextStatus,
                'status_from' => null,
                'status_until' => null,
                'note' => $guestStillUsesOldRoom
                    ? 'Đã khắc phục sự cố; khách vẫn đang sử dụng phòng.'
                    : null,
            ]);

            $issue->update([
                'repair_status' => 'completed',
                'repair_completed_by' => Auth::id(),
                'repair_completed_at' => now(),
                'repair_note' => $data['repair_note'],
            ]);

            RoomActionLog::create([
                'room_id' => $oldRoom->id,
                'user_id' => Auth::id(),
                'action_type' => 'maintenance_support',
                'action_time' => now(),
                'note' => 'Đã khắc phục xong sự cố. ' . $data['repair_note']
                    . '. Trạng thái phòng sau xử lý: ' . ($nextStatus === 'available' ? 'trống' : 'đang ở') . '.',
            ]);

            if ($issue->booking) {
                BookingLog::create([
                    'booking_id' => $issue->booking_id,
                    'user_id' => Auth::id(),
                    'action' => 'room_issue_repair_completed',
                    'description' => 'Buồng phòng xác nhận đã sửa xong phòng ' . $oldRoom->room_number
                        . '. ' . $data['repair_note'],
                ]);
            }
        });

        return redirect()->route('admin.room-repairs.show', $roomIssueRequest)
            ->with('success', 'Đã xác nhận sửa xong và cập nhật trạng thái phòng.');
    }

    private function resolveProposal(RoomIssueRequest $issue): array
    {
        $issue->loadMissing(['booking.bookingRooms', 'currentRoom.category', 'currentCategory']);
        $booking = $issue->booking;
        $excludedRoomIds = $booking?->bookingRooms->pluck('room_id')->all() ?? [$issue->current_room_id];
        $from = now('Asia/Ho_Chi_Minh');
        $to = $booking?->check_out_at;

        if (!$booking || !$to) {
            return ['type' => 'no_room', 'room' => null, 'label' => 'Không còn phòng để đổi'];
        }

        $sameCategoryRoom = Room::with('category')
            ->where('room_category_id', $issue->current_room_category_id)
            ->whereNotIn('id', $excludedRoomIds)
            ->availableForPeriod($from, $to, $booking->id)
            ->orderBy('floor_number')->orderBy('room_number')->first();

        if ($sameCategoryRoom) {
            return [
                'type' => 'same_category',
                'room' => $sameCategoryRoom,
                'label' => 'Đổi phòng cùng hạng',
                'description' => 'Còn phòng ' . $sameCategoryRoom->room_number . ' cùng hạng ' . ($sameCategoryRoom->category?->name ?? '---') . '.',
            ];
        }

        $currentPrice = (float) ($issue->currentCategory?->price ?? $issue->currentRoom?->category?->price ?? 0);
        $upgradeRoom = Room::with('category')
            ->whereNotIn('id', $excludedRoomIds)
            ->whereHas('category', fn ($q) => $q->where('status', 'active')->where('price', '>', $currentPrice))
            ->availableForPeriod($from, $to, $booking->id)
            ->get()
            ->sortBy(fn ($room) => [
                (float) ($room->category?->price ?? PHP_INT_MAX),
                (int) $room->floor_number,
                (string) $room->room_number,
            ])->first();

        if ($upgradeRoom) {
            return [
                'type' => 'upgrade_category',
                'room' => $upgradeRoom,
                'label' => 'Đổi hạng phòng',
                'description' => 'Hết phòng cùng hạng; hệ thống sẽ nâng miễn phí sang phòng '
                    . $upgradeRoom->room_number . ' - ' . ($upgradeRoom->category?->name ?? '---') . '.',
            ];
        }

        return [
            'type' => 'no_room',
            'room' => null,
            'label' => 'Không còn phòng để đổi',
            'description' => 'Không còn phòng cùng hạng hoặc hạng cao hơn trống đến hết thời gian lưu trú. Khách giữ phòng hiện tại và buồng phòng phải sửa gấp.',
        ];
    }

    private function guardManager(): void
    {
        abort_unless(in_array(Auth::user()?->role, ['super_admin', 'manager'], true), 403, 'Chỉ quản lý được duyệt sự cố phòng.');
    }

    private function guardHousekeeping(): void
    {
        abort_unless(in_array(Auth::user()?->role, ['super_admin', 'manager', 'housekeeping_supervisor', 'housekeeping'], true), 403);
    }

    private function guardIssueViewer(?Booking $booking): void
    {
        $role = Auth::user()?->role;
        abort_unless(in_array($role, [
            'super_admin', 'manager', 'receptionist_lead', 'receptionist',
            'housekeeping_supervisor', 'housekeeping',
        ], true), 403);
    }
}
