<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use App\Models\BookingLog;
use App\Support\Realtime;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    // Lock and query expired bookings
    $expiredBookings = Booking::where('status', 'pending')
        ->where('booking_source', 'user_online')
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->get();

    foreach ($expiredBookings as $expiredBooking) {
        DB::transaction(function () use ($expiredBooking) {
            // Lock booking for update
            $booking = Booking::where('id', $expiredBooking->id)
                ->lockForUpdate()
                ->first();

            if (!$booking || $booking->status !== 'pending') {
                return;
            }

            // Release rooms
            $booking->load('bookingRooms.room');
            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update([
                        'status' => 'available',
                    ]);
                }
            }

            // Update status to cancelled
            $booking->update([
                'status' => 'cancelled',
                'note' => trim(($booking->note ? $booking->note . "\n" : '') . 'Đã tự động hủy do hết hạn giữ phòng (chưa thanh toán).'),
            ]);

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => null,
                'action' => 'cron_cancel_expired',
                'description' => 'Hệ thống tự động hủy booking do quá thời gian giữ phòng thanh toán VNPay.',
            ]);

            Realtime::booking($booking, 'cancelled');
        });
    }
})->everyMinute();
