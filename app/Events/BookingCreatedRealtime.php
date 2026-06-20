<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class BookingCreatedRealtime implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public Booking $booking
    ) {
        $this->booking->loadMissing([
            'customer',
            'roomCategory',
            'bookingRooms.room',
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.bookings'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'booking.created';
    }

    public function broadcastWith(): array
    {
        $customerName = trim(
            ($this->booking->customer->last_name ?? '') . ' ' . ($this->booking->customer->first_name ?? '')
        );

        $roomNumbers = $this->booking->bookingRooms
            ? $this->booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ')
            : '';

        return [
            'id' => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'customer_name' => $customerName !== '' ? $customerName : 'Chưa có tên',
            'customer_phone' => $this->booking->customer->phone ?? 'Chưa có SĐT',
            'room_category' => $this->booking->roomCategory->name ?? 'Không xác định',
            'room_numbers' => $roomNumbers,
            'status' => $this->booking->status,
            'payment_status' => $this->booking->payment_status,
            'estimated_total' => (float) $this->booking->estimated_total,
            'created_at' => $this->booking->created_at
                ? $this->booking->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                : now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
            'url' => route('admin.bookings.show', $this->booking->id),
        ];
    }
}