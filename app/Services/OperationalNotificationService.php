<?php

namespace App\Services;

use App\Mail\OperationalNotificationMail;
use App\Models\Booking;
use App\Models\EmailDeliveryLog;
use App\Models\OperationalNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OperationalNotificationService
{
    private const STAFF_AUDIT_ROLES = ['super_admin', 'manager', 'receptionist_lead', 'receptionist'];

    public function toUser(
        int $userId,
        string $title,
        string $message,
        ?string $url = null,
        string $type = 'info',
        array $extra = [],
        bool $sendCustomerEmail = true
    ): OperationalNotification {
        $user = User::query()->select(['id', 'name', 'email', 'role'])->find($userId);

        $notification = OperationalNotification::create(array_merge($extra, [
            'user_id' => $userId,
            'role' => null,
            'title' => $title,
            'message' => $message,
            'target_url' => $url,
            'type' => $type,
        ]));

        if (!$user || $user->role !== 'customer') {
            return $notification;
        }

        $emailStatus = 'not_available';
        $emailAlreadySent = (bool) data_get($extra, 'meta.email_already_sent', false);

        if ($emailAlreadySent) {
            $emailStatus = 'sent';
        } elseif ($sendCustomerEmail && filled($user->email)) {
            $emailStatus = $this->sendCustomerEmailLogged(
                $user->email,
                $title,
                $message,
                $url,
                $extra['booking_id'] ?? null,
                $notification->id
            );
        } elseif (!$sendCustomerEmail) {
            $emailStatus = 'skipped';
        }

        if (!(bool) data_get($extra, 'meta.suppress_admin_audit', false)) {
            $this->auditCustomerDelivery($user, $title, $message, $url, $extra, $emailStatus);
        }

        return $notification;
    }

    /**
     * Gửi đồng thời thông báo web + email cho khách của booking.
     * Booking tại quầy không có tài khoản thì vẫn có thể gửi email nếu có email;
     * trường hợp đó không có trang thông báo web để gắn với người dùng.
     */
    public function toBookingCustomer(
        Booking $booking,
        string $title,
        string $message,
        string $type = 'info',
        ?string $url = null,
        array $extra = []
    ): ?OperationalNotification {
        $booking->loadMissing('customer.user');
        $userId = (int) ($booking->customer?->user_id ?? 0);
        $targetUrl = $url ?: url('/booking-history/' . $booking->id);
        $extra = array_replace_recursive([
            'booking_id' => $booking->id,
            'meta' => ['event' => 'booking_customer_update'],
        ], $extra);

        if ($userId > 0) {
            return $this->toUser($userId, $title, $message, $targetUrl, $type, $extra, true);
        }

        $email = trim((string) ($booking->booked_customer_email ?: $booking->customer?->email));
        $emailStatus = $email !== '' ? 'failed' : 'not_available';

        if ($email !== '') {
            $emailStatus = $this->sendCustomerEmailLogged(
                $email,
                $title,
                $message,
                $targetUrl,
                $booking->id
            );
        }

        // Dù booking tại quầy không có tài khoản/email, phía admin vẫn phải biết
        // thông báo này không có kênh để gửi thay vì im lặng như trước.
        $pseudoUser = new User([
            'name' => $booking->booked_customer_name ?: 'Khách hàng',
            'email' => $email !== '' ? $email : null,
            'role' => 'customer',
        ]);
        $pseudoUser->id = 0;
        $this->auditCustomerDelivery(
            $pseudoUser,
            $title,
            $message,
            route('admin.bookings.show', $booking),
            $extra,
            $emailStatus,
            false
        );

        return null;
    }

    public function auditEmailOnly(
        ?Booking $booking,
        string $recipient,
        string $title,
        string $message,
        string $emailStatus = 'sent',
        bool $hasWebNotification = false
    ): void {
        $pseudoUser = new User([
            'name' => $booking?->booked_customer_name ?: 'Khách hàng',
            'email' => $recipient,
            'role' => 'customer',
        ]);
        $pseudoUser->id = 0;

        $this->auditCustomerDelivery(
            $pseudoUser,
            $title,
            $message,
            $booking ? route('admin.bookings.show', $booking) : null,
            ['booking_id' => $booking?->id],
            $emailStatus,
            $hasWebNotification
        );
    }

    public function toRoles(array $roles, string $title, string $message, ?string $url = null, string $type = 'info', array $extra = []): void
    {
        User::query()->whereIn('role', array_values(array_unique($roles)))
            ->pluck('id')
            ->each(fn ($userId) => $this->toUser((int) $userId, $title, $message, $url, $type, $extra, false));
    }

    private function auditCustomerDelivery(
        User $customer,
        string $title,
        string $message,
        ?string $url,
        array $extra,
        string $emailStatus,
        bool $hasWebNotification = true
    ): void {
        $identity = trim((string) $customer->name);
        if ($identity === '') {
            $identity = 'Khách hàng';
        }
        if (filled($customer->email)) {
            $identity .= ' (' . $customer->email . ')';
        }

        $bookingId = $extra['booking_id'] ?? null;

        // Không đưa biên nhận gửi email/web vào Trung tâm công việc.
        // Trung tâm công việc chỉ dành cho việc vận hành còn phải xử lý.
        // Kết quả giao thông báo được phản hồi ngay cho người thao tác bằng toast bên dưới;
        // lịch sử email chi tiết vẫn được lưu ở EmailDeliveryLog.

        // Sau thao tác nghiệp vụ, nhân viên phải nhìn thấy ngay kết quả giao thông báo.
        // Dùng hàng đợi thay vì một flash duy nhất để một request phát sinh nhiều sự kiện
        // không làm toast sau ghi đè toast trước.
        $actor = auth()->user();
        if ($actor && request()->hasSession()) {
            $emailText = match ($emailStatus) {
                'sent' => filled($customer->email)
                    ? 'Đã gửi email thành công tới ' . $customer->email . '.'
                    : 'Đã gửi email thành công.',
                'failed' => filled($customer->email)
                    ? 'Không gửi được email tới ' . $customer->email . '.'
                    : 'Không gửi được email cho khách.',
                'not_available' => 'Không gửi email vì khách chưa có địa chỉ email.',
                'skipped' => 'Nghiệp vụ này không gửi email.',
                default => 'Chưa xác định được trạng thái gửi email.',
            };

            // Thành công dùng đúng kiểu panel xanh dương/info như các toast cập nhật phòng.
            $toastType = match ($emailStatus) {
                'failed' => 'error',
                'not_available', 'skipped' => 'warning',
                default => 'info',
            };

            $webText = $hasWebNotification
                ? 'Đã lưu thông báo trên trang Thông báo của khách.'
                : 'Booking này không có tài khoản web để nhận thông báo trên trang Thông báo.';

            $toast = [
                'type' => $toastType,
                'title' => $emailStatus === 'sent'
                    ? 'Đã gửi thông báo'
                    : ($emailStatus === 'failed' ? 'Gửi email thất bại' : 'Thông báo khách hàng'),
                'message' => $emailText
                    . "\n" . $webText
                    . "\nKhách: " . $identity
                    . "\nNội dung: " . $title
                    . (trim($message) !== '' ? " — " . trim($message) : ''),
                'duration' => $emailStatus === 'failed' ? 12000 : 10000,
                'dedupe_key' => implode('|', [
                    (string) ($bookingId ?? 0),
                    $title,
                    $emailStatus,
                    (string) $customer->email,
                ]),
            ];

            $queue = session()->get('customer_notification_deliveries', []);
            if (!is_array($queue)) {
                $queue = [];
            }

            $alreadyQueued = collect($queue)->contains(function ($item) use ($toast) {
                return is_array($item)
                    && ($item['dedupe_key'] ?? null) === $toast['dedupe_key'];
            });

            if (!$alreadyQueued) {
                $queue[] = $toast;
                // Tránh một thao tác bất thường tạo quá nhiều panel che màn hình.
                $queue = array_slice($queue, -6);
            }

            session()->flash('customer_notification_deliveries', $queue);
        }
    }

    /**
     * Gửi email thông báo vận hành và ghi EmailDeliveryLog để admin có bằng chứng
     * trạng thái sent/failed, thay vì chỉ suy đoán từ việc không có exception.
     */
    private function sendCustomerEmailLogged(
        string $recipient,
        string $title,
        string $message,
        ?string $url,
        ?int $bookingId = null,
        ?int $notificationId = null
    ): string {
        $log = EmailDeliveryLog::create([
            'booking_id' => $bookingId,
            'recipient' => $recipient,
            'mail_type' => 'operational_notification',
            'subject' => $title,
            'status' => 'pending',
            'attempts' => 1,
            'meta' => [
                'notification_title' => $title,
                'notification_message' => $message,
                'target_url' => $url,
                'notification_id' => $notificationId,
            ],
        ]);

        try {
            Mail::to($recipient)->send(new OperationalNotificationMail($title, $message, $url));
            $log->update(['status' => 'sent', 'sent_at' => now('Asia/Ho_Chi_Minh')]);
            return 'sent';
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'failed_at' => now('Asia/Ho_Chi_Minh'),
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
            ]);
            Log::warning('Không gửi được email tương ứng với thông báo khách hàng.', [
                'booking_id' => $bookingId,
                'notification_id' => $notificationId,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
            return 'failed';
        }
    }
}
