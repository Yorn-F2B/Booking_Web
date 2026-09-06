<?php
    $stayingGuests = $booking->guests;
    $roomCountForRepresentatives = max(1, $booking->bookingRooms->count());
    $needsGroupRepresentative = $roomCountForRepresentatives > 1;
    $groupRepresentative = $stayingGuests->firstWhere('is_booking_representative', true);
    $roomsMissingRepresentative = $booking->bookingRooms->filter(function ($bookingRoom) use ($stayingGuests) {
        return !$stayingGuests->where('booking_room_id', $bookingRoom->id)
            ->contains(fn ($guest) => $guest->guest_type === 'adult');
    });
    $representativeReady = $roomsMissingRepresentative->isEmpty()
        && (!$needsGroupRepresentative || $stayingGuests->where('is_booking_representative', true)->count() === 1);
    $canEditStayGuests = in_array($booking->status, ['pending', 'confirmed', 'checked_in'], true);
?>

<details class="compact-panel mb-3" id="stayingGuestsPanel" <?php if($errors->any() || !$representativeReady): ?> open <?php endif; ?>>
    <summary>
        <span>Bước 3 · Người đại diện từng phòng</span>
        <span class="badge-clean <?php echo e($representativeReady ? 'status-done' : 'status-warning'); ?>">
            <?php echo e($stayingGuests->where('guest_type', 'adult')->count()); ?>/<?php echo e($roomCountForRepresentatives); ?> phòng có đại diện
            <?php if($needsGroupRepresentative): ?> · <?php echo e($groupRepresentative ? 'đã có đại diện đoàn' : 'thiếu đại diện đoàn'); ?> <?php endif; ?>
        </span>
    </summary>

    <div class="compact-panel-body">
        <div class="alert alert-info small py-2">
            <strong>Chỉ khai người đại diện.</strong> Mỗi phòng cần 1 người lớn đại diện. Không nhập CCCD/thông tin của toàn bộ khách.
            <?php if($needsGroupRepresentative): ?>
                Trong các đại diện phòng, chọn thêm đúng 1 người làm <strong>đại diện cả đoàn</strong>.
            <?php endif; ?>
            Số người thực tế được quản lý riêng ở Bước 1.
        </div>

        <?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $roomRepresentative = $stayingGuests->where('booking_room_id', $bookingRoom->id)
                    ->first(fn ($guest) => $guest->guest_type === 'adult');
                $roomCategory = $bookingRoom->room?->category;
                $roomActualTotal = (int) $bookingRoom->adult_count + (int) $bookingRoom->child_count + (int) ($bookingRoom->baby_count ?? 0);
            ?>
            <div class="border rounded mb-3 overflow-hidden">
                <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div>
                        <strong>Phòng <?php echo e($bookingRoom->room?->room_number ?? '---'); ?></strong>
                        <span class="text-muted small">· <?php echo e($roomCategory?->name ?? 'Chưa rõ hạng'); ?> · <?php echo e($roomActualTotal); ?> khách thực tế</span>
                    </div>
                    <?php if($roomRepresentative): ?>
                        <span class="badge text-bg-success">Đã có đại diện</span>
                    <?php else: ?>
                        <span class="badge text-bg-warning">Chưa có đại diện</span>
                    <?php endif; ?>
                </div>

                <div class="p-3 border-top">
                    <?php if($roomRepresentative): ?>
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                            <div>
                                <div class="fw-bold"><?php echo e($roomRepresentative->full_name); ?></div>
                                <div class="small text-muted">
                                    <?php echo e($roomRepresentative->display_document ?: 'Chưa xuất trình giấy tờ'); ?>

                                    <?php if($roomRepresentative->is_booking_representative): ?>
                                        · <span class="text-primary fw-semibold">Đại diện cả đoàn</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if($canEditStayGuests): ?>
                            <details class="border rounded">
                                <summary class="px-3 py-2 small fw-semibold" style="cursor:pointer">Sửa hồ sơ đại diện</summary>
                                <div class="p-3 border-top">
                                    <form method="POST" action="<?php echo e(route('admin.bookings.guests.update', [$booking, $roomRepresentative])); ?>" data-staying-guest-submit>
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <?php echo $__env->make('admin.pages.bookings.partials.staying-guest-fields', ['editingGuest' => $roomRepresentative, 'defaultBookingRoomId' => $bookingRoom->id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                        <button class="btn btn-primary btn-sm mt-3" type="submit">Lưu hồ sơ đại diện</button>
                                    </form>
                                </div>
                            </details>
                        <?php endif; ?>
                    <?php elseif($canEditStayGuests): ?>
                        <form method="POST" action="<?php echo e(route('admin.bookings.guests.store', $booking)); ?>" data-staying-guest-submit>
                            <?php echo csrf_field(); ?>
                            <?php echo $__env->make('admin.pages.bookings.partials.staying-guest-fields', ['editingGuest' => null, 'defaultBookingRoomId' => $bookingRoom->id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <button class="btn btn-primary btn-sm mt-3" type="submit">Lưu người đại diện phòng <?php echo e($bookingRoom->room?->room_number ?? '---'); ?></button>
                        </form>
                    <?php else: ?>
                        <div class="small text-muted">Booking đã kết thúc nên hồ sơ đại diện bị khóa.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</details>

<div id="noDocumentRiskPanel" class="no-document-risk-backdrop d-none" role="dialog" aria-modal="true" aria-labelledby="noDocumentRiskTitle">
    <div class="no-document-risk-card">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <div class="text-uppercase small fw-bold text-warning mb-1">Cảnh báo lưu trú</div>
                <h5 class="mb-1" id="noDocumentRiskTitle">Người đại diện chưa xuất trình giấy tờ</h5>
                <div class="text-muted small">Phải xác nhận trước khi lưu hồ sơ.</div>
            </div>
            <button type="button" class="btn-close" data-no-document-cancel aria-label="Đóng"></button>
        </div>
        <div class="alert alert-warning py-2 small">
            <strong>Người chưa có giấy tờ hợp lệ:</strong>
            <ul class="mb-0 mt-1" id="noDocumentRiskGuestList"></ul>
        </div>
        <div class="border rounded p-3 bg-light small mb-3">
            Khách xác nhận thông tin đã khai là đúng và sẽ bổ sung giấy tờ khi được yêu cầu. Xác nhận này chỉ là dấu vết vận hành, không thay thế nghĩa vụ khai báo lưu trú theo quy định.
        </div>
        <div class="mb-3">
            <label for="noDocumentRiskReason" class="form-label fw-semibold">Lý do chưa xuất trình giấy tờ <span class="text-danger">*</span></label>
            <textarea id="noDocumentRiskReason" class="form-control" rows="2" maxlength="500"></textarea>
        </div>
        <div class="form-check border rounded p-3 ps-5 mb-3">
            <input class="form-check-input" type="checkbox" id="noDocumentRiskConfirm">
            <label class="form-check-label fw-semibold" for="noDocumentRiskConfirm">Tôi xác nhận đã thông báo và khách đồng ý tiếp tục làm thủ tục.</label>
        </div>
        <div class="small text-danger d-none mb-2" id="noDocumentRiskError"></div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" data-no-document-cancel>Quay lại</button>
            <button type="button" class="btn btn-warning" id="noDocumentRiskAccept">Xác nhận và lưu</button>
        </div>
    </div>
</div>

<?php if (! $__env->hasRenderedOnce('d5e6e818-83cc-4ce3-af16-8af3af00bd52')): $__env->markAsRenderedOnce('d5e6e818-83cc-4ce3-af16-8af3af00bd52'); ?>
<style>
.no-document-risk-backdrop { position: fixed; inset: 0; z-index: 1095; background: rgba(15,23,42,.58); display:flex; align-items:center; justify-content:center; padding:20px; }
.no-document-risk-backdrop.d-none { display:none !important; }
.no-document-risk-card { width:min(680px,100%); max-height:calc(100vh - 40px); overflow-y:auto; background:#fff; border-radius:16px; padding:22px; box-shadow:0 24px 70px rgba(15,23,42,.28); }
body.no-document-risk-open { overflow:hidden; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const riskPanel = document.getElementById('noDocumentRiskPanel');
    const riskGuestList = document.getElementById('noDocumentRiskGuestList');
    const riskReason = document.getElementById('noDocumentRiskReason');
    const riskConfirm = document.getElementById('noDocumentRiskConfirm');
    const riskError = document.getElementById('noDocumentRiskError');
    const riskAccept = document.getElementById('noDocumentRiskAccept');
    let pendingForm = null;
    let pendingRow = null;

    const hasValidDocument = (row) => {
        const type = row?.querySelector('.js-document-type')?.value || 'none';
        const number = row?.querySelector('.js-document-number')?.value?.trim() || '';
        return type !== 'none' && number !== '';
    };
    const closeRisk = () => {
        riskPanel?.classList.add('d-none');
        document.body.classList.remove('no-document-risk-open');
        pendingForm = null;
        pendingRow = null;
    };
    document.querySelectorAll('[data-no-document-cancel]').forEach(btn => btn.addEventListener('click', closeRisk));
    riskPanel?.addEventListener('click', e => { if (e.target === riskPanel) closeRisk(); });

    document.querySelectorAll('[data-staying-guest-submit]').forEach(form => {
        form.addEventListener('submit', event => {
            const row = form.querySelector('[data-guest-form]');
            const name = row?.querySelector('[name="full_name"]')?.value?.trim() || '';
            const ack = row?.querySelector('[data-no-document-ack]')?.value === '1';
            const reason = row?.querySelector('[data-no-document-reason]')?.value?.trim() || '';
            if (name && !hasValidDocument(row) && (!ack || !reason)) {
                event.preventDefault();
                pendingForm = form;
                pendingRow = row;
                if (riskGuestList) riskGuestList.innerHTML = `<li>${name}</li>`;
                if (riskReason) riskReason.value = '';
                if (riskConfirm) riskConfirm.checked = false;
                riskError?.classList.add('d-none');
                riskPanel?.classList.remove('d-none');
                document.body.classList.add('no-document-risk-open');
                riskReason?.focus();
            }
        });
    });

    document.addEventListener('input', event => {
        if (!event.target.matches('.js-document-type, .js-document-number')) return;
        const row = event.target.closest('[data-guest-form]');
        if (row?.querySelector('[data-no-document-ack]')) row.querySelector('[data-no-document-ack]').value = '0';
        if (row?.querySelector('[data-no-document-reason]')) row.querySelector('[data-no-document-reason]').value = '';
    });

    riskAccept?.addEventListener('click', () => {
        const reason = riskReason?.value?.trim() || '';
        if (!reason || !riskConfirm?.checked) {
            if (riskError) {
                riskError.textContent = !reason ? 'Vui lòng ghi lý do.' : 'Phải tích xác nhận đã thông báo cho khách.';
                riskError.classList.remove('d-none');
            }
            return;
        }
        if (pendingRow?.querySelector('[data-no-document-ack]')) pendingRow.querySelector('[data-no-document-ack]').value = '1';
        if (pendingRow?.querySelector('[data-no-document-reason]')) pendingRow.querySelector('[data-no-document-reason]').value = reason;
        const form = pendingForm;
        closeRisk();
        form?.requestSubmit();
    });
});
</script>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/bookings/partials/staying-guests.blade.php ENDPATH**/ ?>