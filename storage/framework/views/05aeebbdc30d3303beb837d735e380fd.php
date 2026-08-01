<?php $__env->startSection('title', 'Quản lý khách hàng'); ?>

<?php $__env->startSection('content'); ?>
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Khách hàng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Quản lý khách hàng</h2>
                    <p>Danh sách khách hàng và lịch sử đặt phòng</p>
                </div>

            </div>

            <!-- Filter -->
            <div class="bg-white p-4 rounded-3 mb-4 border">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" 
                               name="keyword" 
                               class="form-control" 
                               placeholder="Tìm theo tên, SĐT, email, CCCD..."
                               value="<?php echo e(request('keyword')); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="active" <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Hoạt động</option>
                            <option value="blacklist" <?php echo e(request('status') === 'blacklist' ? 'selected' : ''); ?>>Blacklist</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Lọc</button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3 border overflow-hidden">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tên khách hàng</th>
                            <th>SĐT</th>
                            <th>Email</th>
                            <th>CCCD</th>
                            <th>Số booking</th>
                            <th>Tổng chi tiêu</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo e($customer->first_name); ?> <?php echo e($customer->last_name); ?></div>
                                    <?php if($customer->gender): ?>
                                        <small class="text-muted"><?php echo e($customer->gender === 'male' ? 'Nam' : ($customer->gender === 'female' ? 'Nữ' : 'Khác')); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($customer->phone); ?></td>
                                <td><?php echo e($customer->email ?? '-'); ?></td>
                                <td><?php echo e($customer->cccd ?? '-'); ?></td>
                                <td><?php echo e($customer->bookings->count()); ?></td>
                                <td><?php echo e(number_format($customer->bookings->sum('estimated_total'), 0, ',', '.')); ?>đ</td>
                                <td>
                                    <?php if($customer->status === 'active'): ?>
                                        <span class="badge bg-success">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Blacklist</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo e(route('admin.customers.show', $customer)); ?>" class="btn btn-sm btn-outline-primary">
                                        Chi tiết
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>

                <?php if($customers->hasPages()): ?>
                    <div class="p-3 border-top">
                        <?php echo e($customers->links()); ?>

                    </div>
                <?php endif; ?>
            </div>

        </main>

    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views/admin/pages/customers/index.blade.php ENDPATH**/ ?>