<?php

namespace App\Services;

use App\Models\BookingRoom;
use App\Models\Room;
use Illuminate\Support\Collection;

class RoomReservationStatusService
{
    /**
     * Đồng bộ phần trạng thái "available/reserved" theo booking đang còn hiệu lực.
     * Không bao giờ ghi đè các trạng thái vận hành thực tế như occupied, cleaning,
     * inspection hoặc maintenance.
     */
    public function syncRoomIds(iterable $roomIds): void
    {
        $ids = collect($roomIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $reservedIds = BookingRoom::query()
            ->whereIn('room_id', $ids->all())
            ->whereHas('booking', fn ($query) => $query->activeForOperations())
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $this->apply($ids, $reservedIds);
    }

    /**
     * Dùng ở trang quản lý phòng như một lớp tự-heal cho dữ liệu cũ: phòng từng
     * giữ cho booking đã hủy/đổi phòng sẽ được mở lại; phòng sẵn sàng nhưng đang
     * thuộc booking hoạt động sẽ hiển thị Đã đặt. Các trạng thái vật lý khác giữ nguyên.
     */
    public function syncAll(): void
    {
        $rooms = Room::query()
            ->whereIn('status', ['available', 'reserved'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($rooms->isEmpty()) {
            return;
        }

        $reservedIds = BookingRoom::query()
            ->whereIn('room_id', $rooms->all())
            ->whereHas('booking', fn ($query) => $query->activeForOperations())
            ->pluck('room_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $this->apply($rooms, $reservedIds);
    }

    private function apply(Collection $roomIds, Collection $reservedIds): void
    {
        $reservedLookup = array_fill_keys($reservedIds->all(), true);

        Room::query()
            ->whereIn('id', $roomIds->all())
            ->whereIn('status', ['available', 'reserved'])
            ->get()
            ->each(function (Room $room) use ($reservedLookup) {
                $shouldBeReserved = isset($reservedLookup[(int) $room->id]);

                if ($shouldBeReserved && $room->status === 'available') {
                    $room->update([
                        'status' => 'reserved',
                        'status_from' => now('Asia/Ho_Chi_Minh'),
                        'status_until' => null,
                    ]);
                    return;
                }

                if (!$shouldBeReserved && $room->status === 'reserved') {
                    $room->update([
                        'status' => 'available',
                        'status_from' => null,
                        'status_until' => null,
                    ]);
                }
            });
    }
}
