<?php $__env->startSection('title','Đã hủy booking'); ?>
<?php $__env->startSection('heading','Đã hủy booking thành công'); ?>
<?php $__env->startSection('subheading','Phòng đã được giải phóng và email xác nhận hủy đã được gửi tới khách hàng.'); ?>
<?php $__env->startSection('content'); ?>
<div class="alert alert-success">Booking <strong><?php echo e($bookingCode); ?></strong> đã được hủy. Toàn bộ tiền đã thanh toán được giữ lại theo chính sách không hoàn tiền và không bảo lưu.</div>
<a class="btn btn-light" href="<?php echo e(route('guest-bookings.index')); ?>">Tra cứu booking khác</a>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('guest-bookings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\guest-bookings\cancelled.blade.php ENDPATH**/ ?>