<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RoomIssueFormMail;
use App\Mail\GuestBookingOtpMail;
use Illuminate\Support\Facades\Cache;
use App\Models\Booking;
use App\Models\BookingCancellationRequest;
use App\Models\BookingPayment;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Models\BookingLog;
use Illuminate\Support\Facades\Auth;
use App\Models\Service;
use App\Models\BookingServiceItem;
use App\Services\BookingOccupancyFeeService;
use App\Services\PromotionService;
use App\Services\BookingPromotionApplicationService;
use App\Services\BookingCancellationService;
use App\Services\BookingFinancialService;
use App\Services\PendingPaymentRequestService;
use App\Services\BookingServicePricingService;
use App\Services\BookingRepricingService;
use App\Services\HotelPolicyService;
use App\Services\ChatAssignmentService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Support\Realtime;

class BookingController extends Controller
{
    private const INFANT_MAX_AGE = 5;
    private const CHILD_MAX_AGE = 17;
    public function index(Request $request)
    {
        $bookings = Booking::query()
            ->visibleToOperationsUser(Auth::user())
            ->with([
                'customer',
                'roomCategory',
                'bookingRooms.room',
                'activeStaffAssignments.staff.staff',
                'pendingCancellationRequest',
                'pendingRoomIssueRequest',
            ])
            ->withExists([
                'logs as auto_cancelled_by_payment_expiry' => fn ($query) => $query
                    ->where('action', 'payment_hold_expired'),
            ]);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $bookings->where(function ($query) use ($keyword) {
                $query->where('booking_code', 'like', '%' . $keyword . '%')
                    ->orWhereHas('customer', function ($customerQuery) use ($keyword) {
                        $customerQuery->where('first_name', 'like', '%' . $keyword . '%')
                            ->orWhere('last_name', 'like', '%' . $keyword . '%')
                            ->orWhere('phone', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $bookings->where('status', $request->status);
        }

        if ($request->filled('payment_status') && in_array($request->payment_status, ['unpaid', 'partial', 'paid'], true)) {
            $bookings->where('payment_status', $request->payment_status);
        }

        $hasDiscoveryFilter = $request->filled('keyword')
            || $request->filled('status')
            || $request->filled('payment_status')
            || $request->filled('filter_date')
            || $request->filled('date_from')
            || $request->filled('date_to')
            || $request->filled('time_from')
            || $request->filled('time_to');

        // Danh sách vận hành mặc định chỉ giữ các đơn còn việc. Đơn đã xong/hủy
        // được ẩn để giao diện gọn; khi người dùng chủ động tìm/lọc hoặc bật lịch sử
        // thì vẫn truy cập đầy đủ. Riêng đơn hủy còn khoản hoàn tiền phải xử lý vẫn
        // giữ trên danh sách vận hành để không bị bỏ sót.
        if (!$request->boolean('show_history') && !$hasDiscoveryFilter) {
            $bookings->where(function ($query) {
                $query->whereNotIn('status', ['checked_out', 'completed', 'cancelled', 'canceled'])
                    ->orWhere(function ($refundQuery) {
                        $refundQuery->whereIn('status', ['cancelled', 'canceled'])
                            ->where('refund_status', 'pending');
                    });
            });
        }

        if ($request->filled('filter_date') || $request->filled('date_from')) {
            $tz = 'Asia/Ho_Chi_Minh';

            // Support both old single-date filter and new date-range filter
            if ($request->filled('date_from')) {
                $dateFrom = $request->date_from;
                $dateTo = $request->filled('date_to') ? $request->date_to : $dateFrom;
            } else {
                $dateFrom = $request->filter_date;
                $dateTo = $dateFrom;
            }

            $timeFrom = $request->input('time_from', $request->input('filter_time_from', '00:00')) ?: '00:00';
            $timeTo = $request->input('time_to', $request->input('filter_time_to', '23:59')) ?: '23:59';

            $filterStart = Carbon::parse($dateFrom . ' ' . $timeFrom . ':00', $tz);
            $filterEnd = Carbon::parse($dateTo . ' ' . $timeTo . ':59', $tz);

            // If same day and end <= start, treat as overnight wrap
            if ($filterEnd->lessThanOrEqualTo($filterStart)) {
                $filterEnd->addDay();
            }

            $bookings->where('check_in_at', '<', $filterEnd)
                ->where('check_out_at', '>', $filterStart);
        }

        $sort = $request->input('sort', 'updated');

        if ($sort === 'operations') {
            // Chế độ điều hành: việc cần xử lý và khách đang lưu trú ở trên.
            $bookings->orderByRaw("CASE
                    WHEN status = 'inspection_requested' THEN 1
                    WHEN status = 'checked_in' AND check_out_at <= DATE_ADD(NOW(), INTERVAL 3 HOUR) THEN 2
                    WHEN status = 'checked_in' THEN 3
                    WHEN status = 'pending' THEN 4
                    WHEN status = 'confirmed' AND check_in_at <= DATE_ADD(NOW(), INTERVAL 3 HOUR) THEN 5
                    WHEN status = 'confirmed' THEN 6
                    WHEN status = 'checked_out' THEN 8
                    WHEN status IN ('completed', 'cancelled', 'canceled') THEN 9
                    ELSE 7
                END")
                ->orderByRaw("CASE
                    WHEN status IN ('pending', 'confirmed') THEN check_in_at
                    WHEN status IN ('checked_in', 'inspection_requested') THEN check_out_at
                    ELSE NULL
                END ASC")
                ->orderByDesc('updated_at')
                ->orderByDesc('id');
        } elseif ($sort === 'created') {
            $bookings->orderByDesc('created_at')->orderByDesc('id');
        } else {
            // Mặc định hiển thị thay đổi mới nhất. Booking vừa tự hủy vì hết hạn
            // thanh toán phải xuất hiện ngay thay vì bị đẩy xuống sau các đơn cũ.
            $sort = 'updated';
            $bookings->orderByDesc('updated_at')->orderByDesc('id');
        }

        $bookings = $bookings
            ->paginate(10)
            ->withQueryString();

        return view('admin.pages.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        return $this->renderBookingWorkspace($booking, 'admin.pages.bookings.show');
    }

    private function renderBookingWorkspace(Booking $booking, string $view)
    {
        $this->guardCanAccessBooking($booking);

        $viewer = Auth::user();
        if ($viewer?->role === 'receptionist') {
            app(ChatAssignmentService::class)->lockBookingForWork($booking, $viewer);
        }

        $booking->load([
            'customer',
            'roomCategory',
            'bookingRooms.room.category',
            'guests.bookingRoom.room.category',
            'guests.guardian.bookingRoom.room',
            'roomInspections.room',
            'roomInspections.items.guestResponder',
            'roomInspections.items.rechecker',
            'roomInspections.items.detector',
            'serviceItems.service',
            'serviceItems.bookingRoom.room.category',
            'bookingPromotions.user',
            'bookingPromotions.bookingRoom.room.category',
            'bookingPromotions.serviceOffers.bookingRoom.room',
            'bookingPromotions.roomUpgradeOffers',
            'promotionServiceOffers',
            'promotionRoomUpgrades',
            'logs.user',
            'payments',
            'roomIssueRequests.attachments',
            'roomIssueRequests.currentRoom.category',
            'roomIssueRequests.approvedRoom.category',
            'roomIssueRequests.proposedRoom.category',
            'roomIssueRequests.reviewer',
        ]);

        // Chỉ đồng bộ trạng thái thanh toán từ giao dịch thành công.
        // Không ghi đè deposit_amount vì đây là mức cọc được chốt khi tạo đơn.
        $financialService = app(BookingFinancialService::class);
        $actualPaidTotal = $financialService->paidTotal($booking);
        $actualPayableTotal = $financialService->currentTotal($booking);
        $actualPaymentStatus = $actualPaidTotal <= 0
            ? 'unpaid'
            : ($actualPaidTotal + 0.01 >= $actualPayableTotal ? 'paid' : 'partial');

        $actualOverpaymentAmount = max(0, round($actualPaidTotal - $actualPayableTotal, 0));

        if (
            $booking->payment_status !== $actualPaymentStatus
            || abs((float) ($booking->overpayment_amount ?? 0) - $actualOverpaymentAmount) > 0.01
        ) {
            $booking->forceFill([
                'payment_status' => $actualPaymentStatus,
                'overpayment_amount' => $actualOverpaymentAmount,
            ])->saveQuietly();
            $booking->payment_status = $actualPaymentStatus;
            $booking->overpayment_amount = $actualOverpaymentAmount;
        }

        $assignedRoomIds = $booking->bookingRooms
            ->pluck('room_id')
            ->toArray();

        $availableRooms = Room::where('room_category_id', $booking->room_category_id)
            ->whereNotIn('id', $assignedRoomIds)
            ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();
        $hasInspection = $booking->roomInspections->count() > 0;

        $allInspectionsConfirmed = $booking->roomInspections->count() > 0
            && $booking->roomInspections->every(function ($inspection) {
                return ($inspection->status ?? null) === 'confirmed'
                    || ($inspection->workflow_stage ?? null) === \App\Models\RoomInspection::STAGE_COMPLETED;
            });

        $approvedInspectionTotal = $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->sum('total');

        $approvedDamageTotal = $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->where('type', 'damage_fee')
            ->sum('total');

        $approvedMinibarTotal = $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->where('type', 'minibar')
            ->sum('total');

        $serviceItemTotal = $booking->serviceItems
            ->where('billing_status', 'confirmed')
            ->sum('total');
        $availableServices = Service::where('status', 'active')
            ->where('price', '>', 0)
            ->whereIn('type', ['service', 'minibar_order'])
            ->orderByRaw("FIELD(service_group, 'vehicle', 'food_drink', 'transport', 'laundry', 'wellness', 'room_support', 'general', 'other')")
            ->orderByRaw("FIELD(type, 'service', 'minibar_order')")
            ->orderBy('name')
            ->get();

        $nightCount = max(
            1,
            Carbon::parse($booking->check_in_date, 'Asia/Ho_Chi_Minh')
                ->diffInDays(Carbon::parse($booking->check_out_date, 'Asia/Ho_Chi_Minh'))
        );

        $promotionSubtotal = (float) ($booking->subtotal_amount ?? 0);

        if ($promotionSubtotal <= 0) {
            $promotionSubtotal = (float) $booking->estimated_total + (float) ($booking->discount_amount ?? 0);
        }

        $latestCancellationRequest = $booking->cancellationRequests()
            ->with(['requester', 'reviewer'])
            ->latest()
            ->first();

        $latestRoomIssueRequest = $booking->roomIssueRequests()->with([
            'attachments', 'currentRoom.category', 'proposedRoom.category', 'approvedRoom.category', 'reviewer', 'repairCompleter'
        ])->latest()->first();

        $activeRoomIssueRoomIds = $booking->roomIssueRequests()
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere(function ($repairQuery) {
                        $repairQuery->whereIn('status', ['approved', 'repair_only'])
                            ->where('repair_status', 'waiting');
                    });
            })
            ->pluck('current_room_id')
            ->map(fn ($roomId) => (int) $roomId);

        $roomIssueFormAvailableCount = $booking->bookingRooms
            ->filter(fn ($bookingRoom) => $bookingRoom->room)
            ->reject(fn ($bookingRoom) => $activeRoomIssueRoomIds->contains((int) $bookingRoom->room_id))
            ->count();
        $roomIssueFormEmail = trim((string) $booking->booked_customer_email);
        $canSendRoomIssueForm = $booking->status === 'checked_in'
            && (bool) $booking->actual_check_in
            && $activeRoomIssueRoomIds->isEmpty();

        $availablePromotions = app(PromotionService::class)->availablePromotions([
            'booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'customer_email' => $booking->customer_email_snapshot ?: $booking->customer?->email,
            'customer_phone' => $booking->customer_phone_snapshot ?: $booking->customer?->phone,
            'customer_cccd' => $booking->customer_cccd_snapshot ?: $booking->customer?->cccd,
            'subtotal_amount' => $promotionSubtotal,
            'check_in_at' => $booking->check_in_at,
            'check_out_at' => $booking->check_out_at,
            'night_count' => $nightCount,
            'room_quantity' => $booking->room_quantity,
        ], 'admin')->reject(function ($promotion) use ($booking) {
            return $booking->bookingPromotions
                ->pluck('code_snapshot')
                ->contains($promotion->code);
        })->values();

        return view($view, compact(
            'booking',
            'availableRooms',
            'assignedRoomIds',
            'hasInspection',
            'allInspectionsConfirmed',
            'approvedDamageTotal',
            'serviceItemTotal',
            'availableServices',
            'approvedInspectionTotal',
            'approvedMinibarTotal',
            'availablePromotions',
            'latestCancellationRequest',
            'latestRoomIssueRequest',
            'roomIssueFormAvailableCount',
            'roomIssueFormEmail',
            'canSendRoomIssueForm',
        ));
    }

    public function sendRoomIssueForm(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'checked_in' || !$booking->actual_check_in) {
            return back()->with('error', 'Chỉ gửi biểu mẫu sự cố khi booking đang ở trạng thái đã nhận phòng.');
        }

        $booking->loadMissing(['customer', 'bookingRooms.room.category']);

        $data = $request->validate([
            'recipient_email' => ['required', 'email:rfc', 'max:255'],
        ], [
            'recipient_email.required' => 'Vui lòng nhập email nhận biểu mẫu sự cố.',
            'recipient_email.email' => 'Email nhận biểu mẫu không đúng định dạng.',
            'recipient_email.max' => 'Email nhận biểu mẫu không được vượt quá 255 ký tự.',
        ]);

        // Email này chỉ dùng cho lần gửi hiện tại, không ghi đè email hồ sơ khách/booking.
        $email = trim((string) $data['recipient_email']);

        $activeRoomIds = $booking->roomIssueRequests()
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere(function ($repairQuery) {
                        $repairQuery->whereIn('status', ['approved', 'repair_only'])
                            ->where('repair_status', 'waiting');
                    });
            })
            ->pluck('current_room_id')
            ->map(fn ($roomId) => (int) $roomId);

        $availableRoomCount = $booking->bookingRooms
            ->filter(fn ($bookingRoom) => $bookingRoom->room)
            ->reject(fn ($bookingRoom) => $activeRoomIds->contains((int) $bookingRoom->room_id))
            ->count();

        if ($activeRoomIds->isNotEmpty()) {
            return back()->with('error', 'Booking đang có yêu cầu sự cố chưa xử lý. Chỉ được gửi form mới sau khi yêu cầu hiện tại hoàn tất.');
        }

        $expiresAt = $booking->check_out_at && $booking->check_out_at->isFuture()
            ? $booking->check_out_at->copy()
            : now('Asia/Ho_Chi_Minh')->addDay();

        $formUrl = URL::temporarySignedRoute(
            'guest-room-issues.form',
            $expiresAt,
            ['booking' => $booking->id]
        );

        try {
            Mail::to($email)->send(new RoomIssueFormMail($booking, $formUrl, $expiresAt));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Không thể gửi email biểu mẫu: ' . $e->getMessage());
        }

        $this->addBookingLog(
            $booking,
            'room_issue_form_emailed',
            'Lễ tân đã gửi biểu mẫu báo sự cố tới email ' . $email
                . '. Có ' . $availableRoomCount . ' phòng có thể chọn báo sự cố.'
        );

        return back()->with('success', 'Đã gửi biểu mẫu báo sự cố tới ' . $email . '.');
    }

    public function approveCancellationRequest(
        Request $request,
        Booking $booking,
        BookingCancellationService $cancellations,
        BookingFinancialService $financials
    ) {
        $this->guardCanAccessBooking($booking);

        $data = $request->validate([
            'review_note' => 'nullable|string|max:1000',
        ]);

        $cancellationRequest = BookingCancellationRequest::where('booking_id', $booking->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$cancellationRequest) {
            return back()->with('error', 'Booking không có yêu cầu hủy nào đang chờ xác nhận.');
        }

        $policy = is_array($cancellationRequest->policy_snapshot)
            ? $cancellationRequest->policy_snapshot
            : $financials->cancellationPolicy($booking);

        try {
            $cancellations->cancel(
                $booking,
                $policy,
                Auth::id(),
                'receptionist_approved_cancellation',
                'Lễ tân'
            );

            $cancellationRequest->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now('Asia/Ho_Chi_Minh'),
                'review_note' => $data['review_note'] ?? null,
            ]);

            Realtime::booking($booking->fresh(), 'cancelled');

            return back()->with('success', 'Đã xác nhận hủy đơn và mở bán lại phòng. Chính sách tiền đã được giữ đúng theo thời điểm khách gửi yêu cầu.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Không thể xác nhận hủy: ' . $e->getMessage());
        }
    }

