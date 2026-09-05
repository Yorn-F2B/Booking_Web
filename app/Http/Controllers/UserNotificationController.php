<?php

namespace App\Http\Controllers;

use App\Models\OperationalNotification;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = OperationalNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('user.pages.notifications', compact('notifications'));
    }

    public function open(Request $request, OperationalNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);
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
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now('Asia/Ho_Chi_Minh')]);

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
