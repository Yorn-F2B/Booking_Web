<?php $__env->startSection('title', 'Phân công lễ tân'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $statusLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đã nhận phòng',
            'inspection_requested' => 'Chờ kiểm tra',
        ];
    ?>

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
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> /
                <a href="<?php echo e(route('admin.staff-assignments.index')); ?>">Phân công nhân sự</a> /
                Lễ tân
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Gán riêng lễ tân</h2>
                    <p class="text-muted mb-0">Bình thường hệ thống tự chia đều booking + chat theo khách. Trang này chỉ dùng cho trường hợp cần ghim đặc biệt.</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?php echo e(route('admin.staff-assignments.status', ['type' => 'receptionist'])); ?>" class="btn btn-outline-primary">
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

            <div class="assignment-flow-note mb-3">
                <strong>Phân phối mặc định là tự động:</strong> booking mới và chat của cùng một khách được giữ cùng một lễ tân; khi lễ tân Offline hệ thống tự bàn giao.
                Chỉ dùng nút gán ở trang này khi quản lý muốn <strong>ghim riêng</strong> khách/booking cho một lễ tân Online. Gói đã ghim không bị soft-rebalance khi người đó vẫn Online.
            </div>

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
                        <label class="form-label">Lễ tân đang phụ trách</label>
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
                                <th>Người chịu trách nhiệm</th>
                                <th style="min-width: 330px;">Gán / chuyển booking</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $customerName = trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? '')) ?: 'Chưa có tên';
                                    $activeAssignments = $booking->activeStaffAssignments;
                                    $primaryAssignment = $activeAssignments->sortByDesc('id')->first();
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
                                        <?php if($primaryAssignment): ?>
                                            <div class="assignment-owner-box is-assigned">
                                                <div class="assignment-owner-name">
                                                    <?php echo e($primaryAssignment->staff->staff->full_name ?? $primaryAssignment->staff->name ?? '---'); ?>

                                                </div>
                                                <div class="assignment-owner-meta">
                                                    <?php echo e($primaryAssignment->assigned_by ? 'Gán riêng bởi quản lý' : 'Hệ thống tự phân phối'); ?> · booking + chat cùng khách
                                                </div>
                                                <div class="assignment-owner-meta">
                                                    Gán <?php echo e(optional($primaryAssignment->created_at)->format('d/m/Y H:i') ?? '---'); ?>

                                                </div>
                                            </div>
                                            <?php if($activeAssignments->count() > 1 || $activeAssignments->contains(fn ($item) => $item->role_in_booking !== 'owner')): ?>
                                                <div class="legacy-assignment-warning">
                                                    Có dữ liệu phân công kiểu cũ. Bấm Lưu để chuẩn hóa booking về một người phụ trách toàn bộ.
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="assignment-owner-box">
                                                <div class="assignment-owner-name text-muted">Chưa gán</div>
                                                <div class="assignment-owner-meta">Chưa có lễ tân Online phù hợp hoặc đang chờ auto-assign.</div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="<?php echo e(route('admin.staff-assignments.receptionists.store')); ?>" method="POST" class="row g-2">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="booking_id" value="<?php echo e($booking->id); ?>">

                                            <div class="col-md-8">
                                                <select name="staff_id" class="form-select form-select-sm" required>
                                                    <option value="">Chọn lễ tân chịu trách nhiệm</option>
                                                    <?php $__currentLoopData = $receptionists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $receptionist): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php ($receptionistOnline = app(\App\Services\ChatPresenceService::class)->isOnline($receptionist)); ?>
                                                        <option value="<?php echo e($receptionist->id); ?>"
                                                            <?php if($primaryAssignment && (int) $primaryAssignment->staff_id === (int) $receptionist->id): echo 'selected'; endif; ?>
                                                            <?php if(!$receptionistOnline): echo 'disabled'; endif; ?>>
                                                            <?php echo e($receptionist->staff->full_name ?? $receptionist->name); ?> · <?php echo e($receptionistOnline ? 'ONLINE' : 'OFFLINE'); ?>

                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>

                                            <div class="col-md-4">
                                                <button class="btn btn-sm btn-primary w-100">
                                                    <?php echo e($primaryAssignment ? 'Ghim / chuyển' : 'Gán riêng'); ?>

                                                </button>
                                            </div>

                                            <div class="col-12">
                                                <input type="text" name="note" class="form-control form-control-sm" placeholder="Lý do gán riêng / ghi chú bàn giao">
                                            </div>
                                        </form>

                                        <?php if($primaryAssignment?->assigned_by): ?>
                                            <form action="<?php echo e(route('admin.staff-assignments.receptionists.cancel', $primaryAssignment->id)); ?>" method="POST" class="mt-2" onsubmit="return confirm('Bỏ ghim thủ công? Hệ thống sẽ tự chia lại booking + chat của khách cho lễ tân Online phù hợp.')">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <button class="btn btn-sm btn-outline-danger">Bỏ ghim / trả về tự động</button>
                                            </form>
                                        <?php endif; ?>
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

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/staff-assignments/receptionists.blade.php ENDPATH**/ ?>