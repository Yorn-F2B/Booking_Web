@extends('layouts.admin')

@section('title', 'Phân công lễ tân')

@section('content')
    @php
        $statusLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đã nhận phòng',
            'inspection_requested' => 'Chờ kiểm tra',
        ];
    @endphp

    <style>
        .assignment-owner-box {
            min-width: 220px;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }
        .assignment-owner-box.is-assigned {
            background: #eefbf4;
            border-color: #b7e4c7;
        }
        .assignment-owner-name {
            font-weight: 700;
            color: #0f172a;
        }
        .assignment-owner-meta {
            font-size: .78rem;
            color: #64748b;
        }
        .assignment-flow-note {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1e3a8a;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .assignment-flow-note strong {
            color: #172554;
        }
        .legacy-assignment-warning {
            margin-top: 6px;
            font-size: .78rem;
            color: #b45309;
        }
    </style>

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
                    <p class="text-muted mb-0">Mỗi booking chỉ có một lễ tân chịu trách nhiệm chính cho toàn bộ vòng đời xử lý.</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.staff-assignments.status', ['type' => 'receptionist']) }}" class="btn btn-outline-primary">
                        <i class="bx bx-list-check me-1"></i> Tình trạng phân công
                    </a>
                    <a href="{{ route('admin.staff-assignments.index') }}" class="btn btn-outline-secondary">Quay lại</a>
                </div>
            </div>

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

            <div class="assignment-flow-note mb-3">
                <strong>Quy tắc:</strong>
                Khi gán một booking cho lễ tân, người đó chịu trách nhiệm <strong>toàn bộ quy trình</strong> của booking trong phạm vi nghiệp vụ lễ tân:
                theo dõi đơn, check-in, khách lưu trú, dịch vụ/phụ thu, thanh toán, yêu cầu khách, sự cố liên quan booking, kiểm tra trước checkout và checkout.
                Khi đổi lễ tân, phân công cũ được dừng và toàn bộ booking chuyển sang người mới.
            </div>

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
                        <label class="form-label">Lễ tân đang phụ trách</label>
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
                                <th>Người chịu trách nhiệm</th>
                                <th style="min-width: 330px;">Gán / chuyển booking</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookings as $booking)
                                @php
                                    $customerName = trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? '')) ?: 'Chưa có tên';
                                    $activeAssignments = $booking->activeStaffAssignments;
                                    $primaryAssignment = $activeAssignments->sortByDesc('id')->first();
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
                                        @if ($primaryAssignment)
                                            <div class="assignment-owner-box is-assigned">
                                                <div class="assignment-owner-name">
                                                    {{ $primaryAssignment->staff->staff->full_name ?? $primaryAssignment->staff->name ?? '---' }}
                                                </div>
                                                <div class="assignment-owner-meta">Phụ trách toàn bộ booking</div>
                                                <div class="assignment-owner-meta">
                                                    Gán {{ optional($primaryAssignment->created_at)->format('d/m/Y H:i') ?? '---' }}
                                                </div>
                                            </div>
                                            @if ($activeAssignments->count() > 1 || $activeAssignments->contains(fn ($item) => $item->role_in_booking !== 'owner'))
                                                <div class="legacy-assignment-warning">
                                                    Có dữ liệu phân công kiểu cũ. Bấm Lưu để chuẩn hóa booking về một người phụ trách toàn bộ.
                                                </div>
                                            @endif
                                        @else
                                            <div class="assignment-owner-box">
                                                <div class="assignment-owner-name text-muted">Chưa gán</div>
                                                <div class="assignment-owner-meta">Lễ tân chưa có người chịu trách nhiệm chính.</div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.staff-assignments.receptionists.store') }}" method="POST" class="row g-2">
                                            @csrf
                                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">

                                            <div class="col-md-8">
                                                <select name="staff_id" class="form-select form-select-sm" required>
                                                    <option value="">Chọn lễ tân chịu trách nhiệm</option>
                                                    @foreach ($receptionists as $receptionist)
                                                        <option value="{{ $receptionist->id }}" @selected($primaryAssignment && (int) $primaryAssignment->staff_id === (int) $receptionist->id)>
                                                            {{ $receptionist->staff->full_name ?? $receptionist->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-4">
                                                <button class="btn btn-sm btn-primary w-100">
                                                    {{ $primaryAssignment ? 'Chuyển / lưu' : 'Gán booking' }}
                                                </button>
                                            </div>

                                            <div class="col-12">
                                                <input type="text" name="note" class="form-control form-control-sm" placeholder="Ghi chú bàn giao nếu cần">
                                            </div>
                                        </form>

                                        @if ($primaryAssignment)
                                            <form action="{{ route('admin.staff-assignments.receptionists.cancel', $primaryAssignment->id) }}" method="POST" class="mt-2" onsubmit="return confirm('Dừng phân công booking này? Sau khi dừng, booking sẽ trở về trạng thái chưa có lễ tân phụ trách.')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-danger">Dừng phân công</button>
                                            </form>
                                        @endif
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
