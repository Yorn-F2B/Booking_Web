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
use Illuminate\Support\Facades\Auth;

class BookingLifecycleController extends Controller
{
    public function checkIn(Request $request, Booking $booking)
    {
        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Chỉ có thể nhận phòng với booking đã xác nhận.');
        }

        $data = $request->validate([
            'actual_adult_count' => 'required|integer|min:1',
            'actual_child_count' => 'nullable|integer|min:0',

            'over_capacity_action' => 'nullable|in:extra_fee,add_room,change_category',

            'actual_baby_count' => 'nullable|integer|min:0',

            'extra_service_ids' => 'nullable|array',
            'extra_service_ids.*' => 'nullable|exists:services,id',
            'extra_quantities' => 'nullable|array',
            'extra_quantities.*' => 'nullable|integer|min:1',
            'extra_fee_notes' => 'nullable|array',
            'extra_fee_notes.*' => 'nullable|string|max:1000',

            'additional_room_category_id' => 'nullable|exists:room_categories,id',
            'additional_room_quantity' => 'nullable|integer|min:1',
            'prefer_near_current_rooms' => 'nullable|boolean',
            'add_room_reason' => 'nullable|string|max:1000',

            'new_room_category_id' => 'nullable|exists:room_categories,id',
            'change_category_reason' => 'nullable|string|max:1000',

            'late_arrival_action' => 'nullable|in:confirm_arriving',
        ]);

        $actualAdultCount = (int) $data['actual_adult_count'];
        $actualChildCount = (int) ($data['actual_child_count'] ?? 0);
        $actualBabyCount = (int) ($data['actual_baby_count'] ?? 0);

        $booking->load([
            'bookingRooms.room.category',
            'roomCategory',
        ]);

