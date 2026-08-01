<?php $__env->startSection('title', 'Chi tiết khách hàng'); ?>

<?php $__env->startSection('content'); ?>
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / 
                <a href="<?php echo e(route('admin.customers.index')); ?>">Khách hàng</a> / 
                Chi tiết
            </p>

            <div class="admin-page-head">

                <div>
                    <h2><?php echo e($customer->first_name); ?> <?php echo e($customer->last_name); ?></h2>
                    <p>Thông tin chi tiết và lịch sử đặt phòng</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?php echo e(route('admin.customers.edit', $customer)); ?>" class="btn btn-outline-primary">
                        Chỉnh sửa
                    </a>
                    <a href="<?php echo e(route('admin.customers.index')); ?>" class="btn btn-outline-secondary">
                        Quay lại
                    </a>
                </div>

            </div>

            <!-- Customer Info -->
            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="bg-white p-4 rounded-3 border">
                        <h5 class="mb-3">Thông tin cá nhân</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Họ tên</label>
                                <div class="fw-semibold"><?php echo e($customer->first_name); ?> <?php echo e($customer->last_name); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Số điện thoại</label>
                                <div class="fw-semibold"><?php echo e($customer->phone); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Email</label>
                                <div><?php echo e($customer->email ?? '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">CCCD</label>
                                <div><?php echo e($customer->cccd ?? '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Ngày sinh</label>
                                <div><?php echo e($customer->birthday ? \Carbon\Carbon::parse($customer->birthday)->format('d/m/Y') : '-'); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Giới tính</label>
                                <div><?php echo e($customer->gender ? ($customer->gender === 'male' ? 'Nam' : ($customer->gender === 'female' ? 'Nữ' : 'Khác')) : '-'); ?></div>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small">Địa chỉ</label>
                                <div><?php echo e($customer->address ?? '-'); ?></div>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small">Ghi chú</label>
                                <div><?php echo e($customer->note ?? '-'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-white p-4 rounded-3 border">
                        <h5 class="mb-3">Thống kê</h5>
                        <div class="mb-3">
                            <label class="text-muted small">Tổng số booking</label>
                            <div class="fs-4 fw-bold"><?php echo e($totalBookings); ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Tổng chi tiêu</label>
                            <div class="fs-4 fw-bold text-success"><?php echo e(number_format($totalSpent, 0, ',', '.')); ?>đ</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Đã thanh toán</label>
                            <div class="fs-4 fw-bold text-primary"><?php echo e(number_format($totalPaid, 0, ',', '.')); ?>đ</div>
                        </div>
                        <div>
                            <label class="text-muted small">Còn nợ</label>
                            <div class="fs-4 fw-bold text-warning"><?php echo e(number_format($totalSpent - $totalPaid, 0, ',', '.')); ?>đ</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking History -->
            <div class="bg-white p-4 rounded-3 border">
                <h5 class="mb-3">Lịch sử đặt phòng</h5>
                
                <?php if($customer->bookings->isEmpty()): ?>
                    <div class="text-center py-4 text-muted">
                        Chưa có booking nào
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Mã booking</th>
                                    <th>Hạng phòng</th>
                                    <th>Ngày nhận</th>
                                    <th>Ngày trả</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $customer->bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo e(route('admin.bookings.show', $booking)); ?>" class="fw-semibold">
                                                <?php echo e($booking->booking_code); ?>

                                            </a>
                                        </td>
                                        <td><?php echo e($booking->roomCategory->name ?? '-'); ?></td>
                                        <td><?php echo e(\Carbon\Carbon::parse($booking->check_in_at)->format('d/m/Y H:i')); ?></td>
                                        <td><?php echo e(\Carbon\Carbon::parse($booking->check_out_at)->format('d/m/Y H:i')); ?></td>
                                        <td><?php echo e(number_format($booking->estimated_total, 0, ',', '.')); ?>đ</td>
                                        <td>
                                            <?php switch($booking->status):
                                                case ('confirmed'): ?>
                                                    <span class="badge bg-warning">Đã xác nhận</span>
                                                    <?php break; ?>
                                                <?php case ('checked_in'): ?>
                                                    <span class="badge bg-primary">Đã check-in</span>
                                                    <?php break; ?>
                                                <?php case ('checked_out'): ?>
                                                    <span class="badge bg-success">Đã trả phòng</span>
                                                    <?php break; ?>
                                                <?php case ('cancelled'): ?>
                                                    <span class="badge bg-danger">Đã hủy</span>
                                                    <?php break; ?>
                                                <?php default: ?>
                                                    <span class="badge bg-secondary"><?php echo e($booking->status); ?></span>
                                            <?php endswitch; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo e(route('admin.bookings.show', $booking)); ?>" class="btn btn-sm btn-outline-primary">
                                                Xem
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </main>

    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\customers\show.blade.php ENDPATH**/ ?>