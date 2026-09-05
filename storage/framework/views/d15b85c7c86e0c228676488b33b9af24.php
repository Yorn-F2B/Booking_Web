<?php $__env->startSection('title', 'Danh sách dịch vụ'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $typeLabels = $typeLabels ?? \App\Models\Service::typeLabels();
        $groupLabels = $groupLabels ?? \App\Models\Service::groupLabels();
        $billingRuleLabels = $billingRuleLabels ?? \App\Models\Service::billingRuleLabels();

        $typeBadgeClasses = [
            'service' => 'bg-primary',
            'minibar' => 'bg-warning text-dark',
            'minibar_order' => 'bg-info text-dark',
            'damage_fee' => 'bg-danger',
            'occupancy_fee' => 'bg-info text-dark',
            'policy_violation_fee' => 'bg-dark',
        ];

        $groupBadgeClasses = [
            'general' => 'bg-secondary',
            'food_drink' => 'bg-success',
            'vehicle' => 'bg-dark',
            'laundry' => 'bg-info text-dark',
            'transport' => 'bg-primary',
            'wellness' => 'bg-danger',
            'room_support' => 'bg-warning text-dark',
            'other' => 'bg-secondary',
        ];
    ?>

    <style>
        .service-filter-grid {
            display: grid;
            grid-template-columns: 1.5fr repeat(3, 1fr) auto;
            gap: 10px;
        }

        .service-muted {
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 991px) {
            .service-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="admin-wrapper">
        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Dịch vụ
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Danh sách dịch vụ</h2>
                    <p>Chỉ quản lý dịch vụ khách mua/gọi, minibar và các nhóm dịch vụ vận hành.</p>
                </div>

                <a href="<?php echo e(route('services.create')); ?>" class="btn btn-gold">
                    <i class="bx bx-plus me-1"></i>
                    Thêm dịch vụ
                </a>
            </div>
<div class="settings-section mb-3">
                <form method="GET" action="<?php echo e(route('services.index')); ?>" class="service-filter-grid">
                    <input type="text" name="keyword" class="form-control"
                        value="<?php echo e(request('keyword')); ?>" placeholder="Tìm tên, mô tả, đơn vị...">

                    <select name="type" class="form-select">
                        <option value="">Tất cả loại</option>
                        <?php $__currentLoopData = $typeLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $typeValue => $typeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($typeValue); ?>" <?php echo e(request('type') == $typeValue ? 'selected' : ''); ?>>
                                <?php echo e($typeLabel); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>

                    <select name="service_group" class="form-select">
                        <option value="">Tất cả nhóm</option>
                        <?php $__currentLoopData = $groupLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupValue => $groupLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($groupValue); ?>" <?php echo e(request('service_group') == $groupValue ? 'selected' : ''); ?>>
                                <?php echo e($groupLabel); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>

                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" <?php echo e(request('status') == 'active' ? 'selected' : ''); ?>>Hoạt động</option>
                        <option value="inactive" <?php echo e(request('status') == 'inactive' ? 'selected' : ''); ?>>Tạm ẩn</option>
                    </select>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Lọc</button>
                        <a href="<?php echo e(route('services.index')); ?>" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <div class="settings-section">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tên dịch vụ</th>
                                <th>Loại</th>
                                <th>Nhóm</th>
                                <th>Giá</th>
                                <th>Đơn vị</th>
                                <th>Cách tính</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($service->id); ?></td>

                                    <td>
                                        <div class="fw-semibold"><?php echo e($service->name); ?></div>
                                        <?php if($service->description): ?>
                                            <div class="service-muted">
                                                <?php echo e(\Illuminate\Support\Str::limit($service->description, 80)); ?>

                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge <?php echo e($typeBadgeClasses[$service->type] ?? 'bg-secondary'); ?>">
                                            <?php echo e($service->type_label); ?>

                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge <?php echo e($groupBadgeClasses[$service->service_group] ?? 'bg-secondary'); ?>">
                                            <?php echo e($service->group_label); ?>

                                        </span>
                                    </td>

                                    <td><?php echo e(number_format((float) $service->price, 0, ',', '.')); ?>đ</td>

                                    <td><?php echo e($service->unit); ?></td>

                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?php echo e($service->billing_rule_label); ?>

                                        </span>
                                    </td>

                                    <td>
                                        <?php if($service->status == 'active'): ?>
                                            <span class="badge bg-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Tạm ẩn</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <a href="<?php echo e(route('services.show', $service->id)); ?>"
                                            class="btn btn-sm btn-outline-secondary">
                                            Xem
                                        </a>

                                        <a href="<?php echo e(route('services.edit', $service->id)); ?>"
                                            class="btn btn-sm btn-outline-primary">
                                            Sửa
                                        </a>

                                        <form action="<?php echo e(route('services.destroy', $service->id)); ?>" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa dịch vụ này không?')">
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
                                    <td colspan="9" class="text-center text-muted">
                                        Chưa có dịch vụ nào
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>
                </div>

                <div class="mt-3">
                    <?php echo e($services->links()); ?>

                </div>
            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/services/index.blade.php ENDPATH**/ ?>