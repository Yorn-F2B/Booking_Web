<?php $__env->startSection('title', 'Danh sách đặt phòng'); ?>

<?php $__env->startSection('content'); ?>
<div data-bookings-index-fragment>
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

        $bookingFilterKeys = ['keyword', 'status', 'payment_status', 'date_from', 'date_to', 'time_from', 'time_to', 'filter_date'];
        $hasActiveBookingFilters = collect($bookingFilterKeys)->contains(fn ($key) => request()->filled($key));
        $showBookingHistory = request()->boolean('show_history');
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

        .booking-filter-card > summary {
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 900;
            color: #334155;
        }

        .booking-filter-card > summary::-webkit-details-marker { display: none; }
        .booking-filter-card > summary .filter-chevron { transition: transform .18s ease; }
        .booking-filter-card[open] > summary .filter-chevron { transform: rotate(180deg); }
        .booking-filter-card[open] > summary { margin-bottom: 14px; }
        .booking-filter-summary-note { color: var(--booking-muted); font-size: 12px; font-weight: 700; }

        .booking-page-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; }

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

        .booking-row-late td:first-child {
            box-shadow: inset 4px 0 0 #7e22ce;
        }

        .booking-priority-label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 6px;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 850;
            background: #eef2ff;
            color: #3730a3;
        }

        .booking-priority-label.is-urgent { background: #fee2e2; color: #991b1b; }
        .booking-priority-label.is-active { background: #dcfce7; color: #166534; }
        .booking-priority-label.is-done { background: #f1f5f9; color: #64748b; }
        .booking-priority-label.is-late { background: #f3e8ff; color: #7e22ce; }

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

        .booking-status-late {
            color: #6b21a8;
            background: #f3e8ff;
            border-color: #d8b4fe;
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

        .booking-attention-late {
            background: #faf5ff;
            color: #7e22ce;
            border: 1px solid #d8b4fe;
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
                    <p>Mặc định sắp theo thay đổi mới nhất để booking vừa tạo, vừa thanh toán hoặc vừa tự hủy luôn hiện ngay.</p>
                </div>

                <div class="booking-page-actions">
                    <?php if($showBookingHistory): ?>
                        <a href="<?php echo e(route('admin.bookings.index', request()->except(['show_history', 'page']))); ?>" class="btn btn-outline-secondary">
                            <i class="bx bx-hide"></i>
                            Ẩn đơn đã xong
                        </a>
                    <?php elseif(!$hasActiveBookingFilters): ?>
                        <a href="<?php echo e(route('admin.bookings.index', array_merge(request()->except('page'), ['show_history' => 1]))); ?>" class="btn btn-outline-secondary">
                            <i class="bx bx-show"></i>
                            Hiển thị toàn bộ đơn
                        </a>
                    <?php endif; ?>

                    <a href="<?php echo e(route('admin.bookings.create')); ?>" class="btn btn-gold">
                        <i class="bx bx-plus"></i>
                        Tạo booking
                    </a>
                </div>
            </div>

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

            <details class="booking-filter-card" <?php if($hasActiveBookingFilters): ?> open <?php endif; ?>>
                <summary>
                    <span class="d-flex align-items-center gap-2">
                        <i class="bx bx-filter-alt"></i>
                        Bộ lọc
                        <?php if($hasActiveBookingFilters): ?>
                            <span class="badge rounded-pill text-bg-primary">Đang áp dụng</span>
                        <?php endif; ?>
                    </span>
                    <span class="d-flex align-items-center gap-2 booking-filter-summary-note">
                        <?php echo e($hasActiveBookingFilters ? 'Bấm để thu gọn' : 'Mặc định ẩn để danh sách gọn hơn'); ?>

                        <i class="bx bx-chevron-down filter-chevron"></i>
                    </span>
                </summary>
                <form action="<?php echo e(route('admin.bookings.index')); ?>" method="GET">
                    <?php if($showBookingHistory): ?>
                        <input type="hidden" name="show_history" value="1">
                    <?php endif; ?>
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

                        <div class="col-xl-2 col-lg-2 col-md-3">
                            <label class="form-label">Sắp xếp</label>
                            <select name="sort" class="form-select">
                                <option value="updated" <?php if(request('sort', 'updated') === 'updated'): echo 'selected'; endif; ?>>Mới cập nhật</option>
                                <option value="created" <?php if(request('sort') === 'created'): echo 'selected'; endif; ?>>Mới tạo</option>
                                <option value="operations" <?php if(request('sort') === 'operations'): echo 'selected'; endif; ?>>Ưu tiên vận hành</option>
                            </select>
                        </div>

                        
                        <div class="col-xl-2 col-lg-5 col-md-6">
                            <div class="d-flex align-items-end gap-1">
                                <div class="flex-fill">
                                    <label class="form-label">Ngày đến</label>
                                    <input type="text" name="date_from" id="filterDateFrom" class="form-control"
                                        value="<?php echo e(request('date_from', request('filter_date'))); ?>"
                                        placeholder="dd/mm/yyyy" autocomplete="off">
                                </div>
                                <div class="text-muted pb-2" style="flex-shrink:0">→</div>
                                <div class="flex-fill">
                                    <label class="form-label">Ngày đi</label>
                                    <input type="text" name="date_to" id="filterDateTo" class="form-control"
                                        value="<?php echo e(request('date_to', request('date_from', request('filter_date')))); ?>"
                                        placeholder="dd/mm/yyyy" autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-lg-2 col-md-4">
                            <div class="d-flex align-items-end gap-1">
                                <div class="flex-fill">
                                    <label class="form-label">Giờ đến</label>
                                    <input type="text" name="time_from" id="filterTimeFrom" class="form-control"
                                        value="<?php echo e(request('time_from', request('filter_time_from'))); ?>"
                                        placeholder="14:00" autocomplete="off">
                                </div>
                                <div class="text-muted pb-2" style="flex-shrink:0">→</div>
                                <div class="flex-fill">
                                    <label class="form-label">Giờ đi</label>
                                    <input type="text" name="time_to" id="filterTimeTo" class="form-control"
                                        value="<?php echo e(request('time_to', request('filter_time_to'))); ?>"
                                        placeholder="12:00" autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="col-12 d-flex justify-content-between align-items-center gap-2 flex-wrap mt-3">
                            <div class="booking-muted-line">
                                <?php if($hasActiveBookingFilters): ?>
                                    Đang lọc danh sách. Bấm "Xóa bộ lọc" để reset.
                                <?php else: ?>
                                    Có thể tìm theo mã booking, tên khách hoặc số điện thoại.
                                <?php endif; ?>
                            </div>

                            <div class="booking-filter-actions">
                                <a href="<?php echo e(route('admin.bookings.index')); ?>" class="btn btn-outline-secondary btn-sm px-3">
                                    <i class="bx bx-refresh"></i>
                                    Xóa bộ lọc
                                </a>

                                <button type="submit" class="btn btn-primary btn-sm px-4">
                                    <i class="bx bx-search"></i>
                                    Lọc
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </details>

            <?php if(!$showBookingHistory && !$hasActiveBookingFilters): ?>
                <div class="booking-muted-line mb-2">
                    Đang ẩn các đơn đã hoàn tất/đã hủy. Đơn hủy còn chờ hoàn tiền vẫn được giữ lại để xử lý.
                </div>
            <?php elseif($hasActiveBookingFilters && !$showBookingHistory): ?>
                <div class="booking-muted-line mb-2">
                    Đang tìm trong toàn bộ lịch sử, bao gồm cả đơn đã hoàn tất/đã hủy.
                </div>
            <?php endif; ?>

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

                                    $customerName = $booking->booked_customer_name !== ''
                                        ? $booking->booked_customer_name
                                        : 'Chưa có tên';
                                    $customerPhone = $booking->booked_customer_phone ?? 'Chưa có SĐT';

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

                                    $isLateCheckout = $booking->isLateCheckout($nowVn);
                                    $lateCheckoutMinutes = $booking->lateCheckoutMinutes($nowVn);
                                    $lateCheckoutText = $formatDuration($lateCheckoutMinutes);

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
                                        'text' => 'Bình thường',
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
                                                        'text' => 'Không đến',
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

                                    if ($isLateCheckout) {
                                        $attention = [
                                            'class' => 'booking-attention-late',
                                            'icon' => 'bx-time-five',
                                            'text' => 'Trả muộn ' . $lateCheckoutText,
                                            'level' => 'late',
                                        ];
                                    }

                                    $priority = [
                                        'class' => '',
                                        'icon' => 'bx-list-check',
                                        'text' => 'Theo dõi',
                                    ];

                                    if ($isLateCheckout) {
                                        $priority = ['class' => 'is-late', 'icon' => 'bx-time-five', 'text' => 'Trả muộn · cần xử lý'];
                                    } elseif ($booking->status === 'inspection_requested' || $booking->pendingCancellationRequest || $booking->pendingRoomIssueRequest) {
                                        $priority = ['class' => 'is-urgent', 'icon' => 'bx-alarm-exclamation', 'text' => 'Cần xử lý ngay'];
                                    } elseif ($booking->status === 'checked_in') {
                                        $priority = ['class' => 'is-active', 'icon' => 'bx-hotel', 'text' => 'Khách đang lưu trú'];
                                    } elseif (in_array($booking->status, ['pending', 'confirmed'], true)) {
                                        $priority = ['class' => '', 'icon' => 'bx-time-five', 'text' => 'Chưa nhận phòng'];
                                    } elseif (in_array($booking->status, ['cancelled', 'canceled'], true) && !empty($booking->auto_cancelled_by_payment_expiry)) {
                                        $priority = [
                                            'class' => 'is-urgent',
                                            'icon' => 'bx-timer',
                                            'text' => 'Tự hủy: hết hạn thanh toán',
                                        ];
                                    } elseif (in_array($booking->status, ['checked_out', 'completed', 'cancelled', 'canceled'], true)) {
                                        $priority = ['class' => 'is-done', 'icon' => 'bx-check-double', 'text' => 'Đã xử lý xong'];
                                    }

                                    $rowClass = '';
                                    if ($attention['level'] === 'late') {
                                        $rowClass = 'booking-row-late';
                                    } elseif ($attention['level'] === 'danger') {
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
                                        <span class="booking-priority-label <?php echo e($priority['class']); ?>">
                                            <i class="bx <?php echo e($priority['icon']); ?>"></i>
                                            <?php echo e($priority['text']); ?>

                                        </span>
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
                                        <?php if($isLateCheckout && $booking->actual_check_out): ?>
                                            <div class="booking-sub-text" style="color:#7e22ce;font-weight:800">
                                                Trả thực tế: <?php echo e($booking->actual_check_out->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?>

                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="booking-badge <?php echo e($bookingStatusClass); ?>">
                                            <?php echo e($bookingStatusLabels[$booking->status] ?? $booking->status); ?>

                                        </span>
                                        <?php if($isLateCheckout): ?>
                                            <div class="mt-1">
                                                <span class="booking-badge booking-status-late">
                                                    Trả muộn · <?php echo e($lateCheckoutText); ?>

                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if($booking->pendingCancellationRequest): ?>
                                            <div class="mt-1">
                                                <span class="booking-badge booking-status-warning">
                                                    Khách yêu cầu hủy
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if($booking->pendingRoomIssueRequest): ?>
                                            <div class="mt-1">
                                                <span class="booking-badge booking-status-warning">
                                                    Báo sự cố / yêu cầu đổi phòng
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="booking-badge <?php echo e($paymentStatusClass); ?>">
                                            <?php echo e($paymentStatusLabels[$booking->payment_status] ?? $booking->payment_status); ?>

                                        </span>
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
            if (typeof flatpickr === 'undefined') return;

            var locale = (flatpickr.l10ns && flatpickr.l10ns.vn) ? 'vn' : 'default';
            var dateOpts = {
                locale: locale,
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: false,
                disableMobile: true,
            };
            var timeOpts = {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                minuteIncrement: 30,
                locale: locale,
                allowInput: false,
                disableMobile: true,
            };

            var fpFrom = flatpickr('#filterDateFrom', Object.assign({}, dateOpts));
            var fpTo   = flatpickr('#filterDateTo',   Object.assign({}, dateOpts));

            // When date_from changes, ensure date_to >= date_from
            document.getElementById('filterDateFrom') && document.getElementById('filterDateFrom').addEventListener('change', function () {
                if (fpFrom && fpTo) {
                    fpTo.set('minDate', fpFrom.selectedDates[0] || null);
                    if (fpTo.selectedDates[0] && fpFrom.selectedDates[0] && fpTo.selectedDates[0] < fpFrom.selectedDates[0]) {
                        fpTo.setDate(fpFrom.selectedDates[0]);
                    }
                }
            });

            flatpickr('#filterTimeFrom', timeOpts);
            flatpickr('#filterTimeTo',   timeOpts);
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/bookings/index.blade.php ENDPATH**/ ?>