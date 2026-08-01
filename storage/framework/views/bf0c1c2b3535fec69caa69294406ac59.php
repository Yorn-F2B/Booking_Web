<?php $__env->startSection('title', 'Sửa tiện ích'); ?>

<?php $__env->startSection('content'); ?>

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('amenities.index')); ?>">Admin</a> / Sửa tiện ích
        </p>

        <div class="admin-page-head">

            <div>
                <h2>Sửa tiện ích</h2>
                <p>Cập nhật thông tin tiện ích</p>
            </div>

        </div>

        <form action="<?php echo e(route('amenities.update', $amenity->id)); ?>" method="POST">

            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <?php if($errors->any()): ?>

                <div class="alert alert-danger">

                    <strong>Có lỗi xảy ra:</strong>

                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>

                </div>

            <?php endif; ?>

            <div class="settings-section">

                <div class="mb-3">

                    <label class="form-label">Tên tiện ích</label>

                    <input type="text"
                        name="name"
                        class="form-control"
                        value="<?php echo e(old('name', $amenity->name)); ?>">

                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <small class="text-danger"><?php echo e($message); ?></small>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                </div>

                <div class="mb-4">

                    <label class="form-label">Icon</label>

                    <input type="text"
                        name="icon"
                        class="form-control"
                        value="<?php echo e(old('icon', $amenity->icon)); ?>"
                        placeholder="Ví dụ: bx bx-wifi, bx bx-swim, bx bx-tv">

                    <small class="text-muted">
                        Dùng class icon của Boxicons, ví dụ: bx bx-wifi
                    </small>

                    <?php $__errorArgs = ['icon'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <small class="text-danger d-block"><?php echo e($message); ?></small>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                    <?php if($amenity->icon): ?>

                        <div class="mt-3">

                            <span class="me-2">Xem trước:</span>

                            <i class="<?php echo e($amenity->icon); ?>" style="font-size: 28px;"></i>

                        </div>

                    <?php endif; ?>

                </div>

                <div class="d-flex gap-2">

                    <button type="submit" class="btn btn-gold">
                        Cập nhật tiện ích
                    </button>

                    <a href="<?php echo e(route('amenities.index')); ?>" class="btn btn-outline-secondary">
                        Quay lại
                    </a>

                </div>

            </div>

        </form>

    </main>

    <footer class="admin-footer">
        <span>MCuong Hotel Admin</span>
    </footer>

</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\amenities\edit.blade.php ENDPATH**/ ?>