<button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Mở menu">
    <i class="bx bx-menu"></i>
</button>

<?php
    $role = auth()->user()->role ?? null;
    $isSuperAdmin = $role === 'super_admin';
    $isManager = $role === 'manager';
    $isReceptionistLead = $role === 'receptionist_lead';
    $isReceptionist = $role === 'receptionist';
    $isHousekeepingSupervisor = $role === 'housekeeping_supervisor';
    $isHousekeeping = $role === 'housekeeping';

    /*
    |--------------------------------------------------------------------------
    | Phân quyền hiển thị menu
    |--------------------------------------------------------------------------
    | - Super Admin: toàn bộ menu.
    | - Manager: vận hành, buồng phòng, cấu hình, phân công và khách hàng.
    | - Receptionist Lead: vận hành lễ tân + phân công lễ tân.
    | - Receptionist: chỉ vận hành lễ tân.
    | - Housekeeping Supervisor: buồng phòng + phân công buồng phòng.
    | - Housekeeping: chỉ buồng phòng.
    |
    | Đây chỉ là lớp hiển thị menu. Route vẫn cần middleware role tương ứng.
    */

    $canUseFrontDesk = in_array($role, [
        'super_admin',
        'manager',
        'receptionist_lead',
        'receptionist',
    ], true);

    $canUseHousekeeping = in_array($role, [
        'super_admin',
        'manager',
        'housekeeping_supervisor',
        'housekeeping',
    ], true);

    $canManageCatalog = in_array($role, [
        'super_admin',
        'manager',
    ], true);

    $canViewCustomers = in_array($role, [
        'super_admin',
        'manager',
    ], true);

    $canViewStaffAssignments = in_array($role, [
        'super_admin',
        'manager',
        'receptionist_lead',
        'housekeeping_supervisor',
    ], true);

    $canManageStaffAccounts = $isSuperAdmin;
    $canManageReceptionistAssignments = in_array($role, ['super_admin', 'manager', 'receptionist_lead'], true);
    $canManageHousekeepingAssignments = in_array($role, ['super_admin', 'manager', 'housekeeping_supervisor'], true);
    $canApproveInspections = $isSuperAdmin || $isManager;
    $canManageRoomIssues = $isSuperAdmin || $isManager;
    $canApproveLateArrivals = $isSuperAdmin || $isManager;
    $pendingBookingCount = $canUseFrontDesk
        ? \App\Models\Booking::where('status', 'pending')->count()
        : 0;
    $pendingRoomIssueCount = $canManageRoomIssues ? \App\Models\RoomIssueRequest::where('status', 'pending')->count() : 0;
    $pendingInspectionApprovalCount = $canApproveInspections
        ? \App\Models\RoomInspection::where('status', 'reported')
            ->where('workflow_stage', 'admin_approval')
            ->count()
        : 0;
    $pendingFloorInspectionCount = $canUseHousekeeping
        ? \App\Models\RoomInspection::where('status', 'pending')->count()
        : 0;
    $pendingRoomRepairCount = $canUseHousekeeping ? \App\Models\RoomIssueRequest::whereIn('status', ['approved', 'repair_only'])->where('repair_status', 'waiting')->count() : 0;
    $pendingLateArrivalCount = $canApproveLateArrivals
        ? \App\Models\CustomerRequest::where('type', 'late_arrival')->where('status', 'pending')->count()
        : 0;

    $operationsOpen = request()->routeIs([
        'admin.rooms.*',
        'admin.room-availability.*',
        'admin.bookings.*',
        'admin.staying-guests.*',
        'admin.chats.*',
    ]);

    $approvalsOpen = request()->routeIs([
        'admin.room-issues.*',
        'admin.inspection-approvals.*',
        'admin.customer-requests.*',
    ]);

    $roomsOpen = request()->routeIs([
        'admin.housekeeping.*',
        'admin.floor-inspections.*',
        'admin.room-repairs.*',
    ]);

    $catalogOpen = request()->routeIs([
        'room-categories.*',
        'admin.rooms.*',
        'services.*',
        'admin.promotions.*',
        'amenities.*',
        'admin.reviews.*',
        'admin.banned-words.*',
    ]);

    $staffOpen = request()->routeIs([
        'staffs.*',
        'admin.staff-assignments.*',
    ]);

    $managementOpen = request()->routeIs([
        'admin.customers.*',
    ]);

    $roleLabels = [
        'super_admin' => 'Quản trị viên cao nhất',
        'manager' => 'Quản lý',
        'receptionist_lead' => 'Trưởng lễ tân',
        'receptionist' => 'Lễ tân',
        'housekeeping_supervisor' => 'Trưởng buồng phòng',
        'housekeeping' => 'Nhân viên buồng phòng',
    ];
