<?php $__env->startSection('title', 'Từ cấm đánh giá'); ?>

<?php $__env->startSection('content'); ?>
<div class="admin-wrapper">
    <main class="admin-content">
        <div class="admin-page-head">
            <div>
                <h2>Từ cấm đánh giá</h2>
                <p>Đánh giá chứa nội dung bị cấm sẽ bị từ chối trước khi ghi vào hệ thống.</p>
            </div>
            <a href="<?php echo e(route('admin.banned-words.create')); ?>" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>
                Thêm từ cấm
            </a>
        </div>

        <section class="card">
            <div class="card-body">
                <?php $__empty_1 = true; $__currentLoopData = $words; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <span class="d-inline-flex align-items-center gap-2 border rounded-pill px-3 py-2 me-2 mb-2 bg-light">
                        <strong><?php echo e($item->word); ?></strong>
                        <form method="POST" action="<?php echo e(route('admin.banned-words.destroy', $item)); ?>" class="d-inline" onsubmit="return confirm('Xóa từ cấm này?');">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button class="btn btn-sm p-0 border-0 text-danger" title="Xóa ngay" aria-label="Xóa <?php echo e($item->word); ?>">
                                <i class="bx bx-x fs-5"></i>
                            </button>
                        </form>
                    </span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="text-muted text-center py-5">Chưa có từ cấm nào.</div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/banned-words/index.blade.php ENDPATH**/ ?>