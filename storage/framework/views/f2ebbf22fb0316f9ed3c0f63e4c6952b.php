<?php $__env->startSection('title', 'Chi tiết khách đang lưu trú'); ?>

<?php $__env->startSection('content'); ?>
<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> /
            <a href="<?php echo e(route('admin.staying-guests.index')); ?>">Khách đang lưu trú</a> / Chi tiết
        </p>

        <div class="admin-page-head d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>Chi tiết lưu trú</h2>
                <p>Booking <?php echo e($booking->booking_code); ?> · toàn bộ người thực tế đã được khai theo từng phòng</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo e(route('admin.bookings.show', $booking)); ?>#stayingGuestsPanel" class="btn btn-outline-primary">Quản lý hồ sơ khách</a>
                <a href="<?php echo e(route('admin.staying-guests.index')); ?>" class="btn btn-outline-secondary">Quay lại</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Kỳ lưu trú</h5>
                <div class="d-grid gap-2 small">
                    <div class="d-flex justify-content-between"><span class="text-muted">Nhận thực tế</span><strong><?php echo e($booking->actual_check_in?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '---'); ?></strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Trả dự kiến</span><strong><?php echo e($booking->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '---'); ?></strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Khách hiện tại</span><strong><?php echo e($booking->guests->where('status', 'checked_in')->count()); ?> người</strong></div>
                </div>
            </div></div>
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Đại diện đoàn</h5>
                <?php ($representative = $booking->guests->firstWhere('is_booking_representative', true)); ?>
                <div class="d-grid gap-2 small">
                    <div><span class="text-muted d-block">Họ tên</span><strong><?php echo e($representative?->full_name ?? 'Chưa chọn'); ?></strong></div>
                    <div><span class="text-muted d-block">Giấy tờ</span><strong><?php echo e($representative?->display_document ?? '---'); ?></strong></div>
                    <div><span class="text-muted d-block">Phòng</span><strong><?php echo e($representative?->bookingRoom?->room?->room_number ?? '---'); ?></strong></div>
                </div>
            </div></div>
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Phòng đang giữ</h5>
                <div class="d-flex flex-wrap gap-2">
                    <?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span class="badge text-bg-light border p-2">Phòng <?php echo e($bookingRoom->room?->room_number ?? '---'); ?> · <?php echo e($booking->guests->where('booking_room_id', $bookingRoom->id)->count()); ?> khách</span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div></div>
        </div>

        <?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($roomGuests = $booking->guests->where('booking_room_id', $bookingRoom->id)); ?>
            <div class="settings-section mb-3">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Phòng <?php echo e($bookingRoom->room?->room_number ?? '---'); ?></h5>
                        <div class="text-muted small"><?php echo e($bookingRoom->room?->category?->name ?? '---'); ?></div>
                    </div>
                    <span class="badge text-bg-primary"><?php echo e($roomGuests->count()); ?> khách</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>Khách</th><th>Nhóm tuổi</th><th>Giấy tờ</th><th>Ngày sinh</th><th>Người giám hộ</th><th>Trạng thái</th>
                        </tr></thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $roomGuests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><strong><?php echo e($guest->full_name); ?></strong><?php if($guest->is_booking_representative): ?><div><span class="badge text-bg-primary">Đại diện đoàn</span></div><?php endif; ?></td>
                                    <td><?php echo e(['adult'=>'Người lớn','child'=>'Trẻ em','infant'=>'Em bé'][$guest->guest_type] ?? '---'); ?></td>
                                    <td><?php echo e($guest->display_document ?: 'Chưa xuất trình'); ?></td>
                                    <td><?php echo e($guest->birthday?->format('d/m/Y') ?? '---'); ?></td>
                                    <td><?php echo e($guest->guardian?->full_name ?? '---'); ?><?php if($guest->guardian_relationship): ?><div class="small text-muted"><?php echo e($guest->guardian_relationship); ?></div><?php endif; ?></td>
                                    <td><?php echo e(['registered'=>'Chưa đến','checked_in'=>'Đang lưu trú','checked_out'=>'Đã rời đi'][$guest->status] ?? $guest->status); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">Phòng này chưa có khách thực tế nhận phòng.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\staying-guests\show.blade.php ENDPATH**/ ?>