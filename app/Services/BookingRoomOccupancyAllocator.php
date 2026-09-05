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
     * adult_count/child_count/baby_count của booking. Chỉ thay số người, không đổi phòng.
     */
    public function synchronizeBooking(Booking $booking): bool
    {
        $booking->loadMissing('bookingRooms.room.category');
        if ($booking->bookingRooms->isEmpty()) {
            return false;
        }

        $expectedAdults = max(0, (int) $booking->adult_count);
        $expectedChildren = max(0, (int) $booking->child_count);
        $expectedBabies = max(0, (int) ($booking->baby_count ?? 0));
        $currentAdults = (int) $booking->bookingRooms->sum('adult_count');
        $currentChildren = (int) $booking->bookingRooms->sum('child_count');
        $currentBabies = (int) $booking->bookingRooms->sum('baby_count');

        if ($currentAdults === $expectedAdults && $currentChildren === $expectedChildren && $currentBabies === $expectedBabies) {
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
            max(0, (int) $booking->child_count),
            max(0, (int) ($booking->baby_count ?? 0))
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
                    'baby_count' => $counts['baby_count'],
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
     * @return array<int,array{adult_count:int,child_count:int,baby_count:int}>
     */
    public function allocate(Collection $rooms, int $adults, int $children = 0, int $babies = 0): array
    {
        $rooms = $rooms->values();
        $roomCount = $rooms->count();
        $adults = max(0, $adults);
        $children = max(0, $children);
        $babies = max(0, $babies);

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
        if (($children + $babies) > $childCapacity) {
            throw ValidationException::withMessages([
                'child_count' => 'Tổng số trẻ em và em bé vượt sức chứa của các phòng đã chọn (tối đa '
                    . $childCapacity . ' trẻ em/em bé).',
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
            $allocation[(int) $room->id] = ['adult_count' => 1, 'child_count' => 0, 'baby_count' => 0];
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
            $usedMinorSlots = $allocation[$roomId]['child_count'] + $allocation[$roomId]['baby_count'];
            $add = min($remainingChildren, max(0, $childCap - $usedMinorSlots));
            $allocation[$roomId]['child_count'] += $add;
            $remainingChildren -= $add;
        }

        $remainingBabies = $babies;
        foreach ($rooms as $room) {
            if ($remainingBabies <= 0) {
                break;
            }
            $roomId = (int) $room->id;
            $childCap = max(0, (int) ($room->category?->child_capacity ?? 0));
            $usedMinorSlots = $allocation[$roomId]['child_count'] + $allocation[$roomId]['baby_count'];
            $add = min($remainingBabies, max(0, $childCap - $usedMinorSlots));
            $allocation[$roomId]['baby_count'] += $add;
            $remainingBabies -= $add;
        }

        if ($remainingAdults > 0 || $remainingChildren > 0 || $remainingBabies > 0) {
            throw ValidationException::withMessages([
                'room_ids' => 'Không thể phân hết số khách vào các phòng đã chọn. Vui lòng chọn lại phòng/hạng phòng.',
            ]);
        }

        return $allocation;
    }
}
