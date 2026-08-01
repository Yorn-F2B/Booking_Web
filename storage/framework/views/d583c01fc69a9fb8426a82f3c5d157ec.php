<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $nowSafe = isset($now)
        ? \Carbon\Carbon::parse($now, 'Asia/Ho_Chi_Minh')
        : \Carbon\Carbon::now('Asia/Ho_Chi_Minh');

    $money = fn ($value) => number_format((float) ($value ?? 0), 0, ',', '.') . 'đ';

    $compact = function ($value) {
        $value = (float) ($value ?? 0);

        if ($value >= 1000000000) {
            return rtrim(rtrim(number_format($value / 1000000000, 1, ',', '.'), '0'), ',') . ' tỷ';
        }

        if ($value >= 1000000) {
            return rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',') . ' triệu';
        }

        return number_format($value, 0, ',', '.') . 'đ';
    };

    $timeOnly = fn ($value) => $value
        ? \Carbon\Carbon::parse($value, 'Asia/Ho_Chi_Minh')->format('H:i')
        : '--:--';

    $percentClass = function ($percent, $prefix = 'hm-w') {
        $percent = max(0, min(100, (float) ($percent ?? 0)));
        $bucket = (int) round($percent / 5) * 5;

        if ($percent > 0 && $bucket < 5) {
            $bucket = 5;
        }

        return $prefix . '-' . $bucket;
    };

    $heightClass = function ($value, $max) {
        $value = (float) ($value ?? 0);
        $max = (float) ($max ?? 0);
        $percent = $max > 0 ? ($value / $max) * 100 : 0;
        $bucket = (int) round(max(0, min(100, $percent)) / 10) * 10;

        if ($value > 0 && $bucket < 10) {
            $bucket = 10;
        }

        return 'hm-h-' . $bucket;
    };

    $customerName = function ($booking) {
        $customer = $booking->customer ?? null;

        if (!$customer) {
            return 'Chưa có khách';
        }

        $name = trim(($customer->last_name ?? '') . ' ' . ($customer->first_name ?? ''));

        return $name !== '' ? $name : ($customer->phone ?? 'Khách hàng');
    };

    $roomNumbers = function ($booking) {
        $rooms = collect($booking->bookingRooms ?? [])
            ->pluck('room.room_number')
            ->filter()
            ->implode(', ');

        return $rooms !== '' ? $rooms : 'Chưa gán';
    };

    $bookingStatusLabels = $bookingStatusLabels ?? [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'checked_in' => 'Đang ở',
        'inspection_requested' => 'Chờ kiểm tra',
        'checked_out' => 'Đã trả phòng',
        'completed' => 'Hoàn tất',
        'canceled' => 'Đã hủy',
        'cancelled' => 'Đã hủy',
    ];

    $paymentStatusLabels = $paymentStatusLabels ?? [
        'unpaid' => 'Chưa thanh toán',
        'partial' => 'Đã cọc',
        'paid' => 'Đã thanh toán',
        'refunded' => 'Đã hoàn tiền',
    ];

    $roomStatusLabels = $roomStatusLabels ?? [
        'available' => 'Trống',
        'reserved' => 'Đã đặt',
        'occupied' => 'Đang ở',
        'inspection' => 'Chờ kiểm tra',
        'cleaning' => 'Chờ dọn',
        'maintenance' => 'Bảo trì',
    ];

    $badgeClass = [
        'pending' => 'hm-badge-warning',
        'confirmed' => 'hm-badge-info',
        'checked_in' => 'hm-badge-primary',
        'inspection_requested' => 'hm-badge-orange',
        'checked_out' => 'hm-badge-success',
        'completed' => 'hm-badge-success',
        'canceled' => 'hm-badge-muted',
        'cancelled' => 'hm-badge-muted',

        'unpaid' => 'hm-badge-muted',
        'partial' => 'hm-badge-warning',
        'paid' => 'hm-badge-success',
        'refunded' => 'hm-badge-info',

        'available' => 'hm-badge-success',
        'reserved' => 'hm-badge-info',
        'occupied' => 'hm-badge-danger',
        'inspection' => 'hm-badge-warning',
        'cleaning' => 'hm-badge-orange',
        'maintenance' => 'hm-badge-muted',
    ];

    $roomClass = [
        'available' => 'hm-room-available',
        'reserved' => 'hm-room-reserved',
        'occupied' => 'hm-room-occupied',
        'inspection' => 'hm-room-inspection',
        'cleaning' => 'hm-room-cleaning',
        'maintenance' => 'hm-room-maintenance',
    ];

    $urgentAlerts = collect($urgentAlerts ?? []);
    $systemWarnings = collect($systemWarnings ?? []);
    $checkinsToday = collect($checkinsToday ?? []);
    $checkoutsToday = collect($checkoutsToday ?? []);
    $floorMap = collect($floorMap ?? []);
    $housekeepingRooms = collect($housekeepingRooms ?? []);

    $allRooms = $floorMap->flatMap(fn ($rooms) => collect($rooms));
    $totalRooms = (int) ($totalRooms ?? $allRooms->count());

    $roomCountByStatus = collect($roomStatusLabels)
        ->keys()
        ->mapWithKeys(fn ($status) => [$status => $allRooms->where('status', $status)->count()]);

    $availableRooms = (int) $roomCountByStatus->get('available', 0);
    $reservedRooms = (int) $roomCountByStatus->get('reserved', 0);
    $occupiedRooms = (int) $roomCountByStatus->get('occupied', 0);
    $inspectionRooms = (int) $roomCountByStatus->get('inspection', 0);
    $cleaningRooms = (int) $roomCountByStatus->get('cleaning', 0);
    $maintenanceRooms = (int) $roomCountByStatus->get('maintenance', 0);
    $notReadyRooms = $inspectionRooms + $cleaningRooms + $maintenanceRooms;

    $occupancyPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;
    $sellablePercent = $totalRooms > 0 ? round(($availableRooms / $totalRooms) * 100) : 0;
    $reservedPercent = $totalRooms > 0 ? round(($reservedRooms / $totalRooms) * 100) : 0;
    $notReadyPercent = $totalRooms > 0 ? round(($notReadyRooms / $totalRooms) * 100) : 0;

    $financeStats = $financeStats ?? [];
    $todayRevenue = (float) data_get($financeStats, 'today_revenue', 0);
    $monthRevenue = (float) data_get($financeStats, 'month_revenue', 0);
    $serviceRevenueToday = (float) data_get($financeStats, 'service_revenue_today', 0);
    $receivableAmount = (float) data_get($financeStats, 'receivable_amount', 0);
    $unpaidActiveBookings = (int) data_get($financeStats, 'unpaid_active_bookings', 0);
    $partialActiveBookings = (int) data_get($financeStats, 'partial_active_bookings', 0);

    $inspectionStats = $inspectionStats ?? [];
    $assignmentStats = $assignmentStats ?? [];

    $inspectionPending = (int) data_get($inspectionStats, 'pending', 0);
    $inspectionReported = (int) data_get($inspectionStats, 'reported', 0);
    $inspectionPendingItems = (int) data_get($inspectionStats, 'pending_items', 0);
    $roomAssigned = (int) data_get($assignmentStats, 'room_assigned', 0);
    $roomCompleted = (int) data_get($assignmentStats, 'room_completed', 0);
    $assignmentPercent = $roomAssigned > 0 ? round(($roomCompleted / $roomAssigned) * 100) : 0;

    $revenueChart = $revenueChart ?? ['labels' => [], 'values' => [], 'max' => 0];
    $revenueLabels = collect(data_get($revenueChart, 'labels', []));
    $revenueValues = collect(data_get($revenueChart, 'values', []));
    $revenueMax = (float) data_get($revenueChart, 'max', $revenueValues->max() ?: 0);

    $bookingStatusChart = collect($bookingStatusChart ?? []);
    $categoryRows = collect(data_get($categoryRevenueChart ?? [], 'rows', []));
    $categoryMax = (float) data_get($categoryRevenueChart ?? [], 'max', $categoryRows->max('total') ?: 0);

    $healthIssues = $urgentAlerts->count() + $systemWarnings->count();

    $routeBookingIndex = Route::has('admin.bookings.index') ? route('admin.bookings.index') : '#';
    $routeBookingCreate = Route::has('admin.bookings.create') ? route('admin.bookings.create') : '#';
    $routeRoomsIndex = Route::has('admin.rooms.index') ? route('admin.rooms.index') : '#';

    $todayTimeline = collect();

    foreach ($checkinsToday->take(4) as $booking) {
        $todayTimeline->push([
            'time' => $timeOnly($booking->check_in_at),
            'sort' => $booking->check_in_at ? \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->format('Hi') : '9999',
            'icon' => 'bx bx-log-in-circle',
            'tone' => 'info',
            'title' => 'Check-in · ' . ($booking->booking_code ?? 'Booking'),
            'text' => $customerName($booking) . ' · Phòng ' . $roomNumbers($booking),
            'url' => Route::has('admin.bookings.show') ? route('admin.bookings.show', $booking) : null,
        ]);
    }

    foreach ($checkoutsToday->take(4) as $booking) {
        $todayTimeline->push([
            'time' => $timeOnly($booking->check_out_at),
            'sort' => $booking->check_out_at ? \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->format('Hi') : '9999',
            'icon' => 'bx bx-log-out-circle',
            'tone' => 'warning',
            'title' => 'Check-out · ' . ($booking->booking_code ?? 'Booking'),
            'text' => $customerName($booking) . ' · Phòng ' . $roomNumbers($booking),
            'url' => Route::has('admin.bookings.show') ? route('admin.bookings.show', $booking) : null,
        ]);
    }

    foreach ($housekeepingRooms->take(4) as $room) {
        $todayTimeline->push([
            'time' => 'Trong ca',
            'sort' => '9998',
            'icon' => 'bx bx-brush-alt',
            'tone' => 'orange',
            'title' => 'Xử lý phòng ' . $room->room_number,
            'text' => 'Tầng ' . $room->floor_number . ' · ' . ($roomStatusLabels[$room->status] ?? $room->status),
            'url' => $routeRoomsIndex,
        ]);
    }

    $todayTimeline = $todayTimeline->sortBy('sort')->values()->take(8);
