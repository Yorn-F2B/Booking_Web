@extends('layouts.admin')

@section('title', 'Phân công lễ tân')

@section('content')
    @php
        $roleLabels = [
            'owner' => 'Phụ trách chính',
            'check_in' => 'Check-in',
            'check_out' => 'Check-out',
            'payment' => 'Thanh toán',
            'support' => 'Hỗ trợ',
        ];

        $statusLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đã nhận phòng',
            'inspection_requested' => 'Chờ kiểm tra',
            'checked_out' => 'Đã trả phòng',
            'completed' => 'Hoàn tất',
            'canceled' => 'Đã hủy',
            'cancelled' => 'Đã hủy',
        ];
    @endphp

    <div class="admin-wrapper">
        <main class="admin-content">
            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.staff-assignments.index') }}">Phân công nhân sự</a> /
                Lễ tân
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Phân công lễ tân</h2>
                </div>

                <a href="{{ route('admin.staff-assignments.index') }}" class="btn btn-outline-secondary">Quay lại</a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Không thể lưu phân công:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="settings-section mb-3">
                <form method="GET" action="{{ route('admin.staff-assignments.receptionists') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tìm booking / khách / SĐT</label>
                        <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}" placeholder="VD: BK0001, An, 098...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Trạng thái booking</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Lễ tân</label>
                        <select name="assigned_staff_id" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="none" {{ request('assigned_staff_id') === 'none' ? 'selected' : '' }}>Chưa gán</option>
                            @foreach ($receptionists as $receptionist)
                                <option value="{{ $receptionist->id }}" {{ request('assigned_staff_id') == $receptionist->id ? 'selected' : '' }}>
                                    {{ $receptionist->staff->full_name ?? $receptionist->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-primary w-100">Lọc</button>
                        <a href="{{ route('admin.staff-assignments.receptionists') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <div class="settings-section">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Booking</th>
                                <th>Khách</th>
                                <th>Thời gian</th>
                                <th>Trạng thái</th>
                                <th>Đang gán</th>
                                <th style="min-width: 330px;">Gán / đổi lễ tân</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookings as $booking)
                                @php
                                    $customerName = trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? '')) ?: 'Chưa có tên';
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.bookings.show', $booking->id) }}" class="fw-bold text-decoration-none">
                                            {{ $booking->booking_code }}
                                        </a>
                                        <div class="small text-muted">Tạo bởi: {{ $booking->creator->name ?? '---' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $customerName }}</div>
                                        <div class="small text-muted">{{ $booking->customer->phone ?? '---' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ optional($booking->check_in_at)->format('d/m/Y H:i') }}</div>
                                        <div class="small text-muted">→ {{ optional($booking->check_out_at)->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $statusLabels[$booking->status] ?? $booking->status }}</span>
                                    </td>
                                    <td>
                                        @forelse ($booking->activeStaffAssignments as $assignment)
                                            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                                <span>
                                                    <strong>{{ $assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---' }}</strong>
                                                    <small class="text-muted">({{ $roleLabels[$assignment->role_in_booking] ?? $assignment->role_in_booking }})</small>
                                                </span>
                                                <form action="{{ route('admin.staff-assignments.receptionists.cancel', $assignment->id) }}" method="POST" onsubmit="return confirm('Hủy phân công này?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-outline-danger">Hủy</button>
                                                </form>
                                            </div>
                                        @empty
                                            <span class="text-muted">Chưa gán</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.staff-assignments.receptionists.store') }}" method="POST" class="row g-2">
                                            @csrf
                                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">

                                            <div class="col-md-5">
                                                <select name="staff_id" class="form-select form-select-sm" required>
                                                    <option value="">Chọn lễ tân</option>
                                                    @foreach ($receptionists as $receptionist)
                                                        <option value="{{ $receptionist->id }}">{{ $receptionist->staff->full_name ?? $receptionist->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-4">
                                                <select name="role_in_booking" class="form-select form-select-sm" required>
                                                    @foreach ($roleLabels as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <button class="btn btn-sm btn-primary w-100">Lưu</button>
                                            </div>

                                            <div class="col-12">
                                                <input type="text" name="note" class="form-control form-control-sm" placeholder="Ghi chú phân công nếu cần">
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Không có booking phù hợp.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $bookings->links() }}
                </div>
            </div>
        </main>
    </div>
@endsection
