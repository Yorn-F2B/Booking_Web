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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\PromotionService;
use App\Models\Promotion;
use App\Support\Realtime;
use App\Mail\BookingCreatedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'required|string|max:20',
            'customer_cccd' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:150',
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

            'adult_count' => 'required|integer|min:1',
            'child_count' => 'nullable|integer|min:0',
            'room_quantity' => 'required|integer|min:1',
            'prefer_adjacent_rooms' => 'nullable|boolean',

            'deposit_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:none,cash,bank_transfer,vnpay',
            'payment_type' => 'nullable|in:deposit_30,full_100,custom',
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

        $availableRooms = $preferAdjacentRooms
            ? $this->getAdjacentRooms(
                $data['room_category_id'],
                $roomQuantity,
                $checkInAt,
                $checkOutAt
            )
            : $this->getAvailableRooms(
                $data['room_category_id'],
                $roomQuantity,
                $checkInAt,
                $checkOutAt
            );

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
            // Lock the selected rooms for update
            $roomIds = $availableRooms->pluck('id')->toArray();
            $lockedRooms = Room::whereIn('id', $roomIds)->lockForUpdate()->get();

            // Re-verify availability inside transaction
            $recheckAvailableCount = Room::whereIn('id', $roomIds)
                ->availableForPeriod($checkInAt, $checkOutAt)
                ->count();

            if ($recheckAvailableCount < $roomQuantity) {
                throw new \Exception('Một số phòng vừa được chọn đã bị đặt hoặc đang bảo trì. Vui lòng chọn lại phương án khác.');
            }

            $customer = $this->createOrUpdateCustomer($data);

            $promotionResult = app(PromotionService::class)->validateCodes(
                $data['promotion_codes'] ?? [],
                [
                    'customer_id' => $customer->id,
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

            if ($depositAmount > $estimatedTotal) {
                throw new \Exception('Số tiền thu không được lớn hơn tổng tiền sau giảm giá.');
            }

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'customer_id' => $customer->id,
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
                'status' => $bookingMode === 'walk_in' ? 'checked_in' : 'confirmed',
                'actual_check_in' => $bookingMode === 'walk_in' ? now('Asia/Ho_Chi_Minh') : null,
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

                $room->update([
                    'status' => $bookingMode === 'walk_in' ? 'occupied' : 'reserved',
                ]);
            }

            foreach ($serviceItems as $item) {
                $this->createBookingServiceItem($booking, $item);
            }

            if ($policyFeeAmount > 0) {
                $this->createPolicyFeeItem(
                    $booking,
                    'Phụ thu nhận phòng sớm',
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
                session()->flash('success', 'Tạo booking và gán phòng thành công. Chưa gửi email xác nhận booking vì khách chưa thanh toán VNPay. Email xác nhận sẽ gửi sau khi VNPay báo thanh toán thành công.');

                return redirect()->away(
                    app(\App\Services\VnpayService::class)->createPaymentUrl($booking, $pendingVnpayPayment, $request)
                );
            }

            $mailResult = $this->sendBookingCreatedEmail($booking, 'admin_created');

            return redirect()
                ->route('admin.bookings.show', $booking->id)
                ->with('success', 'Tạo booking và gán phòng thành công. ' . $mailResult['message']);
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
        if ($paymentMethod === 'none') {
            return;
        }

        if (!$paymentType) {
            throw new \Exception('Vui lòng chọn kiểu thanh toán: cọc 30%, thu đủ hoặc nhập số tiền.');
        }

        if ($paymentMethod === 'vnpay' && $paymentType === 'custom') {
            throw new \Exception('Thanh toán VNPay chỉ hỗ trợ cọc 30% hoặc thanh toán đủ. Nếu muốn nhập số tiền khác, hãy chọn thanh toán trực tiếp tại quầy.');
        }

        if ($paymentType === 'custom' && $customPaymentAmount <= 0) {
            throw new \Exception('Vui lòng nhập số tiền thu thực tế.');
        }

        if ($paymentType === 'custom' && $customPaymentAmount > $temporaryTotal) {
            throw new \Exception('Số tiền thu không được lớn hơn tổng tiền tạm tính trước giảm giá.');
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

        if ($paymentType === 'full_100') {
            return $remaining;
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
        $storedPaymentType = $amount >= $estimatedTotal ? 'full_100' : 'deposit_30';
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
            'payment_type' => $paymentType === 'full_100' ? 'full_100' : 'deposit_30',
        ]);

        $this->addBookingLog(
            $booking,
            'admin_vnpay_created',
            'Tạo giao dịch VNPay khi tạo booking: '
            . number_format($amount, 0, ',', '.')
            . 'đ ('
            . ($payment->payment_type === 'full_100' ? 'thu đủ' : 'cọc 30%')
            . '). Mã giao dịch: '
            . $payment->txn_ref
            . '.'
        );

        return $payment;
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

            if (empty($data['check_out_time'])) {
                throw new \Exception('Vui lòng chọn giờ ra dự kiến cho booking ở ngay theo giờ.');
            }

            $checkInAt = Carbon::parse(
                $data['check_in_date'] . ' ' . $data['check_in_time'] . ':00',
                'Asia/Ho_Chi_Minh'
            );

            $checkOutAt = Carbon::parse(
                $data['check_in_date'] . ' ' . $data['check_out_time'] . ':00',
                'Asia/Ho_Chi_Minh'
            );

            if ($checkOutAt->equalTo($checkInAt)) {
                throw new \Exception('Giờ ra phải khác giờ vào.');
            }

            if ($checkOutAt->lessThan($checkInAt)) {
                $checkOutAt->addDay();
            }

            $nowVn = now('Asia/Ho_Chi_Minh');

            if ($checkInAt->lt($nowVn->copy()->subMinutes(5))) {
                throw new \Exception('Giờ vào của booking ở ngay không được nhỏ hơn thời điểm hiện tại.');
            }

            $durationMinutes = $checkInAt->diffInMinutes($checkOutAt);

            if ($durationMinutes < 30) {
                throw new \Exception('Thời gian ở theo giờ phải tối thiểu 30 phút.');
            }

            if ($durationMinutes > 24 * 60) {
                throw new \Exception('Booking theo giờ không được vượt quá 24 giờ. Nếu khách ở lâu hơn, hãy chọn loại qua đêm.');
            }

            $hourlyPolicy = $this->calculateWalkInHourlyPrice(
                (float) $roomCategory->price,
                $roomQuantity,
                $durationMinutes
            );

            $policyFeeNote = $hourlyPolicy['policy_text'];
        } elseif ($bookingMode === 'walk_in' && $bookingType === 'overnight') {
            $checkInTime = $data['check_in_time'] ?? now('Asia/Ho_Chi_Minh')->format('H:i');

            $checkInAt = Carbon::parse(
                $data['check_in_date'] . ' ' . $checkInTime . ':00',
                'Asia/Ho_Chi_Minh'
            );

            // If user provides check_out_date, use it (multi-night walk-in)
            if (!empty($data['check_out_date'])) {
                if (strtotime($data['check_out_date']) <= strtotime($data['check_in_date'])) {
                    throw new \Exception('Ngày trả phòng phải sau ngày nhận phòng.');
                }

                // Handle late checkout time if provided
                $checkoutTime = !empty($data['check_out_time']) ? $data['check_out_time'] : self::STANDARD_CHECK_OUT_TIME;
                $checkOutAt = Carbon::parse(
                    $data['check_out_date'] . ' ' . $checkoutTime,
                    'Asia/Ho_Chi_Minh'
                );

                $nightCount = max(
                    1,
                    $checkInAt->copy()->startOfDay()->diffInDays($checkOutAt->copy()->startOfDay())
                );

                // Calculate early check-in fee for first night only
                $policy = $this->getWalkInOvernightPolicy($checkInAt, $roomCategory, $roomQuantity);
                $policyFeeAmount = $policy['extra_fee_amount'];
                $policyFeeNote = $policy['policy_text'];
                $policyExtraPercent = $policy['extra_percent'];

                // Calculate late checkout fee if applicable
                if (!empty($data['check_out_time']) && $data['check_out_time'] !== self::STANDARD_CHECK_OUT_TIME) {
                    $lateCheckoutPolicy = $this->calculateLateCheckoutFee(
                        $data['check_out_time'],
                        $roomCategory->price,
                        $roomQuantity
                    );

                    if ($lateCheckoutPolicy['extra_fee_amount'] > 0) {
                        $policyFeeAmount += $lateCheckoutPolicy['extra_fee_amount'];
                        $policyFeeNote .= ' ' . $lateCheckoutPolicy['policy_text'];

                        // If checkout is 18:00 or later, count as additional night
                        if ($lateCheckoutPolicy['add_night']) {
                            $nightCount += 1;
                        }
                    }
                }
            } else {
                // Original logic: auto-calculate checkout based on check-in time
                $policy = $this->getWalkInOvernightPolicy($checkInAt, $roomCategory, $roomQuantity);

                $checkOutAt = $policy['check_out_at'];
                $nightCount = $policy['night_count'];
                $policyFeeAmount = $policy['extra_fee_amount'];
                $policyFeeNote = $policy['policy_text'];
                $policyExtraPercent = $policy['extra_percent'];
            }
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
        $hour = (int) $checkInAt->format('H');
        $baseRoomTotal = (float) $roomCategory->price * max(1, $roomQuantity);
        $nightCount = 1;
        $extraPercent = 0;
        $policyText = '';

        if ($hour >= 0 && $hour < 6) {
            $checkOutAt = $checkInAt->copy()->setTime(12, 0, 0);
            $policyText = 'Khách vào từ 00:00 đến trước 06:00, tính 1 đêm, trả phòng 12:00 cùng ngày.';
        } elseif ($hour >= 6 && $hour < 9) {
            $checkOutAt = $checkInAt->copy()->addDay()->setTime(12, 0, 0);
            $extraPercent = 0.5;
            $policyText = 'Khách vào từ 06:00 đến trước 09:00, tính 1 đêm và phụ thu nhận phòng sớm 50%, trả phòng 12:00 ngày hôm sau.';
        } elseif ($hour >= 9 && $hour < 12) {
            $checkOutAt = $checkInAt->copy()->addDay()->setTime(12, 0, 0);
            $extraPercent = 0.3;
            $policyText = 'Khách vào từ 09:00 đến trước 12:00, tính 1 đêm và phụ thu nhận phòng sớm 30%, trả phòng 12:00 ngày hôm sau.';
        } elseif ($hour >= 12 && $hour < 13) {
            $checkOutAt = $checkInAt->copy()->addDay()->setTime(12, 0, 0);
            $policyText = 'Khách đến từ 12:00 đến trước 13:00 là giai đoạn phòng vừa trả/dọn. Nếu phòng đang dọn, lễ tân gửi yêu cầu dọn ưu tiên; chỉ cho nhận phòng khi phòng đã sẵn sàng. Không phụ thu tự động, trả phòng 12:00 ngày hôm sau.';
        } elseif ($hour >= 13 && $hour < 14) {
            $checkOutAt = $checkInAt->copy()->addDay()->setTime(12, 0, 0);
            $policyText = 'Khách vào từ 13:00 đến trước 14:00 thuộc khung check-in linh hoạt. Cho nhận phòng nếu phòng đã sẵn sàng; nếu chưa sẵn sàng thì yêu cầu buồng phòng ưu tiên dọn. Không phụ thu tự động, trả phòng 12:00 ngày hôm sau.';
        } else {
            $checkOutAt = $checkInAt->copy()->addDay()->setTime(12, 0, 0);
            $policyText = 'Khách vào từ 14:00 trở đi, tính 1 đêm tiêu chuẩn, trả phòng 12:00 ngày hôm sau.';
        }

        $extraFeeAmount = round($baseRoomTotal * $extraPercent, 0);

        return [
            'check_out_at' => $checkOutAt,
            'night_count' => $nightCount,
            'extra_percent' => $extraPercent,
            'extra_fee_amount' => $extraFeeAmount,
            'policy_text' => $policyText,
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
        $baseRoomTotal = $roomPrice * max(1, $roomQuantity);
        $checkoutHour = (int) substr($checkoutTime, 0, 2);
        $extraFeeAmount = 0;
        $policyText = '';
        $addNight = false;

        if ($checkoutHour >= 13 && $checkoutHour < 14) {
            $extraPercent = 0.2;
            $extraFeeAmount = $baseRoomTotal * $extraPercent;
            $policyText = 'Trả phòng muộn đến 13:00, phụ thu 20% giá phòng.';
        } elseif ($checkoutHour >= 14 && $checkoutHour < 15) {
            $extraPercent = 0.4;
            $extraFeeAmount = $baseRoomTotal * $extraPercent;
            $policyText = 'Trả phòng muộn đến 14:00, phụ thu 40% giá phòng.';
        } elseif ($checkoutHour >= 15 && $checkoutHour < 16) {
            $extraPercent = 0.6;
            $extraFeeAmount = $baseRoomTotal * $extraPercent;
            $policyText = 'Trả phòng muộn đến 15:00, phụ thu 60% giá phòng.';
        } elseif ($checkoutHour >= 16 && $checkoutHour < 18) {
            $extraPercent = 0.8;
            $extraFeeAmount = $baseRoomTotal * $extraPercent;
            $policyText = 'Trả phòng muộn đến 16:00, phụ thu 80% giá phòng.';
        } elseif ($checkoutHour >= 18) {
            $addNight = true;
            $extraFeeAmount = $baseRoomTotal;
            $policyText = 'Trả phòng muộn từ 18:00, tính thêm 1 đêm.';
        }

        return [
            'extra_fee_amount' => $extraFeeAmount,
            'policy_text' => $policyText,
            'add_night' => $addNight,
        ];
    }

    private function createOrUpdateCustomer(array $data)
    {
        $nameParts = preg_split('/\s+/', trim($data['customer_name']));
        $firstName = array_pop($nameParts);
        $lastName = implode(' ', $nameParts);

        return Customer::updateOrCreate(
            [
                'phone' => $data['customer_phone'],
            ],
            [
                'first_name' => $firstName ?: $data['customer_name'],
                'last_name' => $lastName,
                'cccd' => $data['customer_cccd'] ?? null,
                'email' => $data['customer_email'] ?? null,
                'address' => $data['customer_address'] ?? null,
                'status' => 'active',
            ]
        );
    }

    private function generateBookingCode()
    {
        do {
            $code = 'BK' . now()->format('ymd') . strtoupper(Str::random(5));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }

    private function calculateWalkInHourlyPrice(float $nightPrice, int $roomQuantity, int $durationMinutes): array
    {
        $durationHours = max(1, (int) ceil($durationMinutes / 60));

        $baseHours = 2;
        $basePercent = 0.5;
        $extraPercentPerHour = 0.1;
        $capPercent = 0.8;

        if ($durationHours <= $baseHours) {
            $chargedPercent = $basePercent;
            $policyText = 'Ở ngay theo giờ: block tối thiểu 2 giờ đầu = 50% giá qua đêm.';
        } else {
            $extraHours = $durationHours - $baseHours;
            $chargedPercent = $basePercent + ($extraHours * $extraPercentPerHour);

            if ($durationHours > 12) {
                $chargedPercent = 1;
                $policyText = 'Ở ngay theo giờ vượt quá 12 giờ, tự động tính 100% giá qua đêm.';
            } elseif ($chargedPercent >= $capPercent) {
                $chargedPercent = $capPercent;
                $policyText = 'Ở ngay theo giờ đạt ngưỡng 80% giá qua đêm, áp dụng giá nửa ngày/day-use.';
            } else {
                $policyText = 'Ở ngay theo giờ: 2 giờ đầu = 50% giá qua đêm, mỗi giờ tiếp theo +10% giá qua đêm.';
            }
        }

        $amount = round($nightPrice * max(1, $roomQuantity) * $chargedPercent, 0);

        return [
            'duration_hours' => $durationHours,
            'charged_percent' => $chargedPercent,
            'amount' => $amount,
            'policy_text' => $policyText
                . ' Thời lượng thực tế được làm tròn lên '
                . $durationHours
                . ' giờ. Tỷ lệ tính tiền: '
                . round($chargedPercent * 100)
                . '% giá qua đêm.',
        ];
    }

    private function countAvailableRooms($roomCategoryId, $checkInAt, $checkOutAt)
    {
        return $this->availableRoomQuery($roomCategoryId, $checkInAt, $checkOutAt)->count();
    }

    private function getAvailableRooms($roomCategoryId, $quantity, $checkInAt, $checkOutAt)
    {
        return $this->availableRoomQuery($roomCategoryId, $checkInAt, $checkOutAt)
            ->with('category')
            ->inRandomOrder()
            ->take($quantity)
            ->get();
    }

    private function getAdjacentRooms($roomCategoryId, $quantity, $checkInAt, $checkOutAt)
    {
        $rooms = $this->availableRoomQuery($roomCategoryId, $checkInAt, $checkOutAt)
            ->with('category')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get()
            ->groupBy('floor_number');

        $validGroups = collect();

        foreach ($rooms as $floorRooms) {
            $sortedRooms = $floorRooms
                ->sortBy(function ($room) {
                    return (int) preg_replace('/\D/', '', $room->room_number);
                })
                ->values();

            $sequence = collect();

            for ($i = 0; $i < $sortedRooms->count(); $i++) {
                if ($sequence->isEmpty()) {
                    $sequence->push($sortedRooms[$i]);
                } else {
                    $previousRoom = $sequence->last();

                    $previousNumber = (int) preg_replace('/\D/', '', $previousRoom->room_number);
                    $currentNumber = (int) preg_replace('/\D/', '', $sortedRooms[$i]->room_number);

                    if ($currentNumber == $previousNumber + 1) {
                        $sequence->push($sortedRooms[$i]);
                    } else {
                        $sequence = collect([$sortedRooms[$i]]);
                    }
                }

                if ($sequence->count() >= $quantity) {
                    $validGroups->push($sequence->take($quantity)->values());
                }
            }
        }

        if ($validGroups->isEmpty()) {
            return collect();
        }

        return $validGroups->random();
    }

    private function availableRoomQuery($roomCategoryId, $checkInAt, $checkOutAt)
    {
        return Room::where('room_category_id', $roomCategoryId)
            ->availableForPeriod($checkInAt, $checkOutAt);
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
            ->availableForPeriod($checkInAt, $checkOutAt)
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
        $paymentMethod = $request->payment_method ?? 'none';
        $paymentType = $request->payment_type;
        $customPaymentAmount = (float) ($request->deposit_amount ?? 0);

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
            // Lock select rooms for update
            $lockedRooms = Room::whereIn('id', $selectedRoomIds)->lockForUpdate()->get();
            if ($lockedRooms->count() !== count($selectedRoomIds)) {
                throw new \Exception('Một số phòng không tồn tại.');
            }

            // Re-verify availability inside transaction
            $availableRoomIds = Room::whereIn('id', $selectedRoomIds)
                ->availableForPeriod($checkInAt, $checkOutAt)
                ->pluck('id')
                ->toArray();

            $invalidRoomIds = collect($selectedRoomIds)
                ->map(fn($id) => (int) $id)
                ->diff(collect($availableRoomIds)->map(fn($id) => (int) $id));

            if ($invalidRoomIds->isNotEmpty()) {
                throw new \Exception('Một số phòng trong phương án đã bị đặt hoặc đang bảo trì. Vui lòng chọn lại phương án khác.');
            }

            $customer = $this->createOrUpdateCustomer([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_cccd' => $request->customer_cccd,
                'customer_email' => $request->customer_email,
                'customer_address' => $request->customer_address,
            ]);

            $promotionResult = app(PromotionService::class)->validateCodes(
                $request->promotion_codes ?? [],
                [
                    'customer_id' => $customer->id,
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

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'customer_id' => $customer->id,
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
                'status' => 'confirmed',
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

                $room->update([
                    'status' => 'reserved',
                ]);
            }

            foreach ($serviceItems as $item) {
                $this->createBookingServiceItem($booking, $item);
            }

            if ($policyFeeAmount > 0) {
                $this->createPolicyFeeItem(
                    $booking,
                    'Phụ thu nhận phòng sớm',
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
                session()->flash('success', 'Tạo booking và gán phòng thành công. Chưa gửi email xác nhận booking vì khách chưa thanh toán VNPay. Email xác nhận sẽ gửi sau khi VNPay báo thanh toán thành công.');

                return redirect()->away(
                    app(\App\Services\VnpayService::class)->createPaymentUrl($booking, $pendingVnpayPayment, $request)
                );
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
            'type' => 'violation_fee',
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

    public function checkHourlyInventory(Request $request)
    {
        $data = $request->validate([
            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'required|date_format:H:i',
            'room_quantity' => 'required|integer|min:1',
        ]);

        $roomCategory = RoomCategory::findOrFail($data['room_category_id']);

        $checkInAt = Carbon::parse(
            $data['check_in_date'] . ' ' . $data['check_in_time'] . ':00',
            'Asia/Ho_Chi_Minh'
        );

        $checkOutAt = Carbon::parse(
            $data['check_in_date'] . ' ' . $data['check_out_time'] . ':00',
            'Asia/Ho_Chi_Minh'
        );

        if ($checkOutAt->equalTo($checkInAt)) {
            return response()->json([
                'blocked' => true,
                'message' => 'Giờ ra phải khác giờ vào.',
            ]);
        }

        if ($checkOutAt->lessThan($checkInAt)) {
            $checkOutAt->addDay();
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
