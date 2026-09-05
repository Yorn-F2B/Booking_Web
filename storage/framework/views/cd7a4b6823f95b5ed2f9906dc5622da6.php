<?php $__env->startSection('title', 'Danh sách loại phòng'); ?>

<?php $__env->startSection('content'); ?>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Loại phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Danh sách loại phòng</h2>
                    <p>Quản lý các hạng phòng khách sạn</p>
                </div>

                <a href="<?php echo e(route('room-categories.create')); ?>" class="btn btn-gold">
                    <i class="bx bx-plus me-1"></i>
                    Thêm loại phòng
                </a>

            </div>
<div class="settings-section mb-3">
                <form method="GET" action="<?php echo e(route('room-categories.index')); ?>" class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tìm hạng phòng</label>
                        <input type="text" name="search" value="<?php echo e($search); ?>" class="form-control"
                            placeholder="Nhập tên hạng phòng...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="all" <?php if($status === 'all'): echo 'selected'; endif; ?>>Tất cả</option>
                            <option value="active" <?php if($status === 'active'): echo 'selected'; endif; ?>>Đang hoạt động</option>
                            <option value="inactive" <?php if($status === 'inactive'): echo 'selected'; endif; ?>>Tạm ẩn</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1"><i class="bx bx-filter-alt me-1"></i>Lọc</button>
                        <a href="<?php echo e(route('room-categories.index')); ?>" class="btn btn-outline-secondary">Đặt lại</a>
                    </div>
                </form>
            </div>

            <div class="settings-section">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Ảnh</th>
                                <th>Tên loại phòng</th>
                                <th>Giá</th>
                                <th>Số người</th>
                                <th>Diện tích</th>
                                <th>Giường</th>
                                <th>Tổng số phòng</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php $__empty_1 = true; $__currentLoopData = $roomCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                                <tr>

                                    <td><?php echo e($category->id); ?></td>

                                    <td>

                                        <?php if($category->thumbnail): ?>

                                            <img src="<?php echo e(asset('storage/' . $category->thumbnail)); ?>" width="90" height="60"
                                                style="object-fit: cover; border-radius: 8px;">

                                        <?php elseif($category->images->count()): ?>

                                            <img src="<?php echo e(asset('storage/' . $category->images->first()->image)); ?>" width="90"
                                                height="60" style="object-fit: cover; border-radius: 8px;">

                                        <?php else: ?>

                                            <span class="text-muted">
                                                Chưa có ảnh
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>
                                        <?php echo e($category->name); ?>

                                    </td>

                                    <td>
                                        <?php echo e(number_format($category->price, 0, ',', '.')); ?>đ
                                    </td>

                                    <td>

                                        <?php echo e($category->adult_capacity); ?> NL

                                        <br>

                                        <small class="text-muted">
                                            <?php echo e($category->child_capacity); ?> TE
                                        </small>

                                    </td>

                                    <td>
                                        <?php echo e($category->area); ?> m²
                                    </td>

                                    <td>
                                        <?php echo e($category->bed_count); ?> giường
                                    </td>

                                    <td>
                                        <span class="fw-bold"><?php echo e($category->rooms_count); ?></span> phòng
                                    </td>

                                    <td>

                                        <?php if($category->status === 'active'): ?>

                                            <span class="badge bg-success">
                                                Hoạt động
                                            </span>

                                        <?php else: ?>

                                            <span class="badge bg-secondary">
                                                Tạm ẩn
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td class="text-end text-nowrap">

                                        <a href="<?php echo e(route('room-categories.show', $category->id)); ?>"
                                            class="btn btn-sm btn-outline-secondary">
                                            Xem
                                        </a>


                                        <a href="<?php echo e(route('room-categories.edit', $category->id)); ?>"
                                            class="btn btn-sm btn-outline-primary">
                                            Sửa
                                        </a>

                                        <form action="<?php echo e(route('room-categories.destroy', $category->id)); ?>" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa loại phòng này không?')">

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
                                    <td colspan="10" class="text-center text-muted">
                                        Chưa có loại phòng nào
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    <?php echo e($roomCategories->links()); ?>

                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/room-categories/index.blade.php ENDPATH**/ ?>