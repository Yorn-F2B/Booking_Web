<?php ($index = $index ?? 0); ?>
<div class="border rounded-3 p-3 bg-white mb-3 js-batch-guest-row" data-index="<?php echo e($index); ?>">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Khách <span class="js-batch-number"><?php echo e(is_numeric($index) ? ((int) $index + 1) : 1); ?></span></strong>
        <button type="button" class="btn btn-sm btn-outline-danger js-remove-batch-guest">Xóa mục</button>
    </div>
    <div class="row g-2">
        <div class="col-md-4"><label class="form-label small">Họ và tên <span class="text-danger">*</span></label><input class="form-control form-control-sm" name="guests[<?php echo e($index); ?>][full_name]" required></div>
        <div class="col-md-4">
            <label class="form-label small">Ngày sinh <span class="text-danger">*</span></label>
            <input type="date" class="form-control form-control-sm js-birthday-value"
                name="guests[<?php echo e($index); ?>][birthday]" min="1900-01-01"
                max="<?php echo e(now('Asia/Ho_Chi_Minh')->toDateString()); ?>" data-birth-date required>
        </div>
        <div class="col-md-2"><label class="form-label small">Nhóm tuổi <span class="text-danger">*</span></label><select class="form-select form-select-sm" name="guests[<?php echo e($index); ?>][guest_type]" required><option value="adult">Người lớn</option><option value="child">Trẻ em</option><option value="infant">Em bé</option></select></div>
        <div class="col-md-2"><label class="form-label small">Giới tính <span class="text-danger">*</span></label><select class="form-select form-select-sm" name="guests[<?php echo e($index); ?>][gender]" required><option value="">-- Chọn --</option><option value="male">Nam</option><option value="female">Nữ</option><option value="other">Khác</option></select></div>
        <div class="col-md-2"><label class="form-label small">Phòng <span class="text-danger">*</span></label><select class="form-select form-select-sm" name="guests[<?php echo e($index); ?>][booking_room_id]" required><option value="">-- Chọn --</option><?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roomOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($roomOption->id); ?>">Phòng <?php echo e($roomOption->room?->room_number ?? '---'); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-md-2"><label class="form-label small">Quốc tịch</label><input class="form-control form-control-sm" name="guests[<?php echo e($index); ?>][nationality]" value="Việt Nam" required></div>
        <div class="col-md-2"><label class="form-label small">Loại giấy tờ</label><select class="form-select form-select-sm" name="guests[<?php echo e($index); ?>][document_type]"><option value="cccd">CCCD</option><option value="passport">Hộ chiếu</option><option value="birth_certificate">Giấy khai sinh</option><option value="personal_id">Mã định danh</option><option value="other">Khác</option><option value="none">Chưa có</option></select></div>
        <div class="col-md-3"><label class="form-label small">Số giấy tờ</label><input class="form-control form-control-sm" name="guests[<?php echo e($index); ?>][document_number]" maxlength="50"></div>
        <div class="col-md-3"><label class="form-label small">Địa chỉ</label><input class="form-control form-control-sm" name="guests[<?php echo e($index); ?>][address]"></div>
        <div class="col-md-4 js-guardian-fields d-none"><label class="form-label small">Người giám hộ đi cùng <span class="text-danger">*</span></label><select class="form-select form-select-sm js-guardian-reference" name="guests[<?php echo e($index); ?>][guardian_reference]"><option value="">-- Chọn người lớn --</option><?php $__currentLoopData = $booking->guests->where('guest_type', 'adult'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $adultGuest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="existing:<?php echo e($adultGuest->id); ?>"><?php echo e($adultGuest->full_name); ?> (đã khai)</option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div class="col-md-3 js-guardian-fields d-none"><label class="form-label small">Quan hệ với trẻ <span class="text-danger">*</span></label><input class="form-control form-control-sm" name="guests[<?php echo e($index); ?>][guardian_relationship]" placeholder="Cha, mẹ, ông, bà..."></div>
        <div class="col-md-2 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input js-representative-checkbox" type="checkbox" name="guests[<?php echo e($index); ?>][is_booking_representative]" value="1"><label class="form-check-label small">Đại diện đoàn</label></div></div>
        <div class="col-12"><label class="form-label small">Ghi chú</label><input class="form-control form-control-sm" name="guests[<?php echo e($index); ?>][note]"></div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\bookings\partials\staying-guest-batch-row.blade.php ENDPATH**/ ?>