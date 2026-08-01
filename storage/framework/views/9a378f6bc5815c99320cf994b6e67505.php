<?php if($services->isEmpty()): ?>
    <div class="text-muted">Chưa có danh mục phù hợp.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light"><tr><th style="width:60px">Chọn</th><th>Hạng mục</th><th>Đơn giá</th><th style="width:120px">Số lượng</th><th>Tạm tính</th></tr></thead>
            <tbody>
                <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $oldItem = $itemMap[$service->id] ?? null;
                        $checked = (bool) $oldItem;
                        $quantity = $oldItem?->quantity ?? 1;
                    ?>
                    <tr>
                        <td><input type="checkbox" name="<?php echo e($checkboxName); ?>" value="<?php echo e($service->id); ?>" class="form-check-input <?php echo e($checkboxClass); ?>" <?php if($checked): echo 'checked'; endif; ?>></td>
                        <td><strong><?php echo e($service->name); ?></strong><?php if($service->description): ?><div class="small text-muted"><?php echo e($service->description); ?></div><?php endif; ?></td>
                        <td><?php echo e(number_format((float) $service->price, 0, ',', '.')); ?>đ / <?php echo e($service->unit ?: 'lần'); ?></td>
                        <td><input type="number" min="1" name="<?php echo e($quantityName); ?>[<?php echo e($service->id); ?>]" value="<?php echo e($quantity); ?>" class="form-control form-control-sm inspection-service-quantity" data-price="<?php echo e((float) $service->price); ?>" <?php echo e($checked ? '' : 'disabled'); ?>></td>
                        <td class="fw-semibold inspection-service-total"><?php echo e($checked ? number_format((float) $service->price * $quantity, 0, ',', '.') . 'đ' : '0đ'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\floor-inspections\partials\service-table.blade.php ENDPATH**/ ?>