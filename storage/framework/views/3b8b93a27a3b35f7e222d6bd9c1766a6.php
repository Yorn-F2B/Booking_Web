<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn <?php echo e($booking->booking_code); ?></title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #18221f;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #d4af37;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #18221f;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .invoice-info div {
            flex: 1;
        }
        .invoice-info h3 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #18221f;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th,
        .table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background-color: #f3f7f5;
            font-weight: bold;
        }
        .table .text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .total-row.grand-total {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #d4af37;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
        }
        .status {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .status.paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status.partial {
            background-color: #fff3cd;
            color: #856404;
        }
        .status.unpaid {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>HÓA ĐƠN ĐẶT PHÒNG</h1>
            <p>Khách sạn Booking Web</p>
            <p>Địa chỉ: 123 Đường ABC, Quận 1, TP.HCM</p>
            <p>Điện thoại: (028) 1234 5678 | Email: info@bookingweb.com</p>
        </div>

        <div class="invoice-info">
            <div>
                <h3>Thông tin khách hàng</h3>
                <p><strong>Họ tên:</strong> <?php echo e($booking->booked_customer_name ?: '-'); ?></p>
                <p><strong>SĐT:</strong> <?php echo e($booking->booked_customer_phone ?? '-'); ?></p>
                <p><strong>Email:</strong> <?php echo e($booking->booked_customer_email ?? '-'); ?></p>
                <p><strong>CCCD:</strong> <?php echo e($booking->booked_customer_cccd ?? '-'); ?></p>
            </div>
            <div>
                <h3>Thông tin hóa đơn</h3>
                <p><strong>Mã booking:</strong> <?php echo e($booking->booking_code); ?></p>
                <p><strong>Ngày tạo:</strong> <?php echo e(\Carbon\Carbon::parse($booking->created_at)->format('d/m/Y H:i')); ?></p>
                <p><strong>Trạng thái thanh toán:</strong> 
                    <?php if($booking->payment_status === 'paid'): ?>
                        <span class="status paid">Đã thanh toán</span>
                    <?php elseif($booking->payment_status === 'partial'): ?>
                        <span class="status partial">Thanh toán một phần</span>
                    <?php else: ?>
                        <span class="status unpaid">Chưa thanh toán</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <h3>Chi tiết đặt phòng</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Mô tả</th>
                    <th class="text-right">Số lượng</th>
                    <th class="text-right">Đơn giá</th>
                    <th class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td>
                            <strong>Phòng <?php echo e($bookingRoom->room->room_number); ?></strong><br>
                            <small><?php echo e($booking->roomCategory->name); ?></small><br>
                            <small><?php echo e(\Carbon\Carbon::parse($booking->check_in_at)->format('d/m/Y H:i')); ?> - <?php echo e(\Carbon\Carbon::parse($booking->check_out_at)->format('d/m/Y H:i')); ?></small>
                        </td>
                        <td class="text-right">1</td>
                        <td class="text-right"><?php echo e(number_format($bookingRoom->price_at_booking, 0, ',', '.')); ?>đ</td>
                        <td class="text-right"><?php echo e(number_format($bookingRoom->price_at_booking, 0, ',', '.')); ?>đ</td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php $__currentLoopData = $booking->serviceItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td>
                            <strong><?php echo e($serviceItem->service->name); ?></strong>
                            <?php if($serviceItem->note): ?>
                                <br><small><?php echo e($serviceItem->note); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><?php echo e($serviceItem->quantity); ?></td>
                        <td class="text-right"><?php echo e(number_format($serviceItem->price, 0, ',', '.')); ?>đ</td>
                        <td class="text-right"><?php echo e(number_format($serviceItem->total, 0, ',', '.')); ?>đ</td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row">
                <span>Tổng tiền phòng:</span>
                <span><?php echo e(number_format($booking->subtotal_amount - $booking->serviceItems->sum('total'), 0, ',', '.')); ?>đ</span>
            </div>
            <?php if($booking->serviceItems->count() > 0): ?>
                <div class="total-row">
                    <span>Tổng tiền dịch vụ:</span>
                    <span><?php echo e(number_format($booking->serviceItems->sum('total'), 0, ',', '.')); ?>đ</span>
                </div>
            <?php endif; ?>
            <?php if($booking->discount_amount > 0): ?>
                <div class="total-row">
                    <span>Giảm giá:</span>
                    <span>-<?php echo e(number_format($Booking->discount_amount, 0, ',', '.')); ?>đ</span>
                </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <span>Tổng thanh toán:</span>
                <span><?php echo e(number_format($booking->estimated_total, 0, ',', '.')); ?>đ</span>
            </div>
            <div class="total-row">
                <span>Đã thanh toán:</span>
                <span><?php echo e(number_format($booking->payments->sum('amount'), 0, ',', '.')); ?>đ</span>
            </div>
            <div class="total-row" style="font-weight: bold; color: #dc3545;">
                <span>Còn nợ:</span>
                <span><?php echo e(number_format($booking->estimated_total - $booking->payments->sum('amount'), 0, ',', '.')); ?>đ</span>
            </div>
        </div>

        <?php if($booking->payments->count() > 0): ?>
            <h3 style="margin-top: 30px;">Lịch sử thanh toán</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Ngày thanh toán</th>
                        <th>Phương thức</th>
                        <th class="text-right">Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $booking->payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e(\Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i')); ?></td>
                            <td>
                                <?php switch($payment->payment_method):
                                    case ('cash'): ?>
                                        Tiền mặt
                                        <?php break; ?>
                                    <?php case ('bank_transfer'): ?>
                                        Chuyển khoản
                                        <?php break; ?>
                                    <?php case ('vnpay'): ?>
                                        VNPay
                                        <?php break; ?>
                                    <?php default: ?>
                                        <?php echo e($payment->payment_method); ?>

                                <?php endswitch; ?>
                            </td>
                            <td class="text-right"><?php echo e(number_format($payment->amount, 0, ',', '.')); ?>đ</td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="footer">
            <p>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!</p>
            <p><?php echo e(\Carbon\Carbon::now()->format('d/m/Y H:i')); ?></p>
            <p>Đây là bản điện tử, không cần chữ ký.</p>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\invoices\pdf.blade.php ENDPATH**/ ?>