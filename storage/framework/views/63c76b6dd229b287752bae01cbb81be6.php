<?php if(config('account_restrictions.enabled', false)): ?>
<?php
    $accountUser = auth()->user();
    $isBanned = $accountUser && ($accountUser->status ?? 'active') === 'banned';
    $isLocked = $accountUser && $accountUser->booking_locked_until && now()->lt($accountUser->booking_locked_until);
?>
<?php if($isBanned): ?>
<div class="alert alert-danger border-danger mb-4">
    <strong><i class="bx bx-error-circle"></i> Tài khoản đang bị cấm đặt phòng.</strong>
    <div class="mt-1">Bạn vẫn có thể đăng nhập và xem thông tin, nhưng không thể tạo booking mới.</div>
    <?php if($accountUser->booking_lock_reason): ?><div class="small mt-1">Lý do: <?php echo e($accountUser->booking_lock_reason); ?></div><?php endif; ?>
</div>
<?php endif; ?>
<?php if($isLocked): ?>
<div class="alert alert-warning border-warning mb-4" data-account-countdown="<?php echo e($accountUser->booking_locked_until->toIso8601String()); ?>">
    <strong>Tài khoản đang bị khóa tạm thời.</strong>
    <div>Khóa đến: <?php echo e($accountUser->booking_locked_until->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')); ?></div>
    <div>Thời gian còn lại: <b data-countdown-output>Đang tính...</b></div>
    <?php if($accountUser->booking_lock_reason): ?><div class="small">Lý do: <?php echo e($accountUser->booking_lock_reason); ?></div><?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
 document.querySelectorAll('[data-account-countdown]').forEach(function(box){
  const end = new Date(box.dataset.accountCountdown).getTime(), out = box.querySelector('[data-countdown-output]');
  const tick=()=>{let d=Math.max(0,end-Date.now()),days=Math.floor(d/86400000);d%=86400000;let h=Math.floor(d/3600000);d%=3600000;let m=Math.floor(d/60000);let s=Math.floor((d%60000)/1000);out.textContent=`${days} ngày ${h} giờ ${m} phút ${s} giây`;}; tick(); setInterval(tick,1000);
 });
});
</script>
<?php endif; ?>

<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views/user/partials/account-restriction.blade.php ENDPATH**/ ?>