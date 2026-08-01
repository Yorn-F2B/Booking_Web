<?php $__env->startSection('title', 'Báo cáo tình trạng phòng'); ?>

<?php $__env->startSection('content'); ?>
    <style>
        .stats-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .report-table table {
            margin-bottom: 0;
        }

        .report-table th {
            background: #f8fafc;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-available { background: #dcfce7; color: #166534; }
        .status-reserved { background: #dbeafe; color: #1e40af; }
        .status-occupied { background: #fef3c7; color: #92400e; }
        .status-cleaning { background: #e0e7ff; color: #3730a3; }
        .status-inspection { background: #fce7f3; color: #9d174d; }
        .status-maintenance { background: #fee2e2; color: #991b1b; }

        .chart-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            height: 300px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .report-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .report-meta {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 8px;
        }

        .export-btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .export-btn:hover {
            transform: translateY(-1px);
        }

        @media print {
            body { background: #fff !important; }
            .no-print, .admin-sidebar, .admin-header, .admin-breadcrumb, .btn { display: none !important; }
            .admin-wrapper { padding-left: 0 !important; }
            .admin-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .stats-card { break-inside: avoid; border: 1px solid #ddd !important; box-shadow: none !important; }
            .chart-container { break-inside: avoid; border: 1px solid #ddd !important; box-shadow: none !important; margin-bottom: 20px !important; page-break-inside: avoid; }
            .report-table { break-inside: avoid; border: 1px solid #ddd !important; box-shadow: none !important; }
            .report-header { background: #f8fafc !important; color: #000 !important; border: 1px solid #ddd !important; }
            .report-header * { color: #000 !important; }
        }
    </style>

    <div class="admin-wrapper">
        <main class="admin-content">
            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Báo cáo tình trạng phòng
            </p>

            <ul class="nav nav-tabs mb-4 no-print">
                <li class="nav-item">
                    <a class="nav-link text-dark" href="<?php echo e(route('admin.reports.index')); ?>">
                        <i class="bx bx-bar-chart-alt-2 me-1"></i> Báo cáo doanh thu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo e(route('admin.reports.room')); ?>">
                        <i class="bx bx-pie-chart-alt-2 me-1"></i> Báo cáo tình trạng phòng
                    </a>
                </li>
            </ul>

            <div class="admin-page-head no-print">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2>Báo cáo tình trạng phòng</h2>
                        <p>Tổng quan tình trạng phòng theo ngày</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" onclick="window.print()" class="btn btn-outline-primary export-btn">
                            <i class="fas fa-print me-1"></i> In báo cáo
                        </button>
                        <a href="<?php echo e(route('admin.reports.room.export-pdf', request()->query())); ?>" class="btn btn-outline-danger export-btn">
                            <i class="fas fa-file-pdf me-1"></i> Xuất PDF
                        </a>
                        <a href="<?php echo e(route('admin.reports.room.export-csv', request()->query())); ?>" class="btn btn-outline-success export-btn">
                            <i class="fas fa-file-csv me-1"></i> Xuất CSV
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="report-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="mb-2">MCuong Hotel - Báo cáo tình trạng phòng</h3>
                        <div class="report-meta">
                            <?php if($startDate && $endDate): ?>
                                <span>Kỳ báo cáo: <?php echo e(\Carbon\Carbon::parse($startDate)->format('d/m/Y')); ?> - <?php echo e(\Carbon\Carbon::parse($endDate)->format('d/m/Y')); ?></span>
                                <span class="mx-2">|</span>
                                <span>Snapshot tại: <?php echo e(\Carbon\Carbon::parse($endDate)->format('d/m/Y')); ?></span>
                            <?php else: ?>
                                <span>Ngày báo cáo: <?php echo e(\Carbon\Carbon::parse($date)->format('d/m/Y')); ?></span>
                            <?php endif; ?>
                            <span class="mx-2">|</span>
                            <span>Cập nhật: <?php echo e(now()->format('H:i')); ?></span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-size: 36px; font-weight: 800; color: <?php echo e($occupancyRate >= 80 ? '#4ade80' : ($occupancyRate >= 50 ? '#fbbf24' : '#f87171')); ?>;">
                            <?php echo e($occupancyRate); ?>%
                        </div>
                        <div style="font-size: 12px; color: #94a3b8;">Công suất phòng</div>
                    </div>
                </div>
            </div>

            
            <?php if(!$isToday): ?>
                <div class="alert alert-info d-flex align-items-center mb-4 no-print" style="border-radius:10px; font-size:13px;">
                    <i class="bx bx-info-circle me-2 fs-5"></i>
                    <span>
                        <strong>Dữ liệu lịch sử:</strong>
                        Trạng thái phòng được tính lại từ lịch sử đặt phòng tại ngày đã chọn.
                        Các trạng thái <strong>Đang dọn</strong>, <strong>Chờ kiểm tra</strong>, <strong>Bảo trì</strong>
                        chỉ hiển thị chính xác khi xem <strong>Hôm nay</strong>.
                    </span>
                </div>
            <?php endif; ?>

            <!-- Filter Section -->
            <div class="bg-white p-4 rounded-3 mb-4 border no-print">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Ngày</label>
                        <input type="date" id="reportDate" class="form-control" value="<?php echo e($date); ?>" data-no-min data-year-select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Khoảng ngày</label>
                        <div class="input-group">
                            <input type="date" id="reportStartDate" class="form-control" value="<?php echo e($startDate); ?>" placeholder="Từ" data-no-min data-year-select>
                            <span class="input-group-text">-</span>
                            <input type="date" id="reportEndDate" class="form-control" value="<?php echo e($endDate); ?>" placeholder="Đến" data-no-min data-year-select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tầng</label>
                        <select id="reportFloor" class="form-select">
                            <option value="">Tất cả</option>
                            <?php $__currentLoopData = $floors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($f); ?>" <?php echo e($floor == $f ? 'selected' : ''); ?>>Tầng <?php echo e($f); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Hạng phòng</label>
                        <select id="reportCategory" class="form-select">
                            <option value="">Tất cả</option>
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($cat->id); ?>" <?php echo e($categoryId == $cat->id ? 'selected' : ''); ?>><?php echo e($cat->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Trạng thái</label>
                        <select id="reportStatus" class="form-select">
                            <option value="">Tất cả</option>
                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" <?php echo e($status == $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button id="applyFilter" class="btn btn-primary w-100">Áp dụng</button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-2">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo e($totalRooms); ?></div>
                        <div class="stats-label">Tổng số phòng</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stats-card">
                        <div class="stats-number text-success"><?php echo e($availableRooms); ?></div>
                        <div class="stats-label">Phòng trống</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stats-card">
                        <div class="stats-number text-primary"><?php echo e($reservedRooms); ?></div>
                        <div class="stats-label">Phòng đã đặt</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stats-card">
                        <div class="stats-number text-warning"><?php echo e($occupiedRooms); ?></div>
                        <div class="stats-label">Phòng đang ở</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stats-card">
                        <div class="stats-number text-info"><?php echo e($cleaningRooms); ?></div>
                        <div class="stats-label">Phòng đang dọn</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="stats-card">
                        <div class="stats-number text-danger"><?php echo e($maintenanceRooms); ?></div>
                        <div class="stats-label">Phòng bảo trì</div>
                    </div>
                </div>
            </div>

            <!-- Occupancy Rate Card -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="stats-card" style="text-align: left; padding: 30px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1">Công suất phòng</h3>
                                <p class="text-muted mb-0">Số phòng đang được đặt hoặc đang ở / tổng số phòng</p>
                                <div class="mt-2">
                                    <?php if($previousOccupancyRate !== null): ?>
                                        <?php if($occupancyTrend === 'up'): ?>
                                            <span class="badge bg-success">↑ <?php echo e(abs($occupancyChange)); ?>% so với hôm qua</span>
                                        <?php elseif($occupancyTrend === 'down'): ?>
                                            <span class="badge bg-danger">↓ <?php echo e(abs($occupancyChange)); ?>% so với hôm qua</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">→ Không đổi so với hôm qua</span>
                                        <?php endif; ?>
                                        <span class="text-muted ms-2">Hôm qua: <?php echo e($previousOccupancyRate); ?>%</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Chế độ khoảng ngày</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="stats-number" style="font-size: 48px; color: <?php echo e($occupancyRate >= 80 ? '#16a34a' : ($occupancyRate >= 50 ? '#d97706' : '#dc2626')); ?>;">
                                    <?php echo e($occupancyRate); ?>%
                                </div>
                                <div class="stats-label">Khách sạn đang kín <?php echo e($occupancyRate); ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Summary -->
            <div class="report-table mb-4">
                <div class="p-3 border-bottom bg-light">
                    <h5 class="mb-0">📊 Tóm tắt nhanh</h5>
                </div>
                <div class="p-3">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="mb-2">
                                <span class="badge bg-success fs-6"><?php echo e($availableRooms); ?></span>
                                <span class="text-muted ms-1">phòng trống</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <span class="badge bg-primary fs-6"><?php echo e($reservedRooms); ?></span>
                                <span class="text-muted ms-1">phòng đã đặt</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <span class="badge bg-warning fs-6"><?php echo e($occupiedRooms); ?></span>
                                <span class="text-muted ms-1">phòng đang ở</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <span class="badge bg-danger fs-6"><?php echo e($cleaningRooms + $maintenanceRooms); ?></span>
                                <span class="text-muted ms-1">phòng không sẵn sàng</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="mb-3">Biểu đồ trạng thái phòng</h5>
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="mb-3">Biểu đồ công suất theo hạng phòng</h5>
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Category Statistics Table -->
            <div class="report-table mb-4">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0">Thống kê theo hạng phòng</h5>
                </div>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Hạng phòng</th>
                            <th class="text-center">Tổng phòng</th>
                            <th class="text-center">Đang ở</th>
                            <th class="text-center">Đã đặt</th>
                            <th class="text-center">Trống</th>
                            <th class="text-center">Đang dọn</th>
                            <th class="text-center">Bảo trì</th>
                            <th class="text-center">Công suất</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $categoryStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($stat['category']); ?></td>
                            <td class="text-center"><?php echo e($stat['total']); ?></td>
                            <td class="text-center"><?php echo e($stat['occupied']); ?></td>
                            <td class="text-center"><?php echo e($stat['reserved']); ?></td>
                            <td class="text-center"><?php echo e($stat['available']); ?></td>
                            <td class="text-center"><?php echo e($stat['cleaning']); ?></td>
                            <td class="text-center"><?php echo e($stat['maintenance']); ?></td>
                            <td class="text-center">
                                <span class="badge <?php echo e($stat['occupancy_rate'] >= 80 ? 'bg-success' : ($stat['occupancy_rate'] >= 50 ? 'bg-warning' : 'bg-danger')); ?>">
                                    <?php echo e($stat['occupancy_rate']); ?>%
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            <!-- Room List Table -->
            <div class="report-table">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0">Danh sách phòng chi tiết</h5>
                </div>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Số phòng</th>
                            <th>Tầng</th>
                            <th>Hạng phòng</th>
                            <th>Trạng thái</th>
                            <th>Khách hàng</th>
                            <th>Giờ trả phòng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $roomList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><strong><?php echo e($room['room_number']); ?></strong></td>
                            <td>Tầng <?php echo e($room['floor']); ?></td>
                            <td><?php echo e($room['category']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo e($room['status']); ?>">
                                    <?php echo e($statuses[$room['status']] ?? $room['status']); ?>

                                </span>
                            </td>
                            <td><?php echo e($room['customer']); ?></td>
                            <td><?php echo e($room['check_out']); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Filter functionality
            const reportDate = document.getElementById('reportDate');
            const reportStartDate = document.getElementById('reportStartDate');
            const reportEndDate = document.getElementById('reportEndDate');
            const reportFloor = document.getElementById('reportFloor');
            const reportCategory = document.getElementById('reportCategory');
            const reportStatus = document.getElementById('reportStatus');
            const applyFilter = document.getElementById('applyFilter');

            // Clear date range when single date is changed
            reportDate.addEventListener('change', function() {
                if (this.value) {
                    if (reportStartDate._flatpickr) reportStartDate._flatpickr.clear();
                    if (reportEndDate._flatpickr) reportEndDate._flatpickr.clear();
                    reportStartDate.value = '';
                    reportEndDate.value = '';
                }
            });

            // Clear single date when date range is changed
            const clearSingleDate = function() {
                if (this.value) {
                    if (reportDate._flatpickr) reportDate._flatpickr.clear();
                    reportDate.value = '';
                }
            };
            reportStartDate.addEventListener('change', clearSingleDate);
            reportEndDate.addEventListener('change', clearSingleDate);

            applyFilter && applyFilter.addEventListener('click', function () {
                const url = new URL(window.location.href);
                
                // Clear existing date parameters
                url.searchParams.delete('date');
                url.searchParams.delete('start_date');
                url.searchParams.delete('end_date');
                
                // Validate and apply dates
                if (reportStartDate.value && reportEndDate.value) {
                    if (new Date(reportStartDate.value) > new Date(reportEndDate.value)) {
                        alert('Ngày bắt đầu không thể lớn hơn ngày kết thúc!');
                        return;
                    }
                    url.searchParams.set('start_date', reportStartDate.value);
                    url.searchParams.set('end_date', reportEndDate.value);
                } else if (reportDate.value) {
                    url.searchParams.set('date', reportDate.value);
                } else if (reportStartDate.value || reportEndDate.value) {
                    alert('Vui lòng chọn đầy đủ cả Ngày bắt đầu và Ngày kết thúc cho Khoảng ngày!');
                    return;
                }
                
                url.searchParams.set('floor', reportFloor.value);
                url.searchParams.set('category_id', reportCategory.value);
                url.searchParams.set('status', reportStatus.value);
                window.location.href = url.toString();
            });

            // Status Chart (Pie Chart)
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($statusChart['labels'], 15, 512) ?>,
                        datasets: [{
                            data: <?php echo json_encode($statusChart['data'], 15, 512) ?>,
                            backgroundColor: [
                                '#22c55e',
                                '#3b82f6',
                                '#f59e0b',
                                '#ec4899',
                                '#6366f1',
                                '#ef4444'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            // Category Chart (Bar Chart)
            const categoryCtx = document.getElementById('categoryChart');
            if (categoryCtx) {
                new Chart(categoryCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($categoryChart['labels'], 15, 512) ?>,
                        datasets: [{
                            label: 'Công suất (%)',
                            data: <?php echo json_encode($categoryChart['data'], 15, 512) ?>,
                            backgroundColor: '#3b82f6'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        });
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\reports\room.blade.php ENDPATH**/ ?>