<?php $__env->startSection('title', 'Phòng cần dọn'); ?>

<?php $__env->startSection('content'); ?>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Phòng cần dọn
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Phòng cần dọn</h2>
                    <?php if(auth()->user()?->role === 'housekeeping'): ?>
                        <p>Chỉ hiển thị phòng thuộc tầng bạn phụ trách lâu dài hoặc nhiệm vụ phòng được giao cho hôm nay.</p>
                    <?php else: ?>
                        <p>Danh sách toàn bộ phòng cần dọn để quản lý/trưởng buồng phòng điều phối. Phòng chưa có người phụ trách sẽ được đánh dấu rõ.</p>
                    <?php endif; ?>
                </div>

            </div>

<div class="settings-section">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th>Phòng</th>
                                <th>Hạng phòng</th>
                                <th>Tầng</th>
                                <th>Trạng thái</th>
                                <th>Phụ trách</th>
                                <th>Ghi chú</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php $__empty_1 = true; $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                                <tr>

                                    <td>
                                        <strong>Phòng <?php echo e($room->room_number); ?></strong>
                                    </td>

                                    <td>
                                        <?php echo e($room->category->name ?? 'Không xác định'); ?>

                                    </td>

                                    <td>
                                        <?php echo e($room->floor_number ?? '---'); ?>

                                    </td>

                                    <td>
                                        <span class="badge bg-primary">Đang dọn dẹp</span>
                                        <?php if(str_contains((string) $room->note, '[PRIORITY_BOOKING:')): ?>
                                            <span class="badge bg-danger ms-1">Ưu tiên - khách đang/chờ nhận phòng</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if(($room->housekeeping_assignees ?? collect())->isNotEmpty()): ?>
                                            <?php $__currentLoopData = $room->housekeeping_assignees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <span class="badge bg-light text-dark border me-1 mb-1"><?php echo e($assignee); ?></span>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Chưa phân công</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php echo e($room->note ?: 'Không có ghi chú'); ?>

                                    </td>

                                    <td class="text-end">

                                        <form action="<?php echo e(route('admin.housekeeping.mark-available', $room->id)); ?>" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Xác nhận phòng đã dọn xong và sẵn sàng cho thuê?')">

                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>

                                            <button type="submit" class="btn btn-sm btn-success">
                                                Dọn xong
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        Không có phòng nào cần dọn.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    <?php echo e($rooms->links()); ?>

                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/housekeeping/index.blade.php ENDPATH**/ ?>