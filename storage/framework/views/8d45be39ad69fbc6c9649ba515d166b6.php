

<?php $__env->startSection('title', 'Register'); ?>

<?php $__env->startSection('content'); ?>

    <section class="page-header">
        <div class="container">

            <h1 class="display-6 fw-bold mb-1">
                Đăng ký tài khoản khách hàng
            </h1>

            <p class="text-muted mb-0">
                Nhập đầy đủ thông tin để đặt phòng
                và quản lý hồ sơ nhanh hơn.
            </p>

        </div>
    </section>

    <main class="py-5">

        <div class="container">

            <div class="row justify-content-center">

                <div class="col-lg-8">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body p-4">

                            <form method="POST" action="<?php echo e(route('register')); ?>" id="registerForm">

                                <?php echo csrf_field(); ?>

                                <?php if($errors->any()): ?>
                                    <div class="alert alert-danger mb-3">
                                        <strong>Có lỗi xảy ra:</strong>
                                        <ul class="mb-0">
                                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <li><?php echo e($error); ?></li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <div class="row g-3">

                                    
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Họ
                                        </label>

                                        <input name="last_name" type="text" class="form-control" required />

                                    </div>

                                    
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Tên
                                        </label>

                                        <input name="first_name" type="text" class="form-control" required />

                                    </div>

                                    <div class="col-12">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                <button type="button" id="registerCccdButton" class="btn btn-outline-primary" onclick="document.getElementById('registerCccdImage').click()">Quét ảnh CCCD</button>
                                                <input type="file" id="registerCccdImage" class="d-none js-cccd-image" accept="image/*" capture="environment"
                                                    data-button="#registerCccdButton" data-status="#registerCccdStatus"
                                                    data-target-cccd="input[name='cccd']" data-target-first-name="input[name='first_name']"
                                                    data-target-last-name="input[name='last_name']" data-target-birthday="#reg_birthday"
                                                    data-target-gender="select[name='gender']" data-target-address="textarea[name='address']"
                                                    data-required-fields="cccd,full_name,birthday,gender,address">
                                                <small id="registerCccdStatus" class="text-muted"></small>
                                            </div>
                                        </div>
                                    </div>

                                    
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Số CCCD
                                        </label>

                                        <input name="cccd" type="text" class="form-control" maxlength="12"
                                            pattern="[0-9]{12}" required />

                                    </div>

                                    
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Số điện thoại
                                        </label>

                                        <input name="phone" type="tel" class="form-control" pattern="0[0-9]{9}" required />

                                    </div>

                                    
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Email
                                        </label>

                                        <input name="email" type="email" class="form-control" required />

                                    </div>

                                    
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Ngày sinh
                                        </label>

                                        <?php ($bdVal = old('birthday', '')); ?>
                                        <input type="date" name="birthday" id="reg_birthday"
                                            class="form-control" value="<?php echo e($bdVal); ?>"
                                            min="1900-01-01" max="<?php echo e(now('Asia/Ho_Chi_Minh')->toDateString()); ?>"
                                            data-birth-date autocomplete="bday">

                                    </div>

                                    
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Giới tính
                                        </label>

                                        <select name="gender" class="form-select">

                                            <option value="male">
                                                Nam
                                            </option>

                                            <option value="female">
                                                Nữ
                                            </option>

                                            <option value="other">
                                                Khác
                                            </option>

                                        </select>

                                    </div>

                                    
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Mật khẩu
                                        </label>

                                        <input name="password" type="password" class="form-control" required />

                                    </div>

                                    
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Xác nhận mật khẩu
                                        </label>

                                        <input name="password_confirmation" type="password" class="form-control" required />

                                    </div>

                                    
                                    <div class="col-12">

                                        <label class="form-label">
                                            Địa chỉ liên hệ
                                        </label>

                                        <textarea name="address" class="form-control" rows="2"></textarea>

                                    </div>

                                </div>

                                
                                <div class="form-check mt-3">

                                    <input class="form-check-input" type="checkbox" id="policyCheck" required />

                                    <label class="form-check-label small" for="policyCheck">

                                        Tôi đồng ý với điều khoản sử dụng
                                        và chính sách bảo mật.

                                    </label>

                                </div>

                                
                                <div class="d-flex gap-2 mt-3">

                                    <button type="submit" class="btn btn-primary">

                                        Tạo tài khoản

                                    </button>

                                    <a href="<?php echo e(route('login')); ?>" class="btn btn-outline-primary">

                                        Đã có tài khoản

                                    </a>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>
    <style>
        .bd-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.18);
        }
    </style>
<?php echo $__env->make('partials.cccd-scanner-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\auth\register.blade.php ENDPATH**/ ?>