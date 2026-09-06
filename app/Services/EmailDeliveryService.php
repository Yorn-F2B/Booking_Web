<?php

namespace App\Services;

use App\Mail\AdminVnpayPaymentRequestMail;
use App\Mail\GuestBookingCancelledMail;
use App\Mail\RoomSelectionResultMail;
use App\Models\Booking;
use App\Models\EmailDeliveryLog;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailDeliveryService
{
    public function send(string $recipient, Mailable $mail, string $mailType, ?Booking $booking = null, ?string $subject = null): bool
    {
        try {
            $this->sendOrFail($recipient, $mail, $mailType, $booking, $subject);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function sendOrFail(string $recipient, Mailable $mail, string $mailType, ?Booking $booking = null, ?string $subject = null): void
    {
        $log = EmailDeliveryLog::create([
            'booking_id' => $booking?->id,
            'recipient' => $recipient,
            'mail_type' => $mailType,
            'subject' => $subject,
            'status' => 'pending',
            'attempts' => 1,
        ]);

        $notificationTitle = $subject ?: 'Thông báo từ khách sạn';
        $notificationMessage = 'Khách sạn vừa có cập nhật mới dành cho bạn.';
        $notificationType = 'info';
        $customerUserId = 0;

        if ($booking) {
            $booking->loadMissing(['customer', 'payments', 'bookingRooms.room.category', 'roomCategory']);
            $customerUserId = (int) ($booking->customer?->user_id ?? 0);
            [$notificationTitle, $notificationMessage, $notificationType] = $this->customerNotificationContent(
                $mailType,
                $booking,
                $mail
            );
        }

        // Lưu luôn nội dung nghiệp vụ để phía admin có thể mở lại lịch sử giao
        // thông báo của booking sau khi toast tức thời đã biến mất.
        $log->update([
            'subject' => $notificationTitle,
            'meta' => [
                'notification_title' => $notificationTitle,
                'notification_message' => $notificationMessage,
                'notification_type' => $notificationType,
                'mail_type' => $mailType,
            ],
        ]);

        try {
            Mail::to($recipient)->send($mail);
            $log->update(['status' => 'sent', 'sent_at' => now('Asia/Ho_Chi_Minh')]);

            if ($booking) {
                if ($customerUserId > 0) {
                    app(OperationalNotificationService::class)->toUser(
                        $customerUserId,
                        $notificationTitle,
                        $notificationMessage,
                        url('/booking-history/' . $booking->id),
                        $notificationType,
                        [
                            'booking_id' => $booking->id,
                            'meta' => [
                                'mail_type' => $mailType,
                                'email_recipient' => $recipient,
                                'email_already_sent' => true,
                            ],
                        ],
                        false // email nghiệp vụ gốc vừa gửi thành công, không gửi trùng
                    );
                } else {
                    app(OperationalNotificationService::class)->auditEmailOnly(
                        $booking,
                        $recipient,
                        $notificationTitle,
                        $notificationMessage,
                        'sent',
                        false
                    );
                }
            }
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'failed_at' => now('Asia/Ho_Chi_Minh'),
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            if ($booking) {
                $hasWebNotification = false;

                // Email lỗi không được làm mất thông báo web của sự kiện nghiệp vụ.
                // Tạo thông báo web bằng đúng nội dung sự kiện, nhưng chặn audit tự động
                // để bên admin chỉ nhận một kết quả giao nhận chính xác: email = failed.
                if ($customerUserId > 0) {
                    app(OperationalNotificationService::class)->toUser(
                        $customerUserId,
                        $notificationTitle,
                        $notificationMessage,
                        url('/booking-history/' . $booking->id),
                        $notificationType,
                        [
                            'booking_id' => $booking->id,
                            'meta' => [
                                'mail_type' => $mailType,
                                'email_recipient' => $recipient,
                                'suppress_admin_audit' => true,
                            ],
                        ],
                        false
                    );
                    $hasWebNotification = true;
                }

                app(OperationalNotificationService::class)->auditEmailOnly(
                    $booking,
                    $recipient,
                    $notificationTitle,
                    $notificationMessage,
                    'failed',
                    $hasWebNotification
                );
            } else {
                app(OperationalNotificationService::class)->toRoles(
                    ['super_admin', 'manager', 'receptionist_lead', 'receptionist'],
                    'Không gửi được email cho khách',
                    'Email ' . $recipient . ' gửi thất bại. Hãy kiểm tra địa chỉ hoặc cấu hình gửi mail.',
                    null,
                    'warning'
                );
            }

            throw $e;
        }
    }

    /**
     * Chuyển mail_type nội bộ thành nội dung mà khách có thể hiểu ngay.
     * Tuyệt đối không đưa các mã kỹ thuật như room_issue_form,
     * payment_success_booking_confirmation... ra giao diện khách hàng.
     */
    public function customerNotificationContent(string $mailType, Booking $booking, ?Mailable $mail = null): array
    {
        $code = (string) $booking->booking_code;
        $checkIn = $booking->check_in_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
        $checkOut = $booking->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
        $stayText = ($checkIn && $checkOut)
            ? ' Thời gian lưu trú: nhận phòng ' . $checkIn . ', trả phòng ' . $checkOut . '.'
            : '';

        return match ($mailType) {
            'booking_confirmation' => [
                'Đặt phòng ' . $code . ' đã được ghi nhận',
                'Khách sạn đã ghi nhận booking ' . $code . '.'
                    . $stayText
                    . ' Tổng tiền tạm tính: ' . $this->money($booking->estimated_total ?: $booking->final_total) . '.',
                'success',
            ],

            'payment_success_booking_confirmation' => [
                'Thanh toán booking ' . $code . ' thành công',
                'Thanh toán cho booking ' . $code . ' đã thành công và đặt phòng đã được xác nhận.'
                    . ' Tổng số tiền hệ thống đã ghi nhận thanh toán thành công: ' . $this->money($booking->payments->where('status', 'success')->sum('amount')) . '.'
                    . $stayText,
                'success',
            ],

            'vnpay_payment_request' => [
                'Yêu cầu thanh toán booking ' . $code,
                'Khách sạn đã tạo yêu cầu thanh toán VNPay cho booking ' . $code
                    . ($mail instanceof AdminVnpayPaymentRequestMail ? ' với số tiền ' . $this->money($mail->payment->amount) : '')
                    . ($mail instanceof AdminVnpayPaymentRequestMail && $mail->expiresAt
                        ? '. Yêu cầu có hiệu lực đến ' . $this->dateTime($mail->expiresAt)
                        : '')
                    . '. Vui lòng mở chi tiết booking hoặc email để thực hiện thanh toán.',
                'info',
            ],

            'room_selection_result' => $this->roomSelectionNotification($booking, $mail),

            'late_arrival_form' => [
                'Cần xác nhận giờ đến muộn cho booking ' . $code,
                'Khách sạn đã gửi biểu mẫu xác nhận đến muộn cho booking ' . $code . '. Vui lòng mở email và gửi lại giờ dự kiến đến để khách sạn tiếp tục giữ phòng theo đúng chính sách.',
                'warning',
            ],

            'room_issue_form' => [
                'Biểu mẫu báo sự cố phòng - booking ' . $code,
                'Khách sạn đã gửi biểu mẫu báo sự cố phòng cho booking ' . $code . '. Vui lòng mở email, mô tả rõ tình trạng đang gặp và gửi lại để bộ phận phụ trách xử lý.',
                'warning',
            ],

            'booking_cancelled' => $this->bookingCancelledNotification($booking, $mail),

            'guest_booking_lookup_otp' => [
                'Mã xác thực tra cứu booking ' . $code . ' đã được gửi',
                'Mã xác thực dùng để tra cứu booking ' . $code . ' vừa được gửi tới email của bạn. Mã chỉ có hiệu lực trong thời gian ghi trong email.',
                'info',
            ],

            'booking_cancel_otp' => [
                'Mã xác thực hủy booking ' . $code . ' đã được gửi',
                'Mã xác thực cho yêu cầu hủy booking ' . $code . ' vừa được gửi tới email của bạn. Chỉ nhập mã này khi chính bạn đang thực hiện thao tác hủy booking.',
                'warning',
            ],

            default => [
                'Có cập nhật cho booking ' . $code,
                'Khách sạn vừa gửi một cập nhật mới liên quan đến booking ' . $code . ' tới email ' . $booking->booked_customer_email . '. Vui lòng mở chi tiết booking để kiểm tra nội dung.',
                'info',
            ],
        };
    }

    private function roomSelectionNotification(Booking $booking, ?Mailable $mail): array
    {
        $code = (string) $booking->booking_code;
        $rooms = $booking->bookingRooms->pluck('room.room_number')->filter()->unique()->values()->implode(', ');

        if ($mail instanceof RoomSelectionResultMail && $mail->fulfilled) {
            $message = 'Yêu cầu chọn phòng của booking ' . $code . ' đã được khách sạn đáp ứng.'
                . ($rooms !== '' ? ' Phòng được xếp: ' . $rooms . '.' : '')
                . ((float) ($booking->room_selection_fee ?? 0) > 0
                    ? ' Phí đảm bảo yêu cầu phòng: ' . $this->money($booking->room_selection_fee) . '.'
                    : '')
                . ($mail->handlingNote !== '' ? ' Ghi chú của khách sạn: ' . $mail->handlingNote : '');

            return ['Đã xác nhận phòng cho booking ' . $code, $message, 'success'];
        }

        $message = 'Khách sạn hiện chưa thể đáp ứng đúng yêu cầu chọn phòng của booking ' . $code . '.'
            . ($rooms !== '' ? ' Phòng dự phòng đang giữ cho bạn: ' . $rooms . '.' : '')
            . ' Khoản phí đảm bảo yêu cầu phòng không được tính.'
            . ($mail instanceof RoomSelectionResultMail && $mail->handlingNote !== ''
                ? ' Lý do/ghi chú: ' . $mail->handlingNote
                : '')
            . ' Vui lòng kiểm tra email để xác nhận phương án phòng dự phòng.';

        return ['Cần xác nhận phương án phòng cho booking ' . $code, $message, 'warning'];
    }

    private function bookingCancelledNotification(Booking $booking, ?Mailable $mail): array
    {
        $code = (string) $booking->booking_code;
        $paidAmount = $mail instanceof GuestBookingCancelledMail
            ? (float) $mail->paidAmount
            : (float) $booking->payments->where('status', 'success')->sum('amount');
        $reason = $mail instanceof GuestBookingCancelledMail ? trim($mail->reason) : '';
        $refund = max(0, (float) ($booking->refund_due_amount ?? 0));

        $message = 'Booking ' . $code . ' đã được hủy.'
            . ($reason !== '' ? ' Lý do: ' . $reason . '.' : '')
            . ' Số tiền đã thanh toán được ghi nhận: ' . $this->money($paidAmount) . '.'
            . ($refund > 0
                ? ' Số tiền dự kiến hoàn lại: ' . $this->money($refund) . '. Trạng thái hoàn tiền hiện tại: ' . $this->refundStatusLabel((string) $booking->refund_status) . '.'
                : ' Booking hiện không có khoản hoàn tiền đang chờ xử lý.');

        return ['Booking ' . $code . ' đã được hủy', $message, 'warning'];
    }

    private function money($amount): string
    {
        return number_format(max(0, (float) $amount), 0, ',', '.') . 'đ';
    }

    private function dateTime($value): string
    {
        try {
            return \Carbon\Carbon::parse($value)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function refundStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'đang chờ hoàn tiền',
            'completed' => 'đã hoàn tiền',
            'not_required', 'none', '' => 'không cần hoàn tiền',
            default => 'đang được khách sạn xử lý',
        };
    }
}
