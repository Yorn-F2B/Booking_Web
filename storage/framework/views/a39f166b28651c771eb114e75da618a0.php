<?php $__env->startSection('title', 'Chi tiết loại phòng'); ?>

<?php $__env->startSection('content'); ?>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('room-categories.index')); ?>">
                    Admin
                </a>

                / Chi tiết loại phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Chi tiết loại phòng</h2>
                    <p>Thông tin chi tiết hạng phòng khách sạn</p>
                </div>

                <a href="<?php echo e(route('room-categories.edit', $roomCategory->id)); ?>" class="btn btn-gold">
                    <i class="bx bx-edit me-1"></i>
                    Chỉnh sửa
                </a>

            </div>

            <div class="settings-section">

                <div class="row">

                    <div class="col-lg-5">

                        <?php if($roomCategory->thumbnail): ?>

                            <img src="<?php echo e(asset('storage/' . $roomCategory->thumbnail)); ?>" class="w-100" style="
                                        height: 320px;
                                        object-fit: cover;
                                        border-radius: 14px;
                                    ">

                        <?php elseif($roomCategory->images->count()): ?>

                            <img src="<?php echo e(asset('storage/' . $roomCategory->images->first()->image)); ?>" class="w-100" style="
                                        height: 320px;
                                        object-fit: cover;
                                        border-radius: 14px;
                                    ">

                        <?php else: ?>

                            <div class="d-flex align-items-center justify-content-center bg-light" style="
                                        height: 320px;
                                        border-radius: 14px;
                                    ">

                                <span class="text-muted">
                                    Chưa có ảnh
                                </span>

                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="col-lg-7">

                        <div class="row">

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Tên loại phòng
                                </label>

                                <h5>
                                    <?php echo e($roomCategory->name); ?>

                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Giá phòng
                                </label>

                                <h5>
                                    <?php echo e(number_format($roomCategory->price, 0, ',', '.')); ?>đ
                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Người lớn tối đa
                                </label>

                                <h5>
                                    <?php echo e($roomCategory->adult_capacity); ?> người
                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Trẻ em tối đa
                                </label>

                                <h5>
                                    <?php echo e($roomCategory->child_capacity); ?> trẻ em
                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Diện tích
                                </label>

                                <h5>
                                    <?php echo e($roomCategory->area); ?> m²
                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Số giường
                                </label>

                                <h5>
                                    <?php echo e($roomCategory->bed_count); ?> giường
                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Trạng thái
                                </label>

                                <div>

                                    <?php if($roomCategory->status == 'active'): ?>

                                        <span class="badge bg-success">
                                            Đang hoạt động
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            Tạm ẩn
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="col-md-12 mb-4">

                                <label class="text-muted small">
                                    Tiện ích
                                </label>

                                <div class="d-flex flex-wrap gap-2 mt-2">

                                    <?php $__empty_1 = true; $__currentLoopData = $roomCategory->amenities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amenity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                                        <span class="badge bg-light text-dark border d-flex align-items-center gap-1 px-3 py-2">

                                            <?php if($amenity->icon): ?>

                                                <i class="<?php echo e($amenity->icon); ?>"></i>

                                            <?php endif; ?>

                                            <?php echo e($amenity->name); ?>


                                        </span>

                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                                        <span class="text-muted">
                                            Chưa có tiện ích
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <hr class="my-4">

                <div>

                    <label class="text-muted small mb-2">
                        Mô tả loại phòng
                    </label>

                    <div class="lh-lg">

                        <?php echo e($roomCategory->description ?: 'Chưa có mô tả'); ?>


                    </div>

                </div>

                <?php if($roomCategory->images->count()): ?>

                    <hr class="my-4">

                    <div>

                        <h5 class="mb-3">
                            Album ảnh
                        </h5>

                        <div class="row g-3">

                            <?php $__currentLoopData = $roomCategory->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

                                <div class="col-md-3">

                                    <img src="<?php echo e(asset('storage/' . $image->image)); ?>" class="w-100" style="
                                                                height: 180px;
                                                                object-fit: cover;
                                                                border-radius: 12px;
                                                            ">

                                </div>

                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        </div>

                    </div>

                <?php endif; ?>

                <div class="mt-4 d-flex gap-2">

                    <a href="<?php echo e(route('room-categories.edit', $roomCategory->id)); ?>" class="btn btn-primary">
                        Chỉnh sửa
                    </a>

                    <a href="<?php echo e(route('room-categories.index')); ?>" class="btn btn-outline-secondary">
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
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\room-categories\show.blade.php ENDPATH**/ ?>