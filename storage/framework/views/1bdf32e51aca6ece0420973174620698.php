<?php if($items->isEmpty()): ?>
    <div class="soft-note"><?php echo e($emptyText ?? 'Chưa có khoản nào.'); ?></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-clean align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tên khoản thu</th>
                    <th>Phạm vi</th>
                    <th>Loại</th>
                    <th>Đơn giá</th>
                    <th>SL</th>
                    <th>Dùng</th>
                    <th>Thành tiền</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $typeLabels = \App\Models\Service::typeLabels();
                        $isSurcharge = in_array($item->type, \App\Models\Service::surchargeCatalogTypes(), true)
                            || $item->type === 'violation_fee';
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo e($item->name); ?></strong>
                            <?php if($item->billing_status === 'pending'): ?>
                                <span class="badge bg-warning text-dark ms-1">Khách yêu cầu · Chờ xác nhận</span>
                            <?php elseif($item->billing_status === 'unused'): ?>
                                <span class="badge bg-secondary ms-1">Chưa sử dụng</span>
                            <?php elseif($item->billing_status === 'cancelled'): ?>
                                <span class="badge bg-secondary ms-1">Đã hủy</span>
                            <?php endif; ?>
                            <?php if($item->note): ?>
                                <div class="text-muted small"><?php echo e($item->note); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(($item->scope ?? 'booking') === 'room'): ?>
                                <span class="badge bg-primary">Phòng <?php echo e($item->bookingRoom?->room?->room_number ?? $item->roomSnapshot?->room_number ?? '---'); ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Toàn bộ đơn</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($isSurcharge): ?>
                                <span class="badge-clean status-muted"><?php echo e($typeLabels[$item->type] ?? 'Phụ thu'); ?></span>
                            <?php elseif($item->type === 'minibar_order'): ?>
                                <span class="badge bg-info text-dark">Minibar gọi thêm</span>
                            <?php elseif($item->type === 'minibar'): ?>
                                <span class="badge-clean status-warning">Minibar</span>
                            <?php else: ?>
                                <span class="badge-clean status-info"><?php echo e($typeLabels[$item->type] ?? 'Dịch vụ'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e(number_format((float) $item->unit_price, 0, ',', '.')); ?>đ</td>
                        <td style="min-width: 120px;">
                            <?php if($canEditServiceItems && in_array($item->type, ['service', 'minibar_order'], true)): ?>
                                <form
                                    action="<?php echo e(route('admin.bookings.service-items.update', [$booking->id, $item->id])); ?>"
                                    method="POST" class="d-flex gap-1 align-items-center">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <input type="number" name="quantity" class="form-control form-control-sm"
                                        value="<?php echo e($item->quantity); ?>" min="1" max="999" style="width: 72px;">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <?php echo e($item->billing_status === 'pending' ? 'Xác nhận' : 'Lưu'); ?>

                                    </button>
                                </form>
                            <?php else: ?>
                                <?php echo e($item->quantity); ?>

                            <?php endif; ?>
                        </td>
                        <td><?php echo e($item->billing_status === 'pending' ? 0 : ($item->used_quantity ?? $item->quantity)); ?></td>
                        <td class="fw-bold <?php echo e($item->billing_status === 'pending' ? 'text-warning' : 'text-danger'); ?>">
                            <?php if($item->billing_status === 'pending'): ?>
                                Chờ xác nhận
                            <?php else: ?>
                                <?php echo e(number_format((float) $item->total, 0, ',', '.')); ?>đ
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if($canEditServiceItems && in_array($item->type, ['service', 'minibar_order'], true)): ?>
                                <form
                                    action="<?php echo e(route('admin.bookings.service-items.destroy', [$booking->id, $item->id])); ?>"
                                    method="POST" onsubmit="return confirm('Xóa dịch vụ này khỏi đơn?')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <?php echo e($item->billing_status === 'pending' ? 'Từ chối' : 'Xóa'); ?>

                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/bookings/partials/service-item-table.blade.php ENDPATH**/ ?>