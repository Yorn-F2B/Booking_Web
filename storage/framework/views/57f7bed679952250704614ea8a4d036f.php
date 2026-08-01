<?php $__env->startSection('title', 'Thêm nhân viên'); ?>

<?php $__env->startSection('content'); ?>
    <div class="admin-wrapper">
        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('staffs.index')); ?>">Admin</a> /
                <a href="<?php echo e(route('staffs.index')); ?>">Nhân viên</a> /
                Thêm mới
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Thêm nhân viên</h2>
                    <p>Tạo tài khoản đăng nhập và thông tin nhân viên</p>
                </div>

                <a href="<?php echo e(route('staffs.index')); ?>" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Quay lại
                </a>
            </div>

            <form class="settings-section" action="<?php echo e(route('staffs.store')); ?>" method="POST" enctype="multipart/form-data"
                autocomplete="off">
                <?php echo csrf_field(); ?>

                <h5 class="mb-3">Thông tin tài khoản</h5>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email đăng nhập <span
                                class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            value="<?php echo e(old('email')); ?>" required autocomplete="off">

                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback">
                                <?php echo e($message); ?>

                            </div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            required autocomplete="new-password">

                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback">
                                <?php echo e($message); ?>

                            </div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <h5 class="mb-3">Thông tin nhân viên</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control <?php $__errorArgs = ['full_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            value="<?php echo e(old('full_name')); ?>" required>

                        <?php $__errorArgs = ['full_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback">
                                <?php echo e($message); ?>

                            </div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            value="<?php echo e(old('phone')); ?>">

                        <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback">
                                <?php echo e($message); ?>

                            </div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">CCCD</label>
                        <input type="text" name="cccd" class="form-control <?php $__errorArgs = ['cccd'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            value="<?php echo e(old('cccd')); ?>">

                        <?php $__errorArgs = ['cccd'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback">
                                <?php echo e($message); ?>

                            </div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Ngày sinh</label>
                        <input type="date" name="birthday" class="form-control" value="<?php echo e(old('birthday')); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Giới tính</label>
                        <select name="gender" class="form-select">
                            <option value="">— Chọn —</option>
                            <option value="male" <?php echo e(old('gender') == 'male' ? 'selected' : ''); ?>>Nam</option>
                            <option value="female" <?php echo e(old('gender') == 'female' ? 'selected' : ''); ?>>Nữ</option>
                            <option value="other" <?php echo e(old('gender') == 'other' ? 'selected' : ''); ?>>Khác</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Ngày vào làm</label>
                        <input type="date" name="hire_date" class="form-control" value="<?php echo e(old('hire_date')); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Trạng thái</label>
                        <select name="work_status" class="form-select">
                            <option value="working" <?php echo e(old('work_status') == 'working' ? 'selected' : ''); ?>>Đang làm việc
                            </option>
                            <option value="temporary_leave" <?php echo e(old('work_status') == 'temporary_leave' ? 'selected' : ''); ?>>
                                Nghỉ tạm</option>
                            <option value="resigned" <?php echo e(old('work_status') == 'resigned' ? 'selected' : ''); ?>>Đã nghỉ</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo e(old('address')); ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Chức vụ</label>
                        <select name="position" class="form-select">
                            <option value="">— Chọn chức vụ —</option>
                            <option value="Quản lý" <?php echo e(old('position') == 'Quản lý' ? 'selected' : ''); ?>>Quản lý</option>
                            <option value="Trưởng lễ tân" <?php echo e(old('position') == 'Trưởng lễ tân' ? 'selected' : ''); ?>>Trưởng lễ tân</option>
                            <option value="Lễ tân" <?php echo e(old('position') == 'Lễ tân' ? 'selected' : ''); ?>>Lễ tân</option>
                            <option value="Trưởng buồng phòng" <?php echo e(old('position') == 'Trưởng buồng phòng' ? 'selected' : ''); ?>>Trưởng buồng phòng</option>
                            <option value="Buồng phòng" <?php echo e(old('position') == 'Buồng phòng' ? 'selected' : ''); ?>>Buồng phòng</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Lương</label>
                        <input type="number" name="salary" class="form-control" min="0" step="1000"
                            value="<?php echo e(old('salary', 0)); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Ảnh đại diện</label>
                        <input type="file" name="avatar" id="staffAvatar" class="form-control" accept="image/*" data-persistent-files>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex gap-2 justify-content-end">
                    <a href="<?php echo e(route('staffs.index')); ?>" class="btn btn-outline-secondary">Hủy</a>
                    <button type="submit" class="btn btn-gold">
                        <i class="bx bx-save me-1"></i>Lưu
                    </button>
                </div>
            </form>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>

<script src="<?php echo e(asset('assets/js/persistent-file-inputs.js')); ?>?v=<?php echo e(filemtime(public_path('assets/js/persistent-file-inputs.js'))); ?>"></script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\staffs\create.blade.php ENDPATH**/ ?>