@extends('layouts.admin')

@section('title', 'Danh sách phòng')

@section('content')

    @php
        $statusLabels = [
            'available' => 'Còn trống',
            'reserved' => 'Đã đặt trước',
            'occupied' => 'Đang sử dụng',
            'inspection' => 'Chờ kiểm tra',
            'cleaning' => 'Đang dọn dẹp',
            'maintenance' => 'Bảo trì',
        ];

        $statusClasses = [
            'available' => 'room-status-available',
            'reserved' => 'room-status-reserved',
            'occupied' => 'room-status-occupied',
            'inspection' => 'room-status-inspection',
            'cleaning' => 'room-status-cleaning',
            'maintenance' => 'room-status-maintenance',
        ];
    @endphp

    <style>
        .room-status-select {
            font-weight: 700;
            border-radius: 999px;
            border-width: 1px;
            padding: 6px 12px;
            min-width: 155px;
            cursor: pointer;
        }

        .room-status-available {
            color: #0f5132;
            background-color: #d1e7dd;
            border-color: #a3cfbb;
        }

        .room-status-reserved {
            color: #664d03;
            background-color: #fff3cd;
            border-color: #ffda6a;
        }

        .room-status-occupied {
            color: #842029;
            background-color: #f8d7da;
            border-color: #f1aeb5;
        }

        .room-status-inspection {
            color: #055160;
            background-color: #cff4fc;
            border-color: #9eeaf9;
        }

        .room-status-cleaning {
            color: #084298;
            background-color: #cfe2ff;
            border-color: #9ec5fe;
        }

        .room-status-maintenance {
            color: #41464b;
            background-color: #e2e3e5;
            border-color: #c4c8cb;
        }

        .room-stat-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 14px 16px;
            background: #fff;
            height: 100%;
        }

        .room-stat-card span {
            display: block;
            font-size: 13px;
            color: #64748b;
        }

        .room-stat-card strong {
            display: block;
            font-size: 24px;
            margin-top: 4px;
        }

        .room-filter-box {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 16px;
            background: #fff;
            margin-bottom: 18px;
        }
    </style>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Danh sách phòng</h2>
                    <p>Quản lý vận hành phòng theo trạng thái thực tế trong khách sạn</p>
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

            @php
                $roomCollection = $rooms->getCollection();

                $totalRooms = $roomCollection->count();
                $availableRooms = $roomCollection->where('status', 'available')->count();
                $reservedRooms = $roomCollection->where('status', 'reserved')->count();
                $occupiedRooms = $roomCollection->where('status', 'occupied')->count();
                $inspectionRooms = $roomCollection->where('status', 'inspection')->count();
                $cleaningRooms = $roomCollection->where('status', 'cleaning')->count();
                $maintenanceRooms = $roomCollection->where('status', 'maintenance')->count();
            @endphp

            <div class="room-filter-box">

                <form action="{{ route('admin.rooms.index') }}" method="GET">

                    <div class="row g-3 align-items-end">

                        <div class="col-md-3">
                            <label class="form-label">Tìm số phòng</label>
                            <input type="text" name="room_number" class="form-control" value="{{ request('room_number') }}"
                                placeholder="Ví dụ: 101, 202...">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>

                                @foreach ($statusLabels as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Tầng</label>

                            <select name="floor_number" class="form-select">

                                <option value="">
                                    Tất cả tầng
                                </option>

                                @for ($i = 1; $i <= $maxFloor; $i++)
                                    <option value="{{ $i }}" {{ request('floor_number') == $i ? 'selected' : '' }}>
                                        Tầng {{ $i }}
                                    </option>
                                @endfor

                            </select>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bx bx-filter-alt me-1"></i>
                                Lọc
                            </button>
                        </div>

                        <div class="col-md-2">
                            <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary w-100">
                                Reset
                            </a>
                        </div>

                    </div>

                </form>

            </div>

            <div class="settings-section">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>
                                <th>ID</th>
                                <th>Số phòng</th>
                                <th>Loại phòng</th>
                                <th>Tầng</th>
                                <th>Trạng thái vận hành</th>
                                <th>Ghi chú</th>
                                <th class="text-end">Thao tác</th>
                            </tr>

                        </thead>

                        <tbody>

                            @forelse ($rooms as $room)

                                @php
                                    $currentStatusClass = $statusClasses[$room->status] ?? 'room-status-maintenance';
                                @endphp

                                <tr>

                                    <td>{{ $room->id }}</td>

                                    <td>
                                        <strong class="fs-6">{{ $room->room_number }}</strong>
                                    </td>

                                    <td>
                                        {{ $room->category->name ?? 'Không xác định' }}
                                    </td>

                                    <td>
                                        {{ $room->floor_number ?? '-' }}
                                    </td>

                                    <td style="min-width: 190px;">

                                        <form action="{{ route('admin.rooms.update-status', $room->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')

                                            <select name="status"
                                                class="form-select form-select-sm room-status-select {{ $currentStatusClass }}"
                                                onchange="this.form.submit()">

                                                @foreach ($statusLabels as $key => $label)
                                                    <option value="{{ $key }}" {{ $room->status == $key ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach

                                            </select>

                                        </form>

                                    </td>

                                    <td>
                                        @if ($room->note)
                                            {{ Str::limit($room->note, 45) }}
                                        @else
                                            <span class="text-muted">
                                                Không có ghi chú
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">

                                        <a href="{{ route('admin.rooms.show', $room->id) }}" class="btn btn-sm btn-outline-secondary">
                                            Xem
                                        </a>

                                        <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn btn-sm btn-outline-primary">
                                            Sửa
                                        </a>

                                        <form action="{{ route('admin.rooms.destroy', $room->id) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa phòng này không?')">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Xóa
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        Chưa có phòng nào
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    {{ $rooms->appends(request()->query())->links() }}
                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

@endsection