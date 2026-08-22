@php
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
@endphp

<div class="row g-2 staying-guest-form" data-guest-form data-editing-guest-id="{{ $editingGuest?->id }}">
    <input type="hidden" name="no_document_acknowledged" value="{{ $existingNoDocumentAck ? 1 : 0 }}" data-no-document-ack>
    <input type="hidden" name="no_document_reason" value="{{ $existingNoDocumentAck ? $editingGuest?->document_exception_reason : '' }}" data-no-document-reason>
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 flex-wrap border rounded p-2 bg-white">
            <input type="file" id="{{ $fieldPrefix }}cccd_image" class="d-none js-cccd-image" accept="image/*"
                data-scan-side="ocr"
                data-button="#{{ $fieldPrefix }}cccd_scan_button"
                data-status="#{{ $fieldPrefix }}cccd_scan_status"
                data-target-cccd="#{{ $fieldPrefix }}document_number"
                data-target-full-name="#{{ $fieldPrefix }}full_name"
                data-target-birthday="#{{ $fieldPrefix }}birthday"
                data-target-gender="#{{ $fieldPrefix }}gender"
                data-target-nationality="#{{ $fieldPrefix }}nationality"
                data-target-address="#{{ $fieldPrefix }}address"
                data-required-fields="cccd,full_name,birthday,gender,nationality,address">
            <label for="{{ $fieldPrefix }}cccd_image" id="{{ $fieldPrefix }}cccd_scan_button" class="btn btn-outline-primary btn-sm mb-0">
                <i class="bx bx-image-add me-1"></i> Quét CCCD từ ảnh
            </label>
            <span id="{{ $fieldPrefix }}cccd_scan_status" class="small text-muted"></span>
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label small">Họ và tên <span class="text-danger">*</span></label>
        <input type="text" name="full_name" id="{{ $fieldPrefix }}full_name" class="form-control form-control-sm" value="{{ old('full_name', $editingGuest?->full_name) }}" required>
    </div>
    <div class="col-md-2">
        <label class="form-label small">Nhóm tuổi <span class="text-danger">*</span></label>
        <select name="guest_type" class="form-select form-select-sm" data-guest-type required>
            <option value="adult" @selected($selectedType === 'adult')>Người lớn (từ 18 tuổi)</option>
            <option value="child" @selected($selectedType === 'child')>Trẻ em (6–17 tuổi)</option>
            <option value="infant" @selected($selectedType === 'infant')>Em bé (0–5 tuổi)</option>
        </select>
        <div class="form-text" data-age-message>Tự xác định theo ngày sinh.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Phòng lưu trú <span class="text-danger">*</span></label>
        <select name="booking_room_id" class="form-select form-select-sm" data-booking-room required>
            <option value="">-- Chọn phòng --</option>
            @foreach($booking->bookingRooms as $roomOption)
                @php
                    $roomGuestsForOption = $booking->guests->where('booking_room_id', $roomOption->id);
                    if ($editingGuest && (int) $editingGuest->booking_room_id === (int) $roomOption->id) {
                        $roomGuestsForOption = $roomGuestsForOption->where('id', '!=', $editingGuest->id);
                    }
                    $currentAdultsForOption = $roomGuestsForOption->where('guest_type', 'adult')->count();
                    $currentMinorsForOption = $roomGuestsForOption->whereIn('guest_type', ['child', 'infant'])->count();
                @endphp
                <option value="{{ $roomOption->id }}"
                    data-room-number="{{ $roomOption->room?->room_number ?? '---' }}"
                    data-adult-capacity="{{ (int) ($roomOption->room?->category?->adult_capacity ?? 0) }}"
                    data-child-capacity="{{ (int) ($roomOption->room?->category?->child_capacity ?? 0) }}"
                    data-current-adults="{{ $currentAdultsForOption }}"
                    data-current-minors="{{ $currentMinorsForOption }}"
                    @selected((string)$selectedRoomId === (string)$roomOption->id)>
                    Phòng {{ $roomOption->room?->room_number ?? '---' }} · {{ $roomOption->room?->category?->name ?? '---' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label small">Ngày sinh <span class="text-danger">*</span></label>
        <input type="date" name="birthday" id="{{ $fieldPrefix }}birthday"
            class="form-control form-control-sm" value="{{ $birthdayValue }}"
            min="1900-01-01" max="{{ now('Asia/Ho_Chi_Minh')->toDateString() }}"
            data-birth-date data-birthday-input required>
    </div>

    <div class="col-12 d-none" data-form-alert></div>

    <div class="col-md-2">
        <label class="form-label small">Giới tính <span class="text-danger">*</span></label>
        <select name="gender" id="{{ $fieldPrefix }}gender" class="form-select form-select-sm" required>
            <option value="">-- Chọn --</option>
            <option value="male" @selected(old('gender', $editingGuest?->gender) === 'male')>Nam</option>
            <option value="female" @selected(old('gender', $editingGuest?->gender) === 'female')>Nữ</option>
            <option value="other" @selected(old('gender', $editingGuest?->gender) === 'other')>Khác</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">Quốc tịch <span class="text-danger">*</span></label>
        <input type="text" name="nationality" id="{{ $fieldPrefix }}nationality" class="form-control form-control-sm" value="{{ old('nationality', $editingGuest?->nationality ?? 'Việt Nam') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Loại giấy tờ</label>
        <select name="document_type" id="{{ $fieldPrefix }}document_type" class="form-select form-select-sm js-document-type" data-document-type>
            <option value="cccd" @selected(old('document_type', $editingGuest?->document_type ?? 'cccd') === 'cccd')>CCCD</option>
            <option value="passport" @selected(old('document_type', $editingGuest?->document_type) === 'passport')>Hộ chiếu</option>
            <option value="birth_certificate" @selected(old('document_type', $editingGuest?->document_type) === 'birth_certificate')>Giấy khai sinh</option>
            <option value="personal_id" @selected(old('document_type', $editingGuest?->document_type) === 'personal_id')>Mã định danh</option>
            <option value="other" @selected(old('document_type', $editingGuest?->document_type) === 'other')>Giấy tờ khác</option>
            <option value="none" @selected(old('document_type', $editingGuest?->document_type) === 'none')>Chưa xuất trình giấy tờ</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Số giấy tờ</label>
        <input type="text" name="document_number" id="{{ $fieldPrefix }}document_number" class="form-control form-control-sm js-document-number" value="{{ old('document_number', $editingGuest?->document_number ?? $editingGuest?->cccd) }}" maxlength="50">
    </div>
    <div class="col-md-2 d-flex align-items-end">
        @if($canChooseRepresentative)
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="is_booking_representative" value="1" id="{{ $fieldPrefix }}representative" @checked($representativeChecked)>
                <label class="form-check-label small" for="{{ $fieldPrefix }}representative">Đại diện đoàn</label>
            </div>
        @else
            <div class="small text-muted mb-2">
                <i class="bx bx-lock-alt"></i> Đại diện: <strong>{{ $currentRepresentative->full_name }}</strong>
            </div>
        @endif
    </div>

    <div class="col-md-4" data-guardian-field>
        <label class="form-label small">Người giám hộ đi cùng</label>
        <select name="guardian_guest_id" class="form-select form-select-sm">
            <option value="">-- Chọn người lớn --</option>
            @foreach($booking->guests->where('guest_type', 'adult') as $adultGuest)
                @continue($editingGuest && $adultGuest->id === $editingGuest->id)
                <option value="{{ $adultGuest->id }}" @selected((string)old('guardian_guest_id', $editingGuest?->guardian_guest_id) === (string)$adultGuest->id)>
                    {{ $adultGuest->full_name }} · Phòng {{ $adultGuest->bookingRoom?->room?->room_number ?? '---' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3" data-guardian-field>
        <label class="form-label small">Quan hệ với trẻ</label>
        <input type="text" name="guardian_relationship" class="form-control form-control-sm" value="{{ old('guardian_relationship', $editingGuest?->guardian_relationship) }}" placeholder="Cha, mẹ, ông, bà...">
    </div>
    <div class="col-md-5">
        <label class="form-label small">Địa chỉ</label>
        <input type="text" name="address" id="{{ $fieldPrefix }}address" class="form-control form-control-sm" value="{{ old('address', $editingGuest?->address) }}">
    </div>
    <div class="col-12">
        <label class="form-label small">Ghi chú</label>
        <input type="text" name="note" class="form-control form-control-sm" value="{{ old('note', $editingGuest?->note) }}" placeholder="Thông tin do người giám hộ cung cấp hoặc ghi chú vận hành">
    </div>
</div>

@once
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
@endonce
