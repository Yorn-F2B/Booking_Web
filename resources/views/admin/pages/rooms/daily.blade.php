@extends('layouts.admin')

@section('title', 'Quản lý phòng theo ngày')

@section('content')
    <style>
        .room-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            padding: 16px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
        }

        .room-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .room-number {
            font-size: 24px;
            font-weight: 800;
            color: #1f2937;
        }

        .room-category {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 12px;
        }

        .booking-info {
            font-size: 12px;
            color: #64748b;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #f1f5f9;
        }

        .date-picker-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
        }

        .stats-card {
            background: #fff;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .stats-number {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
        }

        .stats-label {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
    </style>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.rooms.index') }}">Phòng</a> /
                Quản lý theo ngày
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Quản lý phòng theo ngày</h2>
                    <p>Xem trạng thái phòng theo ngày cụ thể</p>
                </div>

                <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">
                    Quay lại danh sách phòng
                </a>

            </div>

            <div class="date-picker-container">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Chọn ngày xem</label>
                        <input type="date"
                               id="datePicker"
                               class="form-control"
                               value="{{ $selectedDate }}"
                               min="{{ now()->subDays(30)->format('Y-m-d') }}"
                               max="{{ now()->addDays(90)->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Lọc theo hạng phòng</label>
                        <select id="categoryFilter" class="form-select">
                            <option value="all" {{ $selectedCategory === 'all' ? 'selected' : '' }}>Tất cả hạng phòng</option>
                            @foreach($roomCategories as $category)
                                <option value="{{ $category->id }}" {{ $selectedCategory == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stats-card">
                                    <div class="stats-number text-success">{{ collect($roomStatuses)->where('status', 'available')->count() }}</div>
                                    <div class="stats-label">Trống</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-card">
                                    <div class="stats-number text-primary">{{ collect($roomStatuses)->where('status', 'occupied')->count() }}</div>
                                    <div class="stats-label">Đang dùng</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-card">
                                    <div class="stats-number text-warning">{{ collect($roomStatuses)->where('status', 'booked')->count() }}</div>
                                    <div class="stats-label">Đã đặt</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-card">
                                    <div class="stats-number text-danger">{{ collect($roomStatuses)->where('status', 'maintenance')->count() }}</div>
                                    <div class="stats-label">Bảo trì</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                @foreach($rooms as $room)
                    @isset($roomStatuses[$room->id])
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="room-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="room-number">{{ $room->room_number }}</div>
                                        <div class="room-category">{{ $room->category->name ?? 'Chưa phân loại' }}</div>
                                    </div>
                                    <div class="fs-4">{{ $roomStatuses[$room->id]['icon'] }}</div>
                                </div>

                                <div class="status-badge {{ $roomStatuses[$room->id]['color'] }}">
                                    {{ $roomStatuses[$room->id]['label'] }}
                                </div>

                                @if($roomStatuses[$room->id]['booking'])
                                    <div class="booking-info">
                                        <div><strong>Booking:</strong> {{ $roomStatuses[$room->id]['booking']->booking_code }}</div>
                                        <div><strong>Khách:</strong> {{ $roomStatuses[$room->id]['booking']->customer->first_name ?? '' }} {{ $roomStatuses[$room->id]['booking']->customer->last_name ?? '' }}</div>
                                        <div><strong>Nhận:</strong> {{ \Carbon\Carbon::parse($roomStatuses[$room->id]['booking']->check_in_at)->format('d/m/Y H:i') }}</div>
                                        <div><strong>Trả:</strong> {{ \Carbon\Carbon::parse($roomStatuses[$room->id]['booking']->check_out_at)->format('d/m/Y H:i') }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endisset
                @endforeach
            </div>

        </main>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const datePicker = document.getElementById('datePicker');
            const categoryFilter = document.getElementById('categoryFilter');

            function updateUrl() {
                const selectedDate = datePicker ? datePicker.value : '';
                const selectedCategory = categoryFilter ? categoryFilter.value : 'all';

                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('date', selectedDate);
                currentUrl.searchParams.set('category', selectedCategory);
                window.location.href = currentUrl.toString();
            }

            if (datePicker) {
                datePicker.addEventListener('change', updateUrl);
            }

            if (categoryFilter) {
                categoryFilter.addEventListener('change', updateUrl);
            }
        });
    </script>

@endsection
