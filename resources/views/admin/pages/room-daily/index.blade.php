@extends('layouts.admin')

@section('title', 'Trạng thái phòng theo ngày')

@section('content')

@php
    $statusLabels = [
        'available'   => 'Trống',
        'reserved'    => 'Đã đặt',
        'occupied'    => 'Đang ở',
        'inspection'  => 'Kiểm tra',
        'cleaning'    => 'Đang dọn',
        'maintenance' => 'Bảo trì',
    ];

    $statusCardClasses = [
        'available'   => 'room-card-available',
        'reserved'    => 'room-card-reserved',
        'occupied'    => 'room-card-occupied',
        'inspection'  => 'room-card-inspection',
        'cleaning'    => 'room-card-cleaning',
        'maintenance' => 'room-card-maintenance',
    ];

    $statusBadgeClasses = [
        'available'   => 'badge-daily-available',
        'reserved'    => 'badge-daily-reserved',
        'occupied'    => 'badge-daily-occupied',
        'inspection'  => 'badge-daily-inspection',
        'cleaning'    => 'badge-daily-cleaning',
        'maintenance' => 'badge-daily-maintenance',
    ];

    $isPast   = $selectedDate < $today;
    $isFuture = $selectedDate > $today;

    $displayDate = \Carbon\Carbon::parse($selectedDate)->locale('vi')->isoFormat('dddd, DD/MM/YYYY');

    $bookingStatusLabels = [
        'pending'              => 'Chờ xác nhận',
        'confirmed'            => 'Đã xác nhận',
        'checked_in'           => 'Đã check-in',
        'inspection_requested' => 'Yêu cầu kiểm tra',
        'checked_out'          => 'Đã trả phòng',
        'completed'            => 'Hoàn thành',
        'cancelled'            => 'Đã hủy',
    ];
@endphp

