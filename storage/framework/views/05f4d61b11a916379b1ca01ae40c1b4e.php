<?php $__env->startSection('title', 'Sửa nhân viên'); ?>

<?php $__env->startSection('content'); ?>
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('staffs.index')); ?>">Admin</a> /
                <a href="<?php echo e(route('staffs.index')); ?>">Nhân viên</a> /
                Sửa
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Sửa nhân viên</h2>
                    <p>Mã nhân viên: <strong>#<?php echo e($staff->id); ?></strong></p>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?php echo e(route('staffs.show', $staff->id)); ?>" class="btn btn-outline-secondary">
                        Xem chi tiết
                    </a>

                    <a href="<?php echo e(route('staffs.index')); ?>" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Danh sách
                    </a>
                </div>
            </div>

            <form class="settings-section" action="<?php echo e(route('staffs.update', $staff->id)); ?>" method="POST"
                enctype="multipart/form-data">

                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <h5 class="mb-3">Thông tin tài khoản</h5>

                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            Email đăng nhập
                        </label>

                        <input type="email" name="email" class="form-control"
                            value="<?php echo e(old('email', $staff->user->email ?? '')); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            Mật khẩu mới
                        </label>

                        <input type="password" name="password" class="form-control">

                        <small class="text-muted">
                            Để trống nếu không đổi mật khẩu
                        </small>
                    </div>
                </div>

                <h5 class="mb-3">Thông tin nhân viên</h5>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">
                            ID
                        </label>

                        <input type="text" class="form-control" value="<?php echo e($staff->id); ?>" readonly>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">
                            Họ và tên
                        </label>

                        <input type="text" name="full_name" class="form-control"
                            value="<?php echo e(old('full_name', $staff->full_name)); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            Số điện thoại
                        </label>

                        <input type="text" name="phone" class="form-control" value="<?php echo e(old('phone', $staff->phone)); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            CCCD
                        </label>

                        <input type="text" name="cccd" class="form-control" value="<?php echo e(old('cccd', $staff->cccd)); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">
                            Ngày sinh
                        </label>

                        <input type="date" name="birthday" class="form-control"
                            value="<?php echo e(old('birthday', $staff->birthday)); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">
                            Giới tính
                        </label>

                        <select name="gender" class="form-select">
                            <option value="male" <?php echo e($staff->gender == 'male' ? 'selected' : ''); ?>>
                                Nam
                            </option>

                            <option value="female" <?php echo e($staff->gender == 'female' ? 'selected' : ''); ?>>
                                Nữ
                            </option>

                            <option value="other" <?php echo e($staff->gender == 'other' ? 'selected' : ''); ?>>
                                Khác
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">
                            Ngày vào làm
                        </label>

                        <input type="date" name="hire_date" class="form-control"
                            value="<?php echo e(old('hire_date', $staff->hire_date)); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">
                            Địa chỉ
                        </label>

                        <textarea name="address" class="form-control"
                            rows="2"><?php echo e(old('address', $staff->address)); ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            Chức vụ
                        </label>

                        <select name="position" class="form-select">
                            <option value="">— Chọn chức vụ —</option>

                            <option value="Quản lý" <?php echo e(old('position', $staff->position) == 'Quản lý' ? 'selected' : ''); ?>>
                                Quản lý
                            </option>

                            <option value="Lễ tân" <?php echo e(old('position', $staff->position) == 'Lễ tân' ? 'selected' : ''); ?>>
                                Lễ tân
                            </option>

                            <option value="Buồng phòng" <?php echo e(old('position', $staff->position) == 'Buồng phòng' ? 'selected' : ''); ?>>
                                Buồng phòng
                            </option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            Lương
                        </label>

                        <input type="number" name="salary" class="form-control" value="<?php echo e(old('salary', $staff->salary)); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            Trạng thái làm việc
                        </label>

                        <select name="work_status" class="form-select">

                            <option value="working" <?php echo e($staff->work_status == 'working' ? 'selected' : ''); ?>>
                                Đang làm việc
                            </option>

                            <option value="temporary_leave" <?php echo e($staff->work_status == 'temporary_leave' ? 'selected' : ''); ?>>
                                Nghỉ tạm
                            </option>

                            <option value="resigned" <?php echo e($staff->work_status == 'resigned' ? 'selected' : ''); ?>>
                                Đã nghỉ
                            </option>

                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            Ảnh đại diện
                        </label>

                        <input type="file" name="avatar" id="staffAvatar" class="form-control" accept="image/*" data-persistent-files>
                    </div>

                </div>

                <hr class="my-4">

                <div class="d-flex gap-2 justify-content-end">

                    <a href="<?php echo e(route('staffs.index')); ?>" class="btn btn-outline-secondary">
                        Hủy
                    </a>

                    <button type="submit" class="btn btn-gold">
                        <i class="bx bx-save me-1"></i>
                        Cập nhật
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
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\staffs\edit.blade.php ENDPATH**/ ?>