?>

<style>
    .admin-nav-group {
        margin: 5px 9px
    }

    .admin-nav-group>summary {
        list-style: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 11px;
        border-radius: 10px;
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em
    }

    .admin-nav-group>summary::-webkit-details-marker {
        display: none
    }

    .admin-nav-group>summary:hover {
        background: rgba(148, 163, 184, .1)
    }

    .admin-nav-group>summary i:first-child {
        font-size: 18px
    }

    .admin-nav-group>summary .group-chevron {
        margin-left: auto;
        transition: .2s
    }

    .admin-nav-group[open]>summary .group-chevron {
        transform: rotate(180deg)
    }

    .admin-nav-group .admin-nav-link {
        margin: 2px 0
    }

    .admin-sidebar-user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover
    }

    .admin-menu-count {
        min-width: 22px;
        height: 22px;
        margin-left: auto;
        padding: 0 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #e53945;
        color: #fff;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
        box-shadow: 0 0 0 3px rgba(229, 57, 69, .1)
    }

    .admin-menu-count.is-warning {
        background: #d9aa25;
        color: #0b1d38;
        box-shadow: 0 0 0 3px rgba(217, 170, 37, .12)
    }

    .admin-menu-count[hidden] {
        display: none !important
    }
</style>

<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-brand">
        <a href="<?php echo e(route('home')); ?>" target="_blank">
            <span class="logo-mark">MC</span>
            <span>MCuong Hotel<small>Bảng quản trị</small></span>
        </a>
    </div>

    <nav class="admin-nav">
        <?php if($isSuperAdmin): ?>
            <a href="<?php echo e(route('admin.dashboard')); ?>"
                class="admin-nav-link <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>">
                <i class="bx bx-grid-alt"></i>
                Tổng quan
            </a>
        <?php endif; ?>

        <?php if($canUseFrontDesk): ?>
            <details class="admin-nav-group" open>
                <summary>
                    <i class="bx bx-building-house"></i>
                    Vận hành lễ tân
                    <i class="bx bx-chevron-down group-chevron"></i>
                </summary>

                <a href="<?php echo e(route('admin.room-availability.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.room-availability.*') ? 'active' : ''); ?>">
                    <i class="bx bx-search"></i>
                    Tra cứu phòng trống
                </a>

                <a href="<?php echo e(route('admin.bookings.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.bookings.*') ? 'active' : ''); ?>">
                    <i class="bx bx-calendar-check"></i>
                    Đặt phòng
                    <span class="admin-menu-count is-warning"
                        data-realtime-menu-count="pending-bookings"
                        <?php if($pendingBookingCount < 1): ?> hidden <?php endif; ?>><?php echo e($pendingBookingCount); ?></span>
                </a>


                <a href="<?php echo e(route('admin.staying-guests.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.staying-guests.*') ? 'active' : ''); ?>">
                    <i class="bx bx-id-card"></i>
                    Khách đang lưu trú
                </a>

                <a href="<?php echo e(route('admin.chats.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.chats.*') ? 'active' : ''); ?>">
                    <i class="bx bx-message-rounded-dots"></i>
                    Tin nhắn khách hàng
                </a>
            </details>
        <?php endif; ?>

        <?php if($canManageRoomIssues || $canApproveInspections || $canApproveLateArrivals): ?>
            <details class="admin-nav-group" open>
                <summary>
                    <i class="bx bx-check-shield"></i>
                    Quản lý duyệt
                    <i class="bx bx-chevron-down group-chevron"></i>
                </summary>

                <?php if($canManageRoomIssues): ?>
                    <a href="<?php echo e(route('admin.room-issues.index')); ?>"
                        class="admin-nav-link <?php echo e(request()->routeIs('admin.room-issues.*') ? 'active' : ''); ?>">
                        <i class="bx bx-error-circle"></i>
                        Duyệt sự cố phòng
                        <span class="admin-menu-count"
                            data-realtime-menu-count="pending-room-issues"
                            <?php if($pendingRoomIssueCount < 1): ?> hidden <?php endif; ?>><?php echo e($pendingRoomIssueCount); ?></span>
                    </a>
                <?php endif; ?>

                <?php if($canApproveInspections): ?>
                    <a href="<?php echo e(route('admin.inspection-approvals.index')); ?>"
                        class="admin-nav-link <?php echo e(request()->routeIs('admin.inspection-approvals.*') ? 'active' : ''); ?>">
                        <i class="bx bx-check-circle"></i>
                        Duyệt kiểm tra phòng
                        <span class="admin-menu-count"
                            data-realtime-menu-count="pending-inspection-approvals"
                            <?php if($pendingInspectionApprovalCount < 1): ?> hidden <?php endif; ?>><?php echo e($pendingInspectionApprovalCount); ?></span>
                    </a>
                <?php endif; ?>

                <?php if($canApproveLateArrivals): ?>
                    <a href="<?php echo e(route('admin.customer-requests.index')); ?>"
                        class="admin-nav-link <?php echo e(request()->routeIs('admin.customer-requests.*') ? 'active' : ''); ?>">
                        <i class="bx bx-time-five"></i>
                        Duyệt yêu cầu đến muộn
                        <span class="admin-menu-count is-warning"
                            data-realtime-menu-count="pending-late-arrivals"
                            <?php if($pendingLateArrivalCount < 1): ?> hidden <?php endif; ?>><?php echo e($pendingLateArrivalCount); ?></span>
                    </a>
                <?php endif; ?>
            </details>
        <?php endif; ?>

        <?php if($canUseHousekeeping): ?>
            <details class="admin-nav-group" open>
                <summary>
                    <i class="bx bx-bed"></i>
                    Buồng phòng
                    <i class="bx bx-chevron-down group-chevron"></i>
                </summary>

                <a href="<?php echo e(route('admin.housekeeping.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.housekeeping.*') ? 'active' : ''); ?>">
                    <i class="bx bx-brush"></i>
                    Phòng cần dọn
                </a>

                <a href="<?php echo e(route('admin.room-repairs.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.room-repairs.*') ? 'active' : ''); ?>">
                    <i class="bx bx-wrench"></i>
                    Phòng cần sửa
                    <span class="admin-menu-count"
                        data-realtime-menu-count="pending-room-repairs"
                        <?php if($pendingRoomRepairCount < 1): ?> hidden <?php endif; ?>><?php echo e($pendingRoomRepairCount); ?></span>
                </a>

                <a href="<?php echo e(route('admin.floor-inspections.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.floor-inspections.*') ? 'active' : ''); ?>">
                    <i class="bx bx-search-alt"></i>
                    Kiểm tra phòng
                    <span class="admin-menu-count is-warning"
                        data-realtime-menu-count="pending-floor-inspections"
                        <?php if($pendingFloorInspectionCount < 1): ?> hidden <?php endif; ?>><?php echo e($pendingFloorInspectionCount); ?></span>
                </a>

            </details>
        <?php endif; ?>

        <?php if($canManageCatalog): ?>
            <details class="admin-nav-group" open>
                <summary>
                    <i class="bx bx-cog"></i>
                    Cấu hình khách sạn
                    <i class="bx bx-chevron-down group-chevron"></i>
                </summary>

                <a href="<?php echo e(route('room-categories.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('room-categories.*') ? 'active' : ''); ?>">
                    <i class="bx bx-category"></i>
                    Hạng phòng
                </a>

                <a href="<?php echo e(route('admin.rooms.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.rooms.*') ? 'active' : ''); ?>">
                    <i class="bx bx-calendar-event"></i>
                    Quản lý phòng
                </a>

                <a href="<?php echo e(route('services.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('services.*') ? 'active' : ''); ?>">
                    <i class="bx bx-food-menu"></i>
                    Dịch vụ
                </a>

                <a href="<?php echo e(route('amenities.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('amenities.*') ? 'active' : ''); ?>">
                    <i class="bx bx-star"></i>
                    Tiện ích
                </a>

                <a href="<?php echo e(route('admin.promotions.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.promotions.*') ? 'active' : ''); ?>">
                    <i class="bx bx-purchase-tag"></i>
                    Khuyến mãi
                </a>

                <a href="<?php echo e(route('admin.banned-words.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.banned-words.*') ? 'active' : ''); ?>">
                    <i class="bx bx-block"></i>
                    Từ cấm đánh giá
                </a>

                <a href="<?php echo e(route('admin.reviews.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.reviews.*') ? 'active' : ''); ?>">
                    <i class="bx bx-message-square-check"></i>
                    Đánh giá
                </a>
            </details>
        <?php endif; ?>

        <?php if($canManageStaffAccounts || $canViewStaffAssignments): ?>
            <details class="admin-nav-group" open>
                <summary>
                    <i class="bx bx-group"></i>
                    Nhân sự
                    <i class="bx bx-chevron-down group-chevron"></i>
                </summary>

                <?php if($canManageStaffAccounts): ?>
                    <a href="<?php echo e(route('staffs.index')); ?>"
                        class="admin-nav-link <?php echo e(request()->routeIs('staffs.*') ? 'active' : ''); ?>">
                        <i class="bx bx-user-plus"></i>
                        Nhân viên
                    </a>
                <?php endif; ?>

                <?php if($canViewStaffAssignments): ?>
                    <a href="<?php echo e(route('admin.staff-assignments.index')); ?>"
                        class="admin-nav-link <?php echo e(request()->routeIs('admin.staff-assignments.*') ? 'active' : ''); ?>">
                        <i class="bx bx-task"></i>
                        Phân công công việc
                    </a>
                <?php endif; ?>
            </details>
        <?php endif; ?>

        <?php if($canViewCustomers): ?>
            <details class="admin-nav-group" open>
                <summary>
                    <i class="bx bx-line-chart"></i>
                    Quản lý khách hàng
                    <i class="bx bx-chevron-down group-chevron"></i>
                </summary>

                <a href="<?php echo e(route('admin.customers.index')); ?>"
                    class="admin-nav-link <?php echo e(request()->routeIs('admin.customers.*') ? 'active' : ''); ?>">
                    <i class="bx bx-user"></i>
                    Khách hàng
                </a>

            </details>
        <?php endif; ?>
    </nav>

    <div class="admin-sidebar-user">
        <?php
            $avatar = auth()->user()->avatar;
            $avatarUrl = $avatar
                ? (\Illuminate\Support\Str::startsWith($avatar, ['http://', 'https://'])
                    ? $avatar
                    : asset('storage/' . $avatar))
                : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name ?? 'Admin');
        ?>

        <img src="<?php echo e($avatarUrl); ?>" class="admin-sidebar-user-avatar" alt="<?php echo e(auth()->user()->name); ?>">
        <div class="admin-sidebar-user-info">
            <span class="admin-sidebar-user-name"><?php echo e(auth()->user()->name); ?></span>
            <span class="admin-sidebar-user-role"><?php echo e($roleLabels[$role] ?? $role); ?></span>
        </div>
    </div>

    <div class="admin-sidebar-footer">© 2026 MCuong Hotel</div>
