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

    $displayDateFrom = \Carbon\Carbon::parse($dateFrom)->locale('vi')->isoFormat('DD/MM/YYYY');
    $displayDateTo   = \Carbon\Carbon::parse($dateTo)->locale('vi')->isoFormat('DD/MM/YYYY');

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
                    @if ($isToday && !$hasTimeFilter && !$isRange)
                        <span class="window-info-badge window-today">
                            <i class="bx bx-calendar-check"></i> Hôm nay &mdash; {{ $displayDateFrom }}
                        </span>
                    @elseif ($isRange)
                        <span class="window-info-badge window-future">
                            <i class="bx bx-calendar-range"></i>
                            {{ $windowStart->format('H:i d/m/Y') }} → {{ $windowEnd->format('H:i d/m/Y') }}
                        </span>
                    @elseif ($isPast)
                        <span class="window-info-badge window-past">
                            <i class="bx bx-history"></i> Quá khứ &mdash; {{ $displayDateFrom }}
                        </span>
                        @if ($hasTimeFilter)
                            <span class="window-info-badge window-timespan">
                                <i class="bx bx-time-five"></i>
                                {{ $windowStart->format('H:i') }} → {{ $windowEnd->format('H:i') }}
                            </span>
                        @endif
                    @elseif ($isFuture)
                        <span class="window-info-badge window-future">
                            <i class="bx bx-calendar"></i> Tương lai &mdash; {{ $displayDateFrom }}
                        </span>
                        @if ($hasTimeFilter)
                            <span class="window-info-badge window-timespan">
                                <i class="bx bx-time-five"></i>
                                {{ $windowStart->format('H:i') }} → {{ $windowEnd->format('H:i') }}
                            </span>
                        @endif
                    @else
                        <span class="window-info-badge window-today">
                            <i class="bx bx-calendar-check"></i> Hôm nay &mdash; {{ $displayDateFrom }}
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
                        <label>Ngày từ</label>
                        <input type="text"
                            name="date_from"
                            id="dailyDateFrom"
                            class="form-control"
                            value="{{ $dateFrom }}"
                            autocomplete="off"
                            style="width:140px">
                    </div>

                    <div class="filter-group">
                        <label>Giờ từ <span class="text-muted fw-normal">(tuỳ chọn)</span></label>
                        <input type="text"
                            name="time_from"
                            id="timeFrom"
                            class="form-control js-time-picker"
                            value="{{ $timeFrom }}"
                            placeholder="00:00"
                            autocomplete="off"
                            style="width:95px">
                    </div>

                    <div class="time-separator align-self-end pb-1">→</div>

                    <div class="filter-group">
                        <label>Ngày đến</label>
                        <input type="text"
                            name="date_to"
                            id="dailyDateTo"
                            class="form-control"
                            value="{{ $dateTo }}"
                            autocomplete="off"
                            style="width:140px">
                    </div>

                    <div class="filter-group">
                        <label>Giờ đến <span class="text-muted fw-normal">(tuỳ chọn)</span></label>
                        <input type="text"
                            name="time_to"
                            id="timeTo"
                            class="form-control js-time-picker"
                            value="{{ $timeTo }}"
                            placeholder="23:59"
                            autocomplete="off"
                            style="width:95px">
                    </div>

                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-search me-1"></i>Xem
                        </button>
                    </div>

                    @if ($hasTimeFilter || $isRange || !$isToday)
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <a href="{{ route('admin.room-daily.index') }}" class="btn btn-outline-secondary">
                                Hôm nay
                            </a>
                        </div>
                    @endif

                </div>

                @if ($hasTimeFilter || $isRange)
                    <div class="mt-2" style="font-size:13px;color:#64748b">
                        <i class="bx bx-info-circle"></i>
                        Đang lọc phòng có booking trong khung
                        <strong>{{ $windowStart->format('H:i d/m/Y') }}</strong>
                        →
                        <strong>{{ $windowEnd->format('H:i d/m/Y') }}</strong>.
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

                        <div class="daily-room-card {{ $cardClass }}" style="cursor: pointer;" onclick="openRoomLogModal({{ $room->id }}, '{{ $room->room_number }}', '{{ $dateFrom }}')">

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

        const dateOpts = {
            locale,
            altInput: true,
            altFormat: 'd/m/Y',
            dateFormat: 'Y-m-d',
            allowInput: false,
            disableMobile: true,
        };

        const fpFrom = flatpickr('#dailyDateFrom', {
            ...dateOpts,
            onChange: function (selectedDates) {
                if (selectedDates[0]) {
                    fpTo.set('minDate', selectedDates[0]);
                }
            },
        });

        const fpTo = flatpickr('#dailyDateTo', {
            ...dateOpts,
            onChange: function (selectedDates) {
                if (selectedDates[0]) {
                    fpFrom.set('maxDate', selectedDates[0]);
                }
            },
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

<!-- Modal for Room Info (Bookings + Action Logs) -->
<style>
    .room-modal-section-title {
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0 0 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .booking-modal-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 10px;
    }

    .booking-modal-card .bm-row {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #334155;
        margin-bottom: 5px;
    }

    .booking-modal-card .bm-row:last-child { margin-bottom: 0; }
    .booking-modal-card .bm-row i { color: #94a3b8; font-size: 14px; flex-shrink: 0; }
    .booking-modal-card .bm-label { color: #64748b; min-width: 80px; }
    .booking-modal-card .bm-val   { font-weight: 600; color: #1e293b; }

    .log-item {
        display: flex;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .log-item:last-child { border-bottom: none; }

    .log-time-badge {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        padding: 2px 9px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
        height: fit-content;
    }

    .log-body { flex: 1; min-width: 0; }
    .log-body .log-who { font-size: 13px; font-weight: 600; color: #1e293b; }
    .log-body .log-role { font-size: 11px; color: #64748b; }
    .log-body .log-note { font-size: 13px; color: #475569; margin-top: 3px; }

    .modal-empty-state {
        text-align: center;
        padding: 24px;
        color: #94a3b8;
        font-size: 13px;
    }

    .modal-empty-state i { font-size: 32px; display: block; margin-bottom: 8px; }

    #roomLogModal .modal-header {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #fff;
        border-radius: 12px 12px 0 0;
    }

    #roomLogModal .modal-header .modal-title { color: #fff; }
    #roomLogModal .modal-header .btn-close { filter: invert(1); }
    #roomLogModal .modal-content { border-radius: 14px; overflow: hidden; border: none; }

    .booking-status-chip {
        display: inline-flex;
        align-items: center;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }

    .chip-pending              { background: #fef9c3; color: #854d0e; }
    .chip-confirmed            { background: #dbeafe; color: #1e40af; }
    .chip-checked_in           { background: #dcfce7; color: #166534; }
    .chip-inspection_requested { background: #cffafe; color: #155e75; }
    .chip-checked_out          { background: #f3f4f6; color: #374151; }
    .chip-completed            { background: #f0fdf4; color: #166534; }
</style>

<div class="modal fade" id="roomLogModal" tabindex="-1" aria-labelledby="roomLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roomLogModalLabel">
                    <i class="bx bx-hotel me-1"></i>
                    Phòng <span id="modalRoomNumber"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <div id="roomLogContainer">
                    <p class="text-center text-muted"><i class="bx bx-loader-alt bx-spin"></i> Đang tải dữ liệu...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const bookingAdminUrl = '{{ rtrim(url("admin/bookings"), "/") }}/';

    function openRoomLogModal(roomId, roomNumber, date) {
        document.getElementById('modalRoomNumber').innerText = roomNumber + ' — ' + date;
        var modal = new bootstrap.Modal(document.getElementById('roomLogModal'));
        modal.show();

        var container = document.getElementById('roomLogContainer');
        container.innerHTML = '<p class="text-center text-muted py-4"><i class="bx bx-loader-alt bx-spin"></i> Đang tải dữ liệu...</p>';

        fetch(`/admin/rooms/${roomId}/action-logs?date=${date}`)
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (!data.success) {
                    container.innerHTML = '<p class="text-center text-danger"><i class="bx bx-error-circle"></i> Không thể tải dữ liệu.</p>';
                    return;
                }

                let html = '';

                // ── BOOKING SECTION ──────────────────────────────────────
                html += '<p class="room-modal-section-title"><i class="bx bx-calendar-check"></i> Booking trong ngày</p>';

                if (data.bookings && data.bookings.length > 0) {
                    data.bookings.forEach(b => {
                        const statusClass = 'chip-' + b.status;
                        html += `
                        <div class="booking-modal-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <a href="${bookingAdminUrl}${b.id}" target="_blank"
                                   style="font-weight:700;font-size:14px;color:#2563eb;text-decoration:none;">
                                    #${b.booking_code}
                                </a>
                                <span class="booking-status-chip ${statusClass}">${b.status_label}</span>
                            </div>
                            <div class="bm-row">
                                <i class="bx bx-user"></i>
                                <span class="bm-label">Khách:</span>
                                <span class="bm-val">${b.guest_name}</span>
                            </div>
                            <div class="bm-row">
                                <i class="bx bx-log-in"></i>
                                <span class="bm-label">Nhận phòng:</span>
                                <span class="bm-val">${b.check_in_at}</span>
                            </div>
                            <div class="bm-row">
                                <i class="bx bx-log-out"></i>
                                <span class="bm-label">Trả phòng:</span>
                                <span class="bm-val">${b.check_out_at}</span>
                            </div>
                            <div class="bm-row">
                                <i class="bx bx-group"></i>
                                <span class="bm-label">Số khách:</span>
                                <span class="bm-val">${b.adult_count} người lớn${b.child_count ? ' · ' + b.child_count + ' trẻ em' : ''}</span>
                            </div>
                        </div>`;
                    });
                } else {
                    html += `<div class="modal-empty-state"><i class="bx bx-calendar-x"></i>Không có booking nào trong ngày này</div>`;
                }

                // ── ACTION LOG SECTION ───────────────────────────────────
                html += '<hr style="margin: 18px 0;">';
                html += '<p class="room-modal-section-title"><i class="bx bx-history"></i> Lịch sử hành động nhân viên</p>';

                if (data.logs && data.logs.length > 0) {
                    html += '<div>';
                    data.logs.forEach(log => {
                        const noteHtml = log.note ? `<div class="log-note">${log.note}</div>` : '';
                        const editBtn = log.can_edit
                            ? `<button class="btn btn-sm btn-outline-secondary ms-auto" style="font-size:11px;padding:2px 10px;" onclick="editLog(${log.id})">Sửa ghi chú</button>`
                            : '';
                        html += `
                        <div class="log-item">
                            <span class="log-time-badge">${log.action_time}</span>
                            <div class="log-body">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="log-who">${log.user_name}</span>
                                    <span class="log-role">${log.user_role}</span>
                                    ${editBtn}
                                </div>
                                <div id="log-text-${log.id}">${noteHtml}</div>
                                <div class="mt-2 d-none" id="log-edit-${log.id}">
                                    <textarea class="form-control mb-1" style="font-size:13px;" id="log-input-${log.id}">${log.note ?? ''}</textarea>
                                    <button class="btn btn-sm btn-success me-1" onclick="saveLog(${log.id})">Lưu</button>
                                    <button class="btn btn-sm btn-light" onclick="cancelEdit(${log.id})">Huỷ</button>
                                </div>
                            </div>
                        </div>`;
                    });
                    html += '</div>';
                } else {
                    html += `<div class="modal-empty-state"><i class="bx bx-note"></i>Chưa có nhật ký hành động nào trong ngày này</div>`;
                }

                container.innerHTML = html;
            })
            .catch(err => {
                container.innerHTML = `<p class="text-center text-danger"><i class="bx bx-error-circle"></i> Lỗi: ${err.message}</p>`;
            });
    }

    function editLog(id) {
        document.getElementById(`log-text-${id}`).classList.add('d-none');
        document.getElementById(`log-edit-${id}`).classList.remove('d-none');
    }

    function cancelEdit(id) {
        document.getElementById(`log-text-${id}`).classList.remove('d-none');
        document.getElementById(`log-edit-${id}`).classList.add('d-none');
    }

    function saveLog(id) {
        var note = document.getElementById(`log-input-${id}`).value;
        fetch(`/admin/room-action-logs/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ note: note })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById(`log-text-${id}`).innerHTML = `<div class="log-note">${note}</div>`;
                cancelEdit(id);
            } else {
                alert(data.message || 'Lỗi khi cập nhật.');
            }
        });
    }
</script>

@endsection
