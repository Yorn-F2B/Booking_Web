<?php $__env->startSection('title', 'Phòng cần kiểm tra'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $stageLabels = [
        'housekeeping_report' => 'Chờ kiểm tra ban đầu',
        'guest_consultation' => 'Chờ lễ tân trao đổi',
        'housekeeping_recheck' => 'Cần kiểm tra lại',
        'completed' => 'Đã hoàn tất',
    ];
    $stageClasses = [
        'housekeeping_report' => 'bg-warning text-dark',
        'guest_consultation' => 'bg-info text-dark',
        'housekeeping_recheck' => 'bg-danger',
        'completed' => 'bg-success',
    ];
?>

<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Phòng cần kiểm tra</p>
        <div class="admin-page-head">
            <div>
                <h2>Phòng cần kiểm tra</h2>
                <p>Mỗi booking được gom thành một nhóm. Hãy kiểm tra hết các phòng trong cùng booking trước khi chuyển sang đơn khác.</p>
            </div>
        </div>

        <div class="settings-section">
            <?php $__empty_1 = true; $__currentLoopData = $bookingGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $booking = $group->booking;
                    $customerName = trim(($booking?->customer?->last_name ?? '') . ' ' . ($booking?->customer?->first_name ?? ''));
                    $hasActionable = $group->actionable_count > 0;
                    $roomsText = $group->inspections
                        ->map(fn ($inspection) => $inspection->room?->room_number)
                        ->filter()
                        ->implode(', ');
                ?>

                <div class="border rounded-3 mb-3 overflow-hidden <?php echo e($hasActionable ? 'border-warning' : ''); ?>">
                    <div class="p-3 bg-light d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <strong class="fs-5"><?php echo e($booking?->booking_code ?? ('Booking #' . $group->booking_id)); ?></strong>
                                <?php if($hasActionable): ?>
                                    <span class="badge bg-warning text-dark"><?php echo e($group->actionable_count); ?> phòng cần xử lý</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Đã xử lý hết</span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted">
                                <?php echo e($customerName ?: 'Chưa có tên khách'); ?>

                                <?php if($booking?->customer?->phone): ?>
                                    · <?php echo e($booking->customer->phone); ?>

                                <?php endif; ?>
                                · <?php echo e($group->total_rooms); ?> phòng
                                <?php if($roomsText): ?>
                                    · Phòng <?php echo e($roomsText); ?>

                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if($group->next_inspection): ?>
                            <a href="<?php echo e(route('admin.floor-inspections.show', $group->next_inspection->id)); ?>"
                               class="btn <?php echo e($hasActionable ? 'btn-primary' : 'btn-outline-secondary'); ?>">
                                <?php echo e($hasActionable ? 'Bắt đầu / tiếp tục kiểm tra' : 'Xem kết quả'); ?>

                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Phòng</th>
                                    <th>Kết quả tạm tính</th>
                                    <th>Trạng thái</th>
                                    <th>Cập nhật</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $group->inspections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inspection): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $stage = $inspection->workflow_stage ?? 'housekeeping_report';
                                        $total = $inspection->items->sum('total');
                                        $disputed = $inspection->items->where('guest_response', 'disputed')->count();
                                        $actionable = ($stage === 'housekeeping_report' && in_array($inspection->status, ['pending', 'rejected']))
                                            || ($stage === 'housekeeping_recheck' && $inspection->status === 'reported');
                                    ?>
                                    <tr class="<?php echo e($stage === 'housekeeping_recheck' ? 'table-danger' : ''); ?>">
                                        <td>
                                            <strong>Phòng <?php echo e($inspection->room?->room_number ?? '---'); ?></strong>
                                            <div class="small text-muted">Tầng <?php echo e($inspection->room?->floor_number ?? '---'); ?> · Phiếu #<?php echo e($inspection->id); ?></div>
                                        </td>
                                        <td>
                                            <?php if($inspection->items->isEmpty()): ?>
                                                <span class="text-muted">Không phát sinh</span>
                                            <?php else: ?>
                                                <strong><?php echo e(number_format((float) $total, 0, ',', '.')); ?>đ</strong>
                                                <div class="small text-muted"><?php echo e($inspection->items->count()); ?> hạng mục</div>
                                                <?php if($disputed > 0): ?>
                                                    <div class="small text-danger"><?php echo e($disputed); ?> mục khách phản hồi</div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo e($stageClasses[$stage] ?? 'bg-secondary'); ?>"><?php echo e($stageLabels[$stage] ?? $stage); ?></span>
                                        </td>
                                        <td>
                                            <div class="small"><?php echo e($inspection->last_revision_at?->format('d/m/Y H:i:s') ?? $inspection->updated_at?->format('d/m/Y H:i:s')); ?></div>
                                            <div class="small text-muted text-truncate" style="max-width:340px"><?php echo e($inspection->last_update_summary); ?></div>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?php echo e(route('admin.floor-inspections.show', $inspection->id)); ?>"
                                               class="btn btn-sm <?php echo e($actionable ? 'btn-primary' : 'btn-outline-secondary'); ?>">
                                                <?php echo e($stage === 'housekeeping_recheck' ? 'Kiểm tra lại' : ($stage === 'housekeeping_report' ? 'Kiểm tra' : 'Xem')); ?>

                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center text-muted py-4">Không có booking nào có phòng cần kiểm tra.</div>
            <?php endif; ?>

            <div class="mt-3"><?php echo e($bookingGroups->links()); ?></div>
        </div>
    </main>
    <footer class="admin-footer"><span>MCuong Hotel Admin</span></footer>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/floor-inspections/index.blade.php ENDPATH**/ ?>