</aside>

<?php
    $topbarAvatar = auth()->user()->avatar;
    $topbarAvatarUrl = $topbarAvatar
        ? (\Illuminate\Support\Str::startsWith($topbarAvatar, ['http://', 'https://'])
            ? $topbarAvatar
            : asset('storage/' . $topbarAvatar))
        : 'https://ui-avatars.com/api/?background=E7F7F3&color=087A68&name=' . urlencode(auth()->user()->name ?? 'Admin');
?>

<header class="admin-topbar">
    <div class="admin-topbar-context">
        <span>MCuong Hotel</span>
        <strong>Quản trị khách sạn</strong>
    </div>
    <div class="admin-topbar-account">
        <img src="<?php echo e($topbarAvatarUrl); ?>" class="admin-topbar-avatar" alt="<?php echo e(auth()->user()->name); ?>">
        <div class="admin-topbar-user">
            <strong><?php echo e(auth()->user()->name); ?></strong>
            <span><?php echo e($roleLabels[$role] ?? $role); ?></span>
        </div>
        <form method="POST" action="<?php echo e(route('logout')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="admin-logout" title="Đăng xuất">
                <i class="bx bx-log-out"></i><span>Đăng xuất</span>
            </button>
        </form>
    </div>
</header>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\layouts\partials\header.blade.php ENDPATH**/ ?>