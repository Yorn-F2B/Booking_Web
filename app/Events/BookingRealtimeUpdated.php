<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BookingRealtimeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $action = 'updated'
    ) {
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
        $customer = $this->booking->relationLoaded('customer') ? $this->booking->customer : null;
        $roomCategory = $this->booking->relationLoaded('roomCategory') ? $this->booking->roomCategory : null;
        $bookingRooms = $this->booking->relationLoaded('bookingRooms') ? $this->booking->bookingRooms : collect();

        $customerName = trim(
            ($customer->last_name ?? '') . ' ' .
            ($customer->first_name ?? '')
        );

        if ($customerName === '') {
            $customerName = $customer->name ?? 'Chưa có tên';
        }

        $roomNumbers = $bookingRooms instanceof Collection
            ? $bookingRooms->map(function ($bookingRoom) {
                return $bookingRoom->room->room_number ?? null;
            })->filter()->implode(', ')
            : '';

        return [
            'id' => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'action' => $this->action,
            'customer_id' => $this->booking->customer_id,
            'customer_name' => $customerName,
            'customer_phone' => $customer->phone ?? 'Chưa có SĐT',
            'room_category' => $roomCategory->name ?? 'Không xác định',
            'room_numbers' => $roomNumbers,
            'status' => $this->booking->status,
            'status_label' => $this->statusLabel($this->booking->status),
            'payment_status' => $this->booking->payment_status,
            'payment_status_label' => $this->paymentStatusLabel($this->booking->payment_status),
            'estimated_total' => (float) $this->booking->estimated_total,
            'estimated_total_text' => number_format((float) $this->booking->estimated_total, 0, ',', '.') . 'đ',
            'deposit_amount' => (float) $this->booking->deposit_amount,
            'deposit_amount_text' => number_format((float) $this->booking->deposit_amount, 0, ',', '.') . 'đ',
            'updated_at' => now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
            'admin_url' => route('admin.bookings.show', $this->booking->id),
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
