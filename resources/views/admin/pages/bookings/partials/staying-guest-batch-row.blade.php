@php
    $index = $index ?? 0;
@endphp
<div class="border rounded-3 p-3 bg-white mb-3 js-batch-guest-row" data-index="{{ $index }}">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Khách <span class="js-batch-number">{{ is_numeric($index) ? ((int) $index + 1) : 1 }}</span></strong>
        <button type="button" class="btn btn-sm btn-outline-danger js-remove-batch-guest">Xóa mục</button>
    </div>
    <input type="hidden" name="guests[{{ $index }}][no_document_acknowledged]" value="0" data-no-document-ack>
    <input type="hidden" name="guests[{{ $index }}][no_document_reason]" value="" data-no-document-reason>
    <div class="row g-2">
        <div class="col-md-4"><label class="form-label small">Họ và tên <span class="text-danger">*</span></label><input class="form-control form-control-sm" name="guests[{{ $index }}][full_name]" required></div>
        <div class="col-md-4">
            <label class="form-label small">Ngày sinh <span class="text-danger">*</span></label>
            <input type="date" class="form-control form-control-sm js-birthday-value"
                name="guests[{{ $index }}][birthday]" min="1900-01-01"
                max="{{ now('Asia/Ho_Chi_Minh')->toDateString() }}" data-birth-date required>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Nhóm tuổi <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm js-guest-type" name="guests[{{ $index }}][guest_type]" required>
                <option value="adult">Người lớn</option>
                <option value="child">Trẻ em</option>
            </select>
            <div class="form-text js-age-message">Tự xác định theo ngày sinh.</div>
        </div>
        <div class="col-md-2"><label class="form-label small">Giới tính <span class="text-danger">*</span></label><select class="form-select form-select-sm" name="guests[{{ $index }}][gender]" required><option value="">-- Chọn --</option><option value="male">Nam</option><option value="female">Nữ</option><option value="other">Khác</option></select></div>
        <div class="col-md-2"><label class="form-label small">Phòng <span class="text-danger">*</span></label><select class="form-select form-select-sm" name="guests[{{ $index }}][booking_room_id]" required><option value="">-- Chọn --</option>@foreach($booking->bookingRooms as $roomOption)<option value="{{ $roomOption->id }}">Phòng {{ $roomOption->room?->room_number ?? '---' }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label small">Quốc tịch</label><input class="form-control form-control-sm" name="guests[{{ $index }}][nationality]" value="Việt Nam" required></div>
        <div class="col-md-2"><label class="form-label small">Loại giấy tờ</label><select class="form-select form-select-sm js-document-type" name="guests[{{ $index }}][document_type]"><option value="cccd">CCCD</option><option value="passport">Hộ chiếu</option><option value="birth_certificate">Giấy khai sinh</option><option value="personal_id">Mã định danh</option><option value="other">Khác</option><option value="none">Chưa có</option></select></div>
        <div class="col-md-3"><label class="form-label small">Số giấy tờ</label><input class="form-control form-control-sm js-document-number" name="guests[{{ $index }}][document_number]" maxlength="50"></div>
        <div class="col-md-3"><label class="form-label small">Địa chỉ</label><input class="form-control form-control-sm" name="guests[{{ $index }}][address]"></div>
        <div class="col-md-4 js-guardian-fields d-none"><label class="form-label small">Người giám hộ đi cùng <span class="text-danger">*</span></label><select class="form-select form-select-sm js-guardian-reference" name="guests[{{ $index }}][guardian_reference]"><option value="">-- Chọn người lớn --</option>@foreach($booking->guests->where('guest_type', 'adult') as $adultGuest)<option value="existing:{{ $adultGuest->id }}">{{ $adultGuest->full_name }} (đã khai)</option>@endforeach</select></div>
        <div class="col-md-3 js-guardian-fields d-none"><label class="form-label small">Quan hệ với trẻ <span class="text-danger">*</span></label><input class="form-control form-control-sm" name="guests[{{ $index }}][guardian_relationship]" placeholder="Cha, mẹ, ông, bà..."></div>
        <div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input js-representative-checkbox" type="checkbox" name="guests[{{ $index }}][is_booking_representative]" value="1"><label class="form-check-label small">Đại diện đoàn</label></div></div>
        <div class="col-12"><label class="form-label small">Ghi chú</label><input class="form-control form-control-sm" name="guests[{{ $index }}][note]"></div>
    </div>
</div>
