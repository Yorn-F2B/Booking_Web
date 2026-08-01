<?php $__env->startSection('title', 'Mã ưu đãi'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $statusLabels = [
            'active' => 'Hoạt động',
            'inactive' => 'Tạm ẩn',
        ];

        $statusClasses = [
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
        ];
    ?>

    <style>
        .promotion-admin-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            padding: 18px;
        }

        .promotion-filter-grid {
            display: grid;
            grid-template-columns: 1.5fr repeat(4, 1fr) auto;
            gap: 10px;
        }

        .promotion-code-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            background: #111827;
            color: #fff;
            font-weight: 800;
            letter-spacing: 0.04em;
            font-size: 12px;
        }

        .promotion-condition-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .promotion-condition-pill {
            display: inline-flex;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            padding: 4px 8px;
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
        }

        .promotion-muted {
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 991px) {
            .promotion-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="admin-wrapper">
        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Mã ưu đãi
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Mã ưu đãi</h2>
                    <p>Quản lý mã thường, mã sự kiện, mã hỗ trợ và mã điều kiện</p>
                </div>

                <a href="<?php echo e(route('admin.promotions.create')); ?>" class="btn btn-gold">
                    <i class="bx bx-plus me-1"></i>
                    Thêm mã
                </a>
            </div>

            <?php if(session('success')): ?>
                <div class="alert alert-success">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            <div class="promotion-admin-card mb-3">
                <form action="<?php echo e(route('admin.promotions.index')); ?>" method="GET">
                    <div class="promotion-filter-grid">
                        <input type="text" name="keyword" class="form-control"
                            value="<?php echo e(request('keyword')); ?>"
                            placeholder="Tìm mã, tên mã hoặc mô tả">

                        <select name="promotion_type" class="form-select">
                            <option value="">Tất cả loại mã</option>
                            <?php $__currentLoopData = $promotionTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $typeValue => $typeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($typeValue); ?>" <?php if(request('promotion_type') == $typeValue): echo 'selected'; endif; ?>>
                                    <?php echo e($typeLabel); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>

                        <select name="status" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="active" <?php if(request('status') == 'active'): echo 'selected'; endif; ?>>Hoạt động</option>
                            <option value="inactive" <?php if(request('status') == 'inactive'): echo 'selected'; endif; ?>>Tạm ẩn</option>
                        </select>

                        <select name="visibility" class="form-select">
                            <option value="">Tất cả quyền dùng</option>
                            <option value="user" <?php if(request('visibility') == 'user'): echo 'selected'; endif; ?>>User tự dùng</option>
                            <option value="admin" <?php if(request('visibility') == 'admin'): echo 'selected'; endif; ?>>Admin áp dụng</option>
                            <option value="support" <?php if(request('visibility') == 'support'): echo 'selected'; endif; ?>>Mã hỗ trợ</option>
                            <option value="hidden_user" <?php if(request('visibility') == 'hidden_user'): echo 'selected'; endif; ?>>Không hiện user</option>
                        </select>

                        <select name="valid_state" class="form-select">
                            <option value="">Tất cả hiệu lực</option>
                            <option value="active_now" <?php if(request('valid_state') == 'active_now'): echo 'selected'; endif; ?>>Đang hiệu lực</option>
                            <option value="upcoming" <?php if(request('valid_state') == 'upcoming'): echo 'selected'; endif; ?>>Sắp diễn ra</option>
                            <option value="expired" <?php if(request('valid_state') == 'expired'): echo 'selected'; endif; ?>>Đã hết hạn</option>
                        </select>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary text-nowrap">
                                Lọc
                            </button>
                            <a href="<?php echo e(route('admin.promotions.index')); ?>" class="btn btn-outline-secondary text-nowrap">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="promotion-admin-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã</th>
                                <th>Loại / giảm</th>
                                <th>Điều kiện chính</th>
                                <th>Hiệu lực</th>
                                <th>Lượt dùng</th>
                                <th>Quyền dùng</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $promotions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promotion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $conditionPills = [];

                                    if ((float) $promotion->min_booking_amount > 0) {
                                        $conditionPills[] = 'Đơn từ ' . number_format((float) $promotion->min_booking_amount, 0, ',', '.') . 'đ';
                                    }

                                    if ((int) $promotion->min_nights > 0) {
                                        $conditionPills[] = 'Từ ' . (int) $promotion->min_nights . ' đêm';
                                    }

                                    if ((int) $promotion->min_rooms > 0) {
                                        $conditionPills[] = 'Từ ' . (int) $promotion->min_rooms . ' phòng';
                                    }

                                    if ((int) $promotion->min_completed_bookings > 0) {
                                        $conditionPills[] = 'Khách đã hoàn thành ' . (int) $promotion->min_completed_bookings . ' đơn';
                                    }

                                    if ((float) $promotion->min_total_spent > 0) {
                                        $conditionPills[] = 'Đã chi tiêu từ ' . number_format((float) $promotion->min_total_spent, 0, ',', '.') . 'đ';
                                    }

                                    $usageText = $promotion->usage_limit
                                        ? ((int) $promotion->used_count . '/' . (int) $promotion->usage_limit)
                                        : ((int) $promotion->used_count . ' lượt');

                                    $validText = 'Không giới hạn';
                                    if ($promotion->valid_from || $promotion->valid_to) {
                                        $validText = ($promotion->valid_from ? $promotion->valid_from->format('d/m/Y H:i') : '---')
                                            . ' → '
                                            . ($promotion->valid_to ? $promotion->valid_to->format('d/m/Y H:i') : '---');
                                    }
                                ?>

                                <tr>
                                    <td>
                                        <div class="promotion-code-badge"><?php echo e($promotion->code); ?></div>
                                        <div class="promotion-muted mt-1"><?php echo e($promotion->name); ?></div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold"><?php echo e($promotion->type_label); ?></div>
                                        <div class="promotion-muted">Giảm tiền: <?php echo e($promotion->discount_label); ?></div>
                                        <?php if($promotion->serviceOffers->count() > 0): ?>
                                            <div class="promotion-muted mt-1">
                                                Dịch vụ:
                                                <?php echo e($promotion->serviceOffers->map(fn ($offer) => $offer->offer_label)->implode(' · ')); ?>

                                            </div>
                                        <?php endif; ?>
                                        <?php if($promotion->roomUpgradeOffers->count() > 0): ?>
                                            <div class="promotion-muted mt-1 text-success">
                                                Nâng hạng:
                                                <?php echo e($promotion->roomUpgradeOffers->map(fn ($offer) => $offer->kind_label . ' - ' . $offer->cover_label)->implode(' · ')); ?>

                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td style="min-width: 240px;">
                                        <?php if(count($conditionPills) > 0): ?>
                                            <div class="promotion-condition-list">
                                                <?php $__currentLoopData = $conditionPills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conditionPill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <span class="promotion-condition-pill"><?php echo e($conditionPill); ?></span>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="promotion-muted">Không có điều kiện đặc biệt</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="small"><?php echo e($validText); ?></div>
                                        <?php if($promotion->stay_from || $promotion->stay_to): ?>
                                            <div class="promotion-muted">
                                                Lưu trú:
                                                <?php echo e($promotion->stay_from ? $promotion->stay_from->format('d/m/Y') : '---'); ?>

                                                →
                                                <?php echo e($promotion->stay_to ? $promotion->stay_to->format('d/m/Y') : '---'); ?>

                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="fw-semibold"><?php echo e($usageText); ?></div>
                                        <div class="promotion-muted">
                                            Tổng ưu đãi:
                                            <?php echo e(number_format((float) ($promotion->total_discount_used ?? 0), 0, ',', '.')); ?>đ
                                        </div>
                                        <?php if((float) ($promotion->total_room_upgrade_discount_used ?? 0) > 0): ?>
                                            <div class="promotion-muted">
                                                Nâng hạng: <?php echo e(number_format((float) $promotion->total_room_upgrade_discount_used, 0, ',', '.')); ?>đ
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if($promotion->user_can_apply && $promotion->is_public): ?>
                                            <span class="badge bg-primary">User</span>
                                        <?php endif; ?>

                                        <?php if($promotion->admin_can_apply): ?>
                                            <span class="badge bg-dark">Admin</span>
                                        <?php endif; ?>

                                        <?php if($promotion->requires_note): ?>
                                            <span class="badge bg-warning text-dark">Cần lý do</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge <?php echo e($statusClasses[$promotion->status] ?? 'bg-secondary'); ?>">
                                            <?php echo e($statusLabels[$promotion->status] ?? $promotion->status); ?>

                                        </span>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <a href="<?php echo e(route('admin.promotions.show', $promotion->id)); ?>"
                                            class="btn btn-sm btn-outline-secondary">
                                            Xem
                                        </a>

                                        <a href="<?php echo e(route('admin.promotions.edit', $promotion->id)); ?>"
                                            class="btn btn-sm btn-outline-primary">
                                            Sửa
                                        </a>

                                        <form action="<?php echo e(route('admin.promotions.toggle-status', $promotion->id)); ?>"
                                            method="POST" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>

                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <?php echo e($promotion->status == 'active' ? 'Ẩn' : 'Bật'); ?>

                                            </button>
                                        </form>

                                        <form action="<?php echo e(route('admin.promotions.destroy', $promotion->id)); ?>"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Xác nhận xóa hoặc tạm ẩn mã này?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Xóa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        Chưa có mã ưu đãi nào
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <?php echo e($promotions->links()); ?>

                </div>
            </div>
        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\promotions\index.blade.php ENDPATH**/ ?>