<?php $__env->startSection('title', 'Thông báo'); ?>
<?php $__env->startSection('content'); ?>
<div class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h3 mb-1">Thông báo</h1><p class="text-muted mb-0">Các thay đổi quan trọng liên quan đến booking và dịch vụ của bạn.</p></div>
        <?php if($notifications->whereNull('read_at')->count()): ?>
            <form method="POST" action="<?php echo e(route('notifications.read-all')); ?>"><?php echo csrf_field(); ?><button class="btn btn-outline-secondary btn-sm">Đánh dấu đã đọc</button></form>
        <?php endif; ?>
    </div>
    <div class="list-group shadow-sm">
        <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <a href="<?php echo e(route('notifications.open', $notification)); ?>" class="list-group-item list-group-item-action py-3 <?php echo e($notification->read_at ? '' : 'bg-light'); ?>">
                <div class="d-flex justify-content-between gap-3">
                    <div><div class="fw-semibold"><?php echo e($notification->title); ?></div><div class="text-muted small mt-1"><?php echo e($notification->message); ?></div></div>
                    <div class="text-nowrap small text-muted"><?php echo e(optional($notification->created_at)->format('d/m H:i')); ?></div>
                </div>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="list-group-item py-5 text-center text-muted">Chưa có thông báo.</div>
        <?php endif; ?>
    </div>
    <div class="mt-3"><?php echo e($notifications->links()); ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/user/pages/notifications.blade.php ENDPATH**/ ?>