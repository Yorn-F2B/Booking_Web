<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingRoomOccupancyAllocator
{

    /**
     * Tự sửa dữ liệu booking_rooms legacy khi tổng phân bổ không còn khớp
     * adult_count/child_count của booking. Chỉ thay số người, không đổi phòng.
     */
    public function synchronizeBooking(Booking $booking): bool
    {
        $booking->loadMissing('bookingRooms.room.category');
        if ($booking->bookingRooms->isEmpty()) {
            return false;
        }

        $expectedAdults = max(0, (int) $booking->adult_count);
        $expectedChildren = max(0, (int) $booking->child_count);
        $currentAdults = (int) $booking->bookingRooms->sum('adult_count');
        $currentChildren = (int) $booking->bookingRooms->sum('child_count');

        if ($currentAdults === $expectedAdults && $currentChildren === $expectedChildren) {
            return false;
        }

        $rooms = $booking->bookingRooms->pluck('room')->filter()->values();
        if ($rooms->count() !== $booking->bookingRooms->count()) {
            throw ValidationException::withMessages([
                'room_ids' => 'Booking có phân phòng không hợp lệ hoặc phòng đã bị xóa. Vui lòng kiểm tra lại phân phòng trước khi check-in.',
            ]);
        }

        $this->rebalanceBooking($booking);
        return true;
    }

    /**
     * Phân lại occupancy theo tổng adult/child của booking ngay cả khi tổng hiện
     * tại vẫn khớp. Dùng sau thao tác làm thay đổi số phòng/hạng phòng để tránh
     * phòng mới 0 người hoặc một phòng vượt sức chứa trong khi tổng booking vẫn đúng.
     */
    public function rebalanceBooking(Booking $booking): void
    {
        $booking->load('bookingRooms.room.category');
        if ($booking->bookingRooms->isEmpty()) {
            return;
        }

        $rooms = $booking->bookingRooms->pluck('room')->filter()->values();
        if ($rooms->count() !== $booking->bookingRooms->count()) {
            throw ValidationException::withMessages([
                'room_ids' => 'Booking có phân phòng không hợp lệ hoặc phòng đã bị xóa. Vui lòng kiểm tra lại phân phòng.',
            ]);
        }

        $allocation = $this->allocate(
            $rooms,
            max(0, (int) $booking->adult_count),
            max(0, (int) $booking->child_count)
        );

        DB::transaction(function () use ($booking, $allocation) {
            foreach ($booking->bookingRooms as $bookingRoom) {
                $counts = $allocation[(int) $bookingRoom->room_id] ?? null;
                if (!$counts) {
                    continue;
                }
                $bookingRoom->update([
                    'adult_count' => $counts['adult_count'],
                    'child_count' => $counts['child_count'],
                ]);
            }
        });

        $booking->load('bookingRooms.room.category');
    }

    /**
     * Phân người thực tế vào các phòng đã chọn.
     *
     * booking_rooms là nguồn số người vận hành; booking_guests chỉ là hồ sơ
     * đại diện/giấy tờ. Vì check-in yêu cầu một người lớn đại diện mỗi phòng,
     * allocator luôn giữ tối thiểu một người lớn cho mỗi phòng trước khi phân
     * phần người lớn còn lại.
     *
     * @return array<int,array{adult_count:int,child_count:int}>
     */
    public function allocate(Collection $rooms, int $adults, int $children = 0): array
    {
        $rooms = $rooms->values();
        $roomCount = $rooms->count();
        $adults = max(0, $adults);
        $children = max(0, $children);

        if ($roomCount < 1) {
            throw ValidationException::withMessages([
                'room_ids' => 'Booking phải có ít nhất một phòng để phân khách.',
            ]);
        }

        if ($adults < $roomCount) {
            throw ValidationException::withMessages([
                'adult_count' => 'Mỗi phòng cần ít nhất một người lớn đại diện. Với '
                    . $roomCount . ' phòng cần tối thiểu ' . $roomCount . ' người lớn.',
            ]);
        }

        $adultCapacity = (int) $rooms->sum(fn ($room) => max(0, (int) ($room->category?->adult_capacity ?? 0)));
        $childCapacity = (int) $rooms->sum(fn ($room) => max(0, (int) ($room->category?->child_capacity ?? 0)));

        if ($adults > $adultCapacity) {
            throw ValidationException::withMessages([
                'adult_count' => 'Số người lớn vượt tổng sức chứa của các phòng đã chọn (tối đa '
                    . $adultCapacity . ' người lớn).',
            ]);
        }
        if ($children > $childCapacity) {
            throw ValidationException::withMessages([
                'child_count' => 'Số trẻ em vượt sức chứa của các phòng đã chọn (tối đa '
                    . $childCapacity . ' trẻ em).',
            ]);
        }

        $allocation = [];
        foreach ($rooms as $room) {
            $adultCap = max(0, (int) ($room->category?->adult_capacity ?? 0));
            if ($adultCap < 1) {
                throw ValidationException::withMessages([
                    'room_ids' => 'Phòng ' . ($room->room_number ?? ('#' . $room->id))
                        . ' thuộc hạng không có sức chứa người lớn hợp lệ.',
                ]);
            }
            $allocation[(int) $room->id] = ['adult_count' => 1, 'child_count' => 0];
        }

        $remainingAdults = $adults - $roomCount;
        foreach ($rooms as $room) {
            if ($remainingAdults <= 0) {
                break;
            }
            $roomId = (int) $room->id;
            $adultCap = max(0, (int) ($room->category?->adult_capacity ?? 0));
            $freeAdultSlots = max(0, $adultCap - $allocation[$roomId]['adult_count']);
            $add = min($remainingAdults, $freeAdultSlots);
            $allocation[$roomId]['adult_count'] += $add;
            $remainingAdults -= $add;
        }

        $remainingChildren = $children;
        foreach ($rooms as $room) {
            if ($remainingChildren <= 0) {
                break;
            }
            $roomId = (int) $room->id;
            $childCap = max(0, (int) ($room->category?->child_capacity ?? 0));
            $add = min($remainingChildren, max(0, $childCap - $allocation[$roomId]['child_count']));
            $allocation[$roomId]['child_count'] += $add;
            $remainingChildren -= $add;
        }

        if ($remainingAdults > 0 || $remainingChildren > 0) {
            throw ValidationException::withMessages([
                'room_ids' => 'Không thể phân hết số khách vào các phòng đã chọn. Vui lòng chọn lại phòng/hạng phòng.',
            ]);
        }

        return $allocation;
    }
    /**
     * Phân số khách thực tế khi check-in. Khác allocate(): phương thức này vẫn
     * cho phép vượt tổng sức chứa để hệ thống có thể ghi nhận khách thực tế và
     * tính phụ thu. Phần vượt chỉ được dồn sau khi đã lấp hết sức chứa chuẩn.
     */
    public function rebalanceBookingAllowOverflow(
        Booking $booking,
        int $adults,
        int $children = 0
    ): void {
        $booking->load('bookingRooms.room.category');
        if ($booking->bookingRooms->isEmpty()) {
            return;
        }

        $rooms = $booking->bookingRooms->pluck('room')->filter()->values();
        if ($rooms->count() !== $booking->bookingRooms->count()) {
            throw ValidationException::withMessages([
                'room_ids' => 'Booking có phân phòng không hợp lệ hoặc phòng đã bị xóa. Vui lòng kiểm tra lại phân phòng.',
            ]);
        }

        $roomCount = $rooms->count();
        $adults = max(0, $adults);
        $children = max(0, $children);

        if ($adults < $roomCount) {
            throw ValidationException::withMessages([
                'actual_adult_count' => 'Mỗi phòng cần ít nhất một người lớn đại diện. Với '
                    . $roomCount . ' phòng cần tối thiểu ' . $roomCount . ' người lớn thực tế.',
            ]);
        }

        $allocation = [];
        foreach ($rooms as $room) {
            $allocation[(int) $room->id] = [
                'adult_count' => 1,
                'child_count' => 0,
            ];
        }

        $remainingAdults = $adults - $roomCount;
        foreach ($rooms as $room) {
            if ($remainingAdults <= 0) break;
            $id = (int) $room->id;
            $cap = max(1, (int) ($room->category?->adult_capacity ?? 1));
            $free = max(0, $cap - $allocation[$id]['adult_count']);
            $add = min($remainingAdults, $free);
            $allocation[$id]['adult_count'] += $add;
            $remainingAdults -= $add;
        }
        if ($remainingAdults > 0) {
            // Phần vượt được dồn vào phòng cuối để giữ tổng chính xác; phụ thu
            // vẫn tính theo tổng booking chứ không yêu cầu lễ tân phân khách tay.
            $lastRoomId = (int) $rooms->last()->id;
            $allocation[$lastRoomId]['adult_count'] += $remainingAdults;
        }

        $remainingChildren = $children;
        foreach ($rooms as $room) {
            if ($remainingChildren <= 0) break;
            $id = (int) $room->id;
            $cap = max(0, (int) ($room->category?->child_capacity ?? 0));
            $add = min($remainingChildren, max(0, $cap - $allocation[$id]['child_count']));
            $allocation[$id]['child_count'] += $add;
            $remainingChildren -= $add;
        }
        if ($remainingChildren > 0) {
            $lastRoomId = (int) $rooms->last()->id;
            $allocation[$lastRoomId]['child_count'] += $remainingChildren;
        }

        DB::transaction(function () use ($booking, $allocation) {
            foreach ($booking->bookingRooms as $bookingRoom) {
                $counts = $allocation[(int) $bookingRoom->room_id] ?? null;
                if (!$counts) continue;
                $bookingRoom->update($counts);
            }
        });

        $booking->load('bookingRooms.room.category');
    }

}
