<?php $__env->startSection('title', 'Chi tiết tiện ích'); ?>

<?php $__env->startSection('content'); ?>

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('amenities.index')); ?>">Admin</a> / Chi tiết tiện ích
        </p>

        <div class="admin-page-head">

            <div>
                <h2>Chi tiết tiện ích</h2>
                <p>Thông tin chi tiết tiện ích</p>
            </div>

            <a href="<?php echo e(route('amenities.edit', $amenity->id)); ?>" class="btn btn-gold">
                <i class="bx bx-edit me-1"></i>
                Chỉnh sửa
            </a>

        </div>

        <div class="settings-section">

            <div class="row">

                <div class="col-md-4 mb-4">

                    <label class="text-muted small">ID</label>

                    <h5><?php echo e($amenity->id); ?></h5>

                </div>

                <div class="col-md-4 mb-4">

                    <label class="text-muted small">Tên tiện ích</label>

                    <h5><?php echo e($amenity->name); ?></h5>

                </div>

                <div class="col-md-4 mb-4">

                    <label class="text-muted small">Icon</label>

                    <div>
                        <?php if($amenity->icon): ?>

                            <i class="<?php echo e($amenity->icon); ?>" style="font-size: 32px;"></i>

                            <div class="text-muted small mt-2">
                                <?php echo e($amenity->icon); ?>

                            </div>

                        <?php else: ?>

                            <span class="text-muted">Chưa có icon</span>

                        <?php endif; ?>
                    </div>

                </div>

                <div class="col-md-4 mb-4">

                    <label class="text-muted small">Ngày tạo</label>

                    <h5><?php echo e($amenity->created_at?->format('d/m/Y H:i')); ?></h5>

                </div>

            </div>

            <div class="mt-4 d-flex gap-2">

                <a href="<?php echo e(route('amenities.edit', $amenity->id)); ?>" class="btn btn-primary">
                    Chỉnh sửa
                </a>

                <a href="<?php echo e(route('amenities.index')); ?>" class="btn btn-outline-secondary">
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
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\amenities\show.blade.php ENDPATH**/ ?>