        if ($booking->bookingRooms->count() == 0) {
            return back()->with('error', 'Booking này chưa được gán phòng nên không thể check-in.');
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

            if ($isOverCapacity && ($data['over_capacity_action'] ?? null) === 'extra_fee') {
                $actionNote = $this->handleExtraGuestFees($booking, $data);
            }

            if ($isOverCapacity && ($data['over_capacity_action'] ?? null) === 'add_room') {
                $actionNote = $this->handleAddRoomToBooking(
                    $booking,
                    $data,
                    $actualAdultCount,
                    $actualChildCount
                );
            }

            if ($isOverCapacity && ($data['over_capacity_action'] ?? null) === 'change_category') {
                $actionNote = $this->handleChangeRoomCategory(
                    $booking,
                    $data,
                    $actualAdultCount,
                    $actualChildCount
                );
            }

            $lateArrivalNote = $this->handleLateArrivalFee($booking, $data);

            if ($lateArrivalNote !== '') {
                $actionNote .= ' ' . $lateArrivalNote;
            }

            $booking->adult_count = $actualAdultCount;
            $booking->child_count = $actualChildCount;
            $booking->status = 'checked_in';
            $booking->actual_check_in = now();

            $booking->note = $oldNote
                . now()->format('d/m/Y H:i')
                . ' - Check-in thực tế: '
                . $actualAdultCount
                . ' người lớn / '
                . $actualChildCount
                . ' trẻ em / '
                . $actualBabyCount
                . ' em bé. '
                . $actionNote;

            $booking->save();

            $booking->load('bookingRooms.room');

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update([
                        'status' => 'occupied',
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

            return back()->with('success', 'Check-in thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi check-in: ' . $e->getMessage());
        }
    }

    public function extendStay(Request $request, Booking $booking)
    {
        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Chỉ booking đang ở mới được gia hạn lưu trú.');
        }

        $data = $request->validate([
            'new_check_out_date' => 'required|date',
            'new_check_out_time' => 'required|date_format:H:i',
        ], [
            'new_check_out_date.required' => 'Vui lòng chọn ngày trả phòng mới.',
            'new_check_out_date.date' => 'Ngày trả phòng mới không hợp lệ.',
            'new_check_out_time.required' => 'Vui lòng chọn giờ trả phòng mới.',
            'new_check_out_time.date_format' => 'Giờ trả phòng mới phải theo định dạng 24 giờ, ví dụ 14:00 hoặc 17:30.',
        ]);

        $booking->load([
            'bookingRooms.room',
            'bookingRooms.room.category',
            'roomCategory',
        ]);

        if ($booking->bookingRooms->count() == 0) {
            return back()->with('error', 'Booking này chưa có phòng nên không thể gia hạn.');
        }

        $oldCheckOutAt = \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh');
        $newCheckOutAt = \Carbon\Carbon::parse(
            $data['new_check_out_date'] . ' ' . $data['new_check_out_time'] . ':00',
            'Asia/Ho_Chi_Minh'
        );

        if ($newCheckOutAt->lessThanOrEqualTo($oldCheckOutAt)) {
            return back()->with(
                'error',
                'Không thể gia hạn. Thời gian trả phòng mới phải sau thời gian trả phòng hiện tại '
                . $oldCheckOutAt->format('d/m/Y H:i') . '.'
            );
        }

        foreach ($booking->bookingRooms as $bookingRoom) {
            $conflictBookingRoom = BookingRoom::where('room_id', $bookingRoom->room_id)
                ->where('booking_id', '!=', $booking->id)
                ->whereHas('booking', function ($query) use ($oldCheckOutAt, $newCheckOutAt) {
                    $query->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                        ->where(function ($timeQuery) use ($oldCheckOutAt, $newCheckOutAt) {
                            $timeQuery
                                ->where(function ($overlapQuery) use ($oldCheckOutAt, $newCheckOutAt) {
                                    $overlapQuery
                                        ->where('check_in_at', '<', $newCheckOutAt)
                                        ->where('check_out_at', '>', $oldCheckOutAt);
                                })
                                ->orWhere(function ($nextBookingQuery) use ($oldCheckOutAt) {
                                    $nextBookingQuery
                                        ->whereDate('check_in_at', $oldCheckOutAt->toDateString())
                                        ->where('check_in_at', '>=', $oldCheckOutAt);
                                });
                        });
                })
                ->with([
                    'booking.customer',
                    'room',
                ])
                ->orderBy(
                    Booking::select('check_in_at')
                        ->whereColumn('bookings.id', 'booking_rooms.booking_id')
                        ->limit(1)
                )
                ->first();

            if ($conflictBookingRoom) {
                $conflictBooking = $conflictBookingRoom->booking;
                $roomNumber = $bookingRoom->room->room_number ?? ('ID ' . $bookingRoom->room_id);

                $customerName = 'khách mới';

                if ($conflictBooking && $conflictBooking->customer) {
                    $customerName = trim(
                        ($conflictBooking->customer->last_name ?? '') . ' ' . ($conflictBooking->customer->first_name ?? '')
                    );

                    if ($customerName === '') {
                        $customerName = 'khách mới';
                    }
                }

                return back()->with(
                    'error',
                    'Không thể gia hạn lưu trú, kể cả gia hạn thêm giờ. '
                    . 'Phòng ' . $roomNumber
                    . ' đã có booking mới '
                    . ($conflictBooking->booking_code ?? '')
                    . ' của ' . $customerName
                    . ' từ '
                    . \Carbon\Carbon::parse($conflictBooking->check_in_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                    . ' đến '
                    . \Carbon\Carbon::parse($conflictBooking->check_out_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                    . '. Khách hiện tại phải trả phòng đúng hạn. Nếu khách muốn ở tiếp, vui lòng tạo booking mới hoặc chuyển sang phòng khác còn trống.'
                );
            }
        }

        DB::beginTransaction();

        try {
            $oneNightTotal = $booking->bookingRooms->sum(function ($bookingRoom) {
                return (float) $bookingRoom->price_at_booking;
            });

            if ($oneNightTotal <= 0) {
                $oneNightTotal = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
            }

            $extraRoomTotal = 0;
            $extendPolicyText = '';

            if ($oldCheckOutAt->toDateString() === $newCheckOutAt->toDateString()) {
                $extraMinutes = $oldCheckOutAt->diffInMinutes($newCheckOutAt);
                $extraHours = round($extraMinutes / 60, 2);

                if ($extraHours <= 3) {
                    $extraRoomTotal = $oneNightTotal * 0.3;
                    $extendPolicyText = 'Gia hạn thêm ' . $extraHours . ' giờ, phụ thu 30% giá/đêm.';
                } elseif ($extraHours <= 6) {
                    $extraRoomTotal = $oneNightTotal * 0.5;
                    $extendPolicyText = 'Gia hạn thêm ' . $extraHours . ' giờ, phụ thu 50% giá/đêm.';
                } else {
                    $extraRoomTotal = $oneNightTotal;
                    $extendPolicyText = 'Gia hạn thêm ' . $extraHours . ' giờ, tính thêm 1 đêm.';
                }
            } else {
                $extraNights = max(
                    1,
                    $oldCheckOutAt->copy()->startOfDay()->diffInDays($newCheckOutAt->copy()->startOfDay())
                );

                $extraRoomTotal = $oneNightTotal * $extraNights;
                $extendPolicyText = 'Gia hạn thêm ' . $extraNights . ' đêm.';
            }

            $extraRoomTotal = round($extraRoomTotal, 0);

            $extendStayService = Service::firstOrCreate(
                [
                    'name' => 'Phụ thu gia hạn lưu trú',
                    'type' => 'violation_fee',
                ],
                [
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

            $oldNote = $booking->note ? $booking->note . "\n" : '';

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
                . ' Phụ thu: '
                . number_format($extraRoomTotal, 0, ',', '.')
                . 'đ.'
            );

            DB::commit();

            return back()->with(
                'success',
                'Gia hạn lưu trú thành công. '
                . $extendPolicyText
                . ' Phụ thu: '
                . number_format($extraRoomTotal, 0, ',', '.')
                . 'đ.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi gia hạn lưu trú: ' . $e->getMessage());
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
                ->where('type', 'violation_fee')
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
        int $actualAdultCount,
        int $actualChildCount
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

        if ($actualAdultCount > $newAdultCapacity || $actualChildCount > $newChildCapacity) {
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
                'status' => 'occupied',
            ]);

            $booking->estimated_total += $category->price * $nightCount;
        }

        $booking->room_quantity += $quantity;

        return 'Khách vượt sức chứa, đã thêm '
            . $quantity
            . ' phòng hạng '
            . $category->name
            . ' vào booking.';
    }

    private function handleChangeRoomCategory(
        Booking $booking,
        array $data,
        int $actualAdultCount,
        int $actualChildCount
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

        if ($actualAdultCount > $newAdultCapacity || $actualChildCount > $newChildCapacity) {
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
            throw new \Exception('Không còn đủ phòng trống thuộc hạng phòng mới.');
        }

        foreach ($booking->bookingRooms as $bookingRoom) {
            if ($bookingRoom->room) {
                $bookingRoom->room->update([
                    'status' => 'available',
                ]);
            }
        }

        BookingRoom::where('booking_id', $booking->id)->delete();

        $nightCount = $this->getNightCount($booking);
        $newEstimatedTotal = 0;

        foreach ($newRooms as $room) {
            BookingRoom::create([
                'booking_id' => $booking->id,
                'room_id' => $room->id,
                'adult_count' => 0,
                'child_count' => 0,
                'price_at_booking' => $newCategory->price,
                'surcharge' => 0,
                'surcharge_reason' => $data['change_category_reason'] ?? 'Đổi hạng phòng khi check-in do vượt sức chứa.',
                'created_at' => now(),
            ]);

            $room->update([
                'status' => 'occupied',
            ]);

            $newEstimatedTotal += $newCategory->price * $nightCount;
        }

        $booking->room_category_id = $newCategory->id;
        $booking->estimated_total = $newEstimatedTotal;

        return 'Khách vượt sức chứa, đã đổi sang hạng phòng '
            . $newCategory->name
            . '. Lý do: '
            . ($data['change_category_reason'] ?? 'Vượt sức chứa khi check-in.');
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
            ->where('status', 'available')
            ->whereDoesntHave('bookingRooms.booking', function ($query) use ($checkInAt, $checkOutAt) {
                $query->whereIn('status', [
                    'pending',
                    'confirmed',
                    'checked_in',
                ])
                    ->where('check_in_at', '<', $checkOutAt)
                    ->where('check_out_at', '>', $checkInAt);
            });

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

    public function requestInspection(Booking $booking)
    {
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

                RoomInspection::firstOrCreate(
                    [
                        'booking_id' => $booking->id,
                        'room_id' => $bookingRoom->room_id,
                        'status' => 'pending',
                    ],
                    [
                        'has_damage' => false,
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

    public function checkOut(Booking $booking)
    {
        if ($booking->status !== 'checked_in') {
            return back()->with('error', 'Chỉ có thể check-out booking đang ở.');
        }

        $booking->load([
            'bookingRooms.room',
            'roomInspections.items',
        ]);

        if ($booking->roomInspections->count() == 0) {
            return back()->with('error', 'Booking này chưa có phiếu kiểm tra phòng.');
        }

        $notConfirmedInspectionCount = $booking->roomInspections
            ->where('status', '!=', 'confirmed')
            ->count();

        if ($notConfirmedInspectionCount > 0) {
            return back()->with('error', 'Vẫn còn phiếu kiểm tra chưa được admin duyệt.');
        }

        DB::beginTransaction();

        try {
            $booking->update([
                'status' => 'checked_out',
                'actual_check_out' => now(),
                'payment_status' => 'paid',
            ]);

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update([
                        'status' => 'cleaning',
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
                'Xác nhận check-out. Phòng chuyển sang cần dọn: ' . $roomNumbers . '. Thanh toán chuyển sang đã thanh toán.'
            );

            DB::commit();

            return back()->with('success', 'Check-out thành công. Phòng đã chuyển sang trạng thái cần dọn.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi check-out: ' . $e->getMessage());
        }
    }

    private function handleLateArrivalFee(Booking $booking, array $data)
    {
        if (!$booking->check_in_at) {
            return '';
        }

        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');

        $checkInAt = \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh');

        $lateMinutes = 0;

        if ($nowVn->greaterThan($checkInAt)) {
            $lateMinutes = $checkInAt->diffInMinutes($nowVn);
        }

        if ($lateMinutes <= 0) {
            return '';
        }

        $lateHours = round($lateMinutes / 60, 2);

        if ($lateHours < 2) {
            $booking->late_arrival_fee = 0;
            $booking->late_arrival_hours = $lateHours;
            $booking->late_arrival_policy = 'Khách đến muộn dưới 2 giờ, miễn phí.';

            return 'Khách đến muộn dưới 2 giờ, miễn phí.';
        }

        $basePrice = (float) $booking->bookingRooms->sum('price_at_booking');

        if ($basePrice <= 0) {
            $basePrice = (float) ($booking->roomCategory->price ?? 0) * max(1, (int) $booking->room_quantity);
        }

        $percent = 0;
        $policy = '';

        if ($lateHours >= 2 && $lateHours < 4) {
            $percent = 20;
            $policy = 'Khách đến muộn từ 2 đến dưới 4 giờ, phụ thu 20% giá/đêm.';
        } elseif ($lateHours >= 4 && $lateHours < 6) {
            $percent = 50;
            $policy = 'Khách đến muộn từ 4 đến dưới 6 giờ, phụ thu 50% giá/đêm.';
        } else {
            if (($data['late_arrival_action'] ?? null) !== 'confirm_arriving') {
                throw new \Exception('Khách đến muộn quá 6 giờ. Cần gọi xác nhận khách đang đến hoặc hủy phòng.');
            }

            $percent = 100;
            $policy = 'Khách đến muộn quá 6 giờ, đã gọi xác nhận đang đến, phụ thu 100% giá/đêm.';
        }

        $lateFee = $basePrice * $percent / 100;

        $lateArrivalService = Service::firstOrCreate(
            [
                'name' => 'Phụ thu khách đến muộn',
                'type' => 'violation_fee',
            ],
            [
                'price' => 0,
                'unit' => 'lần',
                'description' => 'Phí vi phạm áp dụng khi khách đến muộn theo chính sách khách sạn.',
                'status' => 'active',
            ]
        );

        BookingServiceItem::create([
            'booking_id' => $booking->id,
            'service_id' => $lateArrivalService->id,
            'name' => 'Phụ thu khách đến muộn',
            'type' => 'violation_fee',
            'unit_price' => $lateFee,
            'quantity' => 1,
            'total' => $lateFee,
            'note' => $policy,
        ]);

        $booking->late_arrival_fee = $lateFee;
        $booking->late_arrival_hours = $lateHours;
        $booking->late_arrival_policy = $policy;
        $booking->estimated_total += $lateFee;

        return $policy . ' Số tiền phụ thu: ' . number_format($lateFee, 0, ',', '.') . 'đ.';
    }

    public function cancelLateArrival(Booking $booking)
    {
        if ($booking->status !== 'confirmed') {
            return back()->with('error', 'Chỉ có thể hủy do đến muộn với booking đã xác nhận.');
        }

        if (!$booking->check_in_at) {
            return back()->with('error', 'Booking này chưa có giờ nhận phòng dự kiến.');
        }

        $lateMinutes = $booking->check_in_at->diffInMinutes(now(), false);

        if ($lateMinutes <= 360) {
            return back()->with('error', 'Chỉ được hủy do đến muộn khi khách trễ quá 6 giờ.');
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

            $booking->update([
                'status' => 'cancelled',
                'late_arrival_fee' => 0,
                'late_arrival_hours' => round($lateMinutes / 60, 2),
                'late_arrival_policy' => 'Khách đến muộn quá 6 giờ, không xác nhận đang đến. Hủy phòng và không hoàn tiền cọc.',
                'note' => $oldNote . now()->format('d/m/Y H:i') . ' - Hủy phòng do khách đến muộn quá 6 giờ, từ chối hoàn tiền cọc.',
            ]);

            $this->addBookingLog(
                $booking,
                'cancel_late_arrival',
                'Hủy booking do khách đến muộn quá 6 giờ, không xác nhận đang đến. Không hoàn tiền cọc.'
            );

            DB::commit();

            return back()->with('success', 'Đã hủy booking do khách đến muộn quá 6 giờ và không hoàn tiền cọc.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Có lỗi khi hủy booking: ' . $e->getMessage());
        }
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