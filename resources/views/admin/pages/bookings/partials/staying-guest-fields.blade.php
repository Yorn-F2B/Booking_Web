@php
    $editingGuest = $editingGuest ?? null;
    $batchKey = $batchKey ?? null;
    $externalFormId = $externalFormId ?? null;
    $fieldName = fn (string $name) => $batchKey !== null ? 'guests[' . $batchKey . '][' . $name . ']' : $name;
    $fieldOld = function (string $name, $default = null) use ($batchKey) {
        return $batchKey !== null ? data_get(old('guests', []), $batchKey . '.' . $name, $default) : old($name, $default);
    };
    $selectedRoomId = (int) ($editingGuest?->booking_room_id ?? $defaultBookingRoomId);
    $selectedBookingRoom = $booking->bookingRooms->firstWhere('id', $selectedRoomId);
    $fieldPrefix = $editingGuest ? 'guest_' . $editingGuest->id . '_' : 'rep_' . $selectedRoomId . '_';
    $birthdayValue = $fieldOld('birthday', $editingGuest?->birthday?->format('Y-m-d'));
    $needsGroupRepresentative = max(1, (int) ($booking->room_quantity ?? $booking->bookingRooms->count())) > 1;
    $currentRepresentative = $booking->guests->firstWhere('is_booking_representative', true);
    $representativeChecked = $needsGroupRepresentative
        && (bool) $fieldOld('is_booking_representative', $editingGuest?->is_booking_representative ?? false);
    $editingHasValidDocument = $editingGuest
        && $editingGuest->document_type !== 'none'
        && trim((string) $editingGuest->document_number) !== '';
    $existingNoDocumentAck = $editingGuest
        && !$editingHasValidDocument
        && (bool) $editingGuest->document_exception_acknowledged;
@endphp

