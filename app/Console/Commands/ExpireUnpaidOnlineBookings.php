<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Support\Realtime;

class ExpireUnpaidOnlineBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:expire-unpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hủy tự động các booking online pending quá 30 phút chưa thanh toán';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now('Asia/Ho_Chi_Minh');

        $expiredBookings = Booking::with('bookingRooms.room')
            ->where('booking_source', 'user_online')
            ->where('status', 'pending')
            ->where('payment_status', 'unpaid')
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<', $now)
            ->get();

        $count = 0;

        foreach ($expiredBookings as $booking) {
            $booking->update([
                'status' => 'cancelled',
                'note' => trim($booking->note . "\nHệ thống tự động hủy do quá hạn 15 phút chờ thanh toán.")
            ]);

            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->releaseRoomFromBooking($booking->id);
                }
            }

            BookingLog::create([
                'booking_id' => $booking->id,
                'user_id' => null, // system
                'action' => 'auto_cancel',
                'description' => 'Hệ thống tự động hủy booking do quá hạn thanh toán.',
            ]);

            Realtime::booking($booking, 'cancelled');
            $count++;
        }

        $this->info("Đã tự động hủy {$count} booking online hết hạn thanh toán.");
    }
}
