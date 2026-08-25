<?php
    $editingGuest = $editingGuest ?? null;
    $fieldPrefix = $editingGuest ? 'guest_' . $editingGuest->id . '_' : 'new_guest_';
    $selectedType = old('guest_type', $editingGuest?->guest_type ?? 'adult');
    $selectedRoomId = old('booking_room_id', $editingGuest?->booking_room_id ?? $defaultBookingRoomId);
    $birthdayValue = old('birthday', $editingGuest?->birthday?->format('Y-m-d'));
    $birthdayDate = $birthdayValue ? \Carbon\Carbon::parse($birthdayValue) : null;
    $currentRepresentative = $booking->guests->firstWhere('is_booking_representative', true);
    $canChooseRepresentative = !$currentRepresentative || ($editingGuest && (int) $currentRepresentative->id === (int) $editingGuest->id);
    $representativeChecked = $canChooseRepresentative
        && (bool) old('is_booking_representative', $editingGuest?->is_booking_representative ?? $booking->guests->isEmpty());
    $editingHasValidDocument = $editingGuest
        && $editingGuest->document_type !== 'none'
        && trim((string) $editingGuest->document_number) !== '';
    $existingNoDocumentAck = $editingGuest
        && !$editingHasValidDocument
        && (bool) $editingGuest->document_exception_acknowledged;
?>

