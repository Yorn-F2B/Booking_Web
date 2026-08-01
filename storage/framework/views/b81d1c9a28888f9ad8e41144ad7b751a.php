<?php
    $customerName = $booking->booked_customer_name !== ''
        ? $booking->booked_customer_name
        : 'Quý khách';

    $paymentTypeLabel = $payment->payment_type === 'deposit_30'
        ? 'Thanh toán cọc giữ phòng 30%'
        : 'Thanh toán số tiền còn lại';

    $hotelName = config('mail.from.name') ?: config('app.name', 'MCuong Hotel');
    $amountText = number_format((float) $payment->amount, 0, ',', '.') . 'đ';
    $checkInText = $booking->check_in_at
        ? \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
        : '---';
    $checkOutText = $booking->check_out_at
        ? \Carbon\Carbon::parse($booking->check_out_at, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
        : '---';
    $expiresText = $expiresAt
        ? \Carbon\Carbon::parse($expiresAt, 'Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
        : '---';
?>

<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yêu cầu thanh toán VNPay</title>
</head>
<body style="margin:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <div style="max-width:640px;margin:0 auto;padding:24px 14px;">
        <div style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
            <div style="padding:22px 24px;background:#0a1931;color:#ffffff;border-bottom:3px solid #d4af37;">
                <div style="font-size:13px;color:#e4c765;font-weight:700;">
                    <?php echo e($hotelName); ?>

                </div>
                <h1 style="margin:8px 0 0;font-size:22px;line-height:1.3;">
                    Yêu cầu thanh toán VNPay
                </h1>
            </div>

            <div style="padding:24px;">
                <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">
                    Xin chào <strong><?php echo e($customerName); ?></strong>,
                </p>

                <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
                    <?php echo e($hotelName); ?> gửi yêu cầu thanh toán cho booking của quý khách.
                </p>

                <div style="border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;margin-bottom:18px;">
                    <table style="width:100%;border-collapse:collapse;font-size:14px;">
                        <tr>
                            <td style="padding:11px 14px;color:#64748b;border-bottom:1px solid #eef2f7;">Mã booking</td>
                            <td style="padding:11px 14px;text-align:right;font-weight:700;border-bottom:1px solid #eef2f7;"><?php echo e($booking->booking_code); ?></td>
                        </tr>
                        <tr>
                            <td style="padding:11px 14px;color:#64748b;border-bottom:1px solid #eef2f7;">Mã giao dịch VNPay</td>
                            <td style="padding:11px 14px;text-align:right;font-weight:700;border-bottom:1px solid #eef2f7;"><?php echo e($payment->txn_ref); ?></td>
                        </tr>
                        <tr>
                            <td style="padding:11px 14px;color:#64748b;border-bottom:1px solid #eef2f7;">Nội dung</td>
                            <td style="padding:11px 14px;text-align:right;font-weight:700;border-bottom:1px solid #eef2f7;"><?php echo e($paymentTypeLabel); ?></td>
                        </tr>
                        <tr>
                            <td style="padding:11px 14px;color:#64748b;border-bottom:1px solid #eef2f7;">Số tiền cần thanh toán</td>
                            <td style="padding:11px 14px;text-align:right;font-weight:800;color:#dc2626;font-size:18px;border-bottom:1px solid #eef2f7;"><?php echo e($amountText); ?></td>
                        </tr>
                        <tr>
                            <td style="padding:11px 14px;color:#64748b;border-bottom:1px solid #eef2f7;">Đơn vị nhận thanh toán</td>
                            <td style="padding:11px 14px;text-align:right;font-weight:700;border-bottom:1px solid #eef2f7;"><?php echo e($hotelName); ?> qua cổng VNPay</td>
                        </tr>
                        <tr>
                            <td style="padding:11px 14px;color:#64748b;border-bottom:1px solid #eef2f7;">Thời gian lưu trú</td>
                            <td style="padding:11px 14px;text-align:right;font-weight:700;border-bottom:1px solid #eef2f7;"><?php echo e($checkInText); ?> → <?php echo e($checkOutText); ?></td>
                        </tr>
                        <tr>
                            <td style="padding:11px 14px;color:#64748b;">Hiệu lực thanh toán</td>
                            <td style="padding:11px 14px;text-align:right;font-weight:700;">Đến <?php echo e($expiresText); ?></td>
                        </tr>
                    </table>
                </div>

                <div style="text-align:center;margin:24px 0;">
                    <a href="<?php echo e($paymentUrl); ?>" target="_blank"
                        style="display:inline-block;background:#0a1931;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:7px;font-weight:700;font-size:14px;">
                        Mở thanh toán VNPay
                    </a>
                </div>

                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:13px 14px;font-size:13px;line-height:1.6;color:#475569;">
                    Nếu nút trên không mở được, quý khách có thể sao chép đường dẫn sau và mở trên trình duyệt:<br>
                    <span style="word-break:break-all;color:#1d4ed8;"><?php echo e($paymentUrl); ?></span>
                </div>

                <p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                    Vui lòng không thanh toán lại nếu giao dịch đã thành công.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\emails\admin-vnpay-payment-request.blade.php ENDPATH**/ ?>