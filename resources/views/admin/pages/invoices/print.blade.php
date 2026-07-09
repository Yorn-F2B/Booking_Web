<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn {{ $invoice->invoice_code }}</title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 20px;
                font-family: 'Times New Roman', serif;
            }
            .no-print {
                display: none !important;
            }
            .invoice-container {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
            }
        }
        
        body {
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .invoice-header h1 {
            margin: 0;
            color: #333;
            font-size: 28px;
        }
        
        .invoice-header p {
            margin: 5px 0;
            color: #666;
        }
        
        .invoice-info {
            margin-bottom: 30px;
        }
        
        .invoice-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .invoice-info-label {
            font-weight: bold;
            color: #333;
        }
        
        .invoice-info-value {
            color: #666;
        }
        
        .customer-info {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }
        
        .customer-info h3 {
            margin-top: 0;
            color: #333;
        }
        
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .fee-table th {
            background-color: #007bff;
            color: white;
            padding: 12px;
            text-align: left;
            border: 1px solid #007bff;
        }
        
        .fee-table td {
            padding: 12px;
            border: 1px solid #ddd;
        }
        
        .fee-table .text-right {
            text-align: right;
        }
        
        .fee-table .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .fee-table .remaining-row {
            background-color: #fff3cd;
            font-weight: bold;
        }
        
        .notes-section {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 30px;
            border-left: 4px solid #28a745;
        }
        
        .notes-section h3 {
            margin-top: 0;
            color: #333;
        }
        
        .invoice-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
        }
        
        .print-btn {
            text-align: center;
            margin: 20px 0;
        }
        
        .print-btn button {
            padding: 10px 30px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .print-btn button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="print-btn no-print">
        <button onclick="window.print()">In hóa đơn</button>
        <button onclick="window.close()" style="background-color: #6c757d; margin-left: 10px;">Đóng</button>
    </div>
    
    <div class="invoice-container">
        <div class="invoice-header">
            <h1>HÓA ĐƠN THANH TOÁN</h1>
            <p><strong>Mã hóa đơn:</strong> {{ $invoice->invoice_code }}</p>
            <p><strong>Ngày xuất:</strong> {{ $invoice->issued_at ? \Carbon\Carbon::parse($invoice->issued_at)->format('d/m/Y H:i') : '---' }}</p>
        </div>
        
        <div class="invoice-info">
            <div class="invoice-info-row">
                <span class="invoice-info-label">Người xuất:</span>
                <span class="invoice-info-value">{{ $invoice->creator->name ?? '---' }}</span>
            </div>
        </div>
        
        <div class="customer-info">
            <h3>Thông tin khách hàng</h3>
            <p><strong>Tên khách:</strong> {{ $invoice->customer_name }}</p>
            <p><strong>Mã booking:</strong> {{ $invoice->booking->booking_code ?? '---' }}</p>
            <p><strong>Phòng:</strong> {{ $invoice->room_numbers }}</p>
            <p><strong>Trạng thái thanh toán:</strong> {{ $invoice->payment_status_label }}</p>
        </div>
        
        <div class="customer-info" style="border-left-color: #28a745;">
            <h3>Thời gian lưu trú</h3>
            <p><strong>Ngày nhận phòng (dự kiến):</strong> {{ \Carbon\Carbon::parse($invoice->check_in_date)->format('d/m/Y') }}</p>
            <p><strong>Ngày trả phòng (dự kiến):</strong> {{ \Carbon\Carbon::parse($invoice->check_out_date)->format('d/m/Y') }}</p>
            <p><strong>Check-in thực tế:</strong> {{ $invoice->actual_check_in ? \Carbon\Carbon::parse($invoice->actual_check_in)->format('d/m/Y H:i') : '---' }}</p>
            <p><strong>Check-out thực tế:</strong> {{ $invoice->actual_check_out ? \Carbon\Carbon::parse($invoice->actual_check_out)->format('d/m/Y H:i') : '---' }}</p>
        </div>
        
        <h3>Chi tiết phí</h3>
        <table class="fee-table">
            <thead>
                <tr>
                    <th>Khoản mục</th>
                    <th class="text-right">Số tiền</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tiền phòng</td>
                    <td class="text-right">{{ number_format($invoice->room_charge, 0, ',', '.') }}đ</td>
                </tr>
                <tr>
                    <td>Tiền dịch vụ</td>
                    <td class="text-right">{{ number_format($invoice->service_charge, 0, ',', '.') }}đ</td>
                </tr>
                <tr>
                    <td>Tiền minibar</td>
                    <td class="text-right">{{ number_format($invoice->minibar_charge, 0, ',', '.') }}đ</td>
                </tr>
                <tr>
                    <td>Phụ thu (khách thừa, check-in sớm, check-out muộn, v.v.)</td>
                    <td class="text-right">{{ number_format($invoice->extra_charge, 0, ',', '.') }}đ</td>
                </tr>
                <tr>
                    <td>Tiền hư hại (nếu có)</td>
                    <td class="text-right">{{ number_format($invoice->damage_fee, 0, ',', '.') }}đ</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Tổng cộng</strong></td>
                    <td class="text-right"><strong>{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</strong></td>
                </tr>
                <tr class="total-row" style="background-color: #d4edda;">
                    <td>Tiền cọc đã thanh toán</td>
                    <td class="text-right">-{{ number_format($invoice->deposit_amount, 0, ',', '.') }}đ</td>
                </tr>
                <tr class="remaining-row">
                    <td><strong>Số tiền còn lại cần thanh toán</strong></td>
                    <td class="text-right"><strong>{{ number_format($invoice->remaining_amount, 0, ',', '.') }}đ</strong></td>
                </tr>
            </tbody>
        </table>
        
        @if($invoice->notes)
        <div class="notes-section">
            <h3>Ghi chú</h3>
            <p>{{ $invoice->notes }}</p>
        </div>
        @endif
        
        <div class="invoice-footer">
            <p>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!</p>
            <p>Để biết thêm thông tin, vui lòng liên hệ lễ tân.</p>
        </div>
    </div>
    
    <div class="print-btn no-print">
        <button onclick="window.print()">In hóa đơn</button>
        <button onclick="window.close()" style="background-color: #6c757d; margin-left: 10px;">Đóng</button>
    </div>
</body>
</html>