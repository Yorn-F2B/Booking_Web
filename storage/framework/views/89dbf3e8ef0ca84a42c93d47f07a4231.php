<?php $__env->startSection('title', 'Đánh giá khách sạn'); ?>

<?php $__env->startSection('content'); ?>
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">Đánh giá khách sạn</h1>
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
                            <span class="badge bg-primary-soft text-primary mb-2"><?php echo e($booking->booking_code); ?></span>
                            <h2 class="h5 fw-bold mb-2"><?php echo e($booking->roomCategory->name ?? 'Hạng phòng'); ?></h2>
                            <p class="small text-muted mb-3">
                                <?php echo e(optional($booking->check_in_at)->format('d/m/Y H:i')); ?>

                                -
                                <?php echo e(optional($booking->check_out_at)->format('d/m/Y H:i')); ?>

                            </p>

                            <?php $__empty_1 = true; $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <div class="border rounded-3 p-2 small mb-2">
                                    Phòng <?php echo e($bookingRoom->room->room_number ?? '---'); ?>

                                    <span class="text-muted">· Tầng <?php echo e($bookingRoom->room->floor_number ?? '---'); ?></span>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="alert alert-light border small mb-0">Không có thông tin phòng cụ thể.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <form action="<?php echo e(route('bookings.reviews.store', $booking)); ?>" method="POST" class="card border-0 shadow-sm" novalidate data-review-submit-form>
                        <?php echo csrf_field(); ?>

                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-3">Bạn đánh giá kỳ lưu trú này thế nào?</h2>
                            <?php echo $__env->make('user.reviews._form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>

                        <div class="card-footer bg-white border-0 px-4 pb-4 d-flex flex-wrap gap-2 justify-content-end">
                            <a href="<?php echo e(route('bookings.show', $booking)); ?>" class="btn btn-outline-secondary">Hủy</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-send me-1"></i>
                                Gửi đánh giá
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-review-submit-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.submitting === '1') {
                    event.preventDefault();
                    return;
                }

                form.dataset.submitting = '1';
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.dataset.originalText = button.innerHTML;
                    button.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Đang lưu...';
                }
            });
        });
    });
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/user/reviews/create.blade.php ENDPATH**/ ?>