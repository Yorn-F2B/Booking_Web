<?php $__env->startSection('title', 'Chi tiết khách đang lưu trú'); ?>

<?php $__env->startSection('content'); ?>
<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> /
            <a href="<?php echo e(route('admin.staying-guests.index')); ?>">Khách đang lưu trú</a> / Chi tiết
        </p>

        <?php
            $needsGroupRepresentative = $booking->bookingRooms->count() > 1;
            $groupRepresentative = $booking->guests->firstWhere('is_booking_representative', true);
        ?>

        <div class="admin-page-head d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>Chi tiết lưu trú</h2>
                <p>Booking <?php echo e($booking->booking_code); ?> · quản lý người đại diện của từng phòng</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo e(route('admin.bookings.show', $booking)); ?>#stayingGuestsPanel" class="btn btn-outline-primary">Quản lý người đại diện</a>
                <a href="<?php echo e(route('admin.staying-guests.index')); ?>" class="btn btn-outline-secondary">Quay lại</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Kỳ lưu trú</h5>
                <div class="d-grid gap-2 small">
                    <div class="d-flex justify-content-between"><span class="text-muted">Nhận thực tế</span><strong><?php echo e($booking->actual_check_in?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '---'); ?></strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Trả dự kiến</span><strong><?php echo e($booking->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '---'); ?></strong></div>
                </div>
            </div></div>
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Đầu mối đoàn</h5>
                <?php if($needsGroupRepresentative): ?>
                    <div class="d-grid gap-2 small">
                        <div><span class="text-muted d-block">Họ tên</span><strong><?php echo e($groupRepresentative?->full_name ?? 'Chưa chọn'); ?></strong></div>
                        <div><span class="text-muted d-block">Giấy tờ</span><strong><?php echo e($groupRepresentative?->display_document ?? '---'); ?></strong></div>
                        <div><span class="text-muted d-block">Phòng</span><strong><?php echo e($groupRepresentative?->bookingRoom?->room?->room_number ?? '---'); ?></strong></div>
                    </div>
                <?php else: ?>
                    <div class="small text-muted">Booking chỉ có 1 phòng nên không cần thêm vai trò đại diện cả đoàn.</div>
                <?php endif; ?>
            </div></div>
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Nguyên tắc lưu hồ sơ</h5>
                <div class="small">
                    <div class="mb-2"><strong><?php echo e($booking->bookingRooms->count()); ?> phòng</strong> → cần <?php echo e($booking->bookingRooms->count()); ?> người đại diện phòng.</div>
                    <div>Không lưu toàn bộ danh sách khách; chỉ lưu hồ sơ người đại diện của từng phòng.</div>
                </div>
            </div></div>
        </div>

        <?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $roomRepresentative = $booking->guests->where('booking_room_id', $bookingRoom->id)
                    ->first(fn ($guest) => $guest->guest_type === 'adult');
            ?>
            <div class="settings-section mb-3">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
                    <div>
                        <h5 class="fw-bold mb-1">Phòng <?php echo e($bookingRoom->room?->room_number ?? '---'); ?></h5>
                        <div class="text-muted small"><?php echo e($bookingRoom->room?->category?->name ?? '---'); ?></div>
                    </div>
                    <span class="badge <?php echo e($roomRepresentative ? 'text-bg-success' : 'text-bg-warning'); ?>"><?php echo e($roomRepresentative ? 'Đã có đại diện' : 'Chưa có đại diện'); ?></span>
                </div>

                <?php if($roomRepresentative): ?>
                    <div class="row g-3">
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="small text-muted d-block">Người đại diện phòng</span><strong><?php echo e($roomRepresentative->full_name); ?></strong><?php if($roomRepresentative->is_booking_representative): ?><div class="mt-1"><span class="badge text-bg-primary">Đại diện cả đoàn</span></div><?php endif; ?></div></div>
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="small text-muted d-block">Giấy tờ</span><strong><?php echo e($roomRepresentative->display_document ?: 'Chưa xuất trình'); ?></strong></div></div>
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="small text-muted d-block">Ngày sinh</span><strong><?php echo e($roomRepresentative->birthday?->format('d/m/Y') ?? '---'); ?></strong></div></div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">Phòng này chưa có hồ sơ người đại diện.</div>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </main>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/staying-guests/show.blade.php ENDPATH**/ ?>