<div class="row g-2 staying-guest-form" data-guest-form data-editing-guest-id="{{ $editingGuest?->id }}">
    <input type="hidden" name="{{ $fieldName('booking_room_id') }}" value="{{ $selectedRoomId }}" @if($externalFormId) form="{{ $externalFormId }}" @endif>
    <input type="hidden" name="{{ $fieldName('guest_type') }}" value="adult" @if($externalFormId) form="{{ $externalFormId }}" @endif>
    <input type="hidden" name="{{ $fieldName('no_document_acknowledged') }}" value="{{ $existingNoDocumentAck ? 1 : 0 }}" data-no-document-ack @if($externalFormId) form="{{ $externalFormId }}" @endif>
    <input type="hidden" name="{{ $fieldName('no_document_reason') }}" value="{{ $existingNoDocumentAck ? $editingGuest?->document_exception_reason : '' }}" data-no-document-reason @if($externalFormId) form="{{ $externalFormId }}" @endif>

    <div class="col-12">
        <div class="d-flex justify-content-between gap-2 flex-wrap align-items-center border rounded p-2 bg-white">
            <div class="small">
                <span class="text-muted">Đại diện phòng</span>
                <strong class="ms-1">{{ $selectedBookingRoom?->room?->room_number ?? '---' }}</strong>
                <span class="text-muted">· {{ $selectedBookingRoom?->room?->category?->name ?? 'Chưa rõ hạng' }}</span>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm js-use-booker-info"
                    data-full-name="{{ e($booking->booked_customer_name ?? '') }}"
                    data-birthday="{{ $booking->booked_customer_birthday ? \Carbon\Carbon::parse($booking->booked_customer_birthday)->format('Y-m-d') : '' }}"
                    data-gender="{{ e($booking->booked_customer_gender ?? '') }}"
                    data-nationality="Việt Nam"
                    data-document-number="{{ e($booking->booked_customer_cccd ?? '') }}"
                    data-address="{{ e($booking->booked_customer_address ?? '') }}">
                    <i class="bx bx-user-check me-1"></i> Dùng thông tin người đặt
                </button>
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
                <span id="{{ $fieldPrefix }}cccd_scan_status" class="small text-muted ms-1"></span>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <label class="form-label small">Họ và tên người đại diện <span class="text-danger">*</span></label>
        <input type="text" name="{{ $fieldName('full_name') }}" id="{{ $fieldPrefix }}full_name" class="form-control form-control-sm"
            value="{{ $fieldOld('full_name', $editingGuest?->full_name) }}" required @if($externalFormId) form="{{ $externalFormId }}" @endif>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Ngày sinh <span class="text-danger">*</span></label>
        <input type="date" name="{{ $fieldName('birthday') }}" id="{{ $fieldPrefix }}birthday" class="form-control form-control-sm"
            value="{{ $birthdayValue }}" min="1900-01-01" max="{{ now('Asia/Ho_Chi_Minh')->subYears(18)->toDateString() }}" required @if($externalFormId) form="{{ $externalFormId }}" @endif>
        <div class="form-text">Người đại diện phòng phải từ 18 tuổi.</div>
    </div>
    <div class="col-md-2">
        <label class="form-label small">Giới tính <span class="text-danger">*</span></label>
        <select name="{{ $fieldName('gender') }}" id="{{ $fieldPrefix }}gender" class="form-select form-select-sm" required @if($externalFormId) form="{{ $externalFormId }}" @endif>
            <option value="">-- Chọn --</option>
            <option value="male" @selected($fieldOld('gender', $editingGuest?->gender) === 'male')>Nam</option>
            <option value="female" @selected($fieldOld('gender', $editingGuest?->gender) === 'female')>Nữ</option>
            <option value="other" @selected($fieldOld('gender', $editingGuest?->gender) === 'other')>Khác</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small">Quốc tịch <span class="text-danger">*</span></label>
        <input type="text" name="{{ $fieldName('nationality') }}" id="{{ $fieldPrefix }}nationality" class="form-control form-control-sm"
            value="{{ $fieldOld('nationality', $editingGuest?->nationality ?? 'Việt Nam') }}" required @if($externalFormId) form="{{ $externalFormId }}" @endif>
    </div>

    <div class="col-md-3">
        <label class="form-label small">Loại giấy tờ</label>
        <select name="{{ $fieldName('document_type') }}" id="{{ $fieldPrefix }}document_type" class="form-select form-select-sm js-document-type" @if($externalFormId) form="{{ $externalFormId }}" @endif>
            <option value="cccd" @selected($fieldOld('document_type', $editingGuest?->document_type ?? 'cccd') === 'cccd')>CCCD</option>
            <option value="passport" @selected($fieldOld('document_type', $editingGuest?->document_type) === 'passport')>Hộ chiếu</option>
            <option value="personal_id" @selected($fieldOld('document_type', $editingGuest?->document_type) === 'personal_id')>Mã định danh</option>
            <option value="other" @selected($fieldOld('document_type', $editingGuest?->document_type) === 'other')>Giấy tờ khác</option>
            <option value="none" @selected($fieldOld('document_type', $editingGuest?->document_type) === 'none')>Chưa xuất trình giấy tờ</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Số giấy tờ</label>
        <input type="text" name="{{ $fieldName('document_number') }}" id="{{ $fieldPrefix }}document_number" class="form-control form-control-sm js-document-number"
            value="{{ $fieldOld('document_number', $editingGuest?->document_number ?? $editingGuest?->cccd) }}" maxlength="50" @if($externalFormId) form="{{ $externalFormId }}" @endif>
    </div>
    <div class="col-md-6">
        <label class="form-label small">Địa chỉ</label>
        <input type="text" name="{{ $fieldName('address') }}" id="{{ $fieldPrefix }}address" class="form-control form-control-sm"
            value="{{ $fieldOld('address', $editingGuest?->address) }}" @if($externalFormId) form="{{ $externalFormId }}" @endif>
    </div>

    @if($needsGroupRepresentative)
        <div class="col-12">
            <div class="form-check border rounded p-2 ps-5 bg-light">
                <input class="form-check-input" type="checkbox" name="{{ $fieldName('is_booking_representative') }}" value="1"
                    id="{{ $fieldPrefix }}representative" @checked($representativeChecked) @if($externalFormId) form="{{ $externalFormId }}" @endif>
                <label class="form-check-label small fw-semibold" for="{{ $fieldPrefix }}representative">
                    Đại diện đoàn
                </label>
                @if($currentRepresentative && (!$editingGuest || (int) $currentRepresentative->id !== (int) $editingGuest->id))
                    <div class="small text-primary mt-1">Hiện tại: <strong>{{ $currentRepresentative->full_name }}</strong>.</div>
                @endif
            </div>
        </div>
    @endif

    <div class="col-12">
        <label class="form-label small">Ghi chú</label>
        <input type="text" name="{{ $fieldName('note') }}" class="form-control form-control-sm" value="{{ $fieldOld('note', $editingGuest?->note) }}" @if($externalFormId) form="{{ $externalFormId }}" @endif
            placeholder="Ghi chú cho người đại diện/phòng nếu có">
    </div>
</div>
