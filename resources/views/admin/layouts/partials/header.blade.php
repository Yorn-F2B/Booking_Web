<button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Mở menu">
    <i class="bx bx-menu"></i>
</button>

@php
    $role = auth()->user()->role ?? null;
    $isSuperAdmin = $role === 'super_admin';
    $isManager = $role === 'manager';
    $isReceptionistLead = $role === 'receptionist_lead';
    $isReceptionist = $role === 'receptionist';
    $isHousekeepingSupervisor = $role === 'housekeeping_supervisor';
    $isHousekeeping = $role === 'housekeeping';

    $canManageStaff = $isSuperAdmin || $isManager || $isReceptionistLead || $isHousekeepingSupervisor;
    $canManageCatalog = $isSuperAdmin || $isManager;
    $canManageFrontDesk = $isSuperAdmin || $isManager || $isReceptionistLead || $isReceptionist;
    $canManageRooms = $isSuperAdmin || $isManager || $isHousekeepingSupervisor || $isHousekeeping;

    $operationsOpen = request()->routeIs([
        'admin.room-availability.*',
        'admin.bookings.*',
        'admin.chats.*',
    ]);

    $roomsOpen = request()->routeIs([
        'admin.housekeeping.*',
        'admin.floor-inspections.*',
        'admin.inspection-approvals.*',
    ]);

    $catalogOpen = request()->routeIs([
        'room-categories.*',
        'admin.rooms.*',
        'services.*',
        'admin.promotions.*',
        'amenities.*',
        'admin.reviews.*',
    ]);

    $staffOpen = request()->routeIs([
        'staffs.*',
        'admin.staff-assignments.*',
    ]);
@endphp

<style>
.admin-nav-group{margin:5px 9px}
.admin-nav-group>summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:10px;padding:10px 11px;border-radius:10px;color:#64748b;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
.admin-nav-group>summary::-webkit-details-marker{display:none}
.admin-nav-group>summary:hover{background:rgba(148,163,184,.1)}
.admin-nav-group>summary i:first-child{font-size:18px}.admin-nav-group>summary .group-chevron{margin-left:auto;transition:.2s}
.admin-nav-group[open]>summary .group-chevron{transform:rotate(180deg)}
.admin-nav-group .admin-nav-link{margin:2px 0}
.admin-sidebar-user-avatar{width:40px;height:40px;border-radius:50%;object-fit:cover}
</style>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-brand">
        <a href="{{ route('admin.dashboard') }}">
            <span class="logo-mark">MC</span>
            <span>MCuong Hotel<small>Bảng quản trị</small></span>
        </a>
    </div>

    <nav class="admin-nav">
        <a href="{{ route('admin.dashboard') }}"
           class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bx bx-grid-alt"></i>
            Tổng quan
        </a>

        @if($canManageFrontDesk)
            <details class="admin-nav-group" {{ $operationsOpen ? 'open' : '' }}>
                <summary>
                    <i class="bx bx-building-house"></i>
                    Vận hành lễ tân
                    <i class="bx bx-chevron-down group-chevron"></i>
                </summary>

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

                <a href="{{ route('admin.chats.index') }}"
                   class="admin-nav-link {{ request()->routeIs('admin.chats.*') ? 'active' : '' }}">
                    <i class="bx bx-message-rounded-dots"></i>
                    Tin nhắn khách hàng
                </a>
            </details>
        @endif

        @if($canManageRooms)
            <details class="admin-nav-group" {{ $roomsOpen ? 'open' : '' }}>
                <summary>
                    <i class="bx bx-bed"></i>
                    Buồng phòng
                    <i class="bx bx-chevron-down group-chevron"></i>
                </summary>

                <a href="{{ route('admin.housekeeping.index') }}"
                   class="admin-nav-link {{ request()->routeIs('admin.housekeeping.*') ? 'active' : '' }}">
                    <i class="bx bx-brush"></i>
                    Phòng cần dọn
                </a>

                <a href="{{ route('admin.floor-inspections.index') }}"
                   class="admin-nav-link {{ request()->routeIs('admin.floor-inspections.*') ? 'active' : '' }}">
                    <i class="bx bx-search-alt"></i>
                    Kiểm tra phòng
                </a>

                @if($isSuperAdmin || $isManager)
                    <a href="{{ route('admin.inspection-approvals.index') }}"
                       class="admin-nav-link {{ request()->routeIs('admin.inspection-approvals.*') ? 'active' : '' }}">
                        <i class="bx bx-check-shield"></i>
                        Duyệt kiểm tra
                    </a>
                @endif
            </details>
        @endif

        @if($canManageCatalog)
            <details class="admin-nav-group" {{ $catalogOpen ? 'open' : '' }}>
                <summary>
                    <i class="bx bx-cog"></i>
                    Cấu hình khách sạn
                    <i class="bx bx-chevron-down group-chevron"></i>
                </summary>

                <a href="{{ route('room-categories.index') }}"
                   class="admin-nav-link {{ request()->routeIs('room-categories.*') ? 'active' : '' }}">
                    <i class="bx bx-category"></i>
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

                <a href="{{ route('amenities.index') }}"
                   class="admin-nav-link {{ request()->routeIs('amenities.*') ? 'active' : '' }}">
                    <i class="bx bx-star"></i>
                    Tiện ích
                </a>

                <a href="{{ route('admin.promotions.index') }}"
                   class="admin-nav-link {{ request()->routeIs('admin.promotions.*') ? 'active' : '' }}">
                    <i class="bx bx-purchase-tag"></i>
                    Khuyến mãi
                </a>

                <a href="{{ route('admin.reviews.index') }}"
                   class="admin-nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                    <i class="bx bx-message-square-check"></i>
                    Đánh giá
                </a>
            </details>
        @endif

        @if($isSuperAdmin || $canManageStaff)
            <details class="admin-nav-group" {{ $staffOpen ? 'open' : '' }}>
                <summary>
                    <i class="bx bx-group"></i>
                    Nhân sự
                    <i class="bx bx-chevron-down group-chevron"></i>
                </summary>

                @if($isSuperAdmin)
                    <a href="{{ route('staffs.index') }}"
                       class="admin-nav-link {{ request()->routeIs('staffs.*') ? 'active' : '' }}">
                        <i class="bx bx-user-plus"></i>
                        Nhân viên
                    </a>
                @endif

                @if($canManageStaff)
                    <a href="{{ route('admin.staff-assignments.index') }}"
                       class="admin-nav-link {{ request()->routeIs('admin.staff-assignments.*') ? 'active' : '' }}">
                        <i class="bx bx-task"></i>
                        Phân công công việc
                    </a>
                @endif
            </details>
        @endif
    </nav>

    <div class="admin-sidebar-user">
        @php
            $avatar = auth()->user()->avatar;
            $avatarUrl = $avatar
                ? (\Illuminate\Support\Str::startsWith($avatar, ['http://', 'https://'])
                    ? $avatar
                    : asset('storage/'.$avatar))
                : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name ?? 'Admin');
        @endphp

        <img src="{{ $avatarUrl }}" class="admin-sidebar-user-avatar" alt="{{ auth()->user()->name }}">
        <div class="admin-sidebar-user-info">
            <span class="admin-sidebar-user-name">{{ auth()->user()->name }}</span>
            <span class="admin-sidebar-user-role">{{ str_replace('_', ' ', $role) }}</span>
        </div>
    </div>

    <div class="admin-sidebar-footer">© 2026 MCuong Hotel</div>
</aside>
