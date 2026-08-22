@extends('layouts.admin')

@section('title', 'Tình trạng phân công')

@section('content')
    @php
        $bookingStatusLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đã nhận phòng',
            'inspection_requested' => 'Chờ kiểm tra',
        ];
        $taskLabels = [
            'cleaning' => 'Dọn phòng',
            'inspection' => 'Kiểm tra phòng',
            'maintenance_support' => 'Hỗ trợ bảo trì',
        ];
        $taskStatusLabels = [
            'assigned' => 'Đã giao',
            'in_progress' => 'Đang làm',
        ];
    @endphp

    <style>
        .assignment-status-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }
        .assignment-status-stat {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 12px;
            padding: 14px 16px;
        }
        .assignment-status-stat span { display: block; color: #64748b; font-size: .82rem; }
        .assignment-status-stat strong { display: block; margin-top: 4px; font-size: 1.45rem; color: #0f172a; }
        .assignment-status-owner {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            background: #eefbf4;
            color: #166534;
            font-size: .82rem;
            font-weight: 600;
            margin: 2px 4px 2px 0;
        }
        .assignment-status-section + .assignment-status-section { margin-top: 16px; }
        @media (max-width: 991px) {
            .assignment-status-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575px) {
            .assignment-status-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="admin-wrapper">
        <main class="admin-content">
            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.staff-assignments.index') }}">Phân công nhân sự</a> /
                Tình trạng phân công
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Tình trạng phân công hiện tại</h2>
                    <p class="text-muted mb-0">Một nơi để kiểm tra nhanh booking, tầng và nhiệm vụ phòng đang được giao cho ai.</p>
                </div>
                <a href="{{ route('admin.staff-assignments.index') }}" class="btn btn-outline-secondary">Quay lại</a>
            </div>

            <div class="assignment-status-grid mb-3">
                @if ($canManageReceptionists)
                    <div class="assignment-status-stat">
                        <span>Booking đang có người phụ trách</span>
                        <strong>{{ $summary['bookings'] }}</strong>
                    </div>
                    <div class="assignment-status-stat">
                        <span>Lễ tân đang giữ booking</span>
                        <strong>{{ $summary['receptionists'] }}</strong>
                    </div>
                @endif
                @if ($canManageHousekeeping)
                    <div class="assignment-status-stat">
                        <span>Phân công tầng đang hiệu lực</span>
                        <strong>{{ $summary['floor_assignments'] }}</strong>
                    </div>
                    <div class="assignment-status-stat">
                        <span>Nhiệm vụ phòng hôm nay</span>
                        <strong>{{ $summary['room_tasks'] }}</strong>
                    </div>
                @endif
            </div>

            <div class="settings-section mb-3">
                <form method="GET" action="{{ route('admin.staff-assignments.status') }}" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Tìm booking / khách / nhân viên / phòng / tầng</label>
                        <input type="text" name="keyword" class="form-control" value="{{ $keyword }}" placeholder="VD: BK001, Lễ tân 1, phòng 201...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Loại phân công</label>
                        <select name="type" class="form-select">
                            <option value="all" @selected($type === 'all')>Tất cả</option>
                            @if ($canManageReceptionists)
                                <option value="receptionist" @selected($type === 'receptionist')>Booking → lễ tân</option>
                            @endif
                            @if ($canManageHousekeeping)
                                <option value="floor" @selected($type === 'floor')>Tầng → buồng phòng</option>
                                <option value="room" @selected($type === 'room')>Nhiệm vụ phòng tạm thời</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1">Lọc</button>
                        <a href="{{ route('admin.staff-assignments.status') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>

            @if ($canManageReceptionists && in_array($type, ['all', 'receptionist'], true))
                <section class="settings-section assignment-status-section">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                        <div>
                            <h5 class="mb-1">Booking → lễ tân</h5>
                            <div class="text-muted small">Một lễ tân được giao booking sẽ chịu trách nhiệm toàn bộ vòng đời booking đó.</div>
                        </div>
                        <a href="{{ route('admin.staff-assignments.receptionists') }}" class="btn btn-sm btn-outline-primary">Đi tới phân công lễ tân</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Booking</th>
                                    <th>Khách</th>
                                    <th>Người đang phụ trách</th>
                                    <th>Trạng thái</th>
                                    <th>Cập nhật</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bookingAssignments ?? [] as $booking)
                                    @php
                                        $customerName = trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? '')) ?: 'Chưa có tên';
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.bookings.show', $booking->id) }}" class="fw-semibold text-decoration-none">{{ $booking->booking_code }}</a>
                                        </td>
                                        <td>
                                            {{ $customerName }}
                                            <div class="small text-muted">{{ $booking->customer->phone ?? '---' }}</div>
                                        </td>
                                        <td>
                                            @foreach ($booking->activeStaffAssignments as $assignment)
                                                <span class="assignment-status-owner">
                                                    {{ $assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---' }}
                                                </span>
                                            @endforeach
                                            @if ($booking->activeStaffAssignments->count() > 1)
                                                <div class="small text-warning">Dữ liệu phân công cũ có nhiều người; gán lại để chuẩn hóa.</div>
                                            @endif
                                        </td>
                                        <td>{{ $bookingStatusLabels[$booking->status] ?? $booking->status }}</td>
                                        <td>{{ optional($booking->updated_at)->format('d/m/Y H:i') ?? '---' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Không có booking đang được phân công.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($bookingAssignments)
                        <div class="mt-3">{{ $bookingAssignments->links() }}</div>
                    @endif
                </section>
            @endif

            @if ($canManageHousekeeping && in_array($type, ['all', 'floor'], true))
                <section class="settings-section assignment-status-section">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                        <div>
                            <h5 class="mb-1">Tầng → buồng phòng</h5>
                            <div class="text-muted small">Phạm vi lâu dài đang có hiệu lực ngày {{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}.</div>
                        </div>
                        <a href="{{ route('admin.staff-assignments.housekeeping', ['work_date' => $today]) }}" class="btn btn-sm btn-outline-success">Đi tới phân công buồng phòng</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Phạm vi</th>
                                    <th>Ca</th>
                                    <th>Áp dụng từ</th>
                                    <th>Người gán</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($floorAssignmentGroups as $group)
                                    @php $assignment = $group->first(); @endphp
                                    <tr>
                                        <td>{{ $assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---' }}</td>
                                        <td>Tầng {{ $group->pluck('floor_number')->unique()->sort()->implode(', ') }}</td>
                                        <td>{{ $shiftLabels[$assignment->shift] ?? $assignment->shift }}</td>
                                        <td>{{ optional($assignment->work_date)->format('d/m/Y') ?? '---' }}</td>
                                        <td>{{ $assignment->assigner->name ?? '---' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Không có phân công tầng đang hiệu lực.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if ($canManageHousekeeping && in_array($type, ['all', 'room'], true))
                <section class="settings-section assignment-status-section">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                        <div>
                            <h5 class="mb-1">Nhiệm vụ phòng tạm thời</h5>
                            <div class="text-muted small">Các nhiệm vụ chưa hoàn tất trong ngày {{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}.</div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Phòng</th>
                                    <th>Nhiệm vụ</th>
                                    <th>Ca</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roomAssignments ?? [] as $assignment)
                                    <tr>
                                        <td>{{ $assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---' }}</td>
                                        <td>Phòng {{ $assignment->room->room_number ?? '---' }}</td>
                                        <td>{{ $taskLabels[$assignment->task_type] ?? $assignment->task_type }}</td>
                                        <td>{{ $shiftLabels[$assignment->shift] ?? $assignment->shift }}</td>
                                        <td>{{ $taskStatusLabels[$assignment->status] ?? $assignment->status }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Không có nhiệm vụ phòng đang mở.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($roomAssignments)
                        <div class="mt-3">{{ $roomAssignments->links() }}</div>
                    @endif
                </section>
            @endif
        </main>
    </div>
@endsection
