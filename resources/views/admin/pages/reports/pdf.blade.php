<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo doanh thu — {{ $periodLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; background: #fff; }

        .header { background: #0f172a; color: #fff; padding: 22px 30px; margin-bottom: 22px; }
        .header h1 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .header p  { font-size: 11px; color: #94a3b8; }

        .section { margin: 0 30px 22px; }
        .section-title {
            font-size: 12px; font-weight: 700; color: #475569;
            text-transform: uppercase; letter-spacing: .04em;
            border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 10px;
        }

        /* Summary */
        table.summary { width: 100%; border-collapse: separate; border-spacing: 6px; }
        table.summary td { width: 25%; }
        .summary-card {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 10px 12px; text-align: center;
        }
        .summary-card .val { font-size: 15px; font-weight: 800; color: #0f172a; }
        .summary-card .lbl { font-size: 10px; color: #64748b; margin-top: 3px; }
        .green  { color: #16a34a !important; }
        .blue   { color: #1d4ed8 !important; }
        .orange { color: #d97706 !important; }

        /* Data tables */
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data thead th {
            background: #f1f5f9; color: #475569; font-size: 10px; font-weight: 700;
            padding: 7px 9px; text-align: left; border-bottom: 2px solid #e2e8f0;
        }
        table.data tbody td { padding: 7px 9px; border-bottom: 1px solid #f1f5f9; font-size: 11px; }
        table.data tbody tr:last-child td { border-bottom: none; }
        table.data tbody tr.total-row { background: #fffbeb; font-weight: 700; }
        .tr { text-align: right; }
        .tc { text-align: center; }

        /* Column widths for year mode table (6 columns) */
        table.table-year th:nth-child(1) { width: 12%; }
        table.table-year th:nth-child(2) { width: 10%; }
        table.table-year th:nth-child(3) { width: 20%; }
        table.table-year th:nth-child(4) { width: 20%; }
        table.table-year th:nth-child(5) { width: 20%; }
        table.table-year th:nth-child(6) { width: 18%; }

        /* Column widths for month/range mode table (5 columns) */
        table.table-daily th:nth-child(1) { width: 15%; }
        table.table-daily th:nth-child(2) { width: 12%; }
        table.table-daily th:nth-child(3) { width: 25%; }
        table.table-daily th:nth-child(4) { width: 25%; }
        table.table-daily th:nth-child(5) { width: 23%; }

        /* Column widths for category table (4 columns) */
        table.table-category th:nth-child(1) { width: 35%; }
        table.table-category th:nth-child(2) { width: 15%; }
        table.table-category th:nth-child(3) { width: 30%; }
        table.table-category th:nth-child(4) { width: 20%; }

        .footer {
            margin: 18px 30px 0; padding-top: 12px;
            border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8;
        }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer td:last-child { text-align: right; }
    </style>
</head>
<body>

<div class="header">
    <h1>MCuong Hotel &mdash; Báo cáo doanh thu &mdash; {{ $periodLabel }}</h1>
    <p>Xuất lúc {{ $generatedAt }}</p>
</div>

{{-- Tổng quan --}}
<div class="section">
    <div class="section-title">Tổng quan kỳ báo cáo</div>
    <table class="summary">
        <tr>
            <td>
                <div class="summary-card">
                    <div class="val">{{ number_format($totalBookings) }}</div>
                    <div class="lbl">Tổng booking</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="val green">{{ number_format($totalRevenue, 0, ',', '.') }}đ</div>
                    <div class="lbl">Tổng doanh thu</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="val blue">{{ number_format($totalPaid, 0, ',', '.') }}đ</div>
                    <div class="lbl">Đã thu</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="val orange">{{ number_format($totalPending, 0, ',', '.') }}đ</div>
                    <div class="lbl">Còn nợ</div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- Chế độ NĂM: bảng 12 tháng --}}
@if ($mode === 'year' && $monthlyData)
<div class="section">
    <div class="section-title">Chi tiết theo tháng</div>
    <table class="data table-year">
        <thead>
            <tr>
                <th>Tháng</th>
                <th class="tc">Booking</th>
                <th class="tr">Doanh thu</th>
                <th class="tr">Đã thu</th>
                <th class="tr">Còn nợ</th>
                <th class="tr">Tỷ lệ thu</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($monthlyData as $row)
            <tr>
                <td>{{ $row['month'] }}</td>
                <td class="tc">{{ $row['bookings'] }}</td>
                <td class="tr">{{ number_format($row['revenue'], 0, ',', '.') }}đ</td>
                <td class="tr">{{ number_format($row['paid'], 0, ',', '.') }}đ</td>
                <td class="tr">{{ number_format($row['pending'], 0, ',', '.') }}đ</td>
                <td class="tr">{{ $row['revenue'] > 0 ? number_format($row['paid'] / $row['revenue'] * 100, 1) : '0.0' }}%</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>Tổng cộng</td>
                <td class="tc">{{ $totalBookings }}</td>
                <td class="tr">{{ number_format($totalRevenue, 0, ',', '.') }}đ</td>
                <td class="tr">{{ number_format($totalPaid, 0, ',', '.') }}đ</td>
                <td class="tr">{{ number_format($totalPending, 0, ',', '.') }}đ</td>
                <td class="tr">{{ $totalRevenue > 0 ? number_format($totalPaid / $totalRevenue * 100, 1) : '0.0' }}%</td>
            </tr>
        </tbody>
    </table>
</div>
@endif

{{-- Chế độ THÁNG hoặc KHOẢNG NGÀY: bảng từng ngày --}}
@if (in_array($mode, ['month', 'range']) && $dailyData)
<div class="section">
    <div class="section-title">Chi tiết theo ngày</div>
    <table class="data table-daily">
        <thead>
            <tr>
                <th>Ngày</th>
                <th class="tc">Booking</th>
                <th class="tr">Doanh thu</th>
                <th class="tr">Đã thu</th>
                <th class="tr">Còn nợ</th>
            </tr>
        </thead>
        <tbody>
            @php $hasAny = collect($dailyData)->sum('bookings') > 0; @endphp
            @foreach ($dailyData as $row)
            @if ($hasAny && $row['bookings'] == 0) @continue @endif
            <tr>
                <td>{{ $row['date'] }}</td>
                <td class="tc">{{ $row['bookings'] }}</td>
                <td class="tr">{{ number_format($row['revenue'], 0, ',', '.') }}đ</td>
                <td class="tr">{{ number_format($row['paid'], 0, ',', '.') }}đ</td>
                <td class="tr">{{ number_format(max(0, $row['revenue'] - $row['paid']), 0, ',', '.') }}đ</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>Tổng cộng</td>
                <td class="tc">{{ $totalBookings }}</td>
                <td class="tr">{{ number_format($totalRevenue, 0, ',', '.') }}đ</td>
                <td class="tr">{{ number_format($totalPaid, 0, ',', '.') }}đ</td>
                <td class="tr">{{ number_format($totalPending, 0, ',', '.') }}đ</td>
            </tr>
        </tbody>
    </table>
</div>
@endif

{{-- Theo hạng phòng --}}
@if ($categoryData->isNotEmpty())
<div class="section">
    <div class="section-title">Doanh thu theo hạng phòng</div>
    <table class="data table-category">
        <thead>
            <tr>
                <th>Hạng phòng</th>
                <th class="tc">Booking</th>
                <th class="tr">Doanh thu</th>
                <th class="tr">Tỷ trọng</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categoryData as $cat)
            <tr>
                <td>{{ $cat['name'] }}</td>
                <td class="tc">{{ $cat['bookings'] }}</td>
                <td class="tr">{{ number_format($cat['revenue'], 0, ',', '.') }}đ</td>
                <td class="tr">{{ $totalRevenue > 0 ? number_format($cat['revenue'] / $totalRevenue * 100, 1) : '0.0' }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="footer">
    <table>
        <tr>
            <td>MCuong Hotel &mdash; Tài liệu nội bộ</td>
            <td>{{ $periodLabel }}</td>
        </tr>
    </table>
</div>

</body>
</html>
