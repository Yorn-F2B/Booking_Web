<?php
    $stayingGuests = $booking->guests;
    $declaredAdults = $stayingGuests->where('guest_type', 'adult')->count();
    $declaredChildren = $stayingGuests->where('guest_type', 'child')->count();
    $declaredInfants = $stayingGuests->where('guest_type', 'infant')->count();
    $declaredTotal = $stayingGuests->count();
    $expectedTotal = (int) $booking->adult_count + (int) $booking->child_count + (int) ($booking->baby_count ?? 0);
    $adultGuests = $stayingGuests->where('guest_type', 'adult');
    $procedureGuestAlreadyDeclared = $stayingGuests->contains(function ($guest) use ($booking) {
        $bookingDocument = trim((string) $booking->booked_customer_cccd);
        $guestDocument = trim((string) ($guest->document_number ?: $guest->cccd));
        if ($bookingDocument !== '' && $guestDocument !== '') {
            return $bookingDocument === $guestDocument;
        }

        return mb_strtolower(trim((string) $guest->full_name)) === mb_strtolower(trim((string) $booking->booked_customer_name));
    });
    $canEditStayGuests = in_array($booking->status, ['confirmed', 'checked_in'], true);
    $guestTypeLabels = ['adult' => 'Người lớn', 'child' => 'Trẻ em', 'infant' => 'Em bé'];
    $documentTypeLabels = [
        'cccd' => 'CCCD',
        'passport' => 'Hộ chiếu',
        'birth_certificate' => 'Giấy khai sinh',
        'personal_id' => 'Mã định danh',
        'other' => 'Giấy tờ khác',
        'none' => 'Chưa xuất trình giấy tờ',
    ];
?>

