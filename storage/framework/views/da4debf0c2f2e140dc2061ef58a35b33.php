<?php $__env->startSection('title','Thông tin booking '.$booking->booking_code); ?>
<?php $__env->startSection('heading','Thông tin booking'); ?>
<?php $__env->startSection('subheading','Phiên xác thực có hiệu lực trong thời gian ngắn. Không chia sẻ trang này cho người khác.'); ?>
<?php $__env->startSection('content'); ?>
<?php
$statusLabels=['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','checked_in'=>'Đang ở','inspection_requested'=>'Chờ kiểm tra phòng','checked_out'=>'Đã trả phòng','completed'=>'Hoàn tất','cancelled'=>'Đã hủy'];
?>
<div class="grid">
    <div class="info"><small>Mã booking</small><strong><?php echo e($booking->booking_code); ?></strong></div>
    <div class="info"><small>Trạng thái</small><strong><?php echo e($statusLabels[$booking->status] ?? $booking->status); ?></strong></div>
    <div class="info"><small>Khách hàng</small><strong><?php echo e($booking->booked_customer_name); ?></strong></div>
    <div class="info"><small>Hạng phòng</small><strong><?php echo e($booking->roomCategory->name ?? '---'); ?></strong></div>
    <div class="info"><small>Nhận phòng</small><strong><?php echo e(optional($booking->check_in_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')); ?></strong></div>
    <div class="info"><small>Trả phòng</small><strong><?php echo e(optional($booking->check_out_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')); ?></strong></div>
    <div class="info"><small>Tổng dự kiến</small><strong><?php echo e(number_format((float)$booking->estimated_total,0,',','.')); ?>đ</strong></div>
    <div class="info"><small>Đã thanh toán</small><strong><?php echo e(number_format($paidAmount,0,',','.')); ?>đ</strong></div>
</div>

<?php if($booking->status === 'cancelled'): ?>
    <div class="alert alert-error" style="margin-top:18px;margin-bottom:0">Booking này đã được hủy. Tiền đã thanh toán không được hoàn lại hoặc bảo lưu.</div>
<?php elseif($canCancel): ?>
    <div class="warning" style="margin-top:18px"><strong>Chính sách hủy:</strong> Hủy booking sẽ mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu. Phòng sẽ được mở bán lại ngay sau khi xác nhận hủy.</div>
    <form method="post" action="<?php echo e(route('guest-bookings.cancel',['token'=>$token])); ?>" style="margin-top:18px" onsubmit="return confirm('Bạn chắc chắn muốn hủy booking? Thao tác này không thể hoàn tác.');">
        <?php echo csrf_field(); ?>
        <div class="field"><label>Lý do hủy</label><textarea class="textarea" name="reason" placeholder="Ví dụ: thay đổi kế hoạch, nhập nhầm ngày..." required><?php echo e(old('reason')); ?></textarea></div>
        <label class="checkbox"><input type="checkbox" name="confirm_forfeit" value="1" required><span>Tôi xác nhận đã hiểu toàn bộ số tiền <?php echo e(number_format($paidAmount,0,',','.')); ?>đ đã thanh toán sẽ không được hoàn lại và booking không thể tự khôi phục sau khi hủy.</span></label>
        <button class="btn btn-danger" type="submit">Xác nhận hủy booking</button>
    </form>
<?php else: ?>
    <div class="warning" style="margin-top:18px">Booking hiện không thể tự hủy qua trang tra cứu. Vui lòng liên hệ lễ tân nếu cần hỗ trợ.</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('guest-bookings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\guest-bookings\show.blade.php ENDPATH**/ ?>