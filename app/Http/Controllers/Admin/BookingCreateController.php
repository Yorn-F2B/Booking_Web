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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
use App\Services\BookingServicePricingService;
use App\Services\HotelPolicyService;


class BookingCreateController extends Controller
{
    public function create(Request $request)
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
        $availablePromotionGroups = $availablePromotions->groupBy('promotion_type');

        $queryMode = (string) $request->query('booking_mode', 'advance');
        $queryType = (string) $request->query('booking_type', 'overnight');
        $queryCategoryId = (int) $request->query('room_category_id', 0);

        $bookingPrefill = [
            'booking_mode' => in_array($queryMode, ['advance', 'walk_in'], true) ? $queryMode : 'advance',
            'booking_type' => in_array($queryType, ['overnight', 'hourly'], true) ? $queryType : 'overnight',
            'room_category_id' => $roomCategories->contains('id', $queryCategoryId) ? $queryCategoryId : null,
            'check_in_date' => $this->safePrefillDate($request->query('check_in_date')),
            'check_out_date' => $this->safePrefillDate($request->query('check_out_date')),
            'check_in_time' => $this->safePrefillTime($request->query('check_in_time'))
                ?? now('Asia/Ho_Chi_Minh')->format('H:i'),
            'check_out_time' => $this->safePrefillTime($request->query('check_out_time')),
            'adult_count' => max(1, (int) $request->query('adult_count', 1)),
            'child_count' => max(0, (int) $request->query('child_count', 0)),
            'room_quantity' => max(1, min(max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_rooms', 30)), (int) $request->query('room_quantity', 1))),
        ];

        // Cấu hình hiển thị promotion được truyền từ controller để Blade luôn
        // có dữ liệu kể cả khi view cache còn bản compile cũ.
        $promotionTypeDisplayConfig = [
            'normal_discount' => ['label' => 'Mã thường', 'badge' => 'bg-primary', 'hint' => 'Mã phổ thông dùng cho giảm tiền hoặc tặng/giảm dịch vụ cơ bản.', 'limit' => 1, 'rule' => 'Chọn tối đa 1 mã thường.'],
            'event_discount' => ['label' => 'Mã sự kiện', 'badge' => 'bg-success', 'hint' => 'Mã theo chiến dịch, mùa lễ, combo hoặc chương trình bán hàng.', 'limit' => 1, 'rule' => 'Chọn tối đa 1 mã sự kiện.'],
            'conditional_discount' => ['label' => 'Mã điều kiện', 'badge' => 'bg-warning text-dark', 'hint' => 'Mã chỉ áp dụng khi booking đạt điều kiện về tổng tiền, số đêm, số phòng hoặc lịch sử khách.', 'limit' => 1, 'rule' => 'Chọn tối đa 1 mã điều kiện.'],
            'support_discount' => ['label' => 'Mã hỗ trợ khách', 'badge' => 'bg-danger', 'hint' => '', 'limit' => null, 'rule' => 'Có thể chọn nhiều mã hỗ trợ nếu từng mã cho phép dùng chung.'],
        ];

        // The backend never supports an advance hourly booking. Keep a crafted
        // query string from putting the form into a state that store() will
        // normalize differently.
        if ($bookingPrefill['booking_mode'] === 'advance') {
            $bookingPrefill['booking_type'] = 'overnight';
        }

        return view('admin.pages.bookings.create', compact(
            'roomCategories',
            'services',
            'availablePromotions',
            'availablePromotionGroups',
            'bookingPrefill',
            'promotionTypeDisplayConfig'
        ));
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
        if ($request->input('booking_mode') === 'walk_in') {
            $walkInNow = now('Asia/Ho_Chi_Minh')->startOfMinute();
            $request->merge([
                'check_in_date' => $walkInNow->toDateString(),
                'check_in_time' => $walkInNow->format('H:i'),
            ]);
        }

        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'nullable|string|max:20',
            'customer_cccd' => 'required|regex:/^[0-9]{12}$/',
            'customer_birthday' => 'required|date|before_or_equal:' . now('Asia/Ho_Chi_Minh')->subYears(max(0, (int) app(HotelPolicyService::class)->get('booking.min_age', 18)))->toDateString(),
            'customer_gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'customer_email' => 'nullable|required_if:payment_method,vnpay|email|max:150',
            'customer_address' => 'nullable|string|max:255',

            'booking_mode' => 'required|in:advance,walk_in',
            'booking_type' => 'required|in:overnight,hourly',
            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'nullable|date|after_or_equal:check_in_date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'allow_late_checkout' => 'nullable|boolean',
            'confirm_low_stock' => 'nullable|boolean',
            'confirm_adjacent_fallback' => 'nullable|boolean',

            'adult_count' => 'required|integer|min:1|max:' . max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_guests', 60)),
            'child_count' => 'nullable|integer|min:0|max:' . max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_guests', 60)),
            'room_quantity' => 'required|integer|min:1|max:' . max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_rooms', 30)),
            'prefer_adjacent_rooms' => 'nullable|boolean',
            'room_selection_mode' => 'required|in:automatic,manual',
            'room_selection_request' => 'nullable|string|max:1000',
            'manual_room_ids' => 'nullable|array',
            'manual_room_ids.*' => 'integer|distinct|exists:rooms,id',

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
            'customer_cccd.required' => 'Vui lòng nhập CCCD người đứng tên booking.',
            'customer_cccd.regex' => 'CCCD phải gồm đúng 12 chữ số.',
            'customer_birthday.required' => 'Vui lòng nhập ngày sinh người đứng tên booking.',
            'customer_birthday.before_or_equal' => 'Người đứng tên booking phải đủ ' . max(0, (int) app(HotelPolicyService::class)->get('booking.min_age', 18)) . ' tuổi.',
            'customer_email.required_if' => 'Thanh toán VNPay cần email khách để gửi đường dẫn thanh toán.',
            'customer_email.email' => 'Email khách không đúng định dạng.',
            'booking_mode.required' => 'Vui lòng chọn hình thức tạo booking.',
            'booking_mode.in' => 'Hình thức tạo booking không hợp lệ.',
            'booking_type.required' => 'Vui lòng chọn loại lưu trú.',
            'booking_type.in' => 'Loại lưu trú không hợp lệ.',
            'room_category_id.required' => 'Vui lòng chọn hạng phòng.',
            'check_in_date.required' => 'Vui lòng chọn ngày nhận phòng.',
            'check_in_date.after_or_equal' => 'Ngày nhận phòng không được nhỏ hơn hôm nay.',
            'check_out_date.date' => 'Ngày trả phòng không hợp lệ.',
            'check_out_date.after_or_equal' => 'Ngày trả phòng không được trước ngày nhận phòng.',
            'check_in_time.date_format' => 'Giờ nhận phòng phải đúng định dạng 24 giờ, ví dụ 13:30 hoặc 14:00.',
            'check_out_time.date_format' => 'Giờ trả phòng phải đúng định dạng 24 giờ, ví dụ 16:30 hoặc 18:00.',
            'adult_count.required' => 'Vui lòng nhập số người lớn.',
            'room_quantity.required' => 'Vui lòng nhập số phòng.',
            'room_selection_mode.required' => 'Vui lòng chọn cách phân phòng.',
            'room_selection_mode.in' => 'Cách phân phòng không hợp lệ.',
            'manual_room_ids.*.distinct' => 'Danh sách phòng chọn thủ công bị trùng.',
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
            'payment_type.in' => 'Kiểu thanh toán không hợp lệ.',
            'customer_email.required_if' => 'Thanh toán VNPay bắt buộc phải có email khách để gửi đường dẫn thanh toán.',
        ]);

