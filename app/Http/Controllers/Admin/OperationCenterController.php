<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\CustomerRequest;
use App\Models\EmailDeliveryLog;
use App\Models\OperationalNotification;
use App\Models\Room;
use App\Models\RoomInspection;
use App\Models\RoomIssueRequest;
use App\Support\HousekeepingWorkScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperationCenterController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = (string) $user->role;
        $tasks = collect();
        $frontDesk = in_array($role, ['super_admin', 'manager', 'receptionist_lead', 'receptionist'], true);
        $management = in_array($role, ['super_admin', 'manager'], true);
        $housekeeping = in_array($role, ['super_admin', 'manager', 'housekeeping_supervisor', 'housekeeping'], true);
        $now = now('Asia/Ho_Chi_Minh');

        if ($frontDesk) {
            // Việc quá hạn luôn đứng đầu.
            $lateCheckout = Booking::query()
                ->whereIn('status', ['checked_in', 'inspection_requested'])
                ->whereNotNull('check_out_at')
                ->where('check_out_at', '<', $now);
            $this->scopeFrontDeskBookings($lateCheckout, $user, $role);
            $lateCheckout->with('customer')->orderBy('check_out_at')->get()->each(fn ($b) => $tasks->push([
                'priority' => 'high', 'type' => 'late_checkout', 'title' => 'Khách đã quá giờ trả phòng',
                'detail' => $b->booking_code . ' · ' . ($b->booked_customer_name ?: 'Khách'),
                'url' => route('admin.bookings.show', $b), 'time' => $b->check_out_at,
            ]));

            // Booking sắp nhận nhưng chưa có phòng là rủi ro vận hành lớn hơn booking pending thông thường.
            $unassigned = Booking::query()
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereBetween('check_in_at', [$now->copy()->subHour(), $now->copy()->addHours(12)])
                ->whereDoesntHave('bookingRooms');
            $this->scopeFrontDeskBookings($unassigned, $user, $role);
            $unassigned->orderBy('check_in_at')->get()->each(fn ($b) => $tasks->push([
                'priority' => 'high', 'type' => 'unassigned_arrival', 'title' => 'Khách sắp đến nhưng chưa gán phòng',
                'detail' => $b->booking_code . ' · nhận ' . optional($b->check_in_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m H:i'),
                'url' => route('admin.bookings.show', $b), 'time' => $b->check_in_at,
            ]));

            // Booking đã có phòng và sắp check-in: nhắc lễ tân chuẩn bị nhưng ưu tiên thấp hơn ngoại lệ.
            $upcoming = Booking::query()
                ->where('status', 'confirmed')
                ->whereBetween('check_in_at', [$now, $now->copy()->addHours(4)])
                ->whereHas('bookingRooms');
            $this->scopeFrontDeskBookings($upcoming, $user, $role);
            $upcoming->orderBy('check_in_at')->get()->each(fn ($b) => $tasks->push([
                'priority' => 'medium', 'type' => 'upcoming_checkin', 'title' => 'Khách sắp check-in',
                'detail' => $b->booking_code . ' · ' . optional($b->check_in_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i'),
                'url' => route('admin.bookings.show', $b), 'time' => $b->check_in_at,
            ]));

            // Lễ tân cần biết yêu cầu đến muộn đã được duyệt, không chỉ người duyệt biết.
            CustomerRequest::query()
                ->where('type', 'late_arrival')
                ->where('status', 'approved')
                ->whereNotNull('expected_arrival_at')
                ->whereBetween('expected_arrival_at', [$now->copy()->subHours(2), $now->copy()->addHours(12)])
                ->whereHas('booking', function ($booking) use ($user, $role) {
                    $booking->whereNotIn('status', ['checked_in', 'checked_out', 'completed', 'cancelled']);
                    $this->scopeFrontDeskBookings($booking, $user, $role);
                })
                ->with('booking')
                ->orderBy('expected_arrival_at')
                ->get()
                ->each(function ($r) use ($tasks) {
                    $tasks->push([
                        'priority' => 'medium', 'type' => 'approved_late_arrival', 'title' => 'Khách đã xác nhận đến muộn',
                        'detail' => $r->booking->booking_code . ' · dự kiến ' . optional($r->expected_arrival_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m H:i'),
                        'url' => route('admin.bookings.show', $r->booking), 'time' => $r->expected_arrival_at,
                    ]);
                });

            $pending = Booking::query()
                ->where('status', 'pending')
                ->where(function ($query) {
                    // Booking online chưa thanh toán là luồng tự phục vụ của khách;
                    // không biến toàn bộ chúng thành việc phải click của lễ tân.
                    $query->where('booking_source', '!=', 'user_online')
                        ->orWhere('payment_status', '!=', 'unpaid');
                })
                ->where(function ($query) use ($now) {
                    // Nhóm sắp đến nhưng chưa gán phòng đã có task high riêng.
                    $query->whereHas('bookingRooms')
                        ->orWhere('check_in_at', '<', $now->copy()->subHour())
                        ->orWhere('check_in_at', '>', $now->copy()->addHours(12));
                });
            $this->scopeFrontDeskBookings($pending, $user, $role);
            $pending->orderBy('created_at')->get()->each(fn ($b) => $tasks->push([
                'priority' => 'medium', 'type' => 'pending_booking', 'title' => 'Booking chờ xử lý',
                'detail' => $b->booking_code, 'url' => route('admin.bookings.show', $b), 'time' => $b->created_at,
            ]));

            // Một booking chỉ xuất hiện một task thanh toán, lấy ngoại lệ mới nhất.
            // Nếu khách thử VNPay 3 lần thất bại thì lễ tân vẫn chỉ cần mở 1 đơn để xử lý.
            $paymentExceptionFilter = function ($q) use ($now) {
                $q->where(function ($exception) use ($now) {
                    $exception->where(fn ($x) => $x->where('status', 'pending')->where('created_at', '<=', $now->copy()->subMinutes(15)))
                        ->orWhere(fn ($x) => $x->where('status', 'failed')->where('updated_at', '>=', $now->copy()->subDay()));
                });
            };
            $paymentExceptionBookings = Booking::query()
                ->whereHas('payments', $paymentExceptionFilter)
                ->with(['payments' => function ($q) use ($paymentExceptionFilter) {
                    $paymentExceptionFilter($q);
                    $q->latest('updated_at');
                }])
                ->withMax(['payments as payment_exception_at' => $paymentExceptionFilter], 'updated_at');
            $this->scopeFrontDeskBookings($paymentExceptionBookings, $user, $role);
            $paymentExceptionBookings->orderByDesc('payment_exception_at')->get()
                ->each(function ($booking) use ($tasks) {
                    $payment = $booking->payments->first();
                    if (!$payment) {
                        return;
                    }
                    $tasks->push([
                        'priority' => $payment->status === 'failed' ? 'high' : 'medium',
                        'type' => 'payment_exception',
                        'title' => $payment->status === 'failed' ? 'Thanh toán thất bại' : 'Thanh toán đang treo',
                        'detail' => $booking->booking_code . ' · ' . number_format((float) $payment->amount, 0, ',', '.') . 'đ',
                        'url' => route('admin.bookings.show', $booking), 'time' => $payment->updated_at,
                    ]);
                });


        }

        if ($management) {
            Booking::query()
                ->whereIn('status', ['cancelled', 'canceled'])
                ->where('refund_status', 'pending')
                ->where('refund_due_amount', '>', 0)
                ->oldest('updated_at')
                ->get()
                ->each(fn ($b) => $tasks->push([
                    'priority' => 'high', 'type' => 'refund_pending', 'title' => 'Khoản hoàn tiền đang chờ xử lý',
                    'detail' => $b->booking_code . ' · cần hoàn ' . number_format((float) $b->refund_due_amount, 0, ',', '.') . 'đ',
                    'url' => route('admin.bookings.show', $b), 'time' => $b->updated_at,
                ]));

            CustomerRequest::query()->where('type', 'late_arrival')->where('status', 'pending')->with('booking')->get()->each(fn ($r) => $tasks->push([
                'priority' => 'high', 'type' => 'late_arrival_approval', 'title' => 'Yêu cầu đến muộn chờ duyệt',
                'detail' => $r->booking?->booking_code ?? ('Yêu cầu #' . $r->id),
                'url' => $r->booking ? route('admin.bookings.show', $r->booking) : route('admin.customer-requests.show', $r), 'time' => $r->created_at,
            ]));

            RoomIssueRequest::query()->needsManagerAction()->with('booking')->get()->each(fn ($r) => $tasks->push([
                'priority' => 'high', 'type' => 'room_issue', 'title' => 'Sự cố phòng cần quyết định',
                'detail' => $r->booking?->booking_code ?? ('Sự cố #' . $r->id),
                'url' => route('admin.room-issues.show', $r), 'time' => $r->created_at,
            ]));

            EmailDeliveryLog::query()->unresolvedFailures()->latest('failed_at')->get()->each(fn ($e) => $tasks->push([
                'priority' => 'high', 'type' => 'email_failed', 'title' => 'Email gửi khách thất bại',
                'detail' => $e->recipient . ' · ' . $e->mail_type,
                'url' => $e->booking_id ? route('admin.bookings.show', $e->booking_id) : route('admin.email-logs.index'),
                'time' => $e->failed_at ?: $e->updated_at,
            ]));

            Room::query()
                ->where('status', 'maintenance')
                ->whereNotNull('status_until')
                ->where('status_until', '<=', $now)
                ->with('category')
                ->orderBy('status_until')
                ->get()->each(fn ($room) => $tasks->push([
                    'priority' => 'high', 'type' => 'maintenance_overdue', 'title' => 'Bảo trì phòng đã quá thời gian dự kiến',
                    'detail' => 'Phòng ' . $room->room_number . ' · ' . ($room->category?->name ?: 'Chưa rõ hạng'),
                    'url' => route('admin.rooms.show', $room), 'time' => $room->status_until,
                ]));
        }

        if ($housekeeping) {
            $cleaningRooms = Room::query()->where('status', 'cleaning');
            HousekeepingWorkScope::applyToRooms($cleaningRooms, $user);
            $cleaningRooms->with('category')->orderBy('updated_at')->get()->each(function ($room) use ($tasks) {
                $isPriority = str_contains((string) $room->note, '[PRIORITY_BOOKING:');
                $tasks->push([
                    'priority' => $isPriority ? 'high' : 'medium',
                    'type' => $isPriority ? 'priority_room_cleaning' : 'room_cleaning',
                    'title' => $isPriority
                        ? 'Ưu tiên dọn nhanh phòng ' . $room->room_number
                        : 'Phòng ' . $room->room_number . ' cần hoàn tất dọn',
                    'detail' => ($room->category?->name ?: 'Phòng đang dọn')
                        . ($isPriority ? ' · đã được lễ tân/booking chọn, cần hoàn tất sớm' : ''),
                    'url' => route('admin.housekeeping.index'),
                    'time' => $room->updated_at,
                ]);
            });

            $verification = RoomIssueRequest::query()->where('status', 'pending')->where('workflow_status', 'awaiting_housekeeping');
            HousekeepingWorkScope::applyToIssues($verification, $user);
            $verification->with('currentRoom')->orderBy('created_at')->get()->each(fn ($issue) => $tasks->push([
                'priority' => 'high', 'type' => 'issue_verification', 'title' => 'Cần xác minh sự cố phòng',
                'detail' => 'Phòng ' . ($issue->currentRoom?->room_number ?? ('#' . $issue->current_room_id)),
                'url' => route('admin.room-issue-verifications.show', $issue), 'time' => $issue->created_at,
            ]));

            $inspections = RoomInspection::query()->where(function ($query) {
                $query->where(function ($initial) {
                    $initial->where('workflow_stage', RoomInspection::STAGE_HOUSEKEEPING_REPORT)->whereIn('status', ['pending', 'rejected']);
                })->orWhere(function ($recheck) {
                    $recheck->where('workflow_stage', RoomInspection::STAGE_HOUSEKEEPING_RECHECK)->where('status', 'reported');
                });
            });
            HousekeepingWorkScope::applyToInspections($inspections, $user);
            $inspections->with('room')->orderBy('updated_at')->get()->each(fn ($inspection) => $tasks->push([
                'priority' => 'medium', 'type' => 'floor_inspection', 'title' => 'Cần kiểm tra phòng',
                'detail' => 'Phòng ' . ($inspection->room?->room_number ?? ('#' . $inspection->room_id)),
                'url' => route('admin.floor-inspections.show', $inspection), 'time' => $inspection->updated_at,
            ]));

            $repairs = RoomIssueRequest::query()->whereIn('status', ['approved', 'repair_only'])->where('repair_status', 'waiting');
            HousekeepingWorkScope::applyToIssues($repairs, $user);
            $repairs->with('currentRoom')->orderBy('updated_at')->get()->each(fn ($issue) => $tasks->push([
                'priority' => 'high', 'type' => 'room_repair', 'title' => 'Phòng đang chờ sửa',
                'detail' => 'Phòng ' . ($issue->currentRoom?->room_number ?? ('#' . $issue->current_room_id)),
                'url' => route('admin.room-repairs.show', $issue), 'time' => $issue->updated_at,
            ]));
        }

        // Loại các task trùng hoàn toàn rồi ưu tiên việc high và việc đến hạn sớm hơn.
        $tasks = $tasks->unique(fn ($t) => ($t['type'] ?? '') . '|' . ($t['url'] ?? '') . '|' . ($t['detail'] ?? ''));
        $priorityRank = ['high' => 0, 'medium' => 1, 'low' => 2];
        $tasks = $tasks->sortBy(fn ($t) => sprintf('%d|%s', $priorityRank[$t['priority']] ?? 9, (string) ($t['time'] ?? '')))->values();

        $taskGroups = $tasks->groupBy('type')->map(function ($items, $type) use ($priorityRank) {
            $items = $items->values();
            $first = $items->first();
            $highestPriority = $items->sortBy(fn ($item) => $priorityRank[$item['priority']] ?? 9)->first()['priority'] ?? 'low';

            return [
                'type' => $type,
                'title' => $first['title'] ?? 'Công việc cần xử lý',
                'priority' => $highestPriority,
                'count' => $items->count(),
                'items' => $items,
            ];
        })->sortBy(fn ($group) => sprintf('%d|%s', $priorityRank[$group['priority']] ?? 9, $group['title']))->values();

        $notifications = OperationalNotification::query()
            ->visibleTo($user)
            ->latest()->paginate(30, ['*'], 'notification_page');
        $notificationGroups = collect($notifications->items())
            ->groupBy(fn ($notification) => ($notification->type ?: 'info') . '|' . $notification->title)
            ->map(function ($items) {
                $items = $items->values();
                return [
                    'title' => $items->first()->title ?: 'Thông báo',
                    'count' => $items->count(),
                    'unread_count' => $items->whereNull('read_at')->count(),
                    'items' => $items,
                ];
            })->values();

        return view('admin.pages.operation-center.index', compact('tasks', 'taskGroups', 'notifications', 'notificationGroups'));
    }

    private function scopeFrontDeskBookings($query, $user, string $role): void
    {
        if ($role === 'receptionist') {
            $query->visibleToOperationsUser($user);
        }
    }

    private function frontDeskCanSeeBooking(Booking $booking, $user, string $role): bool
    {
        if ($role !== 'receptionist') {
            return true;
        }

        return Booking::query()->whereKey($booking->id)->visibleToOperationsUser($user)->exists();
    }

    public function open(Request $request, OperationalNotification $notification)
    {
        $user = Auth::user();
        $canOpen = (int) $notification->user_id === (int) $user->id
            || ($notification->user_id === null && $notification->role === $user->role);
        abort_unless($canOpen, 403);
        if (!$notification->read_at) {
            $notification->update(['read_at' => now('Asia/Ho_Chi_Minh')]);
        }

        return redirect()->to($this->safeTargetUrl(
            $notification->target_url,
            $request,
            route('admin.operation-center.index')
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

    public function markAllRead()
    {
        $user = Auth::user();
        OperationalNotification::query()
            ->visibleTo($user)
            ->whereNull('read_at')
            ->update(['read_at' => now('Asia/Ho_Chi_Minh')]);

        return back()->with('success', 'Đã đánh dấu các thông báo là đã đọc.');
    }
}
