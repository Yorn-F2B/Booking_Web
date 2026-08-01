<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\BookingRoomPeriod;
use Carbon\Carbon;

class BookingRoomPeriodService
{
    public function ensureInitial(Booking $booking, BookingRoom $room): void
    {
        if ($room->periods()->exists()) return;
        BookingRoomPeriod::create(['booking_room_id' => $room->id, 'room_id' => $room->room_id, 'start_date' => Carbon::parse($booking->check_in_at ?: $booking->check_in_date)->toDateString(), 'end_date' => Carbon::parse($booking->check_out_at ?: $booking->check_out_date)->toDateString(), 'price_per_night' => $room->price_at_booking]);
    }

    public function changeFrom(Booking $booking, BookingRoom $room, int $newRoomId, float $newPrice, ?string $fromDate = null): void
    {
        $this->ensureInitial($booking, $room);
        $start = Carbon::parse($fromDate ?: now('Asia/Ho_Chi_Minh'))->startOfDay();
        $checkIn = Carbon::parse($booking->check_in_at ?: $booking->check_in_date)->startOfDay();
        $checkOut = Carbon::parse($booking->check_out_at ?: $booking->check_out_date)->startOfDay();
        $start = $start->max($checkIn);
        if ($start->greaterThanOrEqualTo($checkOut)) throw new \RuntimeException('Thời điểm đổi phòng phải trước ngày trả phòng.');
        $active = $room->periods()->where('start_date', '<=', $start->toDateString())->where('end_date', '>', $start->toDateString())->latest('start_date')->first();
        if (!$active) throw new \RuntimeException('Không tìm được giai đoạn lưu trú để đổi phòng.');
        if ($active->room_id === $newRoomId && (float) $active->price_per_night === $newPrice) return;
        $oldEnd = $active->end_date->toDateString();
        if ($active->start_date->toDateString() === $start->toDateString()) $active->delete(); else $active->update(['end_date' => $start->toDateString()]);
        BookingRoomPeriod::create(['booking_room_id' => $room->id, 'room_id' => $newRoomId, 'start_date' => $start->toDateString(), 'end_date' => $oldEnd, 'price_per_night' => $newPrice]);
    }
}
