<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingLog;
use App\Models\BookingRoom;
use App\Models\BookingServiceItem;
use App\Models\BookingStaffAssignment;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\PromotionService;
use App\Services\StayPricingPolicyService;
use App\Models\Promotion;
use App\Support\Realtime;
use App\Mail\BookingCreatedMail;
use App\Mail\AdminVnpayPaymentRequestMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\BookingCodeGenerator;
use App\Services\BookingIdentityGuard;


class BookingCreateController extends Controller
{
    private const DEFAULT_CLEANING_BUFFER_MINUTES = Booking::DEFAULT_CLEANING_BUFFER_MINUTES;
    private const PRIORITY_CLEANING_START_TIME = Booking::PRIORITY_CLEANING_START_TIME;
    private const EARLY_CHECK_IN_TIME = Booking::EARLY_CHECK_IN_TIME;
    private const OVERNIGHT_CHECK_IN_TIME = Booking::STANDARD_CHECK_IN_TIME;
    private const OVERNIGHT_CHECK_OUT_TIME = Booking::STANDARD_CHECK_OUT_TIME;

    public function create()
    {
        $roomCategories = RoomCategory::where('status', 'active')
            ->orderBy('name')
            ->get();

        $services = Service::where('status', 'active')
            ->where('price', '>', 0)
            ->whereIn('type', ['service'])
            ->orderByRaw("FIELD(service_group, 'vehicle', 'food_drink', 'transport', 'laundry', 'wellness', 'room_support', 'general', 'other')")
            ->orderBy('name')
            ->get();

        $availablePromotions = Promotion::with(['serviceOffers.service', 'roomUpgradeOffers.fromCategory', 'roomUpgradeOffers.toCategory'])
            ->where('status', 'active')
            ->where('admin_can_apply', true)
            ->orderByRaw("FIELD(promotion_type, 'normal_discount', 'event_discount', 'conditional_discount', 'support_discount')")
            ->orderBy('code')
            ->get();

        return view('admin.pages.bookings.create', compact('roomCategories', 'services', 'availablePromotions'));
    }



    public function checkCustomerAccount(Request $request)
    {
        $data = $request->validate([
            'customer_email' => ['required', 'email:rfc', 'max:150'],
        ]);

        $email = Str::lower(trim((string) $data['customer_email']));

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        $linkedCustomer = Customer::query()
            ->whereNull('deleted_at')
            ->whereNotNull('user_id')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->with('user:id,email')
            ->first();

        $accountEmail = $user?->email ?: $linkedCustomer?->user?->email;
        $hasAccount = (bool) ($user || $linkedCustomer);

        return response()->json([
            'has_account' => $hasAccount,
            'account_email' => $accountEmail,
            'message' => $hasAccount
                ? 'Email này đã có tài khoản khách hàng. Booking tạo tại quầy sẽ được quản lý trong tài khoản và không tra cứu ở mục booking vãng lai. Hãy báo khách đăng nhập bằng email này.'
                : 'Email chưa gắn tài khoản; booking có thể tra cứu tại mục booking vãng lai bằng mã booking và OTP email.',
        ]);
    }

