<?php $__env->startSection('title', 'Danh sách khách đang lưu trú'); ?>

<?php $__env->startSection('content'); ?>
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Khách đang lưu trú
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Khách đang lưu trú</h2>
                    <p>Mỗi phòng chỉ quản lý một người đại diện; booking nhiều phòng có thêm một đại diện cả đoàn</p>
                </div>
            </div>

            <div class="settings-section mb-4">
                <form action="<?php echo e(route('admin.staying-guests.index')); ?>" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Tìm người đại diện, giấy tờ, SĐT, phòng, mã booking..." value="<?php echo e(request('search')); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
                    </div>
                    <div class="col-md-2">
                        <a href="<?php echo e(route('admin.staying-guests.index')); ?>" class="btn btn-outline-secondary w-100">Xóa lọc</a>
                    </div>
                </form>
            </div>

            <div class="settings-section">
                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Phòng</th>
                                <th>Mã Booking</th>
                                <th>Người đại diện phòng</th>
                                <th>Liên hệ booking</th>
                                <th>Nhận phòng</th>
                                <th>Dự kiến trả phòng</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $rooms = $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ');
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($rooms ?: 'Chưa gán'); ?></strong>
                                    </td>
                                    <td>
                                        <a href="<?php echo e(route('admin.bookings.show', $booking->id)); ?>" class="fw-bold">
                                            <?php echo e($booking->booking_code); ?>

                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                            $roomRepresentatives = $booking->bookingRooms->map(function ($bookingRoom) use ($booking) {
                                                return $booking->guests->where('booking_room_id', $bookingRoom->id)
                                                    ->first(fn ($guest) => $guest->guest_type === 'adult');
                                            })->filter();
                                        ?>
                                        <?php if($roomRepresentatives->isNotEmpty()): ?>
                                            <ul class="mb-0 ps-3 small">
                                                <?php $__currentLoopData = $roomRepresentatives; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <li>
                                                        <strong>P.<?php echo e($guest->bookingRoom?->room?->room_number ?? '---'); ?></strong> · <?php echo e($guest->full_name); ?>

                                                        <?php if($guest->is_booking_representative): ?><span class="text-primary fw-semibold"> · đại diện đoàn</span><?php endif; ?>
                                                    </li>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </ul>
                                        <?php else: ?>
                                            <span class="text-warning">Chưa có người đại diện</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($booking->customer): ?>
                                            <div><i class="bx bx-phone me-1"></i><?php echo e($booking->customer->phone ?? '-'); ?></div>
                                            <div class="small text-muted"><i class="bx bx-id-card me-1"></i><?php echo e($booking->customer->cccd ?? '-'); ?></div>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo e($booking->actual_check_in ? \Carbon\Carbon::parse($booking->actual_check_in)->format('d/m/Y H:i') : ($booking->check_in_at ? \Carbon\Carbon::parse($booking->check_in_at)->format('d/m/Y H:i') : '-')); ?>

                                    </td>
                                    <td>
                                        <span class="text-danger fw-bold">
                                            <?php echo e($booking->check_out_at ? \Carbon\Carbon::parse($booking->check_out_at)->format('d/m/Y H:i') : '-'); ?>

                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo e(route('admin.staying-guests.show', $booking)); ?>" class="btn btn-sm btn-outline-primary">
                                            Xem chi tiết
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Hiện tại không có phòng nào đang có khách lưu trú.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                </div>

                <div class="mt-3">
                    <?php echo e($bookings->links()); ?>

                </div>
            </div>

        </main>

    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/staying-guests/index.blade.php ENDPATH**/ ?>