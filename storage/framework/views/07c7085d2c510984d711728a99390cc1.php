<!doctype html>
<html lang="vi">
<body style="margin:0;background:#f4f5f7;font-family:Arial,sans-serif;color:#1e293b">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 16px">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
    <tr><td style="padding:22px 26px;background:#0a1931;color:#fff;border-bottom:3px solid #d4af37">
        <div style="color:#e4c765;font-size:13px;font-weight:700">MCuong Hotel</div>
        <h1 style="margin:6px 0 0;font-size:22px">Booking đã được hủy</h1>
    </td></tr>
    <tr><td style="padding:26px">
        <p style="margin:0 0 18px;line-height:1.6">Booking <strong><?php echo e($booking->booking_code); ?></strong> đã được hủy thành công.</p>
        <table width="100%" cellpadding="11" cellspacing="0" style="border-collapse:collapse;border:1px solid #e5e9e7;border-radius:10px">
            <tr><td style="color:#66736f">Khách hàng</td><td align="right"><strong><?php echo e($booking->booked_customer_name); ?></strong></td></tr>
            <tr><td style="color:#66736f;border-top:1px solid #edf0ef">Đã thanh toán</td><td align="right" style="border-top:1px solid #edf0ef"><strong><?php echo e(number_format($paidAmount,0,',','.')); ?>đ</strong></td></tr>
            <tr><td style="color:#66736f;border-top:1px solid #edf0ef">Hoàn lại</td><td align="right" style="border-top:1px solid #edf0ef"><strong>0đ</strong></td></tr>
            <tr><td style="color:#66736f;border-top:1px solid #edf0ef">Lý do</td><td align="right" style="border-top:1px solid #edf0ef"><strong><?php echo e($reason); ?></strong></td></tr>
        </table>
        <p style="margin:18px 0 0;color:#8f343a;font-size:13px;line-height:1.6">Khoản đã thanh toán không được hoàn lại hoặc bảo lưu theo chính sách hủy phòng.</p>
    </td></tr>
</table>
</td></tr></table>
</body></html>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\emails\guest-booking-cancelled.blade.php ENDPATH**/ ?>