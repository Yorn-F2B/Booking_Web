<button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Mở menu">
    <i class="bx bx-menu"></i>
</button>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-brand">
        <a href="index.html">
            <span class="logo-mark">MC</span>
            <span>MCuong Hotel<small>Bảng quản trị</small></span>
        </a>
    </div>

    <nav class="admin-nav">
        <?php
            $userRole = auth()->user()->role ?? null;
            $isSuperAdmin = $userRole === 'super_admin';
            $isManager = $userRole === 'manager';
            $isReceptionist = $userRole === 'receptionist';
            $isHousekeeping = $userRole === 'housekeeping';
        ?>

        <div class="admin-nav-label">Tổng quan</div>

        <a href="#" class="admin-nav-link disabled">
            <i class="bx bx-grid-alt"></i>
            Dashboard
        </a>

        <div class="admin-nav-label">Quản lý</div>

        <?php if($isSuperAdmin): ?>
            <a href="<?php echo e(route('staffs.index')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('staffs.*') ? 'active' : ''); ?>">
                <i class="bx bx-group"></i>
                Nhân viên
            </a>
        <?php endif; ?>

        <?php if($isSuperAdmin || $isManager): ?>
            <a href="<?php echo e(route('room-categories.index')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('room-categories.*') ? 'active' : ''); ?>">
                <i class="bx bx-bed"></i>
                Hạng phòng
            </a>

            <a href="<?php echo e(route('admin.rooms.index')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('admin.rooms.*') ? 'active' : ''); ?>">
                <i class="bx bx-door-open"></i>
                Danh sách phòng
            </a>

            <a href="<?php echo e(route('services.index')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('services.*') ? 'active' : ''); ?>">
                <i class="bx bx-food-menu"></i>
                Dịch vụ
            </a>

            <a href="<?php echo e(route('admin.promotions.index')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('admin.promotions.*') ? 'active' : ''); ?>">
                <i class="bx bx-purchase-tag"></i>
                Mã khuyến mãi
            </a>

            <a href="<?php echo e(route('amenities.index')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('amenities.*') ? 'active' : ''); ?>">
                <i class="bx bx-star"></i>
                Tiện ích
            </a>
        <?php endif; ?>

        <a href="<?php echo e(route('admin.room-availability.index')); ?>"
            class="admin-nav-link <?php echo e(request()->routeIs('admin.room-availability.*') ? 'active' : ''); ?>">
            <i class="bx bx-search"></i>
            Tra cứu phòng trống
        </a>

        <?php if($isSuperAdmin || $isReceptionist): ?>
            <a href="<?php echo e(route('admin.bookings.index')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('admin.bookings.*') ? 'active' : ''); ?>">
                <i class="bx bx-calendar-check"></i>
                Đặt phòng
            </a>
        <?php endif; ?>

        <?php if($isSuperAdmin || $isManager || $isReceptionist): ?>
            <a href="<?php echo e(route('admin.chats.index')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('admin.chats.*') ? 'active' : ''); ?>">
                <i class="bx bx-message-rounded-dots"></i>
                Tin nhắn khách hàng
            </a>
        <?php endif; ?>

        <?php if($isSuperAdmin || $isHousekeeping): ?>
            <a href="<?php echo e(route('admin.housekeeping.index')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('admin.housekeeping.*') ? 'active' : ''); ?>">
                <i class="bx bx-brush"></i>
                Phòng cần dọn
            </a>

            <a href="<?php echo e(route('admin.floor-inspections.index')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('admin.floor-inspections.*') ? 'active' : ''); ?>">
                <i class="bx bx-search-alt"></i>
                Yêu cầu kiểm tra phòng
            </a>
        <?php endif; ?>

        <?php if($isSuperAdmin || $isManager): ?>
            <a href="<?php echo e(route('admin.inspection-approvals.index')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('admin.inspection-approvals.*') ? 'active' : ''); ?>">
                <i class="bx bx-check-shield"></i>
                Duyệt kiểm tra phòng
            </a>
        <?php endif; ?>

        <a href="#" class="admin-nav-link disabled">
            <i class="bx bx-user"></i>
            Khách hàng
        </a>
    </nav>

    <div class="admin-sidebar-user">
        <img src="https://images.pexels.com/photos/2379004/pexels-photo-2379004.jpeg?auto=compress&cs=tinysrgb&w=80"
            alt="Admin">
        <div class="admin-sidebar-user-info">
            <span class="admin-sidebar-user-name">Admin</span>
            <span class="admin-sidebar-user-role">Quản trị viên</span>
        </div>
        <a href="#" class="admin-sidebar-user-action" title="Cài đặt"><i class="bx bx-cog"></i></a>
    </div>

    <div class="admin-sidebar-footer">© 2026 MCuong Hotel</div>
</aside><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/layouts/partials/header.blade.php ENDPATH**/ ?>