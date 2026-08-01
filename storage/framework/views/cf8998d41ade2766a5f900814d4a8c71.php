<?php $__env->startSection('title', 'Thêm từ cấm'); ?>
<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4"><div class="row justify-content-center"><div class="col-lg-8">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<h3>Thêm từ cấm</h3><p class="text-muted">Có thể nhập nhiều từ, mỗi từ một dòng hoặc ngăn cách bằng dấu phẩy.</p>
<form method="POST" action="<?php echo e(route('admin.banned-words.store')); ?>"><?php echo csrf_field(); ?>
<textarea name="words" rows="10" class="form-control <?php $__errorArgs = ['words'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Ví dụ:&#10;từ thứ nhất&#10;từ thứ hai"><?php echo e(old('words')); ?></textarea>
<?php $__errorArgs = ['words'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
<div class="d-flex gap-2 mt-3"><button class="btn btn-primary">Lưu danh sách</button><a class="btn btn-light" href="<?php echo e(route('admin.banned-words.index')); ?>">Quay lại</a></div>
</form></div></div></div></div></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\banned-words\create.blade.php ENDPATH**/ ?>