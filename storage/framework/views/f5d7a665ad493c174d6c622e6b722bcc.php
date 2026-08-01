<?php $__env->startSection('title','Phòng cần sửa'); ?>
<?php $__env->startSection('content'); ?>
<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Phòng cần sửa</p>

        <div class="admin-page-head">
            <div>
                <h2>Phòng cần sửa</h2>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge text-bg-danger p-2">Đỏ: sửa gấp khi khách vẫn đang ở phòng</span>
            <span class="badge bg-white text-dark border p-2">Trắng: công việc sửa phòng thông thường</span>
        </div>

        <div class="settings-section mb-3">
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select" style="max-width:260px">
                    <option value="waiting" <?php if($status==='waiting'): echo 'selected'; endif; ?>>Đang chờ sửa</option>
                    <option value="completed" <?php if($status==='completed'): echo 'selected'; endif; ?>>Đã sửa xong</option>
                    <option value="all" <?php if($status==='all'): echo 'selected'; endif; ?>>Tất cả</option>
                </select>
                <button class="btn btn-primary">Lọc</button>
            </form>
        </div>

        <div class="settings-section">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Phòng cần sửa</th>
                            <th>Sự cố</th>
                            <th>Khách đã chuyển đến</th>
                            <th>Quản lý duyệt</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $isUrgentWhileOccupied = $issue->status === 'repair_only'
                                    && $issue->repair_status === 'waiting';
                            ?>
                            <tr class="<?php echo e($isUrgentWhileOccupied ? 'table-danger' : ''); ?>">
                                <td>
                                    <strong>Phòng <?php echo e($issue->currentRoom?->room_number); ?></strong>
                                    <div class="small text-muted"><?php echo e($issue->currentRoom?->category?->name); ?></div>
                                    <div class="small text-muted">Booking <?php echo e($issue->booking?->booking_code); ?></div>
                                </td>
                                <td style="max-width:360px"><?php echo e(\Illuminate\Support\Str::limit($issue->issue_description,120)); ?></td>
                                <td><?php echo e($issue->approvedRoom?->room_number ? 'Phòng '.$issue->approvedRoom->room_number : 'Khách vẫn ở phòng cũ'); ?></td>
                                <td>
                                    <?php echo e($issue->reviewer?->name ?? '---'); ?>

                                    <div class="small text-muted"><?php echo e($issue->reviewed_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?></div>
                                </td>
                                <td>
                                    <?php if($issue->repair_status === 'completed'): ?>
                                        <span class="badge text-bg-success">Đã sửa xong</span>
                                    <?php elseif($isUrgentWhileOccupied): ?>
                                        <span class="badge text-bg-danger">Sửa gấp - khách đang ở</span>
                                    <?php else: ?>
                                        <span class="badge bg-white text-dark border">Cần khắc phục</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo e(route('admin.room-repairs.show',$issue)); ?>" class="btn btn-sm <?php echo e($isUrgentWhileOccupied ? 'btn-danger' : 'btn-outline-primary'); ?>">
                                        Xem việc
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Không có phòng phù hợp.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3"><?php echo e($issues->links()); ?></div>
        </div>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views/admin/pages/room-repairs/index.blade.php ENDPATH**/ ?>