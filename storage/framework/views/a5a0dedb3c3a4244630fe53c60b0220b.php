<?php $__env->startSection('title', 'Báo cáo doanh thu'); ?>

<?php $__env->startSection('content'); ?>
    <style>
        .stats-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .stats-number {
            font-size: 32px;
            font-weight: 800;
            color: #1f2937;
        }

        .stats-label {
            font-size: 13px;
            color: #64748b;
            margin-top: 8px;
        }

        .report-table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
        }

        .report-table table {
            margin-bottom: 0;
        }

        .report-table th {
            background: #f8fafc;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
    </style>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Báo cáo
            </p>

            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo e(route('admin.reports.index')); ?>">
                        <i class="bx bx-bar-chart-alt-2 me-1"></i> Báo cáo doanh thu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-dark" href="<?php echo e(route('admin.reports.room')); ?>">
                        <i class="bx bx-pie-chart-alt-2 me-1"></i> Báo cáo tình trạng phòng
                    </a>
                </li>
            </ul>

            <div class="admin-page-head">

                <div>
                    <h2><?php echo e($title ?? 'Báo cáo doanh thu'); ?></h2>
                    <p>Xem thống kê doanh thu theo thời gian</p>
                </div>

                
                <div class="d-flex align-items-center gap-2">
                    <form method="GET" action="<?php echo e(route('admin.reports.export-pdf')); ?>"
                        class="d-flex align-items-center gap-2 flex-wrap" id="pdfExportForm">

                        
                        <select name="pdf_mode" id="pdfMode" class="form-select form-select-sm" style="width:130px">
                            <option value="year">Cả năm</option>
                            <option value="month">Theo tháng</option>
                            <option value="range">Khoảng ngày</option>
                        </select>

                        
                        <select name="year" id="pdfYear" class="form-select form-select-sm" style="width:90px">
                            <?php for($y = now()->year; $y >= now()->year - 4; $y--): ?>
                                <option value="<?php echo e($y); ?>"><?php echo e($y); ?></option>
                            <?php endfor; ?>
                        </select>

                        
                        <select name="month" id="pdfMonth" class="form-select form-select-sm d-none" style="width:110px">
                            <?php $__currentLoopData = ['01'=>'Tháng 1','02'=>'Tháng 2','03'=>'Tháng 3','04'=>'Tháng 4','05'=>'Tháng 5','06'=>'Tháng 6','07'=>'Tháng 7','08'=>'Tháng 8','09'=>'Tháng 9','10'=>'Tháng 10','11'=>'Tháng 11','12'=>'Tháng 12']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e((int)$v); ?>" <?php echo e(now()->month == (int)$v ? 'selected' : ''); ?>><?php echo e($l); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>

                        
                        <div id="pdfRangeWrap" class="d-none d-flex align-items-center gap-1">
                            <input type="date" name="range_from" id="pdfRangeFrom" data-year-select
                                class="form-control form-control-sm" data-no-min
                                value="<?php echo e(now()->startOfMonth()->format('Y-m-d')); ?>" style="width:130px">
                            <span class="text-muted small">→</span>
                            <input type="date" name="range_to" id="pdfRangeTo" data-year-select
                                class="form-control form-control-sm" data-no-min
                                value="<?php echo e(now()->format('Y-m-d')); ?>" style="width:130px">
                        </div>

                        <button type="submit" class="btn btn-gold btn-sm">
                            <i class="bx bxs-file-pdf me-1"></i>
                            Xuất PDF
                        </button>
                    </form>
                </div>

            </div>

            <!-- Filter Section -->
            <div class="bg-white p-4 rounded-3 mb-4 border">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Loại báo cáo</label>
                        <select id="reportType" class="form-select">
                            <option value="daily" <?php echo e($reportType === 'daily' ? 'selected' : ''); ?>>Theo ngày</option>
                            <option value="monthly" <?php echo e($reportType === 'monthly' ? 'selected' : ''); ?>>Theo tháng</option>
                            <option value="room_category" <?php echo e($reportType === 'room_category' ? 'selected' : ''); ?>>Theo hạng phòng</option>
                            <option value="service" <?php echo e($reportType === 'service' ? 'selected' : ''); ?>>Theo dịch vụ</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Từ ngày</label>
                        <input type="date" id="startDate" class="form-control" value="<?php echo e($startDate); ?>" data-no-min data-year-select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Đến ngày</label>
                        <input type="date" id="endDate" class="form-control" value="<?php echo e($endDate); ?>" data-no-min data-year-select>
                    </div>
                    <div class="col-md-3">
                        <button id="applyFilter" class="btn btn-primary w-100">Áp dụng</button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <?php if($reportType === 'daily'): ?>
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo e($totalBookings ?? 0); ?></div>
                            <div class="stats-label">Tổng booking</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stats-card">
                            <div class="stats-number text-success"><?php echo e(number_format($totalRevenue ?? 0, 0, ',', '.')); ?>đ</div>
                            <div class="stats-label">Tổng doanh thu</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stats-card">
                            <div class="stats-number text-primary"><?php echo e(number_format($totalPaid ?? 0, 0, ',', '.')); ?>đ</div>
                            <div class="stats-label">Đã thanh toán</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stats-card">
                            <div class="stats-number text-warning"><?php echo e(number_format($totalPending ?? 0, 0, ',', '.')); ?>đ</div>
                            <div class="stats-label">Còn nợ</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Report Tables -->
            <?php if($reportType === 'daily' && isset($dailyData)): ?>
                <div class="report-table">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Số booking</th>
                                <th>Doanh thu</th>
                                <th>Đã thanh toán</th>
                                <th>Còn nợ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $dailyData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($day['date']); ?></td>
                                    <td><?php echo e($day['bookings']); ?></td>
                                    <td><?php echo e(number_format($day['revenue'], 0, ',', '.')); ?>đ</td>
                                    <td><?php echo e(number_format($day['paid'], 0, ',', '.')); ?>đ</td>
                                    <td><?php echo e(number_format($day['revenue'] - $day['paid'], 0, ',', '.')); ?>đ</td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif($reportType === 'monthly' && isset($monthlyData)): ?>
                <div class="report-table">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tháng</th>
                                <th>Số booking</th>
                                <th>Doanh thu</th>
                                <th>Đã thanh toán</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $monthlyData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $month): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($month['month']); ?></td>
                                    <td><?php echo e($month['bookings']); ?></td>
                                    <td><?php echo e(number_format($month['revenue'], 0, ',', '.')); ?>đ</td>
                                    <td><?php echo e(number_format($month['paid'], 0, ',', '.')); ?>đ</td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif($reportType === 'room_category' && isset($categoryData)): ?>
                <div class="report-table">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Hạng phòng</th>
                                <th>Số phòng</th>
                                <th>Số booking</th>
                                <th>Số đêm</th>
                                <th>Doanh thu</th>
                                <th>ADR (Trung bình/đêm)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $categoryData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($category['category']); ?></td>
                                    <td><?php echo e($category['roomCount']); ?></td>
                                    <td><?php echo e($category['bookings']); ?></td>
                                    <td><?php echo e($category['nights']); ?></td>
                                    <td><?php echo e(number_format($category['revenue'], 0, ',', '.')); ?>đ</td>
                                    <td><?php echo e(number_format($category['adr'], 0, ',', '.')); ?>đ</td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif($reportType === 'service' && isset($serviceData)): ?>
                <div class="report-table">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dịch vụ</th>
                                <th>Số lượng</th>
                                <th>Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $serviceData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($service['service']); ?></td>
                                    <td><?php echo e($service['quantity']); ?></td>
                                    <td><?php echo e(number_format($service['revenue'], 0, ',', '.')); ?>đ</td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </main>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // ── Bộ lọc báo cáo web ────────────────────────────────────
            const reportType = document.getElementById('reportType');
            const startDate  = document.getElementById('startDate');
            const endDate    = document.getElementById('endDate');
            const applyFilter = document.getElementById('applyFilter');

            applyFilter && applyFilter.addEventListener('click', function () {
                const url = new URL(window.location.href);
                url.searchParams.set('type', reportType.value);
                url.searchParams.set('start_date', startDate.value);
                url.searchParams.set('end_date', endDate.value);
                window.location.href = url.toString();
            });

            // ── Form xuất PDF ─────────────────────────────────────────
            const pdfMode       = document.getElementById('pdfMode');
            const pdfYear       = document.getElementById('pdfYear');
            const pdfMonth      = document.getElementById('pdfMonth');
            const pdfRangeWrap  = document.getElementById('pdfRangeWrap');

            function syncPdfForm() {
                const mode = pdfMode.value;
                pdfYear.classList.toggle('d-none', mode === 'range');
                pdfMonth.classList.toggle('d-none', mode !== 'month');
                pdfRangeWrap.classList.toggle('d-none', mode !== 'range');
            }

            pdfMode && pdfMode.addEventListener('change', syncPdfForm);
            syncPdfForm();
        });
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\reports\index.blade.php ENDPATH**/ ?>