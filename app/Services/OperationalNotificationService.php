<?php

namespace App\Services;

use App\Mail\OperationalNotificationMail;
use App\Models\Booking;
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
            try {
                Mail::to($user->email)->send(new OperationalNotificationMail($title, $message, $url));
                $emailStatus = 'sent';
            } catch (\Throwable $e) {
                $emailStatus = 'failed';
                Log::warning('Không gửi được email tương ứng với thông báo khách hàng.', [
                    'user_id' => $userId,
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
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
        if ($email !== '') {
            $emailStatus = 'failed';
            try {
                Mail::to($email)->send(new OperationalNotificationMail($title, $message, $targetUrl));
                $emailStatus = 'sent';
            } catch (\Throwable $e) {
                Log::warning('Không gửi được email cập nhật booking cho khách không có tài khoản.', [
                    'booking_id' => $booking->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }

            $pseudoUser = new User([
                'name' => $booking->booked_customer_name ?: 'Khách hàng',
                'email' => $email,
                'role' => 'customer',
            ]);
            $pseudoUser->id = 0;
            $this->auditCustomerDelivery($pseudoUser, $title, $message, route('admin.bookings.show', $booking), $extra, $emailStatus, false);
        }

        return null;
    }

    public function auditEmailOnly(?Booking $booking, string $recipient, string $title, string $message, string $emailStatus = 'sent'): void
    {
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
            false
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

        $channelText = match ($emailStatus) {
            'sent' => $hasWebNotification ? 'web + email' : 'email',
            'failed' => $hasWebNotification ? 'web; email gửi thất bại' : 'email gửi thất bại',
            'not_available' => $hasWebNotification ? 'web; khách chưa có email' : 'không có kênh gửi',
            default => $hasWebNotification ? 'web' : 'email',
        };

        $auditTitle = $emailStatus === 'failed'
            ? 'Thông báo khách: email gửi thất bại'
            : 'Đã gửi thông báo cho khách hàng';
        $auditMessage = 'Đã gửi tới ' . $identity . ' qua ' . $channelText . '. Nội dung: ' . $title . ' — ' . $message;

        $bookingId = $extra['booking_id'] ?? null;
        $adminUrl = $bookingId ? route('admin.bookings.show', $bookingId) : $url;
        $auditType = $emailStatus === 'failed' ? 'warning' : 'success';

        $auditExtra = [
            'booking_id' => $bookingId,
            'room_id' => $extra['room_id'] ?? null,
            'meta' => [
                'event' => 'customer_notification_delivery',
                'customer_user_id' => $customer->id ?: null,
                'customer_email' => $customer->email,
                'email_status' => $emailStatus,
                'customer_title' => $title,
                'suppress_admin_audit' => true,
            ],
        ];

        $this->toRoles(self::STAFF_AUDIT_ROLES, $auditTitle, $auditMessage, $adminUrl, $auditType, $auditExtra);
    }
}