    public function rejectCancellationRequest(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        $data = $request->validate([
            'review_note' => 'required|string|max:1000',
        ], [
            'review_note.required' => 'Vui lòng nhập lý do từ chối yêu cầu hủy.',
        ]);

        $cancellationRequest = BookingCancellationRequest::where('booking_id', $booking->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$cancellationRequest) {
            return back()->with('error', 'Booking không có yêu cầu hủy nào đang chờ xác nhận.');
        }

        $cancellationRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now('Asia/Ho_Chi_Minh'),
            'review_note' => $data['review_note'],
        ]);

        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'action' => 'receptionist_rejected_cancellation',
            'description' => 'Lễ tân từ chối yêu cầu hủy của khách. Lý do: ' . $data['review_note'],
        ]);

        Realtime::booking($booking, 'cancellation_rejected');

        return back()->with('success', 'Đã từ chối yêu cầu hủy. Booking vẫn được giữ nguyên.');
    }

    public function applyPromotions(
        Request $request,
        Booking $booking,
        BookingPromotionApplicationService $promotionApplication
    ) {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Chỉ có thể thêm mã cho booking đang chờ, đã xác nhận hoặc đang ở.');
        }

        if ($booking->payment_status === 'paid') {
            return back()->with('error', 'Booking đã thanh toán đủ nên không thể áp thêm mã giảm giá.');
        }

        $data = $request->validate([
            'promotion_codes' => 'required|array|min:1',
            'promotion_codes.*' => 'required|string|max:50',
            'promotion_note' => 'nullable|string|max:1000',
        ], [
            'promotion_codes.required' => 'Vui lòng chọn ít nhất một mã ưu đãi.',
            'promotion_codes.min' => 'Vui lòng chọn ít nhất một mã ưu đãi.',
        ]);

        try {
            DB::beginTransaction();
            $result = $promotionApplication->apply(
                $booking,
                $data['promotion_codes'],
                $data['promotion_note'] ?? null,
                Auth::id()
            );

            $this->addBookingLog(
                $booking,
                'promotion_added',
                'Áp dụng mã sau khi tạo/đổi phòng: ' . implode(', ', $result['codes'])
                . '. Giảm tiền/dịch vụ thêm: ' . number_format($result['discount_total'], 0, ',', '.') . 'đ'
                . '. Quyền lợi nâng hạng ghi nhận: ' . number_format($result['room_upgrade_discount_total'], 0, ',', '.') . 'đ.'
                . (!empty($data['promotion_note']) ? ' Lý do: ' . $data['promotion_note'] : '')
            );

            DB::commit();
            return back()->with('success', 'Đã áp dụng mã ưu đãi vào booking.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Không thể áp mã: ' . $e->getMessage());
        }
    }

    private function upsertPromotionServiceItem(Booking $booking, array $item): void
    {
        if (empty($item['service_id'])) {
            return;
        }

        $existingItem = BookingServiceItem::where('booking_id', $booking->id)
            ->where('service_id', $item['service_id'])
            ->where('billing_status', 'confirmed')
            ->first();

        if ($existingItem) {
            $existingItem->base_quantity = max(1, (int) ($existingItem->base_quantity ?? $existingItem->quantity))
                + max(1, (int) ($item['base_quantity'] ?? $item['quantity']));
            $existingItem->quantity = $existingItem->base_quantity;
            $existingItem->used_quantity = (int) $existingItem->used_quantity + (int) $item['used_quantity'];
            $existingItem->total = (float) $existingItem->total + (float) $item['total'];
            $existingItem->billing_rule_snapshot = $item['billing_rule_snapshot'] ?? $existingItem->billing_rule_snapshot ?? Service::BILLING_ONCE;
            $existingItem->nights_snapshot = $item['nights_snapshot'] ?? $existingItem->nights_snapshot ?? 1;
            $existingItem->rooms_snapshot = $item['rooms_snapshot'] ?? $existingItem->rooms_snapshot ?? 1;
            $existingItem->people_snapshot = $item['people_snapshot'] ?? $existingItem->people_snapshot ?? 1;

            $extraNote = trim((string) ($item['note'] ?? ''));
            if ($extraNote !== '') {
                $existingItem->note = trim((string) ($existingItem->note ?? '')) !== ''
                    ? $existingItem->note . '; ' . $extraNote
                    : $extraNote;
            }

            $existingItem->save();
            return;
        }

        BookingServiceItem::create([
            'booking_id' => $booking->id,
            'service_id' => $item['service_id'],
            'name' => $item['name'],
            'type' => $item['type'],
            'billing_rule_snapshot' => $item['billing_rule_snapshot'] ?? Service::BILLING_ONCE,
            'unit_price' => $item['unit_price'],
            'base_quantity' => $item['base_quantity'] ?? $item['quantity'],
            'quantity' => $item['quantity'],
            'used_quantity' => $item['used_quantity'],
            'nights_snapshot' => $item['nights_snapshot'] ?? 1,
            'rooms_snapshot' => $item['rooms_snapshot'] ?? 1,
            'people_snapshot' => $item['people_snapshot'] ?? 1,
            'billing_status' => $item['billing_status'],
            'total' => $item['total'],
            'note' => $item['note'],
        ]);
    }

    public function edit(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        return view('admin.pages.bookings.edit', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        $data = $request->validate([
            'status' => 'required|in:pending,confirmed,checked_in,inspection_requested,checked_out,completed',
            'payment_status' => 'required|in:unpaid,partial,paid',
            'note' => 'nullable|string|max:1000',
        ]);

        // Không cho form chỉnh sửa chung bỏ qua state machine của booking.
        // Check-in/check-out/xác nhận/hủy phải đi qua các nghiệp vụ riêng để kiểm tra
        // tiền cọc, tình trạng phòng, kiểm phòng và ghi log đầy đủ.
        if ($data['status'] !== $booking->status) {
            return back()->with(
                'error',
                'Không thể đổi trạng thái booking tại màn hình chỉnh sửa chung. Hãy dùng đúng nút nghiệp vụ Xác nhận / Check-in / Kiểm phòng / Check-out / Hủy.'
            );
        }

        $financials = app(BookingFinancialService::class);
        $financials->refreshPaymentStatus($booking);
        $booking->refresh();

        if ($data['payment_status'] !== $booking->payment_status) {
            return back()->with(
                'error',
                'Trạng thái thanh toán được tính tự động từ các giao dịch thành công. Hãy ghi nhận thanh toán ở mục Thanh toán thay vì sửa trạng thái bằng tay.'
            );
        }

        $oldNote = (string) ($booking->note ?? '');
        $newNote = (string) ($data['note'] ?? '');
        if ($oldNote !== $newNote) {
            $booking->update(['note' => $data['note'] ?? null]);
            $this->addBookingLog($booking, 'booking_note_updated', 'Cập nhật ghi chú booking.');
            Realtime::booking($booking, 'updated');
        }

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('success', 'Cập nhật thông tin booking thành công.');
    }

    public function cancel(
        Booking $booking,
        BookingFinancialService $financials,
        BookingCancellationService $cancellations
    ) {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['pending', 'confirmed'], true) || $booking->actual_check_in) {
            return back()->with('error', 'Không thể hủy đơn vì khách đã nhận phòng hoặc đơn không còn hiệu lực.');
        }

        $checkInDate = Carbon::parse($booking->check_in_date, 'Asia/Ho_Chi_Minh');
        $directCancelCutoff = Carbon::parse($booking->check_in_date . ' ' . $booking->directCancelCutoffTime(), 'Asia/Ho_Chi_Minh');
        $holdCutoff = Carbon::parse($booking->check_in_date . ' ' . $booking->lateArrivalCutoffTime(), 'Asia/Ho_Chi_Minh');
        $now = now('Asia/Ho_Chi_Minh');

        if ($now->greaterThanOrEqualTo($holdCutoff)) {
            return back()->with('error', 'Đơn đã qua ' . $holdCutoff->format('H:i') . ' ngày nhận phòng. Hãy xử lý theo luồng no-show.');
        }

        if ($now->greaterThanOrEqualTo($directCancelCutoff)) {
            $email = Str::lower(trim((string) $booking->customer_email_snapshot));
            if ($email === '') {
                return back()->with('error', 'Booking chưa có email để gửi mã xác nhận hủy.');
            }

            $otp = (string) random_int(100000, 999999);
            $lookupKey = hash('sha256', strtoupper($booking->booking_code) . '|' . $email);
            $cacheKey = 'guest-booking-otp:' . $lookupKey;
            Cache::put($cacheKey, [
                'hash' => hash('sha256', $otp),
                'attempts' => 0,
                'booking_id' => $booking->id,
                'email' => $email,
            ], now()->addMinutes(10));

            try {
                Mail::to($email)->send(new GuestBookingOtpMail($booking, $otp, 10));
            } catch (\Throwable $e) {
                Cache::forget($cacheKey);
                return back()->with('error', 'Không gửi được mã xác nhận hủy.');
            }

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'admin_cancellation_otp_sent',
                'description' => 'Lễ tân đã gửi mã OTP xác nhận hủy sau ' . $directCancelCutoff->format('H:i') . '.',
            ]);

            return back()->with('success', 'Đã gửi mã xác nhận hủy về email khách.');
        }

        try {
            $cancellations->cancel(
                $booking,
                $financials->cancellationPolicy($booking),
                Auth::id(),
                'admin_cancelled',
                'Admin/lễ tân'
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Không thể hủy booking: ' . $e->getMessage());
        }

        Realtime::booking($booking->fresh(), 'cancelled');

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Đã hủy booking. Toàn bộ tiền đã thanh toán không hoàn lại, không bảo lưu; phòng được mở bán lại.');
    }

    public function storeServiceItem(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Chỉ được thêm dịch vụ trước khi booking chuyển sang kiểm tra phòng.');
        }

        $data = $request->validate([
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.quantity' => 'required|integer|min:1',
            'services.*.scope' => 'nullable|in:booking,room',
            'services.*.booking_room_id' => 'nullable|integer|exists:booking_rooms,id',
            'services.*.note' => 'nullable|string|max:1000',
        ], [
            'services.required' => 'Vui lòng thêm ít nhất một dịch vụ.',
            'services.*.service_id.required' => 'Vui lòng chọn dịch vụ.',
            'services.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'services.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
            'services.*.booking_room_id.exists' => 'Phòng áp dụng dịch vụ không hợp lệ.',
        ]);

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'], true)) {
                throw new \RuntimeException('Booking vừa thay đổi trạng thái nên không thể thêm dịch vụ lúc này.');
            }
            $booking->loadMissing(['bookingRooms.room.category', 'guests']);
            $totalAdded = 0;
            $logMessages = [];

            foreach ($data['services'] as $serviceRow) {
                $service = Service::where('id', $serviceRow['service_id'])
                    ->where('status', 'active')
                    ->where('price', '>', 0)
                    ->whereIn('type', ['service', 'minibar_order'])
                    ->first();

                if (!$service) {
                    throw new \Exception('Có dịch vụ không hợp lệ hoặc đã bị ẩn.');
                }

                [$scope, $bookingRoom, $roomQuantity, $guestCount] = $this->resolveServiceScope(
                    $booking,
                    $serviceRow['scope'] ?? null,
                    $serviceRow['booking_room_id'] ?? null
                );

                $quantity = max(1, (int) $serviceRow['quantity']);
                $unitPrice = (float) $service->price;
                $pricing = app(BookingServicePricingService::class);
                $nightCount = $pricing->nightCountForNewService($booking);

                $pendingQuery = BookingServiceItem::where('booking_id', $booking->id)
                    ->where('service_id', $service->id)
                    ->where('scope', $scope)
                    ->whereIn('type', ['service', 'minibar_order'])
                    ->where('billing_status', 'pending');

                $scope === 'room'
                    ? $pendingQuery->where('booking_room_id', $bookingRoom->id)
                    : $pendingQuery->whereNull('booking_room_id');

                if ($pendingQuery->lockForUpdate()->exists()) {
                    throw new \RuntimeException(
                        'Dịch vụ "' . $service->name . '" đã có yêu cầu của khách đang chờ xác nhận'
                        . ($scope === 'room' ? ' cho phòng ' . ($bookingRoom->room?->room_number ?? '---') : '')
                        . '. Hãy xác nhận hoặc từ chối yêu cầu hiện có thay vì thêm trùng.'
                    );
                }

                $existingQuery = BookingServiceItem::where('booking_id', $booking->id)
                    ->where('service_id', $service->id)
                    ->where('scope', $scope)
                    ->whereIn('type', ['service', 'minibar_order'])
                    ->where('billing_status', 'confirmed');

                $scope === 'room'
                    ? $existingQuery->where('booking_room_id', $bookingRoom->id)
                    : $existingQuery->whereNull('booking_room_id');

                // Sau khi đã check-in, mỗi lần gọi thêm là một snapshot mới.
                // Không gộp vào dòng đã xác nhận trước đó vì sẽ làm thay đổi ngược
                // số đêm/số phòng/số khách của phần dịch vụ đã chốt.
                $existingItem = ($booking->actual_check_in || $booking->status === 'checked_in')
                    ? null
                    : $existingQuery->lockForUpdate()->first();
                $oldItemTotal = (float) ($existingItem?->total ?? 0);
                $newBaseQuantity = $quantity + ($existingItem
                    ? max(1, (int) ($existingItem->base_quantity ?? $existingItem->quantity))
                    : 0);

                $snapshot = $pricing->snapshotForService(
                    $service,
                    $newBaseQuantity,
                    $unitPrice,
                    $nightCount,
                    $roomQuantity,
                    $guestCount
                );

                $scopeData = [
                    'scope' => $scope,
                    'booking_room_id' => $bookingRoom?->id,
                    'room_id_snapshot' => $bookingRoom?->room_id,
                    'source_type' => 'reception_service',
                    'source_id' => null,
                ];

                if ($existingItem) {
                    $existingItem->forceFill(array_merge($scopeData, [
                        'billing_rule_snapshot' => $snapshot['billing_rule_snapshot'],
                        'base_quantity' => $snapshot['base_quantity'],
                        'quantity' => $snapshot['quantity'],
                        'used_quantity' => $snapshot['used_quantity'],
                        'nights_snapshot' => $snapshot['nights_snapshot'],
                        'rooms_snapshot' => $snapshot['rooms_snapshot'],
                        'people_snapshot' => $snapshot['people_snapshot'],
                        'billing_status' => 'confirmed',
                        'total' => $snapshot['total'],
                    ]));

                    if (!empty($serviceRow['note'])) {
                        $existingItem->note = trim(($existingItem->note ? $existingItem->note . "\n" : '') . $serviceRow['note']);
                    }
                    $existingItem->save();
                } else {
                    BookingServiceItem::create(array_merge($scopeData, [
                        'booking_id' => $booking->id,
                        'service_id' => $service->id,
                        'name' => $service->name,
                        'type' => $service->type,
                        'billing_rule_snapshot' => $snapshot['billing_rule_snapshot'],
                        'unit_price' => $unitPrice,
                        'base_quantity' => $snapshot['base_quantity'],
                        'quantity' => $snapshot['quantity'],
                        'used_quantity' => $snapshot['used_quantity'],
                        'nights_snapshot' => $snapshot['nights_snapshot'],
                        'rooms_snapshot' => $snapshot['rooms_snapshot'],
                        'people_snapshot' => $snapshot['people_snapshot'],
                        'billing_status' => 'confirmed',
                        'confirmed_by' => Auth::id(),
                        'confirmed_at' => now(),
                        'confirm_note' => 'Dịch vụ hoặc minibar gọi thêm, tính tiền ngay vào booking.',
                        'total' => $snapshot['total'],
                        'note' => $serviceRow['note'] ?? null,
                    ]));
                }

                $addedDifference = (float) $snapshot['total'] - $oldItemTotal;
                $totalAdded += $addedDifference;
                $scopeLabel = $scope === 'room'
                    ? 'phòng ' . ($bookingRoom->room?->room_number ?? '---')
                    : 'toàn booking';
                $logMessages[] = $service->name . ' · ' . $scopeLabel
                    . ' (' . $service->billing_rule_label . '): ' . $snapshot['formula'];
            }

            $this->repriceAfterServiceChange($booking);
            $this->addBookingLog(
                $booking,
                'service_added',
                'Thêm dịch vụ/minibar: ' . implode('; ', $logMessages)
                . '. Tổng thay đổi: ' . number_format($totalAdded, 0, ',', '.') . 'đ.'
            );

            DB::commit();
            return back()->with('success', 'Đã thêm dịch vụ đúng phạm vi phòng/toàn booking và tính lại tiền.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Có lỗi khi thêm dịch vụ: ' . $e->getMessage());
        }
    }

    public function updateServiceItem(Request $request, Booking $booking, BookingServiceItem $bookingServiceItem)
    {
        $this->guardCanAccessBooking($booking);

        if ($bookingServiceItem->booking_id != $booking->id) {
            abort(404);
        }

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Booking đã kết thúc hoặc đã hủy nên không thể sửa dịch vụ.');
        }

        if (!in_array($bookingServiceItem->type, ['service', 'minibar_order'])) {
            return back()->with('error', 'Chỉ được sửa số lượng dịch vụ hoặc minibar gọi thêm.');
        }

        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'], true)) {
                throw new \RuntimeException('Booking vừa thay đổi trạng thái nên không thể sửa dịch vụ lúc này.');
            }
            $bookingServiceItem = BookingServiceItem::whereKey($bookingServiceItem->id)
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();
            if (!in_array($bookingServiceItem->type, ['service', 'minibar_order'], true)) {
                throw new \RuntimeException('Khoản thu này không phải dịch vụ/minibar có thể sửa số lượng.');
            }

            if (
                ($booking->actual_check_in || $booking->status === 'checked_in')
                && $bookingServiceItem->billing_status === 'confirmed'
            ) {
                throw new \RuntimeException(
                    'Dịch vụ đã xác nhận sau khi khách check-in được giữ nguyên snapshot lịch sử. '
                    . 'Nếu phát sinh thêm, hãy thêm một dòng dịch vụ mới thay vì sửa ngược dòng đã chốt.'
                );
            }

            $oldTotal = (float) $bookingServiceItem->total;
            $oldQuantity = (int) $bookingServiceItem->quantity;

            $newQuantity = (int) $data['quantity'];
            $pricing = app(BookingServicePricingService::class);
            $nightCount = $pricing->nightCountForNewService($booking);
            $booking->loadMissing(['bookingRooms', 'guests']);
            if (($bookingServiceItem->scope ?? 'booking') === 'room' && $bookingServiceItem->booking_room_id) {
                $roomQuantity = 1;
                $guestCount = max(1, $booking->guests->where('booking_room_id', $bookingServiceItem->booking_room_id)->count());
            } else {
                $roomQuantity = max(1, (int) $booking->room_quantity);
                $guestCount = max(1, (int) $booking->adult_count + (int) $booking->child_count + (int) ($booking->baby_count ?? 0));
            }
            $line = $pricing->calculateLine(
                $bookingServiceItem->billing_rule_snapshot ?: ($bookingServiceItem->service?->billing_rule ?? Service::BILLING_ONCE),
                $newQuantity,
                (float) $bookingServiceItem->unit_price,
                $nightCount,
                $roomQuantity,
                $guestCount
            );
            $newTotal = (float) $line['total'];
            $difference = $newTotal - $oldTotal;

            $bookingServiceItem->update([
                'billing_rule_snapshot' => $line['billing_rule'],
                'base_quantity' => $line['base_quantity'],
                'quantity' => $line['base_quantity'],
                'used_quantity' => $line['billed_quantity'],
                'nights_snapshot' => $line['night_count'],
                'rooms_snapshot' => $line['room_quantity'],
                'people_snapshot' => $line['guest_count'],
                'billing_status' => 'confirmed',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => $bookingServiceItem->confirmed_at ?: now(),
                'confirm_note' => 'Dịch vụ hoặc minibar gọi thêm, tính tiền ngay vào booking.',
                'total' => $newTotal,
            ]);

            $this->repriceAfterServiceChange($booking);

            $this->addBookingLog(
                $booking,
                'service_quantity_updated',
                'Cập nhật số lượng "' . $bookingServiceItem->name . '" từ ' . $oldQuantity . ' sang ' . $newQuantity . '. Chênh lệch: ' . number_format($difference, 0, ',', '.') . 'đ.'
            );

            DB::commit();

            return back()->with('success', 'Cập nhật số lượng dịch vụ thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi cập nhật số lượng dịch vụ: ' . $e->getMessage());
        }
    }

    public function destroyServiceItem(Booking $booking, BookingServiceItem $bookingServiceItem)
    {
        $this->guardCanAccessBooking($booking);

        if ($bookingServiceItem->booking_id != $booking->id) {
            abort(404);
        }

        if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'])) {
            return back()->with('error', 'Booking đã kết thúc hoặc đã hủy nên không thể xóa dịch vụ.');
        }

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            if (!in_array($booking->status, ['pending', 'confirmed', 'checked_in'], true)) {
                throw new \RuntimeException('Booking vừa thay đổi trạng thái nên không thể xóa dịch vụ lúc này.');
            }
            $bookingServiceItem = BookingServiceItem::whereKey($bookingServiceItem->id)
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();
            if (!in_array($bookingServiceItem->type, ['service', 'minibar_order'], true)) {
                throw new \RuntimeException('Khoản thu này không phải dịch vụ/minibar có thể xóa thủ công.');
            }

            $total = (float) $bookingServiceItem->total;
            $serviceName = $bookingServiceItem->name;
            $preserveHistory = (bool) $booking->actual_check_in || $booking->status === 'checked_in';

            if ($preserveHistory) {
                $wasConfirmed = $bookingServiceItem->billing_status === 'confirmed';
                $bookingServiceItem->forceFill([
                    'billing_status' => 'cancelled',
                    'confirm_note' => trim(
                        ($bookingServiceItem->confirm_note ? $bookingServiceItem->confirm_note . "\n" : '')
                        . 'Đã hủy ghi nhận bởi nhân viên lúc '
                        . now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                        . ' để giữ lịch sử sau check-in.'
                    ),
                ])->save();
            } else {
                $wasConfirmed = $bookingServiceItem->billing_status === 'confirmed';
                $bookingServiceItem->delete();
            }

            $this->repriceAfterServiceChange($booking);

            $this->addBookingLog(
                $booking,
                $preserveHistory ? 'service_cancelled' : 'service_removed',
                ($preserveHistory ? 'Hủy ghi nhận' : 'Xóa') . ' dịch vụ "' . $serviceName . '".'
                . ($wasConfirmed
                    ? ' Loại khỏi tổng tiền: ' . number_format($total, 0, ',', '.') . 'đ.'
                    : ' Yêu cầu chưa xác nhận nên chưa phát sinh tiền.')
            );

            DB::commit();

            return back()->with(
                'success',
                $preserveHistory
                    ? 'Đã hủy ghi nhận dịch vụ và giữ lại lịch sử, khoản này không còn được tính vào tổng tiền.'
                    : 'Đã xóa dịch vụ khỏi booking.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi xóa dịch vụ: ' . $e->getMessage());
        }
    }

    public function updatePaymentStatus(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        $request->validate([
            'payment_status' => 'required|in:unpaid,partial,paid',
        ]);

        $financials = app(BookingFinancialService::class);
        $oldPaymentStatus = $booking->payment_status;
        $financials->refreshPaymentStatus($booking);
        $booking->refresh();

        if ($oldPaymentStatus !== $booking->payment_status) {
            $this->addBookingLog(
                $booking,
                'payment_status_reconciled',
                'Đồng bộ trạng thái thanh toán theo giao dịch thực tế: '
                . $oldPaymentStatus . ' → ' . $booking->payment_status . '.'
            );
        }

        return back()->with(
            'success',
            'Trạng thái thanh toán đã được đồng bộ theo các giao dịch thành công. Muốn thu tiền, hãy dùng mục Ghi nhận thanh toán.'
        );
    }

    public function recordPayment(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status === 'cancelled') {
            return back()->with('error', 'Booking đã hủy nên không thể ghi nhận thanh toán.');
        }

        $data = $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer',
            'payment_type' => 'required|in:deposit_30,custom',
            'amount' => 'nullable|numeric|min:1000',
            'payment_note' => 'nullable|string|max:1000',
        ], [
            'payment_method.required' => 'Vui lòng chọn phương thức thu tiền.',
            'payment_method.in' => 'Phương thức thu tiền không hợp lệ.',
            'payment_type.required' => 'Vui lòng chọn kiểu thu tiền.',
            'payment_type.in' => 'Kiểu thu tiền không hợp lệ.',
            'amount.min' => 'Số tiền thu tối thiểu là 1.000đ.',
        ]);

        DB::beginTransaction();

        try {
            $booking = Booking::where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $financialService = app(BookingFinancialService::class);
            $payableTotal = $financialService->currentTotal($booking);
            $currentPaid = $financialService->paidTotal($booking);
            $requiredDeposit = $financialService->requiredDeposit($booking);
            $remaining = max(0, $payableTotal - $currentPaid);
            $depositShortfall = max(0, min($requiredDeposit - $currentPaid, $remaining));

            if ($remaining <= 0.01) {
                $financialService->refreshPaymentStatus($booking);
                DB::commit();

                return back()->with('success', 'Booking đã thanh toán đủ. Phần khách trả dư hiện có vẫn được giữ làm tiền trả trước để bù trừ dịch vụ/phụ thu phát sinh.');
            }

            $tenderedAmount = $data['payment_type'] === 'custom'
                ? (float) ($data['amount'] ?? 0)
                : 0;

            $amount = $this->resolveAdminPaymentAmount(
                $data['payment_type'],
                $tenderedAmount,
                $remaining,
                $depositShortfall
            );

            if ($amount <= 0) {
                throw new \Exception(
                    $data['payment_type'] === 'deposit_30'
                        ? 'Booking đã đủ mức cọc ' . $this->depositPercentLabel($booking) . ' hiện tại. Hãy chọn thu phần còn lại hoặc nhập số tiền khác.'
                        : 'Số tiền thu không hợp lệ.'
                );
            }

            // Ghi nhận đúng toàn bộ số tiền khách thực tế đưa/chuyển. Nếu booking
            // vẫn pending vì đang chờ VNPay/cọc, chuyển sang thanh toán tại quầy
            // phải thu tối thiểu đủ mức cọc trước khi bỏ cơ chế giữ chỗ có hạn.
            $newPaidAmount = $currentPaid + $amount;
            if ($booking->status === 'pending' && $newPaidAmount + 0.01 < $requiredDeposit) {
                throw new \Exception(
                    'Booking đang chờ cọc. Khi chuyển sang thanh toán tại quầy phải thu tối thiểu đủ mức cọc còn thiếu: '
                    . number_format(max(0, $requiredDeposit - $currentPaid), 0, ',', '.')
                    . 'đ.'
                );
            }

            $newOverpayment = max(0, $newPaidAmount - $payableTotal);
            $newPaymentStatus = $newPaidAmount + 0.01 >= $payableTotal ? 'paid' : 'partial';
            $allocatedDepositAfter = min($newPaidAmount, $requiredDeposit);
            $prepaidAfter = max(0, $newPaidAmount - $allocatedDepositAfter);
            $prepaymentAddedByThisPayment = max(0, $newOverpayment - max(0, $currentPaid - $payableTotal));

            $storedPaymentType = $data['payment_type'] === 'deposit_30'
                ? 'deposit_30'
                : 'custom';

            // Khách đã chuyển sang tiền mặt/chuyển khoản tại quầy thì mọi link
            // VNPay còn chờ của booking phải hết hiệu lực ngay.
            app(PendingPaymentRequestService::class)->expire(
                $booking->id,
                'customer_switched_to_counter_payment'
            );

            $payment = BookingPayment::create([
                'booking_id' => $booking->id,
                'provider' => $data['payment_method'],
                'txn_ref' => $this->generateAdminPaymentTxnRef($booking, $data['payment_method']),
                'amount' => $amount,
                'status' => 'success',
                'payment_type' => $storedPaymentType,
                'paid_at' => now('Asia/Ho_Chi_Minh'),
                'raw_response' => [
                    'source' => 'admin',
                    'method' => $data['payment_method'],
                    'type' => $data['payment_type'],
                    'note' => $data['payment_note'] ?? null,
                    'staff_id' => Auth::id(),
                    'tendered_amount' => $amount,
                    'recorded_amount' => $amount,
                    'required_deposit_at_payment' => $requiredDeposit,
                    'allocated_deposit_after' => $allocatedDepositAfter,
                    'prepaid_amount_after' => $prepaidAfter,
                    'overpayment_after' => $newOverpayment,
                    'retained_as_prepayment' => $prepaymentAddedByThisPayment,
                    'change_due' => 0,
                ],
            ]);

            $oldPaymentStatus = $booking->payment_status;

            $booking->forceFill([
                'payment_status' => $newPaymentStatus,
                'overpayment_amount' => $newOverpayment,
                'payment_expires_at' => null,
                'status' => $booking->status === 'pending' && $newPaidAmount + 0.01 >= $requiredDeposit
                    ? 'confirmed'
                    : $booking->status,
            ])->save();
            $bookingObserverBroadcastedPayment = $booking->wasChanged();

            $methodLabel = $data['payment_method'] === 'cash'
                ? 'tiền mặt tại quầy'
                : 'chuyển khoản tại quầy';

            $this->addBookingLog(
                $booking,
                'admin_payment_received',
                'Ghi nhận thanh toán ' . $methodLabel . ': '
                . number_format($amount, 0, ',', '.')
                . 'đ. Tổng đã thu: '
                . number_format($newPaidAmount, 0, ',', '.')
                . 'đ. Mức cọc ' . $this->depositPercentLabel($booking) . ' hiện tại: '
                . number_format($requiredDeposit, 0, ',', '.')
                . 'đ; đã phân bổ vào cọc: '
                . number_format($allocatedDepositAfter, 0, ',', '.')
                . 'đ; thanh toán thêm/trả trước: '
                . number_format($prepaidAfter, 0, ',', '.')
                . 'đ. Tổng booking hiện tại: '
                . number_format($payableTotal, 0, ',', '.')
                . 'đ. Trạng thái thanh toán: '
                . $oldPaymentStatus
                . ' → '
                . $newPaymentStatus
                . '. Mã giao dịch: '
                . $payment->txn_ref
                . ($newOverpayment > 0
                    ? '. Khách đang trả dư ' . number_format($newOverpayment, 0, ',', '.') . 'đ; giữ lại làm tiền trả trước để tự bù trừ phát sinh.'
                    : '')
                . (!empty($data['payment_note']) ? '. Ghi chú: ' . $data['payment_note'] : '')
            );

            DB::commit();

            // BookingObserver đã broadcast nếu model thực sự đổi. Chỉ phát tay
            // khi payment record mới được tạo nhưng các cột booking không đổi.
            if (!$bookingObserverBroadcastedPayment) {
                Realtime::booking($booking->id, 'payment_updated');
            }

            $successMessage = 'Đã ghi nhận toàn bộ ' . number_format($amount, 0, ',', '.') . 'đ khách thực tế thanh toán.';
            if ($newOverpayment > 0) {
                $successMessage .= ' Khách đang trả dư ' . number_format($newOverpayment, 0, ',', '.') . 'đ; khoản này được giữ làm tiền trả trước và sẽ tự bù trừ dịch vụ/phụ thu phát sinh.';
            }

            return back()->with('success', $successMessage);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể ghi nhận thanh toán: ' . $e->getMessage());
        }
    }

    private function calculateAdminPayableTotal(Booking $booking): float
    {
        return app(BookingFinancialService::class)->currentTotal($booking);
    }

    private function resolveAdminPaymentAmount(
        string $paymentType,
        float $customAmount,
        float $remaining,
        float $depositShortfall
    ): float {
        if ($paymentType === 'deposit_30') {
            return max(0, min($depositShortfall, $remaining));
        }

        // Thu số tiền khác phải lưu đúng toàn bộ số khách thực tế trả. Phần vượt
        // công nợ trở thành tiền trả trước, không bị cắt mất khỏi booking_payments.
        return max(0, $customAmount);
    }

    private function generateAdminPaymentTxnRef(Booking $booking, string $method): string
    {
        $prefix = $method === 'cash' ? 'CASH' : 'BANK';

        do {
            $txnRef = $prefix
                . $booking->booking_code
                . now('Asia/Ho_Chi_Minh')->format('YmdHis')
                . strtoupper(Str::random(5));

            $txnRef = preg_replace('/[^A-Za-z0-9]/', '', $txnRef);
        } while (BookingPayment::where('txn_ref', $txnRef)->exists());

        return $txnRef;
    }

    public function updateNote(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        $data = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $booking->update([
            'note' => $data['note'],
        ]);

        $this->addBookingLog(
            $booking,
            'note_update',
            'Cập nhật ghi chú nội bộ cho booking.'
        );

        return back()->with('success', 'Cập nhật ghi chú nội bộ thành công.');
    }

    private function guardCanAccessBooking(Booking $booking): void
    {
        $user = Auth::user();

        abort_unless($booking->canBeHandledBy($user), 403, 'Booking này đang được phân cho lễ tân khác hoặc bạn không có quyền xử lý.');
    }

    public function addGuest(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['confirmed', 'checked_in'], true)) {
            return back()->with('error', 'Chỉ có thể khai báo khách trước hoặc trong thời gian lưu trú.');
        }

        $guestPayloads = $request->boolean('batch_mode')
            ? collect((array) $request->input('guests', []))
                ->filter(fn ($guest) => filled($guest['full_name'] ?? null))
                ->map(fn ($guest, $index) => array_merge($guest, ['_batch_index' => (string) $index]))
                ->values()
                ->all()
            : [$request->all()];

        if (count($guestPayloads) === 0) {
            return back()->withErrors(['guests' => 'Vui lòng nhập ít nhất một khách lưu trú.'])->withInput();
        }

        // Ngày sinh là nguồn xác định nhóm tuổi. Không tin giá trị guest_type từ UI,
        // đồng thời phải phân loại trước khi sort để người lớn được tạo trước trẻ cần giám hộ.
        $guestPayloads = collect($guestPayloads)->map(function (array $payload) {
            if (filled($payload['birthday'] ?? null)) {
                $payload['guest_type'] = $this->guestTypeFromBirthday((string) $payload['birthday']);
            }
            return $payload;
        })->all();

        $createdGuests = collect();
        DB::transaction(function () use ($booking, $guestPayloads, $createdGuests) {
            $hasRepresentative = $booking->guests()->where('is_booking_representative', true)->exists();
            $payloads = collect($guestPayloads)
                ->sortBy(fn ($payload) => ($payload['guest_type'] ?? 'adult') === 'adult' ? 0 : 1)
                ->values();
            $createdByBatchIndex = [];

            if (!$hasRepresentative && !$payloads->contains(fn ($payload) => !empty($payload['is_booking_representative']))) {
                $firstAdultKey = $payloads->search(fn ($payload) => ($payload['guest_type'] ?? null) === 'adult');
                if ($firstAdultKey !== false) {
                    $firstAdultPayload = $payloads->get($firstAdultKey);
                    $firstAdultPayload['is_booking_representative'] = 1;
                    $payloads->put($firstAdultKey, $firstAdultPayload);
                }
            }

            foreach ($payloads as $index => $payload) {
                $batchIndex = (string) ($payload['_batch_index'] ?? $index);
                $guardianReference = (string) ($payload['guardian_reference'] ?? '');
                if (str_starts_with($guardianReference, 'existing:')) {
                    $payload['guardian_guest_id'] = (int) substr($guardianReference, 9);
                } elseif (str_starts_with($guardianReference, 'batch:')) {
                    $guardianBatchIndex = substr($guardianReference, 6);
                    $payload['guardian_guest_id'] = $createdByBatchIndex[$guardianBatchIndex] ?? null;
                }
                unset($payload['_batch_index'], $payload['guardian_reference']);

                $guestRequest = Request::create('/', 'POST', $payload);
                $guestRequest->setUserResolver(fn () => Auth::user());
                $data = $this->validateStayingGuest($guestRequest, $booking->fresh(['guests']));

                $data['cccd'] = $data['document_number'] ?? null;
                $data['status'] = $booking->status === 'checked_in' ? 'checked_in' : 'registered';
                $data['actual_check_in_at'] = $booking->status === 'checked_in' ? now() : null;
                $data['planned_check_in_at'] = $booking->check_in_at;
                $data['planned_check_out_at'] = $booking->check_out_at;
                $data['created_by'] = Auth::id();
                $data['updated_by'] = Auth::id();

                $guest = $booking->guests()->create($data);
                $createdGuests->push($guest);
                $createdByBatchIndex[$batchIndex] = $guest->id;

                if ($booking->status === 'checked_in') {
                    $this->syncBookingGuestCountsAndRoomStatus($booking, $guest->booking_room_id);
                }

                \App\Models\BookingGuestRoomHistory::create([
                    'booking_guest_id' => $guest->id,
                    'from_booking_room_id' => null,
                    'to_booking_room_id' => $guest->booking_room_id,
                    'started_at' => $guest->actual_check_in_at ?: now(),
                    'reason' => 'Khai báo phòng lưu trú ban đầu.',
                    'changed_by' => Auth::id(),
                ]);

                $this->addBookingLog(
                    $booking,
                    'guest_added',
                    'Thêm khách lưu trú: ' . $guest->full_name
                    . ' · ' . ($guest->guest_type === 'adult' ? 'Người lớn' : ($guest->guest_type === 'child' ? 'Trẻ em' : 'Em bé'))
                    . ' · Phòng ' . ($guest->bookingRoom?->room?->room_number ?? 'chưa xác định')
                );
            }
        });

        $freshBooking = $booking->fresh(['guests', 'bookingRooms.room.category']);
        app(BookingOccupancyFeeService::class)->reconcile($freshBooking);

        $capacityWarnings = $createdGuests
            ->map(fn ($guest) => $this->stayingGuestCapacityWarning($freshBooking, (int) $guest->booking_room_id))
            ->filter()->unique()->implode(' ');

        $response = back()->with('success', 'Đã thêm ' . $createdGuests->count() . ' khách lưu trú và gán phòng.');
        return $capacityWarnings !== '' ? $response->with('capacity_warning', $capacityWarnings) : $response;
    }

    public function updateGuest(Request $request, Booking $booking, \App\Models\BookingGuest $guest)
    {
        $this->guardCanAccessBooking($booking);

        if ((int) $guest->booking_id !== (int) $booking->id) {
            abort(404);
        }

        if (!in_array($booking->status, ['confirmed', 'checked_in'], true)) {
            return back()->with('error', 'Danh sách khách lưu trú đã bị khóa chỉnh sửa.');
        }

        $data = $this->validateStayingGuest($request, $booking, $guest);
        $oldBookingRoomId = (int) $guest->booking_room_id;

        DB::transaction(function () use ($booking, $guest, $data, $oldBookingRoomId) {

            $data['cccd'] = $data['document_number'] ?? null;
            $data['updated_by'] = Auth::id();
            $guest->update($data);

            if ($booking->status === 'checked_in') {
                $this->syncBookingGuestCountsAndRoomStatus($booking, $guest->booking_room_id);
                if ((int) $oldBookingRoomId !== (int) $guest->booking_room_id) {
                    $this->syncBookingGuestCountsAndRoomStatus($booking, $oldBookingRoomId);
                }
            }

            if ((int) $oldBookingRoomId !== (int) $guest->booking_room_id) {
                \App\Models\BookingGuestRoomHistory::where('booking_guest_id', $guest->id)
                    ->whereNull('ended_at')
                    ->update(['ended_at' => now()]);

                \App\Models\BookingGuestRoomHistory::create([
                    'booking_guest_id' => $guest->id,
                    'from_booking_room_id' => $oldBookingRoomId,
                    'to_booking_room_id' => $guest->booking_room_id,
                    'started_at' => now(),
                    'reason' => 'Lễ tân cập nhật phòng lưu trú của khách.',
                    'changed_by' => Auth::id(),
                ]);
            }

            $this->addBookingLog($booking, 'guest_updated', 'Cập nhật hồ sơ lưu trú: ' . $guest->full_name . '.');
        });

        $freshBooking = $booking->fresh(['guests', 'bookingRooms.room.category']);
        app(BookingOccupancyFeeService::class)->reconcile($freshBooking);
        $warnings = collect([$oldBookingRoomId, (int) $data['booking_room_id']])
            ->unique()
            ->map(fn ($bookingRoomId) => $this->stayingGuestCapacityWarning($freshBooking, (int) $bookingRoomId))
            ->filter()
            ->implode(' ');

        $response = back()->with('success', 'Đã cập nhật hồ sơ khách lưu trú.');
        return $warnings !== '' ? $response->with('capacity_warning', $warnings) : $response;
    }

    public function removeGuest(Booking $booking, \App\Models\BookingGuest $guest)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['confirmed', 'checked_in'], true)) {
            return back()->with('error', 'Không thể xóa khai báo khách sau khi booking đã kết thúc lưu trú.');
        }

        if ((int) $guest->booking_id !== (int) $booking->id) {
            abort(404);
        }

        if ($guest->dependants()->exists()) {
            return back()->with('error', 'Khách này đang là người giám hộ của trẻ em. Hãy đổi người giám hộ trước khi xóa.');
        }

        $guestName = $guest->full_name;
        $bookingRoomId = $guest->booking_room_id;
        $guest->delete();

        if ($booking->status === 'checked_in') {
            $this->syncBookingGuestCountsAndRoomStatus($booking, $bookingRoomId);
        }

        app(BookingOccupancyFeeService::class)->reconcile($booking->fresh(['guests', 'bookingRooms.room.category']));

        $this->addBookingLog($booking, 'guest_removed', 'Xóa khách lưu trú: ' . $guestName);

        return back()->with('success', 'Đã xóa khách lưu trú.');
    }

    private function validateStayingGuest(Request $request, Booking $booking, ?\App\Models\BookingGuest $guest = null): array
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            // Nhóm tuổi được server tự suy ra từ ngày sinh; field này chỉ giữ để tương thích UI cũ.
            'guest_type' => ['nullable', 'in:adult,child,infant'],
            'document_type' => ['nullable', 'in:cccd,passport,birth_certificate,personal_id,other,none'],
            'document_number' => ['nullable', 'string', 'max:50'],
            'birthday' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'in:male,female,other'],
            'nationality' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'booking_room_id' => ['required', 'integer', 'exists:booking_rooms,id'],
            'is_booking_representative' => ['nullable', 'boolean'],
            'guardian_guest_id' => ['nullable', 'integer', 'exists:booking_guests,id'],
            'guardian_relationship' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
            'no_document_acknowledged' => ['nullable', 'boolean'],
            'no_document_reason' => ['nullable', 'string', 'max:500'],
        ], [
            'full_name.required' => 'Vui lòng nhập họ và tên khách lưu trú.',
            'birthday.required' => 'Vui lòng nhập ngày sinh.',
            'gender.required' => 'Vui lòng chọn giới tính.',
            'nationality.required' => 'Vui lòng nhập quốc tịch.',
            'booking_room_id.required' => 'Mỗi khách phải được gán vào một phòng cụ thể.',
        ]);

        if (!$booking->bookingRooms()->whereKey($data['booking_room_id'])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'booking_room_id' => 'Phòng được chọn không thuộc booking này.',
            ]);
        }

        // Server là nguồn sự thật: ngày sinh tự quyết định Người lớn / Trẻ em / Em bé.
        $data['guest_type'] = $this->guestTypeFromBirthday((string) $data['birthday']);

        if ($request->boolean('is_booking_representative')) {
            $otherRepresentativeExists = $booking->guests()
                ->where('is_booking_representative', true)
                ->when($guest, fn ($query) => $query->where('id', '!=', $guest->id))
                ->exists();

            if ($otherRepresentativeExists) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'is_booking_representative' => 'Booking đã có người đại diện đoàn. Muốn đổi đại diện, hãy bỏ vai trò của người hiện tại trước rồi chọn người mới.',
                ]);
            }
        }

        $documentNumber = trim((string) ($data['document_number'] ?? ''));
        $documentType = (string) ($data['document_type'] ?? 'none');
        $hasValidDocument = $this->hasValidStayingGuestDocument($documentType, $documentNumber);

        if ($documentNumber !== '') {
            $duplicateDocument = $booking->guests()
                ->where('document_number', $documentNumber)
                ->when($guest, fn ($query) => $query->where('id', '!=', $guest->id))
                ->exists();

            if ($duplicateDocument) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'document_number' => 'Số giấy tờ này đã được khai cho một khách khác trong booking.',
                ]);
            }
        }

        // Khách chưa xuất trình giấy tờ chỉ được thêm sau khi lễ tân xác nhận panel rủi ro.
        // Với hồ sơ cũ đã xác nhận, chỉnh sửa thông tin khác không bắt xác nhận lại.
        $existingNoDocumentAcknowledgement = $guest
            && !$this->hasValidStayingGuestDocument((string) $guest->document_type, trim((string) $guest->document_number))
            && (bool) $guest->document_exception_acknowledged;

        if (!$hasValidDocument) {
            $acknowledgedNow = $request->boolean('no_document_acknowledged');
            if (!$acknowledgedNow && !$existingNoDocumentAcknowledgement) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'document_number' => 'Khách chưa xuất trình giấy tờ. Phải mở panel cảnh báo và xác nhận rủi ro trước khi thêm khách vào phòng.',
                    'no_document_acknowledged' => 'Chưa có xác nhận cho trường hợp khách không có giấy tờ.',
                ]);
            }

            $reason = trim((string) ($data['no_document_reason'] ?? ''));
            if ($reason === '' && $existingNoDocumentAcknowledgement) {
                $reason = trim((string) $guest->document_exception_reason);
            }
            if ($reason === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'no_document_reason' => 'Vui lòng ghi lý do khách chưa xuất trình giấy tờ.',
                ]);
            }

            // Chuẩn hóa để DB/UI không hiểu nhầm rằng khách có giấy tờ chỉ vì đã chọn một loại.
            $data['document_type'] = 'none';
            $data['document_number'] = null;

            if ($acknowledgedNow || !$existingNoDocumentAcknowledgement) {
                $data['document_exception_acknowledged'] = true;
                $data['document_exception_reason'] = $reason;
                $data['document_exception_acknowledged_at'] = now('Asia/Ho_Chi_Minh');
                $data['document_exception_acknowledged_by'] = Auth::id();
                $data['document_exception_acknowledged_by_name'] = (string) (Auth::user()?->name ?? 'Nhân viên khách sạn');
            } else {
                // Giữ nguyên audit của lần xác nhận trước.
                $data['document_exception_acknowledged'] = (bool) $guest->document_exception_acknowledged;
                $data['document_exception_reason'] = $guest->document_exception_reason;
                $data['document_exception_acknowledged_at'] = $guest->document_exception_acknowledged_at;
                $data['document_exception_acknowledged_by'] = $guest->document_exception_acknowledged_by;
                $data['document_exception_acknowledged_by_name'] = $guest->document_exception_acknowledged_by_name;
            }
        }

        unset($data['no_document_acknowledged'], $data['no_document_reason']);

        $isMinor = in_array($data['guest_type'], ['child', 'infant'], true);
        if (!$isMinor) {
            $data['guardian_guest_id'] = null;
            $data['guardian_relationship'] = null;
        } else {
            if (empty($data['guardian_guest_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'guardian_guest_id' => 'Trẻ em/em bé phải chọn một người lớn đi cùng làm người giám hộ.',
                ]);
            }

            $guardian = $booking->guests()
                ->whereKey($data['guardian_guest_id'])
                ->where('guest_type', 'adult')
                ->first();

            if (!$guardian || ($guest && (int) $guardian->id === (int) $guest->id)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'guardian_guest_id' => 'Người giám hộ phải là khách người lớn thuộc cùng booking.',
                ]);
            }

            if (empty(trim((string) ($data['guardian_relationship'] ?? '')))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'guardian_relationship' => 'Vui lòng ghi quan hệ với trẻ.',
                ]);
            }

            if (!$hasValidDocument) {
                $data['document_type'] = 'none';
            }
        }

        $data['is_booking_representative'] = $request->boolean('is_booking_representative');

        return $data;
    }

    private function guestTypeFromBirthday(string $birthday): string
    {
        $age = Carbon::parse($birthday, 'Asia/Ho_Chi_Minh')->startOfDay()->age;

        return $age <= self::INFANT_MAX_AGE
            ? 'infant'
            : ($age <= self::CHILD_MAX_AGE ? 'child' : 'adult');
    }

    private function hasValidStayingGuestDocument(?string $documentType, ?string $documentNumber): bool
    {
        return !empty($documentType)
            && $documentType !== 'none'
            && trim((string) $documentNumber) !== '';
    }

    private function resolveServiceScope(Booking $booking, ?string $scope, $bookingRoomId): array
    {
        $scope = $scope === 'room' || !empty($bookingRoomId) ? 'room' : 'booking';

        if ($scope === 'booking') {
            return [
                'booking',
                null,
                max(1, $booking->bookingRooms->count() ?: (int) $booking->room_quantity),
                max(1, $booking->guests->count() ?: ((int) $booking->adult_count + (int) $booking->child_count + (int) ($booking->baby_count ?? 0))),
            ];
        }

        $bookingRoom = $booking->bookingRooms->firstWhere('id', (int) $bookingRoomId);
        if (!$bookingRoom) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'services' => 'Phòng được chọn không thuộc booking này.',
            ]);
        }

        $guestCount = $booking->guests->where('booking_room_id', $bookingRoom->id)->count();
        if ($guestCount <= 0) {
            $guestCount = max(1, (int) $bookingRoom->adult_count + (int) $bookingRoom->child_count);
        }

        return ['room', $bookingRoom, 1, max(1, $guestCount)];
    }

    private function stayingGuestCapacityWarning(Booking $booking, int $bookingRoomId): ?string
    {
        $bookingRoom = $booking->bookingRooms->firstWhere('id', $bookingRoomId);
        if (!$bookingRoom) {
            return null;
        }

        $roomGuests = $booking->guests->where('booking_room_id', $bookingRoomId);
        $adultCount = $roomGuests->where('guest_type', 'adult')->count();
        $minorCount = $roomGuests->whereIn('guest_type', ['child', 'infant'])->count();
        $adultCapacity = (int) ($bookingRoom->room?->category?->adult_capacity ?? 0);
        $childCapacity = (int) ($bookingRoom->room?->category?->child_capacity ?? 0);

        $adultOver = max(0, $adultCount - $adultCapacity);
        $minorOver = max(0, $minorCount - $childCapacity);
        if ($adultOver === 0 && $minorOver === 0) {
            return null;
        }

        $roomNumber = $bookingRoom->room?->room_number ?? '---';
        $parts = [];
        if ($adultOver > 0) {
            $parts[] = 'vượt ' . $adultOver . ' người lớn';
        }
        if ($minorOver > 0) {
            $parts[] = 'vượt ' . $minorOver . ' trẻ em/em bé';
        }

        return 'Đã lưu hồ sơ nhưng phòng ' . $roomNumber . ' đang ' . implode(' và ', $parts)
            . ' so với sức chứa (' . $adultCapacity . ' NL / ' . $childCapacity . ' TE/EB). '
            . 'Khi check-in phải chọn xử lý vượt sức chứa: thu phụ phí, thêm phòng hoặc đổi hạng/phân lại khách.';
    }

    private function syncBookingGuestCountsAndRoomStatus(Booking $booking, ?int $affectedBookingRoomId = null): void
    {
        $booking->load(['guests', 'bookingRooms.room']);

        $booking->forceFill([
            'adult_count' => $booking->guests->where('guest_type', 'adult')->count(),
            'child_count' => $booking->guests->where('guest_type', 'child')->count(),
            'baby_count' => $booking->guests->where('guest_type', 'infant')->count(),
        ])->save();

        foreach ($booking->bookingRooms as $bookingRoom) {
            $roomGuests = $booking->guests->where('booking_room_id', $bookingRoom->id);
            $bookingRoom->update([
                'adult_count' => $roomGuests->where('guest_type', 'adult')->count(),
                'child_count' => $roomGuests->whereIn('guest_type', ['child', 'infant'])->count(),
            ]);

            if ($booking->status === 'checked_in' && $bookingRoom->room) {
                $bookingRoom->room->update([
                    'status' => $roomGuests->isNotEmpty() ? 'occupied' : 'reserved',
                ]);
            }
        }
    }

    private function repriceAfterServiceChange(Booking $booking): array
    {
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
            'roomInspections.items',
        ]);

        $oneNightRoomTotal = (float) $booking->bookingRooms->sum('price_at_booking');
        $preview = app(BookingRepricingService::class)->preview(
            $booking,
            Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh'),
            Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh'),
            $oneNightRoomTotal
        );

        return app(BookingRepricingService::class)->apply($booking, $preview);
    }

    private function addBookingLog(Booking $booking, string $action, string $description): void
    {
        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
        ]);
    }

    private function depositPercentLabel(Booking $booking): string
    {
        $percent = (float) app(HotelPolicyService::class)->forBooking($booking, 'payment.deposit_percent', 30);
        return rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') . '%';
    }

}