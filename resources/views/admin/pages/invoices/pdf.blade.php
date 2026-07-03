<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn {{ $booking->booking_code }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #333;
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
            color: #333;
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
            background-color: #f5f5f5;
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
            border-top: 2px solid #333;
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
                <p><strong>Họ tên:</strong> {{ $booking->customer->first_name }} {{ $booking->customer->last_name }}</p>
                <p><strong>SĐT:</strong> {{ $booking->customer->phone }}</p>
                <p><strong>Email:</strong> {{ $booking->customer->email ?? '-' }}</p>
                <p><strong>CCCD:</strong> {{ $booking->customer->cccd ?? '-' }}</p>
            </div>
            <div>
                <h3>Thông tin hóa đơn</h3>
                <p><strong>Mã booking:</strong> {{ $booking->booking_code }}</p>
                <p><strong>Ngày tạo:</strong> {{ \Carbon\Carbon::parse($booking->created_at)->format('d/m/Y H:i') }}</p>
                <p><strong>Trạng thái thanh toán:</strong> 
                    @if($booking->payment_status === 'paid')
                        <span class="status paid">Đã thanh toán</span>
                    @elseif($booking->payment_status === 'partial')
                        <span class="status partial">Thanh toán một phần</span>
                    @else
                        <span class="status unpaid">Chưa thanh toán</span>
                    @endif
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
                @foreach($booking->bookingRooms as $bookingRoom)
                    <tr>
                        <td>
                            <strong>Phòng {{ $bookingRoom->room->room_number }}</strong><br>
                            <small>{{ $booking->roomCategory->name }}</small><br>
                            <small>{{ \Carbon\Carbon::parse($booking->check_in_at)->format('d/m/Y H:i') }} - {{ \Carbon\Carbon::parse($booking->check_out_at)->format('d/m/Y H:i') }}</small>
                        </td>
                        <td class="text-right">1</td>
                        <td class="text-right">{{ number_format($bookingRoom->price_at_booking, 0, ',', '.') }}đ</td>
                        <td class="text-right">{{ number_format($bookingRoom->price_at_booking, 0, ',', '.') }}đ</td>
                    </tr>
                @endforeach

                @foreach($booking->serviceItems as $serviceItem)
                    <tr>
                        <td>
                            <strong>{{ $serviceItem->service->name }}</strong>
                            @if($serviceItem->note)
                                <br><small>{{ $serviceItem->note }}</small>
                            @endif
                        </td>
                        <td class="text-right">{{ $serviceItem->quantity }}</td>
                        <td class="text-right">{{ number_format($serviceItem->price, 0, ',', '.') }}đ</td>
                        <td class="text-right">{{ number_format($serviceItem->total, 0, ',', '.') }}đ</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row">
                <span>Tổng tiền phòng:</span>
                <span>{{ number_format($booking->subtotal_amount - $booking->serviceItems->sum('total'), 0, ',', '.') }}đ</span>
            </div>
            @if($booking->serviceItems->count() > 0)
                <div class="total-row">
                    <span>Tổng tiền dịch vụ:</span>
                    <span>{{ number_format($booking->serviceItems->sum('total'), 0, ',', '.') }}đ</span>
                </div>
            @endif
            @if($booking->discount_amount > 0)
                <div class="total-row">
                    <span>Giảm giá:</span>
                    <span>-{{ number_format($Booking->discount_amount, 0, ',', '.') }}đ</span>
                </div>
            @endif
            <div class="total-row grand-total">
                <span>Tổng thanh toán:</span>
                <span>{{ number_format($booking->estimated_total, 0, ',', '.') }}đ</span>
            </div>
            <div class="total-row">
                <span>Đã thanh toán:</span>
                <span>{{ number_format($booking->payments->sum('amount'), 0, ',', '.') }}đ</span>
            </div>
            <div class="total-row" style="font-weight: bold; color: #dc3545;">
                <span>Còn nợ:</span>
                <span>{{ number_format($booking->estimated_total - $booking->payments->sum('amount'), 0, ',', '.') }}đ</span>
            </div>
        </div>

        @if($booking->payments->count() > 0)
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
                    @foreach($booking->payments as $payment)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i') }}</td>
                            <td>
                                @switch($payment->payment_method)
                                    @case('cash')
                                        Tiền mặt
                                        @break
                                    @case('bank_transfer')
                                        Chuyển khoản
                                        @break
                                    @case('vnpay')
                                        VNPay
                                        @break
                                    @default
                                        {{ $payment->payment_method }}
                                @endswitch
                            </td>
                            <td class="text-right">{{ number_format($payment->amount, 0, ',', '.') }}đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="footer">
            <p>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!</p>
            <p>Hóa đơn này được tạo tự động vào ngày {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
            <p>Đây là bản điện tử, không cần chữ ký.</p>
        </div>
    </div>
</body>
</html>
