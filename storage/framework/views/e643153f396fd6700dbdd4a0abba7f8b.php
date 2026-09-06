<!doctype html>
<html lang="vi">
<body style="margin:0;background:#f4f5f7;font-family:Arial,sans-serif;color:#1e293b">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:32px 16px">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
    <tr><td style="padding:22px 26px;background:#0a1931;color:#fff;border-bottom:3px solid #d4af37">
        <div style="color:#e4c765;font-size:13px;font-weight:700">MCuong Hotel</div>
        <h1 style="margin:6px 0 0;font-size:22px">Thông báo đến muộn</h1>
    </td></tr>
    <tr><td style="padding:26px">
        <p style="margin:0 0 10px;line-height:1.6">Booking <strong><?php echo e($booking->booking_code); ?></strong></p>
        <p style="margin:0 0 20px;color:#53615d;line-height:1.6">Vui lòng cho khách sạn biết giờ dự kiến đến và gửi minh chứng nếu cần.</p>
        <a href="<?php echo e($formUrl); ?>" style="display:inline-block;background:#0a1931;color:#fff;padding:12px 18px;border-radius:7px;text-decoration:none;font-weight:700">Mở biểu mẫu</a>
        <p style="margin:18px 0 0;color:#75817d;font-size:12px">Liên kết hết hạn lúc <?php echo e($expiresAt->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?>.</p>
    </td></tr>
</table>
</td></tr></table>
</body></html>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/emails/customer-request-form.blade.php ENDPATH**/ ?>