<?php $__env->startSection('title', 'Sự cố phòng'); ?>
<?php $__env->startSection('content'); ?>
<div class="admin-wrapper"><main class="admin-content">
    <p class="admin-breadcrumb mb-3"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Sự cố phòng</p>
    <div class="admin-page-head">
        <div><h2>Sự cố phòng</h2><p>Quản lý duyệt các yêu cầu khách báo sau khi đã nhận phòng.</p></div>
        <span class="badge text-bg-warning fs-6"><?php echo e($pendingCount); ?> chờ duyệt</span>
    </div>

<div class="settings-section mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6"><label class="form-label">Tìm kiếm</label><input name="search" value="<?php echo e($search); ?>" class="form-control" placeholder="Phòng, mã booking, nội dung sự cố..."></div>
            <div class="col-md-3"><label class="form-label">Trạng thái</label><select name="status" class="form-select">
                <option value="pending" <?php if($status==='pending'): echo 'selected'; endif; ?>>Cần quản lý xử lý</option>
                <option value="waiting_guest" <?php if($status==='waiting_guest'): echo 'selected'; endif; ?>>Đang chờ khách xác nhận</option>
                <option value="approved" <?php if($status==='approved'): echo 'selected'; endif; ?>>Đã đổi phòng</option>
                <option value="repair_only" <?php if($status==='repair_only'): echo 'selected'; endif; ?>>Không còn phòng - sửa gấp</option>
                <option value="all" <?php if($status==='all'): echo 'selected'; endif; ?>>Tất cả</option>
            </select></div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill">Lọc</button><a href="<?php echo e(route('admin.room-issues.index')); ?>" class="btn btn-outline-secondary">Xóa</a></div>
        </form>
    </div>

    <div class="settings-section">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Phòng</th><th>Booking</th><th>Khách báo</th><th>Nội dung</th><th>Trạng thái</th><th>Thời gian</th><th></th></tr></thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php $displayStatus=$issue->repair_status==='completed'?'repair_completed':$issue->status; $labels=['pending'=>'Chờ quản lý duyệt','approved'=>'Đã đổi phòng/hạng','repair_only'=>'Đang khắc phục','repair_completed'=>'Đã sửa xong','rejected'=>'Đã từ chối']; ?>
                <tr>
                    <td><strong><?php echo e($issue->currentRoom?->room_number ?? '---'); ?></strong><div class="small text-muted"><?php echo e($issue->currentRoom?->category?->name); ?></div></td>
                    <td><a href="<?php echo e(route('admin.bookings.show',$issue->booking)); ?>" class="fw-semibold"><?php echo e($issue->booking?->booking_code); ?></a></td>
                    <td><?php echo e($issue->booking?->booked_customer_name ?: '---'); ?></td>
                    <td style="max-width:360px"><?php echo e(\Illuminate\Support\Str::limit($issue->issue_description,110)); ?></td>
                    <td><span class="badge <?php echo e($displayStatus==='pending'?'text-bg-warning':($displayStatus==='repair_completed'?'text-bg-success':($issue->status==='approved'?'text-bg-success':'text-bg-info'))); ?>"><?php echo e($labels[$displayStatus] ?? $displayStatus); ?></span></td>
                    <td><?php echo e($issue->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?></td>
                    <td class="text-end"><a href="<?php echo e(route('admin.room-issues.show',$issue)); ?>" class="btn btn-sm btn-outline-primary">Xem chi tiết</a></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Không có yêu cầu phù hợp.</td></tr>
            <?php endif; ?>
            </tbody>
        </table></div>
        <div class="mt-3"><?php echo e($issues->links()); ?></div>
    </div>
</main></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/room-issues/index.blade.php ENDPATH**/ ?>