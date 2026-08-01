<?php $__env->startSection('title', 'Chi tiết dịch vụ'); ?>

<?php $__env->startSection('content'); ?>

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('services.index')); ?>">Admin</a> / Chi tiết dịch vụ
        </p>

        <div class="admin-page-head">

            <div>
                <h2>Chi tiết dịch vụ</h2>
                <p>Thông tin chi tiết dịch vụ</p>
            </div>

            <a href="<?php echo e(route('services.edit', $service->id)); ?>" class="btn btn-gold">
                <i class="bx bx-edit me-1"></i>
                Chỉnh sửa
            </a>

        </div>

        <div class="settings-section">

            <div class="row">

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Tên dịch vụ</label>

                    <h5><?php echo e($service->name); ?></h5>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Loại</label>

                    <div>
                        <?php if($service->type == 'service'): ?>
                            <span class="badge bg-primary">Dịch vụ</span>
                        <?php elseif($service->type == 'minibar_order'): ?>
                            <span class="badge bg-info text-dark">Minibar gọi thêm</span>
                        <?php elseif($service->type == 'minibar'): ?>
                            <span class="badge bg-warning text-dark">Minibar</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Phí hư hại</span>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Giá</label>

                    <h5><?php echo e(number_format($service->price, 0, ',', '.')); ?>đ</h5>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Đơn vị</label>

                    <h5><?php echo e($service->unit); ?></h5>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Cách tính khi lịch lưu trú thay đổi</label>

                    <h5><?php echo e($service->billing_rule_label); ?></h5>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Trạng thái</label>

                    <div>
                        <?php if($service->status == 'active'): ?>
                            <span class="badge bg-success">Hoạt động</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Tạm ẩn</span>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Ngày tạo</label>

                    <h5><?php echo e($service->created_at?->format('d/m/Y H:i')); ?></h5>

                </div>

            </div>

            <hr class="my-4">

            <div>

                <label class="text-muted small mb-2">Mô tả</label>

                <div class="lh-lg">
                    <?php echo e($service->description ?: 'Chưa có mô tả'); ?>

                </div>

            </div>

            <div class="mt-4 d-flex gap-2">

                <a href="<?php echo e(route('services.edit', $service->id)); ?>" class="btn btn-primary">
                    Chỉnh sửa
                </a>

                <a href="<?php echo e(route('services.index')); ?>" class="btn btn-outline-secondary">
                    Quay lại
                </a>

            </div>

        </div>

    </main>

    <footer class="admin-footer">
        <span>MCuong Hotel Admin</span>
    </footer>

</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\services\show.blade.php ENDPATH**/ ?>