<?php if($services->isEmpty()): ?>
    <div class="text-muted">Chưa có danh mục phù hợp.</div>
<?php else: ?>
    <?php if (! $__env->hasRenderedOnce('02b0397c-0ad9-4baa-a7d1-62206947f57f')): $__env->markAsRenderedOnce('02b0397c-0ad9-4baa-a7d1-62206947f57f'); ?>
        <style>
            .inspection-quantity-control {
                display: inline-grid;
                grid-template-columns: 30px minmax(44px, 54px) 30px;
                align-items: stretch;
                width: 100%;
                max-width: 118px;
            }
            .inspection-quantity-control .inspection-quantity-step {
                min-width: 0;
                padding: .25rem;
                font-weight: 700;
                line-height: 1;
            }
            .inspection-quantity-control .inspection-service-quantity,
            .inspection-quantity-control .recheck-quantity {
                min-width: 0;
                padding-left: .25rem;
                padding-right: .25rem;
                text-align: center;
                border-radius: 0;
            }
            .inspection-service-quantity::-webkit-outer-spin-button,
            .inspection-service-quantity::-webkit-inner-spin-button,
            .recheck-quantity::-webkit-outer-spin-button,
            .recheck-quantity::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            .inspection-service-quantity,
            .recheck-quantity {
                -moz-appearance: textfield;
                appearance: textfield;
            }
            @media (max-width: 767.98px) {
                .inspection-service-table th:nth-child(3),
                .inspection-service-table td:nth-child(3) {
                    min-width: 125px;
                }
                .inspection-service-table th:nth-child(4),
                .inspection-service-table td:nth-child(4) {
                    width: 112px !important;
                    min-width: 112px;
                }
                .inspection-quantity-control {
                    grid-template-columns: 28px minmax(40px, 48px) 28px;
                    max-width: 104px;
                }
            }
        </style>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0 inspection-service-table">
            <thead class="table-light"><tr><th style="width:60px">Chọn</th><th>Hạng mục</th><th>Đơn giá</th><th style="width:128px">Số lượng</th><th>Tạm tính</th></tr></thead>
            <tbody>
                <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $oldItem = $itemMap[$service->id] ?? null;
                        $checked = (bool) $oldItem;
                        $quantity = old($quantityName . '.' . $service->id, $oldItem?->quantity ?? 1);
                        $checkboxOldKey = str_replace('[]', '', $checkboxName);
                        $oldSelected = old($checkboxOldKey, null);
                        if (is_array($oldSelected)) {
                            $checked = in_array((string) $service->id, array_map('strval', $oldSelected), true);
                        }
                    ?>
                    <tr>
                        <td><input type="checkbox" name="<?php echo e($checkboxName); ?>" value="<?php echo e($service->id); ?>" class="form-check-input <?php echo e($checkboxClass); ?>" <?php if($checked): echo 'checked'; endif; ?>></td>
                        <td><strong><?php echo e($service->name); ?></strong><?php if($service->description): ?><div class="small text-muted"><?php echo e($service->description); ?></div><?php endif; ?></td>
                        <td><?php echo e(number_format((float) $service->price, 0, ',', '.')); ?>đ / <?php echo e($service->unit ?: 'lần'); ?></td>
                        <td>
                            <div class="inspection-quantity-control" data-inspection-quantity-control>
                                <button type="button" class="btn btn-outline-secondary btn-sm inspection-quantity-step" data-step="-1" aria-label="Giảm số lượng">−</button>
                                <input type="number" min="1" max="999" inputmode="numeric" name="<?php echo e($quantityName); ?>[<?php echo e($service->id); ?>]" value="<?php echo e($quantity); ?>" class="form-control form-control-sm inspection-service-quantity" data-price="<?php echo e((float) $service->price); ?>" <?php echo e($checked ? '' : 'disabled'); ?>>
                                <button type="button" class="btn btn-outline-secondary btn-sm inspection-quantity-step" data-step="1" aria-label="Tăng số lượng">+</button>
                            </div>
                        </td>
                        <td class="fw-semibold inspection-service-total"><?php echo e($checked ? number_format((float) $service->price * max(1, (int) $quantity), 0, ',', '.') . 'đ' : '0đ'); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/floor-inspections/partials/service-table.blade.php ENDPATH**/ ?>