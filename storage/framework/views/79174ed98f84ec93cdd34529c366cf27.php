<?php $__env->startSection('title', 'Phân công lễ tân'); ?>

<?php $__env->startSection('content'); ?>
    <?php
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
    ?>

    <div class="admin-wrapper">
        <main class="admin-content">
            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> /
                <a href="<?php echo e(route('admin.staff-assignments.index')); ?>">Phân công nhân sự</a> /
                Lễ tân
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Phân công lễ tân</h2>
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
                <form method="GET" action="<?php echo e(route('admin.staff-assignments.receptionists')); ?>" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tìm booking / khách / SĐT</label>
                        <input type="text" name="keyword" class="form-control" value="<?php echo e(request('keyword')); ?>" placeholder="VD: BK0001, An, 098...">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Trạng thái booking</label>
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php echo e(request('status') == $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Lễ tân</label>
                        <select name="assigned_staff_id" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="none" <?php echo e(request('assigned_staff_id') === 'none' ? 'selected' : ''); ?>>Chưa gán</option>
                            <?php $__currentLoopData = $receptionists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $receptionist): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($receptionist->id); ?>" <?php echo e(request('assigned_staff_id') == $receptionist->id ? 'selected' : ''); ?>>
                                    <?php echo e($receptionist->staff->full_name ?? $receptionist->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button class="btn btn-primary w-100">Lọc</button>
                        <a href="<?php echo e(route('admin.staff-assignments.receptionists')); ?>" class="btn btn-outline-secondary">Reset</a>
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
                            <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $customerName = trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? '')) ?: 'Chưa có tên';
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo e(route('admin.bookings.show', $booking->id)); ?>" class="fw-bold text-decoration-none">
                                            <?php echo e($booking->booking_code); ?>

                                        </a>
                                        <div class="small text-muted">Tạo bởi: <?php echo e($booking->creator->name ?? '---'); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo e($customerName); ?></div>
                                        <div class="small text-muted"><?php echo e($booking->customer->phone ?? '---'); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo e(optional($booking->check_in_at)->format('d/m/Y H:i')); ?></div>
                                        <div class="small text-muted">→ <?php echo e(optional($booking->check_out_at)->format('d/m/Y H:i')); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo e($statusLabels[$booking->status] ?? $booking->status); ?></span>
                                    </td>
                                    <td>
                                        <?php $__empty_2 = true; $__currentLoopData = $booking->activeStaffAssignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                                <span>
                                                    <strong><?php echo e($assignment->staff->staff->full_name ?? $assignment->staff->name ?? '---'); ?></strong>
                                                    <small class="text-muted">(<?php echo e($roleLabels[$assignment->role_in_booking] ?? $assignment->role_in_booking); ?>)</small>
                                                </span>
                                                <form action="<?php echo e(route('admin.staff-assignments.receptionists.cancel', $assignment->id)); ?>" method="POST" onsubmit="return confirm('Hủy phân công này?')">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('PATCH'); ?>
                                                    <button class="btn btn-sm btn-outline-danger">Hủy</button>
                                                </form>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                            <span class="text-muted">Chưa gán</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="<?php echo e(route('admin.staff-assignments.receptionists.store')); ?>" method="POST" class="row g-2">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="booking_id" value="<?php echo e($booking->id); ?>">

                                            <div class="col-md-5">
                                                <select name="staff_id" class="form-select form-select-sm" required>
                                                    <option value="">Chọn lễ tân</option>
                                                    <?php $__currentLoopData = $receptionists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $receptionist): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($receptionist->id); ?>"><?php echo e($receptionist->staff->full_name ?? $receptionist->name); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>

                                            <div class="col-md-4">
                                                <select name="role_in_booking" class="form-select form-select-sm" required>
                                                    <?php $__currentLoopData = $roleLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Không có booking phù hợp.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <?php echo e($bookings->links()); ?>

                </div>
            </div>
        </main>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\staff-assignments\receptionists.blade.php ENDPATH**/ ?>