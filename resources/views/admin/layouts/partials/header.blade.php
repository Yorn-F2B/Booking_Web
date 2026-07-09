<button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Mở menu">
    <i class="bx bx-menu"></i>
</button>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-brand">
        <a href="{{ route('admin.dashboard') }}">
            <span class="logo-mark">MC</span>
            <span>MCuong Hotel<small>Bảng quản trị</small></span>
        </a>
    </div>

    <nav class="admin-nav">
        @php
            $userRole = auth()->user()->role ?? null;
            $isSuperAdmin = $userRole === 'super_admin';
            $isManager = $userRole === 'manager';
            $isReceptionistLead = $userRole === 'receptionist_lead';
            $isReceptionist = $userRole === 'receptionist';
            $isHousekeepingSupervisor = $userRole === 'housekeeping_supervisor';
            $isHousekeeping = $userRole === 'housekeeping';
        @endphp

        <div class="admin-nav-label">Tổng quan</div>

        <a href="{{ route('admin.dashboard') }}"
            class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bx bx-grid-alt"></i>
            Dashboard
        </a>

        <div class="admin-nav-label">Quản lý</div>

        @if ($isSuperAdmin)
            <a href="{{ route('staffs.index') }}"
                class="admin-nav-link {{ request()->routeIs('staffs.*') ? 'active' : '' }}">
                <i class="bx bx-group"></i>
                Nhân viên
            </a>
        @endif

        @if ($isSuperAdmin || $isManager || $isReceptionistLead || $isHousekeepingSupervisor)
            <a href="{{ route('admin.staff-assignments.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.staff-assignments.*') ? 'active' : '' }}">
                <i class="bx bx-task"></i>
                Phân công nhân sự
            </a>
        @endif

        @if ($isSuperAdmin || $isManager)
            <a href="{{ route('room-categories.index') }}"
                class="admin-nav-link {{ request()->routeIs('room-categories.*') ? 'active' : '' }}">
                <i class="bx bx-bed"></i>
                Hạng phòng
            </a>

            <a href="{{ route('admin.rooms.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.rooms.*') ? 'active' : '' }}">
                <i class="bx bx-door-open"></i>
                Danh sách phòng
            </a>

            <a href="{{ route('services.index') }}"
                class="admin-nav-link {{ request()->routeIs('services.*') ? 'active' : '' }}">
                <i class="bx bx-food-menu"></i>
                Dịch vụ
            </a>

            <a href="{{ route('admin.promotions.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.promotions.*') ? 'active' : '' }}">
                <i class="bx bx-purchase-tag"></i>
                Mã khuyến mãi
            </a>

            <a href="{{ route('amenities.index') }}"
                class="admin-nav-link {{ request()->routeIs('amenities.*') ? 'active' : '' }}">
                <i class="bx bx-star"></i>
                Tiện ích
            </a>

            <a href="{{ route('admin.reviews.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                <i class="bx bx-message-square-check"></i>
                Đánh giá khách sạn
            </a>
        @endif

        @if ($isSuperAdmin || $isManager || $isReceptionistLead || $isReceptionist)
            <a href="{{ route('admin.room-availability.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.room-availability.*') ? 'active' : '' }}">
                <i class="bx bx-search"></i>
                Tra cứu phòng trống
            </a>

            <a href="{{ route('admin.bookings.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                <i class="bx bx-calendar-check"></i>
                Đặt phòng
            </a>

            <a href="{{ route('admin.invoices.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                <i class="bx bx-file"></i>
                Hóa đơn
            </a>
        @endif

        @if ($isSuperAdmin || $isManager || $isReceptionistLead || $isReceptionist)
            <a href="{{ route('admin.chats.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.chats.*') ? 'active' : '' }}">
                <i class="bx bx-message-rounded-dots"></i>
                Tin nhắn khách hàng
            </a>
        @endif

        @if ($isSuperAdmin || $isManager || $isHousekeepingSupervisor || $isHousekeeping)
            <a href="{{ route('admin.housekeeping.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.housekeeping.*') ? 'active' : '' }}">
                <i class="bx bx-brush"></i>
                Phòng cần dọn
            </a>

            <a href="{{ route('admin.floor-inspections.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.floor-inspections.*') ? 'active' : '' }}">
                <i class="bx bx-search-alt"></i>
                Yêu cầu kiểm tra phòng
            </a>
        @endif

        @if ($isSuperAdmin || $isManager)
            <a href="{{ route('admin.inspection-approvals.index') }}"
                class="admin-nav-link {{ request()->routeIs('admin.inspection-approvals.*') ? 'active' : '' }}">
                <i class="bx bx-check-shield"></i>
                Duyệt kiểm tra phòng
            </a>
        @endif

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
</aside>