        $maxOnlineGuests = max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_guests', 60));
        if (((int) $data['adult_count'] + (int) ($data['child_count'] ?? 0)) > $maxOnlineGuests) {
            return back()->withInput()->withErrors(['adult_count' => 'Tổng số người lớn và trẻ em không được vượt quá ' . $maxOnlineGuests . ' người trong một booking.']);
        }
        if ((int) $data['adult_count'] < (int) $data['room_quantity']) {
            return back()->withInput()->withErrors(['adult_count' => 'Mỗi phòng cần ít nhất một người lớn đại diện. Với ' . (int) $data['room_quantity'] . ' phòng cần tối thiểu ' . (int) $data['room_quantity'] . ' người lớn.']);
        }

        $roomCategory = RoomCategory::findOrFail($data['room_category_id']);
        $roomQuantity = (int) $data['room_quantity'];

        $this->assertGuestCapacity(
            (int) $data['adult_count'],
            (int) ($data['child_count'] ?? 0),
            max(0, (int) $roomCategory->adult_capacity) * max(1, $roomQuantity),
            max(0, (int) $roomCategory->child_capacity) * max(1, $roomQuantity)
        );

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

        $roomSelectionMode = (string) ($data['room_selection_mode'] ?? 'automatic');
        $manualRoomIds = collect($data['manual_room_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $manualRoomSelectionFulfilled = false;

        if ($roomSelectionMode === 'manual' && $manualRoomIds->isNotEmpty() && $manualRoomIds->count() !== $roomQuantity) {
            return back()->withInput()->with(
                'error',
                'Nếu chọn phòng cụ thể ngay khi tạo đơn, vui lòng chọn đúng ' . $roomQuantity . ' phòng.'
            );
        }

        $preferAdjacentRooms = $bookingType === 'overnight'
            && $request->boolean('prefer_adjacent_rooms')
            && $roomQuantity >= 2;

        $serviceItems = $this->prepareServiceItems(
            $data['services'] ?? [],
            $nightCount,
            $roomQuantity,
            max(1, (int) $data['adult_count'] + (int) ($data['child_count'] ?? 0))
        );
        $serviceItemTotal = collect($serviceItems)->sum('total');

        $availableCountForSelectedPeriod = null;

        if ($bookingType === 'hourly') {
            $availableCountForSelectedPeriod = $this->countAvailableRooms(
                $data['room_category_id'],
                $checkInAt,
                $checkOutAt
            );
        }

        if ($roomSelectionMode === 'manual' && $manualRoomIds->isNotEmpty()) {
            $availableRooms = Room::query()
                ->whereIn('id', $manualRoomIds->all())
                ->where('room_category_id', $data['room_category_id'])
                ->bookableForPeriod($checkInAt, $checkOutAt)
                ->with('category')
                ->get();

            if ($availableRooms->count() !== $roomQuantity) {
                return back()->withInput()->with(
                    'error',
                    'Một hoặc nhiều phòng chọn thủ công không còn phù hợp với hạng/thời gian booking. Vui lòng tải lại danh sách phòng.'
                );
            }

            $manualRoomSelectionFulfilled = true;
        } elseif ($preferAdjacentRooms) {
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
            $this->validateInitialPaymentChoice($paymentMethod, $paymentType, $customPaymentAmount, $subtotalAmount, $roomQuantity);
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
                    'guest_count' => max(1, (int) $data['adult_count'] + (int) ($data['child_count'] ?? 0)),
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

            $hasCustomerRoomRequest = $roomSelectionMode === 'manual' && trim((string) ($data['room_selection_request'] ?? '')) !== '';
            $manualRoomSelectionFee = $manualRoomSelectionFulfilled && $hasCustomerRoomRequest
                ? round(max(0, (float) app(HotelPolicyService::class)->get('booking.manual_room_selection_fee', 50000)) * $roomQuantity, 0)
                : 0;

            $subtotalAmount = (float) $promotionResult['subtotal_amount'] + $manualRoomSelectionFee;
            $moneyDiscountAmount = (float) ($promotionResult['money_discount_total'] ?? 0);
            $serviceDiscountAmount = (float) ($promotionResult['service_discount_total'] ?? 0);
            $roomUpgradeDiscountAmount = (float) ($promotionResult['room_upgrade_discount_total'] ?? 0);
            $discountAmount = (float) $promotionResult['discount_total'];
            $estimatedTotal = max(0, $subtotalAmount - $discountAmount);
            $roomDiscountForDeposit = min($roomTotal, $moneyDiscountAmount + $roomUpgradeDiscountAmount);
            $requiredDepositAmount = round(max(0, $roomTotal - $roomDiscountForDeposit) * app(HotelPolicyService::class)->depositRate(null, $roomQuantity), 0);
            $initialPaymentAmount = $this->resolveInitialPaymentAmount(
                $paymentMethod,
                $paymentType,
                $customPaymentAmount,
                $estimatedTotal,
                0,
                $requiredDepositAmount
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
                'cleaning_buffer_minutes' => max(0, (int) app(HotelPolicyService::class)->get('booking.cleaning_buffer_minutes', Booking::DEFAULT_CLEANING_BUFFER_MINUTES)),
                'policy_snapshot' => app(HotelPolicyService::class)->snapshot(),
                'adult_count' => $data['adult_count'],
                'child_count' => $data['child_count'] ?? 0,
                'room_quantity' => $roomQuantity,
                'prefer_adjacent_rooms' => $preferAdjacentRooms,
                'room_selection_mode' => $roomSelectionMode,
                'room_selection_request' => $roomSelectionMode === 'manual'
                    ? trim((string) ($data['room_selection_request'] ?? ''))
                    : null,
                'room_selection_status' => $hasCustomerRoomRequest
                    ? ($manualRoomSelectionFulfilled ? 'fulfilled' : 'pending')
                    : 'not_required',
                'room_selection_fee' => $manualRoomSelectionFee,
                'room_selection_handled_by' => $manualRoomSelectionFulfilled ? Auth::id() : null,
                'room_selection_handled_at' => $manualRoomSelectionFulfilled ? now('Asia/Ho_Chi_Minh') : null,
                'subtotal_amount' => $subtotalAmount,
                'discount_amount' => $discountAmount,
                'estimated_total' => $estimatedTotal,
                'deposit_amount' => $depositAmount,
                'required_deposit_amount' => $requiredDepositAmount,
                'payment_status' => $depositAmount >= $estimatedTotal && $estimatedTotal > 0 ? 'paid' : ($depositAmount > 0 ? 'partial' : 'unpaid'),
                'status' => $paymentMethod === 'vnpay' ? 'pending' : 'confirmed',
                // Ở ngay vẫn phải hoàn tất quy trình check-in tại trang chi tiết:
                // xác nhận khách lưu trú/CCCD, tiền cọc và tình trạng phòng trước khi giao phòng.
                'actual_check_in' => null,
                'note' => $data['note'] ?? null,
            ]);

            $this->autoAssignCreatedBooking($booking);

            $roomsForAllocation = $availableRooms->values();
            $roomsForAllocation->loadMissing('category');
            $occupancyAllocation = app(\App\Services\BookingRoomOccupancyAllocator::class)->allocate(
                $roomsForAllocation,
                (int) $data['adult_count'],
                (int) ($data['child_count'] ?? 0),
            );
            foreach ($roomsForAllocation as $room) {
                $roomOccupancy = $occupancyAllocation[(int) $room->id];
                BookingRoom::create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'adult_count' => $roomOccupancy['adult_count'],
                    'child_count' => $roomOccupancy['child_count'],
                    'price_at_booking' => $room->category->price ?? $roomCategory->price,
                    'surcharge' => 0,
                    'surcharge_reason' => null,
                    'created_at' => now(),
                ]);

                app(\App\Services\RoomPreparationService::class)
                    ->flagPriorityIfNeeded($booking, $room, 'lễ tân tạo booking');

                // booking_rooms giữ lịch phòng theo khoảng thời gian. Booking chưa check-in
                // không được ghi đè trạng thái vận hành hiện tại của phòng.
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

            if ($roomSelectionMode === 'manual') {
                $this->addBookingLog(
                    $booking,
                    $manualRoomSelectionFulfilled ? 'manual_room_selection_fulfilled' : 'manual_room_selection_requested',
                    $manualRoomSelectionFulfilled
                        ? 'Lễ tân đã chọn phòng theo yêu cầu ngay khi tạo đơn: ' . $roomNumbers
                            . '. Phí đảm bảo yêu cầu phòng: ' . number_format($manualRoomSelectionFee, 0, ',', '.') . 'đ.'
                        : 'Khách có yêu cầu chọn phòng thủ công: ' . trim((string) ($data['room_selection_request'] ?? ''))
                            . '. Hệ thống đã giữ phòng dự phòng; chờ lễ tân chọn lại phòng và chỉ thu phí khi đáp ứng được.'
                );
            }

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
            app(\App\Services\EmailDeliveryService::class)->sendOrFail($email, new BookingCreatedMail($booking, $source), 'booking_confirmation', $booking);

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
        float $temporaryTotal,
        int $roomQuantity = 1
    ): void {
        $depositLabel = $this->depositPercentForRoomQuantityLabel($roomQuantity);
        if (!in_array($paymentMethod, ['cash', 'bank_transfer', 'vnpay'], true)) {
            throw new \Exception('Booking bắt buộc thanh toán cọc ' . $depositLabel . '.');
        }

        if ($paymentType !== 'deposit_30') {
            throw new \Exception('Khi tạo booking, hệ thống thu mức cọc theo chính sách số lượng phòng (' . $depositLabel . ').');
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
        float $currentPaid = 0,
        ?float $requiredDepositTarget = null
    ): float {
        if ($paymentMethod === 'none' || $estimatedTotal <= 0) {
            return 0;
        }

        $remaining = max(0, $estimatedTotal - $currentPaid);

        if ($paymentType === 'deposit_30') {
            $depositTarget = $requiredDepositTarget !== null
                ? max(0, round($requiredDepositTarget, 0))
                : round($estimatedTotal * app(HotelPolicyService::class)->depositRate(null, $roomQuantity), 0);

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
            . 'cọc ' . $this->depositPercentLabel($booking)
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
            app(\App\Services\EmailDeliveryService::class)->sendOrFail($email, new AdminVnpayPaymentRequestMail($booking, $payment, $paymentUrl, $expiresAt), 'vnpay_payment_request', $booking);

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

    private function safePrefillDate(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value, 'Asia/Ho_Chi_Minh');
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function safePrefillTime(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : null;
    }

    private function assertGuestCapacity(int $adultCount, int $childCount, int $maxAdults, int $maxChildren): void
    {
        $errors = [];

        if ($adultCount > $maxAdults) {
            $errors['adult_count'] = 'Số người lớn vượt sức chứa của số phòng đã chọn (tối đa '
                . $maxAdults . ' người lớn).';
        }

        if ($childCount > $maxChildren) {
            $errors['child_count'] = 'Số trẻ em vượt sức chứa của số phòng đã chọn (tối đa '
                . $maxChildren . ' trẻ em).';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function resolveBookingPeriod(array $data, RoomCategory $roomCategory, int $roomQuantity): array
    {
        $bookingMode = $data['booking_mode'];
        $bookingType = $data['booking_type'];
        $nightCount = 1;
        $policyFeeAmount = 0;
        $policyFeeNote = null;
        $policyExtraPercent = 0;

        // Booking "ở ngay" luôn bắt đầu tại thời điểm thực tế của máy chủ.
        // Không tin ngày/giờ nhận do client gửi lên vì lễ tân không được chọn lùi/tiến thời gian nhận.
        if ($bookingMode === 'walk_in') {
            $walkInNow = now('Asia/Ho_Chi_Minh')->startOfMinute();
            $data['check_in_date'] = $walkInNow->toDateString();
            $data['check_in_time'] = $walkInNow->format('H:i');
        }

        if ($bookingMode === 'advance') {
            $bookingType = 'overnight';

            if (empty($data['check_out_date'])) {
                throw new \Exception('Vui lòng chọn ngày trả phòng cho booking đặt trước.');
            }

            if (strtotime($data['check_out_date']) <= strtotime($data['check_in_date'])) {
                throw new \Exception('Ngày trả phòng phải sau ngày nhận phòng đối với booking đặt trước.');
            }

            $checkInAt = Carbon::parse(
                $data['check_in_date'] . ' ' . $this->standardCheckInTime(),
                'Asia/Ho_Chi_Minh'
            );

            $checkOutAt = Carbon::parse(
                $data['check_out_date'] . ' ' . $this->standardCheckOutTime(),
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

            if ($checkOutAt->lessThan($checkInAt)) {
                throw new \Exception('Thời gian trả phòng phải sau thời gian nhận phòng. Nếu ở qua ngày, hãy chọn đúng ngày trả phòng kế tiếp.');
            }

            $nowVn = now('Asia/Ho_Chi_Minh');

            if ($checkInAt->lt($nowVn->copy()->subMinutes(5))) {
                throw new \Exception('Giờ vào của booking ở ngay không được nhỏ hơn thời điểm hiện tại.');
            }

            $durationMinutes = $checkInAt->diffInMinutes($checkOutAt);

            $minimumHourlyMinutes = max(1, (int) app(HotelPolicyService::class)->get('stay.short_stay_min_minutes', 30));
            if ($durationMinutes < $minimumHourlyMinutes) {
                throw new \Exception('Thời gian ở theo giờ phải tối thiểu ' . $minimumHourlyMinutes . ' phút.');
            }

            $pricingPolicy = app(StayPricingPolicyService::class);

            $overnightThresholdMinutes = max(60, (int) app(HotelPolicyService::class)->get('stay.short_stay_to_overnight_hours', 12) * 60);
            if ($durationMinutes > $overnightThresholdMinutes) {
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

            // Qua đêm trả phòng theo giờ check-out tiêu chuẩn hiện hành.
            $checkOutAt = Carbon::parse(
                $data['check_out_date'] . ' ' . $this->standardCheckOutTime(),
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
                . ' Khách ở ' . $nightCount . ' đêm và trả phòng lúc ' . substr($this->standardCheckOutTime(), 0, 5) . ' ngày '
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
            'check_out_at' => $checkInAt->copy()->addDay()->setTimeFromTimeString($this->standardCheckOutTime()),
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
        $cleaningBufferMinutes = max(0, (int) app(HotelPolicyService::class)->get('booking.cleaning_buffer_minutes', Booking::DEFAULT_CLEANING_BUFFER_MINUTES));
        $cleaningUntil = $checkOutAt->copy()->addMinutes($cleaningBufferMinutes);
        $overnightStartAt = $checkInAt->copy()->setTimeFromTimeString($this->standardCheckInTime());
        $overnightEndAt = $overnightStartAt->copy()->addDay()->setTimeFromTimeString($this->standardCheckOutTime());

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
            . ', vượt mốc check-in cam kết ' . substr($this->standardCheckInTime(), 0, 5) . '. '
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
            ->activeForOperations()
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
        $fullName = trim((string) ($data['customer_name'] ?? ''));
        $nameParts = preg_split('/\s+/', $fullName) ?: [];
        $firstName = array_pop($nameParts);
        $lastName = implode(' ', $nameParts);
        $cccd = preg_replace('/\D+/', '', (string) ($data['customer_cccd'] ?? ''));
        $phone = trim((string) ($data['customer_phone'] ?? ''));
        $email = Str::lower(trim((string) ($data['customer_email'] ?? '')));
        $normalizedName = $this->normalizeCustomerIdentityName($fullName);

        $existingCustomer = null;
        if ($cccd !== '') {
            $existingCustomer = Customer::query()
                ->whereRaw(
                    "REPLACE(REPLACE(REPLACE(cccd, ' ', ''), '-', ''), '.', '') = ?",
                    [$cccd]
                )
                ->lockForUpdate()
                ->first();

            if ($existingCustomer) {
                $candidateName = trim((string) $existingCustomer->last_name . ' ' . (string) $existingCustomer->first_name);
                if ($normalizedName !== '' && $this->normalizeCustomerIdentityName($candidateName) !== $normalizedName) {
                    throw ValidationException::withMessages([
                        'customer_cccd' => 'CCCD này đã thuộc hồ sơ khách "' . $candidateName . '". Vui lòng kiểm tra lại họ tên/CCCD thay vì tạo hồ sơ trùng.',
                    ]);
                }
            }
        }

        // DB đang đặt unique cho phone/email. Báo lỗi nghiệp vụ rõ ràng trước khi để
        // MySQL ném duplicate-key khó hiểu, đồng thời không tự gộp hai người chỉ vì
        // dùng chung thông tin liên hệ.
        $conflictingPhone = $phone !== ''
            ? Customer::query()->where('phone', $phone)
                ->when($existingCustomer, fn ($q) => $q->whereKeyNot($existingCustomer->id))
                ->first()
            : null;
        if ($conflictingPhone) {
            throw ValidationException::withMessages([
                'customer_phone' => 'Số điện thoại này đã thuộc một hồ sơ khách khác. Vui lòng kiểm tra lại thông tin khách.',
            ]);
        }

        $conflictingEmail = $email !== ''
            ? Customer::query()->whereRaw('LOWER(email) = ?', [$email])
                ->when($existingCustomer, fn ($q) => $q->whereKeyNot($existingCustomer->id))
                ->first()
            : null;
        if ($conflictingEmail) {
            throw ValidationException::withMessages([
                'customer_email' => 'Email này đã thuộc một hồ sơ khách khác. Vui lòng kiểm tra lại thông tin khách.',
            ]);
        }

        $attributes = [
            'first_name' => $firstName ?: $fullName,
            'last_name' => $lastName,
            'phone' => $phone !== '' ? $phone : null,
            'cccd' => $cccd ?: null,
            'birthday' => $data['customer_birthday'] ?? null,
        ];

        if (!empty($data['customer_gender'])) {
            $attributes['gender'] = $data['customer_gender'];
        }
        if ($email !== '') {
            $attributes['email'] = $email;
        }
        if (trim((string) ($data['customer_address'] ?? '')) !== '') {
            $attributes['address'] = trim((string) $data['customer_address']);
        }

        if ($existingCustomer) {
            // Không tự gỡ blacklist và không xóa email/địa chỉ cũ chỉ vì form để trống.
            $existingCustomer->fill($attributes)->save();
            return $existingCustomer;
        }

        // Khách mới: bổ sung các trường nullable còn thiếu để snapshot nhất quán.
        $attributes['gender'] = $attributes['gender'] ?? null;
        $attributes['email'] = $attributes['email'] ?? null;
        $attributes['address'] = $attributes['address'] ?? null;

        return Customer::create($attributes + ['status' => 'active']);
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
        $validated = $request->validate([
            'selected_room_ids' => 'required|array|min:1',
            'selected_room_ids.*' => 'required|integer|distinct|exists:rooms,id',

            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'nullable|string|max:20',
            'customer_cccd' => 'required|regex:/^[0-9]{12}$/',
            'customer_birthday' => 'required|date|before_or_equal:' . now('Asia/Ho_Chi_Minh')->subYears(max(0, (int) app(HotelPolicyService::class)->get('booking.min_age', 18)))->toDateString(),
            'customer_gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'customer_email' => 'nullable|required_if:payment_method,vnpay|email|max:150',
            'customer_address' => 'nullable|string|max:255',

            'booking_mode' => 'required|in:advance,walk_in',
            'booking_type' => 'required|in:overnight,hourly',
            'room_category_id' => 'required|exists:room_categories,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'nullable|date|after_or_equal:check_in_date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'allow_late_checkout' => 'nullable|boolean',
            'confirm_low_stock' => 'nullable|boolean',
            'confirm_adjacent_fallback' => 'nullable|boolean',

            'adult_count' => 'required|integer|min:1|max:' . max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_guests', 60)),
            'child_count' => 'nullable|integer|min:0|max:' . max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_guests', 60)),
            'room_quantity' => 'required|integer|min:1|max:' . max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_rooms', 30)),
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
            'selected_room_ids.required' => 'Không tìm thấy phương án phòng đã chọn.',
            'selected_room_ids.*.distinct' => 'Phương án phòng bị trùng phòng, vui lòng chọn lại.',
            'customer_name.required' => 'Vui lòng nhập họ tên khách hàng.',
            'customer_cccd.required' => 'Vui lòng nhập CCCD người đứng tên booking.',
            'customer_cccd.regex' => 'CCCD phải gồm đúng 12 chữ số.',
            'customer_birthday.required' => 'Vui lòng nhập ngày sinh người đứng tên booking.',
            'customer_birthday.before_or_equal' => 'Người đứng tên booking phải đủ ' . max(0, (int) app(HotelPolicyService::class)->get('booking.min_age', 18)) . ' tuổi.',
            'booking_mode.required' => 'Vui lòng chọn hình thức tạo booking.',
            'booking_type.required' => 'Vui lòng chọn loại lưu trú.',
            'room_category_id.required' => 'Vui lòng chọn hạng phòng.',
            'check_in_date.required' => 'Vui lòng chọn ngày nhận phòng.',
            'check_in_date.after_or_equal' => 'Ngày nhận phòng không được nhỏ hơn hôm nay.',
            'payment_method.required' => 'Booking bắt buộc phải chọn phương thức thanh toán.',
            'payment_type.required' => 'Booking bắt buộc chọn mức cọc theo chính sách số lượng phòng.',
            'customer_email.required_if' => 'Thanh toán VNPay bắt buộc phải có email khách để gửi đường dẫn thanh toán.',
        ]);

        $maxOnlineGuests = max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_guests', 60));
        if (((int) $validated['adult_count'] + (int) ($validated['child_count'] ?? 0)) > $maxOnlineGuests) {
            return back()->withInput()->withErrors(['adult_count' => 'Tổng số người lớn và trẻ em không được vượt quá ' . $maxOnlineGuests . ' người trong một booking.']);
        }
        if ((int) $validated['adult_count'] < (int) $validated['room_quantity']) {
            return back()->withInput()->withErrors(['adult_count' => 'Mỗi phòng cần ít nhất một người lớn đại diện. Với ' . (int) $validated['room_quantity'] . ' phòng cần tối thiểu ' . (int) $validated['room_quantity'] . ' người lớn.']);
        }

        $selectedRoomIds = collect($validated['selected_room_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($selectedRoomIds)) {
            return redirect()
                ->route('admin.bookings.create')
                ->with('error', 'Không tìm thấy phòng được chọn.');
        }

        if (count($selectedRoomIds) !== (int) $validated['room_quantity']) {
            return back()->withInput()->withErrors([
                'selected_room_ids' => 'Bạn phải chọn đúng ' . (int) $validated['room_quantity'] . ' phòng cho booking này.',
            ]);
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

        if ($rooms->contains(fn ($room) => (int) $room->room_category_id !== (int) $roomCategory->id)) {
            return back()->withInput()->withErrors([
                'selected_room_ids' => 'Tất cả phòng chọn cho một booking phải thuộc đúng hạng phòng đã chọn.',
            ]);
        }

        $this->assertGuestCapacity(
            (int) $validated['adult_count'],
            (int) ($validated['child_count'] ?? 0),
            (int) $rooms->sum(fn ($room) => max(0, (int) ($room->category?->adult_capacity ?? 0))),
            (int) $rooms->sum(fn ($room) => max(0, (int) ($room->category?->child_capacity ?? 0)))
        );

        $roomQuantity = count($selectedRoomIds);

        $data = [
            'booking_mode' => $request->booking_mode ?? 'advance',
            'booking_type' => $request->booking_type ?? 'overnight',
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'check_in_time' => $request->check_in_time,
            'check_out_time' => $request->check_out_time,
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

        if ($bookingType === 'hourly') {
            return redirect()
                ->route('admin.bookings.create')
                ->withInput()
                ->with('error', 'Booking theo giờ không dùng phương án ghép hạng phòng. Vui lòng tra cứu lại phòng trống trong đúng khung giờ.');
        }

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

        $serviceItems = $this->prepareServiceItems(
            $request->services ?? [],
            $nightCount,
            $roomQuantity,
            max(1, (int) $request->adult_count + (int) ($request->child_count ?? 0))
        );
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
            $this->validateInitialPaymentChoice($paymentMethod, $paymentType, $customPaymentAmount, $subtotalAmount, $roomQuantity);
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
                'customer_birthday' => $request->customer_birthday,
                'customer_gender' => $request->customer_gender,
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
                    'guest_count' => max(1, (int) $request->adult_count + (int) ($request->child_count ?? 0)),
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
            $roomUpgradeDiscountAmount = (float) ($promotionResult['room_upgrade_discount_total'] ?? 0);
            $discountAmount = (float) $promotionResult['discount_total'];
            $estimatedTotal = max(0, $subtotalAmount - $discountAmount);
            $roomDiscountForDeposit = min($roomTotal, $moneyDiscountAmount + $roomUpgradeDiscountAmount);
            $requiredDepositAmount = round(max(0, $roomTotal - $roomDiscountForDeposit) * app(HotelPolicyService::class)->depositRate(null, $roomQuantity), 0);
            $initialPaymentAmount = $this->resolveInitialPaymentAmount(
                $paymentMethod,
                $paymentType,
                $customPaymentAmount,
                $estimatedTotal,
                0,
                $requiredDepositAmount
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
                'cleaning_buffer_minutes' => max(0, (int) app(HotelPolicyService::class)->get('booking.cleaning_buffer_minutes', Booking::DEFAULT_CLEANING_BUFFER_MINUTES)),
                'policy_snapshot' => app(HotelPolicyService::class)->snapshot(),
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
                'required_deposit_amount' => $requiredDepositAmount,
                'payment_status' => $depositAmount >= $estimatedTotal && $estimatedTotal > 0 ? 'paid' : ($depositAmount > 0 ? 'partial' : 'unpaid'),
                'status' => $paymentMethod === 'vnpay' ? 'pending' : 'confirmed',
                'note' => $request->note,
            ]);

            $this->autoAssignCreatedBooking($booking);

            $roomsForAllocation = $rooms->values();
            $roomsForAllocation->loadMissing('category');
            $occupancyAllocation = app(\App\Services\BookingRoomOccupancyAllocator::class)->allocate(
                $roomsForAllocation,
                (int) $request->adult_count,
                (int) ($request->child_count ?? 0),
            );
            foreach ($roomsForAllocation as $room) {
                $roomOccupancy = $occupancyAllocation[(int) $room->id];
                BookingRoom::create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'adult_count' => $roomOccupancy['adult_count'],
                    'child_count' => $roomOccupancy['child_count'],
                    'price_at_booking' => $room->category->price ?? $roomCategory->price,
                    'surcharge' => 0,
                    'surcharge_reason' => null,
                    'created_at' => now(),
                ]);

                app(\App\Services\RoomPreparationService::class)
                    ->flagPriorityIfNeeded($booking, $room, 'lễ tân tạo booking');

                // booking_rooms giữ lịch phòng theo khoảng thời gian. Booking chưa check-in
                // không được ghi đè trạng thái vận hành hiện tại của phòng.
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
            'scope' => $item['scope'] ?? 'booking',
            'booking_room_id' => $item['booking_room_id'] ?? null,
            'room_id_snapshot' => $item['room_id_snapshot'] ?? null,
            'source_type' => $item['source_type'] ?? 'manual',
            'source_id' => $item['source_id'] ?? null,
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
            'note' => $item['note'] ?? null,
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

        if (!$user || !in_array($user->role, ['receptionist', 'receptionist_lead'], true)) {
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

    private function prepareServiceItems(
        array $items,
        int $nightCount,
        int $roomQuantity,
        int $guestCount
    ): array {
        $preparedItems = [];
        $pricingService = app(BookingServicePricingService::class);

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

            $baseQuantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = (float) $service->price;
            $snapshot = $pricingService->snapshotForService(
                $service,
                $baseQuantity,
                $unitPrice,
                max(1, $nightCount),
                max(1, $roomQuantity),
                max(1, $guestCount)
            );

            $preparedItems[] = [
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
                'total' => $snapshot['total'],
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
            'room_quantity' => 'required|integer|min:1|max:' . max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_rooms', 30)),
        ]);

        $bookingMode = $data['booking_mode'];
        $bookingType = $bookingMode === 'advance' ? 'overnight' : $data['booking_type'];

        if ($bookingMode === 'walk_in') {
            $walkInNow = now('Asia/Ho_Chi_Minh')->startOfMinute();
            $data['check_in_date'] = $walkInNow->toDateString();
            $data['check_in_time'] = $walkInNow->format('H:i');
        }

        if ($bookingMode === 'advance') {
            $checkInAt = Carbon::parse($data['check_in_date'] . ' ' . $this->standardCheckInTime(), 'Asia/Ho_Chi_Minh');
            $checkOutAt = Carbon::parse($data['check_out_date'] . ' ' . $this->standardCheckOutTime(), 'Asia/Ho_Chi_Minh');
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
            $checkOutAt = Carbon::parse($data['check_out_date'] . ' ' . $this->standardCheckOutTime(), 'Asia/Ho_Chi_Minh');
        }

        if ($checkOutAt->lessThanOrEqualTo($checkInAt)) {
            return response()->json([
                'categories' => [],
                'message' => 'Thời gian trả phòng phải sau thời gian nhận phòng.',
            ]);
        }

        $normalizedBookingType = $bookingType;
        if ($bookingMode === 'walk_in' && $bookingType === 'hourly') {
            $overnightThresholdMinutes = max(60, (int) app(HotelPolicyService::class)->get('stay.short_stay_to_overnight_hours', 12) * 60);

            if ($checkInAt->diffInMinutes($checkOutAt) > $overnightThresholdMinutes) {
                // Đồng bộ với resolveBookingPeriod(): ca ở ngay kéo dài quá ngưỡng
                // chính sách là booking qua đêm. Tồn phòng phải kiểm tra theo đúng
                // khoảng qua đêm (giờ trả tiêu chuẩn), không trả danh sách rỗng giả.
                $normalizedBookingType = 'overnight';
                $bookingType = 'overnight';
                $checkOutAt = Carbon::parse(
                    $data['check_out_date'] . ' ' . $this->standardCheckOutTime(),
                    'Asia/Ho_Chi_Minh'
                );

                if ($checkOutAt->lessThanOrEqualTo($checkInAt)) {
                    return response()->json([
                        'categories' => [],
                        'normalized_booking_type' => $normalizedBookingType,
                        'message' => 'Ngày trả phòng phải sau ngày nhận phòng.',
                    ]);
                }
            }
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
            ->filter(fn (array $category) => $category['available_count'] >= (int) $data['room_quantity'])
            ->values();

        return response()->json([
            'categories' => $categories,
            'check_in_at' => $checkInAt->format('d/m/Y H:i'),
            'check_out_at' => $checkOutAt->format('d/m/Y H:i'),
            'normalized_booking_type' => $normalizedBookingType,
        ]);
    }

    public function manualRoomOptions(Request $request)
    {
        $data = $request->validate([
            'booking_mode' => 'required|in:advance,walk_in',
            'booking_type' => 'required|in:overnight,hourly',
            'room_category_id' => 'required|exists:room_categories,id',
            'room_quantity' => 'required|integer|min:1|max:' . max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_rooms', 30)),
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'nullable|date|after_or_equal:check_in_date',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
        ]);

        $roomCategory = RoomCategory::findOrFail($data['room_category_id']);

        try {
            $period = $this->resolveBookingPeriod($data, $roomCategory, (int) $data['room_quantity']);
        } catch (\Throwable $e) {
            return response()->json(['rooms' => [], 'message' => $e->getMessage()], 422);
        }

        $rooms = Room::query()
            ->where('room_category_id', $roomCategory->id)
            ->bookableForPeriod($period['check_in_at'], $period['check_out_at'])
            ->with('category:id,name')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get()
            ->map(fn (Room $room) => [
                'id' => (int) $room->id,
                'room_number' => $room->room_number,
                'floor_number' => $room->floor_number,
                'status' => $room->status,
                'category_name' => $room->category?->name,
            ])
            ->values();

        if ($rooms->count() < (int) $data['room_quantity']) {
            return response()->json([
                'rooms' => [],
                'required_quantity' => (int) $data['room_quantity'],
                'available_quantity' => $rooms->count(),
                'check_in_at' => $period['check_in_at']->format('d/m/Y H:i'),
                'check_out_at' => $period['check_out_at']->format('d/m/Y H:i'),
                'message' => 'Không còn đủ số phòng cần chọn trong toàn bộ thời gian lưu trú.',
            ]);
        }

        return response()->json([
            'rooms' => $rooms,
            'required_quantity' => (int) $data['room_quantity'],
            'check_in_at' => $period['check_in_at']->format('d/m/Y H:i'),
            'check_out_at' => $period['check_out_at']->format('d/m/Y H:i'),
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
            'room_quantity' => 'required|integer|min:1|max:' . max(1, (int) app(HotelPolicyService::class)->get('booking.max_online_rooms', 30)),
        ]);

        $roomCategory = RoomCategory::findOrFail($data['room_category_id']);

        $walkInNow = now('Asia/Ho_Chi_Minh')->startOfMinute();
        $data['check_in_date'] = $walkInNow->toDateString();
        $data['check_in_time'] = $walkInNow->format('H:i');

        $checkInAt = $walkInNow->copy();

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

        $minimumShortStayMinutes = max(1, (int) app(HotelPolicyService::class)->get('stay.short_stay_min_minutes', 30));
        if ($durationMinutes < $minimumShortStayMinutes) {
            return response()->json([
                'blocked' => true,
                'message' => 'Thời gian ở theo giờ phải tối thiểu ' . $minimumShortStayMinutes . ' phút.',
            ]);
        }

        $overnightThresholdMinutes = max(60, (int) app(HotelPolicyService::class)->get('stay.short_stay_to_overnight_hours', 12) * 60);
        if ($durationMinutes > $overnightThresholdMinutes) {
            return response()->json([
                'blocked' => false,
                'switch_to_overnight' => true,
                'message' => 'Thời gian lưu trú vượt ' . (int) ceil($overnightThresholdMinutes / 60) . ' giờ nên hệ thống chuyển sang qua đêm.',
            ]);
        }

        $occupiedUntil = $checkOutAt->copy()->addMinutes(max(0, (int) app(HotelPolicyService::class)->get('booking.cleaning_buffer_minutes', Booking::DEFAULT_CLEANING_BUFFER_MINUTES)));
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

        $overnightStart = $checkInAt->copy()->setTimeFromTimeString($this->standardCheckInTime());
        $overnightEnd = $overnightStart->copy()->addDay()->setTimeFromTimeString($this->standardCheckOutTime());
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
                : 'Thông tin: Ca ở ngay theo giờ này có đi qua mốc check-in qua đêm ' . substr($this->standardCheckInTime(), 0, 5) . ', nhưng hạng '
                . $roomCategory->name
                . ' vẫn còn '
                . $remainingAfterHourly
                . ' phòng dự phòng qua đêm nên chưa cần cảnh báo.';
        } else {
            $message = 'Khung giờ này không ảnh hưởng mốc check-in qua đêm ' . substr($this->standardCheckInTime(), 0, 5) . '.';
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
    private function standardCheckInTime(): string
    {
        return (string) app(HotelPolicyService::class)->get('stay.standard_check_in_time', '14:00') . ':00';
    }

    private function standardCheckOutTime(): string
    {
        return (string) app(HotelPolicyService::class)->get('stay.standard_check_out_time', '12:00') . ':00';
    }



    private function depositPercentForRoomQuantityLabel(int $roomQuantity): string
    {
        $percent = (float) app(HotelPolicyService::class)->depositPercentForRooms(max(1, $roomQuantity));
        return rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') . '%';
    }

    private function currentDepositPercentLabel(): string
    {
        $percent = (float) app(HotelPolicyService::class)->get('payment.deposit_percent', 30);
        return rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') . '%';
    }

    private function depositPercentLabel(Booking $booking): string
    {
        $percent = (float) app(HotelPolicyService::class)->depositRate($booking) * 100;
        return rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') . '%';
    }

}
