<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\RoomInspection;
use Illuminate\Support\Facades\DB;
use App\Models\BookingRoom;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Models\RoomCategory;
use App\Models\Service;
use App\Models\BookingServiceItem;
use App\Models\BookingLog;
use App\Models\BookingPayment;
use App\Models\PromotionRoomUpgradeOffer;
use App\Services\PromotionService;
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

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Chỉ có thể nhận phòng với booking đã xác nhận.');
        }

        $data = $request->validate([
            'actual_adult_count' => 'required|integer|min:1',
            'actual_child_count' => 'nullable|integer|min:0',
            'check_in_cccd' => ['required', 'regex:/^[0-9]{12}$/'],
            'cccd_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            'over_capacity_action' => 'nullable|in:extra_fee',
            'actual_baby_count' => 'nullable|integer|min:0',

            'extra_service_ids' => 'nullable|array',
            'extra_service_ids.*' => 'nullable|exists:services,id',
            'extra_quantities' => 'nullable|array',
            'extra_quantities.*' => 'nullable|integer|min:1',
            'extra_fee_notes' => 'nullable|array',
            'extra_fee_notes.*' => 'nullable|string|max:1000',

            'early_check_in_action' => 'nullable|in:accept_fee',
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

        $storedCccd = preg_replace('/\D+/', '', (string) ($booking->customer->cccd ?? ''));
        $scannedCccd = preg_replace('/\D+/', '', (string) $data['check_in_cccd']);

        if ($storedCccd === '' || strlen($storedCccd) !== 12) {
            return back()->withInput()->with('error', 'Booking chưa có CCCD hợp lệ của khách đứng tên. Vui lòng cập nhật thông tin khách trước khi check-in.');
        }

        if (!hash_equals($storedCccd, $scannedCccd)) {
            return back()->withInput()->with('error', 'CCCD vừa quét không trùng với CCCD đã dùng để đặt phòng. Không thể check-in booking này.');
        }

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
            || $actualChildCount > $currentChildCapacity;

        if ($isOverCapacity && empty($data['over_capacity_action'])) {
            return back()->with('error', 'Số khách thực tế vượt sức chứa. Vui lòng chọn cách xử lý.');
        }

        DB::beginTransaction();

        try {
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

            $booking->load('bookingRooms.room');

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update([
                        'status' => 'occupied',
                    ]);

                    \App\Models\RoomActionLog::create([
                        'room_id' => $bookingRoom->room->id,
                        'user_id' => Auth::id(),
                        'action_type' => 'check_in',
                        'action_time' => now(),
                        'note' => 'Khách check-in từ booking #' . $booking->booking_code,
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

            return back()->with('extend_stay_preview', [
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
            ]);
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
                    'status' => 'available',
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

            if ($extraRoomTotal > 0) {
                $extendStayService = Service::firstOrCreate(
                    [
                        'name' => 'Phụ thu gia hạn lưu trú',
                        'type' => 'policy_violation_fee',
                    ],
                    [
                        'service_group' => 'other',
                        'price' => 0,
                        'unit' => 'lần',
                        'description' => 'Phụ thu khi khách gia hạn thêm giờ hoặc thêm đêm.',
                        'status' => 'active',
                    ]
                );

                BookingServiceItem::create([
                    'booking_id' => $booking->id,
                    'service_id' => $extendStayService->id,
                    'name' => 'Phụ thu gia hạn lưu trú',
                    'type' => 'violation_fee',
                    'unit_price' => $extraRoomTotal,
                    'quantity' => 1,
                    'used_quantity' => 1,
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
            $booking->estimated_total = (float) $booking->estimated_total + $extraRoomTotal;
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
                . ' Phụ thu: '
                . number_format($extraRoomTotal, 0, ',', '.')
                . 'đ.'
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

        if ($newCheckOutAt->lessThanOrEqualTo($oldCheckOutAt)) {
            throw new \Exception(
                'Không thể gia hạn. Thời gian trả phòng mới phải sau thời gian trả phòng hiện tại '
                . $oldCheckOutAt->format('d/m/Y H:i') . '.'
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
            ->addMinutes($booking->cleaning_buffer_minutes ?? 60);

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
            ->whereNotIn('status', ['maintenance', 'cleaning', 'inspection'])
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
            if ($extraHours <= 3) {
                $extraRoomTotal = $oneNightTotal * 0.3;
                $extendPolicyText = 'Booking qua đêm, gia hạn thêm ' . $extraHours . ' giờ, phụ thu 30% giá/đêm.';
            } elseif ($extraHours <= 6) {
                $extraRoomTotal = $oneNightTotal * 0.5;
                $extendPolicyText = 'Booking qua đêm, gia hạn thêm ' . $extraHours . ' giờ, phụ thu 50% giá/đêm.';
            } else {
                $extraRoomTotal = $oneNightTotal;
                $extendPolicyText = 'Booking qua đêm, gia hạn thêm ' . $extraHours . ' giờ, tính thêm 1 đêm.';
            }
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
        ]);

        $booking->load('bookingRooms.room.category', 'roomCategory');

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
            'upgrade_payment_action' => 'nullable|in:guest_pay,incident_support,paid_upsell',
            'room_upgrade_promotion_code' => 'nullable|string|max:50',
        ]);

        $booking->load('bookingRooms.room.category', 'roomCategory');

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
            'upgrade_payment_action' => 'nullable|in:guest_pay,incident_support,paid_upsell',
            'room_upgrade_promotion_code' => 'nullable|string|max:50',
        ]);

        $booking->load('bookingRooms.room.category', 'roomCategory');

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
        $quantities = $data['extra_quantities'] ?? [];
        $notes = $data['extra_fee_notes'] ?? [];

        $validRows = array_filter($serviceIds, function ($serviceId) {
            return !empty($serviceId);
        });

        if (count($validRows) == 0) {
            throw new \Exception('Vui lòng chọn ít nhất một khoản phụ thu.');
        }

        $totalExtraFee = 0;
        $feeDescriptions = [];

        foreach ($serviceIds as $index => $serviceId) {
            if (empty($serviceId)) {
                continue;
            }

            $service = Service::where('id', $serviceId)
                ->where('type', 'occupancy_fee')
                ->where('status', 'active')
                ->first();

            if (!$service) {
                throw new \Exception('Có khoản phụ thu không hợp lệ hoặc đã bị ẩn.');
            }

            $quantity = (int) ($quantities[$index] ?? 1);
            $quantity = max(1, $quantity);

            $unitPrice = (float) $service->price;
            $total = $unitPrice * $quantity;

            BookingServiceItem::create([
                'booking_id' => $booking->id,
                'service_id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'total' => $total,
                'note' => $notes[$index] ?? 'Phụ thu phát sinh khi check-in.',
            ]);

            $totalExtraFee += $total;

            $feeDescriptions[] = $service->name
                . ' x '
                . $quantity
                . ': '
                . number_format($total, 0, ',', '.')
                . 'đ';
        }

        $booking->estimated_total += $totalExtraFee;

        return 'Đã thu phụ phí phát sinh khi check-in: '
            . implode('; ', $feeDescriptions)
            . '. Tổng phụ thu: '
            . number_format($totalExtraFee, 0, ',', '.')
            . 'đ.';
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
            $room->update([
                'status' => $booking->status === 'checked_in' ? 'occupied' : 'reserved',
            ]);

            $booking->estimated_total += $category->price * $nightCount;
        }

        $booking->room_quantity += $quantity;

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

        $newCategory = RoomCategory::where('status', 'active')
            ->find($data['new_room_category_id']);

        if (!$newCategory) {
            throw new \Exception('Hạng phòng mới không hợp lệ.');
        }

        $roomQuantity = max(1, (int) $booking->room_quantity);

        $newAdultCapacity = $newCategory->adult_capacity * $roomQuantity;
        $newChildCapacity = $newCategory->child_capacity * $roomQuantity;

        if (
            $actualAdultCount !== null
            && $actualChildCount !== null
            && ($actualAdultCount > $newAdultCapacity || $actualChildCount > $newChildCapacity)
        ) {
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

        $oldRoomTotal = $booking->bookingRooms->sum(function ($bookingRoom) use ($nightCount) {
            return (float) $bookingRoom->price_at_booking * $nightCount;
        });

        foreach ($booking->bookingRooms as $bookingRoom) {
            if ($bookingRoom->room) {
                $bookingRoom->room->update([
                    'status' => 'available',
                ]);
            }
        }

        BookingRoom::where('booking_id', $booking->id)->delete();

        $newRoomTotal = 0;

        foreach ($newRooms as $room) {
            BookingRoom::create([
                'booking_id' => $booking->id,
                'room_id' => $room->id,
                'adult_count' => 0,
                'child_count' => 0,
                'price_at_booking' => $newCategory->price,
                'surcharge' => 0,
                'surcharge_reason' => $data['change_category_reason'] ?? 'Đổi toàn bộ booking sang hạng khác.',
                'created_at' => now(),
            ]);

            $room->update([
                'status' => $booking->status === 'checked_in' ? 'occupied' : 'reserved',
            ]);

            $newRoomTotal += (float) $newCategory->price * $nightCount;
        }

        $difference = $newRoomTotal - $oldRoomTotal;

        $oldCategory = $booking->roomCategory;
        $booking->room_category_id = $newCategory->id;

        $pricingMessage = $this->applyRoomUpgradeDifference(
            $booking,
            $data,
            $difference,
            $oldCategory?->id ?? $booking->getOriginal('room_category_id'),
            $oldCategory?->name ?? 'Hạng cũ',
            $oldRoomTotal / max(1, $nightCount * $roomQuantity),
            $newCategory->id,
            $newCategory->name,
            (float) $newCategory->price,
            $nightCount,
            $roomQuantity,
            null,
            null,
            null
        );

        return 'Đã đổi toàn bộ booking sang hạng phòng '
            . $newCategory->name
            . '. Chênh lệch tiền phòng: '
            . number_format($difference, 0, ',', '.')
            . 'đ. '
            . $pricingMessage
            . ' Lý do: '
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
        $oldCategoryName = $oldRoom->category->name ?? 'không xác định';

        $newCategory = RoomCategory::where('status', 'active')
            ->find($data['new_room_category_id']);

        if (!$newCategory) {
            throw new \Exception('Hạng phòng mới không hợp lệ.');
        }

        $newRooms = $this->findAvailableRoomsForCheckIn(
            $newCategory->id,
            1,
            $booking->check_in_at,
            $booking->check_out_at,
            false,
            $booking
        );

        if ($newRooms->count() < 1) {
            throw new \Exception('Không còn phòng trống thuộc hạng phòng mới trong thời gian booking.');
        }

        $newRoom = $newRooms->first();

        $nightCount = $this->getNightCount($booking);

        $oldRoomPriceAtBooking = (float) $bookingRoom->price_at_booking;
        $oldRoomTotal = $oldRoomPriceAtBooking * $nightCount;
        $newRoomTotal = (float) $newCategory->price * $nightCount;
        $difference = $newRoomTotal - $oldRoomTotal;

        $bookingRoom->update([
            'room_id' => $newRoom->id,
            'price_at_booking' => $newCategory->price,
            'surcharge_reason' => $data['change_category_reason'] ?? 'Đổi 1 phòng sang hạng khác.',
        ]);

        $oldRoom->update([
            'status' => 'available',
        ]);

        $newRoom->update([
            'status' => $booking->status === 'checked_in' ? 'occupied' : 'reserved',
        ]);

        if ((int) $booking->room_quantity === 1) {
            $booking->room_category_id = $newCategory->id;
        }

        $pricingMessage = $this->applyRoomUpgradeDifference(
            $booking,
            $data,
            $difference,
            $oldRoom->category->id ?? $booking->room_category_id,
            $oldCategoryName,
            $oldRoomPriceAtBooking,
            $newCategory->id,
            $newCategory->name,
            (float) $newCategory->price,
            $nightCount,
            1,
            $bookingRoom->id,
            $oldRoom->id,
            $newRoom->id
        );

        return 'Đã đổi phòng '
            . $oldRoom->room_number
            . ' từ hạng '
            . $oldCategoryName
            . ' sang phòng '
            . $newRoom->room_number
            . ' hạng '
            . $newCategory->name
            . '. Chênh lệch tiền phòng: '
            . number_format($difference, 0, ',', '.')
            . 'đ. '
            . $pricingMessage
            . ' Lý do: '
            . ($data['change_category_reason'] ?? 'Khách yêu cầu đổi 1 phòng.');
    }



    private function applyRoomUpgradeDifference(
        Booking $booking,
        array $data,
        float $difference,
        ?int $oldCategoryId,
        string $oldCategoryName,
        float $oldRoomPrice,
        int $newCategoryId,
        string $newCategoryName,
        float $newRoomPrice,
        int $nightCount,
        int $roomQuantity,
        ?int $bookingRoomId = null,
        ?int $oldRoomId = null,
        ?int $newRoomId = null
    ): string {
        $difference = round($difference, 0);
        $action = $data['upgrade_payment_action'] ?? 'guest_pay';
        $reason = trim((string) ($data['change_category_reason'] ?? ''));

        $currentDiscount = (float) ($booking->discount_amount ?? 0);
        $currentSubtotal = (float) ($booking->subtotal_amount ?? 0);

        if ($currentSubtotal <= 0) {
            $currentSubtotal = (float) $booking->estimated_total + $currentDiscount;
        }

        if ($difference <= 0) {
            $booking->subtotal_amount = max(0, $currentSubtotal + $difference);
            $booking->estimated_total = max(0, (float) $booking->estimated_total + $difference);
            $booking->save();

            if ($difference < 0) {
                return 'Hạng mới rẻ hơn, hệ thống đã giảm tổng tiền phòng ' . number_format(abs($difference), 0, ',', '.') . 'đ.';
            }

            return 'Không phát sinh chênh lệch tiền phòng.';
        }

        $coveredAmount = 0;
        $promotion = null;
        $offer = null;
        $code = strtoupper(trim((string) ($data['room_upgrade_promotion_code'] ?? '')));

        if (in_array($action, [
            PromotionRoomUpgradeOffer::KIND_INCIDENT_SUPPORT,
            PromotionRoomUpgradeOffer::KIND_PAID_UPSELL,
        ], true)) {
            if ($code === '') {
                throw new \Exception('Vui lòng nhập mã nâng hạng phòng.');
            }

            if ($booking->bookingPromotions()->where('code_snapshot', $code)->exists()) {
                throw new \Exception('Booking đã áp dụng mã ' . $code . '.');
            }

            $promotionResult = app(PromotionService::class)->findRoomUpgradeOffer(
                $code,
                (int) $oldCategoryId,
                $newCategoryId,
                $action,
                [
                    'customer_id' => $booking->customer_id,
                    'subtotal_amount' => $currentSubtotal + $difference,
                    'check_in_at' => $booking->check_in_at,
                    'check_out_at' => $booking->check_out_at,
                    'night_count' => $nightCount,
                    'room_quantity' => $booking->room_quantity,
                ],
                'admin',
                $reason
            );

            if (!$promotionResult['ok']) {
                throw new \Exception($promotionResult['message']);
            }

            $promotion = $promotionResult['promotion'];
            $offer = $promotionResult['offer'];
            $coveredAmount = app(PromotionService::class)->calculateRoomUpgradeCoverAmount($offer, $difference);

            if ($action === PromotionRoomUpgradeOffer::KIND_INCIDENT_SUPPORT) {
                $coveredAmount = $difference;
            }
        }

        $coveredAmount = min(max(0, $coveredAmount), $difference);
        $guestExtraAmount = max(0, $difference - $coveredAmount);

        $booking->subtotal_amount = max(0, $currentSubtotal + $difference);
        $booking->discount_amount = max(0, $currentDiscount + $coveredAmount);
        $booking->estimated_total = max(0, (float) $booking->estimated_total + $guestExtraAmount);
        $booking->save();

        if ($promotion && $offer && $coveredAmount > 0) {
            app(PromotionService::class)->storeRoomUpgradeUsage(
                $booking,
                $promotion,
                $offer,
                [
                    'booking_room_id' => $bookingRoomId,
                    'old_room_id' => $oldRoomId,
                    'new_room_id' => $newRoomId,
                    'old_room_category_id' => $oldCategoryId,
                    'old_room_category_name_snapshot' => $oldCategoryName,
                    'old_room_price_snapshot' => $oldRoomPrice,
                    'new_room_category_id' => $newCategoryId,
                    'new_room_category_name_snapshot' => $newCategoryName,
                    'new_room_price_snapshot' => $newRoomPrice,
                    'night_count' => $nightCount,
                    'room_quantity' => $roomQuantity,
                    'original_difference_amount' => $difference,
                    'covered_amount' => $coveredAmount,
                    'guest_extra_amount' => $guestExtraAmount,
                    'note' => $data['change_category_reason'] ?? null,
                ],
                'admin',
                $reason,
                Auth::id()
            );
        }

        if ($action === PromotionRoomUpgradeOffer::KIND_INCIDENT_SUPPORT) {
            return 'Khách sạn hỗ trợ toàn bộ tiền chênh ' . number_format($coveredAmount, 0, ',', '.') . 'đ, khách không trả thêm.';
        }

        if ($action === PromotionRoomUpgradeOffer::KIND_PAID_UPSELL) {
            return 'Mã điều kiện nâng hạng hỗ trợ ' . number_format($coveredAmount, 0, ',', '.') . 'đ, khách trả thêm ' . number_format($guestExtraAmount, 0, ',', '.') . 'đ.';
        }

        return 'Khách trả toàn bộ tiền chênh ' . number_format($guestExtraAmount, 0, ',', '.') . 'đ.';
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
            ->availableForPeriod(
                $checkInAt,
                $checkOutAt,
                $booking?->id
            );

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
        ], [
            'new_check_in_date.required' => 'Vui lòng chọn ngày nhận phòng mới.',
            'new_check_in_date.date' => 'Ngày nhận phòng mới không hợp lệ.',
            'new_check_in_time.date_format' => 'Giờ nhận phòng mới phải theo định dạng 24 giờ, ví dụ 07:00 hoặc 14:00.',
            'new_check_out_date.required' => 'Vui lòng chọn ngày trả phòng mới.',
            'new_check_out_date.date' => 'Ngày trả phòng mới không hợp lệ.',
            'new_check_out_time.date_format' => 'Giờ trả phòng mới phải theo định dạng 24 giờ, ví dụ 12:00 hoặc 14:00.',
        ]);

        $oldCheckInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $oldCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        $newCheckInTime = $data['new_check_in_time'] ?? $oldCheckInAt->format('H:i');
        $newCheckOutTime = $data['new_check_out_time'] ?? $oldCheckOutAt->format('H:i');

        $newCheckInAt = \Carbon\Carbon::parse($data['new_check_in_date'] . ' ' . $newCheckInTime . ':00', 'Asia/Ho_Chi_Minh');
        $newCheckOutAt = \Carbon\Carbon::parse($data['new_check_out_date'] . ' ' . $newCheckOutTime . ':00', 'Asia/Ho_Chi_Minh');

        if ($newCheckOutAt->lessThanOrEqualTo($newCheckInAt)) {
            return back()->with('error', 'Thời gian trả phòng mới phải sau thời gian nhận phòng mới.');
        }

        if ($newCheckOutAt->lessThanOrEqualTo(\Carbon\Carbon::now('Asia/Ho_Chi_Minh'))) {
            return back()->with('error', 'Thời gian trả phòng mới phải sau thời điểm hiện tại.');
        }

        $booking->load([
            'bookingRooms.room.category',
            'roomCategory',
        ]);

        if ($booking->bookingRooms->count() == 0) {
            return back()->with('error', 'Booking này chưa được gán phòng nên không thể đổi ngày lưu trú tự động.');
        }

        DB::beginTransaction();

        try {
            $oldNightCount = max(
                1,
                $oldCheckInAt->copy()->startOfDay()->diffInDays($oldCheckOutAt->copy()->startOfDay())
            );
            $newNightCount = max(
                1,
                $newCheckInAt->copy()->startOfDay()->diffInDays($newCheckOutAt->copy()->startOfDay())
            );

            $oneNightRoomTotal = $booking->bookingRooms->sum(function ($bookingRoom) {
                return (float) $bookingRoom->price_at_booking;
            });

            if ($oneNightRoomTotal <= 0) {
                $oneNightRoomTotal = (float) ($booking->roomCategory->price ?? 0)
                    * max(1, (int) $booking->room_quantity);
            }

            $roomDelta = round(($oneNightRoomTotal * $newNightCount) - ($oneNightRoomTotal * $oldNightCount), 0);
            $usedReplacementRoomIds = [];
            $currentRoomIds = $booking->bookingRooms
                ->pluck('room_id')
                ->filter()
                ->values()
                ->toArray();
            $roomChangeMessages = [];

            foreach ($booking->bookingRooms as $bookingRoom) {
                $room = $bookingRoom->room;

                if (!$room) {
                    throw new \Exception('Có phòng trong booking không còn tồn tại. Vui lòng kiểm tra lại dữ liệu gán phòng.');
                }

                $conflictBookingRoom = $this->findConflictBookingRoom(
                    $room->id,
                    $booking->id,
                    $newCheckInAt,
                    $newCheckOutAt->copy()->addMinutes($booking->cleaning_buffer_minutes ?? 60)
                );

                if (!$conflictBookingRoom) {
                    $room->update([
                        'status' => $booking->status === 'confirmed' ? 'reserved' : $room->status,
                    ]);

                    continue;
                }

                $replacementRoom = Room::where('room_category_id', $room->room_category_id)
                    ->where('status', 'available')
                    ->whereNotIn('id', array_merge($currentRoomIds, $usedReplacementRoomIds))
                    ->availableForPeriod(
                        $newCheckInAt,
                        $newCheckOutAt,
                        $booking->id,
                        $booking->cleaning_buffer_minutes ?? 60
                    )
                    ->with('category')
                    ->orderBy('floor_number')
                    ->orderBy('room_number')
                    ->first();

                if (!$replacementRoom) {
                    $conflictBooking = $conflictBookingRoom->booking;

                    throw new \Exception(
                        'Không thể đổi ngày lưu trú. Phòng '
                        . ($room->room_number ?? '---')
                        . ' bị trùng với booking '
                        . ($conflictBooking->booking_code ?? '')
                        . ' từ '
                        . \Carbon\Carbon::parse($conflictBooking->check_in_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                        . ' đến '
                        . \Carbon\Carbon::parse($conflictBooking->check_out_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                        . ', và không còn phòng trống cùng hạng để đổi.'
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
                        . ' khi đổi ngày lưu trú do phòng cũ bị trùng lịch.'
                    ),
                ]);

                $room->update([
                    'status' => 'available',
                ]);

                $replacementRoom->update([
                    'status' => $booking->status === 'confirmed' ? 'reserved' : $replacementRoom->status,
                ]);

                $usedReplacementRoomIds[] = $replacementRoom->id;
                $roomChangeMessages[] = 'Đổi phòng ' . $oldRoomNumber . ' → ' . $newRoomNumber . ' cùng hạng do trùng lịch.';
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $roomChangeText = count($roomChangeMessages) > 0
                ? ' ' . implode(' ', $roomChangeMessages)
                : '';

            $booking->check_in_date = $newCheckInAt->toDateString();
            $booking->check_out_date = $newCheckOutAt->toDateString();
            $booking->check_in_at = $newCheckInAt;
            $booking->check_out_at = $newCheckOutAt;
            $booking->subtotal_amount = max(0, (float) $booking->subtotal_amount + $roomDelta);
            $booking->estimated_total = max(0, (float) $booking->estimated_total + $roomDelta);
            $booking->late_arrival_fee = 0;
            $booking->late_arrival_hours = null;
            $booking->late_arrival_policy = null;
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
                . '. Chênh lệch tiền phòng: '
                . number_format($roomDelta, 0, ',', '.')
                . 'đ.'
                . $roomChangeText;

            $booking->save();

            $this->addBookingLog(
                $booking,
                'change_stay_dates',
                'Đổi ngày lưu trú từ '
                . $oldCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $oldCheckOutAt->format('d/m/Y H:i')
                . ' sang '
                . $newCheckInAt->format('d/m/Y H:i')
                . ' → '
                . $newCheckOutAt->format('d/m/Y H:i')
                . '. Chênh lệch tiền phòng: '
                . number_format($roomDelta, 0, ',', '.')
                . 'đ.'
                . $roomChangeText
            );

            DB::commit();

            return back()->with(
                'success',
                'Đã đổi ngày lưu trú thành công. Chênh lệch tiền phòng: '
                . number_format($roomDelta, 0, ',', '.')
                . 'đ.'
                . $roomChangeText
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi đổi ngày lưu trú: ' . $e->getMessage());
        }
    }


    public function requestInspection(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Chỉ có thể yêu cầu kiểm tra khi khách đang ở.');
        }

        $booking->load('bookingRooms.room');

        DB::beginTransaction();

        try {
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
                        'has_damage'   => false,
                        'damage_total' => 0,
                    ]
                );
            }


            $oldNote = $booking->note ? $booking->note . "\n" : '';

            $booking->update([
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

    public function checkOut(Request $request, Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Chỉ có thể check-out booking đang ở.');
        }

        $data = $request->validate([
            'checkout_late_fee_confirm' => 'nullable|in:1',
            'checkout_extra_name' => 'nullable|string|max:150',
            'checkout_extra_amount' => 'nullable|numeric|min:0',
            'checkout_extra_note' => 'nullable|string|max:1000',
            'checkout_payment_method' => 'nullable|in:cash,bank_transfer',
            'checkout_payment_confirm' => 'nullable|in:1',
        ], [
            'checkout_extra_amount.numeric' => 'Số tiền phí phát sinh khi check-out không hợp lệ.',
            'checkout_extra_amount.min' => 'Số tiền phí phát sinh khi check-out không được âm.',
            'checkout_payment_method.in' => 'Phương thức thanh toán khi check-out không hợp lệ.',
        ]);

        $booking->load([
            'bookingRooms.room.category',
            'roomCategory',
            'roomInspections.items',
            'serviceItems',
        ]);

        if ($booking->roomInspections->count() == 0) {
            return back()->with(
                'error',
                'Không thể check-out vì chưa tạo phiếu kiểm tra phòng.'
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
            $feeMessages = [];

            if ($lateCheckout['amount'] > 0) {
                $lateCheckoutService = Service::firstOrCreate(
                    [
                        'name' => 'Phụ thu check-out muộn',
                        'type' => 'policy_violation_fee',
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
                        'type' => 'violation_fee',
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

            $manualFeeAmount = (float) ($data['checkout_extra_amount'] ?? 0);

            if ($manualFeeAmount > 0) {
                $manualFeeName = trim($data['checkout_extra_name'] ?? '');

                if ($manualFeeName === '') {
                    $manualFeeName = 'Phí phát sinh khi check-out';
                }

                $manualFeeService = Service::firstOrCreate(
                    [
                        'name' => $manualFeeName,
                        'type' => 'policy_violation_fee',
                    ],
                    [
                        'service_group' => 'other',
                        'price' => 0,
                        'unit' => 'lần',
                        'description' => 'Khoản phí phát sinh được lễ tân ghi nhận khi check-out.',
                        'status' => 'active',
                    ]
                );

                BookingServiceItem::create([
                    'booking_id' => $booking->id,
                    'service_id' => $manualFeeService->id,
                    'name' => $manualFeeName,
                    'type' => 'violation_fee',
                    'unit_price' => $manualFeeAmount,
                    'quantity' => 1,
                    'used_quantity' => 1,
                    'billing_status' => 'confirmed',
                    'confirmed_by' => Auth::id(),
                    'confirmed_at' => $actualCheckOutAt,
                    'confirm_note' => $data['checkout_extra_note'] ?? null,
                    'total' => $manualFeeAmount,
                    'note' => $data['checkout_extra_note'] ?? 'Phí phát sinh khi check-out.',
                ]);

                $feeMessages[] = $manualFeeName . ': ' . number_format($manualFeeAmount, 0, ',', '.') . 'đ.';
            }

            $booking = Booking::where('id', $booking->id)
                ->lockForUpdate()
                ->with([
                    'bookingRooms.room.category',
                    'roomCategory',
                    'roomInspections.items',
                    'serviceItems',
                ])
                ->firstOrFail();

            $roomBaseTotal = $this->getCheckoutRoomBaseTotal($booking);
            $serviceItemTotal = (float) BookingServiceItem::where('booking_id', $booking->id)
                ->whereNotIn('billing_status', ['unused', 'cancelled'])
                ->sum('total');
            $approvedInspectionTotal = $this->getApprovedInspectionTotal($booking);
            $finalTotal = round($roomBaseTotal + $serviceItemTotal + $approvedInspectionTotal, 0);

            $paidBeforeCheckout = (float) $booking->deposit_amount;
            $remainingTotal = max(0, $finalTotal - $paidBeforeCheckout);
            $paymentMethod = $data['checkout_payment_method'] ?? null;
            $checkoutPayment = null;

            if ($remainingTotal > 0) {
                if (empty($paymentMethod)) {
                    throw new \Exception(
                        'Booking còn '
                        . number_format($remainingTotal, 0, ',', '.')
                        . 'đ chưa thanh toán. Vui lòng kiểm tra khách đã thanh toán ngoài thực tế và chọn phương thức thanh toán trước khi check-out.'
                    );
                }

                if (($data['checkout_payment_confirm'] ?? null) !== '1') {
                    throw new \Exception(
                        'Booking còn '
                        . number_format($remainingTotal, 0, ',', '.')
                        . 'đ chưa thanh toán. Vui lòng tick xác nhận đã thu đủ khoản còn lại ngoài thực tế trước khi check-out.'
                    );
                }

                $checkoutPayment = BookingPayment::create([
                    'booking_id' => $booking->id,
                    'provider' => $paymentMethod,
                    'txn_ref' => $this->generateCheckoutPaymentTxnRef($booking, $paymentMethod),
                    'amount' => $remainingTotal,
                    'status' => 'success',
                    'payment_type' => 'full_100',
                    'paid_at' => $actualCheckOutAt,
                    'raw_response' => [
                        'source' => 'checkout',
                        'method' => $paymentMethod,
                        'type' => 'remaining_at_checkout',
                        'staff_id' => Auth::id(),
                        'note' => 'Lễ tân xác nhận khách đã thanh toán khoản còn lại khi check-out.',
                    ],
                ]);

                $paidAfterCheckout = $paidBeforeCheckout + $remainingTotal;
            } else {
                $paidAfterCheckout = $paidBeforeCheckout;
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $feeText = count($feeMessages) > 0
                ? ' Phí phát sinh: ' . implode(' ', $feeMessages)
                : ' Không phát sinh phụ thu check-out.';

            $paymentText = $remainingTotal > 0
                ? ' Thu thêm khi check-out: '
                    . number_format($remainingTotal, 0, ',', '.')
                    . 'đ bằng '
                    . $this->getCheckoutPaymentMethodLabel($paymentMethod)
                    . '. Mã giao dịch: '
                    . ($checkoutPayment->txn_ref ?? '---')
                    . '.'
                : ' Khách đã thanh toán đủ trước khi check-out, không cần thu thêm.';

            $booking->update([
                'status' => 'checked_out',
                'actual_check_out' => $actualCheckOutAt,
                'payment_status' => 'paid',
                'deposit_amount' => $paidAfterCheckout,
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
                    $bookingRoom->room->update([
                        'status' => 'cleaning',
                    ]);

                    \App\Models\RoomActionLog::create([
                        'room_id' => $bookingRoom->room->id,
                        'user_id' => Auth::id(),
                        'action_type' => 'check_out',
                        'action_time' => now(),
                        'note' => 'Khách trả phòng từ booking #' . $booking->booking_code . '. Chuyển sang trạng thái dọn dẹp.',
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
            ->where('type', 'violation_fee')
            ->where('name', 'Phụ thu check-out muộn')
            ->whereNotIn('billing_status', ['unused', 'cancelled'])
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

        if ($booking->booking_type === 'hourly') {
            if ($lateMinutes <= 15) {
                return [
                    'amount' => 0,
                    'late_minutes' => $lateMinutes,
                    'late_hours' => $lateHours,
                    'policy_text' => 'Booking theo giờ trả muộn không quá 15 phút, miễn phí.',
                    'note' => '',
                ];
            }

            $currentMinutes = max(
                60,
                \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')
                    ->diffInMinutes($plannedCheckOutAt)
            );
            $currentHours = max(1, $currentMinutes / 60);
            $roomBaseTotal = max(0, $this->getCheckoutRoomBaseTotal($booking));
            $hourlyRate = $roomBaseTotal > 0
                ? $roomBaseTotal / $currentHours
                : (float) ($booking->roomCategory->price ?? 0) / 24;
            $chargedHours = max(1, (int) ceil($lateMinutes / 60));
            $amount = round($hourlyRate * $chargedHours, 0);

            $policyText = 'Booking theo giờ trả muộn '
                . $lateHours
                . ' giờ, tính thêm '
                . $chargedHours
                . ' giờ theo đơn giá tạm tính '
                . number_format($hourlyRate, 0, ',', '.')
                . 'đ/giờ.';

            return [
                'amount' => $amount,
                'late_minutes' => $lateMinutes,
                'late_hours' => $lateHours,
                'policy_text' => $policyText,
                'note' => 'Giờ check-out dự kiến: '
                    . $plannedCheckOutAt->format('d/m/Y H:i')
                    . '. Giờ check-out thực tế: '
                    . $actualCheckOutAt->format('d/m/Y H:i')
                    . '. '
                    . $policyText,
            ];
        }

        if ($lateMinutes <= 60) {
            return [
                'amount' => 0,
                'late_minutes' => $lateMinutes,
                'late_hours' => $lateHours,
                'policy_text' => 'Booking qua đêm trả muộn không quá 1 giờ, miễn phí.',
                'note' => '',
            ];
        }

        $oneNightTotal = (float) $booking->bookingRooms->sum('price_at_booking');

        if ($oneNightTotal <= 0) {
            $oneNightTotal = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
        }

        if ($lateMinutes <= 180) {
            $percent = 30;
            $policyText = 'Booking qua đêm trả muộn trên 1 đến 3 giờ, phụ thu 30% giá 1 đêm.';
        } elseif ($lateMinutes <= 360) {
            $percent = 50;
            $policyText = 'Booking qua đêm trả muộn trên 3 đến 6 giờ, phụ thu 50% giá 1 đêm.';
        } else {
            $percent = 100;
            $policyText = 'Booking qua đêm trả muộn trên 6 giờ, tính thêm 1 đêm.';
        }

        $amount = round($oneNightTotal * $percent / 100, 0);

        return [
            'amount' => $amount,
            'late_minutes' => $lateMinutes,
            'late_hours' => $lateHours,
            'policy_text' => $policyText,
            'note' => 'Giờ check-out dự kiến: '
                . $plannedCheckOutAt->format('d/m/Y H:i')
                . '. Giờ check-out thực tế: '
                . $actualCheckOutAt->format('d/m/Y H:i')
                . '. Trễ khoảng '
                . $lateHours
                . ' giờ. '
                . $policyText,
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
            ->whereNotIn('billing_status', ['unused', 'cancelled'])
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

        if ($actualCheckInAt->greaterThan($plannedCheckInAt) && !$this->isBookingFullyPaid($booking)) {
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
        if (!$booking->check_in_at || !$booking->check_out_at) {
            return null;
        }

        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $checkOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        if ($this->isBookingFullyPaid($booking)) {
            return $checkOutAt;
        }

        if ($booking->booking_type === 'hourly') {
            return $checkInAt->copy()->addMinutes(30);
        }

        $nightCount = $this->getBookingNightCountFromTimes($booking);

        if ($nightCount <= 1) {
            [$holdHour, $holdMinute] = array_map(
                'intval',
                explode(':', Booking::LATE_ARRIVAL_ONE_NIGHT_HOLD_TIME)
            );

            return $checkInAt->copy()->setTime($holdHour, $holdMinute, 0);
        }

        return $checkInAt->copy()->addDays(Booking::LATE_ARRIVAL_MULTI_NIGHT_HOLD_DAYS);
    }


    private function handleLateArrivalFee(Booking $booking, array $data): string
    {
        if (!$booking->check_in_at || !$booking->check_out_at) {
            return '';
        }

        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $checkOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');

        if ($nowVn->lessThanOrEqualTo($checkInAt)) {
            return '';
        }

        if ($nowVn->greaterThanOrEqualTo($checkOutAt)) {
            throw new \Exception(
                'Không thể check-in vì booking đã quá thời gian lưu trú. '
                . 'Giờ trả phòng dự kiến là '
                . $checkOutAt->format('d/m/Y H:i')
                . '. Vui lòng hủy/no-show booking cũ và tạo booking mới nếu khách vẫn muốn ở.'
            );
        }

        $lateMinutes = $checkInAt->diffInMinutes($nowVn);
        $lateHours = round($lateMinutes / 60, 2);
        $holdLimitAt = $this->getLateArrivalHoldLimitAt($booking);
        $isFullPaidOrFullDeposit = $this->isBookingFullyPaid($booking);
        $nightCount = $this->getBookingNightCountFromTimes($booking);

        if ($isFullPaidOrFullDeposit) {
            $policy = 'Khách đã thanh toán/cọc 100%, được check-in muộn bất kỳ thời điểm nào trong thời gian lưu trú. Không phụ thu check-in muộn. Giữ nguyên giờ trả phòng '
                . $checkOutAt->format('d/m/Y H:i') . '.';

            $booking->late_arrival_fee = 0;
            $booking->late_arrival_hours = $lateHours;
            $booking->late_arrival_policy = $policy;

            return $policy;
        }

        if ($holdLimitAt && $nowVn->lessThan($holdLimitAt)) {
            if ($booking->booking_type === 'hourly') {
                $policy = 'Khách đặt theo giờ và đến muộn nhưng vẫn trong hạn giữ phòng đến '
                    . $holdLimitAt->format('d/m/Y H:i')
                    . '. Cho check-in bình thường, không phụ thu check-in muộn.';
            } elseif ($nightCount <= 1) {
                $policy = 'Booking 1 đêm, khách cọc một phần/chưa thanh toán đủ và đến muộn trước mốc giữ phòng '
                    . $holdLimitAt->format('H:i d/m/Y')
                    . '. Cho check-in bình thường, không phụ thu check-in muộn.';
            } else {
                $policy = 'Booking nhiều đêm, khách cọc một phần/chưa thanh toán đủ và vẫn trong hạn giữ phòng 1 ngày đến '
                    . $holdLimitAt->format('d/m/Y H:i')
                    . '. Cho check-in bình thường, không phụ thu check-in muộn. Giữ nguyên ngày trả phòng ban đầu.';
            }

            $booking->late_arrival_fee = 0;
            $booking->late_arrival_hours = $lateHours;
            $booking->late_arrival_policy = $policy;

            return $policy;
        }

        throw new \Exception(
            'Khách đã quá hạn giữ phòng '
            . ($holdLimitAt ? $holdLimitAt->format('d/m/Y H:i') : '')
            . '. Không được check-in booking này nữa. Vui lòng hủy/no-show để giữ cọc nếu có và mở bán lại phòng; nếu khách vẫn muốn ở thì tạo booking mới.'
        );
    }

    public function cancelLateArrival(Booking $booking)
    {
        $this->guardCanAccessBooking($booking);

        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Chỉ có thể hủy no-show với booking đã xác nhận nhưng khách chưa check-in.');
        }

        if (!$booking->check_in_at || !$booking->check_out_at) {
            return back()->with('error', 'Booking này chưa có đủ giờ nhận/trả phòng dự kiến.');
        }

        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');
        $holdLimitAt = $this->getLateArrivalHoldLimitAt($booking);
        $isFullPaidOrFullDeposit = $this->isBookingFullyPaid($booking);
        $lateMinutes = $checkInAt->diffInMinutes($nowVn, false);

        if (!$holdLimitAt) {
            return back()->with('error', 'Không xác định được hạn giữ phòng của booking này.');
        }

        if ($nowVn->lessThan($holdLimitAt)) {
            return back()->with(
                'error',
                'Chưa được hủy no-show. Booking này còn trong hạn giữ phòng đến '
                . $holdLimitAt->format('d/m/Y H:i')
                . '. Trong hạn này khách được check-in muộn và không phụ thu.'
            );
        }

        DB::beginTransaction();

        try {
            $booking->load('bookingRooms.room');

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update([
                        'status' => 'available',
                    ]);
                }
            }

            $oldNote = $booking->note ? $booking->note . "\n" : '';
            $lateHours = round(max(0, $lateMinutes) / 60, 2);
            $depositText = (float) $booking->deposit_amount > 0
                ? 'Giữ 100% tiền cọc/tiền đã thu: ' . number_format((float) $booking->deposit_amount, 0, ',', '.') . 'đ.'
                : 'Chưa ghi nhận tiền cọc trên hệ thống; lễ tân kiểm tra lại thanh toán nếu có.';

            $policyText = $isFullPaidOrFullDeposit
                ? 'Booking đã thanh toán/cọc 100% nhưng đã hết thời gian lưu trú, xử lý no-show/không hoàn tiền theo chính sách.'
                : 'Booking cọc một phần/chưa thanh toán đủ đã quá hạn giữ phòng ' . $holdLimitAt->format('d/m/Y H:i') . ', xử lý no-show/không hoàn cọc.';

            $booking->update([
                'status' => 'cancelled',
                'late_arrival_fee' => 0,
                'late_arrival_hours' => $lateHours,
                'late_arrival_policy' => $policyText . ' ' . $depositText,
                'note' => $oldNote
                    . $nowVn->format('d/m/Y H:i')
                    . ' - Hủy no-show do khách không đến trong hạn giữ phòng. '
                    . $policyText
                    . ' '
                    . $depositText
                    . ' Phòng được mở bán lại.',
            ]);

            $this->addBookingLog(
                $booking,
                'cancel_late_arrival',
                'Hủy no-show do khách không đến trong hạn giữ phòng. '
                . $policyText
                . ' '
                . $depositText
                . ' Phòng được mở bán lại.'
            );

            DB::commit();

            return back()->with('success', 'Đã hủy no-show, giữ tiền cọc/tiền đã thu nếu có và mở bán lại phòng.');
        } catch (\Throwable $e) {
            DB::rollBack();

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
                    $booking->cleaning_buffer_minutes ?? 60
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
                    'type' => 'policy_violation_fee',
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
                    'type' => 'violation_fee',
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

        $basePrice = $booking->bookingRooms->sum(function ($bookingRoom) {
            return (float) $bookingRoom->price_at_booking;
        });

        if ($basePrice <= 0) {
            $basePrice = (float) ($booking->roomCategory->price ?? 0)
                * max(1, (int) $booking->room_quantity);
        }

        $minutesOfDay = ((int) $actualCheckInAt->format('H')) * 60
            + ((int) $actualCheckInAt->format('i'));

        if ($minutesOfDay < 360) {
            $percent = 100;
            $policyText = 'Check-in sớm cùng ngày trước 06:00, phụ thu 100% giá 1 đêm.';
        } elseif ($minutesOfDay < 540) {
            $percent = 50;
            $policyText = 'Check-in sớm cùng ngày từ 06:00 đến trước 09:00, phụ thu 50% giá 1 đêm.';
        } elseif ($minutesOfDay < 660) {
            $percent = 20;
            $policyText = 'Check-in sớm cùng ngày từ 09:00 đến trước 11:00, phụ thu 20% giá 1 đêm.';
        } else {
            $percent = 0;
            $policyText = 'Check-in sớm cùng ngày từ 11:00 đến trước 14:00, miễn phí nếu phòng đã sẵn sàng.';
        }

        $amount = round(($basePrice * $percent) / 100, 0);

        return [
            'percent' => $percent,
            'base_price' => $basePrice,
            'amount' => $amount,
            'policy_text' => $policyText,
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

        if (!$user || in_array($user->role, ['super_admin', 'manager'], true)) {
            return;
        }

        if ($user->role === 'receptionist') {
            $canAccess = (int) $booking->created_by === (int) $user->id
                || $booking->staffAssignments()
                    ->where('staff_id', $user->id)
                    ->where('status', 'active')
                    ->exists();

            abort_unless($canAccess, 403, 'Bạn không được phân công xử lý booking này.');

            return;
        }

        abort(403, 'Bạn không có quyền xử lý booking này.');
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