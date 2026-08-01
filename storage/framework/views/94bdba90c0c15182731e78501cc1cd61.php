<?php $__env->startSection('title', 'Quản lý đánh giá'); ?>

<?php $__env->startSection('content'); ?>
    <div class="admin-wrapper">
        <div class="admin-content">
            <div class="admin-page-head">
                <div>
                    <div class="admin-breadcrumb">
                        <a href="<?php echo e(route('admin.dashboard')); ?>">Dashboard</a>
                        <span>/</span>
                        <span>Đánh giá khách sạn</span>
                    </div>
                    <h2>Quản lý đánh giá khách sạn</h2>
                    <p>Theo dõi và phản hồi các đánh giá đã được hệ thống tự động hiển thị sau khi lọc từ cấm.</p>
                </div>
            </div>

            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Tổng</div>
                            <div class="h4 fw-bold mb-0"><?php echo e($stats['total']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Chờ duyệt</div>
                            <div class="h4 fw-bold mb-0 text-warning"><?php echo e($stats['pending']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Đã duyệt</div>
                            <div class="h4 fw-bold mb-0 text-success"><?php echo e($stats['approved']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Đã ẩn</div>
                            <div class="h4 fw-bold mb-0 text-secondary"><?php echo e($stats['hidden']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Điểm trung bình công khai</div>
                            <div class="h4 fw-bold mb-0 text-warning">★ <?php echo e(number_format((float) $stats['average'], 1)); ?>/5</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="<?php echo e(route('admin.reviews.index')); ?>" class="row g-2 align-items-end">
                        <div class="col-lg-4">
                            <label class="form-label small fw-semibold">Tìm kiếm</label>
                            <input type="text" name="q" value="<?php echo e($keyword); ?>" class="form-control" placeholder="Mã booking, tên khách, nội dung...">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label small fw-semibold">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="pending" <?php echo e($status === 'pending' ? 'selected' : ''); ?>>Chờ duyệt</option>
                                <option value="approved" <?php echo e($status === 'approved' ? 'selected' : ''); ?>>Đã duyệt</option>
                                <option value="hidden" <?php echo e($status === 'hidden' ? 'selected' : ''); ?>>Đã ẩn</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label small fw-semibold">Số sao</label>
                            <select name="rating" class="form-select">
                                <option value="">Tất cả</option>
                                <?php for($i = 5; $i >= 1; $i--): ?>
                                    <option value="<?php echo e($i); ?>" <?php echo e((string) $rating === (string) $i ? 'selected' : ''); ?>><?php echo e($i); ?> sao</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-lg-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Lọc</button>
                            <a href="<?php echo e(route('admin.reviews.index')); ?>" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Khách</th>
                                <th>Booking</th>
                                <th>Đánh giá</th>
                                <th>Nội dung</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $reviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($review->guest_name); ?></div>
                                        <div class="small text-muted"><?php echo e($review->customer->phone ?? $review->customer->email ?? '---'); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($review->booking->booking_code ?? '---'); ?></div>
                                        <div class="small text-muted"><?php echo e($review->booking->roomCategory->name ?? '---'); ?></div>
                                    </td>
                                    <td>
                                        <div class="text-warning small"><?php echo e($review->star_text); ?></div>
                                        <div class="small text-muted"><?php echo e(number_format((float) $review->rating, 1)); ?>/5</div>
                                    </td>
                                    <td style="max-width: 360px;">
                                        <?php if($review->title): ?>
                                            <div class="fw-semibold small"><?php echo e($review->title); ?></div>
                                        <?php endif; ?>
                                        <div class="small text-muted text-truncate"><?php echo e($review->comment); ?></div>
                                        <?php if($review->admin_reply): ?>
                                            <span class="badge text-bg-info mt-1">Đã phản hồi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo e($review->status_badge_class); ?>"><?php echo e($review->status_label); ?></span>
                                        <div class="small text-muted mt-1"><?php echo e(optional($review->created_at)->format('d/m/Y H:i')); ?></div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            <a href="<?php echo e(route('admin.reviews.show', $review)); ?>" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">Chưa có đánh giá phù hợp.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if($reviews->hasPages()): ?>
                    <div class="card-footer bg-white">
                        <?php echo e($reviews->links()); ?>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\reviews\index.blade.php ENDPATH**/ ?>