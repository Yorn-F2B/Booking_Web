@extends('layouts.admin')

@section('title', 'Phân công buồng phòng')

@section('content')
    @php
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

        $floorGroups = $floorAssignments
            ->groupBy(fn ($assignment) => $assignment->staff_id . '|' . $assignment->shift . '|' . optional($assignment->work_date)->toDateString())
            ->values();
    @endphp

    <style>
        .shift-guide {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }
        .shift-guide__item {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 10px;
            padding: 10px 12px;
        }
        .shift-guide__item strong { display: block; color: #0f172a; }
        .shift-guide__item span { color: #64748b; font-size: .8rem; }
        .assignment-current-hint {
            min-height: 20px;
            margin-top: 6px;
            color: #b45309;
            font-size: .8rem;
        }
        .assignment-current-hint.is-free { color: #15803d; }
        .assignment-table-scroll { max-height: 430px; overflow: auto; }
        @media (max-width: 991px) {
            .shift-guide { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575px) {
            .shift-guide { grid-template-columns: 1fr; }
        }
    </style>

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
                    <p class="text-muted mb-0">Tầng là phạm vi phụ trách lâu dài; phòng là nhiệm vụ tạm thời trong ngày.</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.staff-assignments.status', ['type' => 'floor']) }}" class="btn btn-outline-primary">
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

            <div class="alert alert-info mb-3">
                <strong>Quy tắc phân công:</strong>
                Gán theo <strong>tầng</strong> có hiệu lực từ ngày bắt đầu đến khi bấm <strong>Dừng</strong>.
                Một nhân viên không thể nhận thêm phân công tầng ở cùng ca hoặc ca bị chồng thời gian khi phân công cũ còn hiệu lực.
                Gán theo <strong>phòng</strong> là nhiệm vụ tạm thời và có thể giao bổ sung khi cần xử lý ngoại lệ.
            </div>

            <div class="settings-section mb-3">
                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
                    <div>
                        <h5 class="mb-1">Khung giờ các ca</h5>
                        <div class="text-muted small">Khung giờ này dùng thống nhất trên toàn bộ màn phân công.</div>
                    </div>
                </div>
                <div class="shift-guide">
                    @foreach ($shiftDefinitions as $key => $definition)
                        <div class="shift-guide__item">
                            <strong>{{ $definition['label'] }}</strong>
                            <span>{{ $definition['time'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="settings-section mb-3">
                <form method="GET" action="{{ route('admin.staff-assignments.housekeeping') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Mốc ngày cần xem</label>
                        <input type="date" name="work_date" class="form-control" value="{{ $workDate }}">
                        <div class="form-text">Xem phạm vi tầng đang có hiệu lực và nhiệm vụ phòng của ngày đó.</div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">Xem phân công</button>
                    </div>
                </form>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <div class="settings-section h-100">
                        <h5>Gán theo tầng lâu dài</h5>
                        <p class="text-muted small mb-3">Áp dụng từ {{ \Carbon\Carbon::parse($workDate)->format('d/m/Y') }} và tiếp tục cho các ngày sau cho đến khi dừng.</p>

                        <form action="{{ route('admin.staff-assignments.housekeeping.floors.store') }}" method="POST" class="row g-3" data-floor-assignment-form>
                            @csrf
                            <input type="hidden" name="work_date" value="{{ $workDate }}">

                            <div class="col-md-6">
                                <label class="form-label">Nhân viên</label>
                                <select name="staff_id" class="form-select" required data-floor-staff-select>
                                    <option value="">Chọn buồng phòng</option>
                                    @foreach ($housekeepers as $housekeeper)
                                        @php
                                            $activeForStaff = $activeFloorAssignmentsByStaff->get($housekeeper->id, collect());
                                            $activeShifts = $activeForStaff->pluck('shift')->unique()->values()->all();
                                            $activeSummary = $activeForStaff
                                                ->groupBy('shift')
                                                ->map(function ($items, $shift) use ($shiftLabels) {
                                                    return ($shiftLabels[$shift] ?? $shift) . ' · tầng ' . $items->pluck('floor_number')->unique()->sort()->implode(', ');
                                                })
                                                ->implode('; ');
                                        @endphp
                                        <option
                                            value="{{ $housekeeper->id }}"
                                            data-active-shifts="{{ implode(',', $activeShifts) }}"
                                            data-active-summary="{{ $activeSummary }}"
                                            @selected((string) old('staff_id') === (string) $housekeeper->id)>
                                            {{ $housekeeper->staff->full_name ?? $housekeeper->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="assignment-current-hint" data-floor-assignment-hint></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Ca</label>
                                <select name="shift" class="form-select" required data-floor-shift-select>
                                    @foreach ($shiftLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(old('shift', 'morning') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tầng</label>
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach ($floors as $floor)
                                        <label class="form-check">
                                            <input type="checkbox" name="floor_numbers[]" value="{{ $floor }}" class="form-check-input" @checked(in_array((string) $floor, array_map('strval', (array) old('floor_numbers', [])), true))>
                                            <span class="form-check-label">Tầng {{ $floor }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Ghi chú</label>
                                <input type="text" name="note" class="form-control" value="{{ old('note') }}" placeholder="VD: ưu tiên phòng check-out sớm">
                            </div>

                            <div class="col-12">
                                <button class="btn btn-success">Lưu phân công tầng</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="settings-section h-100">
                        <h5>Gán nhiệm vụ theo phòng</h5>
                        <p class="text-muted small mb-3">Chỉ áp dụng cho ngày {{ \Carbon\Carbon::parse($workDate)->format('d/m/Y') }}. Đây là nhiệm vụ bổ sung, không thay thế phạm vi tầng đang phụ trách.</p>

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
                                            <input type="checkbox" name="room_ids[]" value="{{ $room->id }}" class="form-check-input">
                                            <span class="admin-check-card__content">
                                                <strong>Phòng {{ $room->room_number }}</strong>
                                                <small>Tầng {{ $room->floor_number }} · {{ $room->category->name ?? 'Không rõ hạng' }}</small>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Ghi chú</label>
                                <input type="text" name="note" class="form-control" placeholder="VD: dọn gấp trước 13:00">
                            </div>

                            <div class="col-12">
                                <button class="btn btn-warning">Lưu nhiệm vụ phòng</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="settings-section">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                            <h5 class="mb-0">Phân công tầng đang hiệu lực</h5>
                            <span class="badge bg-light text-dark">Ngày xem: {{ \Carbon\Carbon::parse($workDate)->format('d/m/Y') }}</span>
                        </div>
                        <div class="table-responsive assignment-table-scroll">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nhân viên</th>
                                        <th>Phạm vi</th>
                                        <th>Ca</th>
                                        <th>Áp dụng từ</th>
                                        <th class="text-end">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($floorGroups as $group)
                                        @php $assignment = $group->first(); @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ $assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---' }}</strong>
                                                @if ($assignment->note)
                                                    <div class="small text-muted">{{ $assignment->note }}</div>
                                                @endif
                                            </td>
                                            <td>Tầng {{ $group->pluck('floor_number')->unique()->sort()->implode(', ') }}</td>
                                            <td>{{ $shiftLabels[$assignment->shift] ?? $assignment->shift }}</td>
                                            <td>{{ optional($assignment->work_date)->format('d/m/Y') ?: '---' }}</td>
                                            <td class="text-end">
                                                <form action="{{ route('admin.staff-assignments.housekeeping.floors.stop-group') }}" method="POST" onsubmit="return confirm('Dừng toàn bộ phân công ca này của nhân viên? Sau khi dừng có thể gán lại ngay.')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="staff_id" value="{{ $assignment->staff_id }}">
                                                    <input type="hidden" name="shift" value="{{ $assignment->shift }}">
                                                    <button class="btn btn-sm btn-outline-danger">Dừng phân công</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Chưa có phân công tầng đang hiệu lực.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="settings-section">
                        <h5>Nhiệm vụ phòng ngày {{ \Carbon\Carbon::parse($workDate)->format('d/m/Y') }}</h5>
                        <div class="table-responsive assignment-table-scroll">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nhân viên</th>
                                        <th>Phòng</th>
                                        <th>Nhiệm vụ / ca</th>
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
                                            <td>
                                                {{ $taskLabels[$assignment->task_type] ?? $assignment->task_type }}
                                                <div class="small text-muted">{{ $shiftLabels[$assignment->shift] ?? $assignment->shift }} · {{ $assignmentStatusLabels[$assignment->status] ?? $assignment->status }}</div>
                                            </td>
                                            <td class="text-end">
                                                <form action="{{ route('admin.staff-assignments.housekeeping.rooms.destroy', $assignment->id) }}" method="POST" onsubmit="return confirm('Hủy nhiệm vụ phòng này?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Hủy</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Chưa có phân công phòng.</td>
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
            const floorForm = document.querySelector('[data-floor-assignment-form]');
            if (floorForm) {
                const staffSelect = floorForm.querySelector('[data-floor-staff-select]');
                const shiftSelect = floorForm.querySelector('[data-floor-shift-select]');
                const hint = floorForm.querySelector('[data-floor-assignment-hint]');

                const conflicts = function (existingShift, wantedShift) {
                    return existingShift === 'full_day' || wantedShift === 'full_day' || existingShift === wantedShift;
                };

                const refreshStaffAvailability = function () {
                    const wantedShift = shiftSelect.value;
                    Array.from(staffSelect.options).forEach(function (option) {
                        if (!option.value) return;
                        const activeShifts = (option.dataset.activeShifts || '').split(',').filter(Boolean);
                        option.disabled = activeShifts.some(function (existingShift) {
                            return conflicts(existingShift, wantedShift);
                        });
                    });

                    const selected = staffSelect.options[staffSelect.selectedIndex];
                    if (selected && selected.disabled) {
                        staffSelect.value = '';
                    }
                    refreshHint();
                };

                const refreshHint = function () {
                    const selected = staffSelect.options[staffSelect.selectedIndex];
                    if (!selected || !selected.value) {
                        hint.textContent = 'Chọn nhân viên để xem phân công đang hiệu lực.';
                        hint.classList.remove('is-free');
                        return;
                    }

                    const summary = selected.dataset.activeSummary || '';
                    if (summary) {
                        hint.textContent = 'Đang phụ trách: ' + summary + '. Ca không trùng vẫn có thể gán thêm.';
                        hint.classList.remove('is-free');
                    } else {
                        hint.textContent = 'Nhân viên chưa có phân công tầng đang hoạt động.';
                        hint.classList.add('is-free');
                    }
                };

                shiftSelect.addEventListener('change', refreshStaffAvailability);
                staffSelect.addEventListener('change', refreshHint);
                refreshStaffAvailability();
            }

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