    public function eligiblePromotions(Request $request)
    {
        $data = $request->validate([
            'customer_email' => 'nullable|email|max:150',
            'customer_phone' => 'nullable|string|max:20',
            'customer_cccd' => 'nullable|string|max:20',
            'subtotal_amount' => 'nullable|numeric|min:0',
            'night_count' => 'nullable|integer|min:1',
            'room_quantity' => 'nullable|integer|min:1',
            'check_in_at' => 'nullable|date',
            'check_out_at' => 'nullable|date',
        ]);

        $promotions = app(PromotionService::class)->availablePromotions([
            'customer_email' => $data['customer_email'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_cccd' => $data['customer_cccd'] ?? null,
            'subtotal_amount' => (float) ($data['subtotal_amount'] ?? 0),
            'night_count' => (int) ($data['night_count'] ?? 1),
            'room_quantity' => (int) ($data['room_quantity'] ?? 1),
            'check_in_at' => $data['check_in_at'] ?? null,
            'check_out_at' => $data['check_out_at'] ?? null,
        ], 'admin');

        return response()->json([
            'codes' => $promotions->pluck('code')->values(),
            'count' => $promotions->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'required|string|max:20',
            'customer_cccd' => 'required|regex:/^[0-9]{12}$/',
            'customer_birthday' => 'required|date|before_or_equal:' . now()->subYears(18)->toDateString(),
            'customer_email' => 'nullable|required_if:payment_method,vnpay|email|max:150',
            'customer_address' => 'nullable|string|max:255',

            'booking_mode' => 'required|in:advance,walk_in',
            'booking_type' => 'required|in:overnight,hourly',
            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'nullable|date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'allow_late_checkout' => 'nullable|boolean',
            'confirm_low_stock' => 'nullable|boolean',
            'confirm_adjacent_fallback' => 'nullable|boolean',

            'adult_count' => 'required|integer|min:1',
            'child_count' => 'nullable|integer|min:0',
            'room_quantity' => 'required|integer|min:1',
            'prefer_adjacent_rooms' => 'nullable|boolean',

            'deposit_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,bank_transfer,vnpay',
            'payment_type' => 'required|in:deposit_30',
            'confirm_counter_payment' => 'nullable',
            'note' => 'nullable|string|max:1000',

            'services' => 'nullable|array',
            'services.*.service_id' => 'nullable|exists:services,id',
            'services.*.quantity' => 'nullable|integer|min:1',
            'services.*.note' => 'nullable|string|max:1000',

            'promotion_codes' => 'nullable|array',
            'promotion_codes.*' => 'nullable|string|max:50',
            'promotion_note' => 'nullable|string|max:1000',
        ], [
            'customer_name.required' => 'Vui lòng nhập họ tên khách hàng.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại khách hàng.',
            'customer_cccd.required' => 'Vui lòng nhập CCCD người đứng tên booking.',
            'customer_cccd.regex' => 'CCCD phải gồm đúng 12 chữ số.',
            'customer_birthday.required' => 'Vui lòng nhập ngày sinh người đứng tên booking.',
            'customer_birthday.before_or_equal' => 'Người đứng tên booking phải đủ 18 tuổi.',
            'booking_mode.required' => 'Vui lòng chọn hình thức tạo booking.',
            'booking_mode.in' => 'Hình thức tạo booking không hợp lệ.',
            'booking_type.required' => 'Vui lòng chọn loại lưu trú.',
            'booking_type.in' => 'Loại lưu trú không hợp lệ.',
            'room_category_id.required' => 'Vui lòng chọn hạng phòng.',
            'check_in_date.required' => 'Vui lòng chọn ngày nhận phòng.',
            'check_in_date.after_or_equal' => 'Ngày nhận phòng không được nhỏ hơn hôm nay.',
            'check_out_date.date' => 'Ngày trả phòng không hợp lệ.',
            'check_in_time.date_format' => 'Giờ nhận phòng phải đúng định dạng 24 giờ, ví dụ 13:30 hoặc 14:00.',
            'check_out_time.date_format' => 'Giờ trả phòng phải đúng định dạng 24 giờ, ví dụ 16:30 hoặc 18:00.',
            'adult_count.required' => 'Vui lòng nhập số người lớn.',
            'room_quantity.required' => 'Vui lòng nhập số phòng.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'payment_type.in' => 'Kiểu thanh toán không hợp lệ.',
            'customer_email.required_if' => 'Thanh toán VNPay bắt buộc phải có email khách để gửi đường dẫn thanh toán.',
        ]);

        $roomCategory = RoomCategory::findOrFail($data['room_category_id']);
        $roomQuantity = (int) $data['room_quantity'];

        try {
            $period = $this->resolveBookingPeriod($data, $roomCategory, $roomQuantity);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $bookingMode = $period['booking_mode'];
        $bookingType = $period['booking_type'];
        $checkInAt = $period['check_in_at'];
        $checkOutAt = $period['check_out_at'];
        $nightCount = $period['night_count'];
        $policyFeeAmount = $period['policy_fee_amount'];
        $policyFeeNote = $period['policy_fee_note'];
        $policyExtraPercent = $period['policy_extra_percent'];

        $data['booking_mode'] = $bookingMode;
        $data['booking_type'] = $bookingType;
        $data['check_out_date'] = $checkOutAt->toDateString();

        $preferAdjacentRooms = $bookingType === 'overnight'
            && $request->boolean('prefer_adjacent_rooms')
            && $roomQuantity >= 2;

        $serviceItems = $this->prepareServiceItems($data['services'] ?? []);
        $serviceItemTotal = collect($serviceItems)->sum('total');

        $availableCountForSelectedPeriod = null;

        if ($bookingType === 'hourly') {
            $availableCountForSelectedPeriod = $this->countAvailableRooms(
                $data['room_category_id'],
                $checkInAt,
                $checkOutAt
            );
        }

        if ($preferAdjacentRooms) {
            $availableRooms = $this->getAdjacentRooms(
                $data['room_category_id'],
                $roomQuantity,
                $checkInAt,
                $checkOutAt
            );

            // Không đủ toàn bộ phòng liền kề nhưng vẫn còn đủ phòng cùng hạng:
            // đưa ra phương án ghép tốt nhất để lễ tân xác nhận thay vì báo lỗi khó hiểu.
            if ($availableRooms->count() < $roomQuantity) {
                $bestArrangement = $this->getBestAvailableRoomArrangement(
                    $data['room_category_id'],
                    $roomQuantity,
                    $checkInAt,
                    $checkOutAt
                );

                if ($bestArrangement->count() >= $roomQuantity) {
                    if (!$request->boolean('confirm_adjacent_fallback')) {
                        $arrangementLines = $this->describeRoomArrangement($bestArrangement);
                        $maxAdjacentCount = collect($arrangementLines)->max('count') ?? 1;

                        return back()
                            ->withInput()
                            ->with('adjacent_room_warning', [
                                'category_name' => $roomCategory->name,
                                'requested_quantity' => $roomQuantity,
                                'max_adjacent_count' => $maxAdjacentCount,
                                'room_numbers' => $bestArrangement->pluck('room_number')->values()->all(),
                                'lines' => $arrangementLines,
                            ]);
                    }

                    $availableRooms = $bestArrangement;
                } else {
                    // Không đủ tổng số phòng cùng hạng; giữ số phòng tìm được để luồng
                    // gợi ý hạng/phương án khác phía dưới xử lý như trước.
                    $availableRooms = $bestArrangement;
                }
            }
        } else {
            $availableRooms = $this->getAvailableRooms(
                $data['room_category_id'],
                $roomQuantity,
                $checkInAt,
                $checkOutAt
            );
        }

        if (
            $bookingType === 'hourly'
            && $availableCountForSelectedPeriod !== null
            && $availableCountForSelectedPeriod <= 2
            && !$request->boolean('confirm_low_stock')
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Cảnh báo: Hạng ' . $roomCategory->name
                    . ' chỉ còn ' . $availableCountForSelectedPeriod
                    . ' phòng trống trong khung giờ '
                    . $checkInAt->format('d/m/Y H:i')
                    . ' → ' . $checkOutAt->format('d/m/Y H:i')
                    . '. Nếu vẫn nhận khách ở ngay theo giờ, khách sạn có thể mất cơ hội bán phòng qua đêm hoặc khách đặt trước sau đó. Vui lòng tick xác nhận rủi ro tồn phòng thấp để tiếp tục.'
                );
        }

        if ($availableRooms->count() < $roomQuantity) {
            if ($bookingType === 'hourly') {
                $availableCount = $availableRooms->count();

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Không còn đủ phòng trống cho hạng ' . $roomCategory->name
                        . ' trong khung giờ ' . $checkInAt->format('d/m/Y H:i')
                        . ' → ' . $checkOutAt->format('d/m/Y H:i')
                        . '. Yêu cầu ' . $roomQuantity . ' phòng, hiện chỉ còn ' . $availableCount . ' phòng phù hợp.'
                    );
            }

            $suggestions = $this->generateRoomSuggestions(
                $data['room_category_id'],
                $roomQuantity,
                $checkInAt,
                $checkOutAt,
                $preferAdjacentRooms,
                $policyExtraPercent,
                $policyFeeNote,
                $serviceItemTotal
            );

            if ($suggestions->isEmpty()) {
                return back()
                    ->withInput()
                    ->with('error', 'Hiện không có phương án phòng nào đủ số lượng trong thời gian đã chọn.');
            }

            return view('admin.pages.bookings.suggestions', [
                'data' => $data,
                'roomCategory' => $roomCategory,
                'suggestions' => $suggestions,
                'preferAdjacentRooms' => $preferAdjacentRooms,
            ]);
        }

        $inventoryWarning = null;

        if ($bookingType === 'hourly') {
            $inventoryWarning = $this->getHourlyInventoryWarning(
                $data['room_category_id'],
                $roomCategory,
                $roomQuantity,
                $checkInAt,
                $checkOutAt,
                $availableRooms
            );
        }

        $roomTotal = $this->calculateRoomTotal(
            $roomCategory,
            $roomQuantity,
            $bookingType,
            $nightCount,
            $checkInAt,
            $checkOutAt
        );

        $subtotalAmount = $roomTotal + $policyFeeAmount + $serviceItemTotal;
        $paymentMethod = $data['payment_method'] ?? 'none';
        $paymentType = $data['payment_type'] ?? null;
        $customPaymentAmount = (float) ($data['deposit_amount'] ?? 0);

        if (in_array($paymentMethod, ['cash', 'bank_transfer'], true) && !$request->boolean('confirm_counter_payment')) {
            return back()->withInput()->with('error', 'Vui lòng tích xác nhận đã nhận đủ tiền cọc tại quầy trước khi tạo booking.');
        }

        try {
            $this->validateInitialPaymentChoice($paymentMethod, $paymentType, $customPaymentAmount, $subtotalAmount);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $pendingVnpayPayment = null;
        $booking = null;

        DB::beginTransaction();

        try {
            $candidateRoomIds = $availableRooms->pluck('id')->all();
            $availableRooms = Room::whereIn('id', $candidateRoomIds)
                ->lockForUpdate()
                ->bookableForPeriod($checkInAt, $checkOutAt)
                ->with('category')
                ->get();

            if ($availableRooms->count() !== count($candidateRoomIds)) {
                throw new \Exception('Một hoặc nhiều phòng vừa được booking khác giữ. Vui lòng chọn lại phòng.');
            }

            $this->assertRoomsStillFree($candidateRoomIds, $checkInAt, $checkOutAt);

            $customer = $this->createOrUpdateCustomer($data);
            $customerHasAccount = $customer->user_id !== null
                || User::query()->whereRaw('LOWER(email) = ?', [Str::lower(trim((string) ($data['customer_email'] ?? '')))])
                    ->exists();
            $this->assertCustomerHasNoOverlappingBooking($customer, $checkInAt, $checkOutAt);

            $promotionResult = app(PromotionService::class)->validateCodes(
                $data['promotion_codes'] ?? [],
                [
                    'customer_id' => $customer->id,
                    'customer_email' => $data['customer_email'] ?? $customer->email,
                    'customer_phone' => $data['customer_phone'] ?? $customer->phone,
                    'customer_cccd' => $data['customer_cccd'] ?? $customer->cccd,
                    'subtotal_amount' => $subtotalAmount,
                    'service_items' => $serviceItems,
                    'check_in_at' => $checkInAt,
                    'check_out_at' => $checkOutAt,
                    'night_count' => $nightCount,
                    'room_quantity' => $roomQuantity,
                ],
                'admin',
                $data['promotion_note'] ?? null
            );

            if (!$promotionResult['ok']) {
                throw new \Exception(implode(' ', $promotionResult['messages']));
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
            $initialPaymentAmount = $this->resolveInitialPaymentAmount(
                $paymentMethod,
                $paymentType,
                $customPaymentAmount,
                $estimatedTotal,
                0
            );
            $depositAmount = in_array($paymentMethod, ['cash', 'bank_transfer'], true)
                ? $initialPaymentAmount
                : 0;

            $hasRoomsPreparing = $availableRooms->contains(fn ($room) => in_array($room->status, ['cleaning', 'inspection'], true));

            if ($depositAmount > $estimatedTotal) {
                throw new \Exception('Số tiền thu không được lớn hơn tổng tiền sau giảm giá.');
            }

            app(BookingIdentityGuard::class)->assertEligible($customer);

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'customer_id' => $customer->id,
                ...Booking::customerSnapshotAttributes($customer),
                'created_by' => Auth::id(),
                'room_category_id' => $roomCategory->id,
                'booking_type' => $bookingType,
                'booking_mode' => $bookingMode,
                'booking_source' => 'reception',
                'check_in_date' => $checkInAt->toDateString(),
                'check_out_date' => $checkOutAt->toDateString(),
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'cleaning_buffer_minutes' => self::DEFAULT_CLEANING_BUFFER_MINUTES,
                'adult_count' => $data['adult_count'],
                'child_count' => $data['child_count'] ?? 0,
                'room_quantity' => $roomQuantity,
                'prefer_adjacent_rooms' => $preferAdjacentRooms,
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $discountAmount,
                'estimated_total' => $estimatedTotal,
                'deposit_amount' => $depositAmount,
                'payment_status' => $depositAmount >= $estimatedTotal && $estimatedTotal > 0 ? 'paid' : ($depositAmount > 0 ? 'partial' : 'unpaid'),
                'status' => $paymentMethod === 'vnpay' ? 'pending' : 'confirmed',
                // Ở ngay vẫn phải hoàn tất quy trình check-in tại trang chi tiết:
                // xác nhận khách lưu trú/CCCD, tiền cọc và tình trạng phòng trước khi giao phòng.
                'actual_check_in' => null,
                'note' => $data['note'] ?? null,
            ]);

            $this->autoAssignCreatedBooking($booking);

            foreach ($availableRooms as $room) {
                BookingRoom::create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'adult_count' => 0,
                    'child_count' => 0,
                    'price_at_booking' => $room->category->price ?? $roomCategory->price,
                    'surcharge' => 0,
                    'surcharge_reason' => null,
                    'created_at' => now(),
                ]);

                app(\App\Services\RoomPreparationService::class)
                    ->flagPriorityIfNeeded($booking, $room, 'lễ tân tạo booking');

                if (!in_array($room->status, ['cleaning', 'inspection', 'maintenance'], true)) {
                    $room->update([
                        'status' => 'reserved',
                        'status_from' => now('Asia/Ho_Chi_Minh'),
                    ]);
                }
            }

            foreach ($serviceItems as $item) {
                $this->createBookingServiceItem($booking, $item);
            }

            if ($policyFeeAmount > 0) {
                $this->createPolicyFeeItem(
                    $booking,
                    str_contains((string) $policyFeeNote, 'Trả phòng') || str_contains((string) $policyFeeNote, 'check-out')
                        ? 'Phụ thu nhận/trả phòng'
                        : 'Phụ thu nhận phòng sớm',
                    $policyFeeAmount,
                    $policyFeeNote
                );
            }

            app(PromotionService::class)->storeUsages(
                $booking,
                $promotionResult['promotions'],
                'admin',
                $data['promotion_note'] ?? null,
                Auth::id()
            );

            if ($promotionResult['promotions']->count() > 0) {
                $this->addBookingLog(
                    $booking,
                    'promotion_added',
                    'Áp dụng mã ưu đãi khi tạo booking: '
                    . $promotionResult['promotions']->pluck('code')->implode(', ')
                    . '. Giảm tiền: '
                    . number_format($moneyDiscountAmount, 0, ',', '.')
                    . 'đ, ưu đãi dịch vụ: '
                    . number_format($serviceDiscountAmount, 0, ',', '.')
                    . 'đ, tổng ưu đãi: '
                    . number_format($discountAmount, 0, ',', '.')
                    . 'đ.'
                    . (!empty($data['promotion_note']) ? ' Lý do: ' . $data['promotion_note'] : '')
                );
            }

            $roomNumbers = $availableRooms->pluck('room_number')->implode(', ');
            $bookingModeText = $bookingMode === 'advance' ? 'đặt trước' : 'ở ngay';
            $bookingTypeText = $bookingType === 'hourly' ? 'theo giờ' : 'qua đêm';

            $this->addBookingLog(
                $booking,
                'booking_created',
                'Tạo booking ' . $bookingModeText . ' - ' . $bookingTypeText
                . ' bởi lễ tân. Gán phòng: ' . $roomNumbers
                . '. Thời gian: ' . $checkInAt->format('d/m/Y H:i')
                . ' - ' . $checkOutAt->format('d/m/Y H:i')
                . ($policyFeeNote ? '. Chính sách giá: ' . $policyFeeNote : '')
                . (!empty($inventoryWarning) ? '. ' . $inventoryWarning : '')
                . ($discountAmount > 0 ? '. Ưu đãi giảm: ' . number_format($discountAmount, 0, ',', '.') . 'đ' : '')
                . '. Tổng tiền tạm tính: ' . number_format($estimatedTotal, 0, ',', '.') . 'đ.'
            );

