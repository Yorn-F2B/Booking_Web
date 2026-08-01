@extends('layouts.admin')

@section('title', 'Phân công buồng phòng')

@section('content')
    @php
        $shiftLabels = [
            'morning' => 'Ca sáng',
            'afternoon' => 'Ca chiều',
            'evening' => 'Ca tối',
            'full_day' => 'Cả ngày',
        ];

        $taskLabels = [
            'cleaning' => 'Dọn phòng',
            'inspection' => 'Kiểm tra phòng',
            'maintenance_support' => 'Hỗ trợ bảo trì',
        ];

        $assignmentStatusLabels = [
            'assigned' => 'Đã giao',
            'in_progress' => 'Đang làm',
            'completed' => 'Hoàn tất',
            'canceled' => 'Đã hủy',
            'active' => 'Đang hoạt động',
        ];
    @endphp

    <div class="admin-wrapper">
        <main class="admin-content">
            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.staff-assignments.index') }}">Phân công nhân sự</a> /
                Buồng phòng
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Phân công buồng phòng</h2>
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
                <form method="GET" action="{{ route('admin.staff-assignments.housekeeping') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Ngày làm việc</label>
                        <input type="date" name="work_date" class="form-control" value="{{ $workDate }}">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">Xem phân công</button>
                    </div>
                </form>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <div class="settings-section h-100">
                        <h5>Gán theo tầng</h5>

                        <form action="{{ route('admin.staff-assignments.housekeeping.floors.store') }}" method="POST" class="row g-3">
                            @csrf
                            <input type="hidden" name="work_date" value="{{ $workDate }}">

                            <div class="col-md-6">
                                <label class="form-label">Nhân viên</label>
                                <select name="staff_id" class="form-select" required>
                                    <option value="">Chọn buồng phòng</option>
                                    @foreach ($housekeepers as $housekeeper)
                                        <option value="{{ $housekeeper->id }}">{{ $housekeeper->staff->full_name ?? $housekeeper->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Ca</label>
                                <select name="shift" class="form-select" required>
                                    @foreach ($shiftLabels as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tầng</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($floors as $floor)
                                        <label class="form-check">
                                            <input type="checkbox" name="floor_numbers[]" value="{{ $floor }}" class="form-check-input">
                                            <span class="form-check-label">Tầng {{ $floor }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Ghi chú</label>
                                <input type="text" name="note" class="form-control" placeholder="VD: ưu tiên phòng check-out sớm">
                            </div>

                            <div class="col-12">
                                <button class="btn btn-success">Lưu phân công tầng</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="settings-section h-100">
                        <h5>Gán theo phòng</h5>

                        <form action="{{ route('admin.staff-assignments.housekeeping.rooms.store') }}" method="POST" class="row g-3">
                            @csrf
                            <input type="hidden" name="work_date" value="{{ $workDate }}">

                            <div class="col-md-6">
                                <label class="form-label">Nhân viên</label>
                                <select name="staff_id" class="form-select" required>
                                    <option value="">Chọn buồng phòng</option>
                                    @foreach ($housekeepers as $housekeeper)
                                        <option value="{{ $housekeeper->id }}">{{ $housekeeper->staff->full_name ?? $housekeeper->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Ca</label>
                                <select name="shift" class="form-select" required>
                                    @foreach ($shiftLabels as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nhiệm vụ</label>
                                <select name="task_type" class="form-select" required>
                                    @foreach ($taskLabels as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Phòng</label>
                                <div class="admin-check-grid admin-check-grid--rooms" data-checkbox-selection>
                                    @foreach ($rooms as $room)
                                        <label class="admin-check-card">
                                            <input
                                                type="checkbox"
                                                name="room_ids[]"
                                                value="{{ $room->id }}"
                                                class="form-check-input"
                                                @checked(in_array((string) $room->id, array_map('strval', (array) old('room_ids', [])), true))>
                                            <span class="admin-check-card__content">
                                                <strong>Phòng {{ $room->room_number }}</strong>
                                                <small>Tầng {{ $room->floor_number }} · {{ $room->category->name ?? 'Không rõ hạng' }}</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('room_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Ghi chú</label>
                                <input type="text" name="note" class="form-control" placeholder="VD: dọn gấp trước 13:00">
                            </div>

                            <div class="col-12">
                                <button class="btn btn-warning">Lưu phân công phòng</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="settings-section">
                        <h5>Danh sách gán tầng ngày {{ \Carbon\Carbon::parse($workDate)->format('d/m/Y') }}</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nhân viên</th>
                                        <th>Tầng</th>
                                        <th>Ca</th>
                                        <th>Ghi chú</th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($floorAssignments as $assignment)
                                        <tr>
                                            <td>{{ $assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---' }}</td>
                                            <td>Tầng {{ $assignment->floor_number }}</td>
                                            <td>{{ $shiftLabels[$assignment->shift] ?? $assignment->shift }}</td>
                                            <td>{{ $assignment->note ?: '---' }}</td>
                                            <td class="text-end">
                                                <form action="{{ route('admin.staff-assignments.housekeeping.floors.destroy', $assignment->id) }}" method="POST" onsubmit="return confirm('Xóa phân công tầng này?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Chưa có phân công tầng.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="settings-section">
                        <h5>Danh sách gán phòng ngày {{ \Carbon\Carbon::parse($workDate)->format('d/m/Y') }}</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nhân viên</th>
                                        <th>Phòng</th>
                                        <th>Nhiệm vụ</th>
                                        <th>Trạng thái</th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($roomAssignments as $assignment)
                                        <tr>
                                            <td>{{ $assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---' }}</td>
                                            <td>
                                                Phòng {{ $assignment->room->room_number ?? '---' }}
                                                <div class="small text-muted">Tầng {{ $assignment->room->floor_number ?? '---' }}</div>
                                            </td>
                                            <td>{{ $taskLabels[$assignment->task_type] ?? $assignment->task_type }}</td>
                                            <td>{{ $assignmentStatusLabels[$assignment->status] ?? $assignment->status }}</td>
                                            <td class="text-end">
                                                <form action="{{ route('admin.staff-assignments.housekeeping.rooms.destroy', $assignment->id) }}" method="POST" onsubmit="return confirm('Xóa phân công phòng này?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Chưa có phân công phòng.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-checkbox-selection]').forEach(function (collection) {
                const form = collection.closest('form');
                const checks = Array.from(collection.querySelectorAll('input[type="checkbox"]'));
                if (!form || checks.length === 0) return;

                function refreshSelection() {
                    const hasSelection = checks.some(function (checkbox) { return checkbox.checked; });
                    checks[0].setCustomValidity(hasSelection ? '' : 'Vui lòng chọn ít nhất một phòng.');
                }

                checks.forEach(function (checkbox) {
                    checkbox.addEventListener('change', refreshSelection);
                });
                form.addEventListener('submit', refreshSelection);
                refreshSelection();
            });
        });
    </script>
@endsection
