<?php $__env->startSection('content'); ?>
<div class="admin-wrapper">
<main class="admin-content">
<div class="container-fluid px-0">
    <div class="admin-page-head d-flex justify-content-between align-items-center">
        <h1 class="mb-0">Yêu cầu đến muộn</h1>
    </div>

    <form class="card card-body row g-2 mb-3 mx-0">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Tất cả trạng thái</option>
                <?php $__currentLoopData = ['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối','completed'=>'Hoàn tất']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($key); ?>" <?php if(request('status')===$key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col"><button class="btn btn-primary">Lọc</button></div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead><tr><th>Mã đơn</th><th>Khách</th><th>Giờ dự kiến đến</th><th>Nguồn</th><th>Trạng thái</th><th>Ngày gửi</th><th></th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($item->booking?->booking_code); ?></td>
                        <td><?php echo e($item->customer_name); ?></td>
                        <td><?php echo e(optional($item->expected_arrival_at)->format('d/m/Y H:i')); ?></td>
                        <td><?php echo e($item->source==='customer_web' ? 'Website' : 'Email vãng lai'); ?></td>
                        <td><?php echo e($item->status_label); ?></td>
                        <td><?php echo e(optional($item->created_at)->format('d/m/Y H:i')); ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="<?php echo e(route('admin.customer-requests.show',$item)); ?>">Xử lý</a></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có yêu cầu đến muộn</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3"><?php echo e($items->links()); ?></div>
</div>
</main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/customer-requests/index.blade.php ENDPATH**/ ?>