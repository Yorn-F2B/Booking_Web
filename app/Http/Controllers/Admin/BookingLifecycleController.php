<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\RoomInspection;
use Illuminate\Support\Facades\DB;
use App\Models\BookingRoom;
use App\Models\BookingRoomChange;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\BookingServiceItem;
use App\Models\BookingLog;
use App\Models\BookingPayment;
use App\Services\BookingFinancialService;
use App\Services\BookingOccupancyFeeService;
use App\Services\BookingRepricingService;
use App\Services\StayPricingPolicyService;
use Illuminate\Support\Facades\Auth;
use App\Support\Realtime;

class BookingLifecycleController extends Controller
{
    private const PRIORITY_CLEANING_START_TIME = Booking::PRIORITY_CLEANING_START_TIME;
    private const EARLY_CHECK_IN_TIME = Booking::EARLY_CHECK_IN_TIME;
    private const STANDARD_CHECK_IN_TIME = Booking::STANDARD_CHECK_IN_TIME;
    private const STANDARD_CHECK_OUT_TIME = Booking::STANDARD_CHECK_OUT_TIME;
    public function checkIn(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);


        $data = $request->validate([
            'actual_adult_count' => 'required|integer|min:1',
            'actual_child_count' => 'nullable|integer|min:0',
            'check_in_cccd' => ['nullable', 'string', 'max:50'],
            'cccd_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'scanned_full_name' => ['nullable', 'string', 'max:255'],
            'scanned_birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'scanned_gender' => ['nullable', 'in:male,female,other'],
            'scanned_address' => ['nullable', 'string', 'max:1000'],
            'guest_nationality' => ['nullable', 'string', 'max:100'],

            'over_capacity_action' => 'nullable|in:extra_fee,add_room,change_category',
            'actual_baby_count' => 'nullable|integer|min:0',

            'extra_service_ids' => 'nullable|array',
            'extra_service_ids.*' => 'nullable|exists:services,id',
            'extra_booking_room_ids' => 'nullable|array',
            'extra_booking_room_ids.*' => 'nullable|exists:booking_rooms,id',
            'extra_guest_types' => 'nullable|array',
            'extra_guest_types.*' => 'nullable|in:adult,minor',
            'extra_quantities' => 'nullable|array',
            'extra_quantities.*' => 'nullable|integer|min:1',
            'extra_fee_notes' => 'nullable|array',
            'extra_fee_notes.*' => 'nullable|string|max:1000',

            'early_check_in_action' => 'nullable|in:accept_fee',
        ], [
            'scanned_birthday.before_or_equal' => 'Ngày sinh không được lớn hơn ngày hiện tại.',
        ]);

        $actualAdultCount = (int) $data['actual_adult_count'];
        $actualChildCount = (int) ($data['actual_child_count'] ?? 0);
        $actualBabyCount = (int) ($data['actual_baby_count'] ?? 0);
        $actualCheckInAt = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');

        $booking->load([
            'bookingRooms.room.category',
            'roomCategory',
            'customer',
        ]);

        if ($booking->bookingRooms->count() == 0) {
            return back()->with('error', 'Booking này chưa được gán phòng nên không thể check-in.');
        }

        $booking->load(['guests.bookingRoom.room.category', 'guests.guardian']);

        $declaredAdults = $booking->guests->where('guest_type', 'adult')->count();
        $declaredChildren = $booking->guests->where('guest_type', 'child')->count();
        $declaredInfants = $booking->guests->where('guest_type', 'infant')->count();
        $declaredTotal = $booking->guests->count();
        $actualTotal = $actualAdultCount + $actualChildCount + $actualBabyCount;

        if ($declaredTotal !== $actualTotal
            || $declaredAdults !== $actualAdultCount
            || $declaredChildren !== $actualChildCount
            || $declaredInfants !== $actualBabyCount) {
            return back()->withInput()->with('error',
                'Chưa thể check-in: danh sách khai báo lưu trú phải khớp khách thực tế. '
                . 'Đã khai ' . $declaredAdults . ' người lớn / ' . $declaredChildren . ' trẻ em / ' . $declaredInfants . ' em bé; '
                . 'form check-in đang nhập ' . $actualAdultCount . ' / ' . $actualChildCount . ' / ' . $actualBabyCount . '.'
            );
        }

        if ($booking->guests->contains(fn ($guest) => empty($guest->booking_room_id))) {
            return back()->withInput()->with('error', 'Chưa thể check-in: tất cả khách phải được gán đúng phòng lưu trú.');
        }

        if ($booking->guests->where('is_booking_representative', true)->count() !== 1) {
            return back()->withInput()->with('error', 'Chưa thể check-in: cần chọn đúng một người đại diện đoàn trong danh sách khách lưu trú.');
        }

        foreach ($booking->guests->whereIn('guest_type', ['child', 'infant']) as $minorGuest) {
            if (!$minorGuest->guardian || $minorGuest->guardian->guest_type !== 'adult') {
                return back()->withInput()->with('error', 'Chưa thể check-in: trẻ em/em bé ' . $minorGuest->full_name . ' chưa có người giám hộ hợp lệ.');
            }
        }

        $perRoomOverCapacity = false;
        $perRoomCapacityIssues = [];

        foreach ($booking->bookingRooms as $bookingRoom) {
            $roomGuests = $booking->guests->where('booking_room_id', $bookingRoom->id);
            $roomAdultCount = $roomGuests->where('guest_type', 'adult')->count();
            $roomChildCount = $roomGuests->whereIn('guest_type', ['child', 'infant'])->count();
            $adultCapacity = (int) ($bookingRoom->room?->category?->adult_capacity ?? 0);
            $childCapacity = (int) ($bookingRoom->room?->category?->child_capacity ?? 0);
            $adultOver = max(0, $roomAdultCount - $adultCapacity);
            $childOver = max(0, $roomChildCount - $childCapacity);

            if ($adultOver > 0 || $childOver > 0) {
                $perRoomOverCapacity = true;
                $parts = [];

                if ($adultOver > 0) {
                    $parts[] = 'vượt ' . $adultOver . ' người lớn';
                }

                if ($childOver > 0) {
                    $parts[] = 'vượt ' . $childOver . ' trẻ em/em bé';
                }

                $perRoomCapacityIssues[] = 'Phòng '
                    . ($bookingRoom->room?->room_number ?? '---')
                    . ' ' . implode(' và ', $parts);
            }
        }

        $scannedCccd = trim((string) ($data['check_in_cccd'] ?? ''));

        try {
            $this->guardCheckInArrivalTime($booking, $actualCheckInAt);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $currentAdultCapacity = $booking->bookingRooms->reduce(
            function ($total, $bookingRoom) {
                return $total + ($bookingRoom->room->category->adult_capacity ?? 0);
            },
            0
        );

        $currentChildCapacity = $booking->bookingRooms->reduce(
            function ($total, $bookingRoom) {
                return $total + ($bookingRoom->room->category->child_capacity ?? 0);
            },
            0
        );

        $isOverCapacity = $actualAdultCount > $currentAdultCapacity
            || ($actualChildCount + $actualBabyCount) > $currentChildCapacity
            || $perRoomOverCapacity;

        if ($isOverCapacity && empty($data['over_capacity_action'])) {
            $detail = $perRoomCapacityIssues !== []
                ? ' ' . implode('; ', $perRoomCapacityIssues) . '.'
                : '';

            return back()->with(
                'error',
                'Phân bổ khách hiện tại chưa hợp lệ.' . $detail
                . ' Hãy ưu tiên chuyển khách sang phòng còn chỗ; nếu vẫn vượt thì thu phụ phí, thêm phòng hoặc đổi hạng trước khi check-in.'
            );
        }

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($booking->status !== 'confirmed') {
                throw new \Exception('Chỉ có thể nhận phòng với booking đã xác nhận.');
            }

            $financialService = app(BookingFinancialService::class);
            $currentTotal = $financialService->currentTotal($booking);
            $paidTotal = $financialService->paidTotal($booking);
            $requiredDeposit = $financialService->requiredDeposit($booking);

            if ($currentTotal > 0 && $paidTotal + 0.01 < $requiredDeposit) {
                throw new \Exception('Booking chưa thanh toán đủ mức cọc đã chốt khi đặt phòng. Đã thanh toán ' . number_format($paidTotal, 0, ',', '.') . 'đ, yêu cầu tối thiểu ' . number_format($requiredDeposit, 0, ',', '.') . 'đ.');
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $actionNote = '';

            if ($booking->payment_status === 'unpaid') {
                $actionNote .= ' Booking chưa thu tiền/cọc, lễ tân cần thu tại quầy hoặc khi check-out.';
            }

            if ($isOverCapacity && ($data['over_capacity_action'] ?? null) === 'extra_fee') {
                $actionNote .= ' ' . $this->handleExtraGuestFees($booking, $data);
            }

            $earlyCheckInNote = $this->handleEarlyCheckInFee($booking, $data, $actualCheckInAt);

            if ($earlyCheckInNote !== '') {
                $actionNote .= ' ' . $earlyCheckInNote;
            }

            $lateArrivalNote = $this->handleLateArrivalFee($booking, $data);

            if ($lateArrivalNote !== '') {
                $actionNote .= ' ' . $lateArrivalNote;
            }

            $roomReadyNote = $this->prepareRoomsForCheckIn($booking, $actualCheckInAt);

            if ($roomReadyNote !== '') {
                $actionNote .= ' ' . $roomReadyNote;
            }

            $booking->load([
                'bookingRooms.room.category',
                'roomCategory',
            ]);

            $booking->adult_count = $actualAdultCount;
            $booking->child_count = $actualChildCount;
            $booking->baby_count = $actualBabyCount;
            $booking->status = 'checked_in';
            $booking->actual_check_in = $actualCheckInAt;

            $booking->note = $oldNote
                . $actualCheckInAt->format('d/m/Y H:i')
                . ' - Check-in thực tế: '
                . $actualAdultCount
                . ' người lớn / '
                . $actualChildCount
                . ' trẻ em / '
                . $actualBabyCount
                . ' em bé. '
                . trim($actionNote);

            $booking->save();

            // Phụ thu vượt sức chứa là dịch vụ đã xác nhận theo từng phòng; tính lại tổng booking ngay sau khi lưu.
            $this->repriceCurrentBooking($booking);

            // Danh sách khách đã được khai đầy đủ trước khi check-in; chỉ đồng bộ trạng thái và giờ nhận phòng.
            $booking->guests()->update([
                'status' => 'checked_in',
                'actual_check_in_at' => $actualCheckInAt,
                'updated_by' => Auth::id(),
            ]);

            foreach ($booking->bookingRooms as $bookingRoom) {
                $roomGuests = $booking->guests->where('booking_room_id', $bookingRoom->id);
                $bookingRoom->update([
                    'adult_count' => $roomGuests->where('guest_type', 'adult')->count(),
                    'child_count' => $roomGuests->whereIn('guest_type', ['child', 'infant'])->count(),
                ]);
            }

            $booking->load('bookingRooms.room');

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $roomGuestCount = $booking->guests->where('booking_room_id', $bookingRoom->id)->count();
                    $bookingRoom->room->update([
                        'status' => $roomGuestCount > 0 ? 'occupied' : 'reserved',
                    ]);

                    \App\Models\RoomActionLog::create([
                        'room_id' => $bookingRoom->room->id,
                        'user_id' => Auth::id(),
                        'action_type' => 'check_in',
                        'action_time' => now(),
                        'note' => $roomGuestCount > 0
                            ? 'Có ' . $roomGuestCount . ' khách check-in từ booking #' . $booking->booking_code
                            : 'Phòng tiếp tục giữ cho booking #' . $booking->booking_code . ' vì khách phòng này chưa đến.',
                    ]);
                }
            }

            $this->addBookingLog(
                $booking,
                'check_in',
                'Xác nhận check-in thực tế: '
                . $actualAdultCount . ' người lớn / '
                . $actualChildCount . ' trẻ em / '
                . $actualBabyCount . ' em bé. '
                . trim($actionNote)
            );

            DB::commit();

            Realtime::booking($booking, 'checked_in');

