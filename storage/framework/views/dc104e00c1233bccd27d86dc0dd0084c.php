<?php $__env->startSection('title', 'Kiểm tra phòng'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $stageLabels = [
        'housekeeping_report' => 'Buồng phòng kiểm tra ban đầu',
        'guest_consultation' => 'Chờ lễ tân trao đổi với khách',
        'housekeeping_recheck' => 'Khách phản hồi - cần kiểm tra lại',
        'completed' => 'Đã hoàn tất',
    ];
    $stageClasses = [
        'housekeeping_report' => 'bg-warning text-dark',
        'guest_consultation' => 'bg-info text-dark',
        'housekeeping_recheck' => 'bg-danger',
        'completed' => 'bg-success',
    ];
    $customerName = trim(($roomInspection->booking->customer->last_name ?? '') . ' ' . ($roomInspection->booking->customer->first_name ?? ''));
    $oldDamageItemMap = $roomInspection->items->where('type', 'damage_fee')->keyBy('service_id');
    $oldRoomMinibarItemMap = $roomInspection->items->where('type', 'minibar')->keyBy('service_id');
    $isInitialStep = $roomInspection->workflow_stage === 'housekeeping_report' && in_array($roomInspection->status, ['pending', 'rejected']);
    $isRecheckStep = $roomInspection->workflow_stage === 'housekeeping_recheck' && $roomInspection->status === 'reported';
?>

<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> /
            <a href="<?php echo e(route('admin.floor-inspections.index')); ?>">Phòng cần kiểm tra</a> /
            Phòng <?php echo e($roomInspection->room->room_number ?? '---'); ?>

        </p>

        <div class="admin-page-head">
            <div>
                <h2>Kiểm tra phòng <?php echo e($roomInspection->room->room_number ?? '---'); ?></h2>
                <p>Ghi nhận minibar, đồ thất lạc và hư hại; các khoản khách phản hồi phải được kiểm tra lại riêng.</p>
            </div>
            <a href="<?php echo e(route('admin.floor-inspections.index')); ?>" class="btn btn-outline-secondary">Quay lại</a>
        </div>

<?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <strong>Vui lòng kiểm tra lại:</strong>
                <ul class="mb-0 mt-1"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="settings-section h-100">
                    <h5 class="fw-bold mb-3">Thông tin phiếu</h5>
                    <div class="mb-3">
                        <div class="text-muted small">Bước xử lý hiện tại</div>
                        <span class="badge <?php echo e($stageClasses[$roomInspection->workflow_stage] ?? 'bg-secondary'); ?>">
                            <?php echo e($stageLabels[$roomInspection->workflow_stage] ?? $roomInspection->workflow_stage); ?>

                        </span>
                    </div>
                    <div class="mb-3"><div class="text-muted small">Mã booking</div><strong><?php echo e($roomInspection->booking->booking_code ?? '---'); ?></strong></div>
                    <div class="mb-3"><div class="text-muted small">Khách hàng</div><strong><?php echo e($customerName ?: 'Chưa có tên'); ?></strong><div class="text-muted small"><?php echo e($roomInspection->booking->customer->phone ?? '---'); ?></div></div>
                    <div class="mb-3"><div class="text-muted small">Phòng</div><strong><?php echo e($roomInspection->room->room_number ?? '---'); ?></strong> · Tầng <?php echo e($roomInspection->room->floor_number ?? '---'); ?></div>
                    <div class="mb-3"><div class="text-muted small">Hạng phòng</div><strong><?php echo e($roomInspection->booking->roomCategory->name ?? '---'); ?></strong></div>
                    <div class="mb-3"><div class="text-muted small">Lần cập nhật gần nhất</div><?php echo e($roomInspection->last_revision_at?->format('d/m/Y H:i:s') ?? 'Chưa có'); ?></div>
                    <?php if($roomInspection->last_update_summary): ?>
                        <div class="alert alert-light border small mb-0"><?php echo e($roomInspection->last_update_summary); ?></div>
                    <?php endif; ?>

                </div>
            </div>

            <div class="col-lg-8">
                <?php if(!$isInitialStep && ($roomInspection->booking->status ?? null) === 'inspection_requested'): ?>
                    <details class="settings-section border border-warning mb-3 supplemental-inspection-panel" <?php if($errors->has('supplemental_damage_service_ids') || $errors->has('supplemental_minibar_service_ids') || $errors->has('supplemental_note')): ?> open <?php endif; ?>>
                        <summary class="p-3 fw-bold text-warning-emphasis" style="cursor:pointer">
                            <i class="bx bx-plus-circle me-1"></i> Vừa phát hiện thêm lỗi / minibar? Ghi nhận tại đây
                                <span class="badge bg-warning text-dark ms-2">Không ghi đè lịch sử cũ</span>
                            </summary>
                            <div class="p-3 pt-0">
                                <div class="alert alert-warning small">
                                    Chỉ dùng khi vừa phát hiện thêm minibar, mất đồ hoặc hư hại <strong>sau lần kiểm tra trước</strong> và booking chưa checkout. Mỗi khoản sẽ được tạo thành dòng mới, ghi người/thời điểm phát hiện và gửi lại lễ tân để xử lý.
                                </div>
                                <form action="<?php echo e(route('admin.floor-inspections.supplemental-report', $roomInspection->id)); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Minibar / đồ dùng mới phát hiện</label>
                                        <?php echo $__env->make('admin.pages.floor-inspections.partials.service-table', [
                                            'services' => $minibarServices,
                                            'itemMap' => collect(),
                                            'checkboxName' => 'supplemental_minibar_service_ids[]',
                                            'quantityName' => 'supplemental_minibar_quantities',
                                            'checkboxClass' => 'inspection-service-checkbox',
                                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Hư hại / mất tài sản mới phát hiện</label>
                                        <?php echo $__env->make('admin.pages.floor-inspections.partials.service-table', [
                                            'services' => $damageServices,
                                            'itemMap' => collect(),
                                            'checkboxName' => 'supplemental_damage_service_ids[]',
                                            'quantityName' => 'supplemental_damage_quantities',
                                            'checkboxClass' => 'inspection-service-checkbox',
                                        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Lý do / căn cứ phát hiện bổ sung <span class="text-danger">*</span></label>
                                        <textarea name="supplemental_note" rows="3" class="form-control" required placeholder="Ví dụ: phát hiện vết nứt sau khi dọn lớp ga; kiểm kê minibar lần hai thấy thiếu..."><?php echo e(old('supplemental_note')); ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Tạo khoản phát hiện bổ sung? Các khoản đã xử lý trước đó sẽ được giữ nguyên và checkout tiếp tục bị chặn cho đến khi khoản mới được giải quyết.')">
                                        Ghi nhận phát hiện bổ sung
                                    </button>
                                </form>
                            </div>
                    </details>
                <?php endif; ?>
                <div class="settings-section">
                    <?php if($isRecheckStep): ?>
                        <div class="alert alert-danger">
                            <strong>Khách đang phản hồi một số hạng mục.</strong><br>
                            Nhập lại số lượng thực tế.
                        </div>

                        <form action="<?php echo e(route('admin.floor-inspections.recheck', $roomInspection->id)); ?>" method="POST" id="recheckForm">
                            <?php echo csrf_field(); ?>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Hạng mục cần xác minh</th>
                                            <th class="text-center" style="width:135px">Buồng phòng đã báo</th>
                                            <th class="text-center" style="width:150px">Khách xác nhận</th>
                                            <th style="width:165px">Số lượng xác minh lại</th>
                                            <th style="width:175px">Thành tiền mới</th>
                                            <th style="min-width:280px">Ghi chú kiểm tra lại</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $roomInspection->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $reportedQuantity = max(0, (int) $item->quantity);
                                                $guestClaimedQuantity = $item->guest_claimed_quantity !== null
                                                    ? max(0, (int) $item->guest_claimed_quantity)
                                                    : $reportedQuantity;
                                                $verifiedQuantity = old('recheck_quantities.' . $item->id, $reportedQuantity);
                                                $isDisputed = $item->guest_response === 'disputed';
                                            ?>
                                            <tr class="recheck-row <?php echo e($isDisputed ? 'table-warning' : ''); ?>">
                                                <td>
                                                    <strong><?php echo e($item->name); ?></strong>
                                                    <div class="small text-muted">
                                                        Đơn giá: <?php echo e(number_format((float) $item->price, 0, ',', '.')); ?>đ / <?php echo e($item->unit ?: 'đơn vị'); ?>

                                                    </div>
                                                    <?php if($isDisputed): ?>
                                                        <span class="badge bg-danger mt-1">Khách đang phản hồi</span>
                                                        <?php if($item->guest_response_note): ?>
                                                            <div class="small text-danger mt-1"><?php echo e($item->guest_response_note); ?></div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-success mt-1">Khách đã đồng ý trước đó</span>
                                                        <div class="small text-muted mt-1">Vẫn có thể sửa nếu vừa phát hiện kết quả thực tế thay đổi.</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <strong class="fs-5"><?php echo e($reportedQuantity); ?></strong>
                                                    <div class="small text-muted"><?php echo e($item->unit ?: 'đơn vị'); ?></div>
                                                </td>
                                                <td class="text-center">
                                                    <strong class="fs-5 <?php echo e($isDisputed ? 'text-danger' : 'text-success'); ?>"><?php echo e($guestClaimedQuantity); ?></strong>
                                                    <div class="small text-muted"><?php echo e($item->unit ?: 'đơn vị'); ?></div>
                                                </td>
                                                <td>
                                                    <div class="inspection-quantity-control" data-inspection-quantity-control>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm inspection-quantity-step" data-step="-1" aria-label="Giảm số lượng">−</button>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            max="999"
                                                            inputmode="numeric"
                                                            class="form-control form-control-sm recheck-quantity"
                                                            id="recheckQty<?php echo e($item->id); ?>"
                                                            name="recheck_quantities[<?php echo e($item->id); ?>]"
                                                            value="<?php echo e($verifiedQuantity); ?>"
                                                            data-price="<?php echo e((float) $item->price); ?>"
                                                            data-guest-quantity="<?php echo e($guestClaimedQuantity); ?>"
                                                            data-original-quantity="<?php echo e($reportedQuantity); ?>"
                                                            data-was-disputed="<?php echo e($isDisputed ? '1' : '0'); ?>"
                                                            data-unit="<?php echo e($item->unit ?: 'đơn vị'); ?>"
                                                            data-total-id="recheckTotal<?php echo e($item->id); ?>"
                                                            data-compare-id="recheckCompare<?php echo e($item->id); ?>"
                                                            data-note-id="recheckNote<?php echo e($item->id); ?>"
                                                            required
                                                        >
                                                        <button type="button" class="btn btn-outline-secondary btn-sm inspection-quantity-step" data-step="1" aria-label="Tăng số lượng">+</button>
                                                    </div>
                                                    <div id="recheckCompare<?php echo e($item->id); ?>" class="small mt-2"></div>
                                                </td>
                                                <td>
                                                    <strong id="recheckTotal<?php echo e($item->id); ?>" class="recheck-line-total text-danger">0đ</strong>
                                                    <div class="small text-muted mt-1">Số lượng xác minh × đơn giá</div>
                                                </td>
                                                <td>
                                                    <textarea
                                                        class="form-control recheck-note"
                                                        id="recheckNote<?php echo e($item->id); ?>"
                                                        rows="3"
                                                        name="recheck_notes[<?php echo e($item->id); ?>]"
                                                        placeholder="Chỉ bắt buộc khi kết quả vẫn khác ý kiến khách hoặc bạn sửa một hạng mục đã thống nhất."
                                                    ><?php echo e(old('recheck_notes.' . $item->id, '')); ?></textarea>
                                                    <div class="small text-muted mt-1 recheck-note-hint">Khớp với khách thì có thể để trống.</div>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Gửi số lượng đã xác minh cho lễ tân trao đổi lại với khách?')">
                                Cập nhật số lượng và gửi lại lễ tân
                            </button>
                        </form>

                    <?php elseif($isInitialStep): ?>
                        <h5 class="fw-bold mb-3">Kết quả kiểm tra ban đầu</h5>
                        <div class="alert alert-info small">
                            Đây mới là danh sách <strong>dự kiến</strong>. Nếu có khoản phát sinh, lễ tân sẽ trao đổi với khách trước; khoản khách phản hồi sẽ quay lại buồng phòng kiểm tra lại.
                        </div>

                        <form action="<?php echo e(route('admin.floor-inspections.report', $roomInspection->id)); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Phòng có hư hại/mất đồ không?</label>
                                <select name="has_damage" id="hasDamage" class="form-select" required>
                                    <option value="0" <?php if(old('has_damage', $roomInspection->has_damage ? '1' : '0') === '0'): echo 'selected'; endif; ?>>Không</option>
                                    <option value="1" <?php if(old('has_damage', $roomInspection->has_damage ? '1' : '0') === '1'): echo 'selected'; endif; ?>>Có</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Minibar / đồ có sẵn khách đã dùng</label>
                                <?php echo $__env->make('admin.pages.floor-inspections.partials.service-table', [
                                    'services' => $minibarServices,
                                    'itemMap' => $oldRoomMinibarItemMap,
                                    'checkboxName' => 'room_minibar_service_ids[]',
                                    'quantityName' => 'room_minibar_quantities',
                                    'checkboxClass' => 'inspection-service-checkbox',
                                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>

                            <div class="mb-4" id="damageSection">
                                <label class="form-label fw-semibold">Hư hại / mất tài sản</label>
                                <?php echo $__env->make('admin.pages.floor-inspections.partials.service-table', [
                                    'services' => $damageServices,
                                    'itemMap' => $oldDamageItemMap,
                                    'checkboxName' => 'damage_service_ids[]',
                                    'quantityName' => 'damage_quantities',
                                    'checkboxClass' => 'inspection-service-checkbox',
                                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Ghi chú kiểm tra</label>
                                <textarea name="inspection_note" rows="3" class="form-control" placeholder="Mô tả vị trí, tình trạng và thông tin cần lưu ý"><?php echo e(old('inspection_note', $roomInspection->inspection_note)); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Gửi kết quả kiểm tra ban đầu? Các khoản phát sinh sẽ chuyển sang lễ tân trao đổi với khách.')">
                                Gửi kết quả kiểm tra
                            </button>
                        </form>
                    <?php else: ?>
                        <h5 class="fw-bold mb-3">Kết quả đã gửi</h5>
                        <?php if($roomInspection->workflow_stage === 'guest_consultation'): ?>
                            <div class="alert alert-info">Đang chờ lễ tân trao đổi từng khoản với khách. Buồng phòng chưa được sửa trong bước này.</div>
                        <?php elseif($roomInspection->workflow_stage === 'completed'): ?>
                            <div class="alert alert-success">Kết quả đã thống nhất và phiếu kiểm tra đã hoàn tất.</div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light"><tr><th>Hạng mục</th><th>Ban đầu</th><th>Ý kiến khách</th><th>Kiểm tra lại</th><th>Kết quả cuối</th></tr></thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $roomInspection->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($item->name); ?></strong>
                                                <div class="small text-muted"><?php echo e($item->type === 'minibar' ? 'Minibar/đồ dùng' : 'Hư hại/mất đồ'); ?></div>
                                                <?php if(($item->detection_source ?? 'initial') === 'supplemental'): ?>
                                                    <span class="badge bg-warning text-dark mt-1">Phát hiện bổ sung</span>
                                                    <div class="small text-muted mt-1">
                                                        <?php echo e($item->detector->name ?? 'Nhân viên buồng phòng'); ?>

                                                        · <?php echo e($item->detected_at?->format('d/m/Y H:i') ?? $item->created_at?->format('d/m/Y H:i')); ?>

                                                        <?php if($item->detection_version): ?> · Lần #<?php echo e($item->detection_version); ?> <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo e($item->quantity); ?> × <?php echo e(number_format((float) $item->price, 0, ',', '.')); ?>đ = <strong><?php echo e(number_format((float) ($item->original_total ?: $item->total), 0, ',', '.')); ?>đ</strong></td>
                                            <td>
                                                <?php if($item->guest_response === 'accepted'): ?><span class="badge bg-success">Khách đồng ý</span>
                                                <?php elseif($item->guest_response === 'disputed'): ?><span class="badge bg-danger">Khách phản hồi</span><div class="small mt-1"><?php echo e($item->guest_response_note); ?></div>
                                                <?php else: ?> <span class="text-muted">Chưa trao đổi</span><?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($item->recheck_decision === 'remove_charge'): ?><span class="badge bg-success">Đề nghị bỏ phí</span>
                                                <?php elseif($item->recheck_decision === 'keep_charge'): ?><span class="badge bg-warning text-dark">Đã cập nhật số lượng</span>
                                                <?php elseif($item->recheck_decision === 'pending'): ?><span class="badge bg-danger">Đang chờ kiểm tra lại</span>
                                                <?php else: ?> <span class="text-muted">Không cần</span><?php endif; ?>
                                                <?php if($item->recheck_note): ?><div class="small mt-1"><?php echo e($item->recheck_note); ?></div><?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($item->status === 'approved'): ?><span class="badge bg-success">Đã chốt <?php echo e(number_format((float) $item->total, 0, ',', '.')); ?>đ</span>
                                                <?php elseif($item->status === 'rejected'): ?><span class="badge bg-secondary">Không tính phí</span>
                                                <?php else: ?> <span class="text-muted">Đang đối chiếu</span><?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr><td colspan="5" class="text-center text-muted">Không có khoản phát sinh.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
    <footer class="admin-footer"><span>MCuong Hotel Admin</span></footer>
</div>

<script>
document.querySelectorAll('[data-inspection-quantity-control] .inspection-quantity-step').forEach(function (button) {
    button.addEventListener('click', function () {
        const control = button.closest('[data-inspection-quantity-control]');
        const input = control ? control.querySelector('input[type="number"]') : null;
        if (!input || input.disabled) return;

        const min = Number.isFinite(Number(input.min)) && input.min !== '' ? Number(input.min) : 0;
        const max = Number.isFinite(Number(input.max)) && input.max !== '' ? Number(input.max) : 999;
        const step = Number(button.dataset.step || 0);
        const current = Number.isFinite(Number(input.value)) ? Number(input.value) : min;
        input.value = Math.min(max, Math.max(min, current + step));
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
});

document.querySelectorAll('.inspection-service-checkbox').forEach(function (checkbox) {
    const row = checkbox.closest('tr');
    const quantity = row ? row.querySelector('.inspection-service-quantity') : null;
    const lineTotal = row ? row.querySelector('.inspection-service-total') : null;
    const update = function () {
        if (!quantity) return;
        quantity.disabled = !checkbox.checked;
        const control = quantity.closest('[data-inspection-quantity-control]');
        if (control) {
            control.querySelectorAll('.inspection-quantity-step').forEach(function (button) {
                button.disabled = !checkbox.checked;
            });
        }
        const price = Number(quantity.dataset.price || 0);
        const qty = Math.max(1, Math.min(999, Number(quantity.value || 1)));
        quantity.value = qty;
        if (lineTotal) lineTotal.textContent = checkbox.checked ? new Intl.NumberFormat('vi-VN').format(price * qty) + 'đ' : '0đ';
    };
    checkbox.addEventListener('change', update);
    if (quantity) quantity.addEventListener('input', update);
    update();
});

document.querySelectorAll('.recheck-quantity').forEach(function (quantity) {
    const total = document.getElementById(quantity.dataset.totalId);
    const compare = document.getElementById(quantity.dataset.compareId);
    const row = quantity.closest('tr');
    const note = document.getElementById(quantity.dataset.noteId);

    const update = function () {
        const price = Number(quantity.dataset.price || 0);
        const guestQuantity = Math.max(0, Number(quantity.dataset.guestQuantity || 0));
        const originalQuantity = Math.max(0, Number(quantity.dataset.originalQuantity || 0));
        const wasDisputed = quantity.dataset.wasDisputed === '1';
        const qty = Math.max(0, Number(quantity.value || 0));

        if (total) {
            total.textContent = new Intl.NumberFormat('vi-VN').format(price * qty) + 'đ';
            total.classList.toggle('text-success', qty === 0);
            total.classList.toggle('text-danger', qty > 0);
        }

        if (compare) {
            compare.className = 'small mt-2 fw-semibold';
            if (qty === guestQuantity) {
                compare.textContent = 'Khớp số lượng khách xác nhận';
                compare.classList.add('text-success');
            } else if (qty > guestQuantity) {
                compare.textContent = 'Cao hơn ý kiến khách ' + (qty - guestQuantity) + ' ' + (quantity.dataset.unit || 'đơn vị');
                compare.classList.add('text-danger');
            } else {
                compare.textContent = 'Thấp hơn ý kiến khách ' + (guestQuantity - qty) + ' ' + (quantity.dataset.unit || 'đơn vị');
                compare.classList.add('text-primary');
            }
        }

        const needsReason = qty !== guestQuantity || (!wasDisputed && qty !== originalQuantity);
        if (note) {
            note.required = needsReason;
            note.classList.toggle('border-danger', needsReason && note.value.trim() === '');
            const hint = note.parentElement ? note.parentElement.querySelector('.recheck-note-hint') : null;
            if (hint) {
                hint.textContent = needsReason
                    ? 'Bắt buộc ghi căn cứ vì kết quả chưa khớp hoặc vừa thay đổi.'
                    : 'Đã khớp với khách, có thể để trống.';
                hint.className = 'small mt-1 recheck-note-hint ' + (needsReason ? 'text-danger' : 'text-success');
            }
        }

        if (row) row.classList.toggle('table-success', qty === guestQuantity);
    };

    quantity.addEventListener('input', update);
    update();
});

const hasDamage = document.getElementById('hasDamage');
const damageSection = document.getElementById('damageSection');
if (hasDamage && damageSection) {
    const updateDamage = () => { damageSection.style.display = hasDamage.value === '1' ? '' : 'none'; };
    hasDamage.addEventListener('change', updateDamage);
    updateDamage();
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/floor-inspections/show.blade.php ENDPATH**/ ?>