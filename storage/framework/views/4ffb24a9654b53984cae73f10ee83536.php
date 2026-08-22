<?php $__env->startSection('title', 'Phân công buồng phòng'); ?>

<?php $__env->startSection('content'); ?>
    <?php
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
    ?>

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
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> /
                <a href="<?php echo e(route('admin.staff-assignments.index')); ?>">Phân công nhân sự</a> /
                Buồng phòng
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Phân công buồng phòng</h2>
                    <p class="text-muted mb-0">Tầng là phạm vi phụ trách lâu dài; phòng là nhiệm vụ tạm thời trong ngày.</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?php echo e(route('admin.staff-assignments.status', ['type' => 'floor'])); ?>" class="btn btn-outline-primary">
                        <i class="bx bx-list-check me-1"></i> Tình trạng phân công
                    </a>
                    <a href="<?php echo e(route('admin.staff-assignments.index')); ?>" class="btn btn-outline-secondary">Quay lại</a>
                </div>
            </div>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <strong>Không thể lưu phân công:</strong>
                    <ul class="mb-0 mt-2">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

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
                    <?php $__currentLoopData = $shiftDefinitions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $definition): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="shift-guide__item">
                            <strong><?php echo e($definition['label']); ?></strong>
                            <span><?php echo e($definition['time']); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <div class="settings-section mb-3">
                <form method="GET" action="<?php echo e(route('admin.staff-assignments.housekeeping')); ?>" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Mốc ngày cần xem</label>
                        <input type="date" name="work_date" class="form-control" value="<?php echo e($workDate); ?>">
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
                        <p class="text-muted small mb-3">Áp dụng từ <?php echo e(\Carbon\Carbon::parse($workDate)->format('d/m/Y')); ?> và tiếp tục cho các ngày sau cho đến khi dừng.</p>

                        <form action="<?php echo e(route('admin.staff-assignments.housekeeping.floors.store')); ?>" method="POST" class="row g-3" data-floor-assignment-form>
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="work_date" value="<?php echo e($workDate); ?>">

                            <div class="col-md-6">
                                <label class="form-label">Nhân viên</label>
                                <select name="staff_id" class="form-select" required data-floor-staff-select>
                                    <option value="">Chọn buồng phòng</option>
                                    <?php $__currentLoopData = $housekeepers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $housekeeper): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $activeForStaff = $activeFloorAssignmentsByStaff->get($housekeeper->id, collect());
                                            $activeShifts = $activeForStaff->pluck('shift')->unique()->values()->all();
                                            $activeSummary = $activeForStaff
                                                ->groupBy('shift')
                                                ->map(function ($items, $shift) use ($shiftLabels) {
                                                    return ($shiftLabels[$shift] ?? $shift) . ' · tầng ' . $items->pluck('floor_number')->unique()->sort()->implode(', ');
                                                })
                                                ->implode('; ');
                                        ?>
                                        <option
                                            value="<?php echo e($housekeeper->id); ?>"
                                            data-active-shifts="<?php echo e(implode(',', $activeShifts)); ?>"
                                            data-active-summary="<?php echo e($activeSummary); ?>"
                                            <?php if((string) old('staff_id') === (string) $housekeeper->id): echo 'selected'; endif; ?>>
                                            <?php echo e($housekeeper->staff->full_name ?? $housekeeper->name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <div class="assignment-current-hint" data-floor-assignment-hint></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Ca</label>
                                <select name="shift" class="form-select" required data-floor-shift-select>
                                    <?php $__currentLoopData = $shiftLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($value); ?>" <?php if(old('shift', 'morning') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tầng</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php $__currentLoopData = $floors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $floor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <label class="form-check">
                                            <input type="checkbox" name="floor_numbers[]" value="<?php echo e($floor); ?>" class="form-check-input" <?php if(in_array((string) $floor, array_map('strval', (array) old('floor_numbers', [])), true)): echo 'checked'; endif; ?>>
                                            <span class="form-check-label">Tầng <?php echo e($floor); ?></span>
                                        </label>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Ghi chú</label>
                                <input type="text" name="note" class="form-control" value="<?php echo e(old('note')); ?>" placeholder="VD: ưu tiên phòng check-out sớm">
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
                        <p class="text-muted small mb-3">Chỉ áp dụng cho ngày <?php echo e(\Carbon\Carbon::parse($workDate)->format('d/m/Y')); ?>. Đây là nhiệm vụ bổ sung, không thay thế phạm vi tầng đang phụ trách.</p>

                        <form action="<?php echo e(route('admin.staff-assignments.housekeeping.rooms.store')); ?>" method="POST" class="row g-3">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="work_date" value="<?php echo e($workDate); ?>">

                            <div class="col-md-6">
                                <label class="form-label">Nhân viên</label>
                                <select name="staff_id" class="form-select" required>
                                    <option value="">Chọn buồng phòng</option>
                                    <?php $__currentLoopData = $housekeepers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $housekeeper): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($housekeeper->id); ?>"><?php echo e($housekeeper->staff->full_name ?? $housekeeper->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Ca</label>
                                <select name="shift" class="form-select" required>
                                    <?php $__currentLoopData = $shiftLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nhiệm vụ</label>
                                <select name="task_type" class="form-select" required>
                                    <?php $__currentLoopData = $taskLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Phòng</label>
                                <div class="admin-check-grid admin-check-grid--rooms" data-checkbox-selection>
                                    <?php $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <label class="admin-check-card">
                                            <input type="checkbox" name="room_ids[]" value="<?php echo e($room->id); ?>" class="form-check-input">
                                            <span class="admin-check-card__content">
                                                <strong>Phòng <?php echo e($room->room_number); ?></strong>
                                                <small>Tầng <?php echo e($room->floor_number); ?> · <?php echo e($room->category->name ?? 'Không rõ hạng'); ?></small>
                                            </span>
                                        </label>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                            <span class="badge bg-light text-dark">Ngày xem: <?php echo e(\Carbon\Carbon::parse($workDate)->format('d/m/Y')); ?></span>
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
                                    <?php $__empty_1 = true; $__currentLoopData = $floorGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <?php $assignment = $group->first(); ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---'); ?></strong>
                                                <?php if($assignment->note): ?>
                                                    <div class="small text-muted"><?php echo e($assignment->note); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>Tầng <?php echo e($group->pluck('floor_number')->unique()->sort()->implode(', ')); ?></td>
                                            <td><?php echo e($shiftLabels[$assignment->shift] ?? $assignment->shift); ?></td>
                                            <td><?php echo e(optional($assignment->work_date)->format('d/m/Y') ?: '---'); ?></td>
                                            <td class="text-end">
                                                <form action="<?php echo e(route('admin.staff-assignments.housekeeping.floors.stop-group')); ?>" method="POST" onsubmit="return confirm('Dừng toàn bộ phân công ca này của nhân viên? Sau khi dừng có thể gán lại ngay.')">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('PATCH'); ?>
                                                    <input type="hidden" name="staff_id" value="<?php echo e($assignment->staff_id); ?>">
                                                    <input type="hidden" name="shift" value="<?php echo e($assignment->shift); ?>">
                                                    <button class="btn btn-sm btn-outline-danger">Dừng phân công</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Chưa có phân công tầng đang hiệu lực.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="settings-section">
                        <h5>Nhiệm vụ phòng ngày <?php echo e(\Carbon\Carbon::parse($workDate)->format('d/m/Y')); ?></h5>
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
                                    <?php $__empty_1 = true; $__currentLoopData = $roomAssignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td><?php echo e($assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---'); ?></td>
                                            <td>
                                                Phòng <?php echo e($assignment->room->room_number ?? '---'); ?>

                                                <div class="small text-muted">Tầng <?php echo e($assignment->room->floor_number ?? '---'); ?></div>
                                            </td>
                                            <td>
                                                <?php echo e($taskLabels[$assignment->task_type] ?? $assignment->task_type); ?>

                                                <div class="small text-muted"><?php echo e($shiftLabels[$assignment->shift] ?? $assignment->shift); ?> · <?php echo e($assignmentStatusLabels[$assignment->status] ?? $assignment->status); ?></div>
                                            </td>
                                            <td class="text-end">
                                                <form action="<?php echo e(route('admin.staff-assignments.housekeeping.rooms.destroy', $assignment->id)); ?>" method="POST" onsubmit="return confirm('Hủy nhiệm vụ phòng này?')">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button class="btn btn-sm btn-outline-danger">Hủy</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Chưa có phân công phòng.</td>
                                        </tr>
                                    <?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/staff-assignments/housekeeping.blade.php ENDPATH**/ ?>