<div class="row g-2 staying-guest-form" data-guest-form data-editing-guest-id="<?php echo e($editingGuest?->id); ?>">
    <input type="hidden" name="no_document_acknowledged" value="<?php echo e($existingNoDocumentAck ? 1 : 0); ?>" data-no-document-ack>
    <input type="hidden" name="no_document_reason" value="<?php echo e($existingNoDocumentAck ? $editingGuest?->document_exception_reason : ''); ?>" data-no-document-reason>
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 flex-wrap border rounded p-2 bg-white">
            <input type="file" id="<?php echo e($fieldPrefix); ?>cccd_image" class="d-none js-cccd-image" accept="image/*"
                data-scan-side="ocr"
                data-button="#<?php echo e($fieldPrefix); ?>cccd_scan_button"
                data-status="#<?php echo e($fieldPrefix); ?>cccd_scan_status"
                data-target-cccd="#<?php echo e($fieldPrefix); ?>document_number"
                data-target-full-name="#<?php echo e($fieldPrefix); ?>full_name"
                data-target-birthday="#<?php echo e($fieldPrefix); ?>birthday"
                data-target-gender="#<?php echo e($fieldPrefix); ?>gender"
                data-target-nationality="#<?php echo e($fieldPrefix); ?>nationality"
                data-target-address="#<?php echo e($fieldPrefix); ?>address"
                data-required-fields="cccd,full_name,birthday,gender,nationality,address">
            <label for="<?php echo e($fieldPrefix); ?>cccd_image" id="<?php echo e($fieldPrefix); ?>cccd_scan_button" class="btn btn-outline-primary btn-sm mb-0">
                <i class="bx bx-image-add me-1"></i> Quét CCCD từ ảnh
            </label>
            <span id="<?php echo e($fieldPrefix); ?>cccd_scan_status" class="small text-muted"></span>
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label small">Họ và tên <span class="text-danger">*</span></label>
        <input type="text" name="full_name" id="<?php echo e($fieldPrefix); ?>full_name" class="form-control form-control-sm" value="<?php echo e(old('full_name', $editingGuest?->full_name)); ?>" required>
    </div>
    <div class="col-md-2">
        <label class="form-label small">Nhóm tuổi <span class="text-danger">*</span></label>
        <select name="guest_type" class="form-select form-select-sm" data-guest-type required>
            <option value="adult" <?php if($selectedType === 'adult'): echo 'selected'; endif; ?>>Người lớn (từ 18 tuổi)</option>
            <option value="child" <?php if($selectedType === 'child'): echo 'selected'; endif; ?>>Trẻ em (6–17 tuổi)</option>
            <option value="infant" <?php if($selectedType === 'infant'): echo 'selected'; endif; ?>>Em bé (0–5 tuổi)</option>
        </select>
        <div class="form-text" data-age-message>Tự xác định theo ngày sinh.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Phòng lưu trú <span class="text-danger">*</span></label>
        <select name="booking_room_id" class="form-select form-select-sm" data-booking-room required>
            <option value="">-- Chọn phòng --</option>
            <?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roomOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $roomGuestsForOption = $booking->guests->where('booking_room_id', $roomOption->id);
                    if ($editingGuest && (int) $editingGuest->booking_room_id === (int) $roomOption->id) {
                        $roomGuestsForOption = $roomGuestsForOption->where('id', '!=', $editingGuest->id);
                    }
                    $currentAdultsForOption = $roomGuestsForOption->where('guest_type', 'adult')->count();
                    $currentMinorsForOption = $roomGuestsForOption->whereIn('guest_type', ['child', 'infant'])->count();
                ?>
                <option value="<?php echo e($roomOption->id); ?>"
                    data-room-number="<?php echo e($roomOption->room?->room_number ?? '---'); ?>"
                    data-adult-capacity="<?php echo e((int) ($roomOption->room?->category?->adult_capacity ?? 0)); ?>"
                    data-child-capacity="<?php echo e((int) ($roomOption->room?->category?->child_capacity ?? 0)); ?>"
                    data-current-adults="<?php echo e($currentAdultsForOption); ?>"
                    data-current-minors="<?php echo e($currentMinorsForOption); ?>"
                    <?php if((string)$selectedRoomId === (string)$roomOption->id): echo 'selected'; endif; ?>>
                    Phòng <?php echo e($roomOption->room?->room_number ?? '---'); ?> · <?php echo e($roomOption->room?->category?->name ?? '---'); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label small">Ngày sinh <span class="text-danger">*</span></label>
        <input type="date" name="birthday" id="<?php echo e($fieldPrefix); ?>birthday"
            class="form-control form-control-sm" value="<?php echo e($birthdayValue); ?>"
            min="1900-01-01" max="<?php echo e(now('Asia/Ho_Chi_Minh')->toDateString()); ?>"
            data-birth-date data-birthday-input required>
    </div>

    <div class="col-12 d-none" data-form-alert></div>

    <div class="col-md-2">
        <label class="form-label small">Giới tính <span class="text-danger">*</span></label>
        <select name="gender" id="<?php echo e($fieldPrefix); ?>gender" class="form-select form-select-sm" required>
            <option value="">-- Chọn --</option>
            <option value="male" <?php if(old('gender', $editingGuest?->gender) === 'male'): echo 'selected'; endif; ?>>Nam</option>
            <option value="female" <?php if(old('gender', $editingGuest?->gender) === 'female'): echo 'selected'; endif; ?>>Nữ</option>
            <option value="other" <?php if(old('gender', $editingGuest?->gender) === 'other'): echo 'selected'; endif; ?>>Khác</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">Quốc tịch <span class="text-danger">*</span></label>
        <input type="text" name="nationality" id="<?php echo e($fieldPrefix); ?>nationality" class="form-control form-control-sm" value="<?php echo e(old('nationality', $editingGuest?->nationality ?? 'Việt Nam')); ?>" required>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Loại giấy tờ</label>
        <select name="document_type" id="<?php echo e($fieldPrefix); ?>document_type" class="form-select form-select-sm js-document-type" data-document-type>
            <option value="cccd" <?php if(old('document_type', $editingGuest?->document_type ?? 'cccd') === 'cccd'): echo 'selected'; endif; ?>>CCCD</option>
            <option value="passport" <?php if(old('document_type', $editingGuest?->document_type) === 'passport'): echo 'selected'; endif; ?>>Hộ chiếu</option>
            <option value="birth_certificate" <?php if(old('document_type', $editingGuest?->document_type) === 'birth_certificate'): echo 'selected'; endif; ?>>Giấy khai sinh</option>
            <option value="personal_id" <?php if(old('document_type', $editingGuest?->document_type) === 'personal_id'): echo 'selected'; endif; ?>>Mã định danh</option>
            <option value="other" <?php if(old('document_type', $editingGuest?->document_type) === 'other'): echo 'selected'; endif; ?>>Giấy tờ khác</option>
            <option value="none" <?php if(old('document_type', $editingGuest?->document_type) === 'none'): echo 'selected'; endif; ?>>Chưa xuất trình giấy tờ</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Số giấy tờ</label>
        <input type="text" name="document_number" id="<?php echo e($fieldPrefix); ?>document_number" class="form-control form-control-sm js-document-number" value="<?php echo e(old('document_number', $editingGuest?->document_number ?? $editingGuest?->cccd)); ?>" maxlength="50">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <?php if($canChooseRepresentative): ?>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_booking_representative" value="1" id="<?php echo e($fieldPrefix); ?>representative" <?php if($representativeChecked): echo 'checked'; endif; ?>>
                <label class="form-check-label small" for="<?php echo e($fieldPrefix); ?>representative">Đại diện đoàn</label>
            </div>
        <?php else: ?>
            <div class="small text-muted mb-2">
                <i class="bx bx-lock-alt"></i> Đại diện: <strong><?php echo e($currentRepresentative->full_name); ?></strong>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4" data-guardian-field>
        <label class="form-label small">Người giám hộ đi cùng</label>
        <select name="guardian_guest_id" class="form-select form-select-sm">
            <option value="">-- Chọn người lớn --</option>
            <?php $__currentLoopData = $booking->guests->where('guest_type', 'adult'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $adultGuest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($editingGuest && $adultGuest->id === $editingGuest->id) continue; ?>
                <option value="<?php echo e($adultGuest->id); ?>" <?php if((string)old('guardian_guest_id', $editingGuest?->guardian_guest_id) === (string)$adultGuest->id): echo 'selected'; endif; ?>>
                    <?php echo e($adultGuest->full_name); ?> · Phòng <?php echo e($adultGuest->bookingRoom?->room?->room_number ?? '---'); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
    <div class="col-md-3" data-guardian-field>
        <label class="form-label small">Quan hệ với trẻ</label>
        <input type="text" name="guardian_relationship" class="form-control form-control-sm" value="<?php echo e(old('guardian_relationship', $editingGuest?->guardian_relationship)); ?>" placeholder="Cha, mẹ, ông, bà...">
    </div>
    <div class="col-md-5">
        <label class="form-label small">Địa chỉ</label>
        <input type="text" name="address" id="<?php echo e($fieldPrefix); ?>address" class="form-control form-control-sm" value="<?php echo e(old('address', $editingGuest?->address)); ?>">
    </div>
    <div class="col-12">
        <label class="form-label small">Ghi chú</label>
        <input type="text" name="note" class="form-control form-control-sm" value="<?php echo e(old('note', $editingGuest?->note)); ?>" placeholder="Thông tin do người giám hộ cung cấp hoặc ghi chú vận hành">
    </div>
</div>

<?php if (! $__env->hasRenderedOnce('5ed013bc-0f0e-49e1-bf6f-0b0e92c5912e')): $__env->markAsRenderedOnce('5ed013bc-0f0e-49e1-bf6f-0b0e92c5912e'); ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-guest-form]').forEach((form) => {
        const typeSelect = form.querySelector('[data-guest-type]');
        const guardianFields = form.querySelectorAll('[data-guardian-field]');
        const documentType = form.querySelector('[data-document-type]');
        const roomSelect = form.querySelector('[data-booking-room]');
        const birthdayInput = form.querySelector('[data-birthday-input]');
        const ageMessage = form.querySelector('[data-age-message]');
        const formAlert = form.querySelector('[data-form-alert]');
        const submitButton = form.closest('form')?.querySelector('button[type="submit"]');

        const getExpectedType = (date) => {
            const today = new Date();
            let age = today.getFullYear() - date.getFullYear();
            const monthDiff = today.getMonth() - date.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < date.getDate())) age--;
            return { age, type: age <= 5 ? 'infant' : (age <= 17 ? 'child' : 'adult') };
        };

        const typeLabel = (type) => ({ adult: 'Người lớn', child: 'Trẻ em', infant: 'Em bé' }[type] || type);

        const syncBirthday = () => {
            const raw = birthdayInput?.value || '';
            if (!raw) {
                birthdayInput?.setCustomValidity('Vui lòng chọn ngày sinh.');
                if (ageMessage) ageMessage.textContent = '';
                syncCapacity();
                return;
            }

            const date = new Date(`${raw}T00:00:00`);
            if (Number.isNaN(date.getTime()) || date > new Date()) {
                birthdayInput.setCustomValidity('Ngày sinh không hợp lệ hoặc nằm trong tương lai.');
                if (ageMessage) {
                    ageMessage.className = 'form-text text-danger';
                    ageMessage.textContent = 'Ngày sinh không hợp lệ.';
                }
                return;
            }

            birthdayInput.setCustomValidity('');
            const expected = getExpectedType(date);
            typeSelect.value = expected.type;
            typeSelect.setCustomValidity('');
            if (ageMessage) {
                ageMessage.className = 'form-text text-success';
                ageMessage.textContent = `${expected.age} tuổi · ${typeLabel(expected.type)}.`;
            }
            syncTypeFields();
            syncCapacity();
        };

        const syncTypeFields = () => {
            const isMinor = typeSelect?.value === 'child' || typeSelect?.value === 'infant';
            guardianFields.forEach((field) => field.classList.toggle('d-none', !isMinor));
            if (!isMinor && documentType?.value === 'none') documentType.value = 'cccd';
        };

        const syncCapacity = () => {
            if (!formAlert || !roomSelect) return;
            const option = roomSelect.selectedOptions?.[0];
            if (!option || !option.value) {
                formAlert.classList.add('d-none');
                formAlert.innerHTML = '';
                return;
            }

            const adultCapacity = Number(option.dataset.adultCapacity || 0);
            const childCapacity = Number(option.dataset.childCapacity || 0);
            const adults = Number(option.dataset.currentAdults || 0) + (typeSelect.value === 'adult' ? 1 : 0);
            const minors = Number(option.dataset.currentMinors || 0) + (typeSelect.value !== 'adult' ? 1 : 0);
            const adultOver = Math.max(0, adults - adultCapacity);
            const minorOver = Math.max(0, minors - childCapacity);

            if (!adultOver && !minorOver) {
                formAlert.className = 'col-12 d-block';
                formAlert.innerHTML = `<div class="alert alert-success py-2 mb-0 small">Phòng ${option.dataset.roomNumber}: sau khi lưu sẽ có ${adults}/${adultCapacity} người lớn và ${minors}/${childCapacity} trẻ em/em bé — trong sức chứa.</div>`;
                return;
            }

            const overText = [
                adultOver ? `vượt ${adultOver} người lớn` : '',
                minorOver ? `vượt ${minorOver} trẻ em/em bé` : ''
            ].filter(Boolean).join(' và ');
            formAlert.className = 'col-12 d-block';
            formAlert.innerHTML = `<div class="alert alert-danger py-2 mb-0 small"><strong>Phòng ${option.dataset.roomNumber} sẽ ${overText}.</strong> Vẫn có thể lưu khách thực tế, nhưng trước khi check-in bắt buộc xử lý: thu phụ phí, thêm phòng, đổi hạng hoặc chuyển khách sang phòng khác.</div>`;
        };

        birthdayInput?.addEventListener('change', syncBirthday);
        birthdayInput?.addEventListener('input', syncBirthday);
        birthdayInput?.addEventListener('project-date-change', syncBirthday);
        typeSelect?.addEventListener('change', () => {
            // Nhóm tuổi là dữ liệu dẫn xuất; nếu nhân viên bấm nhầm, trả ngay về nhóm đúng theo ngày sinh.
            if (birthdayInput?.value) syncBirthday();
            else {
                syncTypeFields();
                syncCapacity();
            }
        });
        roomSelect?.addEventListener('change', syncCapacity);

        if (birthdayInput && window.initializeProjectDatePicker) window.initializeProjectDatePicker(birthdayInput);
        syncTypeFields();
        if (birthdayInput?.value) syncBirthday();
        syncCapacity();

        form.closest('form')?.addEventListener('submit', (event) => {
            syncBirthday();
            if (!birthdayInput.value || !birthdayInput.checkValidity() || !typeSelect.checkValidity()) {
                event.preventDefault();
                if (!birthdayInput.checkValidity()) birthdayInput.reportValidity();
                else typeSelect.reportValidity();
                submitButton?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });
});
</script>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/bookings/partials/staying-guest-fields.blade.php ENDPATH**/ ?>