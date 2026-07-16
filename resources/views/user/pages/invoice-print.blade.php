<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn {{ $invoice->invoice_code }}</title>
    <style>
        * { box-sizing: border-box; }
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .page { margin: 0; box-shadow: none; }
        }
        body {
            margin: 0;
            background: #eef2f7;
            font-family: Arial, sans-serif;
            color: #1f2937;
        }
        .page {
            max-width: 920px;
            margin: 24px auto;
            background: #fff;
            padding: 40px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }
        .actions { text-align: center; margin: 20px 0; }
        .actions a,
        .actions button {
            display: inline-block;
            padding: 10px 24px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-size: 15px;
            text-decoration: none;
            background: #2563eb;
        }
        .actions a { background: #64748b; margin-left: 10px; }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 24px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 24px;
        }
        .header h1 { margin: 0 0 10px; font-size: 30px; color: #0f172a; }
        .header p { margin: 6px 0; color: #475569; }
        .company { max-width: 360px; }
        .meta { min-width: 260px; }
        .meta-row,
        .panel-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 8px;
        }
        .label { font-weight: bold; color: #334155; }
        .value { color: #0f172a; text-align: right; }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .panel {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            background: #f8fafc;
        }
        .panel h3,
        .table-title {
            margin: 0 0 14px;
            font-size: 18px;
            color: #0f172a;
        }
        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .fee-table th {
            background: #eff6ff;
            color: #1e3a8a;
            padding: 12px;
            text-align: left;
            border: 1px solid #dbeafe;
        }
        .fee-table td {
            padding: 12px;
            border: 1px solid #e5e7eb;
        }
        .text-right { text-align: right; }
        .total-row { background: #f8fafc; font-weight: bold; }
        .remaining-row { background: #fef3c7; font-weight: bold; }
        .overpayment-row { background: #dcfce7; font-weight: bold; }
        .notes {
            border-left: 4px solid #2563eb;
            background: #f8fafc;
            padding: 16px;
            margin-bottom: 24px;
        }
        .notes h3 { margin: 0 0 8px; font-size: 16px; }
        .footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #64748b;
        }
    </style>
</head>
<body>
    @php
        $paymentStatusText = $invoice->payment_status_label;
        $resolvedCheckInDate = $invoice->resolved_check_in_date;
        $resolvedCheckOutDate = $invoice->resolved_check_out_date;
        $resolvedActualCheckIn = $invoice->resolved_actual_check_in;
        $resolvedActualCheckOut = $invoice->resolved_actual_check_out;
    @endphp

    <div class="actions no-print">
        <button onclick="window.print()">In hóa đơn</button>
        <a href="{{ route('bookings.invoice', $booking) }}">Quay lại</a>
    </div>

    <div class="page">
        <div class="header">
            <div class="company">
                <h1>HÓA ĐƠN THANH TOÁN</h1>
                <p><strong>MCuong Hotel</strong></p>
                <p>Mã hóa đơn: {{ $invoice->invoice_code }}</p>
                <p>Mã booking: {{ $invoice->booking->booking_code ?? $booking->booking_code }}</p>
            </div>

            <div class="meta">
                <div class="meta-row">
                    <span class="label">Ngày xuất</span>
                    <span class="value">{{ $invoice->issued_at?->format('d/m/Y H:i') ?? '---' }}</span>
                </div>
                <div class="meta-row">
                    <span class="label">Người xuất</span>
                    <span class="value">{{ $invoice->issuer->name ?? $invoice->creator->name ?? 'Hệ thống' }}</span>
                </div>
                <div class="meta-row">
                    <span class="label">Trạng thái</span>
                    <span class="value">{{ $paymentStatusText }}</span>
                </div>
            </div>
        </div>

        <div class="grid">
            <div class="panel">
                <h3>Thông tin khách lưu trú</h3>
                <div class="panel-row">
                    <span class="label">Khách hàng</span>
                    <span class="value">{{ $invoice->resolved_customer_name }}</span>
                </div>
                <div class="panel-row">
                    <span class="label">Phòng</span>
                    <span class="value">{{ $invoice->resolved_room_numbers }}</span>
                </div>
            </div>

            <div class="panel">
                <h3>Thời gian lưu trú</h3>
                <div class="panel-row">
                    <span class="label">Nhận phòng dự kiến</span>
                    <span class="value">{{ $resolvedCheckInDate ? \Carbon\Carbon::parse($resolvedCheckInDate)->format('d/m/Y') : '---' }}</span>
                </div>
                <div class="panel-row">
                    <span class="label">Trả phòng dự kiến</span>
                    <span class="value">{{ $resolvedCheckOutDate ? \Carbon\Carbon::parse($resolvedCheckOutDate)->format('d/m/Y') : '---' }}</span>
                </div>
                <div class="panel-row">
                    <span class="label">Check-in thực tế</span>
                    <span class="value">{{ $resolvedActualCheckIn ? \Carbon\Carbon::parse($resolvedActualCheckIn)->format('d/m/Y H:i') : '---' }}</span>
                </div>
                <div class="panel-row">
                    <span class="label">Check-out thực tế</span>
                    <span class="value">{{ $resolvedActualCheckOut ? \Carbon\Carbon::parse($resolvedActualCheckOut)->format('d/m/Y H:i') : '---' }}</span>
                </div>
            </div>
        </div>

        <h3 class="table-title">Chi tiết thanh toán</h3>
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
                    <td class="text-right">{{ number_format($invoice->resolved_room_charge, 0, ',', '.') }}đ</td>
                </tr>
                @if ($invoice->resolved_service_charge > 0)
                    <tr>
                        <td>Dịch vụ</td>
                        <td class="text-right">{{ number_format($invoice->resolved_service_charge, 0, ',', '.') }}đ</td>
                    </tr>
                @endif
                @if ($invoice->resolved_inspection_charge > 0)
                    <tr>
                        <td>Minibar / hư hại đã duyệt</td>
                        <td class="text-right">{{ number_format($invoice->resolved_inspection_charge, 0, ',', '.') }}đ</td>
                    </tr>
                @endif
                @if ($invoice->resolved_discount_amount > 0)
                    <tr>
                        <td>Khuyến mãi</td>
                        <td class="text-right">-{{ number_format($invoice->resolved_discount_amount, 0, ',', '.') }}đ</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>Tổng cuối</td>
                    <td class="text-right">{{ number_format($invoice->resolved_final_total, 0, ',', '.') }}đ</td>
                </tr>
                <tr>
                    <td>Đã thanh toán</td>
                    <td class="text-right">{{ number_format($invoice->resolved_total_paid, 0, ',', '.') }}đ</td>
                </tr>
                @if ($invoice->resolved_remaining_amount > 0)
                    <tr class="remaining-row">
                        <td>Còn thiếu</td>
                        <td class="text-right">{{ number_format($invoice->resolved_remaining_amount, 0, ',', '.') }}đ</td>
                    </tr>
                @endif
                @if ($invoice->resolved_overpayment_amount > 0)
                    <tr class="overpayment-row">
                        <td>Trả dư</td>
                        <td class="text-right">{{ number_format($invoice->resolved_overpayment_amount, 0, ',', '.') }}đ</td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if ($invoice->notes)
            <div class="notes">
                <h3>Ghi chú</h3>
                <div>{{ $invoice->notes }}</div>
            </div>
        @endif

        <div class="footer">
            Cảm ơn quý khách đã lưu trú tại MCuong Hotel.
        </div>
    </div>
</body>
</html>
