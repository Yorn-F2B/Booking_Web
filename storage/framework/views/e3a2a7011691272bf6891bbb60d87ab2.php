<?php $__env->startSection('title', 'Thêm dịch vụ'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $typeLabels = $typeLabels ?? \App\Models\Service::typeLabels();
        $groupLabels = $groupLabels ?? \App\Models\Service::groupLabels();
        $billingRuleLabels = $billingRuleLabels ?? \App\Models\Service::billingRuleLabels();
    ?>

    <style>
        .service-form-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            padding: 18px;
        }

        .service-help {
            color: #64748b;
            font-size: 13px;
        }

        .service-suggest-box {
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 12px;
            background: #f8fafc;
        }

        .service-suggest-box code {
            color: #92400e;
        }
    </style>

    <div class="admin-wrapper">
        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('services.index')); ?>">Admin</a> / Thêm dịch vụ
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Thêm dịch vụ</h2>
                    <p>Thêm dịch vụ, minibar gọi thêm, minibar kiểm kê hoặc phí phát sinh</p>
                </div>

                <a href="<?php echo e(route('services.index')); ?>" class="btn btn-outline-secondary">
                    Quay lại
                </a>
            </div>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <strong>Không thể lưu dịch vụ:</strong>
                    <ul class="mb-0 mt-2">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('services.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="service-form-card">
                    <div class="row g-3">

                        <div class="col-lg-6">
                            <label class="form-label">Tên dịch vụ <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                value="<?php echo e(old('name')); ?>" placeholder="Ví dụ: Gửi ô tô qua đêm">
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label">Loại dịch vụ <span class="text-danger">*</span></label>
                            <select name="type" class="form-select <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php $__currentLoopData = $typeLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $typeValue => $typeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($typeValue); ?>" <?php echo e(old('type', 'service') == $typeValue ? 'selected' : ''); ?>>
                                        <?php echo e($typeLabel); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label">Nhóm dịch vụ <span class="text-danger">*</span></label>
                            <select name="service_group" class="form-select <?php $__errorArgs = ['service_group'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php $__currentLoopData = $groupLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupValue => $groupLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($groupValue); ?>" <?php echo e(old('service_group', 'general') == $groupValue ? 'selected' : ''); ?>>
                                        <?php echo e($groupLabel); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['service_group'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Giá <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                value="<?php echo e(old('price')); ?>" min="0" step="1000" placeholder="Ví dụ: 100000">
                            <?php $__errorArgs = ['price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Đơn vị <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                value="<?php echo e(old('unit', 'lần')); ?>" placeholder="lần, đêm, giờ, chiếc...">
                            <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Cách tính khi đổi lịch <span class="text-danger">*</span></label>
                            <select name="billing_rule" class="form-select <?php $__errorArgs = ['billing_rule'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php $__currentLoopData = $billingRuleLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ruleValue => $ruleLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($ruleValue); ?>" <?php echo e(old('billing_rule', 'once') == $ruleValue ? 'selected' : ''); ?>>
                                        <?php echo e($ruleLabel); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['billing_rule'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select name="status" class="form-select <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <option value="active" <?php echo e(old('status', 'active') == 'active' ? 'selected' : ''); ?>>
                                    Hoạt động
                                </option>
                                <option value="inactive" <?php echo e(old('status') == 'inactive' ? 'selected' : ''); ?>>
                                    Tạm ẩn
                                </option>
                            </select>
                            <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" rows="4" class="form-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                placeholder="Mô tả dịch vụ"><?php echo e(old('description')); ?></textarea>
                            <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-gold">
                                Lưu dịch vụ
                            </button>

                            <a href="<?php echo e(route('services.index')); ?>" class="btn btn-outline-secondary">
                                Quay lại
                            </a>
                        </div>

                    </div>
                </div>
            </form>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\services\create.blade.php ENDPATH**/ ?>