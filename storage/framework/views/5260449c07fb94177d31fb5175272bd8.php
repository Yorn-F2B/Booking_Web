<?php $__env->startSection('title', 'Login'); ?>

<?php $__env->startSection('content'); ?>
<?php if(session('locked_until')): ?>
<div class="alert alert-warning" id="locked-login-countdown" data-until="<?php echo e(session('locked_until')); ?>">
    Thời gian khóa còn lại: <strong id="locked-login-output">Đang tính...</strong>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{const b=document.getElementById('locked-login-countdown');if(!b)return;const e=new Date(b.dataset.until).getTime(),o=document.getElementById('locked-login-output');const t=()=>{let d=Math.max(0,e-Date.now()),n=Math.floor(d/86400000);d%=86400000;let h=Math.floor(d/3600000);d%=3600000;let m=Math.floor(d/60000),s=Math.floor((d%60000)/1000);o.textContent=`${n} ngày ${h} giờ ${m} phút ${s} giây`;};t();setInterval(t,1000);});
</script>
<?php endif; ?>


    <section class="page-header">

        <div class="container">

            <h1 class="display-6 fw-bold mb-1">
                Đăng nhập tài khoản
            </h1>

            <p class="text-muted mb-0">
                Quản lý lịch sử đặt phòng và
                thông tin cá nhân của bạn.
            </p>

        </div>

    </section>

    <main class="py-5">

        <div class="container">

            <div class="row justify-content-center">

                <div class="col-lg-5">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body p-4">

                            <h2 class="h5 fw-bold mb-3">
                                Thông tin đăng nhập
                            </h2>

                            <?php if($errors->has('email') || $errors->has('password')): ?>
                                <div class="alert alert-danger">
                                    <?php echo e($errors->first('email') ?: $errors->first('password')); ?>

                                </div>
                            <?php endif; ?>

<form method="POST" action="<?php echo e(route('login')); ?>" id="loginForm">

                                <?php echo csrf_field(); ?>

                                
                                <div class="mb-3">

                                    <label class="form-label">
                                        Email
                                    </label>

                                    <input name="email" type="email" class="form-control" placeholder="email@domain.com"
                                        required />

                                </div>

                                
                                <div class="mb-3">

                                    <label class="form-label">
                                        Mật khẩu
                                    </label>

                                    <input name="password" type="password" class="form-control" placeholder="Nhập mật khẩu"
                                        required />

                                </div>

                                
                                <div class="form-check mb-3">

                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" />

                                    <label class="form-check-label" for="remember">

                                        Ghi nhớ đăng nhập

                                    </label>

                                </div>

                                
                                <div class="d-flex justify-content-between small mb-3">

                                    <?php if(Route::has('password.request')): ?>

                                        <a href="<?php echo e(route('password.request')); ?>" class="text-primary">

                                            Quên mật khẩu?

                                        </a>

                                    <?php endif; ?>

                                    <a href="<?php echo e(route('register')); ?>" class="text-primary">

                                        Chưa có tài khoản?

                                    </a>

                                </div>

                                
                                <button type="submit" class="btn btn-primary w-100">

                                    Đăng nhập

                                </button>

                            </form>

                            
                            <div class="d-flex align-items-center my-3">
                                <hr class="flex-grow-1">
                                <span class="mx-2 text-muted small">hoặc</span>
                                <hr class="flex-grow-1">
                            </div>

                            
                            <a href="<?php echo e(route('auth.google')); ?>"
                               id="btn-google-login"
                               class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2 google-btn">

                                
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20" height="20">
                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                                    <path fill="none" d="M0 0h48v48H0z"/>
                                </svg>

                                <span>Đăng nhập bằng Google</span>

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>

    <style>
        .google-btn {
            font-size: 0.925rem;
            font-weight: 500;
            border-color: #dadce0;
            color: #3c4043;
            background-color: #fff;
            transition: background-color 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .google-btn:hover {
            background-color: #f8f9fa;
            border-color: #c6c8ca;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            color: #3c4043;
        }

        .google-btn:active {
            background-color: #f1f3f4;
        }
    </style>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/auth/login.blade.php ENDPATH**/ ?>