<?php $__env->startSection('title','Tra cứu và yêu cầu hủy booking'); ?>
<?php $__env->startSection('heading','Tra cứu và yêu cầu hủy booking'); ?>
<?php $__env->startSection('subheading','Tra cứu booking'); ?>
<?php $__env->startSection('content'); ?>
<form method="post" action="<?php echo e(route('guest-bookings.send-otp')); ?>">
    <?php echo csrf_field(); ?>
    <div class="field"><label>Mã booking</label><input class="input" name="booking_code" value="<?php echo e($bookingCode); ?>" placeholder="Ví dụ: BK202607200001" autocomplete="off" required></div>
    <div class="field"><label>Email đặt phòng</label><input class="input" type="email" name="email" value="<?php echo e($email); ?>" placeholder="email@example.com" required></div>
    <button class="btn btn-primary" type="submit">Gửi mã OTP</button>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('guest-bookings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\guest-bookings\lookup.blade.php ENDPATH**/ ?>