<?php $__env->startSection('title', 'Lịch sử đặt phòng'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $statusLabels = [
            'pending' => 'Chờ xử lý',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đã nhận phòng',
            'inspection_requested' => 'Đang kiểm tra phòng',
            'checked_out' => 'Đã trả phòng',
            'completed' => 'Đã hoàn tất',
            'cancelled' => 'Đã hủy',
        ];

        $statusBadgeClasses = [
            'pending' => 'text-bg-warning',
            'confirmed' => 'text-bg-primary',
            'checked_in' => 'text-bg-info',
            'inspection_requested' => 'text-bg-secondary',
            'checked_out' => 'text-bg-success',
            'completed' => 'text-bg-success',
            'cancelled' => 'text-bg-danger',
        ];
    ?>

    <section class="page-header">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="display-6 fw-bold mb-1">Lịch sử đặt phòng</h1>
            </div>
            <a href="<?php echo e(route('rooms')); ?>" class="btn btn-outline-primary">
                <i class="bx bx-plus-circle me-1"></i>
                Đặt phòng mới
            </a>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Tổng đơn</div>
                            <div class="h4 fw-bold mb-0"><?php echo e($bookingStatusCounts->sum()); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Đang lưu trú</div>
                            <div class="h4 fw-bold mb-0"><?php echo e((int) ($bookingStatusCounts['checked_in'] ?? 0)); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Đã hoàn tất</div>
                            <div class="h4 fw-bold mb-0"><?php echo e((int) ($bookingStatusCounts['checked_out'] ?? 0) + (int) ($bookingStatusCounts['completed'] ?? 0)); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Đã hủy</div>
                            <div class="h4 fw-bold mb-0"><?php echo e((int) ($bookingStatusCounts['cancelled'] ?? 0)); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="<?php echo e(route('bookings.history')); ?>" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Mã đơn</label>
                            <input type="text" name="q" class="form-control" value="<?php echo e($searchKeyword); ?>"
                                placeholder="VD: BK202606...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php echo e($selectedStatus === $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Khoảng thời gian</label>
                            <select name="period" class="form-select">
                                <option value="">Tất cả thời gian</option>
                                <option value="30_days" <?php echo e($selectedPeriod === '30_days' ? 'selected' : ''); ?>>30 ngày gần nhất</option>
                                <option value="3_months" <?php echo e($selectedPeriod === '3_months' ? 'selected' : ''); ?>>3 tháng gần nhất</option>
                                <option value="12_months" <?php echo e($selectedPeriod === '12_months' ? 'selected' : ''); ?>>12 tháng gần nhất</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button class="btn btn-primary flex-fill" type="submit">Lọc</button>
                            <a href="<?php echo e(route('bookings.history')); ?>" class="btn btn-outline-secondary" title="Xóa lọc">
                                <i class="bx bx-refresh"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Hạng phòng</th>
                                <th>Nhận / trả phòng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Đánh giá</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $review = $booking->hotelReview;
                                    $status = $booking->status;
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($booking->booking_code); ?></div>
                                        <div class="small text-muted"><?php echo e(optional($booking->created_at)->format('d/m/Y H:i')); ?></div>
                                    </td>
                                    <td><?php echo e($booking->roomCategory->name ?? 'Không xác định'); ?></td>
                                    <td>
                                        <div><?php echo e(optional($booking->check_in_at)->format('d/m/Y H:i')); ?></div>
                                        <div class="small text-muted"><?php echo e(optional($booking->check_out_at)->format('d/m/Y H:i')); ?></div>
                                    </td>
                                    <td class="fw-semibold"><?php echo e(number_format((float) $booking->estimated_total, 0, ',', '.')); ?>đ</td>
                                    <td>
                                        <span class="badge <?php echo e($statusBadgeClasses[$status] ?? 'text-bg-secondary'); ?>">
                                            <?php echo e($statusLabels[$status] ?? $status); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <?php if($review): ?>
                                            <div class="text-warning small"><?php echo e($review->star_text); ?></div>
                                            <span class="badge <?php echo e($review->status_badge_class); ?>"><?php echo e($review->status_label); ?></span>
                                        <?php elseif($booking->canBeReviewed()): ?>
                                            <span class="text-muted small">Chưa đánh giá</span>
                                        <?php else: ?>
                                            <span class="text-muted small">Sau khi trả phòng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            <a href="<?php echo e(route('bookings.show', $booking)); ?>" class="btn btn-sm btn-outline-primary">
                                                Chi tiết
                                            </a>
                                            <?php if($review): ?>
                                                <a href="<?php echo e(route('reviews.edit', $review)); ?>" class="btn btn-sm btn-outline-secondary">
                                                    Sửa đánh giá
                                                </a>
                                            <?php elseif($booking->canBeReviewed()): ?>
                                                <a href="<?php echo e(route('bookings.reviews.create', $booking)); ?>" class="btn btn-sm btn-primary">
                                                    Đánh giá
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="mb-2"><i class="bx bx-calendar-x fs-1 text-muted"></i></div>
                                        <div class="fw-semibold">Chưa có đơn đặt phòng phù hợp</div>
                                        <div class="text-muted small">Bạn có thể đặt phòng mới hoặc xóa bộ lọc hiện tại.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if($bookings->hasPages()): ?>
                    <div class="card-footer bg-white">
                        <?php echo e($bookings->links()); ?>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\user\pages\booking-history.blade.php ENDPATH**/ ?>