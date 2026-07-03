@extends('layouts.admin')

@section('title', 'Sơ đồ phòng')

@section('content')

    @php
        $statusLabels = [
            'available' => 'Trống',
            'reserved' => 'Đặt trước',
            'occupied' => 'Đang ở',
            'inspection' => 'Chờ kiểm tra',
            'cleaning' => 'Đang dọn',
            'maintenance' => 'Bảo trì',
        ];

        $statusFullLabels = [
            'available' => 'Còn trống',
            'reserved' => 'Đã đặt trước',
            'occupied' => 'Đang sử dụng',
            'inspection' => 'Chờ kiểm tra',
            'cleaning' => 'Đang dọn dẹp',
            'maintenance' => 'Bảo trì',
        ];

        $statusClasses = [
            'available' => 'room-card-available',
            'reserved' => 'room-card-reserved',
            'occupied' => 'room-card-occupied',
            'inspection' => 'room-card-inspection',
            'cleaning' => 'room-card-cleaning',
            'maintenance' => 'room-card-maintenance',
        ];

        $statusSelectClasses = [
            'available' => 'room-status-available',
            'reserved' => 'room-status-reserved',
            'occupied' => 'room-status-occupied',
            'inspection' => 'room-status-inspection',
            'cleaning' => 'room-status-cleaning',
            'maintenance' => 'room-status-maintenance',
        ];

        $roomCollection = $rooms;

        $totalRooms = $roomCollection->count();
        $availableRooms = $roomCollection->where('status', 'available')->count();
        $reservedRooms = $roomCollection->where('status', 'reserved')->count();
        $occupiedRooms = $roomCollection->where('status', 'occupied')->count();
        $inspectionRooms = $roomCollection->where('status', 'inspection')->count();
        $cleaningRooms = $roomCollection->where('status', 'cleaning')->count();
        $maintenanceRooms = $roomCollection->where('status', 'maintenance')->count();

        $roomsByFloor = $roomCollection
            ->groupBy(function ($room) {
                return $room->floor_number ?: 'unknown';
            })
            ->sortKeysDesc();
    @endphp

    <style>
        .room-map-page {
            color: #0f172a;
        }

        .room-map-toolbar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
            margin-bottom: 18px;
        }

        .room-map-title h2 {
            margin-bottom: 4px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .room-map-title p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .room-filter-box {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 16px;
            background: #fff;
            margin-bottom: 18px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .room-summary-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(120px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .room-summary-item {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 12px 14px;
            min-height: 78px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.035);
        }

        .room-summary-item span {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 6px;
            white-space: nowrap;
        }

        .room-summary-item strong {
            display: block;
            font-size: 26px;
            line-height: 1;
            font-weight: 800;
            color: #0f172a;
        }

        .room-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            display: inline-block;
            flex: 0 0 auto;
        }

        .dot-total {
            background: #0f172a;
        }

        .dot-available {
            background: #16a34a;
        }

        .dot-reserved {
            background: #f59e0b;
        }

        .dot-occupied {
            background: #ef4444;
        }

        .dot-inspection {
            background: #06b6d4;
        }

        .dot-cleaning {
            background: #3b82f6;
        }

        .dot-maintenance {
            background: #64748b;
        }

        .room-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .room-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            background: #fff;
            padding: 8px 12px;
            font-size: 13px;
            color: #475569;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.035);
        }

        .floor-section {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.045);
        }

        .floor-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #eef2f7;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }

        .floor-head h5 {
            margin: 0;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .floor-head span {
            color: #64748b;
            font-size: 13px;
        }

        .room-map-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 14px;
        }

        .room-card {
            position: relative;
            border-radius: 20px;
            border: 1px solid transparent;
            padding: 15px;
            min-height: 188px;
            overflow: hidden;
            transition: 0.18s ease;
        }

        .room-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
        }

        .room-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .room-number {
            font-size: 28px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .room-floor-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.85);
            color: #334155;
            white-space: nowrap;
        }

        .room-category {
            font-weight: 700;
            margin-bottom: 6px;
            color: #1e293b;
        }

        .room-note {
            min-height: 38px;
            font-size: 13px;
            color: #64748b;
            margin-bottom: 12px;
        }

        .room-status-select {
            font-weight: 800;
            border-radius: 999px;
            border-width: 1px;
            padding: 7px 12px;
            cursor: pointer;
            font-size: 13px;
            box-shadow: none !important;
        }

        .room-actions {
            display: flex;
            justify-content: flex-end;
            gap: 7px;
            margin-top: 12px;
        }

        .room-action-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.8);
            color: #334155;
            transition: 0.15s ease;
        }

        .room-action-btn:hover {
            background: #fff;
            color: #0f172a;
            transform: translateY(-1px);
        }

        .room-action-btn.danger {
            color: #dc2626;
        }

        .room-action-btn.danger:hover {
            background: #fee2e2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .room-card-available {
            background: linear-gradient(145deg, #ecfdf5, #d1fae5);
            border-color: #bbf7d0;
        }

        .room-card-reserved {
            background: linear-gradient(145deg, #fffbeb, #fef3c7);
            border-color: #fde68a;
        }

        .room-card-occupied {
            background: linear-gradient(145deg, #fef2f2, #fee2e2);
            border-color: #fecaca;
        }

        .room-card-inspection {
            background: linear-gradient(145deg, #ecfeff, #cffafe);
            border-color: #a5f3fc;
        }

        .room-card-cleaning {
            background: linear-gradient(145deg, #eff6ff, #dbeafe);
            border-color: #bfdbfe;
        }

        .room-card-maintenance {
            background: linear-gradient(145deg, #f8fafc, #e2e8f0);
            border-color: #cbd5e1;
        }

        .room-status-available {
            color: #166534;
            background-color: #dcfce7;
            border-color: #86efac;
        }

        .room-status-reserved {
            color: #92400e;
            background-color: #fef3c7;
            border-color: #fcd34d;
        }

        .room-status-occupied {
            color: #991b1b;
            background-color: #fee2e2;
            border-color: #fca5a5;
        }

        .room-status-inspection {
            color: #155e75;
            background-color: #cffafe;
            border-color: #67e8f9;
        }

        .room-status-cleaning {
            color: #1d4ed8;
            background-color: #dbeafe;
            border-color: #93c5fd;
        }

        .room-status-maintenance {
            color: #334155;
            background-color: #e2e8f0;
            border-color: #cbd5e1;
        }

        .empty-room-map {
            border: 1px dashed #cbd5e1;
            border-radius: 20px;
            padding: 40px 20px;
            text-align: center;
            color: #64748b;
            background: #fff;
        }

        @media (max-width: 1200px) {
            .room-summary-grid {
                grid-template-columns: repeat(4, minmax(120px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .room-map-toolbar {
                grid-template-columns: 1fr;
            }

            .room-summary-grid {
                grid-template-columns: repeat(2, minmax(120px, 1fr));
            }

            .room-map-grid {
                grid-template-columns: 1fr;
            }

            .floor-head {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <div class="admin-wrapper room-map-page">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Phòng
            </p>

            <div class="room-map-toolbar">

                <div class="room-map-title">
                    <h2>Sơ đồ phòng</h2>
                    <p>Theo dõi nhanh trạng thái phòng theo tầng, đổi trạng thái ngay trên từng phòng.</p>
                </div>

                <a href="{{ route('admin.rooms.create') }}" class="btn btn-gold">
                    <i class="bx bx-plus me-1"></i>
                    Thêm phòng
                </a>

            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="room-filter-box">

                <form action="{{ route('admin.rooms.index') }}" method="GET">

                    <div class="row g-3 align-items-end">

                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <label class="form-label">Tìm số phòng</label>
                            <input type="text" name="room_number" class="form-control"
                                value="{{ request('room_number') }}" placeholder="Ví dụ: 101, 202...">
                        </div>

                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <label class="form-label">Hạng phòng</label>
                            <select name="room_category_id" class="form-select">
                                <option value="">Tất cả hạng phòng</option>

                                @foreach ($roomCategories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ request('room_category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>

                                @foreach ($statusFullLabels as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-xl-2 col-lg-4 col-md-6">
                            <label class="form-label">Tầng</label>
                            <select name="floor_number" class="form-select">
                                <option value="">Tất cả tầng</option>

                                @for ($i = 1; $i <= $maxFloor; $i++)
                                    <option value="{{ $i }}" {{ request('floor_number') == $i ? 'selected' : '' }}>
                                        Tầng {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-xl-1 col-lg-4 col-md-6">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bx bx-filter-alt"></i>
                            </button>
                        </div>

                        <div class="col-xl-1 col-lg-4 col-md-6">
                            <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary w-100">
                                Reset
                            </a>
                        </div>

                    </div>

                </form>

            </div>

            <div class="room-summary-grid">

                <div class="room-summary-item">
                    <span><i class="room-dot dot-total"></i> Tổng phòng</span>
                    <strong>{{ $totalRooms }}</strong>
                </div>

                <div class="room-summary-item">
                    <span><i class="room-dot dot-available"></i> Trống</span>
                    <strong>{{ $availableRooms }}</strong>
                </div>

                <div class="room-summary-item">
                    <span><i class="room-dot dot-reserved"></i> Đặt trước</span>
                    <strong>{{ $reservedRooms }}</strong>
                </div>

                <div class="room-summary-item">
                    <span><i class="room-dot dot-occupied"></i> Đang ở</span>
                    <strong>{{ $occupiedRooms }}</strong>
                </div>

                <div class="room-summary-item">
                    <span><i class="room-dot dot-inspection"></i> Kiểm tra</span>
                    <strong>{{ $inspectionRooms }}</strong>
                </div>

                <div class="room-summary-item">
                    <span><i class="room-dot dot-cleaning"></i> Đang dọn</span>
                    <strong>{{ $cleaningRooms }}</strong>
                </div>

                <div class="room-summary-item">
                    <span><i class="room-dot dot-maintenance"></i> Bảo trì</span>
                    <strong>{{ $maintenanceRooms }}</strong>
                </div>

            </div>

            <div class="room-legend">

                @foreach ($statusLabels as $key => $label)
                    <div class="room-legend-item">
                        <i class="room-dot dot-{{ $key }}"></i>
                        {{ $label }}
                    </div>
                @endforeach

            </div>

            @forelse ($roomsByFloor as $floor => $floorRooms)

                <section class="floor-section">

                    <div class="floor-head">

                        <h5>
                            <i class="bx bx-buildings"></i>
                            {{ $floor === 'unknown' ? 'Chưa rõ tầng' : 'Tầng ' . $floor }}
                        </h5>

                        <span>
                            {{ $floorRooms->count() }} phòng
                        </span>

                    </div>

                    <div class="room-map-grid">

                        @foreach ($floorRooms->sortBy('room_number') as $room)

                            @php
                                $currentCardClass = $statusClasses[$room->status] ?? 'room-card-maintenance';
                                $currentSelectClass = $statusSelectClasses[$room->status] ?? 'room-status-maintenance';
                                $currentStatusLabel = $statusFullLabels[$room->status] ?? 'Không xác định';
                            @endphp

                            <article class="room-card {{ $currentCardClass }}">

                                <div class="room-card-top">

                                    <div class="room-number">
                                        {{ $room->room_number }}
                                    </div>

                                    <span class="room-floor-badge">
                                        <i class="bx bx-layer"></i>
                                        {{ $room->floor_number ? 'Tầng ' . $room->floor_number : 'Chưa rõ' }}
                                    </span>

                                </div>

                                <div class="room-category">
                                    {{ $room->category->name ?? 'Không xác định' }}
                                </div>

                                <div class="room-note">
                                    @if ($room->note)
                                        {{ \Illuminate\Support\Str::limit($room->note, 55) }}
                                    @else
                                        Không có ghi chú
                                    @endif
                                </div>

                                <form action="{{ route('admin.rooms.update-status', $room->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')

                                    <select name="status"
                                        class="form-select form-select-sm room-status-select {{ $currentSelectClass }}"
                                        title="{{ $currentStatusLabel }}"
                                        onchange="openStatusModal(this, {{ $room->id }}, '{{ $room->room_number }}')">

                                        @foreach ($statusFullLabels as $key => $label)
                                            <option value="{{ $key }}" {{ $room->status == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach

                                    </select>

                                </form>

                                <div class="room-actions">

                                    <a href="{{ route('admin.rooms.show', $room->id) }}" class="room-action-btn"
                                        title="Xem chi tiết">
                                        <i class="bx bx-show"></i>
                                    </a>

                                    <a href="{{ route('admin.rooms.edit', $room->id) }}" class="room-action-btn"
                                        title="Sửa phòng">
                                        <i class="bx bx-edit"></i>
                                    </a>

                                    <form action="{{ route('admin.rooms.destroy', $room->id) }}" method="POST"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa phòng {{ $room->room_number }} không?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="room-action-btn danger" title="Xóa phòng">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>

                                </div>

                            </article>

                        @endforeach

                    </div>

                </section>

            @empty

                <div class="empty-room-map">
                    <i class="bx bx-hotel fs-1 d-block mb-2"></i>
                    Không có phòng phù hợp với bộ lọc hiện tại.
                </div>

            @endforelse

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

{{-- Modal đổi trạng thái phòng --}}
<style>
    #statusModal .modal-header {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #fff;
        border-radius: 12px 12px 0 0;
    }
    #statusModal .modal-header .modal-title { color: #fff; }
    #statusModal .modal-header .btn-close { filter: invert(1); }
    #statusModal .modal-content { border-radius: 14px; overflow: hidden; border: none; }
    .status-modal-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 12px; border-radius: 999px; font-size: 13px; font-weight: 700;
    }
    .needs-schedule { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .no-schedule    { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
</style>

<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-transfer me-1"></i>
                    Đổi trạng thái — Phòng <span id="smRoomNumber"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="statusForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body" style="padding:20px">

                    <div class="mb-3">
                        <div class="fw-semibold mb-1" style="font-size:13px;color:#64748b">Trạng thái mới</div>
                        <div id="smNewStatusLabel"></div>
                    </div>

                    <input type="hidden" name="status" id="smStatusInput">

                    {{-- Schedule fields: chỉ hiện với maintenance/cleaning/inspection --}}
                    <div id="smScheduleFields">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label" style="font-size:12px;font-weight:600;color:#64748b">
                                    Từ ngày giờ
                                </label>
                                <input type="text" name="status_from" id="smStatusFrom"
                                    class="form-control form-control-sm" autocomplete="off" readonly placeholder="dd/mm/yyyy HH:MM">
                                <input type="hidden" name="status_from_raw" id="smStatusFromRaw">
                            </div>
                            <div class="col-6">
                                <label class="form-label" style="font-size:12px;font-weight:600;color:#64748b">
                                    Đến ngày giờ
                                </label>
                                <input type="text" name="status_until" id="smStatusUntil"
                                    class="form-control form-control-sm" autocomplete="off" readonly placeholder="dd/mm/yyyy HH:MM">
                                <input type="hidden" name="status_until_raw" id="smStatusUntilRaw">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-size:12px;font-weight:600;color:#64748b">
                                Lý do <span class="text-muted fw-normal">(tuỳ chọn)</span>
                            </label>
                            <textarea name="note" id="smNote" class="form-control form-control-sm"
                                rows="2" placeholder="Ví dụ: sửa điều hoà, tổng vệ sinh..."></textarea>
                        </div>
                    </div>

                    {{-- Simple confirm for available --}}
                    <div id="smSimpleMsg" class="text-muted" style="font-size:13px;display:none">
                        Phòng sẽ được chuyển về <strong>Còn trống</strong> ngay lập tức.
                    </div>

                </div>
                <div class="modal-footer" style="gap:8px">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bx bx-check me-1"></i>Xác nhận
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
// Locale tiếng Việt nhúng trực tiếp — không cần CDN ngoài
flatpickr.localize({
    weekdays: {
        shorthand: ['CN','T2','T3','T4','T5','T6','T7'],
        longhand:  ['Chủ Nhật','Thứ Hai','Thứ Ba','Thứ Tư','Thứ Năm','Thứ Sáu','Thứ Bảy']
    },
    months: {
        shorthand: ['Th1','Th2','Th3','Th4','Th5','Th6','Th7','Th8','Th9','Th10','Th11','Th12'],
        longhand:  ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6',
                    'Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12']
    },
    firstDayOfWeek: 1,
    rangeSeparator: ' đến ',
    time_24hr: true,
});

(function () {
    const dtOpts = {
        enableTime: true,
        dateFormat: 'd/m/Y H:i',
        time_24hr: true,
        minuteIncrement: 15,
        allowInput: false,
        disableMobile: true,
    };

    let fpFrom = null, fpUntil = null;

    function initPickers() {
        if (fpFrom)  { fpFrom.destroy();  fpFrom  = null; }
        if (fpUntil) { fpUntil.destroy(); fpUntil = null; }

        fpFrom = flatpickr('#smStatusFrom', {
            ...dtOpts,
            onChange: function(dates) {
                if (dates[0] && fpUntil) fpUntil.set('minDate', dates[0]);
            }
        });
        fpUntil = flatpickr('#smStatusUntil', {
            ...dtOpts,
            onChange: function(dates) {
                if (dates[0] && fpFrom) fpFrom.set('maxDate', dates[0]);
            }
        });
    }

    const statusLabels = {
        available:   { text: 'Còn trống',    cls: 'no-schedule' },
        reserved:    { text: 'Đã đặt trước', cls: 'needs-schedule' },
        occupied:    { text: 'Đang sử dụng', cls: 'needs-schedule' },
        inspection:  { text: 'Chờ kiểm tra', cls: 'needs-schedule' },
        cleaning:    { text: 'Đang dọn dẹp', cls: 'needs-schedule' },
        maintenance: { text: 'Bảo trì',      cls: 'needs-schedule' },
    };

    const needsSchedule = ['maintenance', 'cleaning', 'inspection', 'reserved', 'occupied'];
    let _selectEl = null, _prevValue = null;

    window.openStatusModal = function (selectEl, roomId, roomNumber) {
        const newStatus = selectEl.value;
        _selectEl  = selectEl;
        _prevValue = [...selectEl.options].find(o => o.defaultSelected)?.value ?? selectEl.dataset.original;

        document.getElementById('smRoomNumber').textContent = roomNumber;
        document.getElementById('smStatusInput').value = newStatus;
        document.getElementById('smNote').value = '';

        const info = statusLabels[newStatus] || { text: newStatus, cls: 'no-schedule' };
        document.getElementById('smNewStatusLabel').innerHTML =
            `<span class="status-modal-badge ${info.cls}">${info.text}</span>`;

        const showSchedule = needsSchedule.includes(newStatus);
        document.getElementById('smScheduleFields').style.display = showSchedule ? '' : 'none';
        document.getElementById('smSimpleMsg').style.display      = showSchedule ? 'none' : '';

        document.getElementById('statusForm').action = `/admin/rooms/${roomId}/status`;

        const modal = new bootstrap.Modal(document.getElementById('statusModal'));

        // Khởi tạo flatpickr sau khi modal hiển thị
        document.getElementById('statusModal').addEventListener('shown.bs.modal', function onShown() {
            initPickers();
            // Điền giờ hiện tại cho "Từ ngày giờ"
            if (fpFrom) fpFrom.setDate(new Date(), true);
            if (fpUntil) fpUntil.clear();
            document.getElementById('statusModal').removeEventListener('shown.bs.modal', onShown);
        }, { once: true });

        document.getElementById('statusModal').addEventListener('hide.bs.modal', function onHide() {
            if (_selectEl && _prevValue !== null) {
                _selectEl.value = _prevValue;
                updateSelectStyle(_selectEl);
            }
            document.getElementById('statusModal').removeEventListener('hide.bs.modal', onHide);
        }, { once: true });

        modal.show();
    };

    function updateSelectStyle(sel) {
        const map = {
            available:'room-status-available', reserved:'room-status-reserved',
            occupied:'room-status-occupied',   inspection:'room-status-inspection',
            cleaning:'room-status-cleaning',   maintenance:'room-status-maintenance',
        };
        Object.values(map).forEach(c => sel.classList.remove(c));
        sel.classList.add(map[sel.value] || 'room-status-maintenance');
    }
})();
</script>

@endsection