<header class="site-header">
    <nav class="navbar navbar-expand-lg py-2">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand" href="<?php echo e(url('/')); ?>">
                <span class="logo-mark">MC</span>
                <span>
                    MCuong Hotel
                    <span class="brand-sub">Luxury &amp; Comfort</span>
                </span>
            </a>

            <!-- Toggler -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Nav links -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->is('/') ? 'active' : ''); ?>" href="<?php echo e(url('/')); ?>">Trang chủ</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->is('rooms') ? 'active' : ''); ?>" href="<?php echo e(url('rooms')); ?>">Hạng
                            phòng</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->is('about') ? 'active' : ''); ?>" href="<?php echo e(url('about')); ?>">Giới
                            thiệu</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->is('contact') ? 'active' : ''); ?>"
                            href="<?php echo e(url('contact')); ?>">Liên hệ</a>
                    </li>
                </ul>

                <!-- Right group -->
                <ul class="navbar-nav mb-2 mb-lg-0 align-items-lg-center gap-lg-2">

                    <?php if(auth()->guard()->guest()): ?>

                        
                        <li class="nav-item">

                            <a class="nav-link <?php echo e(request()->is('login') ? 'active' : ''); ?>" href="<?php echo e(route('login')); ?>">

                                <i class="bx bx-log-in me-1"></i>
                                Đăng nhập

                            </a>

                       </li>

                        
                        <li class="nav-item">

                            <a class="nav-link <?php echo e(request()->is('register') ? 'active' : ''); ?>"
                                href="<?php echo e(route('register')); ?>">

                                Đăng ký

                            </a>

                        </li>

                    <?php endif; ?>


                    <?php if(auth()->guard()->check()): ?>

                        
                        <?php if(in_array(Auth::user()->role, ['super_admin', 'manager', 'receptionist_lead', 'receptionist', 'housekeeping_supervisor', 'housekeeping'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo e(request()->is('admin/*') ? 'active' : ''); ?>"
                                    href="<?php echo e(url('admin')); ?>" title="Trang quản trị">
                                    <i class="bx bx-cog me-1"></i>
                                    Quản trị
                                </a>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAdmin" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-user-circle" style="font-size:1.25rem;vertical-align:middle"></i>
                                    <?php echo e(Auth::user()->name); ?>

                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdownAdmin">
                                    <li><a class="dropdown-item" href="<?php echo e(url('user-settings')); ?>">Thông tin tài khoản</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        
                        <?php if(Auth::user()->role === 'customer'): ?>
                            <?php
                                $hasActiveRoomOrder = Auth::user()->customer?->bookings()
                                    ->whereIn('status', ['pending', 'confirmed', 'checked_in', 'inspection_requested'])
                                    ->exists() ?? false;
                            ?>
                            <?php if($hasActiveRoomOrder): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo e(request()->routeIs('bookings.current') || request()->routeIs('bookings.show') ? 'active' : ''); ?>"
                                        href="<?php echo e(route('bookings.current')); ?>" title="Đơn phòng">
                                        <i class="bx bx-receipt me-1"></i>
                                        Đơn phòng
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                                $customerUnreadNotifications = \App\Models\OperationalNotification::query()
                                    ->where('user_id', Auth::id())->whereNull('read_at')->count();
                            ?>
                            <li class="nav-item position-relative">
                                <a class="nav-link <?php echo e(request()->routeIs('notifications.*') ? 'active' : ''); ?>"
                                    href="<?php echo e(route('notifications.index')); ?>" title="Thông báo" aria-label="Thông báo">
                                    <i class="bx bx-bell" style="font-size:1.25rem;vertical-align:middle"></i>
                                    <?php if($customerUnreadNotifications > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="margin-left:-10px;margin-top:8px"><span class="visually-hidden">Có thông báo chưa đọc</span></span>
                                    <?php endif; ?>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo e(request()->is('user-settings') ? 'active' : ''); ?>"
                                    href="<?php echo e(url('user-settings')); ?>" title="Tài khoản">
                                    <i class="bx bx-user-circle" style="font-size:1.25rem;vertical-align:middle"></i>
                                    <?php echo e(Auth::user()->name); ?>

                                </a>
                            </li>
                        <?php endif; ?>

                        
                        <li class="nav-item">

                            <form method="POST" action="<?php echo e(route('logout')); ?>">

                                <?php echo csrf_field(); ?>

                                <button type="submit" class="btn nav-link border-0 bg-transparent">

                                    <i class="bx bx-log-out me-1"></i>
                                    Đăng xuất

                                </button>

                            </form>

                        </li>

                    <?php endif; ?>

                </ul>

            </div>
        </div>
    </nav>
</header><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/user/partials/header.blade.php ENDPATH**/ ?>