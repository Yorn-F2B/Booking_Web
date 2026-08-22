<?php $__env->startSection('title', 'Chính sách khách sạn'); ?>

<?php $__env->startSection('content'); ?>
<div class="admin-wrapper">
    <main class="admin-content">
        <div class="admin-page-head">
            <div>
                <h2>Chính sách khách sạn</h2>
                <p>Các mốc nghiệp vụ dùng cho booking mới. Booking đã chốt giữ snapshot để không thay đổi dữ liệu lịch sử.</p>
            </div>
        </div>

        <form method="POST" action="<?php echo e(route('admin.policies.update')); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $rows): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <section class="card mb-3">
                    <div class="card-header">
                        <strong><?php echo e($groupLabels[$group] ?? ucfirst($group)); ?></strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $policy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $inputType = $policy->type === 'time'
                                        ? 'time'
                                        : (in_array($policy->type, ['integer', 'decimal'], true) ? 'number' : 'text');
                                    $step = $policy->type === 'decimal' ? '0.01' : '1';
                                ?>

                                <div class="col-12 col-xl-6">
                                    <label class="form-label fw-semibold" for="policy-<?php echo e($policy->id); ?>">
                                        <?php echo e($policy->label); ?>

                                    </label>
                                    <input
                                        id="policy-<?php echo e($policy->id); ?>"
                                        name="values[<?php echo e($policy->id); ?>]"
                                        type="<?php echo e($inputType); ?>"
                                        <?php if($inputType === 'number'): ?> step="<?php echo e($step); ?>" <?php endif; ?>
                                        class="form-control <?php $__errorArgs = ['values.' . $policy->id];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('values.' . $policy->id, $policy->value)); ?>"
                                        required
                                    >
                                    <?php $__errorArgs = ['values.' . $policy->id];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <?php if($policy->description): ?>
                                        <div class="form-text"><?php echo e($policy->description); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <div class="d-flex justify-content-end pb-3">
                <button class="btn btn-primary px-4" type="submit">
                    <i class="bx bx-save me-1"></i>
                    Lưu chính sách
                </button>
            </div>
        </form>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/policies/index.blade.php ENDPATH**/ ?>