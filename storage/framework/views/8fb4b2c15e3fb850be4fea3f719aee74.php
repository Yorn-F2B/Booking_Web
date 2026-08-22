<?php $__env->startSection('title', 'Chi tiết đặt phòng'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $workspaceMode = 'main';
?>

<?php echo $__env->make('admin.pages.bookings._workspace', ['workspaceMode' => $workspaceMode], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/bookings/show.blade.php ENDPATH**/ ?>