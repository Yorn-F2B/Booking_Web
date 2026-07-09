<?php

namespace App\Events;

use App\Models\RoomInspection;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InspectionRealtimeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public RoomInspection $inspection,
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
        return 'inspection.updated';
    }

    public function broadcastWith(): array
    {
        $booking = $this->inspection->relationLoaded('booking') ? $this->inspection->booking : null;
        $room = $this->inspection->relationLoaded('room') ? $this->inspection->room : null;

        return [
            'id' => $this->inspection->id,
            'booking_id' => $this->inspection->booking_id,
            'booking_code' => $booking->booking_code ?? '',
            'room_id' => $this->inspection->room_id,
            'room_number' => $room->room_number ?? '',
            'status' => $this->inspection->status,
            'status_label' => $this->statusLabel($this->inspection->status),
            'damage_total' => (float) ($this->inspection->damage_total ?? 0),
            'damage_total_text' => number_format((float) ($this->inspection->damage_total ?? 0), 0, ',', '.') . 'đ',
            'action' => $this->action,
            'updated_at' => now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
        ];
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Chờ kiểm tra',
            'submitted', 'reported' => 'Chờ admin duyệt',
            'confirmed', 'approved' => 'Đã duyệt',
            'rejected' => 'Bị trả lại',
            default => 'Không xác định',
        };
    }
}