<details class="compact-panel mb-3" id="stayingGuestsPanel" <?php if($errors->any()): ?> open <?php endif; ?>>
    <summary>
        <span>Khai báo toàn bộ khách lưu trú</span>
        <span class="badge-clean <?php echo e($declaredTotal >= $expectedTotal ? 'status-done' : 'status-warning'); ?>">
            <?php echo e($declaredTotal); ?> khách đã khai · <?php echo e($declaredAdults); ?> NL / <?php echo e($declaredChildren); ?> TE / <?php echo e($declaredInfants); ?> EB
        </span>
    </summary>

    <div class="compact-panel-body">

        <?php if(session('capacity_warning')): ?>
            <div class="alert alert-danger py-2 small">
                <strong>Cảnh báo sức chứa:</strong> <?php echo e(session('capacity_warning')); ?>

            </div>
        <?php endif; ?>

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="border rounded p-2 h-100">
                    <div class="text-muted small">Dự kiến khi đặt</div>
                    <strong><?php echo e($booking->adult_count); ?> NL / <?php echo e($booking->child_count); ?> TE / <?php echo e($booking->baby_count ?? 0); ?> EB</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-2 h-100">
                    <div class="text-muted small">Đã khai báo</div>
                    <strong><?php echo e($declaredAdults); ?> NL / <?php echo e($declaredChildren); ?> TE / <?php echo e($declaredInfants); ?> EB</strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-2 h-100">
                    <div class="text-muted small">Người đại diện đoàn</div>
                    <strong><?php echo e($stayingGuests->firstWhere('is_booking_representative', true)?->full_name ?? 'Chưa chọn'); ?></strong>
                </div>
            </div>
        </div>

        <?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $roomGuests = $stayingGuests->where('booking_room_id', $bookingRoom->id);
                $roomAdults = $roomGuests->where('guest_type', 'adult')->count();
                $roomChildren = $roomGuests->whereIn('guest_type', ['child', 'infant'])->count();
                $roomCategory = $bookingRoom->room?->category;
                $adultCapacity = (int) ($roomCategory?->adult_capacity ?? 0);
                $childCapacity = (int) ($roomCategory?->child_capacity ?? 0);
                $adultOver = max(0, $roomAdults - $adultCapacity);
                $childOver = max(0, $roomChildren - $childCapacity);
                $isRoomOverCapacity = $adultOver > 0 || $childOver > 0;
            ?>
            <details class="border rounded mb-3 overflow-hidden <?php echo e($isRoomOverCapacity ? 'border-danger' : ''); ?>" <?php if($errors->any()): ?> open <?php endif; ?>>
                <summary class="px-3 py-2 bg-light" style="cursor:pointer">
                    <span class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>
                            <strong>Phòng <?php echo e($bookingRoom->room?->room_number ?? '---'); ?></strong>
                            <span class="text-muted small">· <?php echo e($roomCategory?->name ?? 'Chưa rõ hạng'); ?></span>
                        </span>
                        <span class="badge <?php echo e($isRoomOverCapacity ? 'text-bg-danger' : 'text-bg-light border'); ?>">
                            <?php echo e($roomGuests->count()); ?> khách · <?php echo e($roomAdults); ?>/<?php echo e($adultCapacity); ?> NL · <?php echo e($roomChildren); ?>/<?php echo e($childCapacity); ?> TE/EB
                        </span>
                    </span>
                </summary>

                <div class="p-3 border-top">
                    <?php if($isRoomOverCapacity): ?>
                        <div class="alert alert-danger py-2 small mb-2">
                            <strong>Phòng đang vượt sức chứa:</strong>
                            <?php if($adultOver > 0): ?> vượt <?php echo e($adultOver); ?> người lớn. <?php endif; ?>
                            <?php if($childOver > 0): ?> vượt <?php echo e($childOver); ?> trẻ em/em bé. <?php endif; ?>
                            Trước check-in phải thu phụ phí, thêm phòng, đổi hạng hoặc phân lại khách.
                        </div>
                    <?php endif; ?>
                    <?php $__empty_1 = true; $__currentLoopData = $roomGuests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $guestHasDocument = $guest->document_type !== 'none' && trim((string) $guest->document_number) !== '';
                        ?>
                        <details class="border rounded mb-2">
                            <summary class="px-3 py-2 d-flex justify-content-between align-items-center gap-2">
                                <span>
                                    <strong><?php echo e($guest->full_name); ?></strong>
                                    <span class="text-muted small">· <?php echo e($guestTypeLabels[$guest->guest_type] ?? $guest->guest_type); ?></span>
                                    <?php if($guest->is_booking_representative): ?>
                                        <span class="badge text-bg-primary ms-1">Đại diện đoàn</span>
                                    <?php endif; ?>
                                </span>
                                <span class="small text-muted text-end">
                                    <?php if($guestHasDocument): ?>
                                        <?php echo e($guest->display_document); ?>

                                    <?php else: ?>
                                        <span class="badge text-bg-warning">Chưa giấy tờ</span>
                                        <?php if($guest->document_exception_acknowledged): ?>
                                            <span class="d-block mt-1 text-success">
                                                Đã xác nhận rủi ro<?php echo e($guest->document_exception_acknowledged_at ? ' · ' . $guest->document_exception_acknowledged_at->format('d/m H:i') : ''); ?>

                                            </span>
                                        <?php else: ?>
                                            <span class="d-block mt-1 text-danger fw-semibold">Chưa có xác nhận rủi ro</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </span>
                            </summary>

                            <div class="p-3 border-top">
                                <?php if($canEditStayGuests): ?>
                                    <form method="POST" action="<?php echo e(route('admin.bookings.guests.update', [$booking, $guest])); ?>" data-staying-guest-submit>
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <?php echo $__env->make('admin.pages.bookings.partials.staying-guest-fields', ['editingGuest' => $guest, 'defaultBookingRoomId' => $bookingRoom->id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                        <div class="d-flex justify-content-between gap-2 mt-3">
                                            <button class="btn btn-primary btn-sm" type="submit">Lưu thay đổi</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="<?php echo e(route('admin.bookings.guests.destroy', [$booking, $guest])); ?>" class="mt-2" data-staying-guest-submit onsubmit="return confirm('Xóa khách này khỏi danh sách lưu trú?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Xóa khách</button>
                                    </form>
                                <?php else: ?>
                                    <div class="small text-muted">Hồ sơ đã khóa sau khi kỳ lưu trú kết thúc.</div>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-muted small">Chưa khai khách nào cho phòng này.</div>
                    <?php endif; ?>
                </div>
            </details>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php if($canEditStayGuests): ?>
            <details class="border rounded bg-light" id="batchGuestEntry" <?php if($errors->any()): ?> open <?php endif; ?>>
                <summary class="px-3 py-2 fw-semibold" style="cursor:pointer">
                    Thêm khách lưu trú
                    <span class="text-muted small ms-2">· Mở khi cần khai báo thêm</span>
                </summary>
                <div class="p-3 border-top">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3">
                    <h6 class="fw-bold mb-1">Khai báo khách lưu trú</h6>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="procedureGuestIsStaying"
                                   <?php if($procedureGuestAlreadyDeclared): echo 'checked'; endif; ?> <?php if($procedureGuestAlreadyDeclared): echo 'disabled'; endif; ?>>
                            <label class="form-check-label small fw-semibold" for="procedureGuestIsStaying">
                                Người làm thủ tục có lưu trú
                            </label>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="fillBookingRepresentativeGuest" disabled>
                            <?php echo e($procedureGuestAlreadyDeclared ? 'Đã có trong danh sách lưu trú' : 'Thêm người làm thủ tục vào danh sách'); ?>

                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addBatchGuestRow">+ Thêm người khác</button>
                    </div>
                </div>
                <form method="POST" action="<?php echo e(route('admin.bookings.guests.store', $booking)); ?>" data-staying-guest-submit id="batchStayingGuestsForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="batch_mode" value="1">
                    <div id="batchGuestRows">
                        <?php echo $__env->make('admin.pages.bookings.partials.staying-guest-batch-row', ['index' => 0], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                    <div class="d-flex gap-2 flex-wrap justify-content-between align-items-center mt-2">
                        <button type="button" class="btn btn-outline-primary" id="addBatchGuestRowBottom">
                            <i class="bx bx-user-plus me-1"></i> Thêm người khác
                        </button>
                        <button type="submit" class="btn btn-primary">Xác nhận thêm toàn bộ khách</button>
                    </div>
                </form>
                <template id="batchGuestRowTemplate">
                    <?php echo $__env->make('admin.pages.bookings.partials.staying-guest-batch-row', ['index' => '__INDEX__'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </template>
                </div>
            </details>
        <?php endif; ?>
    </div>
</details>

<div id="noDocumentRiskPanel" class="no-document-risk-backdrop d-none" role="dialog" aria-modal="true" aria-labelledby="noDocumentRiskTitle">
    <div class="no-document-risk-card">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <div class="text-uppercase small fw-bold text-warning mb-1">Cảnh báo lưu trú</div>
                <h5 class="mb-1" id="noDocumentRiskTitle">Khách chưa xuất trình giấy tờ</h5>
                <div class="text-muted small">Phải xác nhận trước khi đưa khách vào phòng.</div>
            </div>
            <button type="button" class="btn-close" data-no-document-cancel aria-label="Đóng"></button>
        </div>

        <div class="alert alert-warning py-2 small">
            <strong>Các khách chưa có giấy tờ hợp lệ:</strong>
            <ul class="mb-0 mt-1" id="noDocumentRiskGuestList"></ul>
        </div>

        <div class="border rounded p-3 bg-light small mb-3">
            <strong>Nội dung phải thông báo cho khách:</strong>
            <div class="mt-1">
                Khách xác nhận thông tin đã khai là đúng, cam kết bổ sung giấy tờ khi được yêu cầu và chịu trách nhiệm
                về thông tin/hậu quả phát sinh do chưa xuất trình giấy tờ. Khách sạn có quyền yêu cầu bổ sung giấy tờ,
                từ chối tiếp nhận hoặc xử lý theo quy định lưu trú hiện hành.
            </div>
            <div class="mt-2 text-muted">
                Xác nhận này là dấu vết vận hành, không thay thế nghĩa vụ tuân thủ pháp luật và khai báo lưu trú của khách sạn.
            </div>
        </div>

        <div class="mb-3">
            <label for="noDocumentRiskReason" class="form-label fw-semibold">Lý do chưa xuất trình giấy tờ <span class="text-danger">*</span></label>
            <textarea id="noDocumentRiskReason" class="form-control" rows="2" maxlength="500"
                placeholder="Ví dụ: để quên giấy tờ, thất lạc, chưa mang theo, trẻ nhỏ chưa có giấy tờ..."></textarea>
        </div>

        <div class="form-check border rounded p-3 ps-5 mb-3">
            <input class="form-check-input" type="checkbox" id="noDocumentRiskConfirm">
            <label class="form-check-label fw-semibold" for="noDocumentRiskConfirm">
                Tôi xác nhận đã thông báo đầy đủ cho khách và khách đồng ý tiếp tục làm thủ tục trong tình trạng chưa xuất trình giấy tờ.
            </label>
        </div>

        <div class="small text-danger d-none mb-2" id="noDocumentRiskError"></div>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-outline-secondary" data-no-document-cancel>Quay lại bổ sung giấy tờ</button>
            <button type="button" class="btn btn-warning" id="noDocumentRiskAccept">Xác nhận và thêm vào phòng</button>
        </div>
    </div>
</div>

<?php if (! $__env->hasRenderedOnce('40a94f35-5bc1-43f0-9375-1b590b7733ad')): $__env->markAsRenderedOnce('40a94f35-5bc1-43f0-9375-1b590b7733ad'); ?>
<style>
.no-document-risk-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1095;
    background: rgba(15, 23, 42, .58);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.no-document-risk-backdrop.d-none { display: none !important; }
.no-document-risk-card {
    width: min(680px, 100%);
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    background: #fff;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
}
body.no-document-risk-open { overflow: hidden; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const storageKey = 'booking-staying-guests-scroll-<?php echo e($booking->id); ?>';
    const panel = document.getElementById('stayingGuestsPanel');
    const riskPanel = document.getElementById('noDocumentRiskPanel');
    const riskGuestList = document.getElementById('noDocumentRiskGuestList');
    const riskReason = document.getElementById('noDocumentRiskReason');
    const riskConfirm = document.getElementById('noDocumentRiskConfirm');
    const riskError = document.getElementById('noDocumentRiskError');
    const riskAccept = document.getElementById('noDocumentRiskAccept');
    let pendingRiskForm = null;
    let pendingRiskRows = [];

    const rowField = (row, suffix) => row?.querySelector(`[name="${suffix}"], [name$="[${suffix}]"]`);
    const rowHasValidDocument = (row) => {
        const type = rowField(row, 'document_type')?.value || 'none';
        const number = rowField(row, 'document_number')?.value?.trim() || '';
        return type !== 'none' && number !== '';
    };
    const formGuestRows = (form) => {
        if (form?.id === 'batchStayingGuestsForm') return Array.from(form.querySelectorAll('.js-batch-guest-row'));
        const row = form?.querySelector('[data-guest-form]');
        return row ? [row] : [];
    };
    const rowsRequiringDocumentRisk = (form) => formGuestRows(form).filter((row) => {
        const fullName = rowField(row, 'full_name')?.value?.trim() || '';
        if (!fullName || rowHasValidDocument(row)) return false;
        const acknowledged = row.querySelector('[data-no-document-ack]')?.value === '1';
        const reason = row.querySelector('[data-no-document-reason]')?.value?.trim() || '';
        return !acknowledged || !reason;
    });
    const closeRiskPanel = () => {
        riskPanel?.classList.add('d-none');
        document.body.classList.remove('no-document-risk-open');
        pendingRiskForm = null;
        pendingRiskRows = [];
    };
    const openRiskPanel = (form, noDocumentRows) => {
        pendingRiskForm = form;
        pendingRiskRows = noDocumentRows;
        if (riskGuestList) {
            riskGuestList.innerHTML = '';
            noDocumentRows.forEach((row) => {
                const name = rowField(row, 'full_name')?.value?.trim() || 'Khách chưa nhập tên';
                const roomSelect = rowField(row, 'booking_room_id');
                const roomLabel = roomSelect?.selectedOptions?.[0]?.textContent?.trim() || 'chưa chọn phòng';
                const item = document.createElement('li');
                item.textContent = `${name} · ${roomLabel}`;
                riskGuestList.appendChild(item);
            });
        }
        if (riskReason) riskReason.value = '';
        if (riskConfirm) riskConfirm.checked = false;
        riskError?.classList.add('d-none');
        riskPanel?.classList.remove('d-none');
        document.body.classList.add('no-document-risk-open');
        riskReason?.focus();
    };

    document.querySelectorAll('[data-no-document-cancel]').forEach((button) => button.addEventListener('click', closeRiskPanel));
    riskPanel?.addEventListener('click', (event) => { if (event.target === riskPanel) closeRiskPanel(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !riskPanel?.classList.contains('d-none')) closeRiskPanel(); });
    riskAccept?.addEventListener('click', () => {
        const reason = riskReason?.value?.trim() || '';
        if (!reason || !riskConfirm?.checked) {
            if (riskError) {
                riskError.textContent = !reason
                    ? 'Vui lòng ghi lý do khách chưa xuất trình giấy tờ.'
                    : 'Phải tích xác nhận đã thông báo và được khách đồng ý.';
                riskError.classList.remove('d-none');
            }
            return;
        }
        pendingRiskRows.forEach((row) => {
            const ack = row.querySelector('[data-no-document-ack]');
            const reasonField = row.querySelector('[data-no-document-reason]');
            if (ack) ack.value = '1';
            if (reasonField) reasonField.value = reason;
        });
        const form = pendingRiskForm;
        closeRiskPanel();
        form?.requestSubmit();
    });

    document.querySelectorAll('[data-staying-guest-submit]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const riskRows = rowsRequiringDocumentRisk(form);
            if (riskRows.length) {
                event.preventDefault();
                openRiskPanel(form, riskRows);
                return;
            }
            try {
                sessionStorage.setItem(storageKey, JSON.stringify({
                    y: window.scrollY,
                    open: panel?.open === true,
                    savedAt: Date.now()
                }));
            } catch (error) {}
        });
    });

    document.addEventListener('input', (event) => {
        if (!event.target.matches('.js-document-type, .js-document-number')) return;
        const row = event.target.closest('.js-batch-guest-row, [data-guest-form]');
        const ack = row?.querySelector('[data-no-document-ack]');
        const reason = row?.querySelector('[data-no-document-reason]');
        if (ack) ack.value = '0';
        if (reason) reason.value = '';
    });
    document.addEventListener('change', (event) => {
        if (!event.target.matches('.js-document-type, .js-document-number')) return;
        const row = event.target.closest('.js-batch-guest-row, [data-guest-form]');
        const ack = row?.querySelector('[data-no-document-ack]');
        const reason = row?.querySelector('[data-no-document-reason]');
        if (ack) ack.value = '0';
        if (reason) reason.value = '';
    });

    try {
        const raw = sessionStorage.getItem(storageKey);
        if (raw) {
            const saved = JSON.parse(raw);
            sessionStorage.removeItem(storageKey);
            if (saved && Date.now() - Number(saved.savedAt || 0) <= 120000) {
                if (panel && saved.open) panel.open = true;
                requestAnimationFrame(() => requestAnimationFrame(() => {
                    window.scrollTo({ top: Number(saved.y || 0), behavior: 'auto' });
                }));
            }
        }
    } catch (error) {}

    const rows = document.getElementById('batchGuestRows');
    const template = document.getElementById('batchGuestRowTemplate');
    const addButtons = [
        document.getElementById('addBatchGuestRow'),
        document.getElementById('addBatchGuestRowBottom')
    ].filter(Boolean);
    const fillRepresentativeButton = document.getElementById('fillBookingRepresentativeGuest');
    const procedureGuestIsStaying = document.getElementById('procedureGuestIsStaying');
    let nextIndex = rows ? rows.querySelectorAll('.js-batch-guest-row').length : 1;
    const serverToday = <?php echo json_encode(now('Asia/Ho_Chi_Minh')->toDateString(), 15, 512) ?>;

    const expectedGuestTypeFromBirthday = (raw) => {
        const match = String(raw || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        const todayMatch = String(serverToday).match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match || !todayMatch) return null;
        const [, y, m, d] = match.map(Number);
        const [, ty, tm, td] = todayMatch.map(Number);
        let age = ty - y;
        if (tm < m || (tm === m && td < d)) age--;
        if (age < 0 || age > 130) return null;
        return { age, type: age <= 5 ? 'infant' : (age <= 17 ? 'child' : 'adult') };
    };
    const guestTypeLabel = (type) => ({ adult: 'Người lớn', child: 'Trẻ em', infant: 'Em bé' }[type] || type);
    const syncBatchBirthday = (row) => {
        if (!row) return;
        const birthday = row.querySelector('.js-birthday-value');
        const type = row.querySelector('.js-guest-type');
        const message = row.querySelector('.js-age-message');
        const expected = expectedGuestTypeFromBirthday(birthday?.value);
        if (!expected) {
            if (message) {
                message.className = 'form-text js-age-message text-muted';
                message.textContent = birthday?.value ? 'Ngày sinh không hợp lệ.' : 'Tự xác định theo ngày sinh.';
            }
            return;
        }
        if (type) type.value = expected.type;
        if (message) {
            message.className = 'form-text js-age-message text-success';
            message.textContent = `${expected.age} tuổi · ${guestTypeLabel(expected.type)}.`;
        }
        syncBatchGuardians();
    };

    const renumber = () => rows?.querySelectorAll('.js-batch-number').forEach((el, i) => el.textContent = String(i + 1));
    const syncBatchGuardians = () => {
        if (!rows) return;
        const adultRows = Array.from(rows.querySelectorAll('.js-batch-guest-row')).filter((row) => row.querySelector('[name$="[guest_type]"]')?.value === 'adult');
        rows.querySelectorAll('.js-batch-guest-row').forEach((row) => {
            const type = row.querySelector('[name$="[guest_type]"]')?.value;
            const isMinor = type === 'child' || type === 'infant';
            row.querySelectorAll('.js-guardian-fields').forEach((field) => field.classList.toggle('d-none', !isMinor));
            const select = row.querySelector('.js-guardian-reference');
            if (!select) return;
            const selected = select.value;
            Array.from(select.querySelectorAll('option[data-batch-option]')).forEach((option) => option.remove());
            adultRows.forEach((adultRow) => {
                if (adultRow === row) return;
                const adultIndex = adultRow.dataset.index;
                const name = adultRow.querySelector('[name$="[full_name]"]')?.value?.trim() || `Khách người lớn ${Number(adultIndex) + 1}`;
                const option = document.createElement('option');
                option.value = `batch:${adultIndex}`;
                option.dataset.batchOption = '1';
                option.textContent = `${name} (trong biểu mẫu)`;
                select.appendChild(option);
            });
            if (Array.from(select.options).some((option) => option.value === selected)) select.value = selected;
        });
    };
    const setBirthday = (row, isoDate) => {
        if (!row || !isoDate) return;
        const input = row.querySelector('.js-birthday-value');
        if (!input) return;
        input.value = String(isoDate).slice(0, 10);
        if (input._flatpickr) input._flatpickr.setDate(input.value, false, 'Y-m-d');
    };
    const addRow = () => {
        if (!rows || !template) return null;
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        rows.insertAdjacentHTML('beforeend', html);
        renumber();
        const addedRow = rows.lastElementChild;
        const birthdayInput = addedRow?.querySelector('.js-birthday-value');
        if (birthdayInput && window.initializeProjectDatePicker) window.initializeProjectDatePicker(birthdayInput);
        syncBatchBirthday(addedRow);
        syncBatchGuardians();
        return addedRow;
    };
    addButtons.forEach((button) => button.addEventListener('click', () => {
        const row = addRow();
        row?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }));
    rows?.addEventListener('click', (event) => {
        const remove = event.target.closest('.js-remove-batch-guest');
        if (!remove) return;
        const all = rows.querySelectorAll('.js-batch-guest-row');
        if (all.length === 1) return;
        remove.closest('.js-batch-guest-row')?.remove();
        renumber();
        syncBatchGuardians();
    });
    rows?.addEventListener('change', (event) => {
        const changedRow = event.target.closest('.js-batch-guest-row');
        if (event.target.matches('.js-birthday-value, .js-guest-type')) syncBatchBirthday(changedRow);
        if (!event.target.classList.contains('js-representative-checkbox') || !event.target.checked) return;
        rows.querySelectorAll('.js-representative-checkbox').forEach((box) => { if (box !== event.target) box.checked = false; });
    });
    rows?.addEventListener('input', (event) => {
        const changedRow = event.target.closest('.js-batch-guest-row');
        if (event.target.matches('.js-birthday-value')) syncBatchBirthday(changedRow);
        if (event.target.matches('[name$="[full_name]"]')) syncBatchGuardians();
    });
    rows?.addEventListener('project-date-change', (event) => {
        if (event.target.matches('.js-birthday-value')) syncBatchBirthday(event.target.closest('.js-batch-guest-row'));
    });
    syncBatchGuardians();
    procedureGuestIsStaying?.addEventListener('change', () => {
        if (fillRepresentativeButton) fillRepresentativeButton.disabled = !procedureGuestIsStaying.checked;
        if (procedureGuestIsStaying.checked) fillRepresentativeButton?.click();
    });
    fillRepresentativeButton?.addEventListener('click', () => {
        if (procedureGuestIsStaying && !procedureGuestIsStaying.checked) return;
        const bookingDocument = String(<?php echo json_encode($booking->booked_customer_cccd, 15, 512) ?> || '').trim();
        let row = Array.from(rows?.querySelectorAll('.js-batch-guest-row') || []).find((candidate) => {
            const documentValue = candidate.querySelector('[name$="[document_number]"]')?.value?.trim() || '';
            return bookingDocument && documentValue === bookingDocument;
        });
        if (!row) {
            row = Array.from(rows?.querySelectorAll('.js-batch-guest-row') || []).find((candidate) => !candidate.querySelector('[name$="[full_name]"]')?.value?.trim());
        }
        if (!row) row = addRow();
        if (!row) return;
        const set = (suffix, value, overwrite = false) => {
            const input = row.querySelector(`[name$="[${suffix}]"]`);
            if (input && (overwrite || !input.value)) input.value = value || '';
        };
        set('full_name', <?php echo json_encode($booking->booked_customer_name, 15, 512) ?>);
        setBirthday(row, <?php echo json_encode(optional($booking->customer_birthday_snapshot)->format('Y-m-d'), 15, 512) ?>);
        set('gender', <?php echo json_encode($booking->customer_gender_snapshot, 15, 512) ?>);
        set('guest_type', 'adult', true);
        set('document_number', <?php echo json_encode($booking->booked_customer_cccd, 15, 512) ?>);
        set('address', <?php echo json_encode($booking->booked_customer_address, 15, 512) ?>);
        syncBatchBirthday(row);
        const representative = row.querySelector('.js-representative-checkbox');
        if (representative) {
            representative.checked = true;
            representative.dispatchEvent(new Event('change', { bubbles: true }));
        }
        syncBatchGuardians();
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    rows?.querySelectorAll('.js-birthday-value').forEach((input) => {
        window.initializeProjectDatePicker?.(input);
        syncBatchBirthday(input.closest('.js-batch-guest-row'));
    });
});
</script>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/bookings/partials/staying-guests.blade.php ENDPATH**/ ?>