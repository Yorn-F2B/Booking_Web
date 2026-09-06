<?php $__env->startSection('content'); ?>
<div class="container py-5" style="max-width:760px">
    <div class="alert alert-success">
        <h4>Đã gửi thông tin đến muộn</h4>
        <p class="mb-0">Booking <strong><?php echo e($booking->booking_code); ?></strong> đã ghi nhận lý do và giờ dự kiến đến. Khách sạn sẽ phản hồi sau khi quản lý xem xét.</p>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('guest-bookings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/guest-customer-requests/done.blade.php ENDPATH**/ ?>