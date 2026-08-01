<?php $__env->startSection('title', 'Danh sách nhân viên'); ?>

<?php $__env->startSection('content'); ?>
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('staffs.index')); ?>">Admin</a> / Nhân viên
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Danh sách nhân viên</h2>
                    <p>Quản lý nhân viên khách sạn</p>
                </div>

                <a href="<?php echo e(route('staffs.create')); ?>" class="btn btn-gold">
                    <i class="bx bx-plus me-1"></i>Thêm nhân viên
                </a>
            </div>

            <?php if(session('success')): ?>
                <div class="alert alert-success">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>

            <div class="settings-section">
                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>SĐT</th>
                                <th>Chức vụ</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $staffs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($staff->id); ?></td>
                                    <td><?php echo e($staff->full_name); ?></td>
                                    <td><?php echo e($staff->user->email ?? 'Chưa có tài khoản'); ?></td>
                                    <td><?php echo e($staff->phone ?? '-'); ?></td>
                                    <td><?php echo e($staff->position ?? '-'); ?></td>

                                    <td>
                                        <?php if($staff->work_status === 'working'): ?>
                                            <span class="badge bg-success">Đang làm</span>
                                        <?php elseif($staff->work_status === 'temporary_leave'): ?>
                                            <span class="badge bg-warning text-dark">Nghỉ tạm</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Đã nghỉ</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <a href="<?php echo e(route('staffs.show', $staff->id)); ?>"
                                            class="btn btn-sm btn-outline-secondary">
                                            Xem
                                        </a>

                                        <a href="<?php echo e(route('staffs.edit', $staff->id)); ?>" class="btn btn-sm btn-outline-primary">
                                            Sửa
                                        </a>

                                        <form action="<?php echo e(route('staffs.destroy', $staff->id)); ?>" method="POST" class="d-inline"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa nhân viên này không?')">
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
                                    <td colspan="7" class="text-center text-muted">
                                        Chưa có nhân viên nào
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                </div>

                <div class="mt-3">
                    <?php echo e($staffs->links()); ?>

                </div>
            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\staffs\index.blade.php ENDPATH**/ ?>