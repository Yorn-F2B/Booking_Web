<?php $__env->startSection('title', 'Duyệt kiểm tra phòng'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $stageLabels = [
        'housekeeping_report' => 'Buồng phòng đang kiểm tra',
        'guest_consultation' => 'Lễ tân đang trao đổi với khách',
        'housekeeping_recheck' => 'Buồng phòng đang kiểm tra lại',
        'admin_approval' => 'Khách đã đồng ý · chờ admin xác nhận',
        'completed' => 'Đã hoàn tất',
    ];
    $stageClasses = [
        'housekeeping_report' => 'bg-secondary',
        'guest_consultation' => 'bg-info text-dark',
        'housekeeping_recheck' => 'bg-warning text-dark',
        'admin_approval' => 'bg-primary',
        'completed' => 'bg-success',
    ];
?>

<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Duyệt kiểm tra phòng</p>
        <div class="admin-page-head">
            <div>
                <h2>Duyệt kiểm tra phòng</h2>
                <p>Admin chỉ xác nhận khi khách đã đồng ý kết quả hiện tại. Phiếu còn tranh luận sẽ tiếp tục quay lại lễ tân và buồng phòng.</p>
            </div>
        </div>
        <?php if(session('success')): ?> <div class="alert alert-success"><?php echo e(session('success')); ?></div> <?php endif; ?>
        <?php if(session('error')): ?> <div class="alert alert-danger"><?php echo e(session('error')); ?></div> <?php endif; ?>

        <div class="settings-section">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Booking</th><th>Phòng</th><th>Khoản hiện tại</th><th>Tình trạng</th><th>Cập nhật gần nhất</th><th class="text-end">Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $inspections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inspection): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $stage = $inspection->workflow_stage ?? 'admin_approval';
                                $customerName = trim(($inspection->booking->customer->last_name ?? '') . ' ' . ($inspection->booking->customer->first_name ?? ''));
                                $proposedTotal = $inspection->items->sum('total');
                                $unseen = (int) $inspection->admin_acknowledged_version < (int) $inspection->version;
                            ?>
                            <tr class="<?php echo e($stage === 'admin_approval' && $unseen ? 'table-warning' : ''); ?>">
                                <td><strong><?php echo e($inspection->booking->booking_code ?? '---'); ?></strong><div class="small text-muted"><?php echo e($customerName ?: 'Chưa có tên'); ?></div></td>
                                <td><strong><?php echo e($inspection->room->room_number ?? '---'); ?></strong><div class="small text-muted">Tầng <?php echo e($inspection->room->floor_number ?? '---'); ?></div></td>
                                <td><strong><?php echo e(number_format((float) $proposedTotal, 0, ',', '.')); ?>đ</strong><div class="small text-muted"><?php echo e($inspection->items->count()); ?> hạng mục</div></td>
                                <td><span class="badge <?php echo e($stageClasses[$stage] ?? 'bg-secondary'); ?>"><?php echo e($stageLabels[$stage] ?? $stage); ?></span></td>
                                <td>
                                    <?php if($unseen && $inspection->status === 'reported'): ?>
                                        <span class="badge bg-danger">Có cập nhật mới</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Đã xem cập nhật</span>
                                    <?php endif; ?>
                                    <div class="small text-muted mt-1"><?php echo e($inspection->last_revision_at?->format('d/m/Y H:i:s') ?: 'Chưa có'); ?></div>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo e(route('admin.inspection-approvals.show', $inspection->id)); ?>" class="btn btn-sm <?php echo e($stage === 'admin_approval' ? 'btn-primary' : 'btn-outline-secondary'); ?>">
                                        <?php echo e($stage === 'admin_approval' ? 'Xem và xác nhận' : 'Xem tiến độ'); ?>

                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="6" class="text-center text-muted">Không có phiếu kiểm tra nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3"><?php echo e($inspections->links()); ?></div>
        </div>
    </main>
    <footer class="admin-footer"><span>MCuong Hotel Admin</span></footer>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\inspection-approvals\index.blade.php ENDPATH**/ ?>