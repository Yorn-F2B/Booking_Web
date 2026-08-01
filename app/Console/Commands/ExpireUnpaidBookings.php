<?php
namespace App\Console\Commands;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid';
    protected $description = 'Hủy các booking online chưa thanh toán khi hết thời hạn VNPay';
    public function handle(): int
    {
        $ids = Booking::where('booking_source','user_online')->where('status','pending')
            ->where('payment_status','unpaid')->whereNotNull('payment_expires_at')
            ->where('payment_expires_at','<=',now('Asia/Ho_Chi_Minh'))->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id) {
                $booking = Booking::whereKey($id)->lockForUpdate()->first();
                if (!$booking || $booking->status !== 'pending' || $booking->payment_status !== 'unpaid') return;
                BookingPayment::where('booking_id',$booking->id)->where('status','pending')->update([
                    'status'=>'failed','response_code'=>'EXPIRED','transaction_status'=>'EXPIRED'
                ]);
                $booking->update(['status'=>'cancelled','payment_expires_at'=>null]);
                BookingLog::create([
                    'booking_id'=>$booking->id,'user_id'=>null,'action'=>'payment_hold_expired',
                    'description'=>'Booking tự hủy vì hết thời hạn thanh toán VNPay.'
                ]);
            });
        }
        $this->info('Đã xử lý '.$ids->count().' booking hết hạn thanh toán.');
        return self::SUCCESS;
    }
}
