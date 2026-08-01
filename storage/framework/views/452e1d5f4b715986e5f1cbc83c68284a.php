<?php $__env->startSection('title', 'Chỉnh sửa đánh giá'); ?>

<?php $__env->startSection('content'); ?>
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">Chỉnh sửa đánh giá</h1>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <div class="mb-4">
                <a href="<?php echo e(route('bookings.show', $booking)); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i>
                    Quay lại chi tiết đơn
                </a>
            </div>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Vui lòng kiểm tra lại thông tin đánh giá.</div>
                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm sticky-top" style="top: 90px;">
                        <div class="card-body">
                            <span class="badge <?php echo e($review->status_badge_class); ?> mb-2"><?php echo e($review->status_label); ?></span>
                            <h2 class="h5 fw-bold mb-2"><?php echo e($booking->booking_code); ?></h2>
                            <p class="text-muted small mb-2"><?php echo e($booking->roomCategory->name ?? 'Hạng phòng'); ?></p>
                            <div class="text-warning fs-5 mb-2"><?php echo e($review->star_text); ?></div>
                            <?php if($review->admin_reply): ?>
                                <div class="alert alert-info small mb-0">
                                    <div class="fw-semibold mb-1">Phản hồi khách sạn</div>
                                    <?php echo e($review->admin_reply); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <form action="<?php echo e(route('reviews.update', $review)); ?>" method="POST" class="card border-0 shadow-sm">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>

                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-3">Cập nhật đánh giá</h2>
                            <?php echo $__env->make('user.reviews._form', ['review' => $review], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>

                        <div class="card-footer bg-white border-0 px-4 pb-4 d-flex flex-wrap gap-2 justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>
                                Lưu chỉnh sửa
                            </button>
                        </div>
                    </form>

                    <form action="<?php echo e(route('reviews.destroy', $review)); ?>" method="POST" class="mt-3 text-end"
                        onsubmit="return confirm('Bạn có chắc muốn xóa đánh giá này không?')">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bx bx-trash me-1"></i>
                            Xóa đánh giá
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\user\reviews\edit.blade.php ENDPATH**/ ?>