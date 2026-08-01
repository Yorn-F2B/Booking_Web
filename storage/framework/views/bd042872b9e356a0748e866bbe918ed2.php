<?php $__env->startSection('title', 'Chi tiết yêu cầu đến muộn'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $statusLabel = match ($customerRequest->status) {
        'approved' => 'Đã duyệt',
        'rejected' => 'Đã từ chối',
        default => 'Đang xử lý',
    };
    $statusClass = match ($customerRequest->status) {
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        default => 'bg-warning text-dark',
    };
?>
<div class="container py-4">
    <div class="card shadow-sm border-0 mx-auto" style="max-width: 900px;">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                <h2 class="h4 mb-0">Chi tiết yêu cầu đến muộn</h2>
                <span class="badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
            </div>

            <dl class="row mb-4">
                <dt class="col-sm-4">Giờ dự kiến đến</dt>
                <dd class="col-sm-8"><?php echo e(optional($customerRequest->expected_arrival_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?></dd>
                <dt class="col-sm-4">Lý do</dt>
                <dd class="col-sm-8"><?php echo e($customerRequest->reason); ?></dd>
                <?php if($customerRequest->admin_note): ?>
                    <dt class="col-sm-4">Phản hồi khách sạn</dt>
                    <dd class="col-sm-8"><?php echo e($customerRequest->admin_note); ?></dd>
                <?php endif; ?>
            </dl>

            <?php if($customerRequest->attachments->isNotEmpty()): ?>
                <div class="row g-3 mb-4">
                    <?php $__currentLoopData = $customerRequest->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-6 col-md-4">
                            <?php if(str_starts_with((string) $attachment->mime_type, 'image/')): ?>
                                <a href="<?php echo e(route('bookings.customer-requests.attachment', [$booking, $attachment])); ?>" target="_blank">
                                    <img src="<?php echo e(route('bookings.customer-requests.attachment', [$booking, $attachment])); ?>" class="img-fluid rounded border" alt="<?php echo e($attachment->original_name); ?>" style="height:150px;width:100%;object-fit:cover;">
                                </a>
                            <?php else: ?>
                                <a class="btn btn-outline-secondary w-100" href="<?php echo e(route('bookings.customer-requests.attachment', [$booking, $attachment])); ?>" target="_blank"><?php echo e($attachment->original_name); ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <a href="<?php echo e(route('bookings.show', $booking)); ?>" class="btn btn-outline-secondary">Quay lại đơn phòng</a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\user\customer-requests\show.blade.php ENDPATH**/ ?>