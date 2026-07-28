<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo tình trạng phòng — {{ $periodLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #18221f; background: #fff; }

        .header { background: #0a1931; color: #fff; padding: 22px 30px; margin-bottom: 22px; border-bottom: 3px solid #d4af37; }
        .header h1 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .header p  { font-size: 11px; color: #c7d0dd; }

        .section { margin: 0 30px 22px; }
        .section-title {
            font-size: 12px; font-weight: 700; color: #475569;
            text-transform: uppercase; letter-spacing: .04em;
            border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 10px;
        }

        /* Summary */
        table.summary { width: 100%; border-collapse: separate; border-spacing: 6px; }
        table.summary td { width: 16.66%; }
        .summary-card {
            background: #f8faf9; border: 1px solid #e1e7e4; border-radius: 8px;
            padding: 10px 12px; text-align: center;
        }
        .summary-card .val { font-size: 15px; font-weight: 800; color: #18221f; }
        .summary-card .lbl { font-size: 10px; color: #64748b; margin-top: 3px; }
        .green  { color: #16a34a !important; }
        .blue   { color: #1d4ed8 !important; }
        .orange { color: #d97706 !important; }
        .red    { color: #dc2626 !important; }

        /* Data tables */
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data thead th {
            background: #f3f7f5; color: #52605c; font-size: 10px; font-weight: 700;
            padding: 7px 9px; text-align: left; border-bottom: 2px solid #e2e8f0;
        }
        table.data tbody td { padding: 7px 9px; border-bottom: 1px solid #f1f5f9; font-size: 11px; }
        table.data tbody tr:last-child td { border-bottom: none; }
        .tc { text-align: center; }
        .tr { text-align: right; }

        /* Column widths */
        table.table-category th:nth-child(1) { width: 25%; }
        table.table-category th:nth-child(2) { width: 10%; }
        table.table-category th:nth-child(3) { width: 10%; }
        table.table-category th:nth-child(4) { width: 10%; }
        table.table-category th:nth-child(5) { width: 10%; }
        table.table-category th:nth-child(6) { width: 10%; }
        table.table-category th:nth-child(7) { width: 10%; }
        table.table-category th:nth-child(8) { width: 15%; }

        table.table-room th:nth-child(1) { width: 12%; }
        table.table-room th:nth-child(2) { width: 10%; }
        table.table-room th:nth-child(3) { width: 20%; }
        table.table-room th:nth-child(4) { width: 15%; }
        table.table-room th:nth-child(5) { width: 23%; }
        table.table-room th:nth-child(6) { width: 20%; }

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
    <h1>MCuong Hotel &mdash; Báo cáo tình trạng phòng &mdash; {{ $periodLabel }}</h1>
    <p>Xuất lúc {{ $generatedAt }}</p>
</div>

{{-- Filter Info --}}
<div class="section">
    <div class="section-title">Bộ lọc áp dụng</div>
    <table class="data">
        <tr>
            <td><strong>Tầng:</strong> {{ $floorLabel }}</td>
            <td><strong>Hạng phòng:</strong> {{ $categoryLabel }}</td>
            <td><strong>Trạng thái:</strong> {{ $statusLabel }}</td>
        </tr>
    </table>
</div>

{{-- Tổng quan --}}
<div class="section">
    <div class="section-title">Tổng quan tình trạng phòng</div>
    <table class="summary">
        <tr>
            <td>
                <div class="summary-card">
                    <div class="val">{{ $totalRooms }}</div>
                    <div class="lbl">Tổng số phòng</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="val green">{{ $availableRooms }}</div>
                    <div class="lbl">Phòng trống</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="val blue">{{ $reservedRooms }}</div>
                    <div class="lbl">Phòng đã đặt</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="val orange">{{ $occupiedRooms }}</div>
                    <div class="lbl">Phòng đang ở</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="val">{{ $cleaningRooms }}</div>
                    <div class="lbl">Phòng đang dọn</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="val red">{{ $maintenanceRooms }}</div>
                    <div class="lbl">Phòng bảo trì</div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- Công suất --}}
<div class="section">
    <div class="section-title">Công suất phòng</div>
    <table class="data">
        <tr>
            <td style="padding: 15px;">
                <span style="font-size: 24px; font-weight: 800; color: {{ $occupancyRate >= 80 ? '#16a34a' : ($occupancyRate >= 50 ? '#d97706' : '#dc2626') }};">
                    {{ $occupancyRate }}%
                </span>
                <span style="font-size: 12px; color: #64748b; margin-left: 10px;">
                    Khách sạn đang kín {{ $occupancyRate }}%
                </span>
            </td>
        </tr>
    </table>
</div>

{{-- Theo hạng phòng --}}
@if ($categoryStats)
<div class="section">
    <div class="section-title">Thống kê theo hạng phòng</div>
    <table class="data table-category">
        <thead>
            <tr>
                <th>Hạng phòng</th>
                <th class="tc">Tổng</th>
                <th class="tc">Đang ở</th>
                <th class="tc">Đã đặt</th>
                <th class="tc">Trống</th>
                <th class="tc">Đang dọn</th>
                <th class="tc">Bảo trì</th>
                <th class="tc">Công suất</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categoryStats as $stat)
            <tr>
                <td>{{ $stat['category'] }}</td>
                <td class="tc">{{ $stat['total'] }}</td>
                <td class="tc">{{ $stat['occupied'] }}</td>
                <td class="tc">{{ $stat['reserved'] }}</td>
                <td class="tc">{{ $stat['available'] }}</td>
                <td class="tc">{{ $stat['cleaning'] }}</td>
                <td class="tc">{{ $stat['maintenance'] }}</td>
                <td class="tc">{{ $stat['occupancy_rate'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Danh sách phòng --}}
@if ($roomList->isNotEmpty())
<div class="section">
    <div class="section-title">Danh sách phòng chi tiết</div>
    <table class="data table-room">
        <thead>
            <tr>
                <th>Số phòng</th>
                <th>Tầng</th>
                <th>Hạng phòng</th>
                <th>Trạng thái</th>
                <th>Khách hàng</th>
                <th>Giờ trả phòng</th>
            </tr>
        </thead>
        <tbody>
            @foreach($roomList as $room)
            <tr>
                <td><strong>{{ $room['room_number'] }}</strong></td>
                <td>Tầng {{ $room['floor'] }}</td>
                <td>{{ $room['category'] }}</td>
                <td>{{ $statuses[$room['status']] ?? $room['status'] }}</td>
                <td>{{ $room['customer'] }}</td>
                <td>{{ $room['check_out'] }}</td>
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
