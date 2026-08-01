<?php $__env->startSection('title', 'Cập nhật đặt phòng'); ?>

<?php $__env->startSection('content'); ?>

    <script>
        window.location.href = "<?php echo e(route('admin.bookings.show', $booking->id)); ?>";
    </script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\bookings\edit.blade.php ENDPATH**/ ?>