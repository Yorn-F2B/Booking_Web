<?php $__env->startSection('title','Xác thực OTP'); ?>
<?php $__env->startSection('heading','Nhập mã OTP'); ?>
<?php $__env->startSection('subheading','Mã gồm 6 chữ số đã được gửi tới email của booking.'); ?>
<?php $__env->startSection('content'); ?>
<form method="post" action="<?php echo e(route('guest-bookings.verify')); ?>">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="booking_code" value="<?php echo e($bookingCode); ?>">
    <input type="hidden" name="email" value="<?php echo e($email); ?>">
    <div class="field"><label>Mã booking</label><input class="input" value="<?php echo e($bookingCode); ?>" disabled></div>
    <div class="field"><label>Mã OTP</label><input class="input" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" autocomplete="one-time-code" autofocus required></div>
    <div class="actions"><button class="btn btn-primary" type="submit">Xác thực và xem booking</button><a class="btn btn-light" href="<?php echo e(route('guest-bookings.index')); ?>">Tra cứu lại</a></div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('guest-bookings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\guest-bookings\verify.blade.php ENDPATH**/ ?>