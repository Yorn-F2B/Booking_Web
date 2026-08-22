<?php $__env->startSection('title', 'Quản lý phòng'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $statusLabels = [
            'available' => 'Trống',
            'reserved' => 'Đã đặt',
            'occupied' => 'Đang ở',
            'inspection' => 'Chờ kiểm tra',
            'cleaning' => 'Đang dọn',
            'maintenance' => 'Bảo trì',
        ];
        $physicalStatusLabels = array_merge($statusLabels, ['available' => 'Sẵn sàng']);
        $bookingLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đang ở',
            'inspection_requested' => 'Chờ kiểm tra',
            'checked_out' => 'Đã trả phòng',
            'completed' => 'Hoàn tất',
        ];
        $role = auth()->user()->role ?? null;
        $canEditCatalog = in_array($role, ['super_admin', 'manager'], true);
        $activeTab = request('tab', 'calendar');
        $prevStart = $startDate->copy()->subDays($dates->count())->toDateString();
        $nextStart = $startDate->copy()->addDays($dates->count())->toDateString();
    ?>

    <style>
        .room-management {
            padding: 24px;
            color: #0f172a;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow-x: hidden
        }

        .room-management .admin-content,
        .room-management .rm-panel,
        .room-management .rm-panel.active {
            width: 100%;
            max-width: 100%;
            min-width: 0
        }

        .rm-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 18px
        }

        .rm-head h2 {
            font-weight: 850;
            margin: 0 0 4px
        }

        .rm-subtitle {
            color: #64748b;
            margin: 0
        }

        .rm-tabs {
            display: inline-flex;
            padding: 4px;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            background: #f8fafc;
            margin-bottom: 16px
        }

        .rm-tab {
            border: 0;
            background: transparent;
            padding: 9px 15px;
            border-radius: 9px;
            font-weight: 700;
            color: #64748b;
            cursor: pointer
        }

        .rm-tab.active {
            background: #fff;
            color: #0f172a;
            box-shadow: 0 2px 9px rgba(15, 23, 42, .1)
        }

        .rm-panel {
            display: none
        }

        .rm-panel.active {
            display: block
        }

        .rm-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 6px 22px rgba(15, 23, 42, .045)
        }

        .rm-filter {
            padding: 16px;
            margin-bottom: 14px
        }

        .rm-filter-grid {
            display: grid;
            grid-template-columns: 1.15fr 1.15fr .8fr 1fr 1fr auto;
            gap: 10px;
            align-items: end
        }

        .rm-field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 5px
        }

        .rm-field input,
        .rm-field select,
        .rm-modal input,
        .rm-modal select,
        .rm-modal textarea {
            width: 100%;
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            padding: 9px 11px;
            background: #fff;
            color: #0f172a;
            outline: none
        }

        .rm-field input:focus,
        .rm-field select:focus,
        .rm-modal input:focus,
        .rm-modal select:focus,
        .rm-modal textarea:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .12)
        }

        .rm-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid #dbe3ee;
            border-radius: 10px;
            padding: 9px 13px;
            background: #fff;
            color: #334155;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap
        }

        .rm-btn:hover {
            color: #0f172a;
            background: #f8fafc
        }

        .rm-btn-primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff
        }

        .rm-btn-primary:hover {
            background: #1d4ed8;
            color: #fff
        }

        .rm-btn-danger {
            color: #dc2626
        }

        .rm-quickbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px
        }

        .rm-nav {
            display: flex;
            gap: 7px;
            align-items: center
        }

        .rm-range-title {
            font-weight: 800
        }

        .rm-summary {
            display: grid;
            grid-template-columns: repeat(7, minmax(105px, 1fr));
            gap: 9px;
            margin-bottom: 14px
        }

        .rm-stat {
            padding: 12px 13px
        }

        .rm-stat small {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            font-weight: 650
        }

        .rm-stat strong {
            display: block;
            font-size: 24px;
            margin-top: 4px
        }

        .rm-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%
        }

        .s-available {
            --status: #16a34a;
            --soft: #dcfce7;
            --border: #86efac
        }

        .s-reserved {
            --status: #d97706;
            --soft: #fef3c7;
            --border: #fcd34d
        }

        .s-occupied {
            --status: #dc2626;
            --soft: #fee2e2;
            --border: #fca5a5
        }

        .s-inspection {
            --status: #0891b2;
            --soft: #cffafe;
            --border: #67e8f9
        }

        .s-cleaning {
            --status: #2563eb;
            --soft: #dbeafe;
            --border: #93c5fd
        }

        .s-maintenance {
            --status: #64748b;
            --soft: #e2e8f0;
            --border: #cbd5e1
        }

        .rm-dot {
            background: var(--status)
        }

        .rm-legend {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 12px
        }

        .rm-legend span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: 999px;
            padding: 6px 10px;
            color: #475569;
            font-size: 12px
        }

        .rm-timeline-scroll-top {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            height: 18px;
            margin-bottom: 6px;
            overflow-x: auto;
            overflow-y: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            scrollbar-gutter: stable
        }

        .rm-timeline-scroll-top > div {
            height: 1px
        }

        .rm-timeline-wrap {
            display: block;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow-x: auto;
            overflow-y: auto;
            max-height: calc(100vh - 260px);
            border-radius: 18px;
            overscroll-behavior-x: contain;
            scrollbar-gutter: stable
        }

        .rm-timeline {
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1100px;
            width: max-content
        }

        .rm-timeline th,
        .rm-timeline td {
            border-right: 1px solid #e8edf4;
            border-bottom: 1px solid #e8edf4
        }

        .rm-timeline thead th {
            position: sticky;
            top: 0;
            z-index: 4;
            background: #f8fafc;
            padding: 10px 8px;
            text-align: center;
            font-size: 12px;
            min-width: 135px
        }

        .rm-timeline thead th.today {
            background: #eff6ff;
            color: #1d4ed8
        }

        .rm-timeline .room-col {
            position: sticky;
            left: 0;
            z-index: 3;
            background: #fff;
            min-width: 185px;
            width: 185px;
            padding: 12px
        }

        .rm-timeline thead .room-col {
            z-index: 6;
            background: #f8fafc;
            text-align: left
        }

        .room-main {
            font-size: 20px;
            font-weight: 850
        }

        .room-meta {
            font-size: 11px;
            color: #64748b;
            margin-top: 3px
        }

        .room-link {
            font-size: 11px;
            text-decoration: none;
            color: #2563eb
        }

        .rm-cell {
            height: 86px;
            padding: 6px;
            background: #fff;
            vertical-align: top;
            position: relative
        }

        .rm-cell.today {
            background: #f8fbff
        }

        .rm-booking {
            display: block;
            height: 100%;
            border: 1px solid var(--border);
            background: var(--soft);
            border-radius: 10px;
            padding: 7px;
            text-decoration: none;
            color: #334155;
            overflow: hidden
        }

        .rm-booking:hover {
            filter: brightness(.985);
            color: #0f172a
        }

        .rm-booking strong {
            display: block;
            color: var(--status);
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .rm-booking small {
            display: block;
            font-size: 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 2px
        }

        .rm-empty {
            height: 100%;
            border: 1px dashed #dbe3ee;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 11px
        }

        .rm-empty.operational {
            background: var(--soft);
            border-style: solid;
            border-color: var(--border);
            color: var(--status);
            font-weight: 750
        }

        .catalog-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px
        }

        .catalog-table {
            width: 100%;
            border-collapse: collapse
        }

        .catalog-table th,
        .catalog-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #edf2f7;
            text-align: left
        }

        .catalog-table th {
            font-size: 12px;
            color: #64748b;
            background: #f8fafc
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 999px;
            background: var(--soft);
            color: var(--status);
            border: 1px solid var(--border);
            font-size: 11px;
            font-weight: 750
        }

        .action-row {
            display: flex;
            gap: 6px
        }

        .rm-alert {
            padding: 11px 14px;
            border-radius: 11px;
            margin-bottom: 14px
        }

        .rm-alert-success {
            background: #ecfdf5;
            color: #166534;
            border: 1px solid #bbf7d0
        }

        .rm-alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca
        }

        .rm-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .52);
            z-index: 2000;
            padding: 20px;
            align-items: center;
            justify-content: center
        }

        .rm-modal-backdrop.open {
            display: flex
        }

        .rm-modal {
            width: min(620px, 100%);
            max-height: 92vh;
            overflow: auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 28px 80px rgba(15, 23, 42, .3)
        }

        .rm-modal-head,
        .rm-modal-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid #e2e8f0
        }

        .rm-modal-foot {
            border-top: 1px solid #e2e8f0;
            border-bottom: 0;
            justify-content: flex-end
        }

        .rm-modal-body {
            padding: 18px
        }

        .rm-modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px
        }

        .rm-modal-field {
            margin-bottom: 12px
        }

        .rm-modal-field label {
            display: block;
            font-size: 12px;
            font-weight: 750;
            color: #475569;
            margin-bottom: 5px
        }

        .rm-close {
            border: 0;
            background: #f1f5f9;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            font-size: 20px
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #64748b
        }

        @media(max-width:1200px) {
            .rm-filter-grid {
                grid-template-columns: repeat(3, 1fr)
            }

            .rm-summary {
                grid-template-columns: repeat(4, 1fr)
            }
        }

        @media(max-width:768px) {
            .room-management {
                padding: 14px
            }

            .rm-head {
                flex-direction: column
            }

            .rm-filter-grid {
                grid-template-columns: 1fr
            }

            .rm-summary {
                grid-template-columns: repeat(2, 1fr)
            }

            .rm-modal-grid {
                grid-template-columns: 1fr
            }
        }
            .catalog-table tbody tr.catalog-row-updated { background:#ecfdf5; box-shadow:inset 4px 0 0 #22a447; }
    </style>

    <div class="admin-wrapper room-management">
        <main class="admin-content">
            <div class="rm-head">
                <div>
                    <p class="admin-breadcrumb mb-2"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Quản lý phòng</p>
                    <h2>Quản lý phòng</h2>
                </div>
            </div>

<?php if($errors->any()): ?>
                <div class="rm-alert rm-alert-error"><strong>Dữ liệu chưa hợp lệ:</strong> <?php echo e($errors->first()); ?></div>
            <?php endif; ?>

            <div class="rm-tabs">
                <button class="rm-tab <?php echo e($activeTab !== 'catalog' ? 'active' : ''); ?>" data-tab="calendar"><i
                        class="bx bx-calendar"></i> Lịch sử dụng phòng</button>
                <button class="rm-tab <?php echo e($activeTab === 'catalog' ? 'active' : ''); ?>" data-tab="catalog"><i
                        class="bx bx-list-ul"></i> Danh mục phòng</button>
            </div>

            <section id="panel-calendar" class="rm-panel <?php echo e($activeTab !== 'catalog' ? 'active' : ''); ?>">
                <form method="GET" action="<?php echo e(route('admin.rooms.index')); ?>" class="rm-card rm-filter">
                    <input type="hidden" name="tab" value="calendar">
                    <div class="rm-filter-grid">
                        <div class="rm-field"><label>Từ ngày</label><input type="date" name="start_date"
                                value="<?php echo e($startDate->toDateString()); ?>"></div>
                        <div class="rm-field"><label>Đến ngày</label><input type="date" name="end_date"
                                value="<?php echo e($endDate->toDateString()); ?>"></div>
                        <div class="rm-field"><label>Tầng</label><select name="floor_number">
                                <option value="">Tất cả tầng</option><?php $__currentLoopData = $floors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $floor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($floor); ?>"
                                    <?php if((string) request('floor_number') === (string) $floor): echo 'selected'; endif; ?>>Tầng <?php echo e($floor); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select></div>
                        <div class="rm-field"><label>Hạng phòng</label><select name="room_category_id">
                                <option value="">Tất cả hạng</option><?php $__currentLoopData = $roomCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option
                                    value="<?php echo e($category->id); ?>"
                                    <?php if((string) request('room_category_id') === (string) $category->id): echo 'selected'; endif; ?>>
                                <?php echo e($category->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select></div>
                        <div class="rm-field"><label>Số phòng / trạng thái</label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:7px"><input name="room_number"
                                    value="<?php echo e(request('room_number')); ?>" placeholder="VD: 401"><select
                                    name="timeline_status">
                                    <option value="">Tất cả</option><?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option
                                        value="<?php echo e($key); ?>" <?php if(request('timeline_status') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?>

                                    </option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select></div>
                        </div>
                        <button class="rm-btn rm-btn-primary" type="submit"><i class="bx bx-filter-alt"></i> Lọc</button>
                    </div>
                </form>

                <div class="rm-quickbar">
                    <div class="rm-nav">
                        <a class="rm-btn"
                            href="<?php echo e(route('admin.rooms.index', array_merge(request()->except(['start_date', 'end_date']), ['start_date' => $prevStart, 'end_date' => $startDate->copy()->subDay()->toDateString()]))); ?>"><i
                                class="bx bx-chevron-left"></i></a>
                        <a class="rm-btn"
                            href="<?php echo e(route('admin.rooms.index', ['start_date' => $today->toDateString(), 'end_date' => $today->copy()->addDays(6)->toDateString()])); ?>">Hôm
                            nay</a>
                        <a class="rm-btn"
                            href="<?php echo e(route('admin.rooms.index', array_merge(request()->except(['start_date', 'end_date']), ['start_date' => $nextStart, 'end_date' => $endDate->copy()->addDays($dates->count())->toDateString()]))); ?>"><i
                                class="bx bx-chevron-right"></i></a>
                        <span class="rm-range-title"><?php echo e($startDate->format('d/m/Y')); ?> –
                            <?php echo e($endDate->format('d/m/Y')); ?></span>
                    </div>
                    <div class="rm-nav">
                        <?php $__currentLoopData = [1 => '1 ngày', 7 => '7 ngày', 31 => '31 ngày', 90 => '3 tháng', 365 => '1 năm']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $days => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a class="rm-btn"
                                href="<?php echo e(route('admin.rooms.index', array_merge(request()->except(['start_date', 'end_date']), ['start_date' => $startDate->toDateString(), 'end_date' => $startDate->copy()->addDays($days - 1)->toDateString()]))); ?>"><?php echo e($label); ?></a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <a class="rm-btn"
                            href="<?php echo e(route('admin.rooms.index', array_merge(request()->except(['start_date', 'end_date']), ['start_date' => $defaultStart->toDateString(), 'end_date' => $defaultEnd->toDateString()]))); ?>">Toàn bộ</a>
                    </div>
                </div>

                <div class="rm-summary">
                    <div class="rm-card rm-stat"><small><span class="rm-dot" style="background:#0f172a"></span>Tổng
                            phòng</small><strong><?php echo e($summary['total']); ?></strong></div>
                    <?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="rm-card rm-stat s-<?php echo e($key); ?>"><small><span
                                    class="rm-dot"></span><?php echo e($label); ?></small><strong><?php echo e($summary[$key]); ?></strong></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div class="rm-legend"><?php $__currentLoopData = $statusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><span class="s-<?php echo e($key); ?>"><i
                class="rm-dot"></i><?php echo e($label); ?></span><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>

                <div class="rm-timeline-scroll-top" id="roomTimelineTopScroll" aria-label="Cuộn ngang lịch sử dụng phòng"><div></div></div>
                <div class="rm-card rm-timeline-wrap" id="roomTimelineScroll">
                    <?php if($rooms->isEmpty()): ?>
                        <div class="empty-state"><i class="bx bx-bed" style="font-size:42px"></i>
                            <p>Không có phòng phù hợp bộ lọc.</p>
                        </div>
                    <?php else: ?>
                        <table class="rm-timeline">
                            <thead>
                                <tr>
                                    <th class="room-col">Phòng</th><?php $__currentLoopData = $dates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th
                                        class="<?php echo e($date->isSameDay($today) ? 'today' : ''); ?>">
                                        <div><?php echo e($date->translatedFormat('D')); ?></div><strong><?php echo e($date->format('d/m')); ?></strong>
                                    </th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td class="room-col">
                                            <div class="room-main"><?php echo e($room->room_number); ?></div>
                                            <div class="room-meta">Tầng <?php echo e($room->floor_number ?? '?'); ?> ·
                                                <?php echo e($room->category->name ?? 'Chưa xếp hạng'); ?></div><a class="room-link"
                                                href="<?php echo e(route('admin.rooms.show', $room)); ?>">Xem chi tiết</a>
                                        </td>
                                        <?php $__currentLoopData = $dates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php $cell = $timeline[$room->id][$date->toDateString()];
                                            $booking = $cell['booking']; ?>
                                            <td class="rm-cell <?php echo e($date->isSameDay($today) ? 'today' : ''); ?>">
                                                <?php if($booking): ?>
                                                    <a class="rm-booking s-<?php echo e($cell['status']); ?>"
                                                        href="<?php echo e(route('admin.bookings.show', $booking)); ?>"
                                                        title="Xem booking <?php echo e($booking->booking_code); ?>">
                                                        <strong><?php echo e($booking->customer->name ?? 'Khách lẻ'); ?></strong>
                                                        <small><?php echo e($booking->booking_code); ?></small>
                                                        <small><?php echo e($bookingLabels[$booking->status] ?? $booking->status); ?></small>
                                                        <small><?php echo e(optional($booking->check_in_at)->format('H:i d/m') ?? $booking->check_in_date); ?>

                                                            →
                                                            <?php echo e(optional($booking->check_out_at)->format('H:i d/m') ?? $booking->check_out_date); ?></small>
                                                    </a>
                                                <?php elseif($cell['status'] !== 'available'): ?>
                                                    <div class="rm-empty operational s-<?php echo e($cell['status']); ?>">
                                                        <?php echo e($statusLabels[$cell['status']]); ?></div>
                                                <?php else: ?>
                                                    <div class="rm-empty">Trống</div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

            <section id="panel-catalog" class="rm-panel <?php echo e($activeTab === 'catalog' ? 'active' : ''); ?>">
                <div class="catalog-toolbar">
                    <div><strong>Danh mục phòng</strong>
                    </div><?php if($canEditCatalog): ?><button class="rm-btn rm-btn-primary" data-open-modal="roomCreateModal"><i
                    class="bx bx-plus"></i> Thêm phòng</button><?php endif; ?>
                </div>
                <div class="rm-card" style="overflow:auto">
                    <table class="catalog-table">
                        <thead>
                            <tr>
                                <th>Số phòng</th>
                                <th>Tầng</th>
                                <th>Hạng phòng</th>
                                <th>Trạng thái vật lý</th>
                                <th>Ghi chú</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr data-room-id="<?php echo e($room->id); ?>" class="<?php echo e((string) request('updated_room') === (string) $room->id ? 'catalog-row-updated' : ''); ?>">
                                    <td><strong><?php echo e($room->room_number); ?></strong></td>
                                    <td><?php echo e($room->floor_number ?? '—'); ?></td>
                                    <td data-room-category><?php echo e($room->category->name ?? '—'); ?></td>
                                    <td><span class="status-pill s-<?php echo e($room->status); ?>" data-room-status><i
                                                class="rm-dot"></i><span data-room-status-label><?php echo e($physicalStatusLabels[$room->status] ?? $room->status); ?></span></span>
                                    </td>
                                    <td><?php echo e($room->note ?: '—'); ?></td>
                                    <td>
                                        <div class="action-row"><a class="rm-btn"
                                                href="<?php echo e(route('admin.rooms.show', $room)); ?>"><i
                                                    class="bx bx-show"></i></a><?php if($canEditCatalog): ?><button type="button"
                                                            class="rm-btn edit-room-btn"
                                                            data-room="<?php echo e(json_encode(['id' => $room->id, 'room_number' => $room->room_number, 'floor_number' => $room->floor_number, 'room_category_id' => $room->room_category_id, 'status' => $room->status, 'note' => $room->note])); ?>"><i
                                                                class="bx bx-edit"></i></button>
                                                        <form method="POST" action="<?php echo e(route('admin.rooms.destroy', $room)); ?>"
                                                            onsubmit="return confirm('Xóa phòng <?php echo e($room->room_number); ?>?')"><?php echo csrf_field(); ?>
                                                            <?php echo method_field('DELETE'); ?><button class="rm-btn rm-btn-danger"><i
                                                    class="bx bx-trash"></i></button></form><?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr>
                                <td colspan="6" class="empty-state">Chưa có phòng.</td>
                            </tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <?php if($canEditCatalog): ?>
        <div class="rm-modal-backdrop" id="roomCreateModal">
            <div class="rm-modal">
                <form method="POST" action="<?php echo e(route('admin.rooms.store')); ?>"><?php echo csrf_field(); ?><div class="rm-modal-head"><strong>Thêm
                            phòng</strong><button type="button" class="rm-close" data-close-modal>&times;</button></div>
                    <div class="rm-modal-body">
                        <div class="rm-modal-grid">
                            <div class="rm-modal-field"><label>Số phòng *</label><input name="room_number" required></div>
                            <div class="rm-modal-field"><label>Tầng</label><input type="number" min="0" name="floor_number">
                            </div>
                            <div class="rm-modal-field"><label>Hạng phòng *</label><select name="room_category_id" required>
                                    <option value="">Chọn hạng phòng</option><?php $__currentLoopData = $activeCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option
                                    value="<?php echo e($category->id); ?>"><?php echo e($category->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select></div>
                            <div class="rm-modal-field"><label>Trạng thái ban đầu</label><select name="status">
                                    <option value="available">Sẵn sàng</option>
                                    <option value="cleaning">Đang dọn</option>
                                    <option value="inspection">Chờ kiểm tra</option>
                                    <option value="maintenance">Bảo trì</option>
                                </select></div>
                        </div>
                        <div class="rm-modal-field"><label>Ghi chú</label><textarea name="note" rows="3"></textarea></div>
                    </div>
                    <div class="rm-modal-foot"><button type="button" class="rm-btn" data-close-modal>Hủy</button><button
                            class="rm-btn rm-btn-primary">Lưu phòng</button></div>
                </form>
            </div>
        </div>
        <div class="rm-modal-backdrop" id="roomEditModal">
            <div class="rm-modal">
                <form method="POST" id="roomEditForm"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?><div class="rm-modal-head"><strong>Chỉnh sửa
                            phòng</strong><button type="button" class="rm-close" data-close-modal>&times;</button></div>
                    <div class="rm-modal-body">
                        <div class="rm-modal-grid">
                            <div class="rm-modal-field"><label>Số phòng *</label><input id="edit_room_number" name="room_number"
                                    required></div>
                            <div class="rm-modal-field"><label>Tầng</label><input id="edit_floor_number" type="number" min="0"
                                    name="floor_number"></div>
                            <div class="rm-modal-field"><label>Hạng phòng *</label><select id="edit_room_category_id"
                                    name="room_category_id" required><?php $__currentLoopData = $activeCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option
                                    value="<?php echo e($category->id); ?>"><?php echo e($category->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                            <div class="rm-modal-field"><label>Trạng thái vật lý</label><select id="edit_status" name="status">
                                    <option value="available">Sẵn sàng</option>
                                    <option value="reserved">Đã giữ (hệ thống)</option>
                                    <option value="occupied">Đang ở (hệ thống)</option>
                                    <option value="cleaning">Đang dọn</option>
                                    <option value="inspection">Chờ kiểm tra</option>
                                    <option value="maintenance">Bảo trì</option>
                                </select><small id="edit_status_hint" class="text-muted d-block mt-1"></small></div>
                        </div>
                        <div class="rm-modal-field"><label>Ghi chú</label><textarea id="edit_note" name="note"
                                rows="3"></textarea></div>
                    </div>
                    <div class="rm-modal-foot"><button type="button" class="rm-btn" data-close-modal>Hủy</button><button
                            class="rm-btn rm-btn-primary">Cập nhật</button></div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('.rm-tab');
            tabs.forEach(tab => tab.addEventListener('click', () => {
                tabs.forEach(x => x.classList.remove('active'));
                document.querySelectorAll('.rm-panel').forEach(x => x.classList.remove('active'));
                tab.classList.add('active');
                document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
                const url = new URL(window.location.href); url.searchParams.set('tab', tab.dataset.tab); history.replaceState({}, '', url);
            }));
            const openModal = id => document.getElementById(id)?.classList.add('open');
            const closeModal = modal => modal?.classList.remove('open');
            document.querySelectorAll('[data-open-modal]').forEach(btn => btn.addEventListener('click', () => openModal(btn.dataset.openModal)));
            document.querySelectorAll('[data-close-modal]').forEach(btn => btn.addEventListener('click', () => closeModal(btn.closest('.rm-modal-backdrop'))));
            document.querySelectorAll('.rm-modal-backdrop').forEach(modal => modal.addEventListener('click', e => { if (e.target === modal) closeModal(modal); }));
            document.querySelectorAll('.edit-room-btn').forEach(btn => btn.addEventListener('click', () => {
                const room = JSON.parse(btn.dataset.room);
                document.getElementById('roomEditForm').action = '<?php echo e(url("admin/rooms")); ?>/' + room.id;
                document.getElementById('edit_room_number').value = room.room_number ?? '';
                document.getElementById('edit_floor_number').value = room.floor_number ?? '';
                document.getElementById('edit_room_category_id').value = room.room_category_id ?? '';
                const statusSelect = document.getElementById('edit_status');
                const statusHint = document.getElementById('edit_status_hint');
                const systemManagedStatus = ['reserved', 'occupied'].includes(room.status);
                statusSelect.value = room.status || 'available';
                Array.from(statusSelect.options).forEach(option => {
                    option.disabled = systemManagedStatus && option.value !== room.status;
                });
                if (statusHint) {
                    statusHint.textContent = systemManagedStatus
                        ? 'Phòng đang thuộc booking hoạt động; trạng thái này do nghiệp vụ booking quản lý.'
                        : 'Đổi trạng thái ở đây sẽ áp dụng ngay và không dùng lại thời hạn cũ.';
                }
                document.getElementById('edit_note').value = room.note ?? '';
                openModal('roomEditModal');
            }));

            const timelineScroll = document.getElementById('roomTimelineScroll');
            const timelineTopScroll = document.getElementById('roomTimelineTopScroll');
            const timelineTable = timelineScroll?.querySelector('.rm-timeline');

            if (timelineScroll && timelineTopScroll && timelineTable) {
                const topSpacer = timelineTopScroll.firstElementChild;
                let syncingFromTop = false;
                let syncingFromTable = false;

                const updateTimelineScrollWidth = () => {
                    if (topSpacer) {
                        topSpacer.style.width = `${timelineTable.scrollWidth}px`;
                    }
                    timelineTopScroll.style.display = timelineTable.scrollWidth > timelineScroll.clientWidth ? 'block' : 'none';
                };

                timelineTopScroll.addEventListener('scroll', () => {
                    if (syncingFromTable) return;
                    syncingFromTop = true;
                    timelineScroll.scrollLeft = timelineTopScroll.scrollLeft;
                    requestAnimationFrame(() => { syncingFromTop = false; });
                });

                timelineScroll.addEventListener('scroll', () => {
                    if (syncingFromTop) return;
                    syncingFromTable = true;
                    timelineTopScroll.scrollLeft = timelineScroll.scrollLeft;
                    requestAnimationFrame(() => { syncingFromTable = false; });
                });

                updateTimelineScrollWidth();
                window.addEventListener('resize', updateTimelineScrollWidth);
            }

            const requestedEditRoomId = new URL(window.location.href).searchParams.get('edit_room');
            if (requestedEditRoomId) {
                const editButton = Array.from(document.querySelectorAll('.edit-room-btn')).find(btn => {
                    try {
                        return String(JSON.parse(btn.dataset.room).id) === String(requestedEditRoomId);
                    } catch (error) {
                        return false;
                    }
                });
                if (editButton) {
                    const catalogTab = document.querySelector('.rm-tab[data-tab="catalog"]');
                    catalogTab?.click();
                    editButton.click();
                }
            }
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/rooms/index.blade.php ENDPATH**/ ?>