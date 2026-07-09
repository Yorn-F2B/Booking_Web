<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\BookingServiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\BookingPayment;
use App\Models\BookingLog;
use App\Models\HotelReview;
use App\Services\VnpayService;
use App\Services\PromotionService;
use App\Events\BookingRealtimeUpdated;
use App\Events\RoomRealtimeUpdated;
use App\Support\Realtime;
use App\Mail\BookingCreatedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    private const ONLINE_CHECK_IN_TIME = Booking::STANDARD_CHECK_IN_TIME;
    private const ONLINE_CHECK_OUT_TIME = Booking::STANDARD_CHECK_OUT_TIME;
    private const ONLINE_CHECK_IN_LABEL = '14:00';
    private const EARLY_CHECK_IN_LABEL = '13:00';
    private const ONLINE_CHECK_OUT_LABEL = '12:00';

    public function confirm(Request $request)
    {
        if (!Auth::check()) {
            session([
                'url.intended' => route('bookings.confirm', $request->only([
                    'room_category_id',
                    'check_in_date',
                    'check_out_date',
                    'adult_count',
                    'child_count',
                    'note',
                ])),
            ]);

            return redirect()
                ->route('login')
                ->with('error', 'Vui lòng đăng nhập để tiếp tục đặt phòng.');
        }

        $minOnlineCheckInDate = $this->getOnlineMinCheckInDate();

        $data = $request->validate([
            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date|after_or_equal:' . $minOnlineCheckInDate,
            'check_out_date' => 'required|date|after:check_in_date',
            'adult_count' => 'required|integer|min:1',
            'child_count' => 'nullable|integer|min:0',
            'note' => 'nullable|string|max:1000',
        ], [
            'check_in_date.after_or_equal' => 'Đã quá mốc giữ phòng online hôm nay lúc ' . self::ONLINE_CHECK_IN_LABEL . '. Vui lòng chọn ngày nhận phòng từ ' . Carbon::parse($minOnlineCheckInDate)->format('d/m/Y') . '.',
            'check_out_date.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
        ]);

        $checkInAt = $data['check_in_date'] . ' ' . self::ONLINE_CHECK_IN_TIME;
        $checkOutAt = $data['check_out_date'] . ' ' . self::ONLINE_CHECK_OUT_TIME;

        $roomCategory = RoomCategory::where('status', 'active')
            ->findOrFail($data['room_category_id']);

        if ($data['adult_count'] > $roomCategory->adult_capacity) {
            return back()
                ->withInput()
                ->with('error', 'Số người lớn vượt quá sức chứa của hạng phòng.');
        }

        if (($data['child_count'] ?? 0) > $roomCategory->child_capacity) {
            return back()
                ->withInput()
                ->with('error', 'Số trẻ em vượt quá sức chứa của hạng phòng.');
        }

        $customer = Customer::where('user_id', Auth::id())->first();

        if ($customer) {
            $activeBooking = $this->findActiveBookingInDateRange(
                $customer->id,
                $checkInAt,
                $checkOutAt
            );

            if ($activeBooking) {
                return redirect()
                    ->route('bookings.show', $activeBooking)
                    ->with('error', 'Bạn đã có một đơn phòng đang hoạt động trong khoảng thời gian này. Nếu đơn chưa thanh toán, vui lòng tiếp tục thanh toán hoặc hủy đơn trước khi đặt đơn mới.');
            }
        }

        $availableRoom = $this->findAvailableRoom(
            $roomCategory->id,
            $checkInAt,
            $checkOutAt
        );

        if (!$availableRoom) {
            $checkInText = date('d/m/Y', strtotime($data['check_in_date']));
            $checkOutText = date('d/m/Y', strtotime($data['check_out_date']));

            return back()
                ->withInput()
                ->with('error', 'Hạng phòng này không còn phòng trống từ ngày '
                    . $checkInText
                    . ' đến ngày '
                    . $checkOutText
                    . '. Vui lòng chọn ngày khác hoặc hạng phòng khác.');
        }

        $nightCount = $this->getNightCount(
            $data['check_in_date'],
            $data['check_out_date']
        );

        $estimatedTotal = $roomCategory->price * $nightCount;

        $services = Service::where('status', 'active')
            ->where('price', '>', 0)
            ->whereIn('type', ['service'])
            ->orderByRaw("FIELD(service_group, 'vehicle', 'food_drink', 'transport', 'laundry', 'wellness', 'room_support', 'general', 'other')")
            ->orderBy('name')
            ->get();

        $availablePromotions = app(PromotionService::class)->availablePromotions([
            'customer_id' => $customer?->id,
            'subtotal_amount' => $estimatedTotal,
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'night_count' => $nightCount,
            'room_quantity' => 1,
        ], 'user');

        return view('user.pages.booking-confirm', [
            'bookingData' => $data,
            'roomCategory' => $roomCategory,
            'customer' => $customer,
            'nightCount' => $nightCount,
            'estimatedTotal' => $estimatedTotal,
            'services' => $services,
            'availablePromotions' => $availablePromotions,
        ]);
    }

    public function store(Request $request)
    {
        $minOnlineCheckInDate = $this->getOnlineMinCheckInDate();

        $data = $request->validate([
            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date|after_or_equal:' . $minOnlineCheckInDate,
            'check_out_date' => 'required|date|after:check_in_date',
            'adult_count' => 'required|integer|min:1',
            'child_count' => 'nullable|integer|min:0',

            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'cccd' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',

            'note' => 'nullable|string|max:1000',
            'payment_type' => 'required|in:deposit_30,full_100',

            'services' => 'nullable|array',
            'services.*.service_id' => 'nullable|exists:services,id',
            'services.*.quantity' => 'nullable|integer|min:1',
            'services.*.note' => 'nullable|string|max:1000',

            'promotion_codes' => 'nullable|array',
            'promotion_codes.*' => 'nullable|string|max:50',
        ], [
            'check_in_date.after_or_equal' => 'Đã quá mốc giữ phòng online hôm nay lúc ' . self::ONLINE_CHECK_IN_LABEL . '. Vui lòng chọn ngày nhận phòng từ ' . Carbon::parse($minOnlineCheckInDate)->format('d/m/Y') . '.',
            'check_out_date.after' => 'Ngày trả phòng phải sau ngày nhận phòng.',
            'payment_type.required' => 'Vui lòng chọn hình thức thanh toán.',
            'payment_type.in' => 'Hình thức thanh toán không hợp lệ.',
        ]);

        $checkInAt = $data['check_in_date'] . ' ' . self::ONLINE_CHECK_IN_TIME;
        $checkOutAt = $data['check_out_date'] . ' ' . self::ONLINE_CHECK_OUT_TIME;

        $roomCategory = RoomCategory::where('status', 'active')
            ->findOrFail($data['room_category_id']);

        if ($data['adult_count'] > $roomCategory->adult_capacity) {
            return back()
                ->withInput()
                ->with('error', 'Số người lớn vượt quá sức chứa của hạng phòng.');
        }

        if (($data['child_count'] ?? 0) > $roomCategory->child_capacity) {
            return back()
                ->withInput()
                ->with('error', 'Số trẻ em vượt quá sức chứa của hạng phòng.');
        }

        $booking = DB::transaction(function () use ($data, $roomCategory, $checkInAt, $checkOutAt) {
            $customer = Customer::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                ],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'cccd' => $data['cccd'] ?? null,
                    'email' => $data['email'] ?? Auth::user()->email,
                    'address' => $data['address'] ?? null,
                    'status' => 'active',
                ]
            );

            if (
                $this->hasActiveBookingInDateRange(
                    $customer->id,
                    $checkInAt,
                    $checkOutAt
                )
            ) {
                return 'active_booking_exists';
            }

            $availableRoom = $this->findAvailableRoom(
                $roomCategory->id,
                $checkInAt,
                $checkOutAt
            );

            if (!$availableRoom) {
                return null;
            }

            $nightCount = $this->getNightCount(
                $data['check_in_date'],
                $data['check_out_date']
            );

            $serviceItems = $this->prepareServiceItems($data['services'] ?? []);
            $serviceItemTotal = collect($serviceItems)->sum('total');
            $subtotalAmount = ($roomCategory->price * $nightCount) + $serviceItemTotal;

            $promotionResult = app(PromotionService::class)->validateCodes(
                $data['promotion_codes'] ?? [],
                [
                    'customer_id' => $customer->id,
                    'subtotal_amount' => $subtotalAmount,
                    'service_items' => $serviceItems,
                    'check_in_at' => $checkInAt,
                    'check_out_at' => $checkOutAt,
                    'night_count' => $nightCount,
                    'room_quantity' => 1,
                ],
                'user'
            );

            if (!$promotionResult['ok']) {
                return [
                    'promotion_error' => implode(' ', $promotionResult['messages']),
                ];
            }

            $serviceItems = app(PromotionService::class)->mergeServiceItems(
                $serviceItems,
                $promotionResult['auto_service_items'] ?? []
            );

            $subtotalAmount = (float) $promotionResult['subtotal_amount'];
            $moneyDiscountAmount = (float) ($promotionResult['money_discount_total'] ?? 0);
            $serviceDiscountAmount = (float) ($promotionResult['service_discount_total'] ?? 0);
            $discountAmount = (float) $promotionResult['discount_total'];
            $estimatedTotal = max(0, $subtotalAmount - $discountAmount);

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'customer_id' => $customer->id,
                'created_by' => null,
                'room_category_id' => $roomCategory->id,
                'booking_type' => 'overnight',
                'booking_mode' => 'advance',
                'booking_source' => 'user_online',
                'cleaning_buffer_minutes' => 60,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'adult_count' => $data['adult_count'],
                'child_count' => $data['child_count'] ?? 0,
                'room_quantity' => 1,
                'prefer_adjacent_rooms' => 0,
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $discountAmount,
                'estimated_total' => $estimatedTotal,
                'deposit_amount' => 0,
                'payment_status' => 'unpaid',
                'status' => 'pending',
                'note' => $data['note'] ?? null,
            ]);

            foreach ($serviceItems as $item) {
                BookingServiceItem::create([
                    'booking_id' => $booking->id,
                    'service_id' => $item['service_id'],
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'used_quantity' => $item['used_quantity'],
                    'billing_status' => $item['billing_status'],
                    'total' => $item['total'],
                    'note' => $item['note'],
                ]);
            }

            app(PromotionService::class)->storeUsages(
                $booking,
                $promotionResult['promotions'],
                'user',
                null,
                Auth::id()
            );

            if ($promotionResult['promotions']->count() > 0) {
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => Auth::id(),
                    'action' => 'promotion_added',
                    'description' => 'Khách áp dụng mã ưu đãi khi đặt phòng online: '
                        . $promotionResult['promotions']->pluck('code')->implode(', ')
                        . '. Giảm tiền: '
                        . number_format($moneyDiscountAmount, 0, ',', '.')
                        . 'đ, ưu đãi dịch vụ: '
                        . number_format($serviceDiscountAmount, 0, ',', '.')
                        . 'đ, tổng ưu đãi: '
                        . number_format($discountAmount, 0, ',', '.')
                        . 'đ.',
                ]);
            }

            return $booking;
        });
        if (is_array($booking) && isset($booking['promotion_error'])) {
            return back()
                ->withInput()
                ->with('error', $booking['promotion_error']);
        }

        if ($booking === 'active_booking_exists') {
            $customer = Customer::where('user_id', Auth::id())->first();

            $activeBooking = $customer
                ? $this->findActiveBookingInDateRange($customer->id, $checkInAt, $checkOutAt)
                : null;

            if ($activeBooking) {
                return redirect()
                    ->route('bookings.show', $activeBooking)
                    ->with('error', 'Bạn đã có một đơn phòng đang hoạt động trong khoảng thời gian này. Nếu đơn chưa thanh toán, vui lòng tiếp tục thanh toán hoặc hủy đơn trước khi đặt đơn mới.');
            }

            return redirect()
                ->route('rooms')
                ->with('error', 'Bạn đã có một đơn đặt phòng đang hoạt động. Vui lòng kiểm tra đơn phòng hiện tại trước khi đặt đơn mới.');
        }

        if (!$booking) {
            $checkInText = date('d/m/Y', strtotime($data['check_in_date']));
            $checkOutText = date('d/m/Y', strtotime($data['check_out_date']));

            return back()
                ->withInput()
                ->with('error', 'Hạng phòng này không còn phòng trống từ ngày '
                    . $checkInText
                    . ' đến ngày '
                    . $checkOutText
                    . '. Phòng có thể vừa được người khác đặt, vui lòng chọn ngày khác.');
        }

        $booking->load(['customer', 'roomCategory', 'bookingRooms.room', 'serviceItems', 'payments']);

        event(new BookingRealtimeUpdated($booking, 'created'));

        foreach ($booking->bookingRooms as $bookingRoom) {
            if ($bookingRoom->room) {
                event(new RoomRealtimeUpdated($bookingRoom->room, 'reserved'));
            }
        }

        $paymentAmount = $this->calculateOnlinePaymentAmount($booking, $data['payment_type']);

        if ($paymentAmount <= 0) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('error', 'Số tiền thanh toán không hợp lệ. Vui lòng liên hệ lễ tân để được hỗ trợ.');
        }

        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'provider' => 'vnpay',
            'txn_ref' => $this->generatePaymentTxnRef($booking),
            'amount' => $paymentAmount,
            'status' => 'pending',
            'payment_type' => $data['payment_type'],
        ]);

        // Không gửi email xác nhận booking ở bước này vì khách chưa thanh toán.
        // Email xác nhận chính thức sẽ được gửi sau khi VNPay trả kết quả thành công.
        return redirect()->away(
            app(VnpayService::class)->createPaymentUrl($booking, $payment, $request)
        );
    }

    private function sendBookingCreatedEmail(Booking $booking, string $source = 'user_online'): array
    {
        $booking->loadMissing([
            'customer',
            'roomCategory',
            'bookingRooms.room',
            'serviceItems.service',
            'payments',
            'hotelReview.replier',
        ]);

        $email = $booking->customer->email
            ?: (Auth::check() ? Auth::user()->email : null);

        if (!$email) {
            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'booking_email_skipped',
                'description' => 'Không gửi email xác nhận booking vì khách chưa có email.',
            ]);

            return [
                'status' => 'skipped',
                'message' => 'Booking đã tạo nhưng chưa gửi email xác nhận vì khách chưa có email.',
            ];
        }

        try {
            Mail::to($email)->send(new BookingCreatedMail($booking, $source));

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'booking_email_sent',
                'description' => 'Đã gửi email xác nhận booking đến ' . $email . '.',
            ]);

            return [
                'status' => 'sent',
                'message' => 'Đã gửi email xác nhận booking đến ' . $email . '.',
            ];
        } catch (\Throwable $e) {
            Log::warning('Không gửi được email xác nhận booking.', [
                'booking_id' => $booking->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'action' => 'booking_email_failed',
                'description' => 'Không gửi được email xác nhận booking đến ' . $email . ': ' . $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'message' => 'Booking đã tạo nhưng chưa gửi được email xác nhận: ' . $e->getMessage(),
            ];
        }
    }


    public function history(Request $request)
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if (!$customer) {
            return redirect()
                ->route('rooms')
                ->with('error', 'Bạn chưa có thông tin khách hàng. Vui lòng đặt phòng trước.');
        }

        $allowedStatuses = [
            'pending',
            'confirmed',
            'checked_in',
            'inspection_requested',
            'checked_out',
            'completed',
            'cancelled',
        ];

        $selectedStatus = $request->input('status');
        $selectedPeriod = $request->input('period');
        $searchKeyword = trim((string) $request->input('q'));

        $bookingsQuery = Booking::with(['roomCategory', 'bookingRooms.room', 'hotelReview'])
            ->where('customer_id', $customer->id)
            ->when(in_array($selectedStatus, $allowedStatuses, true), function ($query) use ($selectedStatus) {
                $query->where('status', $selectedStatus);
            })
            ->when($searchKeyword !== '', function ($query) use ($searchKeyword) {
                $query->where('booking_code', 'like', '%' . $searchKeyword . '%');
            })
            ->when($selectedPeriod === '30_days', function ($query) {
                $query->where('created_at', '>=', now('Asia/Ho_Chi_Minh')->subDays(30));
            })
            ->when($selectedPeriod === '3_months', function ($query) {
                $query->where('created_at', '>=', now('Asia/Ho_Chi_Minh')->subMonths(3));
            })
            ->when($selectedPeriod === '12_months', function ($query) {
                $query->where('created_at', '>=', now('Asia/Ho_Chi_Minh')->subMonths(12));
            })
            ->latest();

        $bookings = $bookingsQuery
            ->paginate(10)
            ->withQueryString();

        $bookingStatusCounts = Booking::where('customer_id', $customer->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('user.pages.booking-history', compact(
            'bookings',
            'bookingStatusCounts',
            'selectedStatus',
            'selectedPeriod',
            'searchKeyword'
        ));
    }

    public function cancel(Booking $booking)
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if (!$customer || $booking->customer_id != $customer->id) {
            abort(403);
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return redirect()
                ->back()
                ->with('error', 'Không thể hủy đơn này vì đơn đã được xử lý.');
        }

        $booking->update([
            'status' => 'cancelled',
        ]);

        foreach ($booking->bookingRooms as $bookingRoom) {
            if ($bookingRoom->room) {
                $bookingRoom->room->update([
                    'status' => 'available',
                ]);
            }
        }

        Realtime::booking($booking, 'cancelled');

        return redirect()
            ->back()
            ->with('success', 'Hủy đơn đặt phòng thành công.');
    }

    public function show(Booking $booking)
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if (!$customer || $booking->customer_id != $customer->id) {
            abort(403);
        }

        $booking->load([
            'roomCategory',
            'bookingRooms.room',
            'serviceItems.service',
            'bookingPromotions.user',
            'bookingPromotions.serviceOffers',
            'promotionServiceOffers',
            'payments',
            'hotelReview.replier',
        ]);

        $availableServices = Service::where('status', 'active')
            ->where('price', '>', 0)
            ->whereIn('type', ['service', 'minibar'])
            ->orderByRaw("FIELD(service_group, 'vehicle', 'food_drink', 'transport', 'laundry', 'wellness', 'room_support', 'general', 'other')")
            ->orderByRaw("FIELD(type, 'service', 'minibar')")
            ->orderBy('name')
            ->get();

        $latestPayment = BookingPayment::where('booking_id', $booking->id)
            ->latest()
            ->first();

        $defaultPaymentType = $latestPayment->payment_type ?? 'deposit_30';

        $canReviewBooking = $booking->canBeReviewed();

        return view(
            'user.pages.booking-detail',
            compact('booking', 'availableServices', 'latestPayment', 'defaultPaymentType', 'canReviewBooking')
        );
    }

    public function current()
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if (!$customer) {
            return redirect()
                ->route('rooms')
                ->with('error', 'Bạn chưa có thông tin khách hàng. Vui lòng đặt phòng trước.');
        }

        $booking = $this->findCurrentActiveBookingForCustomer($customer->id);

        if (!$booking) {
            return redirect()
                ->route('home')
                ->with('error', 'Bạn chưa có đơn phòng đang hoạt động.');
        }

        return redirect()->route('bookings.show', $booking);
    }



    public function storeCustomerService(Request $request, Booking $booking)
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        if (!$customer || $booking->customer_id != $customer->id) {
            abort(403);
        }

        if (!in_array($booking->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'Chỉ có thể tự thêm dịch vụ sau khi đơn đã thanh toán cọc/thanh toán đủ và được xác nhận.');
        }

        if ($booking->payment_status === 'paid') {
            return back()->with('error', 'Đơn này đã thanh toán đủ nên không thể tự thêm dịch vụ. Vui lòng liên hệ lễ tân để được hỗ trợ.');
        }

        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'quantity' => 'required|integer|min:1|max:50',
            'note' => 'nullable|string|max:1000',
        ], [
            'service_id.required' => 'Vui lòng chọn dịch vụ.',
            'service_id.exists' => 'Dịch vụ không hợp lệ hoặc đã bị ẩn.',
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.integer' => 'Số lượng không hợp lệ.',
            'quantity.min' => 'Số lượng phải lớn hơn 0.',
            'quantity.max' => 'Số lượng quá lớn, vui lòng liên hệ lễ tân.',
        ]);

        $service = Service::where('id', $data['service_id'])
            ->where('status', 'active')
            ->where('price', '>', 0)
            ->whereIn('type', ['service', 'minibar'])
            ->first();

        if (!$service) {
            return back()->with('error', 'Dịch vụ không hợp lệ hoặc đã bị ẩn.');
        }

        DB::beginTransaction();

        try {
            $quantity = max(1, (int) $data['quantity']);
            $unitPrice = (float) $service->price;

            $billingStatus = $service->type == 'minibar' ? 'pending' : 'confirmed';
            $usedQuantity = $service->type == 'minibar' ? 0 : $quantity;
            $totalAdded = $unitPrice * $usedQuantity;

            $customerNote = trim($data['note'] ?? '');
            $newNote = 'Khách tự thêm trên website';

            if ($customerNote !== '') {
                $newNote .= ': ' . $customerNote;
            }

            $existingItem = BookingServiceItem::where('booking_id', $booking->id)
                ->where('service_id', $service->id)
                ->whereIn('type', ['service', 'minibar'])
                ->first();

            if ($existingItem) {
                $existingItem->quantity = (int) $existingItem->quantity + $quantity;

                if ($existingItem->type == 'minibar') {
                    $existingItem->used_quantity = 0;
                    $existingItem->billing_status = 'pending';
                    $existingItem->total = 0;
                } else {
                    $existingItem->used_quantity = (int) $existingItem->used_quantity + $quantity;
                    $existingItem->billing_status = 'confirmed';
                    $existingItem->total = (float) $existingItem->total + $totalAdded;
                }

                $existingItem->note = trim(($existingItem->note ? $existingItem->note . "
" : '') . $newNote);
                $existingItem->save();
            } else {
                BookingServiceItem::create([
                    'booking_id' => $booking->id,
                    'service_id' => $service->id,
                    'name' => $service->name,
                    'type' => $service->type,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'used_quantity' => $usedQuantity,
                    'billing_status' => $billingStatus,
                    'total' => $totalAdded,
                    'note' => $newNote,
                ]);
            }

            if ($totalAdded > 0) {
                $booking->estimated_total = (float) $booking->estimated_total + $totalAdded;
                $booking->save();
            }

            DB::commit();

            Realtime::booking($booking, 'service_updated');

            $booking->refresh();
            $booking->load(['customer', 'roomCategory', 'bookingRooms.room']);

            Realtime::booking($booking, 'created');
            if ($service->type == 'minibar') {
                return back()->with('success', 'Đã ghi nhận yêu cầu minibar. Khách sạn sẽ xác nhận số lượng thực dùng khi trả phòng.');
            }

            return back()->with('success', 'Đã thêm dịch vụ vào đơn phòng. Tổng tiền tạm tính đã được cập nhật.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi thêm dịch vụ: ' . $e->getMessage());
        }
    }

    private function prepareServiceItems(array $items): array
    {
        $preparedItems = [];
        $usedServiceIds = [];

        foreach ($items as $item) {
            if (empty($item['service_id'])) {
                continue;
            }

            $serviceId = (int) $item['service_id'];

            if (in_array($serviceId, $usedServiceIds)) {
                continue;
            }

            $service = Service::where('id', $serviceId)
                ->where('status', 'active')
                ->whereIn('type', ['service', 'minibar'])
                ->first();

            if (!$service) {
                continue;
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) $service->price;
            $billingStatus = $service->type == 'minibar' ? 'pending' : 'confirmed';
            $usedQuantity = $service->type == 'minibar' ? 0 : $quantity;
            $total = $unitPrice * $usedQuantity;

            $preparedItems[] = [
                'service_id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'used_quantity' => $usedQuantity,
                'billing_status' => $billingStatus,
                'total' => $total,
                'note' => $item['note'] ?? null,
            ];

            $usedServiceIds[] = $serviceId;
        }

        return $preparedItems;
    }

    private function findAvailableRoom($roomCategoryId, $checkInAt, $checkOutAt)
    {
        return Room::where('room_category_id', $roomCategoryId)
            ->availableForPeriod($checkInAt, $checkOutAt)
            ->inRandomOrder()
            ->first();
    }

    private function hasActiveBookingInDateRange($customerId, $checkInAt, $checkOutAt)
    {
        return $this->findActiveBookingInDateRange($customerId, $checkInAt, $checkOutAt) !== null;
    }

    private function findActiveBookingInDateRange($customerId, $checkInAt, $checkOutAt)
    {
        return Booking::where('customer_id', $customerId)
            ->whereIn('status', [
                'pending',
                'confirmed',
                'checked_in',
                'inspection_requested',
            ])
            ->where('check_in_at', '<', $checkOutAt)
            ->where('check_out_at', '>', $checkInAt)
            ->orderByRaw("FIELD(status, 'checked_in', 'inspection_requested', 'confirmed', 'pending')")
            ->latest()
            ->first();
    }

    private function findCurrentActiveBookingForCustomer($customerId)
    {
        return Booking::where('customer_id', $customerId)
            ->whereIn('status', [
                'pending',
                'confirmed',
                'checked_in',
                'inspection_requested',
            ])
            ->orderByRaw("FIELD(status, 'checked_in', 'inspection_requested', 'confirmed', 'pending')")
            ->latest()
            ->first();
    }

    private function getNightCount($checkInDate, $checkOutDate)
    {
        return max(
            1,
            (strtotime($checkOutDate) - strtotime($checkInDate)) / 86400
        );
    }

    private function getOnlineMinCheckInDate(): string
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $todayCheckInDeadline = $now->copy()->setTimeFromTimeString(self::ONLINE_CHECK_IN_TIME);

        if ($now->greaterThanOrEqualTo($todayCheckInDeadline)) {
            return $now->copy()->addDay()->toDateString();
        }

        return $now->toDateString();
    }

    private function getOnlineMinCheckOutDate(?string $checkInDate = null): string
    {
        $baseCheckInDate = $checkInDate ?: $this->getOnlineMinCheckInDate();

        return Carbon::parse($baseCheckInDate, 'Asia/Ho_Chi_Minh')
            ->addDay()
            ->toDateString();
    }

    private function isOnlineBookingClosedToday(): bool
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        return $now->greaterThanOrEqualTo(
            $now->copy()->setTimeFromTimeString(self::ONLINE_CHECK_IN_TIME)
        );
    }

    private function generateBookingCode()
    {
        do {
            $code = 'BK' . now()->format('YmdHis') . strtoupper(Str::random(3));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }

    private function calculateOnlinePaymentAmount(Booking $booking, string $paymentType): float
    {
        $estimatedTotal = (float) $booking->estimated_total;
        $currentPaid = (float) $booking->deposit_amount;
        $remaining = max(0, $estimatedTotal - $currentPaid);

        if ($paymentType === 'deposit_30') {
            $depositTarget = round($estimatedTotal * 0.3, 0);

            return max(0, min($depositTarget - $currentPaid, $remaining));
        }

        return $remaining;
    }

    private function generatePaymentTxnRef(Booking $booking): string
    {
        do {
            $txnRef = $booking->booking_code
                . now('Asia/Ho_Chi_Minh')->format('YmdHis')
                . strtoupper(Str::random(5));

            $txnRef = preg_replace('/[^A-Za-z0-9]/', '', $txnRef);
        } while (BookingPayment::where('txn_ref', $txnRef)->exists());

        return $txnRef;
    }
}
