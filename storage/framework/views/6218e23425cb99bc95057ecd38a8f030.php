<?php $__env->startSection('title', 'Phòng cần kiểm tra'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $stageLabels = [
        'housekeeping_report' => 'Chờ kiểm tra ban đầu',
        'guest_consultation' => 'Chờ lễ tân trao đổi',
        'housekeeping_recheck' => 'Cần kiểm tra lại',
        'admin_approval' => 'Chờ admin xác nhận',
        'completed' => 'Đã hoàn tất',
    ];
    $stageClasses = [
        'housekeeping_report' => 'bg-warning text-dark',
        'guest_consultation' => 'bg-info text-dark',
        'housekeeping_recheck' => 'bg-danger',
        'admin_approval' => 'bg-primary',
        'completed' => 'bg-success',
    ];
?>

<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Phòng cần kiểm tra</p>
        <div class="admin-page-head">
            <div><h2>Phòng cần kiểm tra</h2><p>Buồng phòng kiểm tra ban đầu và xử lý riêng các khoản khách yêu cầu xác minh lại.</p></div>
        </div>

        <?php if(session('success')): ?> <div class="alert alert-success"><?php echo e(session('success')); ?></div> <?php endif; ?>
        <?php if(session('error')): ?> <div class="alert alert-danger"><?php echo e(session('error')); ?></div> <?php endif; ?>

        <div class="settings-section">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Mã booking</th><th>Khách hàng</th><th>Phòng</th><th>Kết quả tạm tính</th><th>Bước hiện tại</th><th>Cập nhật</th><th class="text-end">Thao tác</th></tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $inspections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inspection): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $customerName = trim(($inspection->booking->customer->last_name ?? '') . ' ' . ($inspection->booking->customer->first_name ?? ''));
                                $stage = $inspection->workflow_stage ?? 'housekeeping_report';
                                $total = $inspection->items->sum('total');
                                $disputed = $inspection->items->where('guest_response', 'disputed')->count();
                            ?>
                            <tr class="<?php echo e($stage === 'housekeeping_recheck' ? 'table-danger' : ''); ?>">
                                <td><strong><?php echo e($inspection->booking->booking_code ?? '---'); ?></strong><div class="small text-muted">Phiếu #<?php echo e($inspection->id); ?> · bản <?php echo e($inspection->version); ?></div></td>
                                <td><strong><?php echo e($customerName ?: 'Chưa có tên'); ?></strong><div class="small text-muted"><?php echo e($inspection->booking->customer->phone ?? '---'); ?></div></td>
                                <td><strong>Phòng <?php echo e($inspection->room->room_number ?? '---'); ?></strong><div class="small text-muted">Tầng <?php echo e($inspection->room->floor_number ?? '---'); ?></div></td>
                                <td>
                                    <?php if($inspection->items->isEmpty()): ?>
                                        <span class="text-muted">Không phát sinh</span>
                                    <?php else: ?>
                                        <strong><?php echo e(number_format((float) $total, 0, ',', '.')); ?>đ</strong>
                                        <div class="small text-muted"><?php echo e($inspection->items->count()); ?> hạng mục</div>
                                        <?php if($disputed > 0): ?><div class="small text-danger"><?php echo e($disputed); ?> mục khách phản hồi</div><?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo e($stageClasses[$stage] ?? 'bg-secondary'); ?>"><?php echo e($stageLabels[$stage] ?? $stage); ?></span></td>
                                <td><div class="small"><?php echo e($inspection->last_revision_at?->format('d/m/Y H:i:s') ?? $inspection->updated_at?->format('d/m/Y H:i:s')); ?></div><div class="small text-muted text-truncate" style="max-width:260px"><?php echo e($inspection->last_update_summary); ?></div></td>
                                <td class="text-end">
                                    <a href="<?php echo e(route('admin.floor-inspections.show', $inspection->id)); ?>" class="btn btn-sm <?php echo e(in_array($stage, ['housekeeping_report','housekeeping_recheck']) ? 'btn-primary' : 'btn-outline-secondary'); ?>">
                                        <?php echo e($stage === 'housekeeping_recheck' ? 'Kiểm tra lại' : (in_array($stage, ['housekeeping_report']) ? 'Kiểm tra' : 'Xem')); ?>

                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="7" class="text-center text-muted">Không có phòng nào cần kiểm tra.</td></tr>
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

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views/admin/pages/floor-inspections/index.blade.php ENDPATH**/ ?>