<style>
    .daily-page { color: #0f172a; }

    /* ── Toolbar ── */
    .daily-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }

    /* ── Filter card ── */
    .daily-filter-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        padding: 16px 20px;
        margin-bottom: 18px;
        box-shadow: 0 4px 14px rgba(15,23,42,.04);
    }

    .daily-filter-card .filter-row {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 10px;
    }

    .daily-filter-card .filter-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .daily-filter-card label {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        margin: 0;
    }

    .time-separator {
        font-size: 18px;
        font-weight: 700;
        color: #94a3b8;
        padding-bottom: 6px;
    }

    .window-info-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
    }

    .window-today    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .window-past     { background: #fdf4ff; color: #6b21a8; border: 1px solid #d8b4fe; }
    .window-future   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .window-timespan { background: #ecfdf5; color: #166534; border: 1px solid #86efac; }

    /* ── Summary ── */
    .daily-summary-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(110px, 1fr));
        gap: 10px;
        margin-bottom: 18px;
    }

    .daily-summary-item {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 12px 14px;
        box-shadow: 0 4px 14px rgba(15,23,42,.04);
    }

    .daily-summary-item .label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #64748b;
        margin-bottom: 6px;
        white-space: nowrap;
    }

    .daily-summary-item .count {
        font-size: 26px;
        font-weight: 800;
        line-height: 1;
        color: #0f172a;
    }

    .room-dot { width: 9px; height: 9px; border-radius: 999px; display: inline-block; flex: 0 0 auto; }
    .dot-total       { background: #0f172a; }
    .dot-available   { background: #16a34a; }
    .dot-reserved    { background: #f59e0b; }
    .dot-occupied    { background: #ef4444; }
    .dot-inspection  { background: #06b6d4; }
    .dot-cleaning    { background: #3b82f6; }
    .dot-maintenance { background: #64748b; }

    /* ── Legend ── */
    .daily-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .daily-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #fff;
        padding: 6px 12px;
        font-size: 13px;
        color: #475569;
    }

    /* ── Floor ── */
    .floor-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 22px;
        padding: 18px;
        margin-bottom: 18px;
        box-shadow: 0 10px 28px rgba(15,23,42,.04);
    }

    .floor-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eef2f7;
        padding-bottom: 12px;
        margin-bottom: 16px;
    }

    .floor-head h5 { margin: 0; font-weight: 800; }
    .floor-head span { color: #64748b; font-size: 13px; }

    /* ── Room grid ── */
    .daily-room-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }

    .daily-room-card {
        border-radius: 18px;
        border: 1px solid transparent;
        padding: 14px;
        min-height: 160px;
        display: flex;
        flex-direction: column;
        gap: 5px;
        transition: .15s ease;
    }

    .daily-room-card:hover { transform: translateY(-2px); box-shadow: 0 14px 28px rgba(15,23,42,.1); }

    .room-card-available   { background: linear-gradient(145deg,#ecfdf5,#d1fae5); border-color: #bbf7d0; }
    .room-card-reserved    { background: linear-gradient(145deg,#fffbeb,#fef3c7); border-color: #fde68a; }
    .room-card-occupied    { background: linear-gradient(145deg,#fef2f2,#fee2e2); border-color: #fecaca; }
    .room-card-inspection  { background: linear-gradient(145deg,#ecfeff,#cffafe); border-color: #a5f3fc; }
    .room-card-cleaning    { background: linear-gradient(145deg,#eff6ff,#dbeafe); border-color: #bfdbfe; }
    .room-card-maintenance { background: linear-gradient(145deg,#f8fafc,#e2e8f0); border-color: #cbd5e1; }

    .daily-room-number { font-size: 26px; font-weight: 900; letter-spacing: -0.02em; line-height: 1; }

    .daily-room-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .badge-daily-available   { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .badge-daily-reserved    { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    .badge-daily-occupied    { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .badge-daily-inspection  { background: #cffafe; color: #155e75; border: 1px solid #67e8f9; }
    .badge-daily-cleaning    { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }
    .badge-daily-maintenance { background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; }

    .daily-room-category { font-size: 12px; font-weight: 600; color: #475569; }

    /* ── Booking entries ── */
    .booking-entry {
        background: rgba(255,255,255,0.65);
        border: 1px solid rgba(255,255,255,0.9);
        border-radius: 10px;
        padding: 7px 9px;
        font-size: 12px;
        color: #334155;
        margin-top: 4px;
    }

    .booking-entry + .booking-entry { margin-top: 6px; }

    .booking-entry .guest-name {
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 3px;
    }

    .booking-entry .booking-code-row {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
        margin-bottom: 3px;
    }

    .booking-entry a { color: #2563eb; font-weight: 600; text-decoration: none; }
    .booking-entry a:hover { text-decoration: underline; }

    .booking-entry .time-row {
        display: flex;
        align-items: center;
        gap: 4px;
        color: #475569;
        font-size: 11px;
    }

    .booking-entry .pax-row {
        color: #64748b;
        font-size: 11px;
        margin-top: 2px;
    }

    .booking-status-pill {
        display: inline-flex;
        align-items: center;
        padding: 1px 7px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        white-space: nowrap;
    }

    .booking-status-pending              { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
    .booking-status-confirmed            { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
    .booking-status-checked_in           { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .booking-status-inspection_requested { background: #cffafe; color: #155e75; border: 1px solid #67e8f9; }
    .booking-status-checked_out          { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
    .booking-status-completed            { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

    .room-note-info {
        font-size: 12px;
        color: #64748b;
        display: flex;
        align-items: flex-start;
        gap: 4px;
        margin-top: auto;
    }

    @media (max-width: 1200px) {
        .daily-summary-grid { grid-template-columns: repeat(4, minmax(110px, 1fr)); }
    }

    @media (max-width: 768px) {
        .daily-summary-grid { grid-template-columns: repeat(2, 1fr); }
        .daily-room-grid { grid-template-columns: 1fr 1fr; }
        .daily-toolbar { flex-direction: column; }
        .daily-filter-card .filter-row { flex-direction: column; align-items: stretch; }
    }
</style>

<div class="admin-wrapper daily-page">
    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('admin.dashboard') }}">Admin</a> / Trạng thái phòng theo ngày
        </p>

        {{-- Title --}}
        <div class="daily-toolbar">
            <div>
                <h2 class="mb-1">Trạng thái phòng theo ngày</h2>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                    @if ($isToday && !$hasTimeFilter)
                        <span class="window-info-badge window-today">
                            <i class="bx bx-calendar-check"></i> Hôm nay &mdash; {{ $displayDate }}
                        </span>
                    @elseif ($isToday && $hasTimeFilter)
                        <span class="window-info-badge window-today">
                            <i class="bx bx-calendar-check"></i> Hôm nay &mdash; {{ $displayDate }}
                        </span>
                        <span class="window-info-badge window-timespan">
                            <i class="bx bx-time-five"></i>
                            {{ $windowStart->format('H:i') }} → {{ $windowEnd->format('H:i') }}
                        </span>
                    @elseif ($isPast)
                        <span class="window-info-badge window-past">
                            <i class="bx bx-history"></i> Quá khứ &mdash; {{ $displayDate }}
                        </span>
                        @if ($hasTimeFilter)
                            <span class="window-info-badge window-timespan">
                                <i class="bx bx-time-five"></i>
                                {{ $windowStart->format('H:i') }} → {{ $windowEnd->format('H:i') }}
                            </span>
                        @endif
                    @else
                        <span class="window-info-badge window-future">
                            <i class="bx bx-calendar"></i> Tương lai &mdash; {{ $displayDate }}
                        </span>
                        @if ($hasTimeFilter)
                            <span class="window-info-badge window-timespan">
                                <i class="bx bx-time-five"></i>
                                {{ $windowStart->format('H:i') }} → {{ $windowEnd->format('H:i') }}
                            </span>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Filter form --}}
        <div class="daily-filter-card">
            <form method="GET" action="{{ route('admin.room-daily.index') }}">
                <div class="filter-row">

                    <div class="filter-group">
                        <label>Ngày</label>
                        <input type="text"
                            name="date"
                            id="dailyDatePicker"
                            class="form-control"
                            value="{{ $selectedDate }}"
                            autocomplete="off"
                            style="width:150px">
                    </div>

                    <div class="filter-group">
                        <label>Giờ từ <span class="text-muted fw-normal">(tuỳ chọn)</span></label>
                        <input type="text"
                            name="time_from"
                            id="timeFrom"
                            class="form-control js-time-picker"
                            value="{{ $timeFrom }}"
                            placeholder="08:00"
                            autocomplete="off"
                            style="width:100px">
                    </div>

                    <div class="time-separator">→</div>

                    <div class="filter-group">
                        <label>Giờ đến</label>
                        <input type="text"
                            name="time_to"
                            id="timeTo"
                            class="form-control js-time-picker"
                            value="{{ $timeTo }}"
                            placeholder="12:00"
                            autocomplete="off"
                            style="width:100px">
                    </div>

                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-search me-1"></i>Xem
                        </button>
                    </div>

                    @if ($hasTimeFilter || !$isToday)
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <a href="{{ route('admin.room-daily.index') }}" class="btn btn-outline-secondary">
                                Hôm nay
                            </a>
                        </div>
                    @endif

                </div>

                @if ($hasTimeFilter)
                    <div class="mt-2" style="font-size:13px;color:#64748b">
                        <i class="bx bx-info-circle"></i>
                        Đang lọc các phòng có booking chạy trong khung
                        <strong>{{ $windowStart->format('H:i d/m/Y') }}</strong>
                        đến
                        <strong>{{ $windowEnd->format('H:i d/m/Y') }}</strong>.
                        @if ($windowEnd->toDateString() !== $windowStart->toDateString())
                            <span style="color:#92400e">(khung giờ qua đêm)</span>
                        @endif
                        Phòng không có booking trong khung này hiển thị là <strong>Trống</strong>.
                    </div>
                @endif
            </form>
        </div>

        {{-- Summary --}}
        <div class="daily-summary-grid">
            <div class="daily-summary-item">
                <div class="label"><i class="room-dot dot-total"></i> Tổng</div>
                <div class="count">{{ $summary['total'] }}</div>
            </div>
            <div class="daily-summary-item">
                <div class="label"><i class="room-dot dot-available"></i> Trống</div>
                <div class="count">{{ $summary['available'] }}</div>
            </div>
            <div class="daily-summary-item">
                <div class="label"><i class="room-dot dot-reserved"></i> Đã đặt</div>
                <div class="count">{{ $summary['reserved'] }}</div>
            </div>
            <div class="daily-summary-item">
                <div class="label"><i class="room-dot dot-occupied"></i> Đang ở</div>
                <div class="count">{{ $summary['occupied'] }}</div>
            </div>
            <div class="daily-summary-item">
                <div class="label"><i class="room-dot dot-inspection"></i> Kiểm tra</div>
                <div class="count">{{ $summary['inspection'] }}</div>
            </div>
            <div class="daily-summary-item">
                <div class="label"><i class="room-dot dot-cleaning"></i> Đang dọn</div>
                <div class="count">{{ $summary['cleaning'] }}</div>
            </div>
            <div class="daily-summary-item">
                <div class="label"><i class="room-dot dot-maintenance"></i> Bảo trì</div>
                <div class="count">{{ $summary['maintenance'] }}</div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="daily-legend">
            @foreach ($statusLabels as $key => $label)
                <div class="daily-legend-item">
                    <i class="room-dot dot-{{ $key }}"></i> {{ $label }}
                </div>
            @endforeach
        </div>

        {{-- Floors --}}
        @forelse ($roomsByFloor->sortKeysDesc() as $floor => $floorRooms)
            <section class="floor-section">
                <div class="floor-head">
                    <h5>
                        <i class="bx bx-buildings me-1"></i>
                        {{ $floor === 'unknown' ? 'Chưa rõ tầng' : 'Tầng ' . $floor }}
                    </h5>
                    <span>{{ $floorRooms->count() }} phòng</span>
                </div>

                <div class="daily-room-grid">
                    @foreach ($floorRooms->sortBy('room_number') as $room)
                        @php
                            $ds          = $room->daily_status ?? 'available';
                            $cardClass   = $statusCardClasses[$ds]  ?? 'room-card-available';
                            $badgeClass  = $statusBadgeClasses[$ds] ?? 'badge-daily-available';
                            $statusLabel = $statusLabels[$ds] ?? $ds;
                            $bookings    = $room->daily_bookings ?? [];
                        @endphp

                        <div class="daily-room-card {{ $cardClass }}">

                            {{-- Số phòng + badge --}}
                            <div class="d-flex justify-content-between align-items-start gap-1">
                                <div class="daily-room-number">{{ $room->room_number }}</div>
                                <span class="daily-room-badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                            </div>

                            {{-- Hạng phòng --}}
                            <div class="daily-room-category">{{ $room->category->name ?? '—' }}</div>

                            {{-- Danh sách bookings trong khung giờ --}}
                            @forelse ($bookings as $booking)
                                <div class="booking-entry">

                                    {{-- Tên khách --}}
                                    @if ($booking->customer)
                                        <div class="guest-name">
                                            <i class="bx bx-user" style="font-size:13px"></i>
                                            {{ $booking->customer->last_name }} {{ $booking->customer->first_name }}
                                        </div>
                                    @endif

                                    {{-- Mã + trạng thái --}}
                                    <div class="booking-code-row">
                                        <a href="{{ route('admin.bookings.show', $booking->id) }}" target="_blank">
                                            #{{ $booking->booking_code }}
                                        </a>
                                        <span class="booking-status-pill booking-status-{{ $booking->status }}">
                                            {{ $bookingStatusLabels[$booking->status] ?? $booking->status }}
                                        </span>
                                    </div>

                                    {{-- Khung giờ --}}
                                    <div class="time-row">
                                        <i class="bx bx-time-five"></i>
                                        {{ \Carbon\Carbon::parse($booking->check_in_at)->format('d/m H:i') }}
                                        →
                                        {{ \Carbon\Carbon::parse($booking->check_out_at)->format('d/m H:i') }}
                                    </div>

                                    {{-- Số khách --}}
                                    @if ($booking->adult_count || $booking->child_count)
                                        <div class="pax-row">
                                            <i class="bx bx-group"></i>
                                            {{ $booking->adult_count ?? 0 }} người lớn
                                            @if ($booking->child_count)
                                                · {{ $booking->child_count }} trẻ em
                                            @endif
                                        </div>
                                    @endif

                                </div>
                            @empty
                                {{-- Phòng trống / bảo trì / dọn: hiện ghi chú nếu có --}}
                                @if ($room->note)
                                    <div class="room-note-info mt-auto">
                                        <i class="bx bx-note" style="font-size:13px;flex-shrink:0;margin-top:1px"></i>
                                        {{ \Illuminate\Support\Str::limit($room->note, 70) }}
                                    </div>
                                @endif
                            @endforelse

                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="text-center text-muted py-5">
                <i class="bx bx-hotel fs-1 d-block mb-2"></i>
                Chưa có phòng nào.
            </div>
        @endforelse

    </main>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>
<script>
    (function () {
        const locale = (typeof flatpickr !== 'undefined' && flatpickr.l10ns && flatpickr.l10ns.vn) ? 'vn' : 'default';

        flatpickr('#dailyDatePicker', {
            locale,
            altInput: true,
            altFormat: 'd/m/Y',
            dateFormat: 'Y-m-d',
            allowInput: false,
            disableMobile: true,
        });

        document.querySelectorAll('.js-time-picker').forEach(function (el) {
            flatpickr(el, {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                minuteIncrement: 30,
                allowInput: false,
                disableMobile: true,
            });
        });
    })();
</script>

@endsection
