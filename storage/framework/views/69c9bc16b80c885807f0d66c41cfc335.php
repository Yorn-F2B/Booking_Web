<?php $__env->startSection('title', 'Danh sách đặt phòng'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $bookingStatusLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đã nhận phòng',
            'inspection_requested' => 'Chờ kiểm tra',
            'checked_out' => 'Đã trả phòng',
            'completed' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
            'canceled' => 'Đã hủy',
        ];

        $bookingStatusClasses = [
            'pending' => 'booking-status-pending',
            'confirmed' => 'booking-status-confirmed',
            'checked_in' => 'booking-status-checked-in',
            'inspection_requested' => 'booking-status-warning',
            'checked_out' => 'booking-status-done',
            'completed' => 'booking-status-done',
            'cancelled' => 'booking-status-cancelled',
            'canceled' => 'booking-status-cancelled',
        ];

        $paymentStatusLabels = [
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Đã cọc',
            'paid' => 'Đã thanh toán',
        ];

        $paymentStatusClasses = [
            'unpaid' => 'payment-status-unpaid',
            'partial' => 'payment-status-partial',
            'paid' => 'payment-status-paid',
        ];
    ?>

    <style>
        .booking-index-page {
            --booking-border: #e5e7eb;
            --booking-soft: #f8fafc;
            --booking-muted: #64748b;
            --booking-ink: #111827;
            --booking-gold: #d4af37;
        }

        .booking-page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .booking-page-head h2 {
            margin: 0;
            font-size: 25px;
            font-weight: 950;
            color: var(--booking-ink);
            letter-spacing: -0.03em;
        }

        .booking-page-head p {
            margin: 5px 0 0;
            color: var(--booking-muted);
            font-size: 13px;
        }

        .booking-filter-card {
            background: #fff;
            border: 1px solid var(--booking-border);
            border-radius: 18px;
            padding: 14px;
            margin-bottom: 14px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .booking-filter-card .form-label {
            font-size: 12px;
            font-weight: 800;
            color: #475569;
            margin-bottom: 6px;
        }

        .booking-filter-card .form-control,
        .booking-filter-card .form-select {
            border-radius: 12px;
            border-color: var(--booking-border);
            font-size: 13px;
            min-height: 40px;
        }

        .booking-table-card {
            background: #fff;
            border: 1px solid var(--booking-border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 34px rgba(15, 23, 42, 0.045);
        }

        .booking-table-card .table {
            min-width: 1120px;
        }

        .booking-table-card thead th {
            background: #f8fafc;
            border-bottom: 1px solid var(--booking-border);
            color: #475569;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .03em;
            padding: 13px 14px;
            white-space: nowrap;
        }

        .booking-table-card tbody td {
            padding: 15px 14px;
            vertical-align: middle;
            border-bottom-color: #eef2f7;
        }

        .booking-table-card tbody tr:last-child td {
            border-bottom: 0;
        }

        .booking-table-card tbody tr:hover {
            background: #fafafa;
        }

        .booking-row-warning td:first-child {
            box-shadow: inset 4px 0 0 #f59e0b;
        }

        .booking-row-danger td:first-child {
            box-shadow: inset 4px 0 0 #ef4444;
        }

        .booking-code {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 950;
            color: var(--booking-ink);
            font-size: 14px;
            white-space: nowrap;
        }

        .booking-main-text {
            color: var(--booking-ink);
            font-size: 14px;
            font-weight: 850;
            line-height: 1.35;
        }

        .booking-sub-text {
            color: var(--booking-muted);
            font-size: 12px;
            line-height: 1.35;
            margin-top: 3px;
        }

        .booking-muted-line {
            color: var(--booking-muted);
            font-size: 12px;
            line-height: 1.45;
        }

        .booking-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 850;
            line-height: 1;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .booking-status-pending {
            color: #854d0e;
            background: #fef3c7;
            border-color: #fde68a;
        }

        .booking-status-confirmed {
            color: #1d4ed8;
            background: #dbeafe;
            border-color: #bfdbfe;
        }

        .booking-status-checked-in {
            color: #0f766e;
            background: #ccfbf1;
            border-color: #99f6e4;
        }

        .booking-status-warning {
            color: #92400e;
            background: #ffedd5;
            border-color: #fed7aa;
        }

        .booking-status-done {
            color: #166534;
            background: #dcfce7;
            border-color: #bbf7d0;
        }

        .booking-status-cancelled {
            color: #991b1b;
            background: #fee2e2;
            border-color: #fecaca;
        }

        .payment-status-unpaid {
            color: #475569;
            background: #f1f5f9;
            border-color: #e2e8f0;
        }

        .payment-status-partial {
            color: #854d0e;
            background: #fef3c7;
            border-color: #fde68a;
        }

        .payment-status-paid {
            color: #166534;
            background: #dcfce7;
            border-color: #bbf7d0;
        }

        .booking-payment-select {
            min-width: 150px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 850;
            border-width: 1px;
            cursor: pointer;
        }

        .booking-time-main {
            color: var(--booking-ink);
            font-size: 13px;
            font-weight: 850;
            line-height: 1.45;
            white-space: nowrap;
        }

        .booking-type-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 6px;
            padding: 4px 8px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 12px;
            font-weight: 800;
        }

        .booking-attention {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            max-width: 250px;
            border-radius: 12px;
            padding: 8px 10px;
            font-size: 12px;
            font-weight: 800;
            line-height: 1.35;
        }

        .booking-attention-neutral {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .booking-attention-info {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .booking-attention-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .booking-attention-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .booking-action-group {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .booking-action-group .btn {
            border-radius: 999px;
            font-weight: 850;
            padding-left: 12px;
            padding-right: 12px;
        }

        .booking-icon-btn {
            width: 34px;
            height: 34px;
            padding: 0 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-size: 18px;
        }

        .booking-filter-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .booking-empty-state {
            padding: 46px 16px;
            text-align: center;
            color: var(--booking-muted);
        }

        .booking-empty-state i {
            font-size: 34px;
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
        }

        .booking-realtime-panel {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 18px;
            padding: 12px 14px;
            margin-bottom: 14px;
        }

        .booking-realtime-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            padding: 6px 11px;
            font-size: 12px;
            font-weight: 900;
        }

        .booking-realtime-note {
            margin-top: 5px;
            color: #1e40af;
            font-size: 12px;
            font-weight: 700;
        }

        .booking-realtime-list {
            display: grid;
            gap: 8px;
            margin-top: 10px;
            max-height: 320px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .booking-realtime-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: #fff;
            border: 1px solid #dbeafe;
            border-radius: 14px;
            padding: 10px 12px;
        }

        .booking-realtime-title {
            font-size: 14px;
            font-weight: 950;
            color: #111827;
        }

        .booking-realtime-text {
            margin-top: 2px;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
        }

        .booking-realtime-time {
            margin-top: 2px;
            color: #64748b;
            font-size: 12px;
        }

        @media (max-width: 767px) {
            .booking-page-head {
                flex-direction: column;
            }

            .booking-page-head .btn {
                width: 100%;
            }
        }
    </style>

    <div class="admin-wrapper booking-index-page">
        <main class="admin-content">
            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Đặt phòng
            </p>

            <div class="booking-page-head">
                <div>
                    <h2>Danh sách đặt phòng</h2>
                    <p>Trang này chỉ giữ thông tin cần nhìn nhanh. Chi tiết xử lý nằm trong từng booking.</p>
                </div>

                <a href="<?php echo e(route('admin.bookings.create')); ?>" class="btn btn-gold">
                    <i class="bx bx-plus"></i>
                    Tạo booking
                </a>
            </div>

            <?php if(session('success')): ?>
                <div class="alert alert-success">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <strong>Không thể lọc danh sách:</strong>
                    <ul class="mb-0 mt-2">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="booking-realtime-panel">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div>
                        <span data-new-booking-badge data-count="0" class="booking-realtime-badge d-none">
                            0 đơn mới
                        </span>

                        <div class="booking-realtime-note">
                            Đơn mới nhất sẽ hiện ở đây. Bấm “Cập nhật danh sách” để đưa các đơn mới vào bảng chính.
                        </div>
                    </div>

                    <a href="<?php echo e(route('admin.bookings.index')); ?>" data-new-booking-reload
                        class="btn btn-sm btn-outline-primary d-none">
                        <i class="bx bx-refresh"></i>
                        Cập nhật danh sách
                    </a>
                </div>

                <div data-new-booking-list class="booking-realtime-list"></div>
            </div>

            <div class="booking-filter-card">
                <form action="<?php echo e(route('admin.bookings.index')); ?>" method="GET">
                    <div class="row g-2 align-items-end">
                        <div class="col-xl-4 col-lg-4 col-md-6">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" name="keyword" class="form-control" value="<?php echo e(request('keyword')); ?>"
                                placeholder="Mã booking, tên khách, SĐT...">
                        </div>

                        <div class="col-xl-2 col-lg-2 col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả</option>
                                <?php $__currentLoopData = $bookingStatusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($key); ?>" <?php echo e(request('status') == $key ? 'selected' : ''); ?>>
                                        <?php echo e($label); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div class="col-xl-2 col-lg-2 col-md-3">
                            <label class="form-label">Thanh toán</label>
                            <select name="payment_status" class="form-select">
                                <option value="">Tất cả</option>
                                <?php $__currentLoopData = $paymentStatusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($key); ?>" <?php echo e(request('payment_status') == $key ? 'selected' : ''); ?>>
                                        <?php echo e($label); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div class="col-xl-2 col-lg-2 col-md-4">
                            <label class="form-label">Ngày lưu trú</label>
                            <input type="text" name="filter_date" id="filterDate" class="form-control"
                                value="<?php echo e(request('filter_date')); ?>" placeholder="dd/mm/yyyy">
                        </div>

                        <div class="col-xl-1 col-lg-1 col-md-4">
                            <label class="form-label">Từ giờ</label>
                            <input type="text" name="filter_time_from" id="filterTimeFrom" class="form-control"
                                value="<?php echo e(request('filter_time_from')); ?>" placeholder="00:00">
                        </div>

                        <div class="col-xl-1 col-lg-1 col-md-4">
                            <label class="form-label">Đến giờ</label>
                            <input type="text" name="filter_time_to" id="filterTimeTo" class="form-control"
                                value="<?php echo e(request('filter_time_to')); ?>" placeholder="23:59">
                        </div>

                        <div class="col-12 d-flex justify-content-between align-items-center gap-2 flex-wrap mt-3">
                            <div class="booking-muted-line">
                                <?php if(request()->hasAny(['keyword', 'status', 'payment_status', 'filter_date', 'filter_time_from', 'filter_time_to'])): ?>
                                    Đang lọc danh sách. Bấm “Xem tất cả” để reset.
                                <?php else: ?>
                                    Có thể tìm theo mã booking, tên khách hoặc số điện thoại.
                                <?php endif; ?>
                            </div>

                            <div class="booking-filter-actions">
                                <a href="<?php echo e(route('admin.bookings.index')); ?>" class="btn btn-outline-secondary btn-sm px-3">
                                    <i class="bx bx-refresh"></i>
                                    Xem tất cả
                                </a>

                                <button type="submit" class="btn btn-primary btn-sm px-4">
                                    <i class="bx bx-search"></i>
                                    Lọc
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="booking-table-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Booking / khách</th>
                                <th>Phòng</th>
                                <th>Thời gian</th>
                                <th>Trạng thái</th>
                                <th>Thanh toán</th>
                                <th class="text-end">Tổng tiền</th>
                                <th>Cần chú ý</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $bookingStatusClass = $bookingStatusClasses[$booking->status] ?? 'booking-status-cancelled';
                                    $paymentStatusClass = $paymentStatusClasses[$booking->payment_status] ?? 'payment-status-unpaid';

                                    $customerName = trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? ''));
                                    $customerName = $customerName !== '' ? $customerName : 'Chưa có tên';
                                    $customerPhone = $booking->customer->phone ?? 'Chưa có SĐT';

                                    $roomNumbers = $booking->bookingRooms
                                        ? $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ')
                                        : '';

                                    $roomCategoryName = $booking->roomCategory->name ?? 'Không xác định';
                                    $roomQuantityText = max(1, (int) ($booking->room_quantity ?? 1)) . ' phòng';
                                    $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');

                                    $checkInAt = $booking->check_in_at
                                        ? \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')
                                        : null;

                                    $checkOutAt = $booking->check_out_at
                                        ? \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')
                                        : null;

                                    $formatDuration = function ($minutes) {
                                        $minutes = abs((int) round($minutes));
                                        $hours = intdiv($minutes, 60);
                                        $mins = $minutes % 60;

                                        if ($hours > 0 && $mins > 0) {
                                            return $hours . ' giờ ' . $mins . ' phút';
                                        }

                                        if ($hours > 0) {
                                            return $hours . ' giờ';
                                        }

                                        return $mins . ' phút';
                                    };

                                    $stayMainText = 'Chưa có thời gian';
                                    $staySubText = $booking->booking_type == 'hourly' ? 'Theo giờ' : 'Qua đêm';

                                    if ($checkInAt && $checkOutAt) {
                                        $stayMainText = $checkInAt->format('d/m/Y H:i') . ' → ' . $checkOutAt->format('d/m/Y H:i');

                                        if ($booking->booking_type == 'hourly') {
                                            $staySubText = 'Theo giờ · ' . $formatDuration($checkInAt->diffInMinutes($checkOutAt));
                                        } else {
                                            $nightCount = max(1, $checkInAt->copy()->startOfDay()->diffInDays($checkOutAt->copy()->startOfDay()));
                                            $staySubText = 'Qua đêm · ' . $nightCount . ' đêm';
                                        }
                                    }

                                    $attention = [
                                        'class' => 'booking-attention-neutral',
                                        'icon' => 'bx-check-circle',
                                        'text' => 'Ổn',
                                        'level' => 'neutral',
                                    ];

                                    if ($checkInAt && $checkOutAt) {
                                        if (in_array($booking->status, ['pending', 'confirmed'], true)) {
                                            $minutesToCheckIn = $nowVn->diffInMinutes($checkInAt, false);

                                            if ($minutesToCheckIn > 0 && $minutesToCheckIn <= 180) {
                                                $attention = [
                                                    'class' => 'booking-attention-info',
                                                    'icon' => 'bx-time-five',
                                                    'text' => 'Nhận phòng: ' . $formatDuration($minutesToCheckIn),
                                                    'level' => 'info',
                                                ];
                                            } elseif ($minutesToCheckIn < 0) {
                                                $lateMinutes = abs($minutesToCheckIn);

                                                if ($lateMinutes < 120) {
                                                    $attention = [
                                                        'class' => 'booking-attention-warning',
                                                        'icon' => 'bx-error-circle',
                                                        'text' => 'Muộn ' . $formatDuration($lateMinutes),
                                                        'level' => 'warning',
                                                    ];
                                                } elseif ($lateMinutes < 360) {
                                                    $attention = [
                                                        'class' => 'booking-attention-danger',
                                                        'icon' => 'bx-error-circle',
                                                        'text' => 'Muộn ' . $formatDuration($lateMinutes),
                                                        'level' => 'danger',
                                                    ];
                                                } else {
                                                    $attention = [
                                                        'class' => 'booking-attention-danger',
                                                        'icon' => 'bx-phone-call',
                                                        'text' => 'No-show',
                                                        'level' => 'danger',
                                                    ];
                                                }
                                            }
                                        }

                                        if ($booking->status == 'checked_in') {
                                            $minutesToCheckOut = $nowVn->diffInMinutes($checkOutAt, false);

                                            if ($minutesToCheckOut > 0 && $minutesToCheckOut <= 180) {
                                                $attention = [
                                                    'class' => 'booking-attention-warning',
                                                    'icon' => 'bx-log-out-circle',
                                                    'text' => 'Trả phòng: ' . $formatDuration($minutesToCheckOut),
                                                    'level' => 'warning',
                                                ];
                                            } elseif ($minutesToCheckOut < 0) {
                                                $attention = [
                                                    'class' => 'booking-attention-danger',
                                                    'icon' => 'bx-error',
                                                    'text' => 'Quá checkout ' . $formatDuration($minutesToCheckOut),
                                                    'level' => 'danger',
                                                ];
                                            }
                                        }
                                    }

                                    $rowClass = '';
                                    if ($attention['level'] === 'danger') {
                                        $rowClass = 'booking-row-danger';
                                    } elseif ($attention['level'] === 'warning') {
                                        $rowClass = 'booking-row-warning';
                                    }

                                    $canQuickUpdatePayment = in_array($booking->status, ['pending', 'confirmed', 'checked_in', 'inspection_requested', 'checked_out'], true)
                                        && in_array($booking->payment_status, ['unpaid', 'partial'], true);
                                ?>

                                <tr class="<?php echo e($rowClass); ?>">
                                    <td>
                                        <div class="booking-code">
                                            <i class="bx bx-receipt"></i>
                                            <?php echo e($booking->booking_code); ?>

                                        </div>
                                        <div class="booking-sub-text"><?php echo e($customerName); ?> · <?php echo e($customerPhone); ?></div>
                                    </td>

                                    <td>
                                        <div class="booking-main-text"><?php echo e($roomCategoryName); ?></div>
                                        <div class="booking-sub-text">
                                            <?php echo e($roomNumbers ? 'Phòng ' . $roomNumbers : 'Chưa gán phòng'); ?> ·
                                            <?php echo e($roomQuantityText); ?>

                                        </div>
                                    </td>

                                    <td>
                                        <div class="booking-time-main"><?php echo e($stayMainText); ?></div>
                                        <span class="booking-type-pill">
                                            <i class="bx bx-calendar"></i>
                                            <?php echo e($staySubText); ?>

                                        </span>
                                    </td>

                                    <td>
                                        <span class="booking-badge <?php echo e($bookingStatusClass); ?>">
                                            <?php echo e($bookingStatusLabels[$booking->status] ?? $booking->status); ?>

                                        </span>
                                    </td>

                                    <td>
                                        <?php if($canQuickUpdatePayment): ?>
                                            <form action="<?php echo e(route('admin.bookings.update-payment-status', $booking->id)); ?>"
                                                method="POST">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>

                                                <select name="payment_status"
                                                    class="form-select form-select-sm booking-payment-select <?php echo e($paymentStatusClass); ?>"
                                                    onchange="this.form.submit()">
                                                    <?php if($booking->payment_status == 'unpaid'): ?>
                                                        <option value="unpaid" selected>Chưa thanh toán</option>
                                                        <option value="partial">Đã cọc</option>
                                                        <option value="paid">Đã thanh toán</option>
                                                    <?php elseif($booking->payment_status == 'partial'): ?>
                                                        <option value="partial" selected>Đã cọc</option>
                                                        <option value="paid">Đã thanh toán</option>
                                                    <?php endif; ?>
                                                </select>
                                            </form>
                                        <?php else: ?>
                                            <span class="booking-badge <?php echo e($paymentStatusClass); ?>">
                                                <?php echo e($paymentStatusLabels[$booking->payment_status] ?? $booking->payment_status); ?>

                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <div class="booking-main-text">
                                            <?php echo e(number_format((float) $booking->estimated_total, 0, ',', '.')); ?>đ
                                        </div>
                                        <div class="booking-sub-text">
                                            Cọc: <?php echo e(number_format((float) $booking->deposit_amount, 0, ',', '.')); ?>đ
                                        </div>
                                    </td>

                                    <td>
                                        <span class="booking-attention <?php echo e($attention['class']); ?>">
                                            <i class="bx <?php echo e($attention['icon']); ?>"></i>
                                            <?php echo e($attention['text']); ?>

                                        </span>
                                    </td>

                                    <td>
                                        <div class="booking-action-group">
                                            <a href="<?php echo e(route('admin.bookings.show', $booking->id)); ?>"
                                                class="btn btn-sm btn-outline-dark booking-icon-btn" title="Xem chi tiết"
                                                aria-label="Xem chi tiết booking <?php echo e($booking->booking_code); ?>">
                                                <i class="bx bx-show"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="booking-empty-state">
                                            <i class="bx bx-calendar-x"></i>
                                            Chưa có booking nào phù hợp với bộ lọc.
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <?php echo e($bookings->appends(request()->query())->links()); ?>

            </div>
        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof flatpickr === 'undefined') {
                return;
            }

            flatpickr('#filterDate', {
                locale: 'vn',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: true,
            });

            flatpickr('#filterTimeFrom', {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                minuteIncrement: 1,
                locale: 'vn',
                allowInput: true,
            });

            flatpickr('#filterTimeTo', {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                minuteIncrement: 1,
                locale: 'vn',
                allowInput: true,
            });
        });
    </script>
    <?php echo app('Illuminate\Foundation\Vite')('resources/js/admin/bookings-realtime.js'); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/bookings/index.blade.php ENDPATH**/ ?>