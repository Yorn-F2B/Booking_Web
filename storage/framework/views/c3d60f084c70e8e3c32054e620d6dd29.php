<?php $__env->startSection('title', 'Phân công buồng phòng'); ?>

<?php $__env->startSection('content'); ?>
    <?php
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
    ?>

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
                </div>

                <a href="<?php echo e(route('admin.staff-assignments.index')); ?>" class="btn btn-outline-secondary">Quay lại</a>
            </div>

            <?php if(session('success')): ?>
                <div class="alert alert-success"><?php echo e(session('success')); ?></div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
            <?php endif; ?>

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

            <div class="settings-section mb-3">
                <form method="GET" action="<?php echo e(route('admin.staff-assignments.housekeeping')); ?>" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Ngày làm việc</label>
                        <input type="date" name="work_date" class="form-control" value="<?php echo e($workDate); ?>">
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

                        <form action="<?php echo e(route('admin.staff-assignments.housekeeping.floors.store')); ?>" method="POST" class="row g-3">
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

                            <div class="col-12">
                                <label class="form-label">Tầng</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php $__currentLoopData = $floors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $floor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <label class="form-check">
                                            <input type="checkbox" name="floor_numbers[]" value="<?php echo e($floor); ?>" class="form-check-input">
                                            <span class="form-check-label">Tầng <?php echo e($floor); ?></span>
                                        </label>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                            <input
                                                type="checkbox"
                                                name="room_ids[]"
                                                value="<?php echo e($room->id); ?>"
                                                class="form-check-input"
                                                <?php if(in_array((string) $room->id, array_map('strval', (array) old('room_ids', [])), true)): echo 'checked'; endif; ?>>
                                            <span class="admin-check-card__content">
                                                <strong>Phòng <?php echo e($room->room_number); ?></strong>
                                                <small>Tầng <?php echo e($room->floor_number); ?> · <?php echo e($room->category->name ?? 'Không rõ hạng'); ?></small>
                                            </span>
                                        </label>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                                <?php $__errorArgs = ['room_ids'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                        <h5>Danh sách gán tầng ngày <?php echo e(\Carbon\Carbon::parse($workDate)->format('d/m/Y')); ?></h5>
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
                                    <?php $__empty_1 = true; $__currentLoopData = $floorAssignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td><?php echo e($assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---'); ?></td>
                                            <td>Tầng <?php echo e($assignment->floor_number); ?></td>
                                            <td><?php echo e($shiftLabels[$assignment->shift] ?? $assignment->shift); ?></td>
                                            <td><?php echo e($assignment->note ?: '---'); ?></td>
                                            <td class="text-end">
                                                <form action="<?php echo e(route('admin.staff-assignments.housekeeping.floors.destroy', $assignment->id)); ?>" method="POST" onsubmit="return confirm('Xóa phân công tầng này?')">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button class="btn btn-sm btn-outline-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Chưa có phân công tầng.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="settings-section">
                        <h5>Danh sách gán phòng ngày <?php echo e(\Carbon\Carbon::parse($workDate)->format('d/m/Y')); ?></h5>
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
                                    <?php $__empty_1 = true; $__currentLoopData = $roomAssignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td><?php echo e($assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---'); ?></td>
                                            <td>
                                                Phòng <?php echo e($assignment->room->room_number ?? '---'); ?>

                                                <div class="small text-muted">Tầng <?php echo e($assignment->room->floor_number ?? '---'); ?></div>
                                            </td>
                                            <td><?php echo e($taskLabels[$assignment->task_type] ?? $assignment->task_type); ?></td>
                                            <td><?php echo e($assignmentStatusLabels[$assignment->status] ?? $assignment->status); ?></td>
                                            <td class="text-end">
                                                <form action="<?php echo e(route('admin.staff-assignments.housekeeping.rooms.destroy', $assignment->id)); ?>" method="POST" onsubmit="return confirm('Xóa phân công phòng này?')">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button class="btn btn-sm btn-outline-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Chưa có phân công phòng.</td>
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

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\staff-assignments\housekeeping.blade.php ENDPATH**/ ?>