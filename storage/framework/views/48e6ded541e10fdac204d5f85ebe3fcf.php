<?php $__env->startSection('title', 'Chi tiết phòng'); ?>

<?php $__env->startSection('content'); ?>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.rooms.index')); ?>">
                    Admin
                </a>

                / Chi tiết phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Chi tiết phòng</h2>
                    <p>Thông tin chi tiết phòng khách sạn</p>
                </div>

                <a href="<?php echo e(route('admin.rooms.index', ['edit_room' => $room->id])); ?>" class="btn btn-gold">
                    <i class="bx bx-edit me-1"></i>
                    Chỉnh sửa
                </a>

            </div>

            <div class="settings-section">

                <div class="row">

                    <div class="col-lg-12">

                        <div class="row">

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    ID phòng
                                </label>

                                <h5>
                                    <?php echo e($room->id); ?>

                                </h5>

                            </div>

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    Số phòng
                                </label>

                                <h5>
                                    <?php echo e($room->room_number); ?>

                                </h5>

                            </div>

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    Tầng
                                </label>

                                <h5>
                                    <?php echo e($room->floor_number ?? 'Chưa cập nhật'); ?>

                                </h5>

                            </div>

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    Loại phòng
                                </label>

                                <h5>
                                    <?php echo e($room->category->name ?? 'Không xác định'); ?>

                                </h5>

                            </div>

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    Trạng thái
                                </label>

                                <div>

                                    <?php if($room->status == 'available'): ?>

                                        <span class="badge bg-success">
                                            Còn trống
                                        </span>

                                    <?php elseif($room->status == 'reserved'): ?>

                                        <span class="badge bg-warning text-dark">
                                            Đã đặt trước
                                        </span>

                                    <?php elseif($room->status == 'occupied'): ?>

                                        <span class="badge bg-danger">
                                            Đang có khách
                                        </span>

                                    <?php elseif($room->status == 'cleaning'): ?>

                                        <span class="badge bg-info text-dark">
                                            Đang dọn phòng
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            Bảo trì
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    Ngày tạo
                                </label>

                                <h5>
                                    <?php echo e($room->created_at?->format('d/m/Y H:i')); ?>

                                </h5>

                            </div>

                        </div>

                    </div>

                </div>

                <hr class="my-4">

                <div>

                    <label class="text-muted small mb-2">
                        Ghi chú
                    </label>

                    <div class="lh-lg">

                        <?php echo e($room->note ?: 'Không có ghi chú'); ?>


                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">

                    <a href="<?php echo e(route('admin.rooms.index', ['edit_room' => $room->id])); ?>"
                        class="btn btn-primary">
                        Chỉnh sửa
                    </a>

                    <a href="<?php echo e(route('admin.rooms.index')); ?>"
                        class="btn btn-outline-secondary">
                        Quay lại
                    </a>

                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\rooms\show.blade.php ENDPATH**/ ?>