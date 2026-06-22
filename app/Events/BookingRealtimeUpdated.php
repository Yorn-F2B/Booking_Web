<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingRealtimeUpdated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $action = 'updated'
    ) {
        $this->booking->loadMissing([
            'customer',
            'roomCategory',
            'bookingRooms.room',
        ]);
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.realtime'),
            new PrivateChannel('admin.bookings'),
        ];

        if ($this->booking->customer_id) {
            $channels[] = new PrivateChannel('customer.' . $this->booking->customer_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'booking.updated';
    }

    public function broadcastWith(): array
    {
        $customerName = trim(
            ($this->booking->customer->last_name ?? '') . ' ' .
            ($this->booking->customer->first_name ?? '')
        );

        $roomNumbers = $this->booking->bookingRooms
            ? $this->booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ')
            : '';

        return [
            'id' => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'action' => $this->action,
            'customer_id' => $this->booking->customer_id,
            'customer_name' => $customerName !== '' ? $customerName : 'Chưa có tên',
            'customer_phone' => $this->booking->customer->phone ?? 'Chưa có SĐT',
            'room_category' => $this->booking->roomCategory->name ?? 'Không xác định',
            'room_numbers' => $roomNumbers,
            'status' => $this->booking->status,
            'status_label' => $this->statusLabel($this->booking->status),
            'payment_status' => $this->booking->payment_status,
            'payment_status_label' => $this->paymentStatusLabel($this->booking->payment_status),
            'estimated_total' => (float) $this->booking->estimated_total,
            'estimated_total_text' => number_format((float) $this->booking->estimated_total, 0, ',', '.') . 'đ',
            'updated_at' => now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
            'url' => route('admin.bookings.show', $this->booking->id),
        ];
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending' => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đang ở',
            'inspection_requested' => 'Chờ kiểm tra phòng',
            'completed' => 'Hoàn tất',
            'checked_out' => 'Đã trả phòng',
            'cancelled', 'canceled' => 'Đã hủy',
            default => 'Không xác định',
        };
    }

    private function paymentStatusLabel(?string $status): string
    {
        return match ($status) {
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Đã cọc',
            'paid' => 'Đã thanh toán',
            'refunded' => 'Đã hoàn tiền',
            default => 'Không xác định',
        };
    }
}