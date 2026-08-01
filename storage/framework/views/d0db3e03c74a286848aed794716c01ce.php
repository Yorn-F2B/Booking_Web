<?php $__env->startSection('title', 'Phòng cần sửa'); ?>

<?php $__env->startSection('content'); ?>
<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Buồng phòng / Phòng cần sửa
        </p>

        <div class="admin-page-head">
            <div>
                <h2>Phòng cần sửa</h2>
            </div>
            <span class="badge bg-danger fs-6"><?php echo e($pendingCount); ?> việc chưa xong</span>
        </div>

        <?php if(session('success')): ?><div class="alert alert-success"><?php echo e(session('success')); ?></div><?php endif; ?>
        <?php if(session('error')): ?><div class="alert alert-danger"><?php echo e(session('error')); ?></div><?php endif; ?>

        <div class="settings-section mb-4">
            <div class="btn-group" role="group">
                <a href="<?php echo e(route('admin.housekeeping.repairs', ['status' => 'pending'])); ?>" class="btn <?php echo e($status === 'pending' ? 'btn-primary' : 'btn-outline-primary'); ?>">Chưa sửa</a>
                <a href="<?php echo e(route('admin.housekeeping.repairs', ['status' => 'completed'])); ?>" class="btn <?php echo e($status === 'completed' ? 'btn-primary' : 'btn-outline-primary'); ?>">Đã sửa</a>
                <a href="<?php echo e(route('admin.housekeeping.repairs', ['status' => 'all'])); ?>" class="btn <?php echo e($status === 'all' ? 'btn-primary' : 'btn-outline-primary'); ?>">Tất cả</a>
            </div>
        </div>

        <div class="settings-section">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Phòng cần sửa</th>
                            <th>Sự cố</th>
                            <th>Booking</th>
                            <th>Quản lý duyệt</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <div class="fw-bold fs-5">Phòng <?php echo e($issue->currentRoom?->room_number ?? '---'); ?></div>
                                    <div class="small text-muted">Tầng <?php echo e($issue->currentRoom?->floor_number ?? '---'); ?> · <?php echo e($issue->currentRoom?->category?->name ?? '---'); ?></div>
                                </td>
                                <td style="min-width:280px">
                                    <div><?php echo e($issue->issue_description); ?></div>
                                    <?php if($issue->attachments->isNotEmpty()): ?>
                                        <div class="d-flex gap-1 flex-wrap mt-2">
                                            <?php $__currentLoopData = $issue->attachments->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <a href="<?php echo e(route('admin.room-issues.attachments.show', $attachment)); ?>" target="_blank">
                                                    <img src="<?php echo e(route('admin.room-issues.attachments.show', $attachment)); ?>" alt="Ảnh sự cố"
                                                        style="width:64px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #ddd">
                                                </a>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo e($issue->booking?->booking_code); ?></div>
                                    <div class="small text-muted"><?php echo e($issue->booking?->booked_customer_name); ?></div>
                                </td>
                                <td>
                                    <div><?php echo e($issue->reviewer?->name ?? '---'); ?></div>
                                    <div class="small text-muted"><?php echo e(optional($issue->reviewed_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?></div>
                                </td>
                                <td>
                                    <?php if($issue->repair_status === 'completed'): ?>
                                        <span class="badge bg-success">Đã sửa xong</span>
                                        <div class="small text-muted mt-1"><?php echo e($issue->repairCompleter?->name); ?><br><?php echo e(optional($issue->repair_completed_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Cần xử lý ngay</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if($issue->repair_status === 'waiting'): ?>
                                        <form action="<?php echo e(route('admin.housekeeping.repairs.complete', $issue)); ?>" method="POST"
                                            onsubmit="return confirm('Xác nhận đã khắc phục xong sự cố phòng này?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <button class="btn btn-success btn-sm" type="submit">Đã sửa xong</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Hoàn tất</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Không có phòng cần sửa trong bộ lọc này.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3"><?php echo e($issues->links()); ?></div>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\housekeeping\repairs.blade.php ENDPATH**/ ?>