            if (in_array($paymentMethod, ['cash', 'bank_transfer'], true) && $initialPaymentAmount > 0) {
                $this->recordInitialDirectPayment($booking, $paymentMethod, $paymentType, $initialPaymentAmount, $estimatedTotal);
            }

            if ($paymentMethod === 'vnpay' && $initialPaymentAmount > 0) {
                $pendingVnpayPayment = $this->createInitialVnpayPayment($booking, $paymentType, $initialPaymentAmount);
            }

            DB::commit();

            try {
                Realtime::booking($booking, $bookingMode === 'walk_in' ? 'walk_in_created' : 'staff_created');
            } catch (\Throwable $realtimeError) {
                Log::warning('Failed to send realtime notification', [
                    'booking_id' => $booking->id,
                    'error' => $realtimeError->getMessage(),
                ]);
            }

            if ($pendingVnpayPayment) {
                $emailResult = $this->sendInitialVnpayPaymentRequest(
                    $booking,
                    $pendingVnpayPayment,
                    (string) $customer->email
                );

                $redirect = redirect()
                    ->route('admin.bookings.show', $booking->id)
                    ->with($emailResult['status'] === 'sent' ? 'success' : 'error', $emailResult['message'])
                    ->with('admin_vnpay_payment_url', $emailResult['payment_url']);

                if ($customerHasAccount) {
                    $redirect->with('warning', 'Email khách đã có tài khoản. Booking này xem trong tài khoản khách và không tra cứu ở mục booking vãng lai.');
                }

                return $redirect;
            }

            $mailResult = $this->sendBookingCreatedEmail($booking, 'admin_created');

