<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomRealtimeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Room $room,
        public string $action = 'updated'
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.realtime'),
            new PrivateChannel('admin.rooms'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.updated';
    }

    public function broadcastWith(): array
    {
        $category = null;

        if ($this->room->relationLoaded('category')) {
            $category = $this->room->category;
        } elseif ($this->room->relationLoaded('roomCategory')) {
            $category = $this->room->roomCategory;
        }

        return [
            'id' => $this->room->id,
            'room_number' => $this->room->room_number,
            'floor_number' => $this->room->floor_number,
            'room_category' => $category->name ?? 'Không xác định',
            'status' => $this->room->status,
            'status_label' => $this->statusLabel($this->room->status),
            'action' => $this->action,
            'updated_at' => now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
        ];
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'available' => 'Trống',
            'reserved' => 'Đã đặt',
            'occupied' => 'Đang ở',
            'inspection' => 'Chờ kiểm tra',
            'cleaning' => 'Đang dọn',
            'maintenance' => 'Bảo trì',
            default => 'Không xác định',
        };
    }
}
