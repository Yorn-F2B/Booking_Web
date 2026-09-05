<?php $__env->startSection('title', $title); ?>
<?php $__env->startSection('content'); ?>
<div class="admin-wrapper"><div class="admin-content">
    <div class="admin-page-head d-flex justify-content-between align-items-start gap-3">
        <div><h1><?php echo e($title); ?></h1><p><?php echo e($formula); ?></p></div>
        <a class="btn btn-outline-secondary" href="<?php echo e(route('admin.dashboard',['from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')])); ?>"><i class="bx bx-arrow-back me-1"></i>Dashboard</a>
    </div>
    <div class="card mb-3"><div class="card-body d-flex justify-content-between flex-wrap gap-2"><span><strong>Kỳ:</strong> <?php echo e($from->format('d/m/Y')); ?> – <?php echo e($to->format('d/m/Y')); ?></span><span><strong><?php echo e(in_array($metric,['new_bookings','failed_emails']) ? 'Số lượng' : 'Tổng'); ?>:</strong> <?php echo e(in_array($metric,['new_bookings','failed_emails']) ? number_format($total,0,',','.') : number_format($total,0,',','.') . 'đ'); ?></span></div></div>
    <div class="card"><div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>Booking</th><th>Khách / người nhận</th><th>Loại / trạng thái</th><th>Thời điểm</th><th class="text-end">Số tiền</th><th></th></tr></thead><tbody>
    <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($row['booking'] ?: '—'); ?></td><td><?php echo e($row['customer'] ?: '—'); ?></td><td><?php echo e($row['kind'] ?: '—'); ?></td><td><?php echo e($row['time'] ? \Carbon\Carbon::parse($row['time'])->format('d/m/Y H:i') : '—'); ?></td><td class="text-end"><?php echo e($row['amount'] === null ? '—' : number_format($row['amount'],0,',','.') . 'đ'); ?></td><td class="text-end"><?php if($row['url']): ?><a href="<?php echo e($row['url']); ?>" class="btn btn-sm btn-outline-primary">Xem</a><?php endif; ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="6" class="text-center text-muted py-5">Không có dữ liệu trong kỳ.</td></tr><?php endif; ?>
    </tbody></table></div></div>
</div></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/dashboard/detail.blade.php ENDPATH**/ ?>