<?php $__env->startSection('title', 'Danh sách tiện ích'); ?>

<?php $__env->startSection('content'); ?>

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Tiện ích
        </p>

        <div class="admin-page-head">

            <div>
                <h2>Danh sách tiện ích</h2>
                <p>Quản lý các tiện ích hiển thị cho hạng phòng</p>
            </div>

            <a href="<?php echo e(route('amenities.create')); ?>" class="btn btn-gold">
                <i class="bx bx-plus me-1"></i>
                Thêm tiện ích
            </a>

        </div>
<div class="settings-section">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Tên tiện ích</th>
                            <th>Ngày tạo</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php $__empty_1 = true; $__currentLoopData = $amenities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amenity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                            <tr>

                                <td><?php echo e($amenity->id); ?></td>

                                <td>
                                    <?php if($amenity->icon): ?>

                                        <i class="<?php echo e($amenity->icon); ?>" style="font-size: 24px;"></i>

                                    <?php else: ?>

                                        <span class="text-muted">Chưa có icon</span>

                                    <?php endif; ?>
                                </td>

                                <td><?php echo e($amenity->name); ?></td>

                                <td>
                                    <?php echo e($amenity->created_at?->format('d/m/Y H:i')); ?>

                                </td>

                                <td class="text-end text-nowrap">

                                    <a href="<?php echo e(route('amenities.show', $amenity->id)); ?>"
                                        class="btn btn-sm btn-outline-secondary">
                                        Xem
                                    </a>

                                    <a href="<?php echo e(route('amenities.edit', $amenity->id)); ?>"
                                        class="btn btn-sm btn-outline-primary">
                                        Sửa
                                    </a>

                                    <form action="<?php echo e(route('amenities.destroy', $amenity->id)); ?>"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa tiện ích này không?')">

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
                                <td colspan="5" class="text-center text-muted">
                                    Chưa có tiện ích nào
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="mt-3">
                <?php echo e($amenities->links()); ?>

            </div>

        </div>

    </main>

    <footer class="admin-footer">
        <span>MCuong Hotel Admin</span>
    </footer>

</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/amenities/index.blade.php ENDPATH**/ ?>