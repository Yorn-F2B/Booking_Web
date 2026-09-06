<?php

namespace App\Http\Controllers;

use App\Models\OperationalNotification;
use App\Services\EmailDeliveryService;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request, EmailDeliveryService $emailDeliveryService)
    {
        $notifications = OperationalNotification::query()
            ->visibleTo($request->user())
            ->with(['booking.customer', 'booking.payments', 'booking.bookingRooms.room.category', 'booking.roomCategory'])
            ->latest()
            ->paginate(20);

        // Các notification cũ đã lưu trước bản sửa có thể chứa mail_type kỹ thuật
        // như payment_success_booking_confirmation/room_issue_form. Chuẩn hóa ngay
        // khi hiển thị để khách không còn nhìn thấy mã nội bộ, không cần xóa lịch sử.
        $notifications->getCollection()->transform(function (OperationalNotification $notification) use ($emailDeliveryService) {
            $mailType = data_get($notification->meta, 'mail_type');

            if (!$mailType && preg_match('/gửi email\s+["“]([^"”]+)["”]/ui', (string) $notification->message, $matches)) {
                $mailType = trim((string) ($matches[1] ?? ''));
            }

            if ($mailType && $notification->booking) {
                [$title, $message, $type] = $emailDeliveryService->customerNotificationContent(
                    (string) $mailType,
                    $notification->booking
                );

                $notification->setAttribute('title', $title);
                $notification->setAttribute('message', $message);
                $notification->setAttribute('type', $type);
            }

            return $notification;
        });

        return view('user.pages.notifications', compact('notifications'));
    }

    public function open(Request $request, OperationalNotification $notification)
    {
        $canOpen = (int) $notification->user_id === (int) $request->user()->id
            || ($notification->user_id === null && $notification->role === $request->user()->role);
        abort_unless($canOpen, 403);
        if (!$notification->read_at) {
            $notification->forceFill(['read_at' => now('Asia/Ho_Chi_Minh')])->save();
        }

        return redirect()->to($this->safeTargetUrl(
            $notification->target_url,
            $request,
            route('notifications.index')
        ));
    }

    private function safeTargetUrl(?string $targetUrl, Request $request, string $fallback): string
    {
        if (!$targetUrl) {
            return $fallback;
        }

        $parts = parse_url($targetUrl);
        if ($parts === false) {
            return $fallback;
        }
        if (isset($parts['scheme']) && !in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return $fallback;
        }
        if (isset($parts['host']) && strcasecmp((string) $parts['host'], $request->getHost()) !== 0) {
            return $fallback;
        }
        if (!isset($parts['host']) && !str_starts_with($targetUrl, '/')) {
            return $fallback;
        }

        return $targetUrl;
    }

    public function markAllRead(Request $request)
    {
        OperationalNotification::query()
            ->visibleTo($request->user())
            ->whereNull('read_at')
            ->update(['read_at' => now('Asia/Ho_Chi_Minh')]);

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
