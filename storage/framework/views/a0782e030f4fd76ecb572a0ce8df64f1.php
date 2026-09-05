<?php $__env->startSection('title', 'Phụ thu / phí phát sinh'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $badgeClasses = [
        'damage_fee' => 'bg-danger',
        'occupancy_fee' => 'bg-info text-dark',
        'extra_guest_fee' => 'bg-info text-dark',
        'early_checkin_fee' => 'bg-warning text-dark',
        'late_checkout_fee' => 'bg-warning text-dark',
        'extension_fee' => 'bg-primary',
        'policy_violation_fee' => 'bg-dark',
        'manual_fee' => 'bg-secondary',
    ];
?>
<style>
    .surcharge-filter-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr auto;gap:10px}
    .surcharge-muted{color:#64748b;font-size:13px}
    @media(max-width:991px){.surcharge-filter-grid{grid-template-columns:1fr}}
</style>
<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Phụ thu / phí phát sinh</p>
        <div class="admin-page-head">
            <div>
                <h2>Phụ thu / phí phát sinh</h2>
                <p>Quản lý hư hại, quá số người, check-in sớm, check-out muộn, gia hạn, vi phạm chính sách và phí thủ công.</p>
            </div>
            <a href="<?php echo e(route('surcharges.create')); ?>" class="btn btn-gold"><i class="bx bx-plus me-1"></i>Thêm khoản phí</a>
        </div>

        <div class="settings-section mb-3">
            <form method="GET" action="<?php echo e(route('surcharges.index')); ?>" class="surcharge-filter-grid">
                <input type="text" name="keyword" class="form-control" value="<?php echo e(request('keyword')); ?>" placeholder="Tìm tên, mô tả, đơn vị...">
                <select name="type" class="form-select">
                    <option value="">Tất cả loại phí</option>
                    <?php $__currentLoopData = $typeLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if(request('type') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <select name="status" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" <?php if(request('status') === 'active'): echo 'selected'; endif; ?>>Hoạt động</option>
                    <option value="inactive" <?php if(request('status') === 'inactive'): echo 'selected'; endif; ?>>Ngừng hoạt động</option>
                </select>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Lọc</button>
                    <a href="<?php echo e(route('surcharges.index')); ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="settings-section">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Tên khoản phí</th><th>Loại</th><th>Mức mặc định</th><th>Đơn vị</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $surcharges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $surcharge): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($surcharge->id); ?></td>
                            <td><div class="fw-semibold"><?php echo e($surcharge->name); ?></div><?php if($surcharge->description): ?><div class="surcharge-muted"><?php echo e(\Illuminate\Support\Str::limit($surcharge->description, 90)); ?></div><?php endif; ?></td>
                            <td><span class="badge <?php echo e($badgeClasses[$surcharge->type] ?? 'bg-secondary'); ?>"><?php echo e($surcharge->type_label); ?></span></td>
                            <td><?php echo e(number_format((float)$surcharge->price, 0, ',', '.')); ?>đ</td>
                            <td><?php echo e($surcharge->unit); ?></td>
                            <td><?php if($surcharge->status === 'active'): ?><span class="badge bg-success">Hoạt động</span><?php else: ?><span class="badge bg-secondary">Ngừng hoạt động</span><?php endif; ?></td>
                            <td class="text-end text-nowrap">
                                <a href="<?php echo e(route('surcharges.show', $surcharge)); ?>" class="btn btn-sm btn-outline-secondary">Xem</a>
                                <a href="<?php echo e(route('surcharges.edit', $surcharge)); ?>" class="btn btn-sm btn-outline-primary">Sửa</a>
                                <form method="POST" action="<?php echo e(route('surcharges.destroy', $surcharge)); ?>" class="d-inline" onsubmit="return confirm('Xóa khoản phí này? Nếu đã có lịch sử, hệ thống chỉ ngừng hoạt động để giữ dữ liệu cũ.')">
                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7" class="text-center text-muted">Chưa có phụ thu / phí phát sinh phù hợp.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3"><?php echo e($surcharges->links()); ?></div>
        </div>
    </main>
    <footer class="admin-footer"><span>MCuong Hotel Admin</span></footer>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/surcharges/index.blade.php ENDPATH**/ ?>