            return back()->with('success', 'Check-in thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi check-in: ' . $e->getMessage());
        }
    }


    public function previewExtendStay(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Chỉ booking đang ở mới được kiểm tra gia hạn lưu trú.');
        }

        try {
            [$oldCheckOutAt, $newCheckOutAt] = $this->getExtendStayTimesFromRequest($request, $booking);

            $booking->load([
                'bookingRooms.room.category',
                'roomCategory',
                'customer',
                'serviceItems',
            ]);

            $analysis = $this->analyzeExtendStay($booking, $oldCheckOutAt, $newCheckOutAt);
            $repricingPreview = $this->previewOvernightExtensionRepricing(
                $booking,
                $oldCheckOutAt,
                $newCheckOutAt
            );

            session()->put('extend_stay_preview', [
                'status' => $analysis['status'],
                'title' => $analysis['title'],
                'message' => $analysis['message'],
                'booking_type' => $booking->booking_type === 'hourly' ? 'Gói giờ' : 'Qua đêm',
                'old_check_out_date' => $oldCheckOutAt->format('Y-m-d'),
                'old_check_out_time' => $oldCheckOutAt->format('H:i'),
                'new_check_out_date' => $newCheckOutAt->format('Y-m-d'),
                'new_check_out_time' => $newCheckOutAt->format('H:i'),
                'old_check_out_text' => $oldCheckOutAt->format('d/m/Y H:i'),
                'new_check_out_text' => $newCheckOutAt->format('d/m/Y H:i'),
                'period_text' => $oldCheckOutAt->format('d/m/Y H:i') . ' → ' . $newCheckOutAt->format('d/m/Y H:i'),
                'fee_amount' => $analysis['fee_amount'],
                'fee_text' => number_format($analysis['fee_amount'], 0, ',', '.') . 'đ',
                'policy_text' => $analysis['policy_text'],
                'conflicts' => $analysis['conflicts_for_view'],
                'replacements' => $analysis['replacements_for_view'],
                'repricing' => $repricingPreview,
            ]);

            return redirect(route('admin.bookings.show', $booking) . '#extend-stay-preview');
        } catch (\Throwable $e) {
            return back()->with('error', 'Không thể kiểm tra gia hạn: ' . $e->getMessage());
        }
    }

    public function extendStay(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Chỉ booking đang ở mới được gia hạn lưu trú.');
        }

        try {
            [$oldCheckOutAt, $newCheckOutAt] = $this->getExtendStayTimesFromRequest($request, $booking);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $booking->load([
            'bookingRooms.room.category',
            'roomCategory',
            'customer',
            'serviceItems',
        ]);

        try {
            $analysis = $this->analyzeExtendStay($booking, $oldCheckOutAt, $newCheckOutAt);
        } catch (\Throwable $e) {
            return back()->with('error', 'Không thể gia hạn: ' . $e->getMessage());
        }

        if ($analysis['status'] === 'blocked') {
            return back()->with('error', $analysis['message']);
        }

        $repricingPreview = $this->previewOvernightExtensionRepricing(
            $booking,
            $oldCheckOutAt,
            $newCheckOutAt
        );
        $isOvernightNightExtension = $repricingPreview !== null;

        DB::beginTransaction();

        try {
            $extraRoomTotal = $analysis['fee_amount'];
            $extendPolicyText = $analysis['policy_text'];
            $roomChangeMessages = [];

            foreach ($analysis['replacement_plans'] as $plan) {
                $bookingRoom = $plan['booking_room'];
                $oldRoom = $plan['old_room'];
                $newRoom = $plan['new_room'];

                $bookingRoom->update([
                    'room_id' => $newRoom->id,
                    'surcharge_reason' => trim(
                        ($bookingRoom->surcharge_reason ? $bookingRoom->surcharge_reason . ' | ' : '')
                        . 'Chuyển từ phòng '
                        . $oldRoom->room_number
                        . ' sang phòng '
                        . $newRoom->room_number
                        . ' để gia hạn do phòng cũ có booking kế tiếp.'
                    ),
                ]);

                $oldRoom->update([
                    'status' => 'inspection',
                    'status_from' => now('Asia/Ho_Chi_Minh'),
                    'status_until' => null,
                ]);

                $newRoom->update([
                    'status' => 'occupied',
                ]);

                $roomChangeMessages[] = 'Chuyển phòng '
                    . $oldRoom->room_number
                    . ' → '
                    . $newRoom->room_number
                    . ' cùng hạng '
                    . ($newRoom->category->name ?? $oldRoom->category->name ?? '')
                    . '.';
            }

            // Gia hạn thêm đêm được tính lại trực tiếp vào tiền phòng và các dịch vụ
            // theo đêm. Chỉ gia hạn theo giờ/check-out muộn mới tạo một dòng phụ thu.
            if ($extraRoomTotal > 0 && !$isOvernightNightExtension) {
                $extendStayService = Service::firstOrCreate(
                    [
                        'name' => 'Phụ thu gia hạn lưu trú',
                        'type' => 'extension_fee',
                    ],
                    [
                        'service_group' => 'other',
                        'price' => 0,
                        'unit' => 'lần',
                        'billing_rule' => Service::BILLING_ONCE,
                        'description' => 'Phụ thu khi khách gia hạn thêm giờ hoặc check-out muộn.',
                        'status' => 'active',
                    ]
                );

                BookingServiceItem::create([
                    'booking_id' => $booking->id,
                    'service_id' => $extendStayService->id,
                    'name' => 'Phụ thu gia hạn lưu trú',
                    'type' => 'extension_fee',
                    'billing_rule_snapshot' => Service::BILLING_ONCE,
                    'unit_price' => $extraRoomTotal,
                    'base_quantity' => 1,
                    'quantity' => 1,
                    'used_quantity' => 1,
                    'nights_snapshot' => 1,
                    'rooms_snapshot' => max(1, (int) $booking->room_quantity),
                    'people_snapshot' => max(1, (int) $booking->adult_count + (int) $booking->child_count),
                    'billing_status' => 'confirmed',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => now(),
                    'confirm_note' => $extendPolicyText,
                    'total' => $extraRoomTotal,
                    'note' => 'Gia hạn từ ' . $oldCheckOutAt->format('d/m/Y H:i')
                        . ' đến ' . $newCheckOutAt->format('d/m/Y H:i')
                        . '. ' . $extendPolicyText,
                ]);
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $roomChangeText = count($roomChangeMessages) > 0
                ? ' ' . implode(' ', $roomChangeMessages)
                : '';

            $booking->check_out_date = $newCheckOutAt->toDateString();
            $booking->check_out_at = $newCheckOutAt;
            if (!$isOvernightNightExtension) {
                $booking->subtotal_amount = (float) $booking->subtotal_amount + $extraRoomTotal;
                $booking->estimated_total = (float) $booking->estimated_total + $extraRoomTotal;
            }
            $booking->note = $oldNote
                . now()->format('d/m/Y H:i')
                . ' - Gia hạn lưu trú từ '
                . $oldCheckOutAt->format('d/m/Y H:i')
                . ' đến '
                . $newCheckOutAt->format('d/m/Y H:i')
                . '. '
                . $extendPolicyText
                . $roomChangeText
                . ' Phụ thu: '
                . number_format($extraRoomTotal, 0, ',', '.')
                . 'đ.';

            $booking->save();

            $financialText = '';
            if ($isOvernightNightExtension && $repricingPreview) {
                app(BookingRepricingService::class)->apply($booking, $repricingPreview);
                $booking->refresh();

                $serviceChanges = collect($repricingPreview['service_preview']['lines'] ?? [])
                    ->filter(fn (array $item) => !empty($item['will_reprice']) || !empty($item['will_remove']))
                    ->map(fn (array $item) => ($item['name'] ?? 'Dịch vụ')
                        . ': ' . number_format((float) ($item['old_total'] ?? 0), 0, ',', '.')
                        . 'đ → ' . number_format((float) ($item['new_total'] ?? 0), 0, ',', '.') . 'đ')
                    ->implode('; ');
                $removedPromotions = collect($repricingPreview['promotion_preview']['removed'] ?? [])
                    ->map(fn (array $item) => ($item['code'] ?? '---') . ': ' . ($item['reason'] ?? 'không còn đủ điều kiện'))
                    ->implode('; ');

                $financialText = ' Tổng đơn: '
                    . number_format((float) $repricingPreview['old']['total'], 0, ',', '.')
                    . 'đ → '
                    . number_format((float) $repricingPreview['new']['total'], 0, ',', '.')
                    . 'đ. Còn phải thu: '
                    . number_format((float) $repricingPreview['new']['remaining'], 0, ',', '.')
                    . 'đ. Khách đang trả dư: '
                    . number_format((float) $repricingPreview['new']['overpayment'], 0, ',', '.')
                    . 'đ.'
                    . ($serviceChanges !== '' ? ' Dịch vụ tính lại: ' . $serviceChanges . '.' : '')
                    . ($removedPromotions !== '' ? ' Mã bị gỡ: ' . $removedPromotions . '.' : '');
            } else {
                // Nếu vừa tạo dòng phụ thu gia hạn, bỏ cache quan hệ để phép tính tài chính
                // đọc đúng dịch vụ mới từ database.
                $booking->unsetRelation('serviceItems');
                app(BookingFinancialService::class)->refreshPaymentStatus($booking);
                $booking->refresh();
            }

            $this->addBookingLog(
                $booking,
                'extend_stay',
                'Gia hạn lưu trú từ '
                . $oldCheckOutAt->format('d/m/Y H:i')
                . ' đến '
                . $newCheckOutAt->format('d/m/Y H:i')
                . '. '
                . $extendPolicyText
                . $roomChangeText
                . ' Phần tiền phòng/phụ thu tăng: '
                . number_format($extraRoomTotal, 0, ',', '.')
                . 'đ.'
                . $financialText
            );

            DB::commit();

            $successMessage = 'Gia hạn lưu trú thành công. '
                . $extendPolicyText
                . ' Phụ thu: '
                . number_format($extraRoomTotal, 0, ',', '.')
                . 'đ.';

            if (count($roomChangeMessages) > 0) {
                $successMessage .= ' Hệ thống đã chuyển phòng cùng hạng: ' . implode(' ', $roomChangeMessages);
            }

            return back()->with('success', $successMessage);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi gia hạn lưu trú: ' . $e->getMessage());
        }
    }

    private function previewOvernightExtensionRepricing(
        Booking $booking,
        \Carbon\Carbon $oldCheckOutAt,
        \Carbon\Carbon $newCheckOutAt
    ): ?array {
        if (
            $booking->booking_type === 'hourly'
            || $newCheckOutAt->toDateString() === $oldCheckOutAt->toDateString()
        ) {
            return null;
        }

        $booking->loadMissing([
            'bookingRooms',
            'serviceItems.service',
            'bookingPromotions.promotion.serviceOffers.service',
            'bookingPromotions.promotion.roomUpgradeOffers',
            'bookingPromotions.serviceOffers',
            'bookingPromotions.roomUpgradeOffers.offer',
            'payments',
            'customer',
        ]);

        $oneNightRoomTotal = (float) $booking->bookingRooms->sum(
            fn ($bookingRoom) => (float) $bookingRoom->price_at_booking
        );
        if ($oneNightRoomTotal <= 0) {
            $oneNightRoomTotal = (float) ($booking->roomCategory->price ?? 0)
                * max(1, (int) $booking->room_quantity);
        }

        return app(BookingRepricingService::class)->preview(
            $booking,
            \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh'),
            $newCheckOutAt,
            $oneNightRoomTotal,
            null
        );
    }

    private function getExtendStayTimesFromRequest(Request $request, Booking $booking): array
    {
        $data = $request->validate([
            'new_check_out_date' => 'required|date',
            'new_check_out_time' => 'required|date_format:H:i',
        ], [
            'new_check_out_date.required' => 'Vui lòng chọn ngày trả phòng mới.',
            'new_check_out_date.date' => 'Ngày trả phòng mới không hợp lệ.',
            'new_check_out_time.required' => 'Vui lòng chọn giờ trả phòng mới.',
            'new_check_out_time.date_format' => 'Giờ trả phòng mới phải theo định dạng 24 giờ, ví dụ 14:00 hoặc 17:30.',
        ]);

        if (!$booking->check_out_at) {
            throw new \Exception('Booking chưa có thời gian trả phòng hiện tại nên không thể gia hạn.');
        }

        $oldCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');
        $newCheckOutAt = \Carbon\Carbon::parse(
            $data['new_check_out_date'] . ' ' . $data['new_check_out_time'] . ':00',
            'Asia/Ho_Chi_Minh'
        );

        if ($newCheckOutAt->toDateString() < $oldCheckOutAt->toDateString()) {
            throw new \Exception(
                'Không thể gia hạn. Ngày trả phòng mới không được trước ngày trả phòng hiện tại '
                . $oldCheckOutAt->format('d/m/Y') . '.'
            );
        }

        if ($newCheckOutAt->lessThanOrEqualTo($oldCheckOutAt)) {
            throw new \Exception(
                'Không thể gia hạn. Nếu giữ nguyên ngày '
                . $oldCheckOutAt->format('d/m/Y')
                . ', giờ trả phòng mới phải sau '
                . $oldCheckOutAt->format('H:i')
                . '. Hoặc hãy chọn một ngày trả phòng muộn hơn.'
            );
        }

        return [$oldCheckOutAt, $newCheckOutAt];
    }

    private function analyzeExtendStay(Booking $booking, \Carbon\Carbon $oldCheckOutAt, \Carbon\Carbon $newCheckOutAt): array
    {
        if ($booking->bookingRooms->count() == 0) {
            throw new \Exception('Booking này chưa có phòng nên không thể gia hạn.');
        }

        $feeResult = $this->calculateExtendStayFee($booking, $oldCheckOutAt, $newCheckOutAt);
        $replacementPlans = [];
        $usedReplacementRoomIds = [];
        $conflictMessages = [];
        $conflictsForView = [];
        $replacementsForView = [];
        $blockedMessages = [];

        $newCheckOutAtWithCleaning = $newCheckOutAt
            ->copy()
            ->addMinutes($booking->cleaning_buffer_minutes ?? 0);

        foreach ($booking->bookingRooms as $bookingRoom) {
            if (!$bookingRoom->room) {
                throw new \Exception('Có phòng trong booking không còn tồn tại. Vui lòng kiểm tra lại dữ liệu gán phòng.');
            }

            $conflictBookingRoom = $this->findConflictBookingRoom(
                $bookingRoom->room_id,
                $booking->id,
                $oldCheckOutAt,
                $newCheckOutAtWithCleaning
            );

            if (!$conflictBookingRoom) {
                continue;
            }

            $conflictBooking = $conflictBookingRoom->booking;
            $oldRoom = $bookingRoom->room;
            $roomNumber = $oldRoom->room_number ?? ('ID ' . $bookingRoom->room_id);
            $categoryId = (int) ($oldRoom->room_category_id ?? $booking->room_category_id);
            $categoryName = $oldRoom->category->name ?? $booking->roomCategory->name ?? 'không xác định';

            $conflictText = 'Phòng ' . $roomNumber
                . ' đã có booking '
                . ($conflictBooking->booking_code ?? '')
                . ' của ' . $this->getCustomerNameFromBooking($conflictBooking)
                . ' từ '
                . \Carbon\Carbon::parse($conflictBooking->check_in_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                . ' đến '
                . \Carbon\Carbon::parse($conflictBooking->check_out_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                . '.';

            $conflictMessages[] = $conflictText;
            $conflictsForView[] = [
                'room_number' => $roomNumber,
                'category_name' => $categoryName,
                'booking_code' => $conflictBooking->booking_code ?? '',
                'customer_name' => $this->getCustomerNameFromBooking($conflictBooking),
                'check_in_text' => \Carbon\Carbon::parse($conflictBooking->check_in_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                'check_out_text' => \Carbon\Carbon::parse($conflictBooking->check_out_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
            ];

            $replacementRoom = $this->findSameCategoryReplacementRoom(
                $categoryId,
                $booking->id,
                $oldCheckOutAt,
                $newCheckOutAtWithCleaning,
                array_merge(
                    $booking->bookingRooms->pluck('room_id')->toArray(),
                    $usedReplacementRoomIds
                )
            );

            if (!$replacementRoom) {
                $blockedMessages[] = 'Khung giờ ' . $oldCheckOutAt->format('d/m/Y H:i')
                    . ' → ' . $newCheckOutAt->format('d/m/Y H:i')
                    . ' của phòng ' . $roomNumber
                    . ' đã có người đặt. Hiện tại không còn phòng trống cùng hạng '
                    . $categoryName
                    . ' để đổi cho khách.';
                continue;
            }

            $replacementPlans[] = [
                'booking_room' => $bookingRoom,
                'old_room' => $oldRoom,
                'new_room' => $replacementRoom,
                'conflict_booking' => $conflictBooking,
            ];

            $usedReplacementRoomIds[] = $replacementRoom->id;
            $replacementsForView[] = [
                'old_room_number' => $roomNumber,
                'new_room_number' => $replacementRoom->room_number,
                'category_name' => $replacementRoom->category->name ?? $categoryName,
            ];
        }

        if (count($blockedMessages) > 0) {
            return [
                'status' => 'blocked',
                'title' => 'Không thể gia hạn',
                'message' => implode(' ', $blockedMessages) . ' Khách cần trả phòng đúng hạn hoặc tạo booking mới ở phòng/hạng khác.',
                'fee_amount' => $feeResult['amount'],
                'policy_text' => $feeResult['policy_text'],
                'replacement_plans' => [],
                'conflicts_for_view' => $conflictsForView,
                'replacements_for_view' => $replacementsForView,
            ];
        }

        if (count($replacementPlans) > 0) {
            return [
                'status' => 'need_room_change',
                'title' => 'Có thể gia hạn nhưng phải chuyển phòng',
                'message' => implode(' ', $conflictMessages) . ' Hệ thống tìm được phòng cùng hạng còn trống để đổi trước khi gia hạn.',
                'fee_amount' => $feeResult['amount'],
                'policy_text' => $feeResult['policy_text'],
                'replacement_plans' => $replacementPlans,
                'conflicts_for_view' => $conflictsForView,
                'replacements_for_view' => $replacementsForView,
            ];
        }

        return [
            'status' => 'available',
            'title' => 'Có thể gia hạn',
            'message' => 'Không có booking nào giao thời gian trong khung giờ gia hạn. Có thể gia hạn trên phòng hiện tại.',
            'fee_amount' => $feeResult['amount'],
            'policy_text' => $feeResult['policy_text'],
            'replacement_plans' => [],
            'conflicts_for_view' => [],
            'replacements_for_view' => [],
        ];
    }

    private function findConflictBookingRoom(
        int $roomId,
        int $currentBookingId,
        \Carbon\Carbon $from,
        \Carbon\Carbon $to
    ) {
        return BookingRoom::where('room_id', $roomId)
            ->where('booking_id', '!=', $currentBookingId)
            ->whereHas('booking', function ($query) use ($from, $to) {
                $query->whereIn('status', [
                    'pending',
                    'confirmed',
                    'checked_in',
                    'inspection_requested',
                ])
                    ->where('check_in_at', '<', $to)
                    ->where('check_out_at', '>', $from);
            })
            ->with(['booking.customer', 'room'])
            ->orderBy(
                Booking::select('check_in_at')
                    ->whereColumn('bookings.id', 'booking_rooms.booking_id')
                    ->limit(1)
            )
            ->first();
    }

    private function findSameCategoryReplacementRoom(
        int $roomCategoryId,
        int $currentBookingId,
        \Carbon\Carbon $from,
        \Carbon\Carbon $to,
        array $excludeRoomIds = []
    ) {
        return Room::where('room_category_id', $roomCategoryId)
            ->when(count($excludeRoomIds) > 0, function ($query) use ($excludeRoomIds) {
                $query->whereNotIn('id', $excludeRoomIds);
            })
            ->availableForPeriod($from, $to, $currentBookingId)
            ->with('category')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->first();
    }

    private function calculateExtendStayFee(Booking $booking, \Carbon\Carbon $oldCheckOutAt, \Carbon\Carbon $newCheckOutAt): array
    {
        $oneNightTotal = $booking->bookingRooms->sum(function ($bookingRoom) {
            return (float) $bookingRoom->price_at_booking;
        });

        if ($oneNightTotal <= 0) {
            $oneNightTotal = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
        }

        $extraMinutes = max(1, $oldCheckOutAt->diffInMinutes($newCheckOutAt));
        $extraHours = round($extraMinutes / 60, 2);

        if ($booking->booking_type === 'hourly') {
            $currentMinutes = max(60, \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->diffInMinutes($oldCheckOutAt));
            $currentHours = max(1, $currentMinutes / 60);

            $confirmedServiceTotal = $booking->serviceItems->sum(function ($item) {
                return (float) $item->total;
            });

            $currentRoomTotal = max(0, (float) $booking->estimated_total - $confirmedServiceTotal);

            if ($currentRoomTotal <= 0) {
                $currentRoomTotal = $oneNightTotal;
            }

            $hourlyRate = $currentRoomTotal / $currentHours;
            $extraRoomTotal = round($hourlyRate * $extraHours, 0);

            return [
                'amount' => $extraRoomTotal,
                'policy_text' => 'Booking theo giờ, gia hạn thêm '
                    . $extraHours
                    . ' giờ. Đơn giá tạm tính theo giá giờ hiện tại: '
                    . number_format($hourlyRate, 0, ',', '.')
                    . 'đ/giờ.',
            ];
        }

        if ($oldCheckOutAt->toDateString() === $newCheckOutAt->toDateString()) {
            $latePolicy = app(StayPricingPolicyService::class)->lateCheckOut($newCheckOutAt, $oneNightTotal);
            $extraRoomTotal = $latePolicy['amount'];
            $extendPolicyText = $latePolicy['policy_text'];
        } else {
            $extraNights = max(
                1,
                $oldCheckOutAt->copy()->startOfDay()->diffInDays($newCheckOutAt->copy()->startOfDay())
            );

            $extraRoomTotal = $oneNightTotal * $extraNights;
            $extendPolicyText = 'Booking qua đêm, gia hạn thêm ' . $extraNights . ' đêm.';
        }

        return [
            'amount' => round($extraRoomTotal, 0),
            'policy_text' => $extendPolicyText,
        ];
    }

    private function getCustomerNameFromBooking(?Booking $booking): string
    {
        if (!$booking || !$booking->customer) {
            return 'khách mới';
        }

        $customerName = trim(
            ($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? '')
        );

        return $customerName !== '' ? $customerName : 'khách mới';
    }

    public function addRoomToBooking(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'Chỉ booking đã xác nhận hoặc đang ở mới được thêm phòng.');
        }

        if ($booking->roomInspections()->whereIn('status', ['pending', 'submitted'])->exists()) {
            return back()->with('error', 'Booking đã yêu cầu kiểm tra phòng, không thể thêm phòng nữa.');
        }

        $data = $request->validate([
            'additional_room_category_id' => 'required|exists:room_categories,id',
            'additional_room_quantity' => 'required|integer|min:1',
            'prefer_near_current_rooms' => 'nullable|boolean',
            'add_room_reason' => 'nullable|string|max:1000',
            'confirm_operation' => 'nullable|boolean',
        ]);

        $booking->load('bookingRooms.room.category', 'roomCategory');

        if (!$request->boolean('confirm_operation')) {
            return $this->previewBookingRoomOperation(
                $booking,
                'add_room',
                $data,
                fn () => $this->handleAddRoomToBooking($booking, $data, null, null),
                route('admin.bookings.add-room-to-booking', $booking->id)
            );
        }

        DB::beginTransaction();

        try {
            $message = $this->handleAddRoomToBooking($booking, $data, null, null);

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $booking->note = $oldNote . now()->format('d/m/Y H:i') . ' - ' . $message;
            $booking->save();

            $this->addBookingLog($booking, 'add_room_to_booking', $message);

            DB::commit();

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể thêm phòng: ' . $e->getMessage());
        }
    }

    public function changeOneRoomCategory(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'Chỉ booking đã xác nhận hoặc đang ở mới được đổi hạng 1 phòng.');
        }

        if ($booking->roomInspections()->whereIn('status', ['pending', 'submitted'])->exists()) {
            return back()->with('error', 'Booking đã yêu cầu kiểm tra phòng, không thể đổi hạng phòng nữa.');
        }

        $data = $request->validate([
            'booking_room_id' => 'required|exists:booking_rooms,id',
            'new_room_category_id' => 'required|exists:room_categories,id',
            'change_category_reason' => 'nullable|string|max:1000',
            'confirm_operation' => 'nullable|boolean',
        ]);

        $booking->load('bookingRooms.room.category', 'roomCategory');

        if (!$request->boolean('confirm_operation')) {
            return $this->previewBookingRoomOperation(
                $booking,
                'change_one_room',
                $data,
                fn () => $this->handleChangeOneRoomCategory($booking, $data),
                route('admin.bookings.change-one-room-category', $booking->id)
            );
        }

        DB::beginTransaction();

        try {
            $message = $this->handleChangeOneRoomCategory($booking, $data);

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $booking->note = $oldNote . now()->format('d/m/Y H:i') . ' - ' . $message;
            $booking->save();

            $this->addBookingLog($booking, 'change_one_room_category', $message);

            DB::commit();

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể đổi hạng 1 phòng: ' . $e->getMessage());
        }
    }

    public function changeAllRoomCategory(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['confirmed', 'checked_in'])) {
            return back()->with('error', 'Chỉ booking đã xác nhận hoặc đang ở mới được đổi toàn bộ hạng phòng.');
        }

        if ($booking->roomInspections()->whereIn('status', ['pending', 'submitted'])->exists()) {
            return back()->with('error', 'Booking đã yêu cầu kiểm tra phòng, không thể đổi toàn bộ hạng phòng nữa.');
        }

        $data = $request->validate([
            'new_room_category_id' => 'required|exists:room_categories,id',
            'change_category_reason' => 'nullable|string|max:1000',
            'confirm_operation' => 'nullable|boolean',
        ]);

        $booking->load('bookingRooms.room.category', 'roomCategory');

        if (!$request->boolean('confirm_operation')) {
            return $this->previewBookingRoomOperation(
                $booking,
                'change_all_rooms',
                $data,
                fn () => $this->handleChangeRoomCategory($booking, $data, null, null),
                route('admin.bookings.change-all-room-category', $booking->id)
            );
        }

        DB::beginTransaction();

        try {
            $message = $this->handleChangeRoomCategory($booking, $data, null, null);

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $booking->note = $oldNote . now()->format('d/m/Y H:i') . ' - ' . $message;
            $booking->save();

            $this->addBookingLog($booking, 'change_all_room_category', $message);

            DB::commit();

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể đổi toàn bộ hạng phòng: ' . $e->getMessage());
        }
    }

    private function handleExtraGuestFees(Booking $booking, array $data)
    {
        $serviceIds = $data['extra_service_ids'] ?? [];
        $bookingRoomIds = $data['extra_booking_room_ids'] ?? [];
        $guestTypes = $data['extra_guest_types'] ?? [];
        $quantities = $data['extra_quantities'] ?? [];
        $notes = $data['extra_fee_notes'] ?? [];

        $booking->loadMissing(['bookingRooms.room.category', 'guests']);

        $capacityMap = [];
        foreach ($booking->bookingRooms as $bookingRoom) {
            $roomGuests = $booking->guests->where('booking_room_id', $bookingRoom->id);
            $adultCount = $roomGuests->where('guest_type', 'adult')->count();
            $minorCount = $roomGuests->whereIn('guest_type', ['child', 'infant'])->count();
            $capacityMap[(int) $bookingRoom->id] = [
                'booking_room' => $bookingRoom,
                'adult' => max(0, $adultCount - (int) ($bookingRoom->room?->category?->adult_capacity ?? 0)),
                'minor' => max(0, $minorCount - (int) ($bookingRoom->room?->category?->child_capacity ?? 0)),
            ];
        }

        $validRows = [];
        $covered = [];

        foreach ($serviceIds as $index => $serviceId) {
            if (empty($serviceId)) {
                continue;
            }

            $bookingRoomId = (int) ($bookingRoomIds[$index] ?? 0);
            $guestType = (string) ($guestTypes[$index] ?? '');
            $quantity = max(1, (int) ($quantities[$index] ?? 1));

            if (!isset($capacityMap[$bookingRoomId])) {
                throw new \Exception('Vui lòng chọn đúng phòng đang vượt sức chứa cho từng khoản phụ thu.');
            }

            if (!in_array($guestType, ['adult', 'minor'], true)) {
                throw new \Exception('Vui lòng chọn loại khách vượt sức chứa cho từng khoản phụ thu.');
            }

            if ($capacityMap[$bookingRoomId][$guestType] <= 0) {
                $roomNumber = $capacityMap[$bookingRoomId]['booking_room']->room?->room_number ?? '---';
                throw new \Exception('Phòng ' . $roomNumber . ' không vượt sức chứa theo loại khách đã chọn.');
            }

            $covered[$bookingRoomId][$guestType] = ($covered[$bookingRoomId][$guestType] ?? 0) + $quantity;
            $validRows[] = compact('index', 'serviceId', 'bookingRoomId', 'guestType', 'quantity');
        }

        if ($validRows === []) {
            throw new \Exception('Vui lòng chọn ít nhất một khoản phụ thu cho phòng đang vượt sức chứa.');
        }

        foreach ($capacityMap as $bookingRoomId => $state) {
            foreach (['adult', 'minor'] as $guestType) {
                $required = (int) $state[$guestType];
                $selected = (int) ($covered[$bookingRoomId][$guestType] ?? 0);
                if ($required !== $selected) {
                    $roomNumber = $state['booking_room']->room?->room_number ?? '---';
                    $label = $guestType === 'adult' ? 'người lớn' : 'trẻ em/em bé';
                    throw new \Exception(
                        'Phòng ' . $roomNumber . ' đang vượt ' . $required . ' ' . $label
                        . ' nhưng phụ thu đang khai cho ' . $selected . '. Vui lòng nhập đúng số lượng vượt.'
                    );
                }
            }
        }

        $totalExtraFee = 0;
        $feeDescriptions = [];

        foreach ($validRows as $row) {
            $service = Service::whereKey($row['serviceId'])
                ->where('type', 'occupancy_fee')
                ->where('status', 'active')
                ->first();

            if (!$service) {
                throw new \Exception('Có khoản phụ thu không hợp lệ hoặc đã bị ẩn.');
            }

            $bookingRoom = $capacityMap[$row['bookingRoomId']]['booking_room'];
            $unitPrice = (float) $service->price;
            $total = $unitPrice * $row['quantity'];
            $typeLabel = $row['guestType'] === 'adult' ? 'người lớn' : 'trẻ em/em bé';
            $roomNumber = $bookingRoom->room?->room_number ?? '---';
            $userNote = trim((string) ($notes[$row['index']] ?? ''));
            $systemMarker = '[capacity_type:' . $row['guestType'] . ']';

            BookingServiceItem::create([
                'booking_id' => $booking->id,
                'scope' => 'room',
                'booking_room_id' => $bookingRoom->id,
                'room_id_snapshot' => $bookingRoom->room_id,
                'source_type' => 'checkin_capacity_fee',
                'service_id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'billing_rule_snapshot' => 'once',
                'unit_price' => $unitPrice,
                'base_quantity' => $row['quantity'],
                'nights_snapshot' => 1,
                'rooms_snapshot' => 1,
                'people_snapshot' => $row['quantity'],
                'quantity' => $row['quantity'],
                'billing_status' => 'confirmed',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
                'total' => $total,
                'note' => $systemMarker . ' Phụ thu vượt sức chứa phòng ' . $roomNumber
                    . ' cho ' . $typeLabel . ($userNote !== '' ? '. ' . $userNote : ''),
            ]);

            $totalExtraFee += $total;
            $feeDescriptions[] = 'phòng ' . $roomNumber . ' - ' . $service->name
                . ' x ' . $row['quantity'] . ': ' . number_format($total, 0, ',', '.') . 'đ';
        }

        return 'Đã ghi phụ phí vượt sức chứa theo từng phòng: '
            . implode('; ', $feeDescriptions)
            . '. Tổng phụ thu: ' . number_format($totalExtraFee, 0, ',', '.') . 'đ.';
    }

    private function handleAddRoomToBooking(
        Booking $booking,
        array $data,
        ?int $actualAdultCount = null,
        ?int $actualChildCount = null
    ) {
        if (empty($data['additional_room_category_id'])) {
            throw new \Exception('Vui lòng chọn hạng phòng cần thêm.');
        }

        $quantity = (int) ($data['additional_room_quantity'] ?? 1);

        $category = RoomCategory::where('status', 'active')
            ->find($data['additional_room_category_id']);

        if (!$category) {
            throw new \Exception('Hạng phòng cần thêm không hợp lệ.');
        }

        $currentAdultCapacity = $booking->bookingRooms->reduce(function ($total, $bookingRoom) {
            return $total + ($bookingRoom->room->category->adult_capacity ?? 0);
        }, 0);

        $currentChildCapacity = $booking->bookingRooms->reduce(function ($total, $bookingRoom) {
            return $total + ($bookingRoom->room->category->child_capacity ?? 0);
        }, 0);

        $newAdultCapacity = $currentAdultCapacity + ($category->adult_capacity * $quantity);
        $newChildCapacity = $currentChildCapacity + ($category->child_capacity * $quantity);

        if (
            $actualAdultCount !== null
            && $actualChildCount !== null
            && ($actualAdultCount > $newAdultCapacity || $actualChildCount > $newChildCapacity)
        ) {
            throw new \Exception('Số phòng thêm vẫn chưa đủ sức chứa cho số khách thực tế.');
        }

        $rooms = $this->findAvailableRoomsForCheckIn(
            $category->id,
            $quantity,
            $booking->check_in_at,
            $booking->check_out_at,
            $data['prefer_near_current_rooms'] ?? false,
            $booking
        );

        if ($rooms->count() < $quantity) {
            throw new \Exception('Không còn đủ phòng trống thuộc hạng phòng đã chọn.');
        }

        $nightCount = $this->getNightCount($booking);

        foreach ($rooms as $room) {
            // Chặn phòng trùng ở tầng ghi dữ liệu, kể cả khi cache quan hệ hoặc
            // truy vấn chọn phòng bị sai trong tương lai.
            $alreadyAssigned = BookingRoom::query()
                ->where('booking_id', $booking->id)
                ->where('room_id', $room->id)
                ->exists();

            if ($alreadyAssigned) {
                throw new \Exception(
                    'Phòng ' . ($room->room_number ?? $room->id)
                    . ' đã có trong booking, không thể thêm trùng.'
                );
            }

            BookingRoom::create([
                'booking_id' => $booking->id,
                'room_id' => $room->id,
                'adult_count' => 0,
                'child_count' => 0,
                'price_at_booking' => $category->price,
                'surcharge' => 0,
                'surcharge_reason' => $data['add_room_reason'] ?? 'Thêm phòng khi check-in do vượt sức chứa.',
                'created_at' => now(),
            ]);
            // Phòng đang dọn/chờ kiểm tra vẫn có thể được giữ nếu không giao lịch,
            // nhưng phải giữ nguyên trạng thái vận hành để buồng phòng thấy việc cần làm.
            app(\App\Services\RoomPreparationService::class)
                ->flagPriorityIfNeeded($booking, $room, 'lễ tân thêm phòng vào booking');

            if (!in_array($room->status, ['cleaning', 'inspection'], true)) {
                $room->update([
                    'status' => 'reserved',
                    'status_from' => now('Asia/Ho_Chi_Minh'),
                ]);
            }

        }

        $booking->room_quantity += $quantity;
        $booking->save();
        $this->repriceCurrentBooking($booking);

        return 'Đã thêm '
            . $quantity
            . ' phòng hạng '
            . $category->name
            . ' vào booking. Lý do: '
            . ($data['add_room_reason'] ?? 'Khách phát sinh nhu cầu thêm phòng.');
    }

    private function handleChangeRoomCategory(
        Booking $booking,
        array $data,
        ?int $actualAdultCount = null,
        ?int $actualChildCount = null
    ) {
        if (empty($data['new_room_category_id'])) {
            throw new \Exception('Vui lòng chọn hạng phòng mới.');
        }

        $newCategory = RoomCategory::where('status', 'active')->find($data['new_room_category_id']);
        if (!$newCategory) {
            throw new \Exception('Hạng phòng mới không hợp lệ.');
        }

        $booking->loadMissing('bookingRooms.room.category');
        $roomQuantity = max(1, $booking->bookingRooms->count());
        $newAdultCapacity = $newCategory->adult_capacity * $roomQuantity;
        $newChildCapacity = $newCategory->child_capacity * $roomQuantity;

        if ($actualAdultCount !== null && $actualChildCount !== null
            && ($actualAdultCount > $newAdultCapacity || $actualChildCount > $newChildCapacity)) {
            throw new \Exception('Hạng phòng mới vẫn không đủ sức chứa. Vui lòng chọn hạng khác hoặc thêm phòng.');
        }

        $newRooms = $this->findAvailableRoomsForCheckIn(
            $newCategory->id,
            $roomQuantity,
            $booking->check_in_at,
            $booking->check_out_at,
            false,
            $booking
        );
        if ($newRooms->count() < $roomQuantity) {
            throw new \Exception('Không còn đủ phòng trống thuộc hạng phòng mới trong thời gian booking.');
        }

        $nightCount = $this->getNightCount($booking);
        $oldBookingRooms = $booking->bookingRooms->sortBy('id')->values();
        $newRooms = $newRooms->values();
        $difference = 0;
        $changeDescriptions = [];

        foreach ($oldBookingRooms as $index => $bookingRoom) {
            $oldRoom = $bookingRoom->room;
            $newRoom = $newRooms->get($index);
            if (!$oldRoom || !$newRoom) {
                throw new \Exception('Không thể ghép đủ phòng cũ và phòng mới.');
            }

            $oldCategory = $oldRoom->category;
            $oldPrice = (float) $bookingRoom->price_at_booking;
            $newPrice = (float) $newCategory->price;
            $roomDifference = ($newPrice - $oldPrice) * $nightCount;
            $difference += $roomDifference;

            $bookingRoom->update([
                'room_id' => $newRoom->id,
                'price_at_booking' => $newPrice,
                'surcharge_reason' => $data['change_category_reason'] ?? 'Lễ tân đổi toàn bộ booking sang hạng khác.',
            ]);

            $oldRoomNextStatus = $oldRoom->status === 'maintenance'
                ? 'maintenance'
                : ($booking->status === 'checked_in' ? 'cleaning' : 'available');
            $oldRoom->update([
                'status' => $oldRoomNextStatus,
                'status_from' => $oldRoomNextStatus === 'maintenance' ? ($oldRoom->status_from ?: now()) : null,
                'status_until' => null,
            ]);
            $newRoom->update(['status' => $booking->status === 'checked_in' ? 'occupied' : 'reserved']);

            BookingRoomChange::create([
                'booking_id' => $booking->id,
                'booking_room_id' => $bookingRoom->id,
                'old_room_id' => $oldRoom->id,
                'new_room_id' => $newRoom->id,
                'old_room_category_id' => $oldRoom->room_category_id,
                'new_room_category_id' => $newCategory->id,
                'old_room_price' => $oldPrice,
                'new_room_price' => $newPrice,
                'night_count' => $nightCount,
                'price_difference_total' => $roomDifference,
                'change_source' => 'front_desk',
                'reason' => $data['change_category_reason'] ?? null,
                'changed_by' => Auth::id(),
            ]);

            $changeDescriptions[] = 'phòng ' . $oldRoom->room_number . ' → ' . $newRoom->room_number;
        }

        $booking->room_category_id = $newCategory->id;
        $booking->save();
        $this->repriceCurrentBooking($booking);

        return 'Đã đổi toàn bộ sang hạng ' . $newCategory->name
            . ' (' . implode(', ', $changeDescriptions) . '). Hệ thống đã cập nhật giá phòng và ghi lịch sử nâng/đổi hạng. '
            . 'Tiền chênh: ' . number_format($difference, 0, ',', '.') . 'đ. '
            . 'Lễ tân có thể vào mục Mã ưu đãi / hỗ trợ khách để áp mã sau nếu cần. Lý do: '
            . ($data['change_category_reason'] ?? 'Khách yêu cầu đổi toàn bộ hạng phòng.');
    }

    private function handleChangeOneRoomCategory(Booking $booking, array $data)
    {
        $bookingRoom = BookingRoom::where('booking_id', $booking->id)
            ->where('id', $data['booking_room_id'])
            ->with('room.category')
            ->first();

        if (!$bookingRoom || !$bookingRoom->room) {
            throw new \Exception('Phòng cần đổi không tồn tại trong booking này.');
        }

        $oldRoom = $bookingRoom->room;
        $oldCategory = $oldRoom->category;
        $newCategory = RoomCategory::where('status', 'active')->find($data['new_room_category_id']);
        if (!$newCategory) {
            throw new \Exception('Hạng phòng mới không hợp lệ.');
        }

        $newRoom = $this->findAvailableRoomsForCheckIn(
            $newCategory->id,
            1,
            $booking->check_in_at,
            $booking->check_out_at,
            false,
            $booking
        )->first();
        if (!$newRoom) {
            throw new \Exception('Không còn phòng trống thuộc hạng phòng mới trong thời gian booking.');
        }

        $nightCount = $this->getNightCount($booking);
        $oldPrice = (float) $bookingRoom->price_at_booking;
        $newPrice = (float) $newCategory->price;
        $difference = ($newPrice - $oldPrice) * $nightCount;

        $bookingRoom->update([
            'room_id' => $newRoom->id,
            'price_at_booking' => $newPrice,
            'surcharge_reason' => $data['change_category_reason'] ?? 'Lễ tân đổi một phòng sang hạng khác.',
        ]);
        $oldRoomNextStatus = $oldRoom->status === 'maintenance'
            ? 'maintenance'
            : ($booking->status === 'checked_in' ? 'cleaning' : 'available');
        $oldRoom->update([
            'status' => $oldRoomNextStatus,
            'status_from' => $oldRoomNextStatus === 'maintenance' ? ($oldRoom->status_from ?: now()) : null,
            'status_until' => null,
        ]);
        $newRoom->update(['status' => $booking->status === 'checked_in' ? 'occupied' : 'reserved']);

        BookingRoomChange::create([
            'booking_id' => $booking->id,
            'booking_room_id' => $bookingRoom->id,
            'old_room_id' => $oldRoom->id,
            'new_room_id' => $newRoom->id,
            'old_room_category_id' => $oldRoom->room_category_id,
            'new_room_category_id' => $newCategory->id,
            'old_room_price' => $oldPrice,
            'new_room_price' => $newPrice,
            'night_count' => $nightCount,
            'price_difference_total' => $difference,
            'change_source' => 'front_desk',
            'reason' => $data['change_category_reason'] ?? null,
            'changed_by' => Auth::id(),
        ]);

        if ((int) $booking->room_quantity === 1) {
            $booking->room_category_id = $newCategory->id;
        }

        $booking->save();
        $this->repriceCurrentBooking($booking);

        return 'Đã đổi phòng ' . $oldRoom->room_number . ' (' . ($oldCategory?->name ?? '---') . ') sang phòng '
            . $newRoom->room_number . ' (' . $newCategory->name . '). Hệ thống đã cập nhật giá và ghi lịch sử nâng/đổi hạng. '
            . 'Tiền chênh: ' . number_format($difference, 0, ',', '.') . 'đ. '
            . 'Lễ tân có thể áp mã riêng ở mục Mã ưu đãi / hỗ trợ khách sau khi đổi. Lý do: '
            . ($data['change_category_reason'] ?? 'Khách yêu cầu đổi phòng.');
    }


    private function previewBookingRoomOperation(
        Booking $booking,
        string $operation,
        array $payload,
        \Closure $mutator,
        string $actionUrl
    ) {
        DB::beginTransaction();

        try {
            $before = $this->bookingRoomOperationSnapshot($booking->fresh());
            $message = $mutator();

            $afterBooking = Booking::findOrFail($booking->id);
            $after = $this->bookingRoomOperationSnapshot($afterBooking);

            $preview = [
                'operation' => $operation,
                'title' => match ($operation) {
                    'add_room' => 'Xem trước thêm phòng',
                    'change_one_room' => 'Xem trước đổi hạng 1 phòng',
                    'change_all_rooms' => 'Xem trước đổi toàn bộ hạng phòng',
                    default => 'Xem trước thay đổi phòng',
                },
                'message' => $message,
                'action_url' => $actionUrl,
                'payload' => collect($payload)
                    ->except(['confirm_operation', '_token', '_method'])
                    ->all(),
                'before' => $before,
                'after' => $after,
                'promotion_changes' => $this->comparePromotionSnapshots(
                    $before['promotions'],
                    $after['promotions']
                ),
                'service_changes' => $this->compareServiceSnapshots(
                    $before['services'],
                    $after['services']
                ),
            ];

            DB::rollBack();

            session()->put('booking_room_operation_preview', $preview);
            session()->flashInput($payload);

            return redirect(route('admin.bookings.show', $booking) . '#room-operation-preview');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Không thể xem trước thay đổi: ' . $e->getMessage());
        }
    }

    private function bookingRoomOperationSnapshot(Booking $booking): array
    {
        $booking->load([
            'bookingRooms.room.category',
            'bookingPromotions.bookingRoom.room',
            'serviceItems.bookingRoom.room',
            'roomInspections.items',
            'payments',
        ]);

        $financial = app(BookingFinancialService::class);
        $nightCount = $this->getNightCount($booking);
        $roomTotal = (float) $booking->bookingRooms->sum(
            fn (BookingRoom $room) => ((float) $room->price_at_booking * $nightCount)
                + (float) $room->surcharge
        );
        $serviceTotal = (float) $booking->serviceItems
            ->where('billing_status', 'confirmed')
            ->sum('total');
        $inspectionTotal = (float) $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->sum('total');
        $paidTotal = $financial->paidTotal($booking);
        $total = $financial->currentTotal($booking);
        $requiredDeposit = $financial->requiredDeposit($booking);

        return [
            'room_quantity' => $booking->bookingRooms->count(),
            'night_count' => $nightCount,
            'rooms' => $booking->bookingRooms->map(fn (BookingRoom $room) => [
                'booking_room_id' => (int) $room->id,
                'room_number' => $room->room?->room_number ?? '---',
                'category_name' => $room->room?->category?->name ?? '---',
                'price_per_night' => (float) $room->price_at_booking,
                'total' => ((float) $room->price_at_booking * $nightCount) + (float) $room->surcharge,
            ])->values()->all(),
            'room_total' => round($roomTotal, 0),
            'service_total' => round($serviceTotal, 0),
            'inspection_total' => round($inspectionTotal, 0),
            'discount_total' => round((float) $booking->discount_amount, 0),
            'total' => round($total, 0),
            'required_deposit' => round($requiredDeposit, 0),
            'paid_total' => round($paidTotal, 0),
            'deposit_shortfall' => max(0, round($requiredDeposit - $paidTotal, 0)),
            'remaining' => max(0, round($total - $paidTotal, 0)),
            'overpayment' => max(0, round($paidTotal - $total, 0)),
            'promotions' => $booking->bookingPromotions->map(fn ($usage) => [
                'key' => ($usage->scope ?? 'booking') . ':'
                    . ((int) ($usage->booking_room_id ?? 0)) . ':'
                    . strtoupper((string) $usage->code_snapshot),
                'code' => $usage->code_snapshot,
                'scope' => $usage->scope ?? 'booking',
                'room_number' => $usage->bookingRoom?->room?->room_number,
                'discount' => (float) $usage->discount_amount,
            ])->values()->all(),
            'services' => $booking->serviceItems
                ->where('billing_status', 'confirmed')
                ->map(fn ($item) => [
                    'id' => (int) $item->id,
                    'name' => $item->name,
                    'scope' => $item->scope ?? 'booking',
                    'room_number' => $item->bookingRoom?->room?->room_number,
                    'total' => (float) $item->total,
                ])->values()->all(),
        ];
    }

    private function comparePromotionSnapshots(array $before, array $after): array
    {
        $beforeByKey = collect($before)->keyBy('key');
        $afterByKey = collect($after)->keyBy('key');
        $changes = [];

        foreach ($beforeByKey as $key => $item) {
            if (!$afterByKey->has($key)) {
                $changes[] = array_merge($item, ['status' => 'removed', 'old_discount' => $item['discount'], 'new_discount' => 0]);
                continue;
            }

            $new = $afterByKey->get($key);
            $changes[] = array_merge($new, [
                'status' => abs((float) $new['discount'] - (float) $item['discount']) > 0.01 ? 'recalculated' : 'kept',
                'old_discount' => (float) $item['discount'],
                'new_discount' => (float) $new['discount'],
            ]);
        }

        foreach ($afterByKey as $key => $item) {
            if (!$beforeByKey->has($key)) {
                $changes[] = array_merge($item, ['status' => 'added', 'old_discount' => 0, 'new_discount' => $item['discount']]);
            }
        }

        return array_values($changes);
    }

    private function compareServiceSnapshots(array $before, array $after): array
    {
        $beforeById = collect($before)->keyBy('id');
        $afterById = collect($after)->keyBy('id');
        $changes = [];

        foreach ($beforeById as $id => $item) {
            if (!$afterById->has($id)) {
                $changes[] = array_merge($item, ['status' => 'removed', 'old_total' => $item['total'], 'new_total' => 0]);
                continue;
            }

            $new = $afterById->get($id);
            if (abs((float) $new['total'] - (float) $item['total']) > 0.01) {
                $changes[] = array_merge($new, [
                    'status' => 'recalculated',
                    'old_total' => (float) $item['total'],
                    'new_total' => (float) $new['total'],
                ]);
            }
        }

        foreach ($afterById as $id => $item) {
            if (!$beforeById->has($id)) {
                $changes[] = array_merge($item, ['status' => 'added', 'old_total' => 0, 'new_total' => $item['total']]);
            }
        }

        return array_values($changes);
    }


    /**
     * Tính lại toàn bộ booking từ các dòng phòng hiện tại. Bộ tính dùng lại cho
     * thêm phòng, đổi một phòng, đổi toàn bộ hạng và các thay đổi lịch lưu trú.
     * Mã/dịch vụ theo phòng được xét đúng booking_room_id; mã toàn booking được
     * kiểm tra lại sau khi toàn bộ giá phòng đã thay đổi.
     */
    private function repriceCurrentBooking(Booking $booking): array
    {
        app(BookingOccupancyFeeService::class)->reconcile($booking->refresh());

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

        $preview = app(BookingRepricingService::class)->preview(
            $booking,
            \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh'),
            \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh'),
            $oneNightRoomTotal
        );

        app(BookingRepricingService::class)->apply($booking, $preview);

        return $preview;
    }


    private function findAvailableRoomsForCheckIn(
        int $roomCategoryId,
        int $quantity,
        $checkInAt,
        $checkOutAt,
        bool $preferNearCurrentRooms = false,
        ?Booking $booking = null
    ) {
        $query = Room::where('room_category_id', $roomCategoryId)
            ->bookableForPeriod(
                $checkInAt,
                $checkOutAt,
                $booking?->id
            );

        // availableForPeriod bỏ qua booking hiện tại để phục vụ đổi lịch/đổi phòng,
        // vì vậy khi THÊM phòng phải loại rõ các phòng đã nằm trong booking này.
        if ($booking) {
            $assignedRoomIds = $booking->bookingRooms
                ->pluck('room_id')
                ->filter()
                ->map(fn ($roomId) => (int) $roomId)
                ->values()
                ->all();

            if ($assignedRoomIds !== []) {
                $query->whereNotIn('id', $assignedRoomIds);
            }
        }

        if ($preferNearCurrentRooms && $booking) {
            $currentFloors = $booking->bookingRooms
                ->pluck('room.floor_number')
                ->filter()
                ->unique()
                ->values();

            if ($currentFloors->count() > 0) {
                $floor = (int) $currentFloors->first();

                $query->orderByRaw('ABS(floor_number - ?) ASC', [$floor]);
            }
        }

        return $query
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->take($quantity)
            ->get();
    }

    private function getNightCount(Booking $booking)
    {
        return max(
            1,
            (strtotime($booking->check_out_date) - strtotime($booking->check_in_date)) / 86400
        );
    }


    public function changeStayDates(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'Chỉ booking chờ xác nhận hoặc đã xác nhận mới được đổi ngày lưu trú.');
        }

        if (!$booking->check_in_at || !$booking->check_out_at) {
            return back()->with('error', 'Booking chưa có đủ thời gian nhận/trả phòng nên không thể đổi ngày.');
        }

        $data = $request->validate([
            'new_check_in_date' => 'required|date',
            'new_check_in_time' => 'nullable|date_format:H:i',
            'new_check_out_date' => 'required|date',
            'new_check_out_time' => 'nullable|date_format:H:i',
            'replacement_room_category_id' => 'nullable|integer|exists:room_categories,id',
            'confirm_reprice' => 'nullable|boolean',
        ], [
            'new_check_in_date.required' => 'Vui lòng chọn ngày nhận phòng mới.',
            'new_check_in_date.date' => 'Ngày nhận phòng mới không hợp lệ.',
            'new_check_in_time.date_format' => 'Giờ nhận phòng mới phải theo định dạng 24 giờ, ví dụ 07:00 hoặc 14:00.',
            'new_check_out_date.required' => 'Vui lòng chọn ngày trả phòng mới.',
            'new_check_out_date.date' => 'Ngày trả phòng mới không hợp lệ.',
            'new_check_out_time.date_format' => 'Giờ trả phòng mới phải theo định dạng 24 giờ, ví dụ 12:00 hoặc 14:00.',
            'replacement_room_category_id.exists' => 'Hạng phòng thay thế không tồn tại.',
        ]);

        $oldCheckInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $oldCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        $newCheckInTime = $data['new_check_in_time'] ?? $oldCheckInAt->format('H:i');
        $newCheckOutTime = $data['new_check_out_time'] ?? $oldCheckOutAt->format('H:i');

        $newCheckInAt = \Carbon\Carbon::parse(
            $data['new_check_in_date'] . ' ' . $newCheckInTime . ':00',
            'Asia/Ho_Chi_Minh'
        );
        $newCheckOutAt = \Carbon\Carbon::parse(
            $data['new_check_out_date'] . ' ' . $newCheckOutTime . ':00',
            'Asia/Ho_Chi_Minh'
        );
        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');

        if ($newCheckInAt->toDateString() < $nowVn->toDateString()) {
            return back()->withInput()->with('error', 'Ngày nhận phòng mới không được trước ngày hiện tại.');
        }

        if (
            $booking->booking_type !== 'hourly'
            && $newCheckOutAt->toDateString() <= $newCheckInAt->toDateString()
        ) {
            return back()->withInput()->with(
                'error',
                'Booking qua đêm phải có ngày trả phòng mới sau ngày nhận phòng mới ít nhất 1 ngày.'
            );
        }

        // Đơn được chuyển từ ngày tương lai về hôm nay sau giờ G là khách đến sớm khác ngày,
        // không phải khách đến muộn/no-show. Ghi nhận thời điểm lễ tân đổi lịch làm mốc giữ phòng mới.
        $isRescheduledToTodayAfterCutoff = $booking->booking_type !== 'hourly'
            && $oldCheckInAt->toDateString() > $nowVn->toDateString()
            && $newCheckInAt->toDateString() === $nowVn->toDateString()
            && $nowVn->format('H:i:s') >= Booking::LATE_ARRIVAL_HOLD_TIME;

        if ($isRescheduledToTodayAfterCutoff && $newCheckInAt->lessThan($nowVn)) {
            $newCheckInAt = $nowVn->copy()->startOfMinute();
        }

        if ($newCheckOutAt->lessThanOrEqualTo($newCheckInAt)) {
            return back()->withInput()->with('error', 'Thời gian trả phòng mới phải sau thời gian nhận phòng mới.');
        }

        if ($newCheckOutAt->lessThanOrEqualTo($nowVn)) {
            return back()->withInput()->with('error', 'Thời gian trả phòng mới phải sau thời điểm hiện tại.');
        }

        $booking->load([
            'bookingRooms.room.category',
            'roomCategory',
        ]);

        if ($booking->bookingRooms->count() === 0) {
            return back()->withInput()->with(
                'error',
                'Booking này chưa được gán phòng nên không thể đổi ngày lưu trú tự động.'
            );
        }

        $oldBookingRooms = $booking->bookingRooms->sortBy('id')->values();
        $roomQuantity = $oldBookingRooms->count();
        $currentRoomIds = $oldBookingRooms->pluck('room_id')->filter()->map(fn ($id) => (int) $id)->values()->toArray();
        $cleaningBufferMinutes = (int) ($booking->cleaning_buffer_minutes ?? 0);
        $newCheckOutWithCleaning = $newCheckOutAt->copy()->addMinutes($cleaningBufferMinutes);

        $oldNightCount = max(
            1,
            $oldCheckInAt->copy()->startOfDay()->diffInDays($oldCheckOutAt->copy()->startOfDay())
        );
        $newNightCount = max(
            1,
            $newCheckInAt->copy()->startOfDay()->diffInDays($newCheckOutAt->copy()->startOfDay())
        );

        $oldOneNightRoomTotal = (float) $oldBookingRooms->sum(function ($bookingRoom) {
            return (float) $bookingRoom->price_at_booking;
        });

        if ($oldOneNightRoomTotal <= 0) {
            $oldOneNightRoomTotal = (float) ($booking->roomCategory->price ?? 0) * max(1, $roomQuantity);
        }

        $selectedCategory = null;
        $targetRoomPlan = [];
        $roomChangeMessages = [];
        $selectedCategoryId = !empty($data['replacement_room_category_id'])
            ? (int) $data['replacement_room_category_id']
            : null;

        if ($selectedCategoryId) {
            $selectedCategory = RoomCategory::where('status', 'active')->find($selectedCategoryId);

            if (!$selectedCategory) {
                return back()->withInput()->with('error', 'Hạng phòng thay thế đã bị ẩn hoặc không còn hoạt động.');
            }

            $selectedAdultCapacity = (int) $selectedCategory->adult_capacity * $roomQuantity;
            $selectedChildCapacity = (int) $selectedCategory->child_capacity * $roomQuantity;
            if (
                $selectedAdultCapacity < (int) ($booking->adult_count ?? 0)
                || $selectedChildCapacity < (int) ($booking->child_count ?? 0)
            ) {
                return $this->redirectWithStayDateCategoryOptions(
                    $request,
                    $booking,
                    $newCheckInAt,
                    $newCheckOutAt,
                    'Hạng ' . $selectedCategory->name
                        . ' không đủ sức chứa cho số khách của booking. Hệ thống đã lọc lại các hạng phù hợp.'
                );
            }

            $candidateRooms = $this->findAvailableRoomsForChangedStayDates(
                $selectedCategory->id,
                $roomQuantity,
                $newCheckInAt,
                $newCheckOutAt,
                $booking,
                []
            );

            if ($candidateRooms->count() < $roomQuantity) {
                return $this->redirectWithStayDateCategoryOptions(
                    $request,
                    $booking,
                    $newCheckInAt,
                    $newCheckOutAt,
                    'Hạng ' . $selectedCategory->name
                        . ' vừa được chọn không còn đủ '
                        . $roomQuantity
                        . ' phòng trong khung giờ mới. Hệ thống đã cập nhật lại danh sách hạng còn phòng.'
                );
            }

            foreach ($oldBookingRooms as $index => $bookingRoom) {
                $newRoom = $candidateRooms->get($index);

                if (!$newRoom) {
                    return back()->withInput()->with('error', 'Không thể ghép đủ phòng mới cho booking.');
                }

                $targetRoomPlan[] = [
                    'booking_room' => $bookingRoom,
                    'old_room' => $bookingRoom->room,
                    'new_room' => $newRoom,
                    'new_price' => (float) $selectedCategory->price,
                    'reason' => 'Đổi sang hạng '
                        . $selectedCategory->name
                        . ' để phù hợp với lịch lưu trú mới '
                        . $newCheckInAt->format('d/m/Y H:i')
                        . ' → '
                        . $newCheckOutAt->format('d/m/Y H:i')
                        . '.',
                ];
            }
        } else {
            $usedReplacementRoomIds = [];

            foreach ($oldBookingRooms as $bookingRoom) {
                $room = $bookingRoom->room;

                if (!$room) {
                    return back()->withInput()->with(
                        'error',
                        'Có phòng trong booking không còn tồn tại. Vui lòng kiểm tra lại dữ liệu gán phòng.'
                    );
                }

                $conflictBookingRoom = $this->findConflictBookingRoom(
                    $room->id,
                    $booking->id,
                    $newCheckInAt,
                    $newCheckOutWithCleaning
                );

                if (!$conflictBookingRoom) {
                    $targetRoomPlan[] = [
                        'booking_room' => $bookingRoom,
                        'old_room' => $room,
                        'new_room' => $room,
                        'new_price' => (float) $bookingRoom->price_at_booking,
                        'reason' => null,
                    ];
                    continue;
                }

                $replacementRoom = $this->findAvailableRoomsForChangedStayDates(
                    (int) $room->room_category_id,
                    1,
                    $newCheckInAt,
                    $newCheckOutAt,
                    $booking,
                    array_merge($currentRoomIds, $usedReplacementRoomIds)
                )->first();

                if (!$replacementRoom) {
                    $conflictBooking = $conflictBookingRoom->booking;
                    $conflictText = 'Phòng '
                        . ($room->room_number ?? '---')
                        . ' bị trùng với booking '
                        . ($conflictBooking->booking_code ?? '')
                        . ' từ '
                        . \Carbon\Carbon::parse($conflictBooking->check_in_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                        . ' đến '
                        . \Carbon\Carbon::parse($conflictBooking->check_out_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                        . ', đồng thời không còn đủ phòng cùng hạng để tự đổi.';

                    return $this->redirectWithStayDateCategoryOptions(
                        $request,
                        $booking,
                        $newCheckInAt,
                        $newCheckOutAt,
                        $conflictText
                    );
                }

                $usedReplacementRoomIds[] = (int) $replacementRoom->id;
                $targetRoomPlan[] = [
                    'booking_room' => $bookingRoom,
                    'old_room' => $room,
                    'new_room' => $replacementRoom,
                    'new_price' => (float) $bookingRoom->price_at_booking,
                    'reason' => 'Đổi cùng hạng do phòng cũ bị trùng lịch khi sửa ngày nhận/trả.',
                ];
            }
        }

        $newOneNightRoomTotal = $selectedCategory
            ? ((float) $selectedCategory->price * $roomQuantity)
            : (float) collect($targetRoomPlan)->sum(fn ($plan) => (float) $plan['new_price']);

        $roomDelta = round(
            ($newOneNightRoomTotal * $newNightCount)
            - ($oldOneNightRoomTotal * $oldNightCount),
            0
        );

        $repricingPreview = app(BookingRepricingService::class)->preview(
            $booking,
            $newCheckInAt,
            $newCheckOutAt,
            $newOneNightRoomTotal,
            $selectedCategory ? (float) $selectedCategory->price : null
        );

        if (!$request->boolean('confirm_reprice')) {
            session()->put('stay_date_reprice_preview', array_merge($repricingPreview, [
                    'booking_id' => (int) $booking->id,
                    'new_check_in_date' => $newCheckInAt->toDateString(),
                    'new_check_in_time' => $newCheckInAt->format('H:i'),
                    'new_check_out_date' => $newCheckOutAt->toDateString(),
                    'new_check_out_time' => $newCheckOutAt->format('H:i'),
                    'replacement_room_category_id' => $selectedCategory?->id,
                    'target_category_name' => $selectedCategory?->name ?? $booking->roomCategory?->name,
                    'room_delta' => $roomDelta,
                ]));
            session()->flashInput($request->all());

            return redirect(route('admin.bookings.show', $booking) . '#stay-date-reprice-preview');
        }

        DB::beginTransaction();

        try {
            $targetRoomIds = collect($targetRoomPlan)
                ->pluck('new_room.id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($targetRoomIds->count() !== $roomQuantity) {
                throw new \Exception('Danh sách phòng mới bị trùng. Vui lòng kiểm tra lại phương án.');
            }

            $stillAvailableRoomIds = Room::whereIn('id', $targetRoomIds->all())
                ->availableForPeriod(
                    $newCheckInAt,
                    $newCheckOutAt,
                    $booking->id,
                    $cleaningBufferMinutes
                )
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

            if ($stillAvailableRoomIds->count() !== $targetRoomIds->count()) {
                throw new \Exception(
                    'Có phòng trong phương án vừa được booking khác sử dụng. Hãy kiểm tra lại lịch để hệ thống chọn phương án mới.'
                );
            }

            foreach ($targetRoomPlan as $plan) {
                /** @var \App\Models\BookingRoom $bookingRoom */
                $bookingRoom = $plan['booking_room'];
                /** @var \App\Models\Room $oldRoom */
                $oldRoom = $plan['old_room'];
                /** @var \App\Models\Room $newRoom */
                $newRoom = $plan['new_room'];
                $newPrice = (float) $plan['new_price'];

                if ((int) $oldRoom->id === (int) $newRoom->id) {
                    if ($booking->status === 'confirmed' && $oldRoom->status === 'available') {
                        $oldRoom->update(['status' => 'reserved']);
                    }
                    continue;
                }

                $oldPrice = (float) $bookingRoom->price_at_booking;
                $oldCategoryId = (int) $oldRoom->room_category_id;
                $newCategoryId = (int) $newRoom->room_category_id;
                $changeDifference = ($newPrice * $newNightCount) - ($oldPrice * $oldNightCount);

                $bookingRoom->update([
                    'room_id' => $newRoom->id,
                    'price_at_booking' => $newPrice,
                    'surcharge_reason' => trim(
                        ($bookingRoom->surcharge_reason ? $bookingRoom->surcharge_reason . ' | ' : '')
                        . ($plan['reason'] ?? 'Đổi phòng để phù hợp với lịch lưu trú mới.')
                    ),
                ]);

                if ($oldRoom->status === 'reserved') {
                    $oldRoom->update(['status' => 'available']);
                }

                if ($booking->status === 'confirmed' && $newRoom->status === 'available') {
                    $newRoom->update(['status' => 'reserved']);
                }

                BookingRoomChange::create([
                    'booking_id' => $booking->id,
                    'booking_room_id' => $bookingRoom->id,
                    'old_room_id' => $oldRoom->id,
                    'new_room_id' => $newRoom->id,
                    'old_room_category_id' => $oldCategoryId,
                    'new_room_category_id' => $newCategoryId,
                    'old_room_price' => $oldPrice,
                    'new_room_price' => $newPrice,
                    'night_count' => $newNightCount,
                    'price_difference_total' => $changeDifference,
                    'change_source' => 'front_desk',
                    'reason' => $plan['reason'] ?? 'Đổi phòng khi sửa ngày nhận/trả.',
                    'changed_by' => Auth::id(),
                ]);

                $roomChangeMessages[] = 'Phòng '
                    . ($oldRoom->room_number ?? '---')
                    . ' → '
                    . ($newRoom->room_number ?? '---')
                    . (
                        $oldCategoryId !== $newCategoryId
                            ? ' (' . ($oldRoom->category->name ?? 'hạng cũ')
                                . ' → '
                                . ($newRoom->category->name ?? $selectedCategory?->name ?? 'hạng mới')
                                . ')'
                            : ' cùng hạng'
                    )
                    . '.';
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $roomChangeText = count($roomChangeMessages) > 0
                ? ' ' . implode(' ', $roomChangeMessages)
                : '';

            $booking->check_in_date = $newCheckInAt->toDateString();
            $booking->check_out_date = $newCheckOutAt->toDateString();
            $booking->check_in_at = $newCheckInAt;
            $booking->check_out_at = $newCheckOutAt;
            if ($selectedCategory) {
                $booking->room_category_id = $selectedCategory->id;
            }
            $booking->late_arrival_fee = 0;
            $booking->late_arrival_hours = null;
            $booking->late_arrival_confirmed_at = $isRescheduledToTodayAfterCutoff ? $nowVn : null;
            $booking->late_arrival_confirmed_by = $isRescheduledToTodayAfterCutoff ? Auth::id() : null;
            $booking->late_arrival_policy = $isRescheduledToTodayAfterCutoff
                ? Booking::RESCHEDULED_AFTER_G_POLICY_PREFIX
                    . ' Lễ tân chuyển đơn từ ngày tương lai về hôm nay lúc '
                    . $nowVn->format('d/m/Y H:i')
                    . '. Đây là khách đến sớm khác ngày, được check-in trong '
                    . Booking::RESCHEDULED_AFTER_G_GRACE_MINUTES
                    . ' phút kể từ lúc đổi lịch.'
                : null;
            $booking->note = $oldNote
                . now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                . ' - Đổi ngày lưu trú từ '
                . $oldCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $oldCheckOutAt->format('d/m/Y H:i')
                . ' sang '
                . $newCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $newCheckOutAt->format('d/m/Y H:i')
                . ($selectedCategory ? ' và đổi toàn bộ sang hạng ' . $selectedCategory->name . '.' : '.')
                . ' Chênh lệch tiền phòng: '
                . number_format($roomDelta, 0, ',', '.')
                . 'đ.'
                . $roomChangeText;

            $booking->save();

            app(BookingRepricingService::class)->apply($booking, $repricingPreview);
            $booking->refresh();

            $removedPromotionText = collect($repricingPreview['promotion_preview']['removed'] ?? [])
                ->map(fn (array $item) => ($item['code'] ?? '---') . ': ' . ($item['reason'] ?? 'không còn đủ điều kiện'))
                ->implode('; ');
            $serviceRepriceText = collect($repricingPreview['service_preview']['lines'] ?? [])
                ->filter(fn (array $item) => !empty($item['will_reprice']) || !empty($item['will_remove']))
                ->map(function (array $item) {
                    return ($item['name'] ?? 'Dịch vụ')
                        . ': '
                        . number_format((float) ($item['old_total'] ?? 0), 0, ',', '.')
                        . 'đ → '
                        . number_format((float) ($item['new_total'] ?? 0), 0, ',', '.')
                        . 'đ';
                })
                ->implode('; ');

            $logDescription = 'Đổi ngày lưu trú từ '
                . $oldCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $oldCheckOutAt->format('d/m/Y H:i')
                . ' sang '
                . $newCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $newCheckOutAt->format('d/m/Y H:i')
                . ($selectedCategory ? '; đổi toàn bộ sang hạng ' . $selectedCategory->name : '')
                . '. Số đêm: '
                . $repricingPreview['old']['night_count']
                . ' → '
                . $repricingPreview['new']['night_count']
                . '. Tiền phòng: '
                . number_format((float) $repricingPreview['old']['room_total'], 0, ',', '.')
                . 'đ → '
                . number_format((float) $repricingPreview['new']['room_total'], 0, ',', '.')
                . 'đ. Tổng đơn: '
                . number_format((float) $repricingPreview['old']['total'], 0, ',', '.')
                . 'đ → '
                . number_format((float) $repricingPreview['new']['total'], 0, ',', '.')
                . 'đ. Đã thanh toán: '
                . number_format((float) $repricingPreview['paid_total'], 0, ',', '.')
                . 'đ. Còn phải thu: '
                . number_format((float) $repricingPreview['new']['remaining'], 0, ',', '.')
                . 'đ. Khách đang trả dư: '
                . number_format((float) $repricingPreview['new']['overpayment'], 0, ',', '.')
                . 'đ. Mức cọc mới: '
                . number_format((float) $repricingPreview['new']['required_deposit'], 0, ',', '.')
                . 'đ.'
                . ($removedPromotionText !== '' ? ' Mã bị gỡ: ' . $removedPromotionText . '.' : '')
                . ($serviceRepriceText !== '' ? ' Dịch vụ tính lại: ' . $serviceRepriceText . '.' : '')
                . $roomChangeText;

            $this->addBookingLog($booking, 'change_stay_dates', $logDescription);

            DB::commit();

            return back()->with(
                'success',
                'Đã đổi ngày lưu trú thành công'
                . ($selectedCategory ? ' và chuyển booking sang hạng ' . $selectedCategory->name : '')
                . '. Chênh lệch tiền phòng: '
                . number_format($roomDelta, 0, ',', '.')
                . 'đ.'
                . $roomChangeText
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            if (str_contains($e->getMessage(), 'phương án vừa được booking khác sử dụng')) {
                return $this->redirectWithStayDateCategoryOptions(
                    $request,
                    $booking,
                    $newCheckInAt,
                    $newCheckOutAt,
                    $e->getMessage()
                );
            }

            return back()->withInput()->with('error', 'Có lỗi khi đổi ngày lưu trú: ' . $e->getMessage());
        }
    }

    public function discardStayDatePreview(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        session()->forget('stay_date_reprice_preview');
        session()->forget('_old_input');

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('success', 'Đã bỏ bản xem trước.');
    }

    private function findAvailableRoomsForChangedStayDates(
        int $roomCategoryId,
        int $quantity,
        \Carbon\Carbon $newCheckInAt,
        \Carbon\Carbon $newCheckOutAt,
        Booking $booking,
        array $excludeRoomIds = []
    ) {
        $query = Room::where('room_category_id', $roomCategoryId)
            ->availableForPeriod(
                $newCheckInAt,
                $newCheckOutAt,
                $booking->id,
                (int) ($booking->cleaning_buffer_minutes ?? 0)
            );

        if (count($excludeRoomIds) > 0) {
            $query->whereNotIn('id', array_values(array_unique(array_map('intval', $excludeRoomIds))));
        }

        return $query
            ->with('category')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->take($quantity)
            ->get();
    }

    private function redirectWithStayDateCategoryOptions(
        Request $request,
        Booking $booking,
        \Carbon\Carbon $newCheckInAt,
        \Carbon\Carbon $newCheckOutAt,
        string $reason
    ) {
        $booking->loadMissing(['bookingRooms.room.category', 'roomCategory']);

        $roomQuantity = max(1, $booking->bookingRooms->count());
        $newNightCount = max(
            1,
            $newCheckInAt->copy()->startOfDay()->diffInDays($newCheckOutAt->copy()->startOfDay())
        );
        $oldCheckInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $oldCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');
        $oldNightCount = max(
            1,
            $oldCheckInAt->copy()->startOfDay()->diffInDays($oldCheckOutAt->copy()->startOfDay())
        );
        $oldOneNightRoomTotal = (float) $booking->bookingRooms->sum(
            fn ($bookingRoom) => (float) $bookingRoom->price_at_booking
        );

        if ($oldOneNightRoomTotal <= 0) {
            $oldOneNightRoomTotal = (float) ($booking->roomCategory->price ?? 0) * $roomQuantity;
        }

        $oldRoomTotal = $oldOneNightRoomTotal * $oldNightCount;
        $options = [];

        $categories = RoomCategory::where('status', 'active')
            ->orderBy('price')
            ->orderBy('name')
            ->get();

        foreach ($categories as $category) {
            $totalAdultCapacity = (int) $category->adult_capacity * $roomQuantity;
            $totalChildCapacity = (int) $category->child_capacity * $roomQuantity;

            if (
                $totalAdultCapacity < (int) ($booking->adult_count ?? 0)
                || $totalChildCapacity < (int) ($booking->child_count ?? 0)
            ) {
                continue;
            }

            $rooms = $this->findAvailableRoomsForChangedStayDates(
                (int) $category->id,
                $roomQuantity,
                $newCheckInAt,
                $newCheckOutAt,
                $booking,
                []
            );

            if ($rooms->count() < $roomQuantity) {
                continue;
            }

            $newRoomTotal = (float) $category->price * $roomQuantity * $newNightCount;
            $difference = round($newRoomTotal - $oldRoomTotal, 0);

            $options[] = [
                'category_id' => (int) $category->id,
                'category_name' => $category->name,
                'price_per_room_per_night' => (float) $category->price,
                'price_text' => number_format((float) $category->price, 0, ',', '.') . 'đ/phòng/đêm',
                'room_quantity' => $roomQuantity,
                'room_numbers' => $rooms->pluck('room_number')->implode(', '),
                'adult_capacity' => $totalAdultCapacity,
                'child_capacity' => $totalChildCapacity,
                'new_room_total' => $newRoomTotal,
                'new_room_total_text' => number_format($newRoomTotal, 0, ',', '.') . 'đ',
                'difference' => $difference,
                'difference_text' => ($difference > 0 ? '+' : '') . number_format($difference, 0, ',', '.') . 'đ',
                'is_current_category' => (int) $category->id === (int) $booking->room_category_id,
            ];
        }

        if (count($options) === 0) {
            return back()->withInput($request->all())->with(
                'error',
                $reason
                . ' Trong khung '
                . $newCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $newCheckOutAt->format('d/m/Y H:i')
                . ', hiện không có hạng phòng nào còn đủ '
                . $roomQuantity
                . ' phòng. Hãy đổi ngày, giảm số phòng hoặc tạo booking khác.'
            );
        }

        return back()
            ->withInput($request->all())
            ->with('stay_date_category_options', [
                'booking_id' => (int) $booking->id,
                'reason' => $reason,
                'new_check_in_date' => $newCheckInAt->toDateString(),
                'new_check_in_time' => $newCheckInAt->format('H:i'),
                'new_check_out_date' => $newCheckOutAt->toDateString(),
                'new_check_out_time' => $newCheckOutAt->format('H:i'),
                'period_text' => $newCheckInAt->format('d/m/Y H:i')
                    . ' → '
                    . $newCheckOutAt->format('d/m/Y H:i'),
                'room_quantity' => $roomQuantity,
                'options' => $options,
            ])
            ->with(
                'warning',
                $reason
                . ' Hãy chọn một hạng còn đủ phòng ngay bên dưới để đổi hạng theo đúng lịch mới.'
            );
    }


    public function requestInspection(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->with('bookingRooms.room')
                ->firstOrFail();

            if ($booking->status !== 'checked_in') {
                throw new \Exception('Chỉ có thể yêu cầu kiểm tra khi khách đang ở và chưa gửi yêu cầu kiểm tra trước đó.');
            }

            if ($booking->bookingRooms->isEmpty()) {
                throw new \Exception('Booking chưa được gán phòng nên không thể tạo phiếu kiểm tra.');
            }

            foreach ($booking->bookingRooms as $bookingRoom) {
                if (!$bookingRoom->room) {
                    continue;
                }

                $bookingRoom->room->update([
                    'status' => 'inspection',
                ]);

                \App\Models\RoomActionLog::create([
                    'room_id'     => $bookingRoom->room->id,
                    'user_id'     => Auth::id(),
                    'action_type' => 'status_change',
                    'action_time' => now(),
                    'note'        => 'Yêu cầu kiểm tra phòng trước check-out từ booking #' . $booking->booking_code,
                ]);

                RoomInspection::firstOrCreate(
                    [
                        'booking_id' => $booking->id,
                        'room_id'    => $bookingRoom->room_id,
                        'status'     => 'pending',
                    ],
                    [
                        'has_damage' => false,
                        'damage_total' => 0,
                        'workflow_stage' => \App\Models\RoomInspection::STAGE_HOUSEKEEPING_REPORT,
                        'version' => 0,
                        'admin_acknowledged_version' => 0,
                    ]
                );
            }


            $oldNote = $booking->note ? $booking->note . "\n" : '';

            $booking->update([
                'status' => 'inspection_requested',
                'note' => $oldNote . now()->format('d/m/Y H:i') . ' - Đã yêu cầu kiểm tra phòng trước khi check-out.',
            ]);

            $roomNumbers = $booking->bookingRooms
                ->pluck('room.room_number')
                ->filter()
                ->implode(', ');

            $this->addBookingLog(
                $booking,
                'request_inspection',
                'Yêu cầu kiểm tra phòng trước check-out. Phòng cần kiểm tra: ' . $roomNumbers . '.'
            );

            DB::commit();

            return back()->with('success', 'Đã tạo phiếu kiểm tra và chuyển phòng sang trạng thái chờ kiểm tra.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi yêu cầu kiểm tra phòng: ' . $e->getMessage());
        }
    }

    public function requestPriorityCleaning(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Chỉ gửi yêu cầu dọn ưu tiên cho booking đã xác nhận nhưng khách chưa check-in.');
        }

        if (!$booking->check_in_at) {
            return back()->with('error', 'Booking này chưa có giờ nhận phòng dự kiến.');
        }

        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $priorityStartAt = $checkInAt->copy()->setTimeFromTimeString(self::PRIORITY_CLEANING_START_TIME);

        if (!$nowVn->isSameDay($checkInAt)) {
            return back()->with('error', 'Chỉ gửi yêu cầu dọn ưu tiên trong đúng ngày nhận phòng.');
        }

        if ($nowVn->lessThan($priorityStartAt)) {
            return back()->with('error', 'Chỉ gửi yêu cầu dọn ưu tiên từ 12:00 ngày nhận phòng.');
        }

        $booking->load('bookingRooms.room');

        $roomsNeedPriority = $booking->bookingRooms->filter(function ($bookingRoom) {
            $status = $bookingRoom->room->status ?? null;

            return in_array($status, ['inspection', 'cleaning']);
        });

        if ($roomsNeedPriority->count() == 0) {
            return back()->with(
                'error',
                'Không có phòng nào đang chờ kiểm tra hoặc cần dọn. Nếu phòng đã sẵn sàng, lễ tân có thể check-in cho khách.'
            );
        }

        DB::beginTransaction();

        try {
            $roomMessages = [];

            foreach ($roomsNeedPriority as $bookingRoom) {
                $room = $bookingRoom->room;

                if (!$room) {
                    continue;
                }

                $oldRoomNote = $room->note ? $room->note . "\n" : '';

                $room->update([
                    'note' => $oldRoomNote
                        . $nowVn->format('d/m/Y H:i')
                        . ' - ƯU TIÊN DỌN NHANH cho booking '
                        . $booking->booking_code
                        . '. Khách đã đến từ 12:00–14:00, cần chuẩn bị phòng sớm nếu có thể.',
                ]);

                $roomMessages[] = 'Phòng '
                    . $room->room_number
                    . ' đang '
                    . mb_strtolower($this->getRoomStatusLabel($room->status));
            }

            $oldBookingNote = $booking->note ? $booking->note . "\n" : '';

            $booking->update([
                'note' => $oldBookingNote
                    . $nowVn->format('d/m/Y H:i')
                    . ' - Lễ tân gửi yêu cầu buồng phòng ưu tiên dọn nhanh. '
                    . implode(', ', $roomMessages)
                    . '.',
            ]);

            $this->addBookingLog(
                $booking,
                'priority_cleaning',
                'Gửi yêu cầu buồng phòng ưu tiên dọn nhanh từ 12:00–14:00. '
                . implode(', ', $roomMessages)
                . '.'
            );

            DB::commit();

            return back()->with('success', 'Đã gửi yêu cầu dọn ưu tiên cho buồng phòng. Khi phòng sẵn sàng, lễ tân có thể check-in.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi gửi yêu cầu dọn ưu tiên: ' . $e->getMessage());
        }
    }

    public function addCheckoutFee(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'inspection_requested') {
            return back()->with('error', 'Chỉ có thể thêm phí chốt tại bước chờ check-out.');
        }

        $data = $request->validate([
            'checkout_extra_name' => 'required|string|max:150',
            'checkout_extra_amount' => 'required|numeric|min:1000',
            'checkout_extra_note' => 'nullable|string|max:1000',
        ], [
            'checkout_extra_name.required' => 'Vui lòng nhập tên khoản phí phát sinh.',
            'checkout_extra_amount.required' => 'Vui lòng nhập số tiền phí phát sinh.',
            'checkout_extra_amount.numeric' => 'Số tiền phí phát sinh không hợp lệ.',
            'checkout_extra_amount.min' => 'Số tiền phí phát sinh tối thiểu là 1.000đ.',
        ]);

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->with(['roomInspections', 'serviceItems'])
                ->firstOrFail();

            if ($booking->status !== 'inspection_requested') {
                throw new \Exception('Booking đã được xử lý hoặc không còn ở bước chờ check-out.');
            }

            if ($booking->roomInspections->contains(fn ($inspection) => $inspection->status !== 'confirmed')) {
                throw new \Exception('Chưa thể thêm phí chốt vì vẫn còn phiếu kiểm tra phòng chưa được duyệt.');
            }

            $feeName = trim((string) $data['checkout_extra_name']);
            $feeAmount = (float) $data['checkout_extra_amount'];
            $feeNote = trim((string) ($data['checkout_extra_note'] ?? ''));

            $manualFeeService = Service::firstOrCreate(
                [
                    'name' => $feeName,
                    'type' => 'manual_fee',
                ],
                [
                    'service_group' => 'other',
                    'price' => 0,
                    'unit' => 'lần',
                    'description' => 'Khoản phí phát sinh được lễ tân ghi nhận trước khi check-out.',
                    'status' => 'active',
                ]
            );

            BookingServiceItem::create([
                'booking_id' => $booking->id,
                'service_id' => $manualFeeService->id,
                'name' => $feeName,
                'type' => 'manual_fee',
                'unit_price' => $feeAmount,
                'quantity' => 1,
                'used_quantity' => 1,
                'billing_status' => 'confirmed',
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now('Asia/Ho_Chi_Minh'),
                'confirm_note' => $feeNote !== '' ? $feeNote : null,
                'total' => $feeAmount,
                'note' => $feeNote !== '' ? $feeNote : 'Phí phát sinh được thêm trước khi check-out.',
            ]);

            $this->addBookingLog(
                $booking,
                'checkout_fee_added',
                'Thêm phí phát sinh trước check-out: ' . $feeName . ' - '
                . number_format($feeAmount, 0, ',', '.') . 'đ'
                . ($feeNote !== '' ? '. Ghi chú: ' . $feeNote : '')
            );

            DB::commit();

            Realtime::booking($booking->id, 'checkout_fee_added');

            return back()->with(
                'success',
                'Đã thêm phí “' . $feeName . '” ' . number_format($feeAmount, 0, ',', '.')
                . 'đ. Tổng cần thanh toán và số còn lại đã được cập nhật.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể thêm phí phát sinh: ' . $e->getMessage());
        }
    }

    public function checkOut(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'inspection_requested') {
            return back()->with('error', 'Chỉ có thể check-out sau khi đã yêu cầu và hoàn tất kiểm tra phòng.');
        }

        $data = $request->validate([
            'checkout_late_fee_confirm' => 'nullable|in:1',
        ]);

        $booking->load([
            'bookingRooms.room.category',
            'roomCategory',
            'roomInspections.items',
            'serviceItems',
        ]);

        $assignedRoomCount = $booking->bookingRooms->pluck('room_id')->filter()->unique()->count();
        $inspectionRoomCount = $booking->roomInspections->pluck('room_id')->filter()->unique()->count();

        if ($assignedRoomCount === 0 || $inspectionRoomCount < $assignedRoomCount) {
            return back()->with(
                'error',
                'Không thể check-out vì chưa có đủ phiếu kiểm tra cho tất cả phòng đã gán.'
            );
        }

        $notConfirmedInspectionCount = $booking->roomInspections
            ->where('status', '!=', 'confirmed')
            ->count();

        if ($notConfirmedInspectionCount > 0) {
            return back()->with(
                'error',
                'Không thể check-out vì vẫn còn '
                . $notConfirmedInspectionCount
                . ' phiếu kiểm tra chưa được quản lý duyệt.'
            );
        }

        $actualCheckOutAt = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $lateCheckout = $this->calculateCheckoutLateFee($booking, $actualCheckOutAt);

        if ($lateCheckout['amount'] > 0 && ($data['checkout_late_fee_confirm'] ?? null) !== '1') {
            return back()->with(
                'error',
                'Booking trả phòng muộn, phát sinh phụ thu '
                . number_format($lateCheckout['amount'], 0, ',', '.')
                . 'đ. Vui lòng tick xác nhận khách đã chấp nhận phụ thu trước khi check-out.'
            );
        }

        DB::beginTransaction();

        try {
            $booking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->with(['bookingRooms.room.category', 'roomCategory', 'roomInspections.items', 'serviceItems'])
                ->firstOrFail();

            if ($booking->status !== 'inspection_requested') {
                throw new \Exception('Booking đã được xử lý bởi yêu cầu khác hoặc chưa ở trạng thái chờ check-out.');
            }

            // Khởi tạo đầy đủ các thành phần tiền dùng khi tổng hợp và ghi log check-out.
            $roomBaseTotal = $this->getCheckoutRoomBaseTotal($booking);
            $serviceItemTotal = (float) $booking->serviceItems
                ->where('billing_status', 'confirmed')
                ->sum(function ($item) {
                    return (float) $item->total;
                });
            $approvedInspectionTotal = $this->getApprovedInspectionTotal($booking);

            $feeMessages = [];

            if ($lateCheckout['amount'] > 0) {
                $lateCheckoutService = Service::firstOrCreate(
                    [
                        'name' => 'Phụ thu check-out muộn',
                        'type' => 'late_checkout_fee',
                    ],
                    [
                        'service_group' => 'other',
                        'price' => 0,
                        'unit' => 'lần',
                        'description' => 'Phụ thu khi khách trả phòng muộn so với giờ check-out trên booking.',
                        'status' => 'active',
                    ]
                );

                BookingServiceItem::updateOrCreate(
                    [
                        'booking_id' => $booking->id,
                        'name' => 'Phụ thu check-out muộn',
                        'type' => 'late_checkout_fee',
                    ],
                    [
                        'service_id' => $lateCheckoutService->id,
                        'unit_price' => $lateCheckout['amount'],
                        'quantity' => 1,
                        'used_quantity' => 1,
                        'billing_status' => 'confirmed',
                        'confirmed_by' => Auth::id(),
                        'confirmed_at' => $actualCheckOutAt,
                        'confirm_note' => $lateCheckout['policy_text'],
                        'total' => $lateCheckout['amount'],
                        'note' => $lateCheckout['note'],
                    ]
                );

                $feeMessages[] = 'Phụ thu check-out muộn: '
                    . number_format($lateCheckout['amount'], 0, ',', '.')
                    . 'đ. '
                    . $lateCheckout['policy_text'];
            }

            $booking->load(['bookingRooms.room.category', 'roomCategory', 'roomInspections.items', 'serviceItems']);
            $financialService = app(BookingFinancialService::class);
            $finalTotal = $financialService->currentTotal($booking);

            $paidBeforeCheckout = (float) $booking->payments()->where('status', 'success')->sum('amount');
            $remainingTotal = max(0, $finalTotal - $paidBeforeCheckout);

            // Check-out tuyệt đối không tự sinh giao dịch thanh toán từ một checkbox.
            // Lễ tân phải ghi nhận khoản thu ở khối Thanh toán trước; chỉ các giao dịch
            // thành công đã lưu trong booking_payments mới được dùng để mở khóa check-out.
            if ($remainingTotal > 0.01) {
                throw new \Exception(
                    'Booking còn '
                    . number_format($remainingTotal, 0, ',', '.')
                    . 'đ chưa thanh toán trên hệ thống. Vui lòng ghi nhận khoản thu ở mục Thanh toán, sau đó bấm Check-out lại.'
                );
            }

            $paidAfterCheckout = $paidBeforeCheckout;

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $feeText = count($feeMessages) > 0
                ? ' Phí phát sinh: ' . implode(' ', $feeMessages)
                : ' Không phát sinh phụ thu check-out.';

            $paymentText = ' Khách đã thanh toán đủ trên hệ thống trước khi check-out.';

            $booking->update([
                'status' => 'checked_out',
                'actual_check_out' => $actualCheckOutAt,
                'payment_status' => 'paid',
                'final_total' => $finalTotal,
                'estimated_total' => $finalTotal,
                'note' => $oldNote
                    . $actualCheckOutAt->format('d/m/Y H:i')
                    . ' - Check-out thực tế. Tổng phải thu: '
                    . number_format($finalTotal, 0, ',', '.')
                    . 'đ. Đã thu trước check-out: '
                    . number_format($paidBeforeCheckout, 0, ',', '.')
                    . 'đ. Còn lại khi check-out: '
                    . number_format($remainingTotal, 0, ',', '.')
                    . 'đ.'
                    . $paymentText
                    . $feeText,
            ]);

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $room = $bookingRoom->room;
                    $nextBookingRoom = \App\Models\BookingRoom::query()
                        ->with('booking')
                        ->where('room_id', $room->id)
                        ->where('booking_id', '!=', $booking->id)
                        ->whereHas('booking', function ($query) use ($actualCheckOutAt) {
                            $query->whereIn('status', ['pending', 'confirmed'])
                                ->where('check_in_at', '>=', $actualCheckOutAt)
                                ->where('check_in_at', '<=', $actualCheckOutAt->copy()->addHours(2));
                        })
                        ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
                        ->orderBy('bookings.check_in_at')
                        ->select('booking_rooms.*')
                        ->first();

                    $urgentText = '';
                    if ($nextBookingRoom?->booking) {
                        $nextCheckIn = \Carbon\Carbon::parse($nextBookingRoom->booking->check_in_at, 'Asia/Ho_Chi_Minh');
                        $urgentText = ' DỌN GẤP: phòng có khách tiếp theo dự kiến nhận lúc '
                            . $nextCheckIn->format('H:i d/m/Y')
                            . ' (đơn ' . $nextBookingRoom->booking->booking_code . '). Lễ tân cần báo khách có thể phải chờ đến khi buồng phòng xác nhận đã dọn xong.';
                    }

                    $room->update([
                        'status' => 'cleaning',
                        'note' => trim(($room->note ? $room->note . "\n" : '')
                            . $actualCheckOutAt->format('d/m/Y H:i')
                            . ' - Phòng vừa trả, cần dọn.' . $urgentText),
                    ]);

                    \App\Models\RoomActionLog::create([
                        'room_id' => $room->id,
                        'user_id' => Auth::id(),
                        'action_type' => $urgentText ? 'priority_cleaning' : 'check_out',
                        'action_time' => now(),
                        'note' => 'Khách trả phòng từ booking #' . $booking->booking_code
                            . '. Chuyển sang trạng thái dọn dẹp.' . $urgentText,
                    ]);
                }
            }

            $roomNumbers = $booking->bookingRooms
                ->pluck('room.room_number')
                ->filter()
                ->implode(', ');

            $this->addBookingLog(
                $booking,
                'check_out',
                'Xác nhận check-out lúc '
                . $actualCheckOutAt->format('d/m/Y H:i')
                . '. Phòng chuyển sang cần dọn: '
                . $roomNumbers
                . '. Tiền phòng: '
                . number_format($roomBaseTotal, 0, ',', '.')
                . 'đ. Dịch vụ/phụ thu: '
                . number_format($serviceItemTotal, 0, ',', '.')
                . 'đ. Minibar/hư hại duyệt: '
                . number_format($approvedInspectionTotal, 0, ',', '.')
                . 'đ. Tổng phải thu: '
                . number_format($finalTotal, 0, ',', '.')
                . 'đ. Đã thu trước check-out: '
                . number_format($paidBeforeCheckout, 0, ',', '.')
                . 'đ. Còn lại khi check-out: '
                . number_format($remainingTotal, 0, ',', '.')
                . 'đ.'
                . $paymentText
                . $feeText
            );

            DB::commit();

            Realtime::booking($booking->id, 'checked_out');

            return back()->with(
                'success',
                'Check-out thành công. Tổng phải thu '
                . number_format($finalTotal, 0, ',', '.')
                . 'đ. '
                . ($remainingTotal > 0
                    ? 'Đã ghi nhận thu thêm '
                        . number_format($remainingTotal, 0, ',', '.')
                        . 'đ bằng '
                        . $this->getCheckoutPaymentMethodLabel($paymentMethod)
                        . '. '
                    : 'Booking đã thanh toán đủ trước đó. ')
                . 'Phòng đã chuyển sang trạng thái cần dọn.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Có lỗi khi check-out: ' . $e->getMessage());
        }
    }

    private function getCheckoutPaymentMethodLabel(?string $method): string
    {
        return match ($method) {
            'cash' => 'tiền mặt tại quầy',
            'bank_transfer' => 'chuyển khoản tại quầy',
            default => 'không xác định',
        };
    }

    private function generateCheckoutPaymentTxnRef(Booking $booking, string $method): string
    {
        $prefix = $method === 'cash' ? 'CASHOUT' : 'BANKOUT';

        do {
            $txnRef = $prefix
                . $booking->booking_code
                . now('Asia/Ho_Chi_Minh')->format('YmdHis')
                . strtoupper(\Illuminate\Support\Str::random(5));

            $txnRef = preg_replace('/[^A-Za-z0-9]/', '', $txnRef);
        } while (BookingPayment::where('txn_ref', $txnRef)->exists());

        return $txnRef;
    }


    private function calculateCheckoutLateFee(Booking $booking, ?\Carbon\Carbon $actualCheckOutAt = null): array
    {
        if (!$booking->check_out_at) {
            return [
                'amount' => 0,
                'late_minutes' => 0,
                'late_hours' => 0,
                'policy_text' => 'Booking chưa có giờ check-out dự kiến.',
                'note' => '',
            ];
        }

        $actualCheckOutAt = $actualCheckOutAt ?: \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $plannedCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        if ($actualCheckOutAt->lessThanOrEqualTo($plannedCheckOutAt)) {
            return [
                'amount' => 0,
                'late_minutes' => 0,
                'late_hours' => 0,
                'policy_text' => 'Khách trả phòng đúng hạn, không phụ thu check-out muộn.',
                'note' => '',
            ];
        }

        $lateMinutes = $plannedCheckOutAt->diffInMinutes($actualCheckOutAt);
        $lateHours = round($lateMinutes / 60, 2);

        $existingLateCheckoutFee = BookingServiceItem::where('booking_id', $booking->id)
            ->where('type', 'late_checkout_fee')
            ->where('billing_status', 'confirmed')
            ->first();

        if ($existingLateCheckoutFee) {
            return [
                'amount' => 0,
                'late_minutes' => $lateMinutes,
                'late_hours' => $lateHours,
                'policy_text' => 'Phụ thu check-out muộn đã được ghi nhận trước đó.',
                'note' => $existingLateCheckoutFee->note ?? '',
            ];
        }

        $oneNightTotal = (float) $booking->bookingRooms->sum('price_at_booking');
        if ($oneNightTotal <= 0) {
            $oneNightTotal = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
        }

        $pricing = app(StayPricingPolicyService::class);

        if ($booking->booking_type === 'hourly') {
            $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
            $totalMinutes = $checkInAt->diffInMinutes($actualCheckOutAt);
            $nightPrice = (float) ($booking->roomCategory->price ?? 0);
            $quantity = max(1, (int) $booking->room_quantity);

            $newTotal = $totalMinutes > 12 * 60
                ? $pricing->longStay($checkInAt, $actualCheckOutAt, $nightPrice, $quantity)['total_amount']
                : $pricing->shortStay($nightPrice, $quantity, $totalMinutes)['amount'];

            $currentRoomTotal = max(0, $this->getCheckoutRoomBaseTotal($booking));
            $amount = max(0, round($newTotal - $currentRoomTotal, 0));
            $policyText = $totalMinutes > 12 * 60
                ? 'Tổng thời gian sau khi trả muộn vượt 12 giờ, hệ thống tự chuyển sang chính sách qua đêm và chỉ thu phần chênh lệch.'
                : 'Booking theo giờ được tính lại theo tổng thời gian thực tế và chỉ thu phần chênh lệch.';
        } else {
            $extraDays = max(0, $plannedCheckOutAt->copy()->startOfDay()->diffInDays($actualCheckOutAt->copy()->startOfDay()));
            $dayPolicy = $pricing->lateCheckOut($actualCheckOutAt, $oneNightTotal);
            $amount = round(($extraDays * $oneNightTotal) + $dayPolicy['amount'], 0);
            $policyText = ($extraDays > 0 ? 'Trả sang thêm ' . $extraDays . ' ngày, tính thêm ' . $extraDays . ' đêm. ' : '')
                . $dayPolicy['policy_text'];
        }

        return [
            'amount' => $amount,
            'late_minutes' => $lateMinutes,
            'late_hours' => $lateHours,
            'policy_text' => $policyText,
            'note' => 'Giờ check-out dự kiến: ' . $plannedCheckOutAt->format('d/m/Y H:i')
                . '. Giờ check-out thực tế: ' . $actualCheckOutAt->format('d/m/Y H:i')
                . '. ' . $policyText,
        ];
    }
    private function getCheckoutRoomBaseTotal(Booking $booking): float
    {
        $booking->loadMissing([
            'bookingRooms.room.category',
            'roomCategory',
            'serviceItems',
        ]);

        $serviceItemTotal = $booking->serviceItems
            ->where('billing_status', 'confirmed')
            ->sum(function ($item) {
                return (float) $item->total;
            });

        if ($booking->booking_type === 'hourly') {
            $roomTotal = max(0, (float) $booking->estimated_total - $serviceItemTotal);

            if ($roomTotal > 0) {
                return round($roomTotal, 0);
            }

            return round((float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity), 0);
        }

        $nightCount = $this->getNightCount($booking);
        $roomTotal = $booking->bookingRooms->sum(function ($bookingRoom) use ($nightCount) {
            return (float) $bookingRoom->price_at_booking * $nightCount;
        });

        if ($roomTotal > 0) {
            return round($roomTotal, 0);
        }

        return round(max(0, (float) $booking->estimated_total - $serviceItemTotal), 0);
    }

    private function getApprovedInspectionTotal(Booking $booking): float
    {
        $booking->loadMissing('roomInspections.items');

        return (float) $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->sum(function ($item) {
                return (float) $item->total;
            });
    }


    private function guardCheckInArrivalTime(Booking $booking, \Carbon\Carbon $actualCheckInAt): void
    {
        if (!$booking->check_in_at || !$booking->check_out_at) {
            return;
        }

        $plannedCheckInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $plannedCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        if ($actualCheckInAt->greaterThanOrEqualTo($plannedCheckOutAt)) {
            throw new \Exception(
                'Booking đã quá thời gian lưu trú '
                . $plannedCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $plannedCheckOutAt->format('d/m/Y H:i')
                . '. Không được check-in booking cũ. Vui lòng hủy/no-show và tạo booking mới nếu khách vẫn muốn ở.'
            );
        }

        if (
            $booking->booking_type !== 'hourly'
            && $actualCheckInAt->toDateString() < $plannedCheckInAt->toDateString()
        ) {
            throw new \Exception(
                'Khách đến trước ngày booking. Booking bắt đầu lúc '
                . $plannedCheckInAt->format('d/m/Y H:i')
                . ', hiện tại là '
                . $actualCheckInAt->format('d/m/Y H:i')
                . '. Đây không phải check-in sớm trong cùng ngày. Vui lòng dùng chức năng Đổi ngày lưu trú hoặc tạo booking mới trước khi check-in.'
            );
        }

        // Walk-in, ở ngay qua đêm, booking theo giờ và đơn vừa đổi ngày không thuộc
        // chính sách giờ G/no-show. Các đơn này chỉ bị chặn khi đã quá giờ trả phòng.
        if (!$booking->usesLateArrivalNoShowPolicy()) {
            return;
        }

        if ($actualCheckInAt->greaterThan($plannedCheckInAt)) {
            $holdLimitAt = $this->getLateArrivalHoldLimitAt($booking);

            if ($holdLimitAt && $actualCheckInAt->greaterThanOrEqualTo($holdLimitAt)) {
                throw new \Exception(
                    'Khách đã quá hạn giữ phòng '
                    . $holdLimitAt->format('d/m/Y H:i')
                    . '. Không phụ thu check-in muộn trong hạn; nhưng quá hạn thì không được check-in. Vui lòng hủy/no-show để giữ cọc nếu có và mở bán lại phòng.'
                );
            }
        }
    }

    private function getBookingNightCountFromTimes(Booking $booking): int
    {
        if (!$booking->check_in_at || !$booking->check_out_at) {
            return $this->getNightCount($booking);
        }

        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->startOfDay();
        $checkOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->startOfDay();

        return max(1, $checkInAt->diffInDays($checkOutAt));
    }

    private function isBookingFullyPaid(Booking $booking): bool
    {
        $estimatedTotal = (float) $booking->estimated_total;
        $depositAmount = (float) $booking->deposit_amount;

        return $booking->payment_status === 'paid'
            || ($estimatedTotal > 0 && $depositAmount >= $estimatedTotal);
    }

    private function getLateArrivalHoldLimitAt(Booking $booking): ?\Carbon\Carbon
    {
        return $booking->lateArrivalHoldLimitAt();
    }


    private function handleLateArrivalFee(Booking $booking, array $data): string
    {
        if (!$booking->check_in_at || !$booking->check_out_at) {
            return '';
        }

        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $checkOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        // Chỉ booking đặt trước thật sự mới được tính phụ thu/giữ phòng sau giờ G.
        // Walk-in, ở ngay qua đêm, theo giờ và đơn vừa đổi ngày đều xử lý như check-in bình thường.
        if (!$booking->usesLateArrivalNoShowPolicy()) {
            $booking->late_arrival_fee = 0;
            $booking->late_arrival_hours = null;

            if ($booking->isRescheduledAfterCutoff()) {
                $rescheduledAt = $booking->rescheduledAfterCutoffAt();
                $policy = 'Đơn được lễ tân chuyển từ ngày tương lai về hôm nay'
                    . ($rescheduledAt ? ' lúc ' . $rescheduledAt->format('d/m/Y H:i') : '')
                    . '. Đây là đổi ngày nhận phòng, không phải khách đến muộn và không phát sinh phụ thu sau giờ G.';
                $booking->late_arrival_policy = Booking::RESCHEDULED_AFTER_G_POLICY_PREFIX . ' ' . $policy;
                return $policy;
            }

            $booking->late_arrival_policy = null;
            return '';
        }

        if ($nowVn->lessThanOrEqualTo($checkInAt)) {
            return '';
        }

        if ($nowVn->greaterThanOrEqualTo($checkOutAt)) {
            throw new \Exception('Không thể check-in vì booking đã quá thời gian lưu trú. Vui lòng xử lý no-show và tạo booking mới nếu khách vẫn muốn ở.');
        }

        $holdLimitAt = $this->getLateArrivalHoldLimitAt($booking);
        if ($holdLimitAt && $nowVn->greaterThanOrEqualTo($holdLimitAt)) {
            throw new \Exception('Khách đã quá giờ G ' . $holdLimitAt->format('H:i d/m/Y') . '. Booking phải được hủy no-show và mở bán lại phòng.');
        }

        $lateHours = round($checkInAt->diffInMinutes($nowVn) / 60, 2);
        $cutoffAt = $checkInAt->copy()->setTime(18, 0, 0);

        if ($nowVn->lessThanOrEqualTo($cutoffAt)) {
            $policy = 'Khách check-in trong khoảng từ 14:00 đến giờ G 18:00. Không phụ thu đến muộn.';
            $booking->late_arrival_fee = 0;
            $booking->late_arrival_policy = $policy;
            return $policy;
        }

        $existingFee = BookingServiceItem::where('booking_id', $booking->id)
            ->where('type', 'late_arrival_fee')
            ->where('billing_status', 'confirmed')
            ->first();

        if (!$booking->late_arrival_confirmed_at || !$existingFee) {
            throw new \Exception('Khách đến sau giờ G nhưng chưa được lễ tân xác nhận giữ phòng và ghi nhận phụ thu.');
        }

        $policy = 'Khách check-in sau giờ G theo xác nhận đến muộn. Phụ thu đã ghi nhận: '
            . number_format((float) $existingFee->total, 0, ',', '.') . 'đ. Hạn giữ phòng: '
            . ($holdLimitAt ? $holdLimitAt->format('H:i d/m/Y') : '---') . '.';

        $booking->late_arrival_fee = (float) $existingFee->total;
        $booking->late_arrival_policy = $policy;

        return $policy;
    }


    public function confirmLateArrival(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'confirmed' || $booking->actual_check_in) {
            return back()->with('error', 'Chỉ xác nhận khách đến muộn cho booking đã xác nhận và chưa check-in.');
        }

        $data = $request->validate([
            'expected_arrival_date' => ['required', 'date_format:Y-m-d'],
            'expected_arrival_time' => ['required', 'date_format:H:i'],
        ], [
            'expected_arrival_date.required' => 'Vui lòng chọn ngày khách dự kiến đến.',
            'expected_arrival_date.date_format' => 'Ngày khách dự kiến đến không hợp lệ.',
            'expected_arrival_time.required' => 'Vui lòng chọn giờ khách dự kiến đến.',
            'expected_arrival_time.date_format' => 'Giờ khách dự kiến đến không hợp lệ.',
        ]);

        $now = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInAt = $booking->check_in_at ? \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh') : null;
        $checkOutAt = $booking->check_out_at ? \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh') : null;

        if (!$checkInAt || !$checkOutAt || !$booking->usesLateArrivalNoShowPolicy()) {
            return back()->with('error', 'Chức năng giữ phòng sau giờ G chỉ áp dụng cho booking đặt trước qua đêm thuộc diện khách đến muộn.');
        }

        [$holdHour, $holdMinute] = array_map('intval', explode(':', Booking::LATE_ARRIVAL_HOLD_TIME));
        $cutoffAt = $checkInAt->copy()->setTime($holdHour, $holdMinute, 0);
        $expectedArrivalAt = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i',
            $data['expected_arrival_date'] . ' ' . $data['expected_arrival_time'],
            'Asia/Ho_Chi_Minh'
        );

        if ($now->lt($checkInAt)) {
            return back()->with('error', 'Chỉ xác nhận khách đến muộn từ giờ nhận phòng dự kiến trở đi.');
        }

        if ($expectedArrivalAt->lessThanOrEqualTo($cutoffAt)) {
            return back()->with('error', 'Giờ dự kiến đến phải sau giờ G ' . $cutoffAt->format('H:i d/m/Y') . '. Từ 14:00 đến giờ G không phát sinh phụ thu.');
        }

        if ($expectedArrivalAt->greaterThanOrEqualTo($checkOutAt)) {
            return back()->with('error', 'Giờ dự kiến đến phải trước giờ trả phòng của booking.');
        }

        $booking->loadMissing(['bookingRooms', 'roomCategory']);
        $oneNightTotal = (float) $booking->bookingRooms->sum('price_at_booking');
        if ($oneNightTotal <= 0) {
            $oneNightTotal = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
        }

        $latePolicy = app(StayPricingPolicyService::class)->lateArrival($expectedArrivalAt, $oneNightTotal, $cutoffAt);
        $feeAmount = (float) $latePolicy['amount'];
        $hoursAfterCutoff = (float) $latePolicy['hours_after_cutoff'];
        $extendedHoldAt = $expectedArrivalAt->copy()->addMinutes(30)->min($checkOutAt);

        DB::transaction(function () use ($booking, $now, $expectedArrivalAt, $extendedHoldAt, $latePolicy, $feeAmount, $hoursAfterCutoff) {
            $service = Service::query()
                ->where('name', 'Phụ thu khách đến muộn')
                ->orWhere('type', 'policy_violation_fee')
                ->orderByRaw("CASE WHEN name = 'Phụ thu khách đến muộn' THEN 0 ELSE 1 END")
                ->first();

            if (!$service) {
                throw new \RuntimeException('Chưa cấu hình dịch vụ Phụ thu khách đến muộn trong danh mục dịch vụ.');
            }

            BookingServiceItem::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'type' => 'late_arrival_fee',
                ],
                [
                    'service_id' => $service->id,
                    'name' => 'Phụ thu khách đến muộn',
                    'unit_price' => $feeAmount,
                    'quantity' => 1,
                    'used_quantity' => 1,
                    'billing_status' => 'confirmed',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => $now,
                    'confirm_note' => $latePolicy['policy_text'],
                    'total' => $feeAmount,
                    'note' => 'Giờ G: ' . $booking->check_in_at->copy()->setTime(18, 0)->format('d/m/Y H:i')
                        . '. Khách dự kiến đến: ' . $expectedArrivalAt->format('d/m/Y H:i')
                        . '. Giữ phòng đến: ' . $extendedHoldAt->format('d/m/Y H:i') . '.',
                ]
            );

            $booking->update([
                'late_arrival_fee' => $feeAmount,
                'late_arrival_hours' => $hoursAfterCutoff,
                'late_arrival_confirmed_at' => $now,
                'late_arrival_confirmed_by' => Auth::id(),
                'late_arrival_policy' => 'Khách xác nhận đến sau giờ G. Dự kiến đến '
                    . $expectedArrivalAt->format('d/m/Y H:i') . '; giữ phòng đến '
                    . $extendedHoldAt->format('d/m/Y H:i') . '. '
                    . $latePolicy['policy_text'] . ' Phụ thu: '
                    . number_format($feeAmount, 0, ',', '.') . 'đ.',
            ]);
        });

        app(BookingFinancialService::class)->refreshPaymentStatus($booking->fresh());
        $currentTotal = app(BookingFinancialService::class)->currentTotal($booking->fresh());
        $booking->update(['final_total' => $currentTotal]);

        $this->addBookingLog(
            $booking,
            'late_arrival_hold_extended',
            'Lễ tân xác nhận khách sẽ đến sau giờ G. Giờ G: ' . $cutoffAt->format('d/m/Y H:i')
            . '. Giờ khách dự kiến đến: ' . $expectedArrivalAt->format('d/m/Y H:i')
            . '. Phòng được giữ tiếp đến: ' . $extendedHoldAt->format('d/m/Y H:i')
            . '. Chính sách: ' . $latePolicy['policy_text']
            . ' Phụ thu khách đến muộn: ' . number_format($feeAmount, 0, ',', '.') . 'đ.'
            . ' Tổng booking sau phụ thu: ' . number_format($currentTotal, 0, ',', '.') . 'đ.'
        );

        return back()->with('success', 'Đã giữ phòng đến ' . $extendedHoldAt->format('H:i d/m/Y')
            . ' và ghi nhận phụ thu ' . number_format($feeAmount, 0, ',', '.') . 'đ.');
    }

    public function cancelLateArrival(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'confirmed' || $booking->actual_check_in) {
            return back()->with('error', 'Chỉ có thể hủy no-show với booking đã xác nhận nhưng khách chưa check-in.');
        }

        if (!$booking->usesLateArrivalNoShowPolicy()) {
            return back()->with('error', 'Booking này không thuộc diện khách đến muộn theo giờ G nên không được hủy no-show.');
        }

        if (!$booking->check_in_at) {
            return back()->with('error', 'Booking chưa có giờ nhận phòng dự kiến.');
        }

        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInDate = \Carbon\Carbon::parse($booking->check_in_date, 'Asia/Ho_Chi_Minh');
        $noShowStartAt = $checkInDate->copy()->setTime(14, 0, 0);
        $noShowEndAt = $checkInDate->copy()->setTime(18, 0, 0);

        if ($nowVn->lt($noShowStartAt) || $nowVn->greaterThanOrEqualTo($noShowEndAt)) {
            return back()->with('error', 'No-show chỉ được xử lý từ 14:00 đến trước 18:00 ngày nhận phòng.');
        }

        $holdLimitAt = $this->getLateArrivalHoldLimitAt($booking);
        $policy = app(\App\Services\BookingFinancialService::class)->cancellationPolicy($booking);

        try {
            app(\App\Services\BookingCancellationService::class)->cancel(
                $booking,
                $policy,
                Auth::id(),
                'receptionist_no_show_cancelled',
                'Lễ tân'
            );
            $booking->refresh()->update([
                'late_arrival_policy' => 'Lễ tân xác nhận khách không đến và hủy no-show lúc ' . $nowVn->format('d/m/Y H:i')
                    . '. Giờ G của booking: ' . ($holdLimitAt ? $holdLimitAt->format('H:i d/m/Y') : 'không xác định') . '.',
            ]);

            return back()->with('success', 'Đã hủy đơn do khách xác nhận không đến. Tiền cọc không hoàn lại và phòng được mở bán lại.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi khi hủy no-show: ' . $e->getMessage());
        }
    }


    private function prepareRoomsForCheckIn(Booking $booking, \Carbon\Carbon $actualCheckInAt): string
    {
        $messages = [];

        $excludeRoomIds = $booking->bookingRooms
            ->pluck('room_id')
            ->filter()
            ->values()
            ->toArray();

        foreach ($booking->bookingRooms as $bookingRoom) {
            $room = $bookingRoom->room;

            if (!$room) {
                throw new \Exception('Có phòng trong booking không còn tồn tại. Vui lòng kiểm tra lại dữ liệu gán phòng.');
            }

            $status = $room->status ?? null;

            if (in_array($status, ['available', 'reserved'])) {
                continue;
            }

            $statusText = mb_strtolower($this->getRoomStatusLabel($status));

            $replacementRoom = Room::where('room_category_id', $room->room_category_id)
                ->where('status', 'available')
                ->whereNotIn('id', $excludeRoomIds)
                ->availableForPeriod(
                    $actualCheckInAt,
                    $booking->check_out_at,
                    $booking->id,
                    $booking->cleaning_buffer_minutes ?? 0
                )
                ->orderBy('floor_number')
                ->orderBy('room_number')
                ->first();

            if (!$replacementRoom) {
                throw new \Exception(
                    'Chưa thể check-in vì phòng '
                    . ($room->room_number ?? '---')
                    . ' đang '
                    . $statusText
                    . ', và hiện không còn phòng trống sạch cùng hạng để đổi. '
                    . 'Khách cần đợi buồng phòng dọn/kiểm tra xong rồi mới xác nhận check-in.'
                );
            }

            $oldRoomNumber = $room->room_number;
            $newRoomNumber = $replacementRoom->room_number;

            $bookingRoom->update([
                'room_id' => $replacementRoom->id,
                'surcharge_reason' => trim(
                    ($bookingRoom->surcharge_reason ? $bookingRoom->surcharge_reason . ' | ' : '')
                    . 'Đổi từ phòng '
                    . $oldRoomNumber
                    . ' sang phòng '
                    . $newRoomNumber
                    . ' khi check-in sớm vì phòng cũ chưa sẵn sàng.'
                ),
            ]);

            $messages[] = 'Phòng '
                . $oldRoomNumber
                . ' đang '
                . $statusText
                . ', hệ thống đã đổi sang phòng '
                . $newRoomNumber
                . ' cùng hạng để check-in.';

            $excludeRoomIds[] = $replacementRoom->id;
        }

        return count($messages) > 0 ? implode(' ', $messages) : '';
    }
    private function handleEarlyCheckInFee(
        Booking $booking,
        array $data,
        \Carbon\Carbon $actualCheckInAt
    ): string {
        if ($booking->booking_type === 'hourly') {
            return '';
        }

        if (!$booking->check_in_at) {
            return '';
        }

        $plannedCheckInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $standardCheckInAt = $plannedCheckInAt->copy()->setTime(14, 0, 0);

        if ($actualCheckInAt->toDateString() < $plannedCheckInAt->toDateString()) {
            throw new \Exception(
                'Khách đến trước ngày booking '
                . $plannedCheckInAt->format('d/m/Y')
                . '. Đây là đổi ngày lưu trú hoặc tạo booking mới, không phải check-in sớm trong cùng ngày.'
            );
        }

        if (!$actualCheckInAt->isSameDay($plannedCheckInAt) || $actualCheckInAt->greaterThanOrEqualTo($standardCheckInAt)) {
            return '';
        }

        $policy = $this->calculateEarlyCheckInFee($booking, $actualCheckInAt);

        $earlyFeeAlreadyIncluded = $booking->serviceItems()
            ->where('billing_status', 'confirmed')
            ->where(function ($query) {
                $query->where('type', 'early_checkin_fee')
                    ->orWhere(function ($policyFee) {
                        $policyFee->where('type', 'policy_violation_fee')
                            ->where(function ($text) {
                                $text->where('name', 'like', '%nhận phòng%')
                                    ->orWhere('note', 'like', '%Check-in%')
                                    ->orWhere('note', 'like', '%nhận phòng sớm%');
                            });
                    });
            })
            ->exists();

        if ($earlyFeeAlreadyIncluded) {
            return 'Phụ thu check-in sớm đã được tính khi tạo booking; không thu trùng lần nữa.';
        }

        if ($policy['amount'] > 0 && ($data['early_check_in_action'] ?? null) !== 'accept_fee') {
            throw new \Exception(
                'Khách check-in sớm lúc '
                . $actualCheckInAt->format('H:i')
                . ', phát sinh phụ thu '
                . number_format($policy['amount'], 0, ',', '.')
                . 'đ. Vui lòng xác nhận khách đồng ý phụ thu trước khi check-in.'
            );
        }

        if ($policy['amount'] > 0) {
            $earlyCheckInService = Service::firstOrCreate(
                [
                    'name' => 'Phụ thu check-in sớm',
                    'type' => 'early_checkin_fee',
                ],
                [
                    'service_group' => 'other',
                    'price' => 0,
                    'unit' => 'lần',
                    'description' => 'Phụ thu khi khách nhận phòng sớm trước giờ check-in chuẩn.',
                    'status' => 'active',
                ]
            );

            BookingServiceItem::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'name' => 'Phụ thu check-in sớm',
                    'type' => 'early_checkin_fee',
                ],
                [
                    'service_id' => $earlyCheckInService->id,
                    'unit_price' => $policy['amount'],
                    'quantity' => 1,
                    'used_quantity' => 1,
                    'billing_status' => 'confirmed',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => $actualCheckInAt,
                    'confirm_note' => $policy['policy_text'],
                    'total' => $policy['amount'],
                    'note' => 'Check-in sớm lúc '
                        . $actualCheckInAt->format('d/m/Y H:i')
                        . '. Đến sớm '
                        . $policy['duration_text']
                        . '. '
                        . $policy['policy_text'],
                ]
            );

            $booking->estimated_total = (float) $booking->estimated_total + $policy['amount'];
        }

        return 'Check-in sớm trong cùng ngày lúc '
            . $actualCheckInAt->format('d/m/Y H:i')
            . ', sớm hơn giờ chuẩn '
            . $policy['duration_text']
            . '. '
            . $policy['policy_text']
            . ' Phụ thu: '
            . number_format($policy['amount'], 0, ',', '.')
            . 'đ.';
    }
    private function calculateEarlyCheckInFee(Booking $booking, \Carbon\Carbon $actualCheckInAt): array
    {
        $plannedCheckInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $standardCheckInAt = $plannedCheckInAt->copy()->setTime(14, 0, 0);
        $earlyMinutes = max(0, $actualCheckInAt->diffInMinutes($standardCheckInAt));
        $earlyHoursOnly = intdiv($earlyMinutes, 60);
        $earlyRemainMinutes = $earlyMinutes % 60;
        $durationText = $earlyHoursOnly . ' giờ'
            . ($earlyRemainMinutes > 0 ? ' ' . $earlyRemainMinutes . ' phút' : '');

        $basePrice = $booking->bookingRooms->sum(fn ($bookingRoom) => (float) $bookingRoom->price_at_booking);
        if ($basePrice <= 0) {
            $basePrice = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
        }

        $policy = app(StayPricingPolicyService::class)->earlyCheckIn($actualCheckInAt, $basePrice);

        return [
            'percent' => $policy['percent'],
            'base_price' => $basePrice,
            'amount' => $policy['amount'],
            'policy_text' => $policy['policy_text'],
            'early_minutes' => $earlyMinutes,
            'duration_text' => $durationText,
        ];
    }




    private function getRoomStatusLabel(?string $status): string
    {
        return [
            'available' => 'Trống',
            'reserved' => 'Đã giữ',
            'occupied' => 'Đang ở',
            'cleaning' => 'Cần dọn',
            'inspection' => 'Chờ kiểm tra',
            'maintenance' => 'Bảo trì',
        ][$status] ?? 'Chưa rõ trạng thái';
    }

    private function guardCanAccessBooking(Booking $booking): void
    {
        $user = Auth::user();

        abort_unless($user && in_array($user->role, [
            'super_admin',
            'manager',
            'receptionist_lead',
            'receptionist',
        ], true), 403, 'Bạn không có quyền xử lý booking này.');
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
}
