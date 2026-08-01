<?php $__env->startSection('title', 'Gợi ý phòng thay thế'); ?>

<?php $__env->startSection('content'); ?>

    <style>
        .suggestion-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            height: 100%;
            overflow: hidden;
        }

        .suggestion-card-head {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .suggestion-card-body {
            padding: 16px;
        }

        .suggestion-room-group {
            padding: 12px;
            border-radius: 12px;
            background: #f8fafc;
            margin-bottom: 10px;
        }

        .suggestion-room-group strong {
            display: block;
            color: #1f2937;
        }

        .suggestion-sub-text {
            font-size: 13px;
            color: #64748b;
        }

        .request-box {
            border: 1px solid #facc15;
            background: #fef9c3;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 18px;
        }
    </style>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Đặt phòng / Gợi ý phòng thay thế
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Gợi ý phòng thay thế</h2>
                    <p>Hạng phòng đã chọn không đủ số lượng. Lễ tân chọn một phương án bên dưới để tạo booking nhanh.</p>
                </div>

                <a href="<?php echo e(route('admin.bookings.create')); ?>" class="btn btn-outline-secondary">
                    Quay lại tạo booking
                </a>
            </div>

            <div class="request-box">
                <strong>Yêu cầu ban đầu</strong>

                <div class="row mt-2">
                    <div class="col-md-3">
                        <div class="suggestion-sub-text">Hạng phòng</div>
                        <strong><?php echo e($roomCategory->name); ?></strong>
                    </div>

                    <div class="col-md-2">
                        <div class="suggestion-sub-text">Số phòng</div>
                        <strong><?php echo e($data['room_quantity']); ?></strong>
                    </div>

                    <div class="col-md-2">
                        <div class="suggestion-sub-text">Ngày nhận</div>
                        <strong><?php echo e(date('d/m/Y', strtotime($data['check_in_date']))); ?></strong>
                    </div>

                    <div class="col-md-2">
                        <div class="suggestion-sub-text">Ngày trả</div>
                        <strong><?php echo e(date('d/m/Y', strtotime($data['check_out_date']))); ?></strong>
                    </div>

                    <div class="col-md-3">
                        <div class="suggestion-sub-text">Yêu cầu cạnh nhau</div>
                        <strong><?php echo e($preferAdjacentRooms ? 'Có' : 'Không'); ?></strong>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <?php $__currentLoopData = $suggestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $suggestion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="suggestion-card">

                            <div class="suggestion-card-head">
                                <div>
                                    <h5 class="mb-0">Phương án <?php echo e($index + 1); ?></h5>
                                    <div class="suggestion-sub-text">
                                        <?php echo e($suggestion['label'] ?? 'Phương án thay thế'); ?>

                                    </div>
                                </div>
                                <span class="badge bg-light text-dark">
                                    <?php echo e(count($suggestion['rooms'])); ?> phòng
                                </span>
                            </div>

                            <div class="px-3 pt-2">

                                <div class="text-muted small">
                                    Tổng tiền tạm tính
                                </div>

                                <h4 class="fw-bold text-primary mb-0">
                                    <?php echo e(number_format($suggestion['estimated_total'], 0, ',', '.')); ?>đ
                                </h4>

                                <small class="text-muted">
                                    <?php echo e($suggestion['night_count']); ?> đêm
                                </small>

                            </div>

                            <div class="suggestion-card-body">
                                <?php $__currentLoopData = $suggestion['summary']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="suggestion-room-group">
                                        <strong>
                                            <?php echo e($item['quantity']); ?> phòng <?php echo e($item['category_name']); ?>

                                        </strong>

                                        <div class="suggestion-sub-text">
                                            Tầng:
                                            <?php echo e($item['floors']->implode(', ')); ?>

                                        </div>

                                        <div class="suggestion-sub-text">
                                            Giá:
                                            <?php echo e(number_format($item['price'], 0, ',', '.')); ?>đ / đêm
                                        </div>

                                        <div class="suggestion-sub-text">
                                            Phòng: <?php echo e(implode(', ', $item['rooms']->toArray())); ?>

                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                <form method="POST" action="<?php echo e(route('admin.bookings.suggestions.store')); ?>" class="mt-3">
                                    <?php echo csrf_field(); ?>

                                    <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if(is_array($value)): ?>
                                            <?php $__currentLoopData = $value; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php if(is_array($item)): ?>
                                                    <?php $__currentLoopData = $item; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subKey => $subValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <input type="hidden" name="<?php echo e($key); ?>[<?php echo e($index); ?>][<?php echo e($subKey); ?>]" value="<?php echo e($subValue); ?>">
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <?php else: ?>
                                                    <input type="hidden" name="<?php echo e($key); ?>[]" value="<?php echo e($item); ?>">
                                                <?php endif; ?>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php else: ?>
                                            <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                    <?php $__currentLoopData = $suggestion['rooms']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <input type="hidden" name="selected_room_ids[]" value="<?php echo e($room->id); ?>">
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                    <button type="submit" class="btn btn-gold w-100">
                                        Chọn phương án này
                                    </button>
                                </form>
                            </div>

                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\bookings\suggestions.blade.php ENDPATH**/ ?>