<?php
    $editingGuest = $editingGuest ?? null;
    $selectedRoomId = (int) ($editingGuest?->booking_room_id ?? $defaultBookingRoomId);
    $selectedBookingRoom = $booking->bookingRooms->firstWhere('id', $selectedRoomId);
    $fieldPrefix = $editingGuest ? 'guest_' . $editingGuest->id . '_' : 'rep_' . $selectedRoomId . '_';
    $birthdayValue = old('birthday', $editingGuest?->birthday?->format('Y-m-d'));
    $needsGroupRepresentative = max(1, (int) ($booking->room_quantity ?? $booking->bookingRooms->count())) > 1;
    $currentRepresentative = $booking->guests->firstWhere('is_booking_representative', true);
    $canChooseRepresentative = !$currentRepresentative || ($editingGuest && (int) $currentRepresentative->id === (int) $editingGuest->id);
    $representativeChecked = $needsGroupRepresentative && $canChooseRepresentative
        && (bool) old('is_booking_representative', $editingGuest?->is_booking_representative ?? false);
    $editingHasValidDocument = $editingGuest
        && $editingGuest->document_type !== 'none'
        && trim((string) $editingGuest->document_number) !== '';
    $existingNoDocumentAck = $editingGuest
        && !$editingHasValidDocument
        && (bool) $editingGuest->document_exception_acknowledged;
?>

<div class="row g-2 staying-guest-form" data-guest-form data-editing-guest-id="<?php echo e($editingGuest?->id); ?>">
    <input type="hidden" name="booking_room_id" value="<?php echo e($selectedRoomId); ?>">
    <input type="hidden" name="guest_type" value="adult">
    <input type="hidden" name="no_document_acknowledged" value="<?php echo e($existingNoDocumentAck ? 1 : 0); ?>" data-no-document-ack>
    <input type="hidden" name="no_document_reason" value="<?php echo e($existingNoDocumentAck ? $editingGuest?->document_exception_reason : ''); ?>" data-no-document-reason>

    <div class="col-12">
        <div class="d-flex justify-content-between gap-2 flex-wrap align-items-center border rounded p-2 bg-white">
            <div class="small">
                <span class="text-muted">Đại diện phòng</span>
                <strong class="ms-1"><?php echo e($selectedBookingRoom?->room?->room_number ?? '---'); ?></strong>
                <span class="text-muted">· <?php echo e($selectedBookingRoom?->room?->category?->name ?? 'Chưa rõ hạng'); ?></span>
            </div>
            <div>
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
                <span id="<?php echo e($fieldPrefix); ?>cccd_scan_status" class="small text-muted ms-1"></span>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <label class="form-label small">Họ và tên người đại diện <span class="text-danger">*</span></label>
        <input type="text" name="full_name" id="<?php echo e($fieldPrefix); ?>full_name" class="form-control form-control-sm"
            value="<?php echo e(old('full_name', $editingGuest?->full_name)); ?>" required>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Ngày sinh <span class="text-danger">*</span></label>
        <input type="date" name="birthday" id="<?php echo e($fieldPrefix); ?>birthday" class="form-control form-control-sm"
            value="<?php echo e($birthdayValue); ?>" min="1900-01-01" max="<?php echo e(now('Asia/Ho_Chi_Minh')->subYears(18)->toDateString()); ?>" required>
        <div class="form-text">Người đại diện phòng phải từ 18 tuổi.</div>
    </div>
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
        <input type="text" name="nationality" id="<?php echo e($fieldPrefix); ?>nationality" class="form-control form-control-sm"
            value="<?php echo e(old('nationality', $editingGuest?->nationality ?? 'Việt Nam')); ?>" required>
    </div>

    <div class="col-md-3">
        <label class="form-label small">Loại giấy tờ</label>
        <select name="document_type" id="<?php echo e($fieldPrefix); ?>document_type" class="form-select form-select-sm js-document-type">
            <option value="cccd" <?php if(old('document_type', $editingGuest?->document_type ?? 'cccd') === 'cccd'): echo 'selected'; endif; ?>>CCCD</option>
            <option value="passport" <?php if(old('document_type', $editingGuest?->document_type) === 'passport'): echo 'selected'; endif; ?>>Hộ chiếu</option>
            <option value="personal_id" <?php if(old('document_type', $editingGuest?->document_type) === 'personal_id'): echo 'selected'; endif; ?>>Mã định danh</option>
            <option value="other" <?php if(old('document_type', $editingGuest?->document_type) === 'other'): echo 'selected'; endif; ?>>Giấy tờ khác</option>
            <option value="none" <?php if(old('document_type', $editingGuest?->document_type) === 'none'): echo 'selected'; endif; ?>>Chưa xuất trình giấy tờ</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Số giấy tờ</label>
        <input type="text" name="document_number" id="<?php echo e($fieldPrefix); ?>document_number" class="form-control form-control-sm js-document-number"
            value="<?php echo e(old('document_number', $editingGuest?->document_number ?? $editingGuest?->cccd)); ?>" maxlength="50">
    </div>
    <div class="col-md-6">
        <label class="form-label small">Địa chỉ</label>
        <input type="text" name="address" id="<?php echo e($fieldPrefix); ?>address" class="form-control form-control-sm"
            value="<?php echo e(old('address', $editingGuest?->address)); ?>">
    </div>

    <?php if($needsGroupRepresentative): ?>
        <div class="col-12">
            <?php if($canChooseRepresentative): ?>
                <div class="form-check border rounded p-2 ps-5 bg-light">
                    <input class="form-check-input" type="checkbox" name="is_booking_representative" value="1"
                        id="<?php echo e($fieldPrefix); ?>representative" <?php if($representativeChecked): echo 'checked'; endif; ?>>
                    <label class="form-check-label small fw-semibold" for="<?php echo e($fieldPrefix); ?>representative">
                        Chọn người này làm đại diện cả đoàn
                    </label>
                    <div class="small text-muted">Booking nhiều phòng chỉ cần đúng 1 đại diện đoàn; người này đồng thời là đại diện của phòng đang ở.</div>
                </div>
            <?php else: ?>
                <div class="small text-muted border rounded p-2 bg-light">
                    Đại diện đoàn hiện tại: <strong><?php echo e($currentRepresentative->full_name); ?></strong>. Muốn đổi, bỏ vai trò ở hồ sơ hiện tại trước rồi chọn người khác.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="col-12">
        <label class="form-label small">Ghi chú</label>
        <input type="text" name="note" class="form-control form-control-sm" value="<?php echo e(old('note', $editingGuest?->note)); ?>"
            placeholder="Ghi chú cho người đại diện/phòng nếu có">
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/bookings/partials/staying-guest-fields.blade.php ENDPATH**/ ?>