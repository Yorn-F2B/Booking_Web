<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn {{ $booking->booking_code }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; line-height: 1.5; color: #18221f; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #d4af37; padding-bottom: 16px; }
        .header h1 { font-size: 24px; margin: 0; }
        .header h2 { font-size: 16px; margin: 6px 0 0; }
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { width: 50%; vertical-align: top; padding: 0 14px 0 0; }
        .info-table h3 { margin: 0 0 8px; font-size: 14px; }
        .info-table p { margin: 3px 0; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .table th, .table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: top; }
        .table th { background-color: #f3f7f5; font-weight: bold; }
        .text-right { text-align: right !important; }
        .muted { color: #666; font-size: 10px; }
        .summary { width: 55%; margin-left: auto; border-collapse: collapse; }
        .summary td { padding: 5px 0 5px 10px; }
        .summary .grand td { font-weight: bold; font-size: 14px; border-top: 2px solid #d4af37; padding-top: 9px; }
        .summary .remaining td { font-weight: bold; }
        .status { padding: 3px 7px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .status.paid { background-color: #d4edda; color: #155724; }
        .status.partial { background-color: #fff3cd; color: #856404; }
        .status.unpaid { background-color: #f8d7da; color: #721c24; }
        .footer { margin-top: 35px; padding-top: 15px; border-top: 1px solid #ddd; text-align: center; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>HÓA ĐƠN KHÁCH SẠN</h1>
        <h2>{{ strtoupper(config('app.name', 'MCuong Hotel')) }}</h2>
    </div>

    <table class="info-table">
        <tr>
            <td>
                <h3>Thông tin khách hàng</h3>
                <p><strong>Họ tên:</strong> {{ $booking->booked_customer_name ?: '-' }}</p>
                <p><strong>SĐT:</strong> {{ $booking->booked_customer_phone ?: '-' }}</p>
                <p><strong>Email:</strong> {{ $booking->booked_customer_email ?: '-' }}</p>
                <p><strong>CCCD:</strong> {{ $booking->booked_customer_cccd ?: '-' }}</p>
            </td>
            <td>
                <h3>Thông tin hóa đơn</h3>
                <p><strong>Mã booking:</strong> {{ $booking->booking_code }}</p>
                <p><strong>Ngày tạo:</strong> {{ optional($booking->created_at)->format('d/m/Y H:i') ?? '-' }}</p>
                <p><strong>Thời gian ở:</strong><br>
                    {{ \Carbon\Carbon::parse($booking->check_in_at)->format('d/m/Y H:i') }}
                    → {{ \Carbon\Carbon::parse($booking->check_out_at)->format('d/m/Y H:i') }}
                </p>
                <p><strong>Thanh toán:</strong>
                    @if($isCancelled && $pendingRefundTotal > 0)
                        <span class="status partial">Đã hủy · Chờ hoàn tiền</span>
                    @elseif($isCancelled && $refundedTotal > 0)
                        <span class="status paid">Đã hủy · Đã hoàn tiền</span>
                    @elseif($isCancelled)
                        <span class="status unpaid">Đã hủy</span>
                    @elseif($remainingTotal <= 0.01)
                        <span class="status paid">Đã thanh toán</span>
                    @elseif($netPaidTotal > 0)
                        <span class="status partial">Thanh toán một phần</span>
                    @else
                        <span class="status unpaid">Chưa thanh toán</span>
                    @endif
                </p>
            </td>
        </tr>
    </table>

    <h3>Chi tiết tiền phòng</h3>
    <table class="table">
        <thead>
        <tr>
            <th>Mô tả</th>
            <th class="text-right">Thời lượng</th>
            <th class="text-right">Giá niêm yết/đêm</th>
            <th class="text-right">Thành tiền</th>
        </tr>
        </thead>
        <tbody>
        @forelse($roomLines as $line)
            <tr>
                <td>
                    <strong>Phòng {{ $line['room_number'] }}</strong><br>
                    <span class="muted">{{ $line['category_name'] }}</span>
                    @if(abs($line['surcharge']) > 0.01)
                        <br><span class="muted">
                            Điều chỉnh/phụ thu phòng: {{ number_format($line['surcharge'], 0, ',', '.') }}đ
                            @if($line['surcharge_reason']) — {{ $line['surcharge_reason'] }} @endif
                        </span>
                    @endif
                </td>
                <td class="text-right">{{ $line['quantity_label'] }}</td>
                <td class="text-right">{{ number_format($line['unit_price'], 0, ',', '.') }}đ</td>
                <td class="text-right">{{ number_format($line['total'], 0, ',', '.') }}đ</td>
            </tr>
        @empty
            <tr><td colspan="4">Chưa có phòng trong booking.</td></tr>
        @endforelse
        </tbody>
    </table>

    @if($roomChangeLines->isNotEmpty())
        <h3>Lịch sử đổi phòng</h3>
        <table class="table">
            <thead>
            <tr>
                <th>Thời điểm / lý do</th>
                <th>Phòng</th>
                <th class="text-right">Phần lưu trú còn lại</th>
                <th class="text-right">Giá cũ → giá mới</th>
                <th class="text-right">Chênh lệch tính khách</th>
            </tr>
            </thead>
            <tbody>
            @foreach($roomChangeLines as $change)
                @php
                    $oldRoomNumber = $change->oldRoom?->room_number ?? '---';
                    $newRoomNumber = $change->newRoom?->room_number ?? '---';
                    $oldCategoryName = $change->oldCategory?->name ?? $change->oldRoom?->category?->name;
                    $newCategoryName = $change->newCategory?->name ?? $change->newRoom?->category?->name;
                    $sourceLabel = $change->change_source === 'incident' ? 'Sự cố phía khách sạn' : 'Đổi phòng theo yêu cầu/nghiệp vụ';
                @endphp
                <tr>
                    <td>
                        {{ optional($change->created_at)->format('d/m/Y H:i') ?? '-' }}<br>
                        <span class="muted">{{ $sourceLabel }}@if($change->reason) — {{ $change->reason }}@endif</span>
                    </td>
                    <td>
                        <strong>{{ $oldRoomNumber }} → {{ $newRoomNumber }}</strong>
                        @if($oldCategoryName || $newCategoryName)
                            <br><span class="muted">{{ $oldCategoryName ?: '---' }} → {{ $newCategoryName ?: '---' }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ max(0, (int) $change->night_count) }} đêm</td>
                    <td class="text-right">
                        {{ number_format((float) $change->old_room_price, 0, ',', '.') }}đ
                        → {{ number_format((float) $change->new_room_price, 0, ',', '.') }}đ
                    </td>
                    <td class="text-right">
                        {{ number_format((float) $change->price_difference_total, 0, ',', '.') }}đ
                        @if($change->change_source === 'incident')
                            <br><span class="muted">Khách không chịu chênh nâng hạng do sự cố khách sạn.</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($serviceLines->isNotEmpty())
        <h3>Dịch vụ và phụ thu</h3>
        <table class="table">
            <thead>
            <tr>
                <th>Dịch vụ</th>
                <th class="text-right">Số lượng tính tiền</th>
                <th class="text-right">Đơn giá</th>
                <th class="text-right">Thành tiền</th>
            </tr>
            </thead>
            <tbody>
            @foreach($serviceLines as $serviceItem)
                <tr>
                    <td>
                        <strong>{{ $serviceItem->name ?: ($serviceItem->service?->name ?? 'Dịch vụ') }}</strong>
                        @if($serviceItem->note)
                            <br><span class="muted">{{ $serviceItem->note }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $serviceItem->used_quantity ?: $serviceItem->quantity }}</td>
                    <td class="text-right">{{ number_format($serviceItem->unit_price, 0, ',', '.') }}đ</td>
                    <td class="text-right">{{ number_format($serviceItem->total, 0, ',', '.') }}đ</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($inspectionLines->isNotEmpty())
        <h3>Minibar / hư hại đã duyệt</h3>
        <table class="table">
            <thead>
            <tr>
                <th>Nội dung</th>
                <th class="text-right">Số lượng</th>
                <th class="text-right">Đơn giá</th>
                <th class="text-right">Thành tiền</th>
            </tr>
            </thead>
            <tbody>
            @foreach($inspectionLines as $item)
                <tr>
                    <td>{{ $item->name ?: 'Khoản kiểm phòng' }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->price, 0, ',', '.') }}đ</td>
                    <td class="text-right">{{ number_format($item->total, 0, ',', '.') }}đ</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <table class="summary">
        <tr><td>Tổng tiền phòng:</td><td class="text-right">{{ number_format($roomTotal, 0, ',', '.') }}đ</td></tr>
        @if($serviceTotal > 0)
            <tr><td>Dịch vụ/phụ thu:</td><td class="text-right">{{ number_format($serviceTotal, 0, ',', '.') }}đ</td></tr>
        @endif
        @if($inspectionTotal > 0)
            <tr><td>Minibar/hư hại:</td><td class="text-right">{{ number_format($inspectionTotal, 0, ',', '.') }}đ</td></tr>
        @endif
        @if((float) ($booking->room_selection_fee ?? 0) > 0)
            <tr><td>Phí chọn phòng thủ công:</td><td class="text-right">{{ number_format((float) $booking->room_selection_fee, 0, ',', '.') }}đ</td></tr>
        @endif
        @if((float) $booking->discount_amount > 0)
            <tr><td>Giảm giá:</td><td class="text-right">-{{ number_format($booking->discount_amount, 0, ',', '.') }}đ</td></tr>
        @endif
        <tr class="grand"><td>Tổng giá trị booking:</td><td class="text-right">{{ number_format($grandTotal, 0, ',', '.') }}đ</td></tr>
        <tr><td>Tổng tiền đã thu:</td><td class="text-right">{{ number_format($paidTotal, 0, ',', '.') }}đ</td></tr>
        @if($refundedTotal > 0)
            <tr><td>Đã hoàn khách:</td><td class="text-right">-{{ number_format($refundedTotal, 0, ',', '.') }}đ</td></tr>
            <tr><td>Thực giữ sau hoàn:</td><td class="text-right">{{ number_format($netPaidTotal, 0, ',', '.') }}đ</td></tr>
        @elseif($pendingRefundTotal > 0)
            <tr class="remaining"><td>Đang chờ hoàn khách:</td><td class="text-right">{{ number_format($pendingRefundTotal, 0, ',', '.') }}đ</td></tr>
        @endif
        @if($isCancelled)
            <tr class="remaining"><td>Còn phải thu:</td><td class="text-right">0đ</td></tr>
        @elseif($remainingTotal > 0)
            <tr class="remaining"><td>Còn phải thu:</td><td class="text-right">{{ number_format($remainingTotal, 0, ',', '.') }}đ</td></tr>
        @elseif($overpaymentTotal > 0)
            <tr class="remaining"><td>Khách thanh toán dư:</td><td class="text-right">{{ number_format($overpaymentTotal, 0, ',', '.') }}đ</td></tr>
        @else
            <tr class="remaining"><td>Còn phải thu:</td><td class="text-right">0đ</td></tr>
        @endif
        @if($refundDueTotal ?? false)
            @if($booking->refund_reason)
                <tr><td>Lý do hoàn:</td><td class="text-right">{{ $booking->refund_reason }}</td></tr>
            @endif
            @if($booking->refund_status === 'completed' && $booking->refund_processed_at)
                <tr><td>Hoàn tất lúc:</td><td class="text-right">{{ $booking->refund_processed_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td></tr>
                @if($booking->refund_processed_note)
                    <tr><td>Ghi chú hoàn tiền:</td><td class="text-right">{{ $booking->refund_processed_note }}</td></tr>
                @endif
            @endif
        @endif
    </table>

    @if($successfulPayments->isNotEmpty())
        <h3 style="margin-top: 28px;">Lịch sử tiền đã thu</h3>
        <table class="table">
            <thead>
            <tr>
                <th>Thời gian</th>
                <th>Phương thức</th>
                <th>Mã giao dịch</th>
                <th class="text-right">Số tiền</th>
            </tr>
            </thead>
            <tbody>
            @foreach($successfulPayments as $payment)
                <tr>
                    <td>{{ optional($payment->paid_at ?? $payment->created_at)->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>
                        @switch($payment->provider)
                            @case('cash') Tiền mặt @break
                            @case('bank_transfer') Chuyển khoản @break
                            @case('vnpay') VNPay @break
                            @default {{ $payment->provider ?: '-' }}
                        @endswitch
                    </td>
                    <td>{{ $payment->txn_ref ?: '-' }}</td>
                    <td class="text-right">{{ number_format($payment->amount, 0, ',', '.') }}đ</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>Cảm ơn quý khách đã sử dụng dịch vụ của {{ config('app.name', 'MCuong Hotel') }}!</p>
        <p>Hóa đơn được xuất lúc {{ now('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}.</p>
    </div>
</div>
</body>
</html>
