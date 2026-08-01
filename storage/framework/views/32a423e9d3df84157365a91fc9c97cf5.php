<?php
    $customerName = $booking->booked_customer_name ?: 'Quý khách';
    $roomNumbers = $booking->bookingRooms
        ->pluck('room.room_number')
        ->filter()
        ->map(fn ($number) => 'Phòng ' . $number)
        ->implode(', ');
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Biểu mẫu báo sự cố phòng</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:28px 12px;">
    <tr>
        <td align="center">
            <table width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
                <tr>
                    <td style="background:#0a1931;color:#fff;padding:22px 24px;border-bottom:3px solid #d4af37;">
                        <div style="font-size:13px;color:#e4c765;font-weight:700;">MCuong Hotel</div>
                        <div style="font-size:24px;font-weight:800;margin-top:6px;">Báo sự cố phòng</div>
                        <div style="font-size:14px;color:#cbd5e1;margin-top:6px;">Booking <strong><?php echo e($booking->booking_code); ?></strong></div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 12px;font-size:15px;line-height:1.6;">Xin chào <strong><?php echo e($customerName); ?></strong>,</p>
                        <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
                            Lễ tân đã gửi biểu mẫu để quý khách báo chi tiết sự cố của từng phòng. Quý khách có thể chọn một hoặc nhiều phòng; mỗi phòng sẽ có phần mô tả và tải ảnh riêng.
                        </p>

                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:15px 16px;margin-bottom:18px;line-height:1.7;">
                            <div><strong>Mã booking:</strong> <?php echo e($booking->booking_code); ?></div>
                            <div><strong>Phòng đang lưu trú:</strong> <?php echo e($roomNumbers ?: 'Chưa xác định'); ?></div>
                            <div><strong>Liên kết có hiệu lực đến:</strong> <?php echo e($expiresAt?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?></div>
                        </div>

                        <div style="text-align:center;margin:22px 0;">
                            <a href="<?php echo e($formUrl); ?>" style="display:inline-block;background:#0a1931;color:#fff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:7px;">Mở biểu mẫu báo sự cố</a>
                        </div>

                        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:13px 15px;color:#7c2d12;font-size:14px;line-height:1.6;">
                            Chỉ chọn các phòng thực sự gặp sự cố. Nếu nhiều phòng bị lỗi khác nhau, vui lòng nhập đúng nội dung và ảnh tương ứng trong từng mục phòng.
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:16px 24px;color:#64748b;font-size:12px;line-height:1.6;">
                        Đây là liên kết bảo mật dành riêng cho booking này. Không chuyển tiếp email cho người không liên quan.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\emails\room-issue-form.blade.php ENDPATH**/ ?>