            return redirect()
                ->route('admin.bookings.show', $booking->id)
                ->with(
                    'success',
                    'Tạo booking và gán phòng thành công. ' . $mailResult['message']
                    . ($customerHasAccount
                        ? ' Lưu ý: email khách đã có tài khoản nên booking này xem trong tài khoản khách, không dùng mục tra cứu booking vãng lai.'
                        : ' Khách chưa có tài khoản có thể dùng mục tra cứu booking vãng lai bằng mã booking và email.')
                );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra khi tạo booking: ' . $e->getMessage());
        }
    }

    private function sendBookingCreatedEmail(Booking $booking, string $source = 'admin_created'): array
    {
        $booking->loadMissing([
            'customer',
            'roomCategory',
            'bookingRooms.room',
            'serviceItems.service',
            'payments',
        ]);

        $email = $booking->customer->email ?? null;

        if (!$email) {
            $this->addBookingLog(
                $booking,
                'booking_email_skipped',
                'Không gửi email xác nhận booking vì khách chưa có email.'
            );

            return [
                'status' => 'skipped',
                'message' => 'Chưa gửi email xác nhận vì khách chưa có email.',
            ];
        }

        try {
            Mail::to($email)->send(new BookingCreatedMail($booking, $source));

            $this->addBookingLog(
                $booking,
                'booking_email_sent',
                'Đã gửi email xác nhận booking đến ' . $email . '.'
            );

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

            $this->addBookingLog(
                $booking,
                'booking_email_failed',
                'Không gửi được email xác nhận booking đến ' . $email . ': ' . $e->getMessage()
            );

            return [
                'status' => 'failed',
                'message' => 'Chưa gửi được email xác nhận: ' . $e->getMessage(),
            ];
        }
    }

    private function validateInitialPaymentChoice(
        string $paymentMethod,
        ?string $paymentType,
        float $customPaymentAmount,
        float $temporaryTotal
    ): void {
        if (!in_array($paymentMethod, ['cash', 'bank_transfer', 'vnpay'], true)) {
            throw new \Exception('Booking bắt buộc thanh toán cọc 30%.');
        }

        if ($paymentType !== 'deposit_30') {
            throw new \Exception('Chỉ hỗ trợ mức cọc 30% khi tạo booking.');
        }

        if ($temporaryTotal <= 0) {
            throw new \Exception('Tổng tiền booking không hợp lệ để thực hiện thanh toán.');
        }
    }

    private function resolveInitialPaymentAmount(
        string $paymentMethod,
        ?string $paymentType,
        float $customPaymentAmount,
        float $estimatedTotal,
        float $currentPaid = 0
    ): float {
        if ($paymentMethod === 'none' || $estimatedTotal <= 0) {
            return 0;
        }

        $remaining = max(0, $estimatedTotal - $currentPaid);

        if ($paymentType === 'deposit_30') {
            $depositTarget = round($estimatedTotal * 0.3, 0);

            return max(0, min($depositTarget - $currentPaid, $remaining));
        }


        return min($customPaymentAmount, $remaining);
    }

    private function recordInitialDirectPayment(
        Booking $booking,
        string $paymentMethod,
        ?string $paymentType,
        float $amount,
        float $estimatedTotal
    ): void {
        $storedPaymentType = 'deposit_30';
        $methodLabel = $paymentMethod === 'cash'
            ? 'tiền mặt tại quầy'
            : 'chuyển khoản tại quầy';

        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'provider' => $paymentMethod,
            'txn_ref' => $this->generateInitialPaymentTxnRef($booking, $paymentMethod),
            'amount' => $amount,
            'status' => 'success',
            'payment_type' => $storedPaymentType,
            'paid_at' => now('Asia/Ho_Chi_Minh'),
            'raw_response' => [
                'source' => 'admin_create_booking',
                'method' => $paymentMethod,
                'type' => $paymentType,
                'staff_id' => Auth::id(),
            ],
        ]);

        $this->addBookingLog(
            $booking,
            'admin_payment_received',
            'Thu tiền khi tạo booking bằng ' . $methodLabel . ': '
            . number_format($amount, 0, ',', '.')
            . 'đ. Trạng thái thanh toán: '
            . $booking->payment_status
            . '. Mã giao dịch: '
            . $payment->txn_ref
            . '.'
        );
    }

    private function createInitialVnpayPayment(Booking $booking, ?string $paymentType, float $amount): BookingPayment
    {
        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'provider' => 'admin_vnpay',
            'txn_ref' => $this->generateInitialPaymentTxnRef($booking, 'vnpay'),
            'amount' => $amount,
            'status' => 'pending',
            'payment_type' => 'deposit_30',
        ]);

        $this->addBookingLog(
            $booking,
            'admin_vnpay_created',
            'Tạo giao dịch VNPay khi tạo booking: '
            . number_format($amount, 0, ',', '.')
            . 'đ ('
            . 'cọc 30%'
            . '). Mã giao dịch: '
            . $payment->txn_ref
            . '.'
        );

        return $payment;
    }

    private function sendInitialVnpayPaymentRequest(Booking $booking, BookingPayment $payment, string $email): array
    {
        $vnpayService = app(\App\Services\VnpayService::class);
        $expiresAt = now('Asia/Ho_Chi_Minh')->addMinutes($vnpayService->expireMinutes());
        $paymentUrl = route('payment.vnpay.admin-request', [
            'payment' => $payment->id,
            'token' => $vnpayService->paymentRequestToken($payment),
        ]);

        $rawResponse = $payment->raw_response ?? [];
        $rawResponse['source'] = 'admin_create_guest_booking';
        $rawResponse['customer_email'] = $email;
        $rawResponse['payment_request_url'] = $paymentUrl;
        $rawResponse['request_expires_at'] = $expiresAt->toDateTimeString();
        $rawResponse['request_expire_minutes'] = $vnpayService->expireMinutes();
        $payment->update(['raw_response' => $rawResponse]);

        $booking->update(['payment_expires_at' => $expiresAt]);

        try {
            Mail::to($email)->send(new AdminVnpayPaymentRequestMail($booking, $payment, $paymentUrl, $expiresAt));

            $rawResponse['email_sent_at'] = now('Asia/Ho_Chi_Minh')->toDateTimeString();
            $payment->update(['raw_response' => $rawResponse]);

            $this->addBookingLog(
                $booking,
                'admin_vnpay_email_sent',
                'Đã gửi email thanh toán VNPay cho khách vãng lai ' . $email
                . '. Số tiền cọc: ' . number_format((float) $payment->amount, 0, ',', '.')
                . 'đ. Hạn thanh toán: ' . $expiresAt->format('d/m/Y H:i') . '.'
            );

            return [
                'status' => 'sent',
                'message' => 'Đã tạo booking ở trạng thái chờ thanh toán và gửi đường dẫn VNPay tới ' . $email
                    . '. Booking chỉ được xác nhận sau khi VNPay thanh toán thành công.',
                'payment_url' => $paymentUrl,
            ];
        } catch (\Throwable $e) {
            $this->addBookingLog(
                $booking,
                'admin_vnpay_email_failed',
                'Đã tạo booking chờ thanh toán nhưng gửi email VNPay thất bại: ' . $e->getMessage()
            );

            return [
                'status' => 'failed',
                'message' => 'Đã tạo booking chờ thanh toán nhưng gửi email thất bại: ' . $e->getMessage()
                    . '. Có thể sao chép đường dẫn thanh toán ở trang chi tiết booking để gửi thủ công.',
                'payment_url' => $paymentUrl,
            ];
        }
    }

    private function generateInitialPaymentTxnRef(Booking $booking, string $method): string
    {
        $prefix = match ($method) {
            'cash' => 'CASH',
            'bank_transfer' => 'BANK',
            default => 'ADMVNP',
        };

        do {
            $txnRef = $prefix
                . $booking->booking_code
                . now('Asia/Ho_Chi_Minh')->format('YmdHis')
                . strtoupper(Str::random(5));

            $txnRef = preg_replace('/[^A-Za-z0-9]/', '', $txnRef);
        } while (BookingPayment::where('txn_ref', $txnRef)->exists());

        return $txnRef;
    }

    private function resolveBookingPeriod(array $data, RoomCategory $roomCategory, int $roomQuantity): array
    {
        $bookingMode = $data['booking_mode'];
        $bookingType = $data['booking_type'];
        $nightCount = 1;
        $policyFeeAmount = 0;
        $policyFeeNote = null;
        $policyExtraPercent = 0;

        if ($bookingMode === 'advance') {
            $bookingType = 'overnight';

            if (empty($data['check_out_date'])) {
                throw new \Exception('Vui lòng chọn ngày trả phòng cho booking đặt trước.');
            }

            if (strtotime($data['check_out_date']) <= strtotime($data['check_in_date'])) {
                throw new \Exception('Ngày trả phòng phải sau ngày nhận phòng đối với booking đặt trước.');
            }

            $checkInAt = Carbon::parse(
                $data['check_in_date'] . ' ' . self::OVERNIGHT_CHECK_IN_TIME,
                'Asia/Ho_Chi_Minh'
            );

            $checkOutAt = Carbon::parse(
                $data['check_out_date'] . ' ' . self::OVERNIGHT_CHECK_OUT_TIME,
                'Asia/Ho_Chi_Minh'
            );

            $nightCount = max(
                1,
                $checkInAt->copy()->startOfDay()->diffInDays($checkOutAt->copy()->startOfDay())
            );
        } elseif ($bookingMode === 'walk_in' && $bookingType === 'hourly') {
            if (empty($data['check_in_time'])) {
                throw new \Exception('Vui lòng chọn giờ vào cho booking ở ngay theo giờ.');
            }

            if (empty($data['check_out_date'])) {
                throw new \Exception('Vui lòng chọn ngày ra cho booking ở ngay theo giờ.');
            }

            if (empty($data['check_out_time'])) {
                throw new \Exception('Vui lòng chọn giờ ra dự kiến cho booking ở ngay theo giờ.');
            }

            $checkInAt = Carbon::parse(
                $data['check_in_date'] . ' ' . $data['check_in_time'] . ':00',
                'Asia/Ho_Chi_Minh'
            );

            $checkOutAt = Carbon::parse(
                $data['check_out_date'] . ' ' . $data['check_out_time'] . ':00',
                'Asia/Ho_Chi_Minh'
            );

            if ($checkOutAt->equalTo($checkInAt)) {
                throw new \Exception('Giờ ra phải khác giờ vào.');
            }


            $nowVn = now('Asia/Ho_Chi_Minh');

            if ($checkInAt->lt($nowVn->copy()->subMinutes(5))) {
                throw new \Exception('Giờ vào của booking ở ngay không được nhỏ hơn thời điểm hiện tại.');
            }

            $durationMinutes = $checkInAt->diffInMinutes($checkOutAt);

            if ($durationMinutes < 30) {
                throw new \Exception('Thời gian ở theo giờ phải tối thiểu 30 phút.');
            }

            $pricingPolicy = app(StayPricingPolicyService::class);

            if ($durationMinutes > 12 * 60) {
                $longStay = $pricingPolicy->longStay(
                    $checkInAt,
                    $checkOutAt,
                    (float) $roomCategory->price,
                    $roomQuantity
                );

                $bookingType = 'overnight';
                $nightCount = $longStay['night_count'];
                $policyFeeAmount = $longStay['surcharge_amount'];
                $policyFeeNote = $longStay['policy_text'];
                $policyExtraPercent = (($longStay['early']['percent'] + $longStay['late']['percent']) / 100);
            } else {
                $hourlyPolicy = $this->calculateWalkInHourlyPrice(
                    (float) $roomCategory->price,
                    $roomQuantity,
                    $durationMinutes
                );

                $policyFeeNote = $hourlyPolicy['policy_text'];
            }
        } elseif ($bookingMode === 'walk_in' && $bookingType === 'overnight') {
            $checkInTime = $data['check_in_time'] ?? now('Asia/Ho_Chi_Minh')->format('H:i');

            if (empty($data['check_out_date'])) {
                throw new \Exception('Vui lòng chọn ngày trả phòng cho booking ở ngay qua đêm.');
            }

            if (strtotime($data['check_out_date']) <= strtotime($data['check_in_date'])) {
                throw new \Exception('Ngày trả phòng phải sau ngày nhận phòng.');
            }

            $checkInAt = Carbon::parse(
                $data['check_in_date'] . ' ' . $checkInTime . ':00',
                'Asia/Ho_Chi_Minh'
            );

            // Qua đêm luôn trả phòng lúc 12:00 của ngày trả đã chọn.
            $checkOutAt = Carbon::parse(
                $data['check_out_date'] . ' ' . self::OVERNIGHT_CHECK_OUT_TIME,
                'Asia/Ho_Chi_Minh'
            );

            $nightCount = max(
                1,
                $checkInAt->copy()->startOfDay()->diffInDays($checkOutAt->copy()->startOfDay())
            );

            // Phụ thu nhận phòng sớm chỉ tính một lần cho ngày đầu tiên,
            // còn tiền phòng tính đủ số đêm mà khách chọn.
            $policy = $this->getWalkInOvernightPolicy($checkInAt, $roomCategory, $roomQuantity);
            $policyFeeAmount = $policy['extra_fee_amount'];
            $policyExtraPercent = $policy['extra_percent'];
            $policyFeeNote = $policy['policy_text']
                . ' Khách ở ' . $nightCount . ' đêm và trả phòng lúc 12:00 ngày '
                . $checkOutAt->format('d/m/Y') . '.';
        } else {
            throw new \Exception('Hình thức booking không hợp lệ.');
        }

        if ($checkOutAt->lessThanOrEqualTo($checkInAt)) {
            throw new \Exception('Thời gian trả phòng phải sau thời gian nhận phòng.');
        }

        return [
            'booking_mode' => $bookingMode,
            'booking_type' => $bookingType,
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'night_count' => $nightCount,
            'policy_fee_amount' => $policyFeeAmount,
            'policy_fee_note' => $policyFeeNote,
            'policy_extra_percent' => $policyExtraPercent,
        ];
    }

    private function getWalkInOvernightPolicy(Carbon $checkInAt, RoomCategory $roomCategory, int $roomQuantity): array
    {
        $baseRoomTotal = (float) $roomCategory->price * max(1, $roomQuantity);
        $policy = app(StayPricingPolicyService::class)->earlyCheckIn($checkInAt, $baseRoomTotal);

        return [
            'check_out_at' => $checkInAt->copy()->addDay()->setTime(12, 0, 0),
            'night_count' => 1,
            'extra_percent' => $policy['percent'] / 100,
            'extra_fee_amount' => $policy['amount'],
            'policy_text' => $policy['policy_text'],
        ];
    }

    private function calculateRoomTotal(
        RoomCategory $roomCategory,
        int $roomQuantity,
        string $bookingType,
        int $nightCount,
        ?Carbon $checkInAt = null,
        ?Carbon $checkOutAt = null
    ): float {
        if ($bookingType === 'hourly') {
            if (!$checkInAt || !$checkOutAt) {
                return 0;
            }

            $durationMinutes = $checkInAt->diffInMinutes($checkOutAt);

            $hourlyPolicy = $this->calculateWalkInHourlyPrice(
                (float) $roomCategory->price,
                $roomQuantity,
                $durationMinutes
            );

            return $hourlyPolicy['amount'];
        }

        return (float) $roomCategory->price * $roomQuantity * max(1, $nightCount);
    }

    private function getHourlyInventoryWarning(
        $roomCategoryId,
        RoomCategory $roomCategory,
        int $roomQuantity,
        Carbon $checkInAt,
        Carbon $checkOutAt,
        $availableRooms
    ): ?string {
        $cleaningBufferMinutes = self::DEFAULT_CLEANING_BUFFER_MINUTES;
        $cleaningUntil = $checkOutAt->copy()->addMinutes($cleaningBufferMinutes);
        $overnightStartAt = $checkInAt->copy()->setTimeFromTimeString(self::OVERNIGHT_CHECK_IN_TIME);
        $overnightEndAt = $overnightStartAt->copy()->addDay()->setTimeFromTimeString(self::OVERNIGHT_CHECK_OUT_TIME);

        if (!$cleaningUntil->greaterThan($overnightStartAt)) {
            return null;
        }

        $selectedHourlyRoomIds = $availableRooms->pluck('id')->toArray();

        $overnightAvailableAfterHourly = $this->availableRoomQuery(
            $roomCategoryId,
            $overnightStartAt,
            $overnightEndAt
        )
            ->whereNotIn('id', $selectedHourlyRoomIds)
            ->count();

        return 'Cảnh báo: ca thuê theo giờ này trả phòng lúc '
            . $checkOutAt->format('d/m/Y H:i')
            . ', cộng ' . $cleaningBufferMinutes . ' phút dọn phòng sẽ chiếm phòng đến '
            . $cleaningUntil->format('d/m/Y H:i')
            . ', vượt mốc check-in cam kết 14:00. '
            . 'Sau khi giữ ' . $roomQuantity . ' phòng theo giờ, hạng '
            . $roomCategory->name . ' còn ' . $overnightAvailableAfterHourly
            . ' phòng có thể bán qua đêm trong khung '
            . $overnightStartAt->format('d/m/Y H:i') . ' → '
            . $overnightEndAt->format('d/m/Y H:i')
            . '. Lễ tân vẫn được tạo booking nếu khách xác nhận thuê theo giờ.';
    }

    private function calculateLateCheckoutFee(string $checkoutTime, float $roomPrice, int $roomQuantity): array
    {
        $checkOutAt = Carbon::parse('2000-01-01 ' . $checkoutTime, 'Asia/Ho_Chi_Minh');
        $policy = app(StayPricingPolicyService::class)->lateCheckOut(
            $checkOutAt,
            $roomPrice * max(1, $roomQuantity)
        );

        return [
            'extra_fee_amount' => $policy['amount'],
            'policy_text' => $policy['policy_text'],
            'add_night' => $policy['percent'] === 100,
        ];
    }

    private function assertRoomsStillFree(array $roomIds, $checkInAt, $checkOutAt): void
    {
        $roomIds = collect($roomIds)->map(fn ($id) => (int) $id)->unique()->values();
        if ($roomIds->isEmpty()) {
            throw new \RuntimeException('Không có phòng hợp lệ để giữ.');
        }

        $conflicts = DB::table('booking_rooms')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->join('rooms', 'rooms.id', '=', 'booking_rooms.room_id')
            ->whereIn('booking_rooms.room_id', $roomIds->all())
            ->whereNull('bookings.deleted_at')
            ->where(function ($query) {
                $query->whereIn('bookings.status', ['confirmed', 'checked_in', 'inspection_requested'])
                    ->orWhere(function ($pending) {
                        $pending->where('bookings.status', 'pending')
                            ->whereNotNull('bookings.payment_expires_at')
                            ->where('bookings.payment_expires_at', '>', now('Asia/Ho_Chi_Minh'));
                    });
            })
            ->where('bookings.check_in_at', '<', Carbon::parse($checkOutAt, 'Asia/Ho_Chi_Minh')->toDateTimeString())
            ->where('bookings.check_out_at', '>', Carbon::parse($checkInAt, 'Asia/Ho_Chi_Minh')->toDateTimeString())
            ->select('bookings.booking_code', 'rooms.room_number')
            ->lockForUpdate()
            ->get();

        if ($conflicts->isNotEmpty()) {
            $details = $conflicts->map(fn ($row) => 'phòng ' . $row->room_number . ' (' . $row->booking_code . ')')->unique()->implode(', ');
            throw new \RuntimeException('Phòng vừa được đơn khác giữ: ' . $details . '. Vui lòng tải lại và chọn phòng khác.');
        }
    }

    private function assertCustomerHasNoOverlappingBooking(Customer $customer, $checkInAt, $checkOutAt): void
    {
        $cccd = preg_replace('/\D+/', '', (string) $customer->cccd);
        $customerName = $this->normalizeCustomerIdentityName(
            trim((string) $customer->last_name . ' ' . (string) $customer->first_name)
        );

        // Không đủ CCCD hoặc họ tên thì không kết luận đây là cùng một người.
        // Email và số điện thoại chỉ là thông tin liên hệ, có thể được dùng để đặt hộ.
        if ($cccd === '' || $customerName === '') {
            return;
        }

        $checkIn = Carbon::parse($checkInAt, 'Asia/Ho_Chi_Minh')->toDateTimeString();
        $checkOut = Carbon::parse($checkOutAt, 'Asia/Ho_Chi_Minh')->toDateTimeString();

        $candidates = Booking::query()
            ->whereNull('deleted_at')
            ->where(function ($active) {
                $active->whereIn('status', ['confirmed', 'checked_in', 'inspection_requested'])
                    ->orWhere(function ($pending) {
                        $pending->where('status', 'pending')
                            ->whereNotNull('payment_expires_at')
                            ->where('payment_expires_at', '>', now('Asia/Ho_Chi_Minh'));
                    });
            })
            ->where('check_in_at', '<', $checkOut)
            ->where('check_out_at', '>', $checkIn)
            ->where(function ($identity) use ($cccd) {
                $identity->whereRaw(
                    "REPLACE(REPLACE(REPLACE(customer_cccd_snapshot, ' ', ''), '-', ''), '.', '') = ?",
                    [$cccd]
                )->orWhereHas('customer', function ($customerQuery) use ($cccd) {
                    $customerQuery->whereRaw(
                        "REPLACE(REPLACE(REPLACE(cccd, ' ', ''), '-', ''), '.', '') = ?",
                        [$cccd]
                    );
                });
            })
            ->lockForUpdate()
            ->get();

        $existing = $candidates->first(function (Booking $booking) use ($customerName) {
            $snapshotName = $this->normalizeCustomerIdentityName((string) $booking->customer_name_snapshot);
            if ($snapshotName !== '') {
                return $snapshotName === $customerName;
            }

            $relatedCustomer = $booking->customer;
            $storedName = $relatedCustomer
                ? trim((string) $relatedCustomer->last_name . ' ' . (string) $relatedCustomer->first_name)
                : '';

            return $this->normalizeCustomerIdentityName($storedName) === $customerName;
        });

        if ($existing) {
            throw new \RuntimeException(
                'Khách ' . trim((string) $customer->last_name . ' ' . (string) $customer->first_name)
                . ' với CCCD ' . $customer->cccd
                . ' đã có booking ' . $existing->booking_code
                . ' trùng thời gian. Nếu cần nhiều phòng, hãy tăng số lượng phòng trong booking hiện có.'
            );
        }
    }

    private function createOrUpdateCustomer(array $data)
    {
        $nameParts = preg_split('/\s+/', trim($data['customer_name']));
        $firstName = array_pop($nameParts);
        $lastName = implode(' ', $nameParts);
        $cccd = preg_replace('/\D+/', '', (string) ($data['customer_cccd'] ?? ''));
        $normalizedName = $this->normalizeCustomerIdentityName((string) $data['customer_name']);

        $existingCustomer = null;
        if ($cccd !== '' && $normalizedName !== '') {
            $existingCustomer = Customer::query()
                ->whereNull('deleted_at')
                ->whereRaw(
                    "REPLACE(REPLACE(REPLACE(cccd, ' ', ''), '-', ''), '.', '') = ?",
                    [$cccd]
                )
                ->get()
                ->first(function (Customer $candidate) use ($normalizedName) {
                    $candidateName = trim((string) $candidate->last_name . ' ' . (string) $candidate->first_name);
                    return $this->normalizeCustomerIdentityName($candidateName) === $normalizedName;
                });
        }

        $attributes = [
            'first_name' => $firstName ?: $data['customer_name'],
            'last_name' => $lastName,
            'phone' => $data['customer_phone'],
            'cccd' => $data['customer_cccd'] ?? null,
            'email' => $data['customer_email'] ?? null,
            'birthday' => $data['customer_birthday'] ?? null,
            'address' => $data['customer_address'] ?? null,
            'status' => 'active',
        ];

        if ($existingCustomer) {
            $existingCustomer->fill($attributes)->save();
            return $existingCustomer;
        }

        // Không gộp khách chỉ vì trùng email hoặc số điện thoại: đây có thể là người đặt hộ.
        return Customer::create($attributes);
    }

    private function normalizeCustomerIdentityName(string $name): string
    {
        return (string) Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }

    private function generateBookingCode(): string
    {
        return app(BookingCodeGenerator::class)->generate();
    }

    private function calculateWalkInHourlyPrice(float $nightPrice, int $roomQuantity, int $durationMinutes): array
    {
        return app(StayPricingPolicyService::class)->shortStay(
            $nightPrice,
            $roomQuantity,
            $durationMinutes
        );
    }

    private function countAvailableRooms($roomCategoryId, $checkInAt, $checkOutAt)
    {
        return $this->availableRoomQuery($roomCategoryId, $checkInAt, $checkOutAt)->count();
    }

    private function getAvailableRooms($roomCategoryId, $quantity, $checkInAt, $checkOutAt)
    {
        return $this->availableRoomQuery($roomCategoryId, $checkInAt, $checkOutAt)
            ->with('category')
            ->take($quantity)
            ->get();
    }

    private function getAdjacentRooms($roomCategoryId, $quantity, $checkInAt, $checkOutAt)
    {
        $roomsByFloor = $this->availableRoomQuery($roomCategoryId, $checkInAt, $checkOutAt)
            ->with('category')
            ->reorder()
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get()
            ->groupBy('floor_number');

        $validGroups = collect();

        foreach ($roomsByFloor as $floorRooms) {
            $sortedRooms = $floorRooms
                ->sortBy(function ($room) {
                    return (int) preg_replace('/\D/', '', $room->room_number);
                })
                ->values();

            // Tạo đầy đủ mọi cửa sổ phòng liền kề. Ví dụ 201-202-203,
            // đặt 2 phòng sẽ có cả 201-202 và 202-203 để random công bằng.
            for ($startIndex = 0; $startIndex <= $sortedRooms->count() - $quantity; $startIndex++) {
                $candidate = $sortedRooms->slice($startIndex, $quantity)->values();
                $isConsecutive = true;

                for ($index = 1; $index < $candidate->count(); $index++) {
                    $previousNumber = (int) preg_replace('/\D/', '', $candidate[$index - 1]->room_number);
                    $currentNumber = (int) preg_replace('/\D/', '', $candidate[$index]->room_number);

                    if ($currentNumber !== $previousNumber + 1) {
                        $isConsecutive = false;
                        break;
                    }
                }

                if (!$isConsecutive) {
                    continue;
                }

                // Nhóm có toàn phòng sẵn sàng được ưu tiên trước. Chỉ khi không có
                // nhóm tốt hơn mới xét nhóm chứa phòng đang dọn/trạng thái khác.
                $priorityScore = $candidate->sum(function ($room) {
                    return match ($room->status) {
                        'available' => 0,
                        'reserved' => 1,
                        'cleaning' => 2,
                        'occupied' => 3,
                        default => 4,
                    };
                });

                $validGroups->push([
                    'rooms' => $candidate,
                    'priority_score' => $priorityScore,
                ]);
            }
        }

        if ($validGroups->isEmpty()) {
            return collect();
        }

        $bestScore = $validGroups->min('priority_score');
        $bestGroups = $validGroups
            ->where('priority_score', $bestScore)
            ->values();

        return $bestGroups->random()['rooms'];
    }

    /**
     * Chọn đủ số phòng cùng hạng theo phương án gần nhau tốt nhất có thể.
     * Ưu tiên chuỗi phòng liền kề dài nhất, sau đó mới ghép thêm nhóm/phòng lẻ.
     */
    private function getBestAvailableRoomArrangement($roomCategoryId, $quantity, $checkInAt, $checkOutAt)
    {
        $availableRooms = $this->availableRoomQuery($roomCategoryId, $checkInAt, $checkOutAt)
            ->with('category')
            ->reorder()
            ->orderByRaw("CASE rooms.status WHEN 'available' THEN 0 WHEN 'reserved' THEN 1 WHEN 'cleaning' THEN 2 WHEN 'occupied' THEN 3 ELSE 4 END")
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();

        if ($availableRooms->count() <= $quantity) {
            return $availableRooms->take($quantity)->values();
        }

        $runs = collect();

        foreach ($availableRooms->groupBy('floor_number') as $floor => $floorRooms) {
            $sortedRooms = $floorRooms
                ->sortBy(fn ($room) => (int) preg_replace('/\D/', '', $room->room_number))
                ->values();

            $currentRun = collect();

            foreach ($sortedRooms as $room) {
                if ($currentRun->isEmpty()) {
                    $currentRun->push($room);
                    continue;
                }

                $previousNumber = (int) preg_replace('/\D/', '', $currentRun->last()->room_number);
                $currentNumber = (int) preg_replace('/\D/', '', $room->room_number);

                if ($currentNumber === $previousNumber + 1) {
                    $currentRun->push($room);
                    continue;
                }

                $runs->push([
                    'floor' => $floor,
                    'rooms' => $currentRun->values(),
                    'priority_score' => $currentRun->sum(fn ($item) => match ($item->status) {
                        'available' => 0,
                        'reserved' => 1,
                        'cleaning' => 2,
                        'occupied' => 3,
                        default => 4,
                    }),
                ]);

                $currentRun = collect([$room]);
            }

            if ($currentRun->isNotEmpty()) {
                $runs->push([
                    'floor' => $floor,
                    'rooms' => $currentRun->values(),
                    'priority_score' => $currentRun->sum(fn ($item) => match ($item->status) {
                        'available' => 0,
                        'reserved' => 1,
                        'cleaning' => 2,
                        'occupied' => 3,
                        default => 4,
                    }),
                ]);
            }
        }

        $sortedRuns = $runs->sort(function (array $left, array $right) {
            $countComparison = $right['rooms']->count() <=> $left['rooms']->count();
            if ($countComparison !== 0) {
                return $countComparison;
            }

            $priorityComparison = $left['priority_score'] <=> $right['priority_score'];
            if ($priorityComparison !== 0) {
                return $priorityComparison;
            }

            return ((int) $left['floor']) <=> ((int) $right['floor']);
        })->values();

        $selectedRooms = collect();

        foreach ($sortedRuns as $run) {
            $remaining = $quantity - $selectedRooms->count();
            if ($remaining <= 0) {
                break;
            }

            $selectedRooms = $selectedRooms->concat(
                $run['rooms']->take($remaining)
            );
        }

        return $selectedRooms
            ->unique('id')
            ->take($quantity)
            ->values();
    }

    /**
     * Mô tả phương án để hiển thị rõ trong modal xác nhận.
     */
    private function describeRoomArrangement($rooms): array
    {
        $lines = [];

        foreach ($rooms->groupBy('floor_number') as $floor => $floorRooms) {
            $sortedRooms = $floorRooms
                ->sortBy(fn ($room) => (int) preg_replace('/\D/', '', $room->room_number))
                ->values();

            $currentRun = collect();
            $flushRun = function () use (&$currentRun, &$lines, $floor) {
                if ($currentRun->isEmpty()) {
                    return;
                }

                $count = $currentRun->count();
                $lines[] = [
                    'count' => $count,
                    'type' => $count > 1 ? 'adjacent' : 'single',
                    'label' => $count > 1
                        ? $count . ' phòng cạnh nhau'
                        : '1 phòng lẻ',
                    'floor' => $floor,
                    'rooms' => $currentRun->pluck('room_number')->values()->all(),
                ];
                $currentRun = collect();
            };

            foreach ($sortedRooms as $room) {
                if ($currentRun->isEmpty()) {
                    $currentRun->push($room);
                    continue;
                }

                $previousNumber = (int) preg_replace('/\D/', '', $currentRun->last()->room_number);
                $currentNumber = (int) preg_replace('/\D/', '', $room->room_number);

                if ($currentNumber === $previousNumber + 1) {
                    $currentRun->push($room);
                } else {
                    $flushRun();
                    $currentRun->push($room);
                }
            }

            $flushRun();
        }

        return $lines;
    }

    private function availableRoomQuery($roomCategoryId, $checkInAt, $checkOutAt)
    {
        return Room::where('room_category_id', $roomCategoryId)
            ->bookableForPeriod($checkInAt, $checkOutAt);
    }

    private function generateRoomSuggestions(
        $requestedCategoryId,
        $quantity,
        $checkInAt,
        $checkOutAt,
        $preferAdjacentRooms,
        float $policyExtraPercent = 0,
        ?string $policyFeeNote = null,
        float $serviceItemTotal = 0
    ) {
        $checkInAt = Carbon::parse($checkInAt, 'Asia/Ho_Chi_Minh');
        $checkOutAt = Carbon::parse($checkOutAt, 'Asia/Ho_Chi_Minh');

        $categories = RoomCategory::where('status', 'active')
            ->orderByRaw('id = ? DESC', [$requestedCategoryId])
            ->orderBy('price')
            ->get();

        $allAvailableRooms = collect();

        foreach ($categories as $category) {
            $rooms = $this->availableRoomQuery($category->id, $checkInAt, $checkOutAt)
                ->with('category')
                ->orderBy('floor_number')
                ->orderBy('room_number')
                ->get();

            $allAvailableRooms = $allAvailableRooms->merge($rooms);
        }

        if ($allAvailableRooms->count() < $quantity) {
            return collect();
        }

        $nightCount = max(
            1,
            $checkInAt->copy()->startOfDay()->diffInDays($checkOutAt->copy()->startOfDay())
        );

        $suggestions = collect();

        $makeSuggestion = function ($rooms, $label) use ($suggestions, $quantity, $nightCount, $policyExtraPercent, $policyFeeNote, $serviceItemTotal) {
            $rooms = $rooms->take($quantity)->values();

            if ($rooms->count() < $quantity) {
                return;
            }

            $key = $rooms->pluck('id')->sort()->implode('-');

            if ($suggestions->has($key)) {
                return;
            }

            $roomTotal = $rooms->sum(function ($room) use ($nightCount) {
                return (float) ($room->category->price ?? 0) * $nightCount;
            });

            $policyFeeAmount = round($rooms->sum(function ($room) {
                return (float) ($room->category->price ?? 0);
            }) * $policyExtraPercent, 0);

            $estimatedTotal = $roomTotal + $policyFeeAmount + $serviceItemTotal;

            $suggestions->put($key, [
                'label' => $label,
                'rooms' => $rooms,
                'night_count' => $nightCount,
                'estimated_total' => $estimatedTotal,
                'policy_fee_amount' => $policyFeeAmount,
                'policy_fee_note' => $policyFeeNote,
                'service_total' => $serviceItemTotal,
                'summary' => $rooms
                    ->groupBy('room_category_id')
                    ->map(function ($groupRooms) {
                        $category = $groupRooms->first()->category;

                        return [
                            'category_name' => $category->name ?? 'Không rõ hạng phòng',
                            'price' => $category->price ?? 0,
                            'quantity' => $groupRooms->count(),
                            'rooms' => $groupRooms->pluck('room_number')->values(),
                            'floors' => $groupRooms->pluck('floor_number')->unique()->sort()->values(),
                        ];
                    })
                    ->values(),
            ]);
        };

        $requestedFirst = $allAvailableRooms
            ->sortBy([
                fn($a, $b) => $a->room_category_id == $requestedCategoryId ? -1 : 1,
                fn($a, $b) => ($a->category->price ?? 0) <=> ($b->category->price ?? 0),
            ])
            ->values();

        $makeSuggestion($requestedFirst, 'Ưu tiên hạng phòng khách chọn');

        $cheapest = $allAvailableRooms
            ->sortBy(function ($room) {
                return $room->category->price ?? 0;
            })
            ->values();

        $makeSuggestion($cheapest, 'Giá thấp nhất');

        $floors = $allAvailableRooms
            ->pluck('floor_number')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        foreach ($floors as $floor) {
            $nearFloorRooms = $allAvailableRooms
                ->sortBy(function ($room) use ($floor, $requestedCategoryId) {
                    $floorDistance = abs((int) $room->floor_number - (int) $floor);
                    $categoryPenalty = $room->room_category_id == $requestedCategoryId ? 0 : 1;

                    return ($floorDistance * 1000)
                        + ($categoryPenalty * 100)
                        + ($room->category->price ?? 0);
                })
                ->values();

            $makeSuggestion($nearFloorRooms, 'Ưu tiên gần tầng ' . $floor);

            if ($suggestions->count() >= 5) {
                break;
            }
        }

        return $suggestions
            ->values()
            ->sortBy('estimated_total')
            ->values()
            ->take(5);
    }

    private function getBestAdjacentRooms($roomCategoryId, $quantity, $checkInAt, $checkOutAt)
    {
        if ($quantity <= 0) {
            return collect();
        }

        $rooms = $this->availableRoomQuery($roomCategoryId, $checkInAt, $checkOutAt)
            ->with('category')
            ->reorder()
            ->orderByRaw("CASE rooms.status WHEN 'available' THEN 0 WHEN 'reserved' THEN 1 WHEN 'cleaning' THEN 2 WHEN 'occupied' THEN 3 ELSE 4 END")
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get()
            ->groupBy('floor_number');

        $bestGroup = collect();

        foreach ($rooms as $floorRooms) {
            $sortedRooms = $floorRooms
                ->sortBy(function ($room) {
                    return (int) preg_replace('/\D/', '', $room->room_number);
                })
                ->values();

            $sequence = collect();

            foreach ($sortedRooms as $room) {
                if ($sequence->isEmpty()) {
                    $sequence->push($room);
                } else {
                    $previousRoom = $sequence->last();

                    $previousNumber = (int) preg_replace('/\D/', '', $previousRoom->room_number);
                    $currentNumber = (int) preg_replace('/\D/', '', $room->room_number);

                    if ($currentNumber == $previousNumber + 1) {
                        $sequence->push($room);
                    } else {
                        $sequence = collect([$room]);
                    }
                }

                if ($sequence->count() > $bestGroup->count()) {
                    $bestGroup = $sequence->values();
                }

                if ($bestGroup->count() >= $quantity) {
                    return $bestGroup->take($quantity)->values();
                }
            }
        }

        return $bestGroup->take($quantity)->values();
    }

    public function storeSuggestion(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer,vnpay',
            'payment_type' => 'required|in:deposit_30',
            'confirm_counter_payment' => 'nullable',
        ], [
            'payment_method.required' => 'Booking bắt buộc phải chọn phương thức thanh toán.',
            'payment_type.required' => 'Booking bắt buộc chọn cọc 30%.',
        ]);

        $selectedRoomIds = $request->selected_room_ids ?? [];

        if (empty($selectedRoomIds)) {
            return redirect()
                ->route('admin.bookings.create')
                ->with('error', 'Không tìm thấy phòng được chọn.');
        }

        $roomCategory = RoomCategory::findOrFail($request->room_category_id);

        $rooms = Room::whereIn('id', $selectedRoomIds)
            ->with('category')
            ->get();

        if ($rooms->count() != count($selectedRoomIds)) {
            return redirect()
                ->route('admin.bookings.create')
                ->with('error', 'Một số phòng không tồn tại.');
        }

        $roomQuantity = count($selectedRoomIds);

        $data = [
            'booking_mode' => $request->booking_mode ?? 'advance',
            'booking_type' => $request->booking_type ?? 'overnight',
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'check_in_time' => $request->check_in_time,
            'hourly_duration' => $request->hourly_duration,
        ];

        try {
            $period = $this->resolveBookingPeriod($data, $roomCategory, $roomQuantity);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.bookings.create')
                ->with('error', $e->getMessage());
        }

        $bookingMode = $period['booking_mode'];
        $bookingType = $period['booking_type'];
        $checkInAt = $period['check_in_at'];
        $checkOutAt = $period['check_out_at'];
        $nightCount = $period['night_count'];
        $policyExtraPercent = $period['policy_extra_percent'];
        $policyFeeNote = $period['policy_fee_note'];

        $availableRoomIds = Room::whereIn('id', $selectedRoomIds)
            ->bookableForPeriod($checkInAt, $checkOutAt)
            ->pluck('id')
            ->toArray();

        $invalidRoomIds = collect($selectedRoomIds)
            ->map(fn($id) => (int) $id)
            ->diff(collect($availableRoomIds)->map(fn($id) => (int) $id));

        if ($invalidRoomIds->isNotEmpty()) {
            return redirect()
                ->route('admin.bookings.create')
                ->with('error', 'Một số phòng trong phương án đã bị đặt hoặc đang bảo trì. Vui lòng chọn lại phương án khác.');
        }

        $roomTotal = $rooms->sum(function ($room) use ($nightCount, $roomCategory) {
            return (float) ($room->category->price ?? $roomCategory->price) * max(1, $nightCount);
        });

        $policyFeeAmount = round($rooms->sum(function ($room) use ($roomCategory) {
            return (float) ($room->category->price ?? $roomCategory->price);
        }) * $policyExtraPercent, 0);

        $serviceItems = $this->prepareServiceItems($request->services ?? []);
        $serviceItemTotal = collect($serviceItems)->sum('total');

        $subtotalAmount = $roomTotal + $policyFeeAmount + $serviceItemTotal;
        $paymentMethod = $request->payment_method;
        $paymentType = $request->payment_type;
        $customPaymentAmount = (float) ($request->deposit_amount ?? 0);

        if (in_array($paymentMethod, ['cash', 'bank_transfer'], true) && !$request->boolean('confirm_counter_payment')) {
            return redirect()->route('admin.bookings.create')->withInput()->with('error', 'Vui lòng tích xác nhận đã nhận đủ tiền cọc tại quầy trước khi tạo booking.');
        }

        if ($paymentMethod === 'vnpay' && !filter_var((string) $request->customer_email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('admin.bookings.create')->withInput()->with('error', 'Thanh toán VNPay bắt buộc phải có email khách hợp lệ để gửi đường dẫn thanh toán.');
        }

        try {
            $this->validateInitialPaymentChoice($paymentMethod, $paymentType, $customPaymentAmount, $subtotalAmount);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.bookings.create')
                ->with('error', $e->getMessage());
        }

        $pendingVnpayPayment = null;
        $booking = null;

        DB::beginTransaction();

        try {
            $rooms = Room::whereIn('id', $selectedRoomIds)
                ->lockForUpdate()
                ->bookableForPeriod($checkInAt, $checkOutAt)
                ->with('category')
                ->get();

            if ($rooms->count() !== count($selectedRoomIds)) {
                throw new \Exception('Một hoặc nhiều phòng trong phương án vừa được booking khác giữ. Vui lòng chọn lại.');
            }

            $this->assertRoomsStillFree($selectedRoomIds, $checkInAt, $checkOutAt);

            $customer = $this->createOrUpdateCustomer([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_cccd' => $request->customer_cccd,
                'customer_email' => $request->customer_email,
                'customer_address' => $request->customer_address,
            ]);
            $this->assertCustomerHasNoOverlappingBooking($customer, $checkInAt, $checkOutAt);

            $promotionResult = app(PromotionService::class)->validateCodes(
                $request->promotion_codes ?? [],
                [
                    'customer_id' => $customer->id,
                    'customer_email' => $data['customer_email'] ?? $customer->email,
                    'customer_phone' => $data['customer_phone'] ?? $customer->phone,
                    'customer_cccd' => $data['customer_cccd'] ?? $customer->cccd,
                    'subtotal_amount' => $subtotalAmount,
                    'service_items' => $serviceItems,
                    'check_in_at' => $checkInAt,
                    'check_out_at' => $checkOutAt,
                    'night_count' => $nightCount,
                    'room_quantity' => $roomQuantity,
                ],
                'admin',
                $request->promotion_note
            );

            if (!$promotionResult['ok']) {
                throw new \Exception(implode(' ', $promotionResult['messages']));
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
            $initialPaymentAmount = $this->resolveInitialPaymentAmount(
                $paymentMethod,
                $paymentType,
                $customPaymentAmount,
                $estimatedTotal,
                0
            );
            $depositAmount = in_array($paymentMethod, ['cash', 'bank_transfer'], true)
                ? $initialPaymentAmount
                : 0;

            if ($depositAmount > $estimatedTotal) {
                throw new \Exception('Số tiền thu không được lớn hơn tổng tiền sau giảm giá.');
            }

            app(BookingIdentityGuard::class)->assertEligible($customer);

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'customer_id' => $customer->id,
                ...Booking::customerSnapshotAttributes($customer),
                'created_by' => Auth::id(),
                'room_category_id' => $roomCategory->id,
                'booking_type' => $bookingType,
                'booking_mode' => $bookingMode,
                'booking_source' => 'reception',
                'cleaning_buffer_minutes' => self::DEFAULT_CLEANING_BUFFER_MINUTES,
                'check_in_date' => $checkInAt->toDateString(),
                'check_out_date' => $checkOutAt->toDateString(),
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'adult_count' => $request->adult_count,
                'child_count' => $request->child_count ?? 0,
                'room_quantity' => $roomQuantity,
                'prefer_adjacent_rooms' => $request->boolean('prefer_adjacent_rooms'),
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $discountAmount,
                'estimated_total' => $estimatedTotal,
                'deposit_amount' => $depositAmount,
                'payment_status' => $depositAmount >= $estimatedTotal && $estimatedTotal > 0 ? 'paid' : ($depositAmount > 0 ? 'partial' : 'unpaid'),
                'status' => $paymentMethod === 'vnpay' ? 'pending' : 'confirmed',
                'note' => $request->note,
            ]);

            $this->autoAssignCreatedBooking($booking);

            foreach ($rooms as $room) {
                BookingRoom::create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'adult_count' => 0,
                    'child_count' => 0,
                    'price_at_booking' => $room->category->price ?? $roomCategory->price,
                    'surcharge' => 0,
                    'surcharge_reason' => null,
                    'created_at' => now(),
                ]);

                app(\App\Services\RoomPreparationService::class)
                    ->flagPriorityIfNeeded($booking, $room, 'lễ tân tạo booking');

                if (!in_array($room->status, ['cleaning', 'inspection', 'maintenance'], true)) {
                    $room->update([
                        'status' => 'reserved',
                        'status_from' => now('Asia/Ho_Chi_Minh'),
                    ]);
                }
            }

            foreach ($serviceItems as $item) {
                $this->createBookingServiceItem($booking, $item);
            }

            if ($policyFeeAmount > 0) {
                $this->createPolicyFeeItem(
                    $booking,
                    str_contains((string) $policyFeeNote, 'Trả phòng') || str_contains((string) $policyFeeNote, 'check-out')
                        ? 'Phụ thu nhận/trả phòng'
                        : 'Phụ thu nhận phòng sớm',
                    $policyFeeAmount,
                    $policyFeeNote
                );
            }

            app(PromotionService::class)->storeUsages(
                $booking,
                $promotionResult['promotions'],
                'admin',
                $request->promotion_note,
                Auth::id()
            );

            if ($promotionResult['promotions']->count() > 0) {
                $this->addBookingLog(
                    $booking,
                    'promotion_added',
                    'Áp dụng mã ưu đãi khi tạo booking từ gợi ý phòng: '
                    . $promotionResult['promotions']->pluck('code')->implode(', ')
                    . '. Giảm tiền: '
                    . number_format($moneyDiscountAmount, 0, ',', '.')
                    . 'đ, ưu đãi dịch vụ: '
                    . number_format($serviceDiscountAmount, 0, ',', '.')
                    . 'đ, tổng ưu đãi: '
                    . number_format($discountAmount, 0, ',', '.')
                    . 'đ.'
                    . (!empty($request->promotion_note) ? ' Lý do: ' . $request->promotion_note : '')
                );
            }

            $roomNumbers = $rooms
                ->sortBy('room_number')
                ->pluck('room_number')
                ->implode(', ');

            $bookingModeText = $bookingMode === 'advance' ? 'đặt trước' : 'ở ngay';
            $bookingTypeText = $bookingType === 'hourly' ? 'theo giờ' : 'qua đêm';

            $this->addBookingLog(
                $booking,
                'booking_created',
                'Tạo booking ' . $bookingModeText . ' - ' . $bookingTypeText
                . ' từ phương án gợi ý bởi lễ tân. Gán phòng: ' . $roomNumbers
                . '. Thời gian: ' . $checkInAt->format('d/m/Y H:i')
                . ' - ' . $checkOutAt->format('d/m/Y H:i')
                . ($policyFeeNote ? '. Chính sách giá: ' . $policyFeeNote : '')
                . ($discountAmount > 0 ? '. Ưu đãi giảm: ' . number_format($discountAmount, 0, ',', '.') . 'đ' : '')
                . '. Tổng tiền tạm tính: ' . number_format($estimatedTotal, 0, ',', '.') . 'đ.'
            );

            if (in_array($paymentMethod, ['cash', 'bank_transfer'], true) && $initialPaymentAmount > 0) {
                $this->recordInitialDirectPayment($booking, $paymentMethod, $paymentType, $initialPaymentAmount, $estimatedTotal);
            }

            if ($paymentMethod === 'vnpay' && $initialPaymentAmount > 0) {
                $pendingVnpayPayment = $this->createInitialVnpayPayment($booking, $paymentType, $initialPaymentAmount);
            }

            DB::commit();

            Realtime::booking($booking, 'staff_created_from_suggestion');

            if ($pendingVnpayPayment) {
                $emailResult = $this->sendInitialVnpayPaymentRequest(
                    $booking,
                    $pendingVnpayPayment,
                    (string) $customer->email
                );

                return redirect()
                    ->route('admin.bookings.show', $booking)
                    ->with($emailResult['status'] === 'sent' ? 'success' : 'error', $emailResult['message'])
                    ->with('admin_vnpay_payment_url', $emailResult['payment_url']);
            }

            $mailResult = $this->sendBookingCreatedEmail($booking, 'admin_created');

            return redirect()
                ->route('admin.bookings.show', $booking)
                ->with('success', 'Đã tạo booking từ phương án gợi ý. ' . $mailResult['message']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('admin.bookings.create')
                ->with('error', $e->getMessage());
        }
    }

    private function createBookingServiceItem(Booking $booking, array $item): void
    {
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

    private function createPolicyFeeItem(
        Booking $booking,
        string $name,
        float $amount,
        ?string $note = null
    ): void {
        $policyFeeService = Service::firstOrCreate(
            [
                'name' => $name,
                'type' => 'policy_violation_fee',
            ],
            [
                'service_group' => 'other',
                'price' => 0,
                'unit' => 'lần',
                'description' => 'Phụ thu theo chính sách giờ nhận/trả phòng của khách sạn.',
                'status' => 'active',
            ]
        );

        BookingServiceItem::create([
            'booking_id' => $booking->id,
            'service_id' => $policyFeeService->id,
            'name' => $name,
            'type' => 'policy_violation_fee',
            'unit_price' => $amount,
            'quantity' => 1,
            'used_quantity' => 1,
            'billing_status' => 'confirmed',
            'total' => $amount,
            'note' => $note,
        ]);
    }

    private function autoAssignCreatedBooking(Booking $booking): void
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'receptionist') {
            return;
        }

        BookingStaffAssignment::updateOrCreate(
            [
                'booking_id' => $booking->id,
                'staff_id' => $user->id,
                'role_in_booking' => 'owner',
            ],
            [
                'assigned_by' => $user->id,
                'status' => 'active',
                'note' => 'Tự động gán cho lễ tân tạo booking.',
            ]
        );
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

    private function prepareServiceItems(array $items): array
    {
        $preparedItems = [];

        foreach ($items as $item) {
            if (empty($item['service_id'])) {
                continue;
            }

            $service = Service::where('id', $item['service_id'])
                ->where('status', 'active')
                ->where('price', '>', 0)
                ->whereIn('type', ['service'])
                ->first();

            if (!$service) {
                continue;
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) $service->price;
            $total = $unitPrice * $quantity;

            $preparedItems[] = [
                'service_id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'used_quantity' => $quantity,
                'billing_status' => 'confirmed',
                'total' => $total,
                'note' => $item['note'] ?? null,
            ];
        }

        return $preparedItems;
    }

    public function checkRoomCategoryAvailability(Request $request)
    {
        $data = $request->validate([
            'booking_mode' => 'required|in:advance,walk_in',
            'booking_type' => 'required|in:overnight,hourly',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
        ]);

        $bookingMode = $data['booking_mode'];
        $bookingType = $bookingMode === 'advance' ? 'overnight' : $data['booking_type'];

        if ($bookingMode === 'advance') {
            $checkInAt = Carbon::parse($data['check_in_date'] . ' ' . self::OVERNIGHT_CHECK_IN_TIME, 'Asia/Ho_Chi_Minh');
            $checkOutAt = Carbon::parse($data['check_out_date'] . ' ' . self::OVERNIGHT_CHECK_OUT_TIME, 'Asia/Ho_Chi_Minh');
        } elseif ($bookingType === 'hourly') {
            if (empty($data['check_in_time']) || empty($data['check_out_time'])) {
                return response()->json(['categories' => []]);
            }

            $checkInAt = Carbon::parse($data['check_in_date'] . ' ' . $data['check_in_time'] . ':00', 'Asia/Ho_Chi_Minh');
            $checkOutAt = Carbon::parse($data['check_out_date'] . ' ' . $data['check_out_time'] . ':00', 'Asia/Ho_Chi_Minh');
        } else {
            if (empty($data['check_in_time'])) {
                return response()->json(['categories' => []]);
            }

            $checkInAt = Carbon::parse($data['check_in_date'] . ' ' . $data['check_in_time'] . ':00', 'Asia/Ho_Chi_Minh');
            $checkOutAt = Carbon::parse($data['check_out_date'] . ' ' . self::OVERNIGHT_CHECK_OUT_TIME, 'Asia/Ho_Chi_Minh');
        }

        if ($checkOutAt->lessThanOrEqualTo($checkInAt)) {
            return response()->json(['categories' => []]);
        }

        $categories = RoomCategory::where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (RoomCategory $category) use ($checkInAt, $checkOutAt) {
                $availableCount = $this->countAvailableRooms($category->id, $checkInAt, $checkOutAt);

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'price' => (float) $category->price,
                    'available_count' => $availableCount,
                ];
            })
            ->filter(fn (array $category) => $category['available_count'] > 0)
            ->values();

        return response()->json([
            'categories' => $categories,
            'check_in_at' => $checkInAt->format('d/m/Y H:i'),
            'check_out_at' => $checkOutAt->format('d/m/Y H:i'),
        ]);
    }

    public function checkHourlyInventory(Request $request)
    {
        $data = $request->validate([
            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_date' => 'required|date',
            'check_out_time' => 'required|date_format:H:i',
            'room_quantity' => 'required|integer|min:1',
        ]);

        $roomCategory = RoomCategory::findOrFail($data['room_category_id']);

        $checkInAt = Carbon::parse(
            $data['check_in_date'] . ' ' . $data['check_in_time'] . ':00',
            'Asia/Ho_Chi_Minh'
        );

        $checkOutAt = Carbon::parse(
            $data['check_out_date'] . ' ' . $data['check_out_time'] . ':00',
            'Asia/Ho_Chi_Minh'
        );

        if ($checkOutAt->equalTo($checkInAt)) {
            return response()->json([
                'blocked' => true,
                'message' => 'Giờ ra phải khác giờ vào.',
            ]);
        }

        if ($checkOutAt->lessThanOrEqualTo($checkInAt)) {
            return response()->json([
                'blocked' => true,
                'message' => 'Ngày giờ ra phải sau ngày giờ vào.',
            ]);
        }

        $durationMinutes = $checkInAt->diffInMinutes($checkOutAt);

        if ($durationMinutes < 30) {
            return response()->json([
                'blocked' => true,
                'message' => 'Thời gian ở theo giờ phải tối thiểu 30 phút.',
            ]);
        }

        if ($durationMinutes > 24 * 60) {
            return response()->json([
                'blocked' => true,
                'message' => 'Booking theo giờ không được vượt quá 24 giờ. Nếu khách ở lâu hơn, hãy chọn qua đêm.',
            ]);
        }

        $occupiedUntil = $checkOutAt->copy()->addMinutes(self::DEFAULT_CLEANING_BUFFER_MINUTES);
        $requestedQuantity = (int) $data['room_quantity'];

        $availableForSelectedPeriod = $this->countAvailableRooms(
            $data['room_category_id'],
            $checkInAt,
            $checkOutAt
        );

        $blocked = $availableForSelectedPeriod < $requestedQuantity;

        $hourlyPolicy = $this->calculateWalkInHourlyPrice(
            (float) $roomCategory->price,
            $requestedQuantity,
            $durationMinutes
        );

        $overnightStart = $checkInAt->copy()->setTimeFromTimeString(self::OVERNIGHT_CHECK_IN_TIME);
        $overnightEnd = $overnightStart->copy()->addDay()->setTimeFromTimeString(self::OVERNIGHT_CHECK_OUT_TIME);
        $affectsOvernight = $occupiedUntil->greaterThan($overnightStart);

        $overnightAvailable = $this->countAvailableRooms(
            $data['room_category_id'],
            $overnightStart,
            $overnightEnd
        );

        $remainingAfterHourly = max(0, $overnightAvailable - $requestedQuantity);

        $lowSelectedStock = !$blocked && $availableForSelectedPeriod <= 2;
        $lowOvernightStock = !$blocked && $affectsOvernight && $remainingAfterHourly < 2;

        if ($blocked) {
            $message = 'Không còn đủ phòng trống cho hạng '
                . $roomCategory->name
                . ' trong khung giờ '
                . $checkInAt->format('d/m/Y H:i')
                . ' → '
                . $checkOutAt->format('d/m/Y H:i')
                . '. Yêu cầu '
                . $requestedQuantity
                . ' phòng, hiện chỉ còn '
                . $availableForSelectedPeriod
                . ' phòng phù hợp.';
        } elseif ($lowSelectedStock) {
            $message = 'Cảnh báo: Hạng '
                . $roomCategory->name
                . ' chỉ còn '
                . $availableForSelectedPeriod
                . ' phòng trống trong khung giờ đã chọn. Nếu vẫn nhận khách ở ngay theo giờ, khách sạn tự chịu rủi ro mất cơ hội bán phòng cho khách khác.';
        } elseif ($affectsOvernight) {
            $message = $lowOvernightStock
                ? 'Cảnh báo: Ca ở ngay theo giờ này cộng dọn phòng sẽ chiếm phòng đến '
                . $occupiedUntil->format('d/m/Y H:i')
                . '. Sau khi giữ phòng, hạng '
                . $roomCategory->name
                . ' chỉ còn '
                . $remainingAfterHourly
                . ' phòng có thể bán qua đêm.'
                : 'Thông tin: Ca ở ngay theo giờ này có đi qua mốc check-in qua đêm 14:00, nhưng hạng '
                . $roomCategory->name
                . ' vẫn còn '
                . $remainingAfterHourly
                . ' phòng dự phòng qua đêm nên chưa cần cảnh báo.';
        } else {
            $message = 'Khung giờ này không ảnh hưởng mốc check-in qua đêm 14:00.';
        }

        return response()->json([
            'room_category_name' => $roomCategory->name,
            'check_in_at' => $checkInAt->format('d/m/Y H:i'),
            'check_out_at' => $checkOutAt->format('d/m/Y H:i'),
            'occupied_until' => $occupiedUntil->format('d/m/Y H:i'),

            'duration_minutes' => $durationMinutes,
            'duration_hours' => $hourlyPolicy['duration_hours'],
            'charged_percent' => $hourlyPolicy['charged_percent'],
            'room_fee' => $hourlyPolicy['amount'],
            'policy_text' => $hourlyPolicy['policy_text'],

            'requested_quantity' => $requestedQuantity,
            'available_for_selected_period' => $availableForSelectedPeriod,
            'blocked' => $blocked,

            'affects_overnight' => $affectsOvernight,
            'overnight_available' => $overnightAvailable,
            'remaining_after_hourly' => $remainingAfterHourly,
            'low_selected_stock' => $lowSelectedStock,
            'low_overnight_stock' => $lowOvernightStock,
            'requires_low_stock_confirmation' => $lowSelectedStock,

            'message' => $message,
        ]);
    }
}