?>

<style>
    .hotel-modern {
        --bg: #f3f6f4;
        --card: #ffffff;
        --text: #111827;
        --muted: #6b7280;
        --line: #e5e7eb;
        --dark: #162033;
        --lime: #dfff63;
        --green: #35d399;
        --green-soft: #dcfce7;
        --blue: #3b82f6;
        --blue-soft: #dbeafe;
        --red: #ef4444;
        --red-soft: #fee2e2;
        --orange: #f59e0b;
        --orange-soft: #fef3c7;
        --purple: #8b5cf6;
        --purple-soft: #ede9fe;

        min-height: 100vh;
        margin-left: 260px;
        padding: 22px;
        background:
            radial-gradient(circle at 90% 0%, rgba(223, 255, 99, .35), transparent 330px),
            linear-gradient(180deg, #f9fbfa 0%, var(--bg) 100%);
        color: var(--text);
    }

    .hm-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
    }

    .hm-title h1 {
        margin: 0;
        font-size: 28px;
        font-weight: 950;
        letter-spacing: -0.055em;
    }

    .hm-title p {
        margin: 6px 0 0;
        color: var(--muted);
        font-size: 13px;
    }

    .hm-actions {
        display: flex;
        gap: 9px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .hm-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 40px;
        padding: 9px 13px;
        border-radius: 14px;
        border: 1px solid var(--line);
        background: #fff;
        color: #1f2937;
        text-decoration: none;
        font-size: 13px;
        font-weight: 900;
        box-shadow: 0 10px 22px rgba(15, 23, 42, .045);
    }

    .hm-btn:hover {
        color: #111827;
        transform: translateY(-1px);
    }

    .hm-btn-main {
        background: var(--lime);
        border-color: #d6f555;
        color: #253000;
    }

    .hm-card {
        background: rgba(255,255,255,.94);
        border: 1px solid rgba(226,232,240,.9);
        border-radius: 24px;
        box-shadow: 0 16px 46px rgba(15,23,42,.055);
        overflow: hidden;
    }

    .hm-card-pad {
        padding: 16px;
    }

    .hm-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 16px 0;
        margin-bottom: 13px;
    }

    .hm-card-title {
        margin: 0;
        font-size: 16px;
        font-weight: 950;
        letter-spacing: -0.035em;
    }

    .hm-card-sub {
        margin: 5px 0 0;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.45;
    }

    .hm-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: #f8fafc;
        color: #475569;
        font-size: 19px;
        line-height: 1;
        flex: 0 0 38px;
    }

    .hm-main-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 16px;
        align-items: start;
    }

    .hm-left,
    .hm-right {
        min-width: 0;
        display: grid;
        gap: 16px;
    }

    .hm-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 16px;
        min-height: 260px;
        padding: 18px;
        color: #fff;
        background:
            radial-gradient(circle at 88% 10%, rgba(223,255,99,.65), transparent 220px),
            linear-gradient(135deg, #121827 0%, #162033 60%, #17372f 100%);
    }

    .hm-eyebrow {
        width: fit-content;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 11px;
        border-radius: 999px;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.14);
        color: rgba(255,255,255,.82);
        font-size: 12px;
        font-weight: 850;
    }

    .hm-hero h2 {
        max-width: 620px;
        margin: 16px 0 0;
        font-size: clamp(36px, 4vw, 60px);
        line-height: .94;
        font-weight: 950;
        letter-spacing: -0.085em;
    }

    .hm-hero p {
        max-width: 610px;
        margin: 12px 0 0;
        color: rgba(255,255,255,.72);
        font-size: 14px;
        line-height: 1.6;
    }

    .hm-hero-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-top: 24px;
    }

    .hm-hero-stat {
        padding: 13px;
        border-radius: 18px;
        background: rgba(255,255,255,.11);
        border: 1px solid rgba(255,255,255,.13);
    }

    .hm-hero-stat span {
        display: block;
        color: rgba(255,255,255,.66);
        font-size: 11px;
        font-weight: 850;
    }

    .hm-hero-stat strong {
        display: block;
        margin-top: 7px;
        color: #fff;
        font-size: 24px;
        line-height: 1;
        font-weight: 950;
    }

    .hm-room-summary {
        padding: 16px;
        border-radius: 22px;
        background: rgba(255,255,255,.96);
        color: var(--text);
    }

    .hm-availability-bar {
        display: flex;
        height: 68px;
        overflow: hidden;
        border-radius: 18px;
        background: #edf2f7;
        border: 1px solid #e2e8f0;
    }

    .hm-bar {
        min-width: 8px;
        height: 100%;
    }

    .hm-bar-available { background: #bdf6d8; }
    .hm-bar-occupied { background: #ffd6dd; }
    .hm-bar-reserved { background: #dbeafe; }
    .hm-bar-not-ready { background: var(--lime); }

    .hm-room-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 11px;
        margin-top: 14px;
    }

    .hm-room-mini {
        display: flex;
        gap: 9px;
        align-items: center;
    }

    .hm-room-mini-line {
        width: 4px;
        height: 36px;
        border-radius: 999px;
        flex: 0 0 4px;
    }

    .hm-room-mini span {
        display: block;
        color: var(--muted);
        font-size: 12px;
        font-weight: 850;
    }

    .hm-room-mini strong {
        display: block;
        margin-top: 2px;
        font-size: 23px;
        line-height: 1;
        font-weight: 950;
    }

    .hm-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 13px;
    }

    .hm-kpi {
        min-height: 124px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
        border-radius: 22px;
    }

    .hm-kpi.green { background: #c9f8df; }
    .hm-kpi.blue { background: #dbeafe; }
    .hm-kpi.red { background: #ffe1e6; }
    .hm-kpi.lime { background: #f0ffad; }

    .hm-kpi span {
        display: block;
        color: rgba(17,24,39,.58);
        font-size: 12px;
        font-weight: 850;
    }

    .hm-kpi strong {
        display: block;
        margin-top: 8px;
        font-size: 29px;
        line-height: 1;
        font-weight: 950;
        letter-spacing: -0.05em;
    }

    .hm-kpi small {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 12px;
        color: rgba(17,24,39,.62);
        font-size: 11px;
        font-weight: 850;
    }

    .hm-kpi-icon {
        width: 41px;
        height: 41px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        background: rgba(255,255,255,.75);
        color: #111827;
        font-size: 20px;
        line-height: 1;
        flex: 0 0 41px;
    }

    .hm-two-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(300px, .9fr);
        gap: 16px;
    }

    .hm-chart {
        height: 240px;
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 10px;
        align-items: end;
        padding: 0 16px 16px;
    }

    .hm-chart-item {
        min-width: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: center;
        gap: 7px;
    }

    .hm-chart-value {
        min-height: 28px;
        color: var(--muted);
        font-size: 11px;
        font-weight: 850;
        text-align: center;
        line-height: 1.2;
    }

    .hm-chart-track {
        width: 100%;
        max-width: 40px;
        height: 150px;
        display: flex;
        align-items: flex-end;
        overflow: hidden;
        border-radius: 999px;
        background: #edf2f7;
    }

    .hm-chart-fill {
        width: 100%;
        min-height: 6px;
        border-radius: 999px 999px 0 0;
        background: linear-gradient(180deg, var(--lime), var(--green));
    }

    .hm-chart-label {
        color: var(--muted);
        font-size: 12px;
        font-weight: 850;
    }

    .hm-progress-list {
        display: grid;
        gap: 13px;
        padding: 0 16px 16px;
    }

    .hm-progress-head {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 7px;
        font-size: 13px;
        font-weight: 900;
    }

    .hm-progress-head span:last-child {
        color: var(--muted);
        font-size: 12px;
        white-space: nowrap;
    }

    .hm-progress-track {
        height: 10px;
        overflow: hidden;
        border-radius: 999px;
        background: #edf2f7;
    }

    .hm-progress-fill {
        display: block;
        height: 100%;
        min-width: 4px;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--green), var(--lime));
    }

    .hm-task-list,
    .hm-timeline,
    .hm-finance-grid,
    .hm-mini-list {
        display: grid;
        gap: 10px;
        padding: 0 16px 16px;
    }

    .hm-task {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr) auto;
        gap: 11px;
        align-items: center;
        padding: 12px;
        border-radius: 17px;
        border: 1px solid var(--line);
        background: #fff;
    }

    .hm-task.danger {
        background: #fff3f5;
        border-color: #fecdd3;
    }

    .hm-task.warning {
        background: #fff9e8;
        border-color: #fde68a;
    }

    .hm-task-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: #f8fafc;
        color: #475569;
        font-size: 18px;
        line-height: 1;
    }

    .hm-task.danger .hm-task-icon { color: #dc2626; }
    .hm-task.warning .hm-task-icon { color: #b45309; }

    .hm-task-title,
    .hm-main-text {
        color: var(--text);
        font-size: 13px;
        font-weight: 950;
        line-height: 1.3;
    }

    .hm-task-text,
    .hm-sub-text {
        margin-top: 3px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.35;
    }

    .hm-task-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 32px;
        padding: 7px 10px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--line);
        color: #1f2937;
        text-decoration: none;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .hm-timeline-item {
        display: grid;
        grid-template-columns: 58px 38px minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 12px;
        border-radius: 18px;
        border: 1px solid var(--line);
        background: #fff;
    }

    .hm-time {
        color: #111827;
        font-size: 12px;
        font-weight: 950;
        white-space: nowrap;
    }

    .hm-timeline-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: #f8fafc;
        color: #475569;
        font-size: 18px;
        line-height: 1;
    }

    .hm-tone-info .hm-timeline-icon { background: var(--blue-soft); color: #1d4ed8; }
    .hm-tone-warning .hm-timeline-icon { background: var(--orange-soft); color: #b45309; }
    .hm-tone-orange .hm-timeline-icon { background: #ffedd5; color: #c2410c; }

    .hm-room-board {
        display: grid;
        gap: 12px;
        padding: 0 16px 16px;
    }

    .hm-floor {
        display: grid;
        grid-template-columns: 70px minmax(0, 1fr);
        gap: 11px;
        align-items: start;
    }

    .hm-floor-label {
        min-height: 62px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 17px;
        background: #111827;
        color: #fff;
        font-size: 13px;
        font-weight: 950;
    }

    .hm-room-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(108px, 1fr));
        gap: 9px;
    }

    .hm-room {
        min-height: 74px;
        display: block;
        padding: 11px;
        border-radius: 17px;
        border: 1px solid var(--line);
        background: #fff;
        color: var(--text);
        text-decoration: none;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .hm-room:hover {
        color: var(--text);
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(15,23,42,.08);
    }

    .hm-room-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 7px;
        font-size: 17px;
        font-weight: 950;
        line-height: 1;
    }

    .hm-room-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        background: #94a3b8;
        flex: 0 0 9px;
    }

    .hm-room-meta {
        display: block;
        margin-top: 5px;
        color: var(--muted);
        font-size: 11px;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .hm-room-available { background: #f0fdf4; border-color: #bbf7d0; }
    .hm-room-reserved { background: #eff6ff; border-color: #bfdbfe; }
    .hm-room-occupied { background: #fff1f2; border-color: #fecdd3; }
    .hm-room-inspection { background: #fffbeb; border-color: #fde68a; }
    .hm-room-cleaning { background: #fff7ed; border-color: #fed7aa; }
    .hm-room-maintenance { background: #f1f5f9; border-color: #cbd5e1; }

    .hm-room-available .hm-room-dot { background: #16a34a; }
    .hm-room-reserved .hm-room-dot { background: #2563eb; }
    .hm-room-occupied .hm-room-dot { background: #dc2626; }
    .hm-room-inspection .hm-room-dot { background: #d97706; }
    .hm-room-cleaning .hm-room-dot { background: #ea580c; }
    .hm-room-maintenance .hm-room-dot { background: #64748b; }

    .hm-finance-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .hm-finance {
        padding: 13px;
        border-radius: 17px;
        border: 1px solid var(--line);
        background: #f9fbfa;
    }

    .hm-finance span {
        display: block;
        color: var(--muted);
        font-size: 12px;
        font-weight: 850;
    }

    .hm-finance strong {
        display: block;
        margin-top: 7px;
        font-size: 18px;
        line-height: 1.1;
        font-weight: 950;
        letter-spacing: -0.035em;
    }

    .hm-mini {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px;
        border-radius: 17px;
        border: 1px solid var(--line);
        background: #fff;
    }

    .hm-badge {
        width: fit-content;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 6px 10px;
        border: 1px solid transparent;
        font-size: 11px;
        line-height: 1;
        font-weight: 950;
        white-space: nowrap;
    }

    .hm-badge-primary { color: #1d4ed8; background: #dbeafe; border-color: #bfdbfe; }
    .hm-badge-info { color: #0369a1; background: #e0f2fe; border-color: #bae6fd; }
    .hm-badge-success { color: #166534; background: #dcfce7; border-color: #bbf7d0; }
    .hm-badge-warning { color: #854d0e; background: #fef3c7; border-color: #fde68a; }
    .hm-badge-orange { color: #9a3412; background: #ffedd5; border-color: #fed7aa; }
    .hm-badge-danger { color: #991b1b; background: #fee2e2; border-color: #fecaca; }
    .hm-badge-muted { color: #475569; background: #f1f5f9; border-color: #e2e8f0; }

    .hm-empty {
        padding: 28px 16px;
        text-align: center;
        color: var(--muted);
        font-size: 13px;
        font-weight: 750;
    }

    .hm-empty i {
        display: block;
        margin-bottom: 8px;
        color: #94a3b8;
        font-size: 32px;
        line-height: 1;
    }

    .hm-w-0 { width: 0%; }
    .hm-w-5 { width: 5%; }
    .hm-w-10 { width: 10%; }
    .hm-w-15 { width: 15%; }
    .hm-w-20 { width: 20%; }
    .hm-w-25 { width: 25%; }
    .hm-w-30 { width: 30%; }
    .hm-w-35 { width: 35%; }
    .hm-w-40 { width: 40%; }
    .hm-w-45 { width: 45%; }
    .hm-w-50 { width: 50%; }
    .hm-w-55 { width: 55%; }
    .hm-w-60 { width: 60%; }
    .hm-w-65 { width: 65%; }
    .hm-w-70 { width: 70%; }
    .hm-w-75 { width: 75%; }
    .hm-w-80 { width: 80%; }
    .hm-w-85 { width: 85%; }
    .hm-w-90 { width: 90%; }
    .hm-w-95 { width: 95%; }
    .hm-w-100 { width: 100%; }

    .hm-h-0 { height: 0%; }
    .hm-h-10 { height: 10%; }
    .hm-h-20 { height: 20%; }
    .hm-h-30 { height: 30%; }
    .hm-h-40 { height: 40%; }
    .hm-h-50 { height: 50%; }
    .hm-h-60 { height: 60%; }
    .hm-h-70 { height: 70%; }
    .hm-h-80 { height: 80%; }
    .hm-h-90 { height: 90%; }
    .hm-h-100 { height: 100%; }

    @media (max-width: 1399.98px) {
        .hm-main-grid {
            grid-template-columns: 1fr;
        }

        .hm-right {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 1199.98px) {
        .hm-hero,
        .hm-two-grid {
            grid-template-columns: 1fr;
        }

        .hm-kpi-grid,
        .hm-hero-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991.98px) {
        .hotel-modern {
            margin-left: 0;
            padding: 72px 14px 20px;
        }
    }

    @media (max-width: 767.98px) {
        .hm-top {
            flex-direction: column;
        }

        .hm-actions,
        .hm-btn {
            width: 100%;
        }

        .hm-kpi-grid,
        .hm-hero-stats,
        .hm-room-summary-grid,
        .hm-right,
        .hm-finance-grid {
            grid-template-columns: 1fr;
        }

        .hm-floor,
        .hm-timeline-item {
            grid-template-columns: 1fr;
        }

        .hm-task {
            grid-template-columns: 38px minmax(0, 1fr);
        }

        .hm-task-action {
            grid-column: 2;
            width: fit-content;
        }
    }
</style>

<div class="admin-wrapper hotel-modern">
    <div class="hm-top">
        <div class="hm-title">
            <h1>Hotel Management Dashboard</h1>
            <p>Tổng quan khách sạn: phòng, booking, buồng phòng, doanh thu và việc cần xử lý hôm nay.</p>
        </div>

        <div class="hm-actions">
            <a href="<?php echo e($routeBookingCreate); ?>" class="hm-btn hm-btn-main">
                <i class="bx bx-plus"></i>
                Tạo booking
            </a>

            <a href="<?php echo e($routeBookingIndex); ?>" class="hm-btn">
                <i class="bx bx-list-ul"></i>
                Danh sách booking
            </a>

            <a href="<?php echo e($routeRoomsIndex); ?>" class="hm-btn">
                <i class="bx bx-bed"></i>
                Quản lý phòng
            </a>
        </div>
    </div>

    <div class="hm-main-grid">
        <main class="hm-left">
            <section class="hm-card hm-hero">
                <div>
                    <div class="hm-eyebrow">
                        <i class="bx bx-buildings"></i>
                        <?php echo e($nowSafe->format('d/m/Y H:i')); ?> · Bảng điều hành khách sạn
                    </div>

                    <h2><?php echo e($occupancyPercent); ?>% công suất hôm nay</h2>

                    <p>
                        Tập trung vào các số liệu cần nhìn ngay: phòng có thể bán, phòng đang ở,
                        phòng chưa sẵn sàng, booking đến/trả và việc cần xử lý trong ca.
                    </p>

                    <div class="hm-hero-stats">
                        <div class="hm-hero-stat">
                            <span>Phòng đang ở</span>
                            <strong><?php echo e($occupiedRooms); ?>/<?php echo e($totalRooms); ?></strong>
                        </div>

                        <div class="hm-hero-stat">
                            <span>Có thể bán</span>
                            <strong><?php echo e($availableRooms); ?></strong>
                        </div>

                        <div class="hm-hero-stat">
                            <span>Chưa sẵn sàng</span>
                            <strong><?php echo e($notReadyRooms); ?></strong>
                        </div>

                        <div class="hm-hero-stat">
                            <span>Việc cần xem</span>
                            <strong><?php echo e($healthIssues); ?></strong>
                        </div>
                    </div>
                </div>

                <div class="hm-room-summary">
                    <div class="hm-card-head p-0 mb-3">
                        <div>
                            <h3 class="hm-card-title">Room Availability</h3>
                            <p class="hm-card-sub">Tổng <?php echo e($totalRooms); ?> phòng theo trạng thái thực tế.</p>
                        </div>
                        <span class="hm-icon"><i class="bx bx-door-open"></i></span>
                    </div>

                    <div class="hm-availability-bar">
                        <div class="hm-bar hm-bar-available <?php echo e($percentClass($sellablePercent)); ?>"></div>
                        <div class="hm-bar hm-bar-occupied <?php echo e($percentClass($occupancyPercent)); ?>"></div>
                        <div class="hm-bar hm-bar-reserved <?php echo e($percentClass($reservedPercent)); ?>"></div>
                        <div class="hm-bar hm-bar-not-ready <?php echo e($percentClass($notReadyPercent)); ?>"></div>
                    </div>

                    <div class="hm-room-summary-grid">
                        <div class="hm-room-mini">
                            <span class="hm-room-mini-line hm-bar-available"></span>
                            <div>
                                <span>Có thể bán</span>
                                <strong><?php echo e($availableRooms); ?></strong>
                            </div>
                        </div>

                        <div class="hm-room-mini">
                            <span class="hm-room-mini-line hm-bar-occupied"></span>
                            <div>
                                <span>Đang ở</span>
                                <strong><?php echo e($occupiedRooms); ?></strong>
                            </div>
                        </div>

                        <div class="hm-room-mini">
                            <span class="hm-room-mini-line hm-bar-reserved"></span>
                            <div>
                                <span>Đã đặt</span>
                                <strong><?php echo e($reservedRooms); ?></strong>
                            </div>
                        </div>

                        <div class="hm-room-mini">
                            <span class="hm-room-mini-line hm-bar-not-ready"></span>
                            <div>
                                <span>Chưa sẵn sàng</span>
                                <strong><?php echo e($notReadyRooms); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="hm-kpi-grid">
                <article class="hm-kpi green">
                    <div>
                        <span>Check-in hôm nay</span>
                        <strong><?php echo e($checkinsToday->count()); ?></strong>
                        <small><i class="bx bx-log-in-circle"></i> Khách đến trong ngày</small>
                    </div>
                    <div class="hm-kpi-icon"><i class="bx bx-calendar-plus"></i></div>
                </article>

                <article class="hm-kpi blue">
                    <div>
                        <span>Check-out hôm nay</span>
                        <strong><?php echo e($checkoutsToday->count()); ?></strong>
                        <small><i class="bx bx-log-out-circle"></i> Theo lịch trả phòng</small>
                    </div>
                    <div class="hm-kpi-icon"><i class="bx bx-calendar-check"></i></div>
                </article>

                <article class="hm-kpi red">
                    <div>
                        <span>Cần xử lý</span>
                        <strong><?php echo e($healthIssues); ?></strong>
                        <small><i class="bx bx-error-circle"></i> Cảnh báo & lệch dữ liệu</small>
                    </div>
                    <div class="hm-kpi-icon"><i class="bx bx-bell"></i></div>
                </article>

                <article class="hm-kpi lime">
                    <div>
                        <span>Doanh thu hôm nay</span>
                        <strong><?php echo e($compact($todayRevenue)); ?></strong>
                        <small><i class="bx bx-wallet-alt"></i> Tháng này <?php echo e($compact($monthRevenue)); ?></small>
                    </div>
                    <div class="hm-kpi-icon"><i class="bx bx-line-chart"></i></div>
                </article>
            </section>

            <section class="hm-two-grid">
                <div class="hm-card">
                    <div class="hm-card-head">
                        <div>
                            <h3 class="hm-card-title">Revenue Overview</h3>
                            <p class="hm-card-sub">Doanh thu đã thu trong 7 ngày gần nhất.</p>
                        </div>
                        <span class="hm-badge hm-badge-success">Max <?php echo e($compact($revenueMax)); ?></span>
                    </div>

                    <div class="hm-chart">
                        <?php $__empty_1 = true; $__currentLoopData = $revenueValues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="hm-chart-item">
                                <div class="hm-chart-value"><?php echo e($compact($value)); ?></div>
                                <div class="hm-chart-track">
                                    <div class="hm-chart-fill <?php echo e($heightClass($value, $revenueMax)); ?>"></div>
                                </div>
                                <div class="hm-chart-label"><?php echo e($revenueLabels[$index] ?? ''); ?></div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="hm-empty">
                                <i class="bx bx-bar-chart-alt-2"></i>
                                Chưa có dữ liệu doanh thu.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="hm-card">
                    <div class="hm-card-head">
                        <div>
                            <h3 class="hm-card-title">Booking Flow</h3>
                        </div>
                        <span class="hm-icon"><i class="bx bx-git-branch"></i></span>
                    </div>

                    <div class="hm-progress-list">
                        <?php $__empty_1 = true; $__currentLoopData = $bookingStatusChart; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div>
                                <div class="hm-progress-head">
                                    <span><?php echo e($row['label']); ?></span>
                                    <span><?php echo e($row['count']); ?> booking · <?php echo e($row['percent']); ?>%</span>
                                </div>
                                <div class="hm-progress-track">
                                    <span class="hm-progress-fill <?php echo e($percentClass($row['percent'])); ?>"></span>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="hm-empty">
                                <i class="bx bx-receipt"></i>
                                Chưa có dữ liệu booking.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="hm-card">
                <div class="hm-card-head">
                    <div>
                        <h3 class="hm-card-title">Lịch vận hành hôm nay</h3>
                        <p class="hm-card-sub">Thay cho nhật ký thao tác: gom check-in, check-out và phòng cần xử lý trong một timeline.</p>
                    </div>
                    <span class="hm-icon"><i class="bx bx-time-five"></i></span>
                </div>

                <div class="hm-timeline">
                    <?php $__empty_1 = true; $__currentLoopData = $todayTimeline; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="hm-timeline-item hm-tone-<?php echo e($item['tone']); ?>">
                            <div class="hm-time"><?php echo e($item['time']); ?></div>

                            <div class="hm-timeline-icon">
                                <i class="<?php echo e($item['icon']); ?>"></i>
                            </div>

                            <div>
                                <div class="hm-main-text"><?php echo e($item['title']); ?></div>
                                <div class="hm-sub-text"><?php echo e($item['text']); ?></div>
                            </div>

                            <?php if(!empty($item['url'])): ?>
                                <a href="<?php echo e($item['url']); ?>" class="hm-task-action">Xem</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="hm-empty">
                            <i class="bx bx-calendar-check"></i>
                            Hôm nay chưa có lịch vận hành cần chú ý.
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="hm-card">
                <div class="hm-card-head">
                    <div>
                        <h3 class="hm-card-title">Room Status Board</h3>
                        <p class="hm-card-sub">Sơ đồ phòng theo tầng, nhìn nhanh phòng trống, đang ở, chờ dọn hoặc bảo trì.</p>
                    </div>
                    <a href="<?php echo e($routeRoomsIndex); ?>" class="hm-btn">
                        <i class="bx bx-bed"></i>
                        Quản lý phòng
                    </a>
                </div>

                <div class="hm-room-board">
                    <?php $__empty_1 = true; $__currentLoopData = $floorMap; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $floorNumber => $rooms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="hm-floor">
                            <div class="hm-floor-label">Tầng <?php echo e($floorNumber ?: 'N/A'); ?></div>

                            <div class="hm-room-grid">
                                <?php $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <a href="<?php echo e(Route::has('admin.rooms.index') ? route('admin.rooms.index', ['floor' => $room->floor_number]) : '#'); ?>"
                                        class="hm-room <?php echo e($roomClass[$room->status] ?? ''); ?>"
                                        title="Phòng <?php echo e($room->room_number); ?> - <?php echo e($roomStatusLabels[$room->status] ?? $room->status); ?>">
                                        <span class="hm-room-top">
                                            <?php echo e($room->room_number); ?>

                                            <span class="hm-room-dot"></span>
                                        </span>
                                        <span class="hm-room-meta"><?php echo e($roomStatusLabels[$room->status] ?? $room->status); ?></span>
                                        <span class="hm-room-meta"><?php echo e($room->category->name ?? 'Chưa có hạng'); ?></span>
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="hm-empty">
                            <i class="bx bx-bed"></i>
                            Chưa có dữ liệu phòng.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <aside class="hm-right">
            <section class="hm-card">
                <div class="hm-card-head">
                    <div>
                        <h3 class="hm-card-title">Cần xử lý</h3>
                        <p class="hm-card-sub">Việc ưu tiên trong ca hiện tại.</p>
                    </div>
                    <span class="hm-icon"><i class="bx bx-bell"></i></span>
                </div>

                <div class="hm-task-list">
                    <?php $__empty_1 = true; $__currentLoopData = $urgentAlerts->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="hm-task <?php echo e(($alert['level'] ?? 'warning') === 'danger' ? 'danger' : 'warning'); ?>">
                            <div class="hm-task-icon">
                                <i class="<?php echo e($alert['icon'] ?? 'bx bx-bell'); ?>"></i>
                            </div>

                            <div>
                                <div class="hm-task-title"><?php echo e($alert['title'] ?? 'Cần xử lý'); ?></div>
                                <div class="hm-task-text"><?php echo e($alert['message'] ?? ''); ?></div>
                            </div>

                            <?php if(!empty($alert['url'])): ?>
                                <a href="<?php echo e($alert['url']); ?>" class="hm-task-action"><?php echo e($alert['action'] ?? 'Xem'); ?></a>
                            <?php else: ?>
                                <span class="hm-task-action"><?php echo e($alert['action'] ?? 'Xem'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="hm-empty">
                            <i class="bx bx-check-circle"></i>
                            Chưa có việc gấp.
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="hm-card">
                <div class="hm-card-head">
                    <div>
                        <h3 class="hm-card-title">Bất thường dữ liệu</h3>
                        <p class="hm-card-sub">Tránh sai phòng, sai tiền hoặc sai trạng thái.</p>
                    </div>
                    <span class="hm-icon"><i class="bx bx-shield-x"></i></span>
                </div>

                <div class="hm-task-list">
                    <?php $__empty_1 = true; $__currentLoopData = $systemWarnings->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warning): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="hm-task warning">
                            <div class="hm-task-icon">
                                <i class="bx bx-bug"></i>
                            </div>

                            <div>
                                <div class="hm-task-title"><?php echo e($warning['title'] ?? 'Bất thường'); ?></div>
                                <div class="hm-task-text"><?php echo e($warning['message'] ?? ''); ?></div>
                            </div>

                            <?php if(!empty($warning['url'])): ?>
                                <a href="<?php echo e($warning['url']); ?>" class="hm-task-action"><?php echo e($warning['action'] ?? 'Xem'); ?></a>
                            <?php else: ?>
                                <span class="hm-task-action"><?php echo e($warning['action'] ?? 'Xem'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="hm-empty">
                            <i class="bx bx-check-shield"></i>
                            Chưa phát hiện lệch dữ liệu.
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="hm-card">
                <div class="hm-card-head">
                    <div>
                        <h3 class="hm-card-title">Financials</h3>
                        <p class="hm-card-sub">Tiền đã thu và khoản cần theo dõi.</p>
                    </div>
                    <span class="hm-icon"><i class="bx bx-wallet-alt"></i></span>
                </div>

                <div class="hm-finance-grid">
                    <div class="hm-finance">
                        <span>Hôm nay</span>
                        <strong><?php echo e($money($todayRevenue)); ?></strong>
                    </div>

                    <div class="hm-finance">
                        <span>Tháng này</span>
                        <strong><?php echo e($money($monthRevenue)); ?></strong>
                    </div>

                    <div class="hm-finance">
                        <span>Dịch vụ</span>
                        <strong><?php echo e($money($serviceRevenueToday)); ?></strong>
                    </div>

                    <div class="hm-finance">
                        <span>Cần thu</span>
                        <strong><?php echo e($money($receivableAmount)); ?></strong>
                    </div>

                    <div class="hm-finance">
                        <span>Chưa TT</span>
                        <strong><?php echo e($unpaidActiveBookings); ?></strong>
                    </div>

                    <div class="hm-finance">
                        <span>Đã cọc</span>
                        <strong><?php echo e($partialActiveBookings); ?></strong>
                    </div>
                </div>
            </section>

            <section class="hm-card">
                <div class="hm-card-head">
                    <div>
                        <h3 class="hm-card-title">Buồng phòng</h3>
                        <p class="hm-card-sub">Phòng đang chờ xử lý và tiến độ phân công.</p>
                    </div>
                    <span class="hm-icon"><i class="bx bx-brush-alt"></i></span>
                </div>

                <div class="hm-finance-grid">
                    <div class="hm-finance">
                        <span>Chờ kiểm tra</span>
                        <strong><?php echo e($inspectionPending); ?></strong>
                    </div>

                    <div class="hm-finance">
                        <span>Chờ duyệt</span>
                        <strong><?php echo e($inspectionReported); ?></strong>
                    </div>

                    <div class="hm-finance">
                        <span>Item chờ duyệt</span>
                        <strong><?php echo e($inspectionPendingItems); ?></strong>
                    </div>

                    <div class="hm-finance">
                        <span>Phân công</span>
                        <strong><?php echo e($roomCompleted); ?>/<?php echo e($roomAssigned); ?></strong>
                    </div>
                </div>

                <div class="hm-progress-list">
                    <div>
                        <div class="hm-progress-head">
                            <span>Tiến độ phân công</span>
                            <span><?php echo e($assignmentPercent); ?>%</span>
                        </div>
                        <div class="hm-progress-track">
                            <span class="hm-progress-fill <?php echo e($percentClass($assignmentPercent)); ?>"></span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="hm-card">
                <div class="hm-card-head">
                    <div>
                        <h3 class="hm-card-title">Doanh thu hạng phòng</h3>
                        <p class="hm-card-sub">Top hạng phòng tháng này.</p>
                    </div>
                    <span class="hm-icon"><i class="bx bx-category-alt"></i></span>
                </div>

                <div class="hm-progress-list">
                    <?php $__empty_1 = true; $__currentLoopData = $categoryRows->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $percent = $categoryMax > 0
                                ? round(((float) $row->total / $categoryMax) * 100, 1)
                                : 0;
                        ?>

                        <div>
                            <div class="hm-progress-head">
                                <span><?php echo e($row->name); ?></span>
                                <span><?php echo e($compact($row->total)); ?></span>
                            </div>
                            <div class="hm-progress-track">
                                <span class="hm-progress-fill <?php echo e($percentClass($percent)); ?>"></span>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="hm-empty">
                            <i class="bx bx-bar-chart-square"></i>
                            Chưa có dữ liệu.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </aside>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\dashboard\dashboard.blade.php ENDPATH**/ ?>