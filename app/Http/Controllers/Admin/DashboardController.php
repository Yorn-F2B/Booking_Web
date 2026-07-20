<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingServiceItem;
use App\Models\Room;
use App\Models\RoomCategory;
use App\Models\RoomInspection;
use App\Models\RoomInspectionItem;
use App\Models\StaffFloorAssignment;
use App\Models\StaffRoomAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private array $activeBookingStatuses = ['pending', 'confirmed', 'checked_in', 'inspection_requested'];

    public function index()
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();
        $user = Auth::user();

        $roomStatusLabels = $this->roomStatusLabels();
        $bookingStatusLabels = $this->bookingStatusLabels();
        $paymentStatusLabels = $this->paymentStatusLabels();

        $roomsByStatus = Room::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalRooms = (int) Room::query()->count();
        $occupiedRooms = (int) ($roomsByStatus['occupied'] ?? 0);
        $reservedRooms = (int) ($roomsByStatus['reserved'] ?? 0);
        $availableRooms = (int) ($roomsByStatus['available'] ?? 0);
        $cleaningRooms = (int) ($roomsByStatus['cleaning'] ?? 0);
        $inspectionRooms = (int) ($roomsByStatus['inspection'] ?? 0);
        $maintenanceRooms = (int) ($roomsByStatus['maintenance'] ?? 0);
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        $checkinsTodayCount = (int) $this->bookingQuery()
            ->whereBetween('check_in_at', [$todayStart, $todayEnd])
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->count();

        $checkoutsTodayCount = (int) $this->bookingQuery()
            ->whereBetween('check_out_at', [$todayStart, $todayEnd])
            ->whereIn('status', ['checked_in', 'inspection_requested', 'checked_out'])
            ->count();

        $checkinsToday = $this->bookingQuery()
            ->with(['customer', 'roomCategory', 'bookingRooms.room'])
            ->whereBetween('check_in_at', [$todayStart, $todayEnd])
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->orderBy('check_in_at')
            ->limit(8)
            ->get();

        $checkoutsToday = $this->bookingQuery()
            ->with(['customer', 'roomCategory', 'bookingRooms.room'])
            ->whereBetween('check_out_at', [$todayStart, $todayEnd])
            ->whereIn('status', ['checked_in', 'inspection_requested', 'checked_out'])
            ->orderBy('check_out_at')
            ->limit(8)
            ->get();

        $todayRevenue = $this->paidRevenueForPeriod($todayStart, $todayEnd);
        $monthRevenue = $this->paidRevenueForPeriod($monthStart, $monthEnd);
        $serviceRevenueToday = (float) BookingServiceItem::query()
            ->where('billing_status', 'confirmed')
            ->whereBetween('confirmed_at', [$todayStart, $todayEnd])
            ->sum('total');

        $receivableAmount = (float) $this->bookingQuery()
            ->whereIn('status', $this->activeBookingStatuses)
            ->selectRaw('COALESCE(SUM(GREATEST(COALESCE(estimated_total, 0) - COALESCE(deposit_amount, 0), 0)), 0) as total')
            ->value('total');

        $summaryCards = [
            [
                'label' => 'Công suất hôm nay',
                'value' => $occupiedRooms . '/' . max($totalRooms, 0),
                'sub' => $occupancyRate . '% phòng đang ở',
                'icon' => 'bx bx-pulse',
                'tone' => 'primary',
            ],
            [
                'label' => 'Phòng có thể bán',
                'value' => $availableRooms,
                'sub' => 'Reserved: ' . $reservedRooms . ' · Bảo trì: ' . $maintenanceRooms,
                'icon' => 'bx bx-door-open',
                'tone' => 'success',
            ],
            [
                'label' => 'Check-in hôm nay',
                'value' => $checkinsTodayCount,
                'sub' => 'Khách đến trong ngày ' . $now->format('d/m/Y'),
                'icon' => 'bx bx-log-in-circle',
                'tone' => 'info',
            ],
            [
                'label' => 'Check-out hôm nay',
                'value' => $checkoutsTodayCount,
                'sub' => 'Theo lịch trả phòng hôm nay',
                'icon' => 'bx bx-log-out-circle',
                'tone' => 'warning',
            ],
            [
                'label' => 'Chờ dọn / kiểm tra',
                'value' => $cleaningRooms + $inspectionRooms,
                'sub' => 'Dọn: ' . $cleaningRooms . ' · Kiểm tra: ' . $inspectionRooms,
                'icon' => 'bx bx-brush',
                'tone' => ($cleaningRooms + $inspectionRooms) > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Doanh thu đã thu hôm nay',
                'value' => $this->formatCompactMoney($todayRevenue),
                'sub' => 'Tháng này: ' . $this->formatCompactMoney($monthRevenue),
                'icon' => 'bx bx-wallet',
                'tone' => 'money',
            ],
        ];

        $urgentAlerts = $this->buildUrgentAlerts($now, $todayStart, $todayEnd, $user);
        $systemWarnings = $this->buildSystemWarnings($now, $user);

        $floorMap = Room::query()
            ->with(['category'])
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get()
            ->groupBy('floor_number');

        $housekeepingRooms = Room::query()
            ->with('category')
            ->whereIn('status', ['inspection', 'cleaning', 'maintenance'])
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->limit(12)
            ->get();

        $inspectionStats = [
            'pending' => (int) RoomInspection::query()->where('status', 'pending')->count(),
            'reported' => (int) RoomInspection::query()->where('status', 'reported')->count(),
            'confirmed' => (int) RoomInspection::query()->where('status', 'confirmed')->count(),
            'rejected' => (int) RoomInspection::query()->where('status', 'rejected')->count(),
            'pending_items' => (int) RoomInspectionItem::query()->where('status', 'pending')->count(),
        ];

        $assignmentStats = [
            'room_assigned' => (int) StaffRoomAssignment::query()
                ->whereDate('work_date', $now->toDateString())
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count(),
            'room_completed' => (int) StaffRoomAssignment::query()
                ->whereDate('work_date', $now->toDateString())
                ->where('status', 'completed')
                ->count(),
            'floor_active' => (int) StaffFloorAssignment::query()
                ->whereDate('work_date', $now->toDateString())
                ->where('status', 'active')
                ->count(),
        ];

        $revenueChart = $this->buildRevenueChart($now);
        $roomStatusChart = $this->buildRoomStatusChart($roomsByStatus, $roomStatusLabels, $totalRooms);
        $bookingStatusChart = $this->buildBookingStatusChart($bookingStatusLabels);
        $categoryRevenueChart = $this->buildCategoryRevenueChart($monthStart, $monthEnd);

        $latestLogs = BookingLog::query()
            ->with(['booking.customer', 'user'])
            ->latest()
            ->limit(8)
            ->get();

        $financeStats = [
            'today_revenue' => $todayRevenue,
            'month_revenue' => $monthRevenue,
            'service_revenue_today' => $serviceRevenueToday,
            'receivable_amount' => $receivableAmount,
            'unpaid_active_bookings' => (int) $this->bookingQuery()
                ->whereIn('status', $this->activeBookingStatuses)
                ->where('payment_status', 'unpaid')
                ->count(),
            'partial_active_bookings' => (int) $this->bookingQuery()
                ->whereIn('status', $this->activeBookingStatuses)
                ->where('payment_status', 'partial')
                ->count(),
        ];

        return view('admin.pages.dashboard.dashboard', compact(
            'now',
            'roomStatusLabels',
            'bookingStatusLabels',
            'paymentStatusLabels',
            'summaryCards',
            'urgentAlerts',
            'systemWarnings',
            'floorMap',
            'housekeepingRooms',
            'inspectionStats',
            'assignmentStats',
            'revenueChart',
            'roomStatusChart',
            'bookingStatusChart',
            'categoryRevenueChart',
            'latestLogs',
            'financeStats',
            'checkinsToday',
            'checkoutsToday',
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'reservedRooms',
            'cleaningRooms',
            'inspectionRooms',
            'maintenanceRooms',
            'occupancyRate'
        ));
    }

    private function bookingQuery()
    {
        $query = Booking::query();
        $user = Auth::user();

        if ($user && $user->role === 'receptionist') {
            $query->where(function ($bookingQuery) use ($user) {
                $bookingQuery->where('created_by', $user->id)
                    ->orWhereHas('activeStaffAssignments', function ($assignmentQuery) use ($user) {
                        $assignmentQuery->where('staff_id', $user->id);
                    });
            });
        }

        return $query;
    }

    private function paidRevenueForPeriod(Carbon $from, Carbon $to): float
    {
        return (float) $this->bookingQuery()
            ->whereIn('status', ['checked_out', 'completed'])
            ->where('payment_status', 'paid')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('actual_check_out', [$from, $to])
                    ->orWhere(function ($subQuery) use ($from, $to) {
                        $subQuery->whereNull('actual_check_out')
                            ->whereBetween('updated_at', [$from, $to]);
                    });
            })
            ->sum('estimated_total');
    }

    private function buildUrgentAlerts(Carbon $now, Carbon $todayStart, Carbon $todayEnd, $user): array
    {
        $alerts = [];
        $canOpenBooking = $this->canOpenBooking($user);

        $unassignedToday = $this->bookingQuery()
            ->with(['customer', 'roomCategory'])
            ->whereBetween('check_in_at', [$todayStart, $todayEnd])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDoesntHave('bookingRooms')
            ->orderBy('check_in_at')
            ->limit(4)
            ->get();

        foreach ($unassignedToday as $booking) {
            $alerts[] = [
                'level' => 'danger',
                'icon' => 'bx bx-error-circle',
                'title' => 'Booking hôm nay chưa gán phòng',
                'message' => $booking->booking_code . ' · ' . $this->customerName($booking) . ' · nhận ' . $this->formatDateTime($booking->check_in_at),
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Gán phòng',
            ];
        }

        $lateArrivals = $this->bookingQuery()
            ->with(['customer', 'bookingRooms.room'])
            ->where('status', 'confirmed')
            ->where('check_in_at', '<', $now)
            ->orderBy('check_in_at')
            ->limit(4)
            ->get();

        foreach ($lateArrivals as $booking) {
            $lateMinutes = max(0, Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->diffInMinutes($now));

            $alerts[] = [
                'level' => $lateMinutes >= 240 ? 'danger' : 'warning',
                'icon' => 'bx bx-time-five',
                'title' => 'Khách quá giờ check-in',
                'message' => $booking->booking_code . ' · muộn khoảng ' . $this->formatDuration($lateMinutes) . ' · phòng ' . $this->roomNumbers($booking),
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Xử lý',
            ];
        }

        $lateCheckouts = $this->bookingQuery()
            ->with(['customer', 'bookingRooms.room'])
            ->where('status', 'checked_in')
            ->where('check_out_at', '<', $now)
            ->orderBy('check_out_at')
            ->limit(4)
            ->get();

        foreach ($lateCheckouts as $booking) {
            $lateMinutes = max(0, Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->diffInMinutes($now));

            $alerts[] = [
                'level' => 'danger',
                'icon' => 'bx bx-log-out',
                'title' => 'Khách quá giờ check-out',
                'message' => $booking->booking_code . ' · trễ ' . $this->formatDuration($lateMinutes) . ' · phòng ' . $this->roomNumbers($booking),
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Check-out',
            ];
        }

        $slowRooms = Room::query()
            ->with('category')
            ->whereIn('status', ['cleaning', 'inspection'])
            ->where('updated_at', '<', $now->copy()->subHours(2))
            ->orderBy('updated_at')
            ->limit(5)
            ->get();

        foreach ($slowRooms as $room) {
            $waitingMinutes = max(0, Carbon::parse($room->updated_at, 'Asia/Ho_Chi_Minh')->diffInMinutes($now));
            $statusText = $room->status === 'inspection' ? 'chờ kiểm tra' : 'chờ dọn';

            $alerts[] = [
                'level' => $room->status === 'inspection' ? 'warning' : 'danger',
                'icon' => $room->status === 'inspection' ? 'bx bx-search-alt' : 'bx bx-brush',
                'title' => 'Phòng ' . $statusText . ' quá lâu',
                'message' => 'Phòng ' . $room->room_number . ' · tầng ' . $room->floor_number . ' · chờ ' . $this->formatDuration($waitingMinutes),
                'url' => $room->status === 'cleaning' ? route('admin.housekeeping.index') : route('admin.floor-inspections.index'),
                'action' => 'Xem',
            ];
        }

        $reportedInspections = RoomInspection::query()
            ->with(['booking.customer', 'room'])
            ->where('status', 'reported')
            ->latest()
            ->limit(4)
            ->get();

        foreach ($reportedInspections as $inspection) {
            $alerts[] = [
                'level' => 'warning',
                'icon' => 'bx bx-check-shield',
                'title' => 'Phiếu kiểm tra chờ duyệt',
                'message' => 'Phòng ' . ($inspection->room->room_number ?? 'N/A') . ' · booking ' . ($inspection->booking->booking_code ?? 'N/A') . ' · cần duyệt minibar/hư hại',
                'url' => $this->canApproveInspection($user) ? route('admin.inspection-approvals.show', $inspection) : route('admin.floor-inspections.show', $inspection),
                'action' => $this->canApproveInspection($user) ? 'Duyệt' : 'Xem',
            ];
        }

        return collect($alerts)
            ->sortBy(function ($alert) {
                return ['danger' => 0, 'warning' => 1, 'info' => 2][$alert['level']] ?? 9;
            })
            ->take(12)
            ->values()
            ->all();
    }

    private function buildSystemWarnings(Carbon $now, $user): array
    {
        $warnings = [];
        $canOpenBooking = $this->canOpenBooking($user);

        $paidButUnassigned = $this->bookingQuery()
            ->with('customer')
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereIn('payment_status', ['partial', 'paid'])
            ->whereDoesntHave('bookingRooms')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($paidButUnassigned as $booking) {
            $warnings[] = [
                'level' => 'danger',
                'title' => 'Đã thu tiền nhưng chưa có phòng',
                'message' => $booking->booking_code . ' · ' . $this->customerName($booking) . ' · trạng thái thanh toán: ' . ($booking->payment_status ?? 'N/A'),
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Kiểm tra',
            ];
        }

        $depositMismatch = $this->bookingQuery()
            ->with('customer')
            ->where('deposit_amount', '>', 0)
            ->where('payment_status', 'unpaid')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($depositMismatch as $booking) {
            $warnings[] = [
                'level' => 'danger',
                'title' => 'Cọc khác trạng thái thanh toán',
                'message' => $booking->booking_code . ' · cọc ' . number_format((float) $booking->deposit_amount, 0, ',', '.') . 'đ nhưng vẫn unpaid',
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Sửa',
            ];
        }

        $checkedInRoomMismatch = $this->bookingQuery()
            ->with(['customer', 'bookingRooms.room'])
            ->where('status', 'checked_in')
            ->whereHas('bookingRooms.room', function ($query) {
                $query->where('status', '!=', 'occupied');
            })
            ->latest()
            ->limit(5)
            ->get();

        foreach ($checkedInRoomMismatch as $booking) {
            $warnings[] = [
                'level' => 'danger',
                'title' => 'Booking đang ở nhưng phòng không occupied',
                'message' => $booking->booking_code . ' · phòng ' . $this->roomNumbers($booking) . ' · cần đồng bộ trạng thái phòng',
                'url' => $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Xem',
            ];
        }

        $availableWithActiveBooking = Room::query()
            ->with('category')
            ->where('status', 'available')
            ->whereHas('bookingRooms.booking', function ($query) use ($now) {
                $query->whereIn('status', $this->activeBookingStatuses)
                    ->where('check_in_at', '<=', $now)
                    ->where('check_out_at', '>', $now);
            })
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->limit(5)
            ->get();

        foreach ($availableWithActiveBooking as $room) {
            $warnings[] = [
                'level' => 'danger',
                'title' => 'Phòng trống nhưng có booking active',
                'message' => 'Phòng ' . $room->room_number . ' · tầng ' . $room->floor_number . ' · dữ liệu phòng/booking đang lệch',
                'url' => route('admin.rooms.index'),
                'action' => 'Xem phòng',
            ];
        }

        $occupiedWithoutBooking = Room::query()
            ->with('category')
            ->where('status', 'occupied')
            ->whereDoesntHave('bookingRooms.booking', function ($query) use ($now) {
                $query->where('status', 'checked_in')
                    ->where('check_in_at', '<=', $now)
                    ->where('check_out_at', '>', $now);
            })
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->limit(5)
            ->get();

        foreach ($occupiedWithoutBooking as $room) {
            $warnings[] = [
                'level' => 'warning',
                'title' => 'Phòng occupied nhưng không thấy booking đang ở',
                'message' => 'Phòng ' . $room->room_number . ' · tầng ' . $room->floor_number . ' · kiểm tra lại vòng đời phòng',
                'url' => route('admin.rooms.index'),
                'action' => 'Xem phòng',
            ];
        }

        $pendingServiceAfterCheckout = BookingServiceItem::query()
            ->with(['booking.customer'])
            ->where('billing_status', 'pending')
            ->whereHas('booking', function ($query) {
                $query->whereIn('status', ['checked_out', 'completed']);
            })
            ->latest()
            ->limit(5)
            ->get();

        foreach ($pendingServiceAfterCheckout as $item) {
            $booking = $item->booking;

            $warnings[] = [
                'level' => 'warning',
                'title' => 'Dịch vụ chưa chốt sau check-out',
                'message' => ($booking->booking_code ?? 'N/A') . ' · ' . ($item->name ?? 'Dịch vụ') . ' x' . ($item->quantity ?? 1) . ' vẫn pending',
                'url' => $booking && $canOpenBooking ? route('admin.bookings.show', $booking) : null,
                'action' => 'Kiểm tra',
            ];
        }

        return collect($warnings)
            ->sortBy(function ($warning) {
                return ['danger' => 0, 'warning' => 1, 'info' => 2][$warning['level']] ?? 9;
            })
            ->take(12)
            ->values()
            ->all();
    }

    private function buildRevenueChart(Carbon $now): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $labels[] = $day->format('d/m');
            $values[] = $this->paidRevenueForPeriod($day->copy()->startOfDay(), $day->copy()->endOfDay());
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'max' => max(max($values), 1),
        ];
    }

    private function buildRoomStatusChart($roomsByStatus, array $labels, int $totalRooms): array
    {
        return collect($labels)
            ->map(function ($label, $status) use ($roomsByStatus, $totalRooms) {
                $count = (int) ($roomsByStatus[$status] ?? 0);

                return [
                    'status' => $status,
                    'label' => $label,
                    'count' => $count,
                    'percent' => $totalRooms > 0 ? round(($count / $totalRooms) * 100, 1) : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function buildBookingStatusChart(array $labels): array
    {
        $counts = $this->bookingQuery()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereIn('status', array_keys($labels))
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = max((int) $counts->sum(), 1);

        return collect($labels)
            ->map(function ($label, $status) use ($counts, $total) {
                $count = (int) ($counts[$status] ?? 0);

                return [
                    'status' => $status,
                    'label' => $label,
                    'count' => $count,
                    'percent' => round(($count / $total) * 100, 1),
                ];
            })
            ->values()
            ->all();
    }

    private function buildCategoryRevenueChart(Carbon $from, Carbon $to): array
    {
        $rows = Booking::query()
            ->join('room_categories', 'room_categories.id', '=', 'bookings.room_category_id')
            ->select('room_categories.name', DB::raw('COALESCE(SUM(bookings.estimated_total), 0) as total'))
            ->where('bookings.payment_status', 'paid')
            ->whereIn('bookings.status', ['checked_out', 'completed'])
            ->whereBetween('bookings.updated_at', [$from, $to])
            ->groupBy('room_categories.id', 'room_categories.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        if ($rows->isEmpty()) {
            $rows = RoomCategory::query()
                ->select('name', DB::raw('0 as total'))
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(6)
                ->get();
        }

        $max = max((float) $rows->max('total'), 1);

        return [
            'rows' => $rows,
            'max' => $max,
        ];
    }

    private function canOpenBooking($user): bool
    {
        return $user && in_array($user->role, ['super_admin', 'manager', 'receptionist_lead', 'receptionist'], true);
    }

    private function canApproveInspection($user): bool
    {
        return $user && in_array($user->role, ['super_admin', 'manager'], true);
    }

    private function customerName(Booking $booking): string
    {
        $customer = $booking->customer;

        if (!$customer) {
            return 'Chưa có khách';
        }

        $name = trim(($customer->last_name ?? '') . ' ' . ($customer->first_name ?? ''));

        return $name !== '' ? $name : ($customer->phone ?? 'Khách hàng');
    }

    private function roomNumbers(Booking $booking): string
    {
        $rooms = $booking->bookingRooms
            ->pluck('room.room_number')
            ->filter()
            ->implode(', ');

        return $rooms !== '' ? $rooms : 'chưa gán';
    }

    private function formatDateTime($value): string
    {
        if (!$value) {
            return 'N/A';
        }

        return Carbon::parse($value, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i');
    }

    private function formatDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours <= 0) {
            return $mins . ' phút';
        }

        return $hours . ' giờ' . ($mins > 0 ? ' ' . $mins . ' phút' : '');
    }

    private function formatCompactMoney(float $amount): string
    {
        if ($amount >= 1000000000) {
            return round($amount / 1000000000, 1) . ' tỷ';
        }

        if ($amount >= 1000000) {
            return round($amount / 1000000, 1) . ' triệu';
        }

        return number_format($amount, 0, ',', '.') . 'đ';
    }

    private function roomStatusLabels(): array
    {
        return [
            'available' => 'Trống',
            'reserved' => 'Đã đặt',
            'occupied' => 'Đang ở',
            'inspection' => 'Chờ kiểm tra',
            'cleaning' => 'Cần dọn',
            'maintenance' => 'Bảo trì',
        ];
    }

    private function bookingStatusLabels(): array
    {
        return [
            'pending' => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đang ở',
            'inspection_requested' => 'Chờ kiểm tra',
            'checked_out' => 'Đã check-out',
            'completed' => 'Hoàn tất',
            'canceled' => 'Đã hủy',
        ];
    }

    private function paymentStatusLabels(): array
    {
        return [
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Đã cọc',
            'paid' => 'Đã thanh toán',
            'refunded' => 'Đã hoàn tiền',
        ];
    }
}
