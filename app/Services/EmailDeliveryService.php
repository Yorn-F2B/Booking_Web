<?php
namespace App\Services;
use App\Models\Booking;
use App\Models\EmailDeliveryLog;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailDeliveryService
{
    public function send(string $recipient, Mailable $mail, string $mailType, ?Booking $booking=null, ?string $subject=null): bool
    {
        try { $this->sendOrFail($recipient,$mail,$mailType,$booking,$subject); return true; }
        catch (Throwable) { return false; }
    }

    public function sendOrFail(string $recipient, Mailable $mail, string $mailType, ?Booking $booking=null, ?string $subject=null): void
    {
        $log = EmailDeliveryLog::create([
            'booking_id'=>$booking?->id, 'recipient'=>$recipient, 'mail_type'=>$mailType,
            'subject'=>$subject, 'status'=>'pending', 'attempts'=>1,
        ]);
        try {
            Mail::to($recipient)->send($mail);
            $log->update(['status'=>'sent','sent_at'=>now('Asia/Ho_Chi_Minh')]);

            if ($booking) {
                $booking->loadMissing('customer');
                $customerUserId = (int) ($booking->customer?->user_id ?? 0);
                if ($customerUserId > 0) {
                    app(OperationalNotificationService::class)->toUser(
                        $customerUserId,
                        'Có cập nhật cho booking ' . $booking->booking_code,
                        'Khách sạn vừa gửi email "' . ($subject ?: $mailType) . '" tới ' . $recipient . '.',
                        url('/booking-history/' . $booking->id),
                        'info',
                        ['booking_id' => $booking->id]
                    );
                }
            }
        } catch (Throwable $e) {
            $log->update(['status'=>'failed','failed_at'=>now('Asia/Ho_Chi_Minh'),'error_message'=>mb_substr($e->getMessage(),0,2000)]);
            app(OperationalNotificationService::class)->toRoles(
                ['super_admin','manager','receptionist_lead','receptionist'],
                'Không gửi được email cho khách',
                'Email '.$recipient.' gửi thất bại'.($booking ? ' cho booking '.$booking->booking_code : '').'. Hãy kiểm tra địa chỉ hoặc liên hệ khách bằng kênh khác.',
                $booking ? route('admin.bookings.show',$booking) : null,
                'danger', ['booking_id'=>$booking?->id]
            );
            if ($booking) {
                $booking->loadMissing('customer');
                $customerUserId = (int) ($booking->customer?->user_id ?? 0);
                if ($customerUserId > 0) {
                    app(OperationalNotificationService::class)->toUser(
                        $customerUserId,
                        'Không gửi được email cho booking ' . $booking->booking_code,
                        'Khách sạn không gửi được email tới ' . $recipient . '. Thông tin booking vẫn được lưu trên tài khoản; vui lòng kiểm tra lại địa chỉ email hoặc liên hệ lễ tân.',
                        url('/booking-history/' . $booking->id),
                        'warning',
                        ['booking_id' => $booking->id]
                    );
                }
            }
            throw $e;
        }
    }
}
