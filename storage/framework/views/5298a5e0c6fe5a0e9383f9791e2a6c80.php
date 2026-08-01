<?php
    $stageLabels = [
        'housekeeping_report' => 'Buồng phòng đang kiểm tra',
        'guest_consultation' => 'Cần trao đổi với khách',
        'housekeeping_recheck' => 'Buồng phòng đang kiểm tra lại',
        'admin_approval' => 'Khách đã đồng ý · chờ admin xác nhận',
        'completed' => 'Đã hoàn tất',
    ];
    $stageClasses = [
        'housekeeping_report' => 'bg-secondary',
        'guest_consultation' => 'bg-primary',
        'housekeeping_recheck' => 'bg-warning text-dark',
        'admin_approval' => 'bg-info text-dark',
        'completed' => 'bg-success',
    ];
?>

<div class="mb-3">
    <div class="alert alert-warning">
        <strong>Chưa thể check-out.</strong> Mọi khoản minibar, mất đồ hoặc hư hại phải được khách xem lại. Nếu khách chưa đồng ý, buồng phòng kiểm tra lại và lễ tân tiếp tục trao đổi cho tới khi khách chấp nhận kết quả hiện tại; sau đó admin mới xác nhận cuối.
    </div>

    <?php $__currentLoopData = $booking->roomInspections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inspection): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $stage = $inspection->workflow_stage ?? 'housekeeping_report';
            $hasRecheckResult = $inspection->items->contains(fn ($item) => in_array($item->recheck_decision, ['keep_charge', 'remove_charge'], true));
        ?>
        <details class="compact-panel mb-3" <?php if($stage === 'guest_consultation'): ?> open <?php endif; ?>>
            <summary>
                <span>Phòng <?php echo e($inspection->room->room_number ?? '---'); ?></span>
                <span class="badge <?php echo e($stageClasses[$stage] ?? 'bg-secondary'); ?>"><?php echo e($stageLabels[$stage] ?? $stage); ?></span>
            </summary>
            <div class="compact-panel-body">
                <?php if($stage === 'guest_consultation'): ?>
                    <div class="alert alert-info small">
                        <?php if($hasRecheckResult): ?>
                            <strong>Buồng phòng vừa cập nhật kết quả kiểm tra lại.</strong> Hạng mục đã khớp với số khách xác nhận được khóa tự động. Lễ tân chỉ cần trao đổi lại các khoản còn lệch.
                        <?php else: ?>
                            Nói rõ từng khoản dự kiến với khách. Khoản khách chưa đồng ý phải ghi lý do cụ thể để buồng phòng kiểm tra lại.
                        <?php endif; ?>
                    </div>

                    <form action="<?php echo e(route('admin.bookings.inspections.guest-consultation', [$booking->id, $inspection->id])); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle guest-consultation-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Hạng mục</th>
                                        <th style="min-width:210px">Kết quả hiện tại</th>
                                        <th style="min-width:240px">Khách xác nhận</th>
                                        <th style="width:150px">Số lượng khách xác nhận</th>
                                        <th style="min-width:260px">Ghi chú nếu cần kiểm tra lại</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $inspection->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $oldResponse = old(
                                                'item_responses.' . $item->id,
                                                $item->guest_response === 'disputed' ? 'disputed' : 'accepted'
                                            );
                                            $oldClaimedQuantity = old(
                                                'item_claimed_quantities.' . $item->id,
                                                $item->guest_claimed_quantity !== null
                                                    ? (int) $item->guest_claimed_quantity
                                                    : (int) $item->quantity
                                            );
                                            $isLockedAccepted = $item->guest_response === 'accepted';
                                        ?>
                                        <tr class="<?php echo e((float) $item->total <= 0 ? 'table-success' : ''); ?>">
                                            <td>
                                                <strong><?php echo e($item->name); ?></strong>
                                                <div class="small text-muted"><?php echo e($item->type === 'minibar' ? 'Minibar / đồ dùng' : 'Hư hại / mất đồ'); ?></div>
                                                <?php if($item->guest_response_note && $item->guest_response !== 'accepted'): ?>
                                                    <div class="small text-danger mt-1"><strong>Khách đã phản hồi trước:</strong> <?php echo e($item->guest_response_note); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($item->recheck_decision === 'remove_charge'): ?>
                                                    <span class="badge bg-success mb-1">Buồng phòng xác minh số lượng bằng 0</span>
                                                <?php elseif($item->recheck_decision === 'keep_charge'): ?>
                                                    <span class="badge bg-warning text-dark mb-1">Kết quả buồng phòng xác minh lại</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary mb-1">Kết quả kiểm tra ban đầu</span>
                                                <?php endif; ?>
                                                <div>
                                                    <?php echo e((int) $item->quantity); ?> <?php echo e($item->unit ?: 'đơn vị'); ?>

                                                    × <?php echo e(number_format((float) $item->price, 0, ',', '.')); ?>đ
                                                    = <strong class="<?php echo e((float) $item->total > 0 ? 'text-danger' : 'text-success'); ?>"><?php echo e(number_format((float) $item->total, 0, ',', '.')); ?>đ</strong>
                                                </div>
                                                <?php if($item->recheck_note): ?>
                                                    <div class="small text-muted mt-1"><strong>Kết quả xác minh:</strong> <?php echo e($item->recheck_note); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($isLockedAccepted): ?>
                                                    <input type="hidden" name="item_responses[<?php echo e($item->id); ?>]" value="accepted">
                                                    <div class="alert alert-success py-2 px-3 mb-0 small">
                                                        <strong>Đã thống nhất với khách</strong><br>
                                                        Hạng mục này không cần phản hồi lại. Nếu thực tế thay đổi, buồng phòng phải cập nhật từ màn kiểm tra phòng.
                                                    </div>
                                                <?php else: ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input guest-response-radio" type="radio" name="item_responses[<?php echo e($item->id); ?>]" id="accept<?php echo e($item->id); ?>" value="accepted" data-note-id="guestNote<?php echo e($item->id); ?>" data-quantity-id="guestQty<?php echo e($item->id); ?>" data-current-quantity="<?php echo e((int) $item->quantity); ?>" <?php if($oldResponse === 'accepted'): echo 'checked'; endif; ?>>
                                                        <label class="form-check-label" for="accept<?php echo e($item->id); ?>">Khách đồng ý kết quả hiện tại</label>
                                                    </div>
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input guest-response-radio" type="radio" name="item_responses[<?php echo e($item->id); ?>]" id="dispute<?php echo e($item->id); ?>" value="disputed" data-note-id="guestNote<?php echo e($item->id); ?>" data-quantity-id="guestQty<?php echo e($item->id); ?>" data-current-quantity="<?php echo e((int) $item->quantity); ?>" <?php if($oldResponse === 'disputed'): echo 'checked'; endif; ?>>
                                                        <label class="form-check-label text-danger" for="dispute<?php echo e($item->id); ?>">Khách chưa đồng ý số lượng hiện tại</label>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="guest-quantity-cell">
                                                <?php if($isLockedAccepted): ?>
                                                    <input type="hidden" name="item_claimed_quantities[<?php echo e($item->id); ?>]" value="<?php echo e((int) $item->quantity); ?>">
                                                    <div class="text-center py-2">
                                                        <strong class="fs-5 text-success"><?php echo e((int) $item->quantity); ?></strong>
                                                        <div class="small text-muted"><?php echo e($item->unit ?: 'đơn vị'); ?></div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="guest-quantity-control" data-quantity-control>
                                                        <button type="button" class="btn btn-outline-secondary guest-quantity-step" data-step="-1" aria-label="Giảm số lượng">−</button>
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            max="999"
                                                            step="1"
                                                            inputmode="numeric"
                                                            class="form-control guest-claimed-quantity"
                                                            id="guestQty<?php echo e($item->id); ?>"
                                                            name="item_claimed_quantities[<?php echo e($item->id); ?>]"
                                                            value="<?php echo e($oldClaimedQuantity); ?>"
                                                            data-current-quantity="<?php echo e((int) $item->quantity); ?>"
                                                            aria-label="Số lượng khách xác nhận"
                                                        >
                                                        <button type="button" class="btn btn-outline-secondary guest-quantity-step" data-step="1" aria-label="Tăng số lượng">+</button>
                                                    </div>
                                                    <div class="small text-muted text-center mt-1"><?php echo e($item->unit ?: 'đơn vị'); ?></div>
                                                    <div class="small text-danger text-center mt-1 quantity-match-warning d-none">Số lượng đã trùng kết quả hiện tại; không cần kiểm tra lại.</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($isLockedAccepted): ?>
                                                    <div class="small text-muted py-2">Không cần nhập.</div>
                                                <?php else: ?>
                                                    <textarea class="form-control guest-response-note" id="guestNote<?php echo e($item->id); ?>" name="item_notes[<?php echo e($item->id); ?>]" rows="3" placeholder="Ví dụ: Khách nói chỉ vỡ 2 ly; đề nghị đếm lại."><?php echo e(old('item_notes.' . $item->id, $item->guest_response === 'disputed' ? $item->guest_response_note : '')); ?></textarea>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chú trao đổi chung</label>
                            <textarea name="guest_consultation_note" rows="2" class="form-control" placeholder="Ghi chú thêm nếu cần"><?php echo e(old('guest_consultation_note', $inspection->guest_consultation_note)); ?></textarea>
                        </div>
                        <button class="btn btn-primary w-100" type="submit" onclick="return confirm('Đã trao đổi rõ kết quả hiện tại với khách và ghi nhận đúng lựa chọn?')">
                            Gửi lựa chọn của khách
                        </button>
                    </form>
                <?php elseif($stage === 'housekeeping_recheck'): ?>
                    <div class="alert alert-warning mb-2">
                        <strong>Đang chờ buồng phòng xác minh lại.</strong> Sau khi có kết quả mới, phiếu sẽ tự quay lại đây để lễ tân trao đổi tiếp với khách.
                    </div>
                    <?php $__currentLoopData = $inspection->items->where('guest_response', 'disputed'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="border rounded p-2 mb-2">
                            <strong><?php echo e($item->name); ?></strong>
                            <div class="text-danger small">Khách xác nhận: <strong><?php echo e($item->guest_claimed_quantity ?? $item->quantity); ?> <?php echo e($item->unit ?: 'đơn vị'); ?></strong></div>
                            <?php if($item->guest_response_note): ?><div class="small text-muted">Ghi chú: <?php echo e($item->guest_response_note); ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php elseif($stage === 'admin_approval'): ?>
                    <div class="alert alert-info mb-2"><strong>Khách đã đồng ý toàn bộ kết quả hiện tại.</strong> Đang chờ admin xác nhận các khoản được cộng vào hóa đơn.</div>
                    <?php $__currentLoopData = $inspection->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="d-flex justify-content-between border-bottom py-2 gap-3">
                            <div>
                                <strong><?php echo e($item->name); ?></strong>
                                <div class="small text-muted">
                                    <?php echo e((int) $item->quantity); ?> <?php echo e($item->unit ?: 'đơn vị'); ?> × <?php echo e(number_format((float) $item->price, 0, ',', '.')); ?>đ
                                    <?php if($item->recheck_note): ?> · <?php echo e($item->recheck_note); ?> <?php endif; ?>
                                </div>
                            </div>
                            <strong class="<?php echo e((float) $item->total > 0 ? 'text-danger' : 'text-success'); ?>"><?php echo e(number_format((float) $item->total, 0, ',', '.')); ?>đ</strong>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php elseif($stage === 'completed'): ?>
                    <div class="alert alert-success mb-0">Admin đã xác nhận. Tổng phí kiểm tra phòng được duyệt: <strong><?php echo e(number_format((float)$inspection->approved_total,0,',','.')); ?>đ</strong>.</div>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0">Buồng phòng đang thực hiện kiểm tra ban đầu.</div>
                <?php endif; ?>
            </div>
        </details>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<style>
.guest-consultation-table {
    width: 100%;
    min-width: 0;
    table-layout: fixed;
}
.guest-consultation-table th,
.guest-consultation-table td {
    vertical-align: top;
    overflow-wrap: anywhere;
}
.guest-consultation-table th:nth-child(1){width:13%}
.guest-consultation-table th:nth-child(2){width:24%}
.guest-consultation-table th:nth-child(3){width:27%}
.guest-consultation-table th:nth-child(4){width:18%}
.guest-consultation-table th:nth-child(5){width:18%}
.guest-consultation-table .guest-quantity-cell {
    min-width: 0;
    width: auto;
}
.guest-quantity-control {
    display: grid;
    grid-template-columns: 38px minmax(56px, 1fr) 38px;
    gap: 5px;
    align-items: stretch;
    width: 100%;
    min-width: 0;
}
.guest-quantity-control .guest-claimed-quantity {
    min-width: 76px;
    height: 46px;
    padding: 6px 8px;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.2;
    text-align: center;
    color: #172033 !important;
    background-color: #fff !important;
    opacity: 1 !important;
    -webkit-text-fill-color: #172033;
}
.guest-quantity-control .guest-claimed-quantity[readonly] {
    color: #495057 !important;
    background-color: #f3f4f6 !important;
    -webkit-text-fill-color: #495057;
}
.guest-quantity-control .guest-quantity-step {
    height: 46px;
    padding: 0;
    font-size: 22px;
    font-weight: 700;
    line-height: 1;
}
@media (max-width: 1199.98px) {
    .guest-consultation-table { min-width: 920px; table-layout: auto; }
    .guest-consultation-table .guest-quantity-cell { min-width: 150px; }
}
</style>

<script>
(function () {
    function updateGuestResponseState(radio) {
        const noteId = radio.dataset.noteId;
        const quantityId = radio.dataset.quantityId;
        const note = document.getElementById(noteId);
        const quantity = document.getElementById(quantityId);
        const dispute = document.querySelector('input[data-note-id="' + noteId + '"][value="disputed"]');
        const accept = document.querySelector('input[data-note-id="' + noteId + '"][value="accepted"]');
        const isDisputed = dispute && dispute.checked;

        if (quantity) {
            quantity.required = isDisputed;
            quantity.readOnly = !isDisputed;
            quantity.classList.toggle('bg-light', !isDisputed);
            if (!isDisputed && accept) quantity.value = accept.dataset.currentQuantity || quantity.value;
        }

        if (note) {
            note.disabled = !isDisputed;
            note.required = isDisputed;
            note.placeholder = isDisputed
                ? 'Bắt buộc ghi ngắn gọn nội dung cần kiểm tra lại.'
                : 'Chỉ nhập khi khách chưa đồng ý.';
            if (!isDisputed) note.value = '';
        }

        if (quantity) {
            const warning = quantity.closest('.guest-quantity-cell')?.querySelector('.quantity-match-warning');
            const refreshWarning = function () {
                const currentQty = Number(quantity.dataset.currentQuantity || 0);
                const claimedQty = Number(quantity.value || 0);
                if (warning) warning.classList.toggle('d-none', !isDisputed || claimedQty !== currentQty);
            };
            quantity.oninput = refreshWarning;
            refreshWarning();
        }
    }
    document.querySelectorAll('.guest-response-radio').forEach(function (radio) {
        radio.addEventListener('change', function () { updateGuestResponseState(radio); });
        updateGuestResponseState(radio);
    });

    document.querySelectorAll('[data-quantity-control]').forEach(function (control) {
        const input = control.querySelector('.guest-claimed-quantity');
        if (!input) return;

        control.querySelectorAll('[data-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (input.readOnly || input.disabled) return;
                const min = Number(input.min || 0);
                const max = Number(input.max || 999);
                const current = Number.parseInt(input.value || '0', 10);
                const step = Number.parseInt(button.dataset.step || '0', 10);
                input.value = Math.min(max, Math.max(min, (Number.isNaN(current) ? 0 : current) + step));
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.focus();
            });
        });
    });
})();
</script>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\bookings\partials\inspection-guest-consultation.blade.php ENDPATH**/ ?>