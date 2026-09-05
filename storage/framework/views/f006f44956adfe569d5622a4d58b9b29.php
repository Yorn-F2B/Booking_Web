<?php $__env->startSection('title', 'Tạo đặt phòng'); ?>

<?php $__env->startSection('content'); ?>

<?php
    $adjacentRoomWarning = session('adjacent_room_warning');
    $policyService = app(\App\Services\HotelPolicyService::class);
    $minBookingAge = max(0, (int) $policyService->get('booking.min_age', 18));
    $depositPercent = max(0, min(100, (float) $policyService->get('payment.deposit_percent', 30)));
    $depositRate = $depositPercent / 100;
    $manualRoomSelectionFee = max(0, (float) $policyService->get('booking.manual_room_selection_fee', 50000));
    $policyUi = [
        'standardCheckIn' => (string) $policyService->get('stay.standard_check_in_time', '14:00'),
        'standardCheckOut' => (string) $policyService->get('stay.standard_check_out_time', '12:00'),
        'earlyFreeFrom' => (string) $policyService->get('stay.early_checkin_free_from', '12:00'),
        'earlyTier1End' => (string) $policyService->get('stay.early_checkin_tier1_end', '06:00'),
        'earlyTier2End' => (string) $policyService->get('stay.early_checkin_tier2_end', '09:00'),
        'earlyPercent1' => (float) $policyService->get('stay.early_checkin_percent_1', 100),
        'earlyPercent2' => (float) $policyService->get('stay.early_checkin_percent_2', 50),
        'earlyPercent3' => (float) $policyService->get('stay.early_checkin_percent_3', 20),
        'lateFreeMinutes' => (int) $policyService->get('stay.late_checkout_free_minutes', 15),
        'lateTier1End' => (string) $policyService->get('stay.late_checkout_tier1_end', '13:00'),
        'lateTier2End' => (string) $policyService->get('stay.late_checkout_tier2_end', '14:00'),
        'lateTier3End' => (string) $policyService->get('stay.late_checkout_tier3_end', '15:00'),
        'lateFullFrom' => (string) $policyService->get('stay.late_checkout_full_night_from', '18:00'),
        'latePercent1' => (float) $policyService->get('stay.late_checkout_percent_1', 20),
        'latePercent2' => (float) $policyService->get('stay.late_checkout_percent_2', 40),
        'latePercent3' => (float) $policyService->get('stay.late_checkout_percent_3', 60),
        'latePercent4' => (float) $policyService->get('stay.late_checkout_percent_4', 80),
        'latePercentFull' => (float) $policyService->get('stay.late_checkout_percent_full', 100),
        'shortMinMinutes' => (int) $policyService->get('stay.short_stay_min_minutes', 30),
        'shortToOvernightHours' => (int) $policyService->get('stay.short_stay_to_overnight_hours', 12),
        'shortBaseHours' => (int) $policyService->get('stay.short_stay_base_hours', 2),
        'shortBasePercent' => (float) $policyService->get('stay.short_stay_base_percent', 50),
        'shortExtraHourPercent' => (float) $policyService->get('stay.short_stay_extra_hour_percent', 10),
        'shortMaxPercent' => (float) $policyService->get('stay.short_stay_max_percent', 80),
        'depositPercent' => $depositPercent,
        'depositRate' => $depositRate,
        'depositPercent2Rooms' => (float) $policyService->get('payment.deposit_percent_2_rooms', 35),
        'depositPercent3Rooms' => (float) $policyService->get('payment.deposit_percent_3_rooms', 40),
        'depositPercent4Rooms' => (float) $policyService->get('payment.deposit_percent_4_rooms', 45),
        'depositPercent5PlusRooms' => (float) $policyService->get('payment.deposit_percent_5plus_rooms', 50),
    ];
?>

    <style>
        .booking-form-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            padding: 18px;
            margin-bottom: 18px;
        }

        .booking-form-card h5 {
            font-weight: 800;
            margin-bottom: 16px;
        }

        .booking-action-card {
            position: sticky;
            top: 84px;
            z-index: 4;
            border-color: #d8dee8;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
        }

        .booking-action-card h5 {
            margin-bottom: 6px;
        }

        .booking-options-details {
            border: 0;
        }

        .booking-options-details > summary {
            list-style: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-weight: 800;
            color: #0f172a;
        }

        .booking-options-details > summary::-webkit-details-marker {
            display: none;
        }

        .booking-options-details > summary::after {
            content: 'Mở';
            flex: 0 0 auto;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            border: 1px solid #dbe3ee;
            border-radius: 999px;
            padding: 4px 9px;
            background: #f8fafc;
        }

        .booking-options-details[open] > summary::after {
            content: 'Thu gọn';
        }

        .booking-options-body {
            max-height: min(54vh, 560px);
            overflow-y: auto;
            margin-top: 14px;
            padding-right: 5px;
        }

        .booking-options-body .service-row {
            background: #fff;
        }

        @media (max-width: 991px) {
            .booking-action-card {
                position: static;
            }

            .booking-options-body {
                max-height: none;
                overflow: visible;
            }
        }

        .booking-help-text {
            font-size: 13px;
            color: #64748b;
        }

        .booking-help-text:empty {
            display: none;
        }

        .booking-payment-confirm {
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 14px 16px 14px 42px;
            background: #f8fafc;
        }

        .booking-payment-confirm .form-check-input {
            width: 1.15rem;
            height: 1.15rem;
            margin-left: -1.65rem;
        }

        .booking-payment-confirm .form-check-label {
            font-weight: 700;
            color: #0f172a;
        }

        .booking-total-box {
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 14px;
            background: #f8fafc;
        }

        .booking-total-box strong {
            font-size: 22px;
        }

        .adjacent-room-box {
            display: none;
        }

        .hourly-preview-box {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 14px;
            padding: 14px;
        }

        .hourly-preview-box.warning {
            border-color: #facc15;
            background: #fefce8;
        }

        .hourly-preview-box.safe {
            border-color: #86efac;
            background: #f0fdf4;
        }

        .hourly-preview-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }


        .promotion-list {
            display: grid;
            gap: 10px;
        }

        .promotion-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 12px;
            background: #ffffff;
            transition: 0.15s ease;
        }

        .promotion-card:hover {
            border-color: #d4af37;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }

        .promotion-card .form-check-input {
            margin-top: 4px;
        }

        .promotion-code {
            font-weight: 800;
            letter-spacing: 0.03em;
            color: #111827;
        }

        .promotion-meta {
            font-size: 12px;
            color: #64748b;
        }

        .promotion-total-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 14px;
            margin-top: 8px;
        }

        .promotion-total-row strong {
            font-size: 16px;
        }

        .promotion-collapsible {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #f8fafc;
            overflow: hidden;
        }

        .promotion-collapsible > summary {
            list-style: none;
            cursor: pointer;
            padding: 13px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-weight: 800;
        }

        .promotion-collapsible > summary::-webkit-details-marker {
            display: none;
        }

        .promotion-collapsible-body {
            border-top: 1px solid #e5e7eb;
            padding: 14px;
            background: #fff;
        }

        .promotion-selected-hint {
            display: block;
            margin-top: 2px;
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
        }


        @media (max-width: 767px) {
            .hourly-preview-grid {
                grid-template-columns: 1fr;
            }
        }

        .hourly-preview-item span {
            display: block;
            font-size: 12px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .hourly-preview-item strong {
            display: block;
            font-size: 14px;
            color: #111827;
        }

        .category-stock-select {
            min-height: 48px;
            border: 1px solid #b8c7df;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(15, 23, 42, .06);
        }

        .category-stock-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .14);
        }

        .category-stock-note {
            min-height: 20px;
            color: #52627a;
        }
    </style>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> /
                <a href="<?php echo e(route('admin.bookings.index')); ?>">Đặt phòng</a> /
                Tạo mới
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Tạo đặt phòng</h2>
                </div>

                <a href="<?php echo e(route('admin.bookings.index')); ?>" class="btn btn-outline-secondary">
                    Quay lại
                </a>

            </div>
<?php if($errors->any()): ?>
                <div class="alert alert-danger">

                    <strong>Không thể tạo booking:</strong>

                    <ul class="mb-0 mt-2">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>

                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('admin.bookings.store')); ?>" method="POST" id="bookingCreateForm">

                <?php echo csrf_field(); ?>
                <input type="hidden" name="confirm_adjacent_fallback" id="confirmAdjacentFallback" value="<?php echo e(old('confirm_adjacent_fallback', 0)); ?>">

                <div class="row g-4">

                    <div class="col-lg-8">

                        <div class="booking-form-card">

                            <h5>Thông tin khách hàng</h5>

                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Họ tên khách hàng <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name"
                                        class="form-control <?php $__errorArgs = ['customer_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('customer_name')); ?>" placeholder="Ví dụ: Nguyễn Văn An" required>

                                    <?php $__errorArgs = ['customer_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_phone" id="customerPhone"
                                        class="form-control <?php $__errorArgs = ['customer_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('customer_phone')); ?>" placeholder="Ví dụ: 0987654321 (nếu có)">

                                    <?php $__errorArgs = ['customer_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-12">
                                    <div class="border rounded p-3 bg-light">
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <button type="button" id="adminCreateCccdButton" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('adminCreateCccdImage').click()"><i class="bx bx-image-add me-1"></i> Quét CCCD từ ảnh</button>
                                            <input type="file" id="adminCreateCccdImage" class="d-none js-cccd-image" accept="image/*"
                                                data-button="#adminCreateCccdButton" data-status="#adminCreateCccdStatus"
                                                data-target-cccd="input[name='customer_cccd']" data-target-full-name="input[name='customer_name']"
                                                data-target-birthday="input[name='customer_birthday']" data-target-gender="select[name='customer_gender']"
                                                data-target-address="input[name='customer_address']"
                                                data-required-fields="cccd,full_name,birthday,gender,address">
                                            <small id="adminCreateCccdStatus" class="text-muted">Quét mặt trước CCCD để điền nhanh thông tin khách.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">CCCD <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_cccd" id="customerCccd"
                                        class="form-control <?php $__errorArgs = ['customer_cccd'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('customer_cccd')); ?>" placeholder="Nhập đúng 12 số CCCD" required>

                                    <?php $__errorArgs = ['customer_cccd'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>


                                <div class="col-md-6">
                                    <label class="form-label">Ngày sinh <span class="text-danger">*</span></label>
                                    <input type="date" name="customer_birthday"
                                        class="form-control <?php $__errorArgs = ['customer_birthday'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('customer_birthday')); ?>"
                                        max="<?php echo e(now('Asia/Ho_Chi_Minh')->subYears($minBookingAge)->toDateString()); ?>" required>
                                    <?php $__errorArgs = ['customer_birthday'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Giới tính</label>
                                    <select name="customer_gender" class="form-select <?php $__errorArgs = ['customer_gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                        <option value="">-- Chọn --</option>
                                        <option value="male" <?php if(old('customer_gender') === 'male'): echo 'selected'; endif; ?>>Nam</option>
                                        <option value="female" <?php if(old('customer_gender') === 'female'): echo 'selected'; endif; ?>>Nữ</option>
                                        <option value="other" <?php if(old('customer_gender') === 'other'): echo 'selected'; endif; ?>>Khác</option>
                                    </select>
                                    <?php $__errorArgs = ['customer_gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="customer_email" id="customerEmail"
                                        class="form-control <?php $__errorArgs = ['customer_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('customer_email')); ?>" placeholder="email@example.com">

                                    <?php $__errorArgs = ['customer_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <div id="customerAccountLookupNotice" class="alert d-none mt-2 mb-0 py-2 px-3 small" role="alert"></div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Địa chỉ</label>
                                    <input type="text" name="customer_address"
                                        class="form-control <?php $__errorArgs = ['customer_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('customer_address')); ?>" placeholder="Nhập địa chỉ nếu có">

                                    <?php $__errorArgs = ['customer_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                            </div>

                        </div>

                        <div class="booking-form-card">

                            <h5>Thông tin đặt phòng</h5>

                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Hình thức tạo booking <span
                                            class="text-danger">*</span></label>

                                    <select name="booking_mode" id="bookingMode" class="form-select" required>
                                        <option value="advance" <?php echo e(old('booking_mode', $bookingPrefill['booking_mode'] ?? 'advance') == 'advance' ? 'selected' : ''); ?>>
                                            Đặt trước
                                        </option>

                                        <option value="walk_in" <?php echo e(old('booking_mode', $bookingPrefill['booking_mode'] ?? 'advance') == 'walk_in' ? 'selected' : ''); ?>>
                                            Ở ngay
                                        </option>
                                    </select>

                                    <div class="booking-help-text mt-1" id="bookingModeHelpText">
                                        Đặt trước giữ phòng theo giờ chuẩn <?php echo e($policyUi['standardCheckIn']); ?> → <?php echo e($policyUi['standardCheckOut']); ?>.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Loại lưu trú <span class="text-danger">*</span></label>
                                    <select name="booking_type" id="bookingType" class="form-select" required>
                                        <option value="overnight" <?php echo e(old('booking_type', $bookingPrefill['booking_type'] ?? 'overnight') == 'overnight' ? 'selected' : ''); ?>>
                                            Qua đêm
                                        </option>

                                        <option value="hourly" <?php echo e(old('booking_type', $bookingPrefill['booking_type'] ?? 'overnight') == 'hourly' ? 'selected' : ''); ?>>
                                            Theo giờ
                                        </option>
                                    </select>

                                    <div class="booking-help-text mt-1" id="bookingTypeHelpText">
                                        Qua đêm giữ phòng theo giờ chuẩn <?php echo e($policyUi['standardCheckIn']); ?> → <?php echo e($policyUi['standardCheckOut']); ?>. Khách có thể nhận từ <?php echo e($policyUi['earlyFreeFrom']); ?> nếu phòng
                                        đã sẵn sàng.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Hạng phòng <span class="text-danger">*</span></label>
                                    <select name="room_category_id" id="roomCategorySelect"
                                        class="form-select category-stock-select <?php $__errorArgs = ['room_category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>

                                        <option value="">-- Chọn hạng phòng --</option>

                                        <?php $__currentLoopData = $roomCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roomCategory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($roomCategory->id); ?>" data-price="<?php echo e($roomCategory->price); ?>" data-name="<?php echo e($roomCategory->name); ?>"
                                                <?php if(old('room_category_id', $bookingPrefill['room_category_id'] ?? null) == $roomCategory->id): echo 'selected'; endif; ?>>
                                                <?php echo e($roomCategory->name); ?>

                                                -
                                                <?php echo e(number_format($roomCategory->price, 0, ',', '.')); ?>đ/đêm
                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                    </select>

                                    <div class="booking-help-text category-stock-note mt-1" id="roomCategoryStockNote">
                                    </div>

                                    <?php $__errorArgs = ['room_category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Ngày nhận <span class="text-danger">*</span></label>
                                    <input type="date" name="check_in_date" id="checkInDate"
                                        class="form-control <?php $__errorArgs = ['check_in_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('check_in_date', $bookingPrefill['check_in_date'] ?? null)); ?>" required>

                                    <?php $__errorArgs = ['check_in_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-md-3" id="checkInTimeBox">
                                    <label class="form-label">Giờ vào</label>
                                    <input type="text" name="check_in_time" id="checkInTime"
                                        class="form-control <?php $__errorArgs = ['check_in_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('check_in_time', $bookingPrefill['check_in_time'] ?? now('Asia/Ho_Chi_Minh')->format('H:i'))); ?>"
                                        placeholder="Ví dụ: 13:30">

                                    <?php $__errorArgs = ['check_in_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-md-3" id="checkOutDateBox">
                                    <label class="form-label">Ngày trả <span class="text-danger">*</span></label>
                                    <input type="date" name="check_out_date" id="checkOutDate"
                                        class="form-control <?php $__errorArgs = ['check_out_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('check_out_date', $bookingPrefill['check_out_date'] ?? null)); ?>" required>

                                    <?php $__errorArgs = ['check_out_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-md-3 d-none" id="overnightCheckOutTimeBox">
                                    <label class="form-label">Giờ trả (tùy chọn)</label>
                                    <select name="check_out_time" id="overnightCheckOutTime"
                                        class="form-select <?php $__errorArgs = ['check_out_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" disabled>
                                        <option value="">-- Mặc định <?php echo e($policyUi['standardCheckOut']); ?> --</option>
                                        <option value="<?php echo e($policyUi['lateTier1End']); ?>" <?php if(old('check_out_time', $bookingPrefill['check_out_time'] ?? null) == $policyUi['lateTier1End']): echo 'selected'; endif; ?>><?php echo e($policyUi['lateTier1End']); ?> (Phụ thu <?php echo e($policyUi['latePercent1']); ?>%)</option>
                                        <option value="<?php echo e($policyUi['lateTier2End']); ?>" <?php if(old('check_out_time', $bookingPrefill['check_out_time'] ?? null) == $policyUi['lateTier2End']): echo 'selected'; endif; ?>><?php echo e($policyUi['lateTier2End']); ?> (Phụ thu <?php echo e($policyUi['latePercent2']); ?>%)</option>
                                        <option value="<?php echo e($policyUi['lateTier3End']); ?>" <?php if(old('check_out_time', $bookingPrefill['check_out_time'] ?? null) == $policyUi['lateTier3End']): echo 'selected'; endif; ?>><?php echo e($policyUi['lateTier3End']); ?> (Phụ thu <?php echo e($policyUi['latePercent3']); ?>%)</option>
                                        <option value="<?php echo e($policyUi['lateFullFrom']); ?>" <?php if(old('check_out_time', $bookingPrefill['check_out_time'] ?? null) == $policyUi['lateFullFrom']): echo 'selected'; endif; ?>><?php echo e($policyUi['lateFullFrom']); ?> (Phụ thu <?php echo e($policyUi['latePercentFull']); ?>%)</option>
                                    </select>

                                    <?php $__errorArgs = ['check_out_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                    <div class="booking-help-text mt-1">
                                    </div>
                                </div>

                                <div class="col-md-3 d-none" id="hourlyCheckOutTimeBox">
                                    <label class="form-label">Giờ ra <span class="text-danger">*</span></label>

                                    <input type="text" name="check_out_time" id="hourlyCheckOutTime"
                                        class="form-control <?php $__errorArgs = ['check_out_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('check_out_time', $bookingPrefill['check_out_time'] ?? null)); ?>" placeholder="Ví dụ: 16:30"
                                        <?php if(old('booking_type', $bookingPrefill['booking_type'] ?? 'overnight') !== 'hourly'): echo 'disabled'; endif; ?>>

                                    <?php $__errorArgs = ['check_out_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                    <div class="booking-help-text mt-1">
                                        Chọn cùng với ngày trả. Có thể trả sang ngày hôm sau, ví dụ vào 23:00 và ra 06:00.
                                    </div>
                                </div>


                                <div class="col-md-12 d-none" id="hourlyPreviewWrapper">
                                    <div class="hourly-preview-box" id="hourlyPreviewBox">
                                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                                            <div>
                                                <strong>Dự kiến thuê theo giờ</strong>
                                            </div>
                                            <span class="badge bg-primary" id="hourlyPreviewBadge">Đang tính</span>
                                        </div>

                                        <div class="hourly-preview-grid">

                                            <div class="small fw-semibold mb-2 d-none" id="hourlyPreviewStockText">
                                                Còn -- phòng trống trong khung giờ đã chọn.
                                            </div>
                                            <div class="hourly-preview-item">
                                                <span>Nhận phòng</span>
                                                <strong id="hourlyPreviewCheckIn">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Trả phòng dự kiến</span>
                                                <strong id="hourlyPreviewCheckOut">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Dọn phòng đến</span>
                                                <strong id="hourlyPreviewCleaningUntil">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Tiền phòng</span>
                                                <strong id="hourlyPreviewRoomFee">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Tổng tạm tính</span>
                                                <strong id="hourlyPreviewTotalFee">---</strong>
                                            </div>
                                        </div>

                                        <div class="booking-help-text mt-2" id="hourlyPreviewMessage"></div>
                                    </div>
                                </div>

                                <div class="col-md-12 d-none" id="lowStockConfirmWrapper">
                                    <div class="alert alert-warning small mb-0">
                                        <div class="form-check">
                                            <input type="checkbox" name="confirm_low_stock" value="1" id="confirmLowStock"
                                                class="form-check-input" <?php if(old('confirm_low_stock')): echo 'checked'; endif; ?>>

                                            <label for="confirmLowStock" class="form-check-label fw-semibold">
                                                Tôi xác nhận vẫn tạo booking ở ngay theo giờ dù hạng phòng này đang còn rất
                                                ít phòng trống.
                                            </label>
                                        </div>

                                    </div>
                                </div>


                                <div class="col-md-12 d-none" id="walkInOvernightPolicyWrapper">
                                    <div class="hourly-preview-box safe" id="walkInOvernightPolicyBox">
                                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                                            <div>
                                                <strong>Dự kiến ở ngay qua đêm</strong>
                                            </div>
                                            <span class="badge bg-success" id="walkInOvernightPolicyBadge">Đang tính</span>
                                        </div>

                                        <div class="hourly-preview-grid">
                                            <div class="hourly-preview-item">
                                                <span>Nhận phòng thực tế</span>
                                                <strong id="walkInPolicyCheckIn">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Trả phòng dự kiến</span>
                                                <strong id="walkInPolicyCheckOut">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Phụ thu theo ca</span>
                                                <strong id="walkInPolicyExtraFee">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Tiền phòng</span>
                                                <strong id="walkInPolicyBaseFee">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Tổng tạm tính</span>
                                                <strong id="walkInPolicyTotalFee">---</strong>
                                            </div>
                                        </div>

                                        <div class="booking-help-text mt-2" id="walkInPolicyMessage"></div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Người lớn <span class="text-danger">*</span></label>
                                    <input type="number" name="adult_count" id="adultCount"
                                        class="form-control <?php $__errorArgs = ['adult_count'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('adult_count', $bookingPrefill['adult_count'] ?? 1)); ?>" min="1" max="60" required>

                                    <?php $__errorArgs = ['adult_count'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Trẻ em</label>
                                    <input type="number" name="child_count" id="childCount"
                                        class="form-control <?php $__errorArgs = ['child_count'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('child_count', $bookingPrefill['child_count'] ?? 0)); ?>" min="0">

                                    <?php $__errorArgs = ['child_count'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Số phòng <span class="text-danger">*</span></label>
                                    <input type="number" name="room_quantity" id="roomQuantity"
                                        class="form-control <?php $__errorArgs = ['room_quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('room_quantity', $bookingPrefill['room_quantity'] ?? 1)); ?>" min="1" max="<?php echo e((int) app(\App\Services\HotelPolicyService::class)->get('booking.max_online_rooms', 30)); ?>" required>

                                    <?php $__errorArgs = ['room_quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="col-md-3 adjacent-room-box" id="adjacentRoomBox">
                                    <label class="form-label d-block">Tùy chọn phòng</label>

                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="prefer_adjacent_rooms" value="1"
                                            id="preferAdjacentRooms" class="form-check-input"
                                            <?php if(old('prefer_adjacent_rooms')): echo 'checked'; endif; ?>>

                                        <label for="preferAdjacentRooms" class="form-check-label">
                                            Ưu tiên phòng cạnh nhau
                                        </label>
                                    </div>

                                </div>

                                <div class="col-12">
                                    <div class="border rounded-3 p-3 bg-light-subtle">
                                        <div class="fw-semibold mb-2">Cách phân phòng</div>

                                        <div class="d-flex flex-wrap gap-3">
                                            <label class="form-check mb-0">
                                                <input class="form-check-input" type="radio" name="room_selection_mode" value="automatic"
                                                    <?php if(old('room_selection_mode', 'automatic') === 'automatic'): echo 'checked'; endif; ?>>
                                                <span class="form-check-label">Hệ thống tự chọn phòng</span>
                                            </label>
                                            <label class="form-check mb-0">
                                                <input class="form-check-input" type="radio" name="room_selection_mode" value="manual"
                                                    <?php if(old('room_selection_mode') === 'manual'): echo 'checked'; endif; ?>>
                                                <span class="form-check-label">Lễ tân chọn phòng thủ công</span>
                                            </label>
                                        </div>

                                        <div id="manualRoomSelectionBox" class="mt-3 <?php echo e(old('room_selection_mode') === 'manual' ? '' : 'd-none'); ?>">
                                            <label for="roomSelectionRequest" class="form-label">Yêu cầu cụ thể của khách <span class="text-muted">(nếu có)</span></label>
                                            <textarea id="roomSelectionRequest" name="room_selection_request" rows="3"
                                                class="form-control <?php $__errorArgs = ['room_selection_request'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                placeholder="Ví dụ: khách muốn tầng cao, yên tĩnh, xa thang máy; nếu còn thì ưu tiên phòng 605..."><?php echo e(old('room_selection_request')); ?></textarea>
                                            <?php $__errorArgs = ['room_selection_request'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                            <div class="booking-help-text mt-1">Chỉ thu phí đảm bảo <?php echo e(number_format($manualRoomSelectionFee, 0, ',', '.')); ?>đ/phòng khi khách có yêu cầu cụ thể và khách sạn đáp ứng; lễ tân tự chọn phòng để vận hành thì không thu phí.</div>

                                            <div class="mt-3">
                                                <label for="manualRoomIds" class="form-label">Chọn phòng ngay (không bắt buộc)</label>
                                                <select id="manualRoomIds" name="manual_room_ids[]" class="d-none" multiple
                                                    data-old-values='<?php echo json_encode(array_map("intval", (array) old("manual_room_ids", []))) ?>'></select>
                                                <div id="manualRoomChecklist" class="border rounded-3 bg-white p-2" style="max-height:260px;overflow:auto">
                                                    <div class="text-muted small p-2">Chọn hạng phòng và thời gian để tải các phòng thực sự còn trống.</div>
                                                </div>
                                                <div class="booking-help-text mt-1" id="manualRoomHelp">Có thể tick nhiều phòng; hệ thống chỉ cho hoàn tất khi đủ đúng số phòng của booking.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <div class="booking-form-card">

                            <h5>Thanh toán và ghi chú</h5>

                            <div class="row g-3">

                                <div class="col-md-4">
                                    <label class="form-label">Phương thức thanh toán</label>
                                    <select name="payment_method" id="paymentMethod"
                                        class="form-select <?php $__errorArgs = ['payment_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                        <option value="cash" <?php echo e(old('payment_method', 'cash') == 'cash' ? 'selected' : ''); ?>>
                                            Tiền mặt tại quầy
                                        </option>
                                        <option value="bank_transfer" <?php echo e(old('payment_method') == 'bank_transfer' ? 'selected' : ''); ?>>
                                            Chuyển khoản tại quầy
                                        </option>
                                        <option value="vnpay" <?php echo e(old('payment_method') == 'vnpay' ? 'selected' : ''); ?>>
                                            Online VNPay
                                        </option>
                                    </select>

                                    <?php $__errorArgs = ['payment_method'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                    <div class="booking-help-text mt-1">
                                        <?php($policy = app(\App\Services\HotelPolicyService::class))
                                        Booking cọc theo số lượng phòng: 1 phòng {{ $policy->depositPercentForRooms(1) }}%, 2 phòng {{ $policy->depositPercentForRooms(2) }}%, 3 phòng {{ $policy->depositPercentForRooms(3) }}%, 4 phòng {{ $policy->depositPercentForRooms(4) }}%, từ 5 phòng {{ $policy->depositPercentForRooms(5) }}%.
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Kiểu thanh toán</label>
                                    <select name="payment_type" id="paymentType"
                                        class="form-select @error('payment_type') is-invalid @enderror">
                                        <option value="deposit_30" {{ old('payment_type', 'deposit_30') == 'deposit_30' ? 'selected' : '' }}>
                                            Thu cọc theo mức của số phòng
                                        </option>
                                    </select>

                                    @error('payment_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="booking-help-text mt-1" id="paymentTypeHelp">
                                    </div>
                                </div>

                                <div class="col-md-4 d-none" id="customPaymentAmountBox">
                                    <label class="form-label">Số tiền thu thực tế</label>
                                    <input type="number" name="deposit_amount" id="depositAmount"
                                        class="form-control @error('deposit_amount') is-invalid @enderror"
                                        value="{{ old('deposit_amount', 0) }}" min="0" step="1000">

                                    @error('deposit_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="booking-help-text mt-1" id="paymentAmountHelp">
                                        Chỉ nhập khi chọn kiểu "Nhập số tiền thực thu".
                                    </div>
                                </div>

                                <div class="col-md-12" id="counterPaymentConfirmBox">
                                    <div class="form-check booking-payment-confirm">
                                        <input class="form-check-input @error('confirm_counter_payment') is-invalid @enderror"
                                            type="checkbox" name="confirm_counter_payment" value="1"
                                            id="confirmCounterPayment" {{ old('confirm_counter_payment') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="confirmCounterPayment" id="confirmCounterPaymentLabel">
                                            Tôi xác nhận đã nhận đủ mức cọc bắt buộc theo số lượng phòng của khách tại quầy.
                                        </label>
                                        @error('confirm_counter_payment')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <div class="booking-help-text mt-1" id="counterPaymentConfirmHelp">
                                            Chỉ được tạo booking sau khi lễ tân đã kiểm tra và nhận đúng số tiền cần thu.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="booking-total-box">
                                        <div class="booking-help-text">Tổng tiền tạm tính</div>
                                        <strong id="estimatedTotalText">0đ</strong>
                                        <div class="booking-help-text mt-1" id="nightCountText">
                                            Chọn hạng phòng và ngày lưu trú để tính tiền.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Ghi chú</label>
                                    <textarea name="note" rows="4" class="form-control @error('note') is-invalid @enderror"
                                        placeholder="Ví dụ: khách muốn tầng thấp, đến muộn, cần hỗ trợ hành lý...">{{ old('note') }}</textarea>

                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="col-lg-4">

                        <div class="booking-form-card booking-action-card">
                            <h5>Hoàn tất booking</h5>
                            <p class="booking-help-text mb-3">
                                Kiểm tra tổng tiền và thông tin chính ở cột bên trái trước khi tạo đơn.
                            </p>

                            <button type="submit" class="btn btn-gold w-100">
                                Tạo booking
                            </button>

                            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary w-100 mt-2">
                                Hủy
                            </a>
                        </div>

                        <div class="booking-form-card">

                            <details class="booking-options-details" {{ old('services') ? 'open' : '' }}>
                                <summary>
                                    <span>
                                        Dịch vụ đặt trước
                                        <span class="d-block booking-help-text fw-normal mt-1">Chỉ mở khi khách có nhu cầu thêm dịch vụ.</span>
                                    </span>
                                </summary>

                                <div class="booking-options-body">
                                    @foreach ($services as $index => $service)
                                        <div class="border rounded p-2 mb-2 service-row" data-price="{{ $service->price }}"
                                            data-billing-rule="{{ $service->billing_rule ?: \App\Models\Service::BILLING_ONCE }}">

                                            <div class="form-check mb-2">
                                                <input type="checkbox" name="services[{{ $index }}][service_id]"
                                                    value="{{ $service->id }}" class="form-check-input service-check"
                                                    id="service{{ $service->id }}"
                                                    @checked((int) old("services.$index.service_id", 0) === (int) $service->id)>

                                                <label for="service{{ $service->id }}" class="form-check-label">
                                                    <strong>{{ $service->name }}</strong>
                                                    -
                                                    {{ number_format($service->price, 0, ',', '.') }}đ / {{ $service->unit }}
                                                    <span
                                                        class="badge bg-{{ ($service->service_group ?? '') == 'vehicle' ? 'dark' : (($service->type == 'minibar_order') ? 'warning text-dark' : 'primary') }}">
                                                        {{ $service->type === 'minibar_order' ? 'Minibar gọi thêm' : ($service->group_label ?? 'Dịch vụ') }}
                                                    </span>
                                                </label>
                                            </div>

                                            <div class="row g-2">
                                                <div class="col-4">
                                                    <input type="number" name="services[{{ $index }}][quantity]"
                                                        class="form-control form-control-sm service-quantity"
                                                        value="{{ old("services.$index.quantity", 1) }}" min="1">
                                                </div>

                                                <div class="col-8">
                                                    <input type="text" name="services[{{ $index }}][note]"
                                                        class="form-control form-control-sm" placeholder="Ghi chú nếu có"
                                                        value="{{ old("services.$index.note") }}">
                                                </div>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>

                                <div class="booking-total-box mt-3">
                                    <div class="booking-help-text">Tổng dịch vụ đặt trước</div>
                                    <strong id="serviceTotalText">0đ</strong>
                                </div>
                            </details>

                        </div>

                        <div class="booking-form-card">

                            <h5>Mã ưu đãi</h5>

                            <p class="booking-help-text">
                                Trường hợp khách đến sớm, hạng phòng cũ chưa sẵn sàng, cần đổi phòng/đổi hạng kèm ưu đãi
                                thì chọn mã thuộc loại <strong>mã hỗ trợ khách</strong>, không tạo thêm loại riêng.
                            </p>

                            @php
                                $promotionTypeDisplayConfig = [
                                    'normal_discount' => [
                                        'label' => 'Mã thường',
                                        'badge' => 'bg-primary',
                                        'hint' => 'Mã phổ thông dùng cho giảm tiền hoặc tặng/giảm dịch vụ cơ bản.',
                                        'limit' => 1,
                                        'rule' => 'Chọn tối đa 1 mã thường.',
                                    ],
                                    'event_discount' => [
                                        'label' => 'Mã sự kiện',
                                        'badge' => 'bg-success',
                                        'hint' => 'Mã theo chiến dịch, mùa lễ, combo hoặc chương trình bán hàng.',
                                        'limit' => 1,
                                        'rule' => 'Chọn tối đa 1 mã sự kiện.',
                                    ],
                                    'conditional_discount' => [
                                        'label' => 'Mã điều kiện',
                                        'badge' => 'bg-warning text-dark',
                                        'hint' => 'Mã chỉ áp dụng khi booking đạt điều kiện về tổng tiền, số đêm, số phòng hoặc lịch sử khách.',
                                        'limit' => 1,
                                        'rule' => 'Chọn tối đa 1 mã điều kiện.',
                                    ],
                                    'support_discount' => [
                                        'label' => 'Mã hỗ trợ khách',
                                        'badge' => 'bg-danger',
                                        'hint' => '',
                                        'limit' => null,
                                        'rule' => 'Có thể chọn nhiều mã hỗ trợ nếu từng mã cho phép dùng chung.',
                                    ],
                                ];

                                $availablePromotionGroups = collect($availablePromotions ?? collect())->groupBy('promotion_type');
                            ?>

                            <?php if(($availablePromotions ?? collect())->count() > 0): ?>
                                <details class="promotion-collapsible" <?php echo e(!empty(old('promotion_codes', [])) ? 'open' : ''); ?>>
                                    <summary>
                                        <span>
                                            Có <span id="eligiblePromotionCount">0</span> mã hiện đủ điều kiện
                                            <span class="promotion-selected-hint" id="adminSelectedPromotionCountText">
                                                Chưa chọn mã nào
                                            </span>
                                        </span>
                                        <span class="badge bg-light text-dark border">Bấm để xem / chọn</span>
                                    </summary>

                                    <div class="promotion-collapsible-body">
                                        <?php $__currentLoopData = $promotionTypeDisplayConfig; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promotionType => $typeConfig): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $groupPromotions = $availablePromotionGroups->get($promotionType, collect());
                                            ?>

                                            <?php if($groupPromotions->count() > 0): ?>
                                                <div class="mb-3" data-promotion-group data-promotion-type="<?php echo e($promotionType); ?>" data-promotion-limit="<?php echo e($typeConfig['limit'] ?? ''); ?>">
                                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                                        <div>
                                                            <div class="fw-bold">
                                                                <?php echo e($typeConfig['label']); ?>

                                                                <span class="badge <?php echo e($typeConfig['badge']); ?> ms-1" data-promotion-group-count>
                                                                    <?php echo e($groupPromotions->count()); ?>

                                                                </span>
                                                            </div>
                                                            <div class="promotion-meta">
                                                                <?php echo e($typeConfig['hint']); ?>

                                                            </div>
                                                            <div class="promotion-meta fw-semibold text-dark mt-1">
                                                                <?php echo e($typeConfig['rule']); ?>

                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="promotion-list">
                                                        <?php $__currentLoopData = $groupPromotions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promotion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <?php
                                                                $promotionDiscountText = $promotion->discount_type == 'percent'
                                                                    ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, ',', '.'), '0'), ',') . '%'
                                                                    : number_format((float) $promotion->discount_value, 0, ',', '.') . 'đ';

                                                                if ($promotion->discount_type == 'percent' && (float) $promotion->max_discount_amount > 0) {
                                                                    $promotionDiscountText .= ' - tối đa ' . number_format((float) $promotion->max_discount_amount, 0, ',', '.') . 'đ';
                                                                }


                                                                $promotionServiceOffersPayload = $promotion->serviceOffers
                                                                    ->map(function ($offer) {
                                                                        return [
                                                                            'service_id' => $offer->service_id,
                                                                            'service_name' => $offer->service->name ?? 'Dịch vụ',
                                                                            'service_unit' => $offer->service->unit ?? '',
                                                                            'service_price' => (float) ($offer->service->price ?? 0),
                                                                            'service_type' => $offer->service->type ?? 'service',
                                                                            'service_billing_rule' => $offer->service->billing_rule ?? \App\Models\Service::BILLING_ONCE,
                                                                            'discount_type' => $offer->discount_type,
                                                                            'discount_value' => (float) $offer->discount_value,
                                                                            'quantity' => (int) $offer->quantity,
                                                                            'auto_add_service' => (bool) $offer->auto_add_service,
                                                                        ];
                                                                    })
                                                                    ->values();

                                                                $promotionServiceOffersJson = $promotionServiceOffersPayload->toJson(
                                                                    JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                                                                );
                                                            ?>

                                                            <label class="promotion-card mb-0 d-none" data-promotion-card data-promotion-code="<?php echo e($promotion->code); ?>">
                                                                <div class="form-check">
                                                                    <input type="checkbox"
                                                                        name="promotion_codes[]"
                                                                        value="<?php echo e($promotion->code); ?>"
                                                                        class="form-check-input promotion-check"
                                                                        data-code="<?php echo e($promotion->code); ?>"
                                                                        data-type="<?php echo e($promotion->promotion_type); ?>"
                                                                        data-requires-note="<?php echo e($promotion->requires_note || $promotion->promotion_type == 'support_discount' ? 1 : 0); ?>"
                                                                        data-discount-type="<?php echo e($promotion->discount_type); ?>"
                                                                        data-discount-value="<?php echo e((float) $promotion->discount_value); ?>"
                                                                        data-max-discount="<?php echo e((float) $promotion->max_discount_amount); ?>"
                                                                        data-stackable="<?php echo e($promotion->is_stackable ? 1 : 0); ?>"
                                                                        data-service-offers="<?php echo e(e($promotionServiceOffersJson)); ?>"
                                                                        <?php if(in_array($promotion->code, old('promotion_codes', []))): echo 'checked'; endif; ?>>

                                                                    <div class="ms-1">
                                                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                                                            <div>
                                                                                <div class="promotion-code"><?php echo e($promotion->code); ?></div>
                                                                                <div class="fw-semibold"><?php echo e($promotion->name); ?></div>
                                                                            </div>
                                                                            <span class="badge <?php echo e($typeConfig['badge']); ?>"><?php echo e($typeConfig['label']); ?></span>
                                                                        </div>

                                                                        <div class="promotion-meta mt-1">
                                                                            Giảm <?php echo e($promotionDiscountText); ?>

                                                                            <?php if((float) $promotion->min_booking_amount > 0): ?>
                                                                                · Đơn từ <?php echo e(number_format((float) $promotion->min_booking_amount, 0, ',', '.')); ?>đ
                                                                            <?php endif; ?>
                                                                            <?php if((int) $promotion->min_nights > 0): ?>
                                                                                · Từ <?php echo e((int) $promotion->min_nights); ?> đêm
                                                                            <?php endif; ?>
                                                                            <?php if((int) $promotion->min_rooms > 0): ?>
                                                                                · Từ <?php echo e((int) $promotion->min_rooms); ?> phòng
                                                                            <?php endif; ?>
                                                                            <?php if($promotion->requires_note || $promotion->promotion_type == 'support_discount'): ?>
                                                                                · Cần nhập lý do
                                                                            <?php endif; ?>
                                                                            · <?php echo e($promotion->is_stackable ? 'Có thể dùng chung' : 'Chỉ dùng một mình'); ?>

                                                                        </div>

                                                                        <?php if($promotion->serviceOffers->count() > 0): ?>
                                                                            <div class="promotion-meta mt-1 text-success">
                                                                                Dịch vụ ưu đãi:
                                                                                <?php echo e($promotion->serviceOffers->map(fn ($offer) => $offer->offer_label)->implode(' · ')); ?>

                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <?php if($promotion->roomUpgradeOffers->count() > 0): ?>
                                                                            <div class="promotion-meta mt-1 text-primary">
                                                                                Nâng hạng:
                                                                                <?php echo e($promotion->roomUpgradeOffers->map(fn ($offer) => $offer->kind_label . ' - ' . $offer->cover_label)->implode(' · ')); ?>

                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                        <div class="mt-3">
                                            <label class="form-label">
                                                Lý do hỗ trợ nếu chọn mã hỗ trợ khách
                                                <span class="text-danger" id="promotionNoteRequiredMark" style="display:none">*</span>
                                            </label>
                                            <textarea name="promotion_note" id="promotionNote" rows="3" class="form-control"
                                                placeholder="Ví dụ: khách đến sớm nhưng hạng phòng chưa sẵn, hỗ trợ đổi hạng và tặng dịch vụ."><?php echo e(old('promotion_note')); ?></textarea>
                                            <div class="booking-help-text mt-1">
                                                Bắt buộc khi chọn mã hỗ trợ khách hoặc mã được cấu hình yêu cầu lý do.
                                            </div>
                                        </div>
                                    </div>
                                </details>

                                <div class="booking-total-box mt-3">
                                    <div class="promotion-total-row">
                                        <span>Tổng trước giảm</span>
                                        <strong id="promotionSubtotalText">0đ</strong>
                                    </div>
                                    <div class="promotion-total-row text-success">
                                        <span>Ưu đãi</span>
                                        <strong id="promotionDiscountText">-0đ</strong>
                                    </div>
                                    <div class="promotion-total-row text-danger">
                                        <span>Sau ưu đãi</span>
                                        <strong id="promotionFinalText">0đ</strong>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light border mb-0">
                                    Chưa có mã ưu đãi đang hoạt động.
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>

            </form>


            <?php if($adjacentRoomWarning): ?>
                <div class="modal fade" id="adjacentRoomFallbackModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header border-0 pb-0">
                                <div>
                                    <span class="badge text-bg-warning mb-2">Không đủ toàn bộ phòng liền kề</span>
                                    <h4 class="modal-title fw-bold">Xác nhận cách xếp phòng</h4>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                            </div>
                            <div class="modal-body pt-3">
                                <p class="mb-3">
                                    Hạng <strong><?php echo e($adjacentRoomWarning['category_name'] ?? 'đã chọn'); ?></strong>
                                    còn đủ <strong><?php echo e($adjacentRoomWarning['requested_quantity'] ?? 0); ?> phòng</strong>,
                                    nhưng không có đủ <?php echo e($adjacentRoomWarning['requested_quantity'] ?? 0); ?> phòng nằm liền nhau.
                                </p>

                                <div class="alert alert-info mb-3">
                                    Có thể ghép tối đa
                                    <strong><?php echo e($adjacentRoomWarning['max_adjacent_count'] ?? 1); ?> phòng cạnh nhau</strong>
                                    và bố trí các phòng còn lại gần nhất có thể.
                                </div>

                                <div class="border rounded-3 overflow-hidden">
                                    <?php $__currentLoopData = ($adjacentRoomWarning['lines'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="d-flex justify-content-between gap-3 px-3 py-2 <?php echo e(!$loop->last ? 'border-bottom' : ''); ?>">
                                            <div>
                                                <strong><?php echo e($line['label'] ?? 'Phòng'); ?></strong>
                                                <div class="small text-muted">
                                                    Tầng <?php echo e($line['floor'] ?? '---'); ?>

                                                </div>
                                            </div>
                                            <div class="fw-semibold text-end">
                                                <?php echo e(implode(', ', $line['rooms'] ?? [])); ?>

                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>

                            </div>
                            <div class="modal-footer border-0 pt-0 d-grid gap-2">
                                <button type="button" class="btn btn-primary" id="confirmAdjacentFallbackButton">
                                    <i class="bx bx-check me-1"></i>
                                    Xác nhận dùng phương án này
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="continueWithoutAdjacentButton">
                                    Bỏ ưu tiên phòng cạnh nhau và tiếp tục
                                </button>
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    Đóng để đổi hạng phòng
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const policy = <?php echo json_encode($policyUi, 15, 512) ?>;
            const timeToMinutes = (value) => {
                const [hour, minute] = String(value || '00:00').split(':').map(Number);
                return (hour * 60) + minute;
            };
            const percentLabel = (value) => Number(value).toLocaleString('vi-VN', { maximumFractionDigits: 2 });
            const bookingMode = document.getElementById('bookingMode');
            const bookingModeHelpText = document.getElementById('bookingModeHelpText');
            const bookingType = document.getElementById('bookingType');
            const bookingTypeHelpText = document.getElementById('bookingTypeHelpText');
            const roomCategorySelect = document.getElementById('roomCategorySelect');
            const bookingCreateForm = document.getElementById('bookingCreateForm');
            const confirmAdjacentFallback = document.getElementById('confirmAdjacentFallback');

            const checkInDate = document.getElementById('checkInDate');
            const checkOutDate = document.getElementById('checkOutDate');
            const checkOutDateBox = document.getElementById('checkOutDateBox');

            const checkInTime = document.getElementById('checkInTime');
            const checkInTimeBox = document.getElementById('checkInTimeBox');

            const hourlyCheckOutTime = document.getElementById('hourlyCheckOutTime');
            const hourlyCheckOutTimeBox = document.getElementById('hourlyCheckOutTimeBox');
            const overnightCheckOutTimeBox = document.getElementById('overnightCheckOutTimeBox');
            const overnightCheckOutTime = document.getElementById('overnightCheckOutTime');
            const roomCategoryStockNote = document.getElementById('roomCategoryStockNote');

            const lowStockConfirmWrapper = document.getElementById('lowStockConfirmWrapper');
            const confirmLowStock = document.getElementById('confirmLowStock');

            const roomQuantity = document.getElementById('roomQuantity');
            const adjacentRoomBox = document.getElementById('adjacentRoomBox');
            const preferAdjacentRooms = document.getElementById('preferAdjacentRooms');

            const estimatedTotalText = document.getElementById('estimatedTotalText');
            const nightCountText = document.getElementById('nightCountText');

            const serviceRows = document.querySelectorAll('.service-row');
            const serviceTotalText = document.getElementById('serviceTotalText');

            const promotionChecks = document.querySelectorAll('.promotion-check');
            const customerEmail = document.getElementById('customerEmail');
            const customerPhone = document.getElementById('customerPhone');
            const customerCccd = document.getElementById('customerCccd');
            const eligiblePromotionCount = document.getElementById('eligiblePromotionCount');
            const eligiblePromotionsUrl = <?php echo json_encode(route('admin.bookings.eligible-promotions'), 15, 512) ?>;
            const customerAccountLookupUrl = <?php echo json_encode(route('admin.bookings.check-customer-account'), 15, 512) ?>;
            const customerAccountLookupNotice = document.getElementById('customerAccountLookupNotice');
            let customerAccountLookupTimer = null;
            let customerAccountLookupAbortController = null;
            let currentCustomerHasAccount = false;
            let promotionEligibilityTimer = null;
            let promotionEligibilityRequestSequence = 0;
            let promotionEligibilityAbortController = null;
            const promotionSubtotalText = document.getElementById('promotionSubtotalText');
            const promotionDiscountText = document.getElementById('promotionDiscountText');
            const promotionFinalText = document.getElementById('promotionFinalText');

            const paymentMethod = document.getElementById('paymentMethod');
            const paymentType = document.getElementById('paymentType');
            const depositAmount = document.getElementById('depositAmount');
            const customPaymentAmountBox = document.getElementById('customPaymentAmountBox');
            const paymentTypeHelp = document.getElementById('paymentTypeHelp');
            const paymentAmountHelp = document.getElementById('paymentAmountHelp');
            const counterPaymentConfirmBox = document.getElementById('counterPaymentConfirmBox');
            const confirmCounterPayment = document.getElementById('confirmCounterPayment');
            const confirmCounterPaymentLabel = document.getElementById('confirmCounterPaymentLabel');
            const counterPaymentConfirmHelp = document.getElementById('counterPaymentConfirmHelp');

            const hourlyPreviewWrapper = document.getElementById('hourlyPreviewWrapper');
            const hourlyPreviewBox = document.getElementById('hourlyPreviewBox');
            const hourlyPreviewBadge = document.getElementById('hourlyPreviewBadge');
            const hourlyPreviewCheckIn = document.getElementById('hourlyPreviewCheckIn');
            const hourlyPreviewCheckOut = document.getElementById('hourlyPreviewCheckOut');
            const hourlyPreviewCleaningUntil = document.getElementById('hourlyPreviewCleaningUntil');
            const hourlyPreviewRoomFee = document.getElementById('hourlyPreviewRoomFee');
            const hourlyPreviewTotalFee = document.getElementById('hourlyPreviewTotalFee');
            const hourlyPreviewMessage = document.getElementById('hourlyPreviewMessage');
            const hourlyPreviewStockText = document.getElementById('hourlyPreviewStockText');

            const walkInOvernightPolicyWrapper = document.getElementById('walkInOvernightPolicyWrapper');
            const walkInOvernightPolicyBox = document.getElementById('walkInOvernightPolicyBox');
            const walkInOvernightPolicyBadge = document.getElementById('walkInOvernightPolicyBadge');
            const walkInPolicyCheckIn = document.getElementById('walkInPolicyCheckIn');
            const walkInPolicyCheckOut = document.getElementById('walkInPolicyCheckOut');
            const walkInPolicyExtraFee = document.getElementById('walkInPolicyExtraFee');
            const walkInPolicyBaseFee = document.getElementById('walkInPolicyBaseFee');
            const walkInPolicyTotalFee = document.getElementById('walkInPolicyTotalFee');
            const walkInPolicyMessage = document.getElementById('walkInPolicyMessage');

            const cleaningBufferMinutes = 0;
            const hourlyInventoryCheckUrl = "<?php echo e(route('admin.bookings.hourly-inventory-check')); ?>";
            const roomCategoryAvailabilityUrl = "<?php echo e(route('admin.bookings.room-category-availability')); ?>";
            const manualRoomOptionsUrl = "<?php echo e(route('admin.bookings.manual-room-options')); ?>";
            const manualRoomSelectionFeePerRoom = <?php echo e(json_encode($manualRoomSelectionFee)); ?>;
            let categoryAvailabilityRequestId = 0;
            let categoryAvailabilityAbortController = null;
            let categoryAvailabilityTimer = null;

            const roomSelectionModeInputs = Array.from(document.querySelectorAll('input[name="room_selection_mode"]'));
            const manualRoomSelectionBox = document.getElementById('manualRoomSelectionBox');
            const roomSelectionRequest = document.getElementById('roomSelectionRequest');
            const manualRoomIds = document.getElementById('manualRoomIds');
            const manualRoomChecklist = document.getElementById('manualRoomChecklist');
            const manualRoomHelp = document.getElementById('manualRoomHelp');
            let manualRoomOptionsTimer = null;
            let manualRoomOptionsRequestId = 0;
            let manualRoomOptionsAbortController = null;

            function isManualRoomSelection() {
                return roomSelectionModeInputs.find((input) => input.checked)?.value === 'manual';
            }

            function calculateManualRoomSelectionFee() {
                if (!isManualRoomSelection() || !manualRoomIds) {
                    return 0;
                }
                const selectedCount = Array.from(manualRoomIds.selectedOptions || []).filter(option => option.value).length;
                const hasCustomerRequest = (roomSelectionRequest?.value || '').trim().length > 0;
                return hasCustomerRequest && selectedCount === getRoomQuantity()
                    ? manualRoomSelectionFeePerRoom * getRoomQuantity()
                    : 0;
            }

            function currentManualRoomOptionsPayload() {
                return {
                    booking_mode: bookingMode.value,
                    booking_type: bookingType.value,
                    room_category_id: roomCategorySelect?.value || '',
                    room_quantity: getRoomQuantity(),
                    check_in_date: checkInDate?.value || '',
                    check_out_date: checkOutDate?.value || '',
                    check_in_time: checkInTime?.value || '',
                    check_out_time: bookingType.value === 'hourly'
                        ? (hourlyCheckOutTime?.value || '')
                        : (overnightCheckOutTime?.value || ''),
                };
            }

            function sameManualRoomOptionsPayload(left, right) {
                return JSON.stringify(left) === JSON.stringify(right);
            }

            async function refreshManualRoomOptions() {
                if (!manualRoomIds || !isManualRoomSelection()) {
                    if (manualRoomOptionsAbortController) {
                        manualRoomOptionsAbortController.abort();
                        manualRoomOptionsAbortController = null;
                    }
                    return;
                }

                const payload = currentManualRoomOptionsPayload();
                if (!payload.room_category_id || !payload.check_in_date) {
                    if (manualRoomOptionsAbortController) {
                        manualRoomOptionsAbortController.abort();
                        manualRoomOptionsAbortController = null;
                    }
                    manualRoomIds.innerHTML = '';
                    if (manualRoomChecklist) manualRoomChecklist.innerHTML = '<div class="text-muted small p-2">Chọn hạng phòng và thời gian để tải các phòng thực sự còn trống.</div>';
                    if (manualRoomHelp) {
                        manualRoomHelp.textContent = 'Danh sách sẽ tự cập nhật khi chọn đủ hạng phòng và thời gian.';
                    }
                    return;
                }

                const requestId = ++manualRoomOptionsRequestId;
                if (manualRoomOptionsAbortController) {
                    manualRoomOptionsAbortController.abort();
                }
                manualRoomOptionsAbortController = new AbortController();

                const oldValues = new Set(JSON.parse(manualRoomIds.dataset.oldValues || '[]').map(String));
                const selectedValues = new Set(Array.from(manualRoomIds.selectedOptions).map((option) => option.value));
                const body = new URLSearchParams(payload);

                try {
                    manualRoomIds.disabled = true;
                    if (manualRoomHelp) {
                        manualRoomHelp.textContent = 'Đang tải các phòng thực sự còn trống...';
                    }

                    const response = await fetch(manualRoomOptionsUrl, {
                        method: 'POST',
                        signal: manualRoomOptionsAbortController.signal,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        },
                        body,
                    });
                    const json = await response.json();
                    if (!response.ok) {
                        throw new Error(json.message || 'Không tải được phòng trống.');
                    }

                    if (
                        requestId !== manualRoomOptionsRequestId
                        || !sameManualRoomOptionsPayload(payload, currentManualRoomOptionsPayload())
                    ) {
                        return;
                    }

                    manualRoomIds.innerHTML = '';
                    if (manualRoomChecklist) manualRoomChecklist.innerHTML = '';
                    if (!Array.isArray(json.rooms) || json.rooms.length === 0) {
                        if (manualRoomChecklist) manualRoomChecklist.innerHTML = '<div class="text-muted small p-2">Không còn phòng phù hợp trong khoảng thời gian này.</div>';
                    } else {
                        json.rooms.forEach((room) => {
                            const option = document.createElement('option');
                            option.value = String(room.id);
                            option.textContent = `Phòng ${room.room_number}`;
                            option.selected = selectedValues.has(option.value) || oldValues.has(option.value);
                            manualRoomIds.appendChild(option);

                            if (manualRoomChecklist) {
                                const label = document.createElement('label');
                                label.className = 'form-check d-flex align-items-center gap-2 px-2 py-2 mb-1 rounded border';
                                const checkbox = document.createElement('input');
                                checkbox.type = 'checkbox'; checkbox.className = 'form-check-input m-0';
                                checkbox.value = option.value; checkbox.checked = option.selected;
                                checkbox.addEventListener('change', () => {
                                    option.selected = checkbox.checked;
                                    const chosen = Array.from(manualRoomIds.selectedOptions).filter(o => o.value).length;
                                    const required = getRoomQuantity();
                                    if (chosen > required) {
                                        checkbox.checked = false; option.selected = false;
                                        if (manualRoomHelp) manualRoomHelp.textContent = `Chỉ được chọn đúng ${required} phòng cho booking này.`;
                                    } else if (manualRoomHelp) {
                                        manualRoomHelp.textContent = `Đã chọn ${chosen}/${required} phòng. Chỉ các phòng còn trống trong toàn bộ thời gian lưu trú mới xuất hiện.`;
                                    }
                                    updateEstimatedTotal();
                                });
                                const text = document.createElement('span');
                                text.innerHTML = `<strong>Phòng ${room.room_number}</strong> <span class="text-muted small">· Tầng ${room.floor_number ?? '—'} · ${room.category_name ?? ''}</span>`;
                                label.appendChild(checkbox); label.appendChild(text); manualRoomChecklist.appendChild(label);
                            }
                        });
                    }

                    if (manualRoomHelp) {
                        manualRoomHelp.textContent = `Có ${json.rooms?.length || 0} phòng phù hợp. Nếu chọn ngay, hãy chọn đúng ${getRoomQuantity()} phòng.`;
                    }
                    manualRoomIds.dataset.oldValues = '[]';
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                    if (
                        requestId !== manualRoomOptionsRequestId
                        || !sameManualRoomOptionsPayload(payload, currentManualRoomOptionsPayload())
                    ) {
                        return;
                    }
                    if (manualRoomHelp) {
                        manualRoomHelp.textContent = error.message || 'Không tải được danh sách phòng.';
                    }
                } finally {
                    if (requestId === manualRoomOptionsRequestId) {
                        manualRoomIds.disabled = false;
                        updateEstimatedTotal();
                    }
                }
            }

            function queueManualRoomOptionsRefresh() {
                window.clearTimeout(manualRoomOptionsTimer);
                manualRoomOptionsTimer = window.setTimeout(refreshManualRoomOptions, 180);
            }

            function syncManualRoomSelection() {
                const manual = isManualRoomSelection();
                manualRoomSelectionBox?.classList.toggle('d-none', !manual);
                if (roomSelectionRequest) {
                    roomSelectionRequest.required = manual;
                }
                if (manual) {
                    queueManualRoomOptionsRefresh();
                }
            }

            roomSelectionModeInputs.forEach((input) => input.addEventListener('change', function () {
                syncManualRoomSelection();
                updateEstimatedTotal();
            }));
            manualRoomIds?.addEventListener('change', updateEstimatedTotal);
            [bookingMode, bookingType, roomCategorySelect, checkInDate, checkOutDate, checkInTime, hourlyCheckOutTime, overnightCheckOutTime, roomQuantity]
                .filter(Boolean)
                .forEach((input) => {
                    input.addEventListener('change', queueManualRoomOptionsRefresh);
                    input.addEventListener('input', queueManualRoomOptionsRefresh);
                });
            syncManualRoomSelection();

            function formatMoney(value) {
                return new Intl.NumberFormat('vi-VN').format(Math.max(0, Number(value || 0))) + 'đ';
            }

            function formatDateInput(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            }

            function addDays(dateValue, days) {
                const date = new Date(dateValue);
                date.setDate(date.getDate() + days);

                return formatDateInput(date);
            }

            function parseDateTime(dateValue, timeValue) {
                if (!dateValue || !timeValue) {
                    return null;
                }

                const parts = timeValue.split(':');
                const hour = String(parts[0] || '00').padStart(2, '0');
                const minute = String(parts[1] || '00').padStart(2, '0');

                return new Date(`${dateValue}T${hour}:${minute}:00`);
            }

            function formatDateTimeVn(date) {
                if (!date || Number.isNaN(date.getTime())) {
                    return '---';
                }

                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                const hour = String(date.getHours()).padStart(2, '0');
                const minute = String(date.getMinutes()).padStart(2, '0');

                return `${day}/${month}/${year} ${hour}:${minute}`;
            }

            function getSelectedRoomPrice() {
                if (!roomCategorySelect) {
                    return 0;
                }

                const selectedOption = roomCategorySelect.options[roomCategorySelect.selectedIndex];

                return selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
            }

            function getRoomQuantity() {
                return Math.max(1, parseInt(roomQuantity.value || 1));
            }

            function getCurrentGuestCount() {
                const adultInput = document.getElementById('adultCount');
                const childInput = document.getElementById('childCount');
                const adults = Math.max(1, parseInt(adultInput?.value || 1));
                const children = Math.max(0, parseInt(childInput?.value || 0));

                return Math.max(1, adults + children);
            }

            function getCurrentServiceNightCount() {
                if (bookingMode.value === 'walk_in' && bookingType.value === 'hourly') {
                    const checkInDateTime = parseDateTime(checkInDate.value, checkInTime.value);
                    const checkOutDateTime = parseDateTime(checkOutDate.value, hourlyCheckOutTime.value);

                    if (!checkInDateTime || !checkOutDateTime
                        || Number.isNaN(checkInDateTime.getTime())
                        || Number.isNaN(checkOutDateTime.getTime())) {
                        return 1;
                    }

                    if (checkOutDateTime < checkInDateTime) {
                        checkOutDateTime.setDate(checkOutDateTime.getDate() + 1);
                    }

                    const durationMinutes = Math.ceil((checkOutDateTime - checkInDateTime) / (1000 * 60));
                    const hourlyPrice = calculateWalkInHourlyPrice(
                        Math.max(0, getSelectedRoomPrice()),
                        getRoomQuantity(),
                        durationMinutes,
                        checkInDateTime,
                        checkOutDateTime
                    );

                    return hourlyPrice.effectiveType === 'overnight'
                        ? Math.max(1, parseInt(hourlyPrice.nightCount || 1))
                        : 1;
                }

                if (bookingMode.value === 'walk_in' && bookingType.value === 'overnight') {
                    const policy = calculateWalkInOvernightPolicy();
                    return Math.max(1, parseInt(policy?.nightCount || 1));
                }

                return Math.max(1, parseInt(calculateNightCount() || 1));
            }

            function serviceMultiplier(billingRule) {
                const nights = getCurrentServiceNightCount();
                const rooms = getRoomQuantity();
                const guests = getCurrentGuestCount();

                if (billingRule === 'per_night') return nights;
                if (billingRule === 'per_room') return rooms;
                if (billingRule === 'per_room_per_night') return rooms * nights;
                if (billingRule === 'per_guest') return guests;
                if (billingRule === 'per_guest_per_night') return guests * nights;
                return 1;
            }

            function billedServiceQuantity(baseQuantity, billingRule) {
                return Math.max(1, parseInt(baseQuantity || 1)) * serviceMultiplier(billingRule || 'once');
            }

            function calculateServiceTotal() {
                let total = 0;

                serviceRows.forEach(function (row) {
                    const checkbox = row.querySelector('.service-check');
                    const quantityInput = row.querySelector('.service-quantity');

                    if (!checkbox || !quantityInput || !checkbox.checked) {
                        return;
                    }

                    const price = parseFloat(row.dataset.price || 0);
                    const baseQuantity = Math.max(1, parseInt(quantityInput.value || 1));
                    const billingRule = row.dataset.billingRule || 'once';

                    total += price * billedServiceQuantity(baseQuantity, billingRule);
                });

                if (serviceTotalText) {
                    serviceTotalText.innerText = formatMoney(total);
                }

                return total;
            }

            function getSelectedServiceQuantity(serviceId) {
                let quantity = 0;

                serviceRows.forEach(function (row) {
                    const checkbox = row.querySelector('.service-check');
                    const quantityInput = row.querySelector('.service-quantity');

                    if (!checkbox || !quantityInput || !checkbox.checked) {
                        return;
                    }

                    if (String(checkbox.value) === String(serviceId)) {
                        quantity += billedServiceQuantity(
                            Math.max(1, parseInt(quantityInput.value || 1)),
                            row.dataset.billingRule || 'once'
                        );
                    }
                });

                return quantity;
            }

            function parseServiceOffers(checkbox) {
                try {
                    return JSON.parse(checkbox.dataset.serviceOffers || '[]');
                } catch (error) {
                    return [];
                }
            }

            function calculatePromotionTotals(roomTotal, serviceTotal) {
                let autoServiceTotal = 0;
                let serviceDiscount = 0;

                promotionChecks.forEach(function (checkbox) {
                    if (!checkbox.checked) {
                        return;
                    }

                    parseServiceOffers(checkbox).forEach(function (offer) {
                        const price = parseFloat(offer.service_price || 0);
                        const billingRule = offer.service_billing_rule || 'once';
                        const offerQuantity = Math.max(1, parseInt(offer.quantity || 1));
                        let applicableQuantity = Math.min(offerQuantity, getSelectedServiceQuantity(offer.service_id));
                        const missingQuantity = Math.max(0, offerQuantity - applicableQuantity);

                        if (missingQuantity > 0 && offer.auto_add_service) {
                            autoServiceTotal += price * missingQuantity * serviceMultiplier(billingRule);
                            applicableQuantity += missingQuantity;
                        }

                        if (applicableQuantity <= 0 || price <= 0) {
                            return;
                        }

                        const originalAmount = price * applicableQuantity;
                        let discountAmount = 0;

                        if (offer.discount_type === 'percent') {
                            discountAmount = Math.round(originalAmount * parseFloat(offer.discount_value || 0) / 100);
                        } else {
                            discountAmount = parseFloat(offer.discount_value || 0) * applicableQuantity;
                        }

                        serviceDiscount += Math.min(Math.max(0, discountAmount), originalAmount);
                    });
                });

                const subtotal = roomTotal + serviceTotal + autoServiceTotal;
                let moneyDiscount = 0;

                promotionChecks.forEach(function (checkbox) {
                    if (!checkbox.checked) {
                        return;
                    }

                    const discountType = checkbox.dataset.discountType;
                    const discountValue = parseFloat(checkbox.dataset.discountValue || 0);
                    const maxDiscount = parseFloat(checkbox.dataset.maxDiscount || 0);
                    let amount = 0;

                    if (discountType === 'percent') {
                        amount = Math.round(subtotal * discountValue / 100);

                        if (maxDiscount > 0) {
                            amount = Math.min(amount, maxDiscount);
                        }
                    } else {
                        amount = discountValue;
                    }

                    moneyDiscount += Math.max(0, amount);
                });

                const effectiveServiceDiscount = Math.min(subtotal, Math.max(0, serviceDiscount));
                const effectiveMoneyDiscount = Math.min(
                    Math.max(0, moneyDiscount),
                    Math.max(0, subtotal - effectiveServiceDiscount)
                );
                const totalDiscount = effectiveServiceDiscount + effectiveMoneyDiscount;

                if (promotionSubtotalText) {
                    promotionSubtotalText.innerText = formatMoney(subtotal);
                }

                if (promotionDiscountText) {
                    promotionDiscountText.innerText = '-' + formatMoney(totalDiscount);
                }

                if (promotionFinalText) {
                    promotionFinalText.innerText = formatMoney(Math.max(0, subtotal - totalDiscount));
                }

                return {
                    subtotal: subtotal,
                    moneyDiscount: effectiveMoneyDiscount,
                    serviceDiscount: effectiveServiceDiscount,
                    totalDiscount: totalDiscount,
                    finalTotal: Math.max(0, subtotal - totalDiscount),
                };
            }

            function calculateNightCount() {
                if (!checkInDate.value || !checkOutDate.value) {
                    return 0;
                }

                const checkIn = new Date(checkInDate.value);
                const checkOut = new Date(checkOutDate.value);
                const diffTime = checkOut - checkIn;
                const diffDays = diffTime / (1000 * 60 * 60 * 24);

                return diffDays > 0 ? diffDays : 0;
            }

            function getEarlyCheckInPolicy(dateTime) {
                const minutes = dateTime.getHours() * 60 + dateTime.getMinutes();
                const tier1 = timeToMinutes(policy.earlyTier1End);
                const tier2 = timeToMinutes(policy.earlyTier2End);
                const freeFrom = timeToMinutes(policy.earlyFreeFrom);
                const standard = timeToMinutes(policy.standardCheckIn);
                if (minutes < tier1) return { percent: policy.earlyPercent1 / 100, text: `Check-in trước ${policy.earlyTier1End}: phụ thu ${percentLabel(policy.earlyPercent1)}%.` };
                if (minutes < tier2) return { percent: policy.earlyPercent2 / 100, text: `Check-in ${policy.earlyTier1End}–${policy.earlyTier2End}: phụ thu ${percentLabel(policy.earlyPercent2)}%.` };
                if (minutes < freeFrom) return { percent: policy.earlyPercent3 / 100, text: `Check-in ${policy.earlyTier2End}–${policy.earlyFreeFrom}: phụ thu ${percentLabel(policy.earlyPercent3)}%.` };
                if (minutes < standard) return { percent: 0, text: `Check-in ${policy.earlyFreeFrom}–${policy.standardCheckIn}: miễn phụ thu nếu phòng sẵn sàng.` };
                return { percent: 0, text: `Check-in từ ${policy.standardCheckIn}: không phụ thu.` };
            }

            function getLateCheckOutPolicy(dateTime) {
                const minutes = dateTime.getHours() * 60 + dateTime.getMinutes();
                const freeUntil = timeToMinutes(policy.standardCheckOut) + Number(policy.lateFreeMinutes || 0);
                const tier1 = timeToMinutes(policy.lateTier1End);
                const tier2 = timeToMinutes(policy.lateTier2End);
                const tier3 = timeToMinutes(policy.lateTier3End);
                const full = timeToMinutes(policy.lateFullFrom);
                const formatMinutes = (total) => `${String(Math.floor(total / 60) % 24).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
                if (minutes <= freeUntil) return { percent: 0, text: `Trả đến ${formatMinutes(freeUntil)}: miễn phụ thu.` };
                if (minutes <= tier1) return { percent: policy.latePercent1 / 100, text: `Trả đến ${policy.lateTier1End}: phụ thu ${percentLabel(policy.latePercent1)}%.` };
                if (minutes <= tier2) return { percent: policy.latePercent2 / 100, text: `Trả đến ${policy.lateTier2End}: phụ thu ${percentLabel(policy.latePercent2)}%.` };
                if (minutes <= tier3) return { percent: policy.latePercent3 / 100, text: `Trả đến ${policy.lateTier3End}: phụ thu ${percentLabel(policy.latePercent3)}%.` };
                if (minutes < full) return { percent: policy.latePercent4 / 100, text: `Trả trước ${policy.lateFullFrom}: phụ thu ${percentLabel(policy.latePercent4)}%.` };
                return { percent: policy.latePercentFull / 100, text: `Trả từ ${policy.lateFullFrom}: phụ thu ${percentLabel(policy.latePercentFull)}%.` };
            }

            function calculateWalkInHourlyPrice(price, quantity, durationMinutes, checkInDateTime = null, checkOutDateTime = null) {
                const durationHours = Math.max(1, Math.ceil(durationMinutes / 60));

                if (durationMinutes > Number(policy.shortToOvernightHours) * 60 && checkInDateTime && checkOutDateTime) {
                    const startDay = new Date(checkInDateTime.getFullYear(), checkInDateTime.getMonth(), checkInDateTime.getDate());
                    const endDay = new Date(checkOutDateTime.getFullYear(), checkOutDateTime.getMonth(), checkOutDateTime.getDate());
                    const nightCount = Math.max(1, Math.round((endDay - startDay) / 86400000));
                    const early = getEarlyCheckInPolicy(checkInDateTime);
                    const late = getLateCheckOutPolicy(checkOutDateTime);
                    const chargedPercent = nightCount + early.percent + late.percent;

                    return {
                        durationHours,
                        chargedPercent,
                        amount: Math.round(price * quantity * chargedPercent),
                        policyText: 'Vượt ' + policy.shortToOvernightHours + ' giờ: tự động tính ' + nightCount + ' đêm. ' + early.text + ' ' + late.text,
                        effectiveType: 'overnight',
                        nightCount,
                        earlyPercent: early.percent,
                        latePercent: late.percent,
                    };
                }

                const baseHours = Number(policy.shortBaseHours);
                const baseRate = Number(policy.shortBasePercent) / 100;
                const extraRate = Number(policy.shortExtraHourPercent) / 100;
                const maxRate = Number(policy.shortMaxPercent) / 100;
                const chargedPercent = durationHours <= baseHours
                    ? baseRate
                    : Math.min(maxRate, baseRate + ((durationHours - baseHours) * extraRate));

                return {
                    durationHours,
                    chargedPercent,
                    amount: Math.round(price * quantity * chargedPercent),
                    policyText: durationHours <= baseHours
                        ? `${baseHours} giờ đầu = ${percentLabel(policy.shortBasePercent)}% giá qua đêm.`
                        : (chargedPercent >= maxRate
                            ? `Đạt ngưỡng ${percentLabel(policy.shortMaxPercent)}% giá qua đêm.`
                            : `${baseHours} giờ đầu = ${percentLabel(policy.shortBasePercent)}%, mỗi giờ tiếp theo +${percentLabel(policy.shortExtraHourPercent)}%.`),
                    effectiveType: 'hourly',
                };
            }

            function calculateWalkInOvernightPolicy() {
                const checkInDateTime = parseDateTime(checkInDate.value, checkInTime.value);
                const checkOutDateTime = parseDateTime(checkOutDate.value, policy.standardCheckOut);
                const price = getSelectedRoomPrice();
                const quantity = getRoomQuantity();

                if (!checkInDateTime || !checkOutDateTime
                    || Number.isNaN(checkInDateTime.getTime())
                    || Number.isNaN(checkOutDateTime.getTime())
                    || checkOutDateTime <= checkInDateTime) {
                    return null;
                }

                const earlyPolicy = getEarlyCheckInPolicy(checkInDateTime);
                const extraPercent = earlyPolicy.percent;
                let policyText = earlyPolicy.text;

                const startDay = new Date(checkInDate.value + 'T00:00:00');
                const endDay = new Date(checkOutDate.value + 'T00:00:00');
                const nightCount = Math.max(1, Math.round((endDay - startDay) / 86400000));
                const baseTotal = price * quantity * nightCount;
                const extraFee = Math.round(price * quantity * extraPercent);
                const total = baseTotal + extraFee;

                policyText += ' Khách ở ' + nightCount + ' đêm và tự động trả phòng lúc ' + policy.standardCheckOut + ' ngày ' + checkOutDate.value.split('-').reverse().join('/') + '.';

                return {
                    checkInDateTime,
                    checkOutDateTime,
                    nightCount,
                    extraPercent,
                    baseTotal,
                    extraFee,
                    total,
                    policyText,
                };
            }

            function setMinDates() {
                const today = formatDateInput(new Date());

                if (checkInDate) {
                    checkInDate.min = today;
                }

                if (!checkOutDate) {
                    return;
                }

                if (bookingMode.value === 'advance') {
                    checkOutDate.min = checkInDate.value ? addDays(checkInDate.value, 1) : today;
                    return;
                }

                checkOutDate.min = checkInDate.value || today;
            }

            function autoSetCheckoutDate() {
                if (!checkInDate.value || !checkOutDate) {
                    return;
                }

                if (bookingMode.value === 'advance') {
                    const minCheckoutDate = addDays(checkInDate.value, 1);

                    checkOutDate.min = minCheckoutDate;

                    if (!checkOutDate.value || checkOutDate.value <= checkInDate.value) {
                        checkOutDate.value = minCheckoutDate;
                    }

                    return;
                }

                // For walk-in overnight, default to next day
                if (bookingMode.value === 'walk_in' && bookingType.value === 'overnight') {
                    const minCheckoutDate = addDays(checkInDate.value, 1);
                    checkOutDate.min = minCheckoutDate;

                    if (!checkOutDate.value || checkOutDate.value <= checkInDate.value) {
                        checkOutDate.value = minCheckoutDate;
                    }

                    return;
                }

                // Walk-in theo giờ: giữ nguyên ngày ra mà lễ tân đã chọn.
                // Chỉ khởi tạo cùng ngày nhận khi ô ngày ra đang trống; tuyệt đối không
                // tự reset ngày ra về ngày nhận vì ca qua đêm (ví dụ 23:00 -> 06:00)
                // sẽ bị backend hiểu sai thành giờ ra trước giờ vào.
                checkOutDate.required = true;
                checkOutDate.min = checkInDate.value;

                if (!checkOutDate.value) {
                    checkOutDate.value = checkInDate.value;
                }
            }

            function updateBookingTypeUi() {
                const isAdvance = bookingMode.value === 'advance';
                const isWalkIn = bookingMode.value === 'walk_in';
                const isHourly = bookingType.value === 'hourly';
                const hourlyOption = bookingType.querySelector('option[value="hourly"]');

                if (isAdvance) {
                    bookingType.value = 'overnight';

                    if (hourlyOption) {
                        hourlyOption.disabled = true;
                    }

                    checkInTimeBox.classList.add('d-none');
                    hourlyCheckOutTimeBox.classList.add('d-none');
                    hourlyPreviewWrapper.classList.add('d-none');
                    walkInOvernightPolicyWrapper.classList.add('d-none');

                    checkOutDateBox.classList.remove('d-none');
                    checkOutDate.required = true;

                    if (hourlyCheckOutTime) {
                        hourlyCheckOutTime.required = false;
                        hourlyCheckOutTime.disabled = true;
                    }
                    if (overnightCheckOutTime) {
                        overnightCheckOutTime.disabled = true;
                    }

                    if (lowStockConfirmWrapper) {
                        lowStockConfirmWrapper.classList.add('d-none');
                    }

                    if (confirmLowStock) {
                        confirmLowStock.checked = false;
                    }

                    bookingModeHelpText.innerText = 'Đặt trước giữ phòng theo giờ chuẩn <?php echo e($policyUi['standardCheckIn']); ?> → <?php echo e($policyUi['standardCheckOut']); ?>.';
                    bookingTypeHelpText.innerText = `Đặt trước luôn là booking qua đêm; khách có thể nhận từ ${policy.earlyFreeFrom} nếu phòng đã sẵn sàng, hệ thống giữ phòng theo mốc ${policy.standardCheckIn}.`;
                    return;
                }

                if (hourlyOption) {
                    hourlyOption.disabled = false;
                }

                if (isWalkIn && isHourly) {
                    checkInTimeBox.classList.remove('d-none');
                    hourlyCheckOutTimeBox.classList.remove('d-none');
                    hourlyPreviewWrapper.classList.remove('d-none');
                    walkInOvernightPolicyWrapper.classList.add('d-none');

                    checkOutDateBox.classList.remove('d-none');
                    checkOutDate.required = true;

                    if (hourlyCheckOutTime) {
                        hourlyCheckOutTime.required = true;
                        hourlyCheckOutTime.disabled = false;
                    }
                    if (overnightCheckOutTime) {
                        overnightCheckOutTime.disabled = true;
                    }

                    if (hourlyCheckOutTime) {
                        if (!hourlyCheckOutTime.value && checkInTime.value) {
                            const parts = checkInTime.value.split(':');
                            const defaultOut = new Date();

                            defaultOut.setHours(parseInt(parts[0] || '0'), parseInt(parts[1] || '0'), 0, 0);
                            defaultOut.setHours(defaultOut.getHours() + 2);

                            const hour = String(defaultOut.getHours()).padStart(2, '0');
                            const minute = String(defaultOut.getMinutes()).padStart(2, '0');

                            hourlyCheckOutTime.value = `${hour}:${minute}`;
                        }
                    }

                    bookingModeHelpText.innerText = 'Ở ngay: hệ thống lấy giờ vào thực tế để tính giờ chiếm phòng.';
                    bookingTypeHelpText.innerText = '';
                    return;
                }

                checkInTimeBox.classList.remove('d-none');
                hourlyCheckOutTimeBox.classList.add('d-none');
                hourlyPreviewWrapper.classList.add('d-none');
                walkInOvernightPolicyWrapper.classList.add('d-none');
                overnightCheckOutTimeBox.classList.add('d-none');

                checkOutDateBox.classList.remove('d-none');
                checkOutDate.required = true;

                if (hourlyCheckOutTime) {
                    hourlyCheckOutTime.required = false;
                    hourlyCheckOutTime.disabled = true;
                }
                if (overnightCheckOutTime) {
                    overnightCheckOutTime.disabled = true;
                }

                if (lowStockConfirmWrapper) {
                    lowStockConfirmWrapper.classList.add('d-none');
                }

                if (confirmLowStock) {
                    confirmLowStock.checked = false;
                }

                bookingModeHelpText.innerText = `Ở ngay qua đêm: giờ nhận là giờ thực tế; ngày trả do lễ tân chọn và giờ trả luôn là ${policy.standardCheckOut}.`;
                bookingTypeHelpText.innerText = `Qua đêm ở ngay: chọn ngày nhận và ngày trả. Hệ thống tính đủ số đêm, cộng phụ thu nhận phòng sớm cho ngày đầu nếu có; tự động trả lúc ${policy.standardCheckOut}.`;
            }

            function updateAdjacentRoomBox() {
                const quantity = parseInt(roomQuantity.value || 1);

                if (quantity >= 2 && bookingType.value === 'overnight') {
                    adjacentRoomBox.style.display = 'block';
                } else {
                    adjacentRoomBox.style.display = 'none';

                    if (preferAdjacentRooms) {
                        preferAdjacentRooms.checked = false;
                    }
                }
            }

            function resetHourlyStockText() {
                if (!hourlyPreviewStockText) {
                    return;
                }

                hourlyPreviewStockText.className = 'small fw-semibold mb-2 d-none';
                hourlyPreviewStockText.innerText = 'Còn -- phòng trống trong khung giờ đã chọn.';
            }

            function updateHourlyStockText(data, selectedAvailable) {
                if (!hourlyPreviewStockText) {
                    return;
                }

                hourlyPreviewStockText.classList.remove('d-none');

                if (data.blocked || selectedAvailable <= 0) {
                    hourlyPreviewStockText.className = 'small fw-semibold mb-2 text-danger';
                    hourlyPreviewStockText.innerText = 'Hết phòng trong khung giờ đã chọn.';
                    return;
                }

                if (selectedAvailable === 1) {
                    hourlyPreviewStockText.className = 'small fw-semibold mb-2 text-danger';
                    hourlyPreviewStockText.innerText = 'Chỉ còn 1 phòng trống trong khung giờ đã chọn.';
                    return;
                }

                if (selectedAvailable === 2) {
                    hourlyPreviewStockText.className = 'small fw-semibold mb-2 text-warning';
                    hourlyPreviewStockText.innerText = 'Còn 2 phòng trống trong khung giờ đã chọn.';
                    return;
                }

                hourlyPreviewStockText.className = 'small fw-semibold mb-2 text-success';
                hourlyPreviewStockText.innerText = 'Còn ' + selectedAvailable + ' phòng trống trong khung giờ đã chọn.';
            }

            function updateHourlyPreview() {
                if (!hourlyPreviewWrapper || bookingMode.value !== 'walk_in' || bookingType.value !== 'hourly') {
                    if (lowStockConfirmWrapper) {
                        lowStockConfirmWrapper.classList.add('d-none');
                    }

                    if (confirmLowStock) {
                        confirmLowStock.checked = false;
                    }

                    resetHourlyStockText();
                    return;
                }

                const checkInDateTime = parseDateTime(checkInDate.value, checkInTime.value);
                const checkOutDateTime = parseDateTime(checkOutDate.value, hourlyCheckOutTime.value);

                hourlyPreviewBox.classList.remove('safe', 'warning');
                resetHourlyStockText();

                if (lowStockConfirmWrapper) {
                    lowStockConfirmWrapper.classList.add('d-none');
                }

                if (!checkInDateTime || Number.isNaN(checkInDateTime.getTime()) || !checkOutDateTime || Number.isNaN(checkOutDateTime.getTime())) {
                    hourlyPreviewCheckIn.innerText = '---';
                    hourlyPreviewCheckOut.innerText = '---';
                    hourlyPreviewCleaningUntil.innerText = '---';
                    hourlyPreviewRoomFee.innerText = '---';
                    hourlyPreviewTotalFee.innerText = '---';
                    hourlyPreviewBadge.className = 'badge bg-secondary';
                    hourlyPreviewBadge.innerText = 'Chưa đủ dữ liệu';
                    hourlyPreviewMessage.innerText = 'Chọn ngày, giờ vào và giờ ra để hệ thống tính tiền, thời gian dọn phòng và cảnh báo tồn kho.';
                    return;
                }

                if (checkOutDateTime.getTime() === checkInDateTime.getTime()) {
                    hourlyPreviewBadge.className = 'badge bg-danger';
                    hourlyPreviewBadge.innerText = 'Sai giờ';
                    hourlyPreviewMessage.innerText = 'Giờ ra phải khác giờ vào.';
                    return;
                }

                if (checkOutDateTime < checkInDateTime) {
                    checkOutDateTime.setDate(checkOutDateTime.getDate() + 1);
                }

                const durationMinutes = Math.ceil((checkOutDateTime - checkInDateTime) / (1000 * 60));

                if (durationMinutes < Number(policy.shortMinMinutes || 1)) {
                    hourlyPreviewBadge.className = 'badge bg-danger';
                    hourlyPreviewBadge.innerText = 'Quá ngắn';
                    hourlyPreviewMessage.innerText = 'Thời gian ở theo giờ phải tối thiểu ' + Number(policy.shortMinMinutes || 1) + ' phút.';
                    return;
                }

                const cleaningUntil = new Date(checkOutDateTime.getTime());
                cleaningUntil.setMinutes(cleaningUntil.getMinutes() + cleaningBufferMinutes);

                const price = getSelectedRoomPrice();
                const quantity = getRoomQuantity();
                const serviceTotal = calculateServiceTotal();
                const hourlyPrice = calculateWalkInHourlyPrice(price, quantity, durationMinutes, checkInDateTime, checkOutDateTime);

                hourlyPreviewCheckIn.innerText = formatDateTimeVn(checkInDateTime);
                hourlyPreviewCheckOut.innerText = formatDateTimeVn(checkOutDateTime);
                hourlyPreviewCleaningUntil.innerText = formatDateTimeVn(cleaningUntil);
                hourlyPreviewRoomFee.innerText = price > 0
                    ? formatMoney(hourlyPrice.amount) + (hourlyPrice.effectiveType === 'overnight' ? ' (tự động tính qua đêm)' : ' (' + Math.round(hourlyPrice.chargedPercent * 100) + '% giá đêm, làm tròn ' + hourlyPrice.durationHours + ' giờ)')
                    : 'Chọn hạng phòng';
                hourlyPreviewTotalFee.innerText = price > 0
                    ? formatMoney(hourlyPrice.amount + serviceTotal)
                    : '---';

                if (!roomCategorySelect.value || !checkInDate.value || !checkOutDate.value || !checkInTime.value || !hourlyCheckOutTime.value || !roomQuantity.value) {
                    hourlyPreviewBox.classList.add('warning');
                    hourlyPreviewBadge.className = 'badge bg-warning text-dark';
                    hourlyPreviewBadge.innerText = 'Cần kiểm tra';
                    hourlyPreviewMessage.innerText = 'Chọn đủ hạng phòng, ngày giờ vào, ngày giờ ra và số phòng để kiểm tra tồn kho.';
                    return;
                }

                hourlyPreviewBox.classList.add('warning');
                hourlyPreviewBadge.className = 'badge bg-warning text-dark';
                hourlyPreviewBadge.innerText = 'Đang kiểm tra...';
                hourlyPreviewMessage.innerText = 'Đang kiểm tra phòng trống thật trong khung giờ này.';

                fetch(hourlyInventoryCheckUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        room_category_id: roomCategorySelect.value,
                        check_in_date: checkInDate.value,
                        check_in_time: checkInTime.value,
                        check_out_date: checkOutDate.value,
                        check_out_time: hourlyCheckOutTime.value,
                        room_quantity: roomQuantity.value,
                    }),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Không kiểm tra được tồn kho.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        const selectedAvailable = Number(data.available_for_selected_period || 0);
                        const remainingOvernight = Number(data.remaining_after_hourly || 0);

                        hourlyPreviewMessage.innerText = data.message || 'Đã kiểm tra phòng trống theo khung giờ.';

                        if (data.check_in_at) {
                            hourlyPreviewCheckIn.innerText = data.check_in_at;
                        }

                        if (data.check_out_at) {
                            hourlyPreviewCheckOut.innerText = data.check_out_at;
                        }

                        if (data.occupied_until) {
                            hourlyPreviewCleaningUntil.innerText = data.occupied_until;
                        }

                        if (typeof data.room_fee !== 'undefined') {
                            hourlyPreviewRoomFee.innerText = formatMoney(data.room_fee)
                                + ' (' + Math.round((data.charged_percent || 0) * 100)
                                + '% giá đêm, làm tròn '
                                + (data.duration_hours || hourlyPrice.durationHours)
                                + ' giờ)';

                            hourlyPreviewTotalFee.innerText = formatMoney(Number(data.room_fee || 0) + serviceTotal);
                        }

                        updateHourlyStockText(data, selectedAvailable);

                        const mustConfirmLowStock = !data.blocked
                            && (
                                selectedAvailable === 1
                                || (data.affects_overnight && remainingOvernight <= 1)
                            );

                        if (mustConfirmLowStock && lowStockConfirmWrapper) {
                            lowStockConfirmWrapper.classList.remove('d-none');
                        } else if (lowStockConfirmWrapper) {
                            lowStockConfirmWrapper.classList.add('d-none');

                            if (confirmLowStock) {
                                confirmLowStock.checked = false;
                            }
                        }

                        if (data.blocked || selectedAvailable <= 0) {
                            hourlyPreviewBox.classList.remove('safe');
                            hourlyPreviewBox.classList.add('warning');
                            hourlyPreviewBadge.className = 'badge bg-danger';
                            hourlyPreviewBadge.innerText = 'Hết phòng';
                            return;
                        }

                        if (selectedAvailable === 1 || (data.affects_overnight && remainingOvernight <= 1)) {
                            hourlyPreviewBox.classList.remove('safe');
                            hourlyPreviewBox.classList.add('warning');
                            hourlyPreviewBadge.className = 'badge bg-danger';
                            hourlyPreviewBadge.innerText = 'Cấp thiết';
                            return;
                        }

                        if (selectedAvailable === 2 || (data.affects_overnight && remainingOvernight === 2)) {
                            hourlyPreviewBox.classList.remove('safe');
                            hourlyPreviewBox.classList.add('warning');
                            hourlyPreviewBadge.className = 'badge bg-warning text-dark';
                            hourlyPreviewBadge.innerText = 'Gần hết phòng';
                            return;
                        }

                        hourlyPreviewBox.classList.remove('warning');
                        hourlyPreviewBox.classList.add('safe');
                        hourlyPreviewBadge.className = 'badge bg-success';
                        hourlyPreviewBadge.innerText = 'An toàn';
                    })
                    .catch(function () {
                        hourlyPreviewBox.classList.remove('safe');
                        hourlyPreviewBox.classList.add('warning');
                        hourlyPreviewBadge.className = 'badge bg-danger';
                        hourlyPreviewBadge.innerText = 'Lỗi kiểm tra';
                        hourlyPreviewMessage.innerText = 'Không kiểm tra được tồn kho. Vui lòng thử lại hoặc bấm tạo để hệ thống kiểm tra lại ở backend.';
                    });
            }

            function updateWalkInOvernightPreview() {
                if (!walkInOvernightPolicyWrapper || bookingMode.value !== 'walk_in' || bookingType.value !== 'overnight') {
                    return;
                }

                const policy = calculateWalkInOvernightPolicy();

                walkInOvernightPolicyBox.classList.remove('safe', 'warning');

                if (!policy) {
                    walkInPolicyCheckIn.innerText = '---';
                    walkInPolicyCheckOut.innerText = '---';
                    walkInPolicyExtraFee.innerText = '---';
                    walkInPolicyBaseFee.innerText = '---';
                    walkInPolicyTotalFee.innerText = '---';
                    walkInOvernightPolicyBadge.className = 'badge bg-secondary';
                    walkInOvernightPolicyBadge.innerText = 'Chưa đủ dữ liệu';
                    walkInPolicyMessage.innerText = 'Chọn ngày nhận, giờ vào và hạng phòng để xem cách tính giá.';
                    return;
                }

                walkInPolicyCheckIn.innerText = formatDateTimeVn(policy.checkInDateTime);
                walkInPolicyCheckOut.innerText = formatDateTimeVn(policy.checkOutDateTime);
                walkInPolicyExtraFee.innerText = policy.extraPercent > 0
                    ? Math.round(policy.extraPercent * 100) + '% = ' + formatMoney(policy.extraFee)
                    : 'Không phụ thu';
                walkInPolicyBaseFee.innerText = formatMoney(policy.baseTotal) + ' (' + policy.nightCount + ' đêm)';
                walkInPolicyTotalFee.innerText = formatMoney(policy.total + calculateServiceTotal());

                walkInOvernightPolicyBox.classList.add(policy.extraPercent > 0 ? 'warning' : 'safe');
                walkInOvernightPolicyBadge.className = policy.extraPercent > 0
                    ? 'badge bg-warning text-dark'
                    : 'badge bg-success';
                walkInOvernightPolicyBadge.innerText = policy.extraPercent > 0 ? 'Có phụ thu' : 'Tiêu chuẩn';
                walkInPolicyMessage.innerText = policy.policyText;
            }

            function updateEstimatedTotal() {
                const serviceTotal = calculateServiceTotal();
                const price = getSelectedRoomPrice();
                const quantity = getRoomQuantity();

                let roomTotal = 0;
                let depositRoomTotal = 0;
                let summaryText = 'Chọn hạng phòng và thời gian lưu trú để tính tiền.';

                if (price > 0) {
                    if (bookingMode.value === 'walk_in' && bookingType.value === 'hourly') {
                        const checkInDateTime = parseDateTime(checkInDate.value, checkInTime.value);
                        const checkOutDateTime = parseDateTime(checkOutDate.value, hourlyCheckOutTime.value);

                        if (checkInDateTime && checkOutDateTime && !Number.isNaN(checkInDateTime.getTime()) && !Number.isNaN(checkOutDateTime.getTime())) {
                            if (checkOutDateTime < checkInDateTime) {
                                checkOutDateTime.setDate(checkOutDateTime.getDate() + 1);
                            }

                            const durationMinutes = Math.ceil((checkOutDateTime - checkInDateTime) / (1000 * 60));
                            const hourlyPrice = calculateWalkInHourlyPrice(price, quantity, durationMinutes, checkInDateTime, checkOutDateTime);

                            roomTotal = hourlyPrice.amount;
                            depositRoomTotal = hourlyPrice.effectiveType === 'overnight'
                                ? price * quantity * Math.max(1, parseInt(hourlyPrice.nightCount || 1))
                                : hourlyPrice.amount;
                            summaryText = hourlyPrice.effectiveType === 'overnight'
                                ? quantity + ' phòng, tự động tính qua đêm (' + hourlyPrice.nightCount + ' đêm + phụ thu sớm/muộn)'
                                : quantity + ' phòng x ở ngay theo giờ, làm tròn '
                                    + hourlyPrice.durationHours + ' giờ x '
                                    + Math.round(hourlyPrice.chargedPercent * 100) + '% giá/đêm';
                        }
                    } else if (bookingMode.value === 'walk_in' && bookingType.value === 'overnight') {
                        const policy = calculateWalkInOvernightPolicy();

                        if (policy) {
                            roomTotal = policy.total;
                            depositRoomTotal = policy.baseTotal;
                            summaryText = quantity + ' phòng x ' + policy.nightCount + ' đêm';

                            if (policy.extraPercent > 0) {
                                summaryText += ' + phụ thu nhận phòng sớm ' + Math.round(policy.extraPercent * 100) + '%';
                            }
                        }
                    } else {
                        const nights = calculateNightCount();

                        if (nights > 0) {
                            roomTotal = price * quantity * nights;
                            depositRoomTotal = roomTotal;
                            summaryText = quantity + ' phòng x ' + nights + ' đêm';
                        }
                    }
                }

                const promotionTotals = calculatePromotionTotals(roomTotal, serviceTotal);
                const manualSelectionFee = calculateManualRoomSelectionFee();
                const total = promotionTotals.finalTotal + manualSelectionFee;

                if (manualSelectionFee > 0) {
                    summaryText += ' + phí đảm bảo yêu cầu phòng ' + formatMoney(manualSelectionFee);
                }

                estimatedTotalText.innerText = formatMoney(total);
                updatePaymentUi(total, depositRoomTotal, promotionTotals.moneyDiscount);

                if (roomTotal <= 0 && serviceTotal > 0) {
                    nightCountText.innerText = 'Đã cộng dịch vụ đặt trước. Chọn hạng phòng và thời gian lưu trú để tính tiền phòng.';
                } else {
                    nightCountText.innerText = summaryText;
                }
            }

            function updatePaymentUi(total, roomTotalForDeposit = 0, moneyDiscount = 0) {
                if (!paymentMethod || !paymentType || !depositAmount) {
                    return;
                }

                const method = paymentMethod.value || 'cash';
                const type = paymentType.value || '';
                const customOption = paymentType.querySelector('option[value="custom"]');
                const depositBase = Math.max(
                    0,
                    Number(roomTotalForDeposit || 0) - Math.min(
                        Math.max(0, Number(roomTotalForDeposit || 0)),
                        Math.max(0, Number(moneyDiscount || 0))
                    )
                );
                const roomQtyForDeposit = Math.max(1, getRoomQuantity());
                const dynamicDepositPercent = roomQtyForDeposit >= 5 ? Number(policy.depositPercent5PlusRooms)
                    : roomQtyForDeposit === 4 ? Number(policy.depositPercent4Rooms)
                    : roomQtyForDeposit === 3 ? Number(policy.depositPercent3Rooms)
                    : roomQtyForDeposit === 2 ? Number(policy.depositPercent2Rooms)
                    : Number(policy.depositPercent);
                const dynamicDepositRate = dynamicDepositPercent / 100;
                const deposit30 = Math.round(depositBase * dynamicDepositRate);
                const isCounterPayment = method === 'cash' || method === 'bank_transfer';

                if (counterPaymentConfirmBox) {
                    counterPaymentConfirmBox.classList.toggle('d-none', !isCounterPayment);
                }

                if (confirmCounterPayment) {
                    confirmCounterPayment.required = isCounterPayment;
                    confirmCounterPayment.disabled = !isCounterPayment;
                    if (!isCounterPayment) {
                        confirmCounterPayment.checked = false;
                    }
                }

                if (confirmCounterPaymentLabel) {
                    confirmCounterPaymentLabel.innerText = method === 'bank_transfer'
                        ? `Tôi xác nhận đã kiểm tra tài khoản và nhận đủ tiền cọc ${percentLabel(dynamicDepositPercent)}% bằng chuyển khoản tại quầy.`
                        : `Tôi xác nhận đã nhận đủ tiền cọc ${percentLabel(dynamicDepositPercent)}% bằng tiền mặt tại quầy.`;
                }

                if (counterPaymentConfirmHelp) {
                    counterPaymentConfirmHelp.innerText = 'Số tiền cần xác nhận đã nhận: ' + formatMoney(deposit30) + '.';
                }

                paymentType.disabled = false;

                if (!paymentType.value) {
                    paymentType.value = 'deposit_30';
                }

                if (method === 'vnpay' && customOption) {
                    customOption.disabled = true;

                    if (paymentType.value === 'custom') {
                        paymentType.value = 'deposit_30';
                    }
                } else if (customOption) {
                    customOption.disabled = false;
                }

                const activeType = paymentType.value;
                const isCustom = activeType === 'custom' && method !== 'vnpay';

                if (customPaymentAmountBox) {
                    customPaymentAmountBox.classList.toggle('d-none', !isCustom);
                }

                depositAmount.disabled = !isCustom;

                if (!isCustom) {
                    depositAmount.value = 0;
                }

                if (paymentTypeHelp) {
                    if (method === 'vnpay') {
                        paymentTypeHelp.innerText = `Sau khi tạo booking, hệ thống gửi email có đường dẫn VNPay để khách đặt cọc ${percentLabel(dynamicDepositPercent)}% khoảng ` + formatMoney(deposit30) + '.';
                    } else if (activeType === 'custom') {
                        paymentTypeHelp.innerText = 'Lễ tân nhập đúng số tiền khách thực trả tại quầy.';
                    } else {
                        paymentTypeHelp.innerText = `Ghi nhận cọc ${percentLabel(dynamicDepositPercent)}% khoảng ` + formatMoney(deposit30) + '.';
                    }
                }

                if (paymentAmountHelp) {
                    paymentAmountHelp.innerText = isCustom
                        ? 'Số tiền nhập không được lớn hơn tổng tiền sau giảm giá.'
                        : 'Ô này chỉ bật khi chọn kiểu nhập số tiền thực thu.';
                }
            }

            function canCheckCategoryAvailability() {
                if (!checkInDate.value || !checkOutDate.value) {
                    return false;
                }

                if (bookingMode.value === 'walk_in' && !checkInTime.value) {
                    return false;
                }

                if (bookingMode.value === 'walk_in' && bookingType.value === 'hourly' && !hourlyCheckOutTime.value) {
                    return false;
                }

                return true;
            }

            function currentCategoryAvailabilityPayload() {
                return {
                    booking_mode: bookingMode.value,
                    booking_type: bookingType.value,
                    check_in_date: checkInDate.value,
                    check_out_date: checkOutDate.value,
                    check_in_time: checkInTime.value,
                    check_out_time: bookingType.value === 'hourly' ? hourlyCheckOutTime.value : null,
                };
            }

            function sameCategoryAvailabilityPayload(left, right) {
                return JSON.stringify(left) === JSON.stringify(right);
            }

            function refreshRoomCategoryAvailability() {
                window.clearTimeout(categoryAvailabilityTimer);

                if (!canCheckCategoryAvailability()) {
                    if (categoryAvailabilityAbortController) {
                        categoryAvailabilityAbortController.abort();
                        categoryAvailabilityAbortController = null;
                    }
                    if (roomCategoryStockNote) {
                        roomCategoryStockNote.innerText = 'Chọn đủ ngày giờ vào/ra để xem các hạng còn phòng.';
                    }
                    return;
                }

                // Các ô ngày/giờ có thể phát nhiều sự kiện liên tiếp khi flatpickr cập nhật.
                // Chờ giao diện ổn định rồi chỉ gửi đúng một request mới nhất.
                categoryAvailabilityTimer = window.setTimeout(function () {
                    const requestId = ++categoryAvailabilityRequestId;
                    const previousValue = roomCategorySelect.value;
                    const payload = currentCategoryAvailabilityPayload();

                    if (categoryAvailabilityAbortController) {
                        categoryAvailabilityAbortController.abort();
                    }
                    categoryAvailabilityAbortController = new AbortController();

                    if (roomCategoryStockNote) {
                        roomCategoryStockNote.innerText = 'Đang kiểm tra số phòng còn lại...';
                    }

                    fetch(roomCategoryAvailabilityUrl, {
                        method: 'POST',
                        signal: categoryAvailabilityAbortController.signal,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Không kiểm tra được hạng phòng.');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        if (
                            requestId !== categoryAvailabilityRequestId
                            || !sameCategoryAvailabilityPayload(payload, currentCategoryAvailabilityPayload())
                        ) {
                            return;
                        }

                        const categories = Array.isArray(data.categories) ? data.categories : [];
                        roomCategorySelect.innerHTML = '<option value="">-- Chọn hạng phòng còn phòng --</option>';

                        categories.forEach(function (category) {
                            const option = document.createElement('option');
                            option.value = String(category.id);
                            option.dataset.price = String(category.price || 0);
                            option.dataset.name = category.name || '';
                            option.dataset.availableCount = String(category.available_count || 0);
                            option.textContent = (category.name || 'Hạng phòng')
                                + ' — còn ' + Number(category.available_count || 0) + ' phòng'
                                + ' — ' + formatMoney(category.price || 0) + '/đêm';
                            roomCategorySelect.appendChild(option);
                        });

                        const canRestore = categories.some(function (category) {
                            return String(category.id) === String(previousValue);
                        });

                        roomCategorySelect.value = canRestore ? previousValue : '';

                        if (roomCategoryStockNote) {
                            roomCategoryStockNote.innerText = categories.length > 0
                                ? 'Chỉ hiển thị hạng còn phòng trong khoảng ' + (data.check_in_at || '') + ' → ' + (data.check_out_at || '') + '.'
                                : 'Không còn hạng phòng nào phù hợp trong thời gian đã chọn.';
                        }

                        updateEstimatedTotal();
                        updateAdjacentRoomBox();
                        queueManualRoomOptionsRefresh();
                    })
                    .catch(function (error) {
                        if (error && error.name === 'AbortError') {
                            return;
                        }
                        if (
                            requestId !== categoryAvailabilityRequestId
                            || !sameCategoryAvailabilityPayload(payload, currentCategoryAvailabilityPayload())
                        ) {
                            return;
                        }
                        if (roomCategoryStockNote) {
                            roomCategoryStockNote.innerText = 'Không kiểm tra được số phòng còn lại. Hãy thử chọn lại thời gian.';
                        }
                    });
                }, 250);
            }

            function refreshBookingForm() {
                updateBookingTypeUi();
                setMinDates();
                updateAdjacentRoomBox();
                updateHourlyPreview();
                updateWalkInOvernightPreview();
                refreshRoomCategoryAvailability();
                updateEstimatedTotal();
            }

            if (typeof flatpickr !== 'undefined' && checkInTime) {
                flatpickr(checkInTime, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                    minuteIncrement: 1,
                    locale: 'vn',
                });
            }

            if (typeof flatpickr !== 'undefined' && hourlyCheckOutTime) {
                flatpickr(hourlyCheckOutTime, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                    minuteIncrement: 1,
                    locale: 'vn',
                });
            }

            bookingMode.addEventListener('change', function () {
                autoSetCheckoutDate();
                refreshBookingForm();
            });

            bookingType.addEventListener('change', function () {
                autoSetCheckoutDate();
                refreshBookingForm();
            });

            checkInDate.addEventListener('change', function () {
                autoSetCheckoutDate();
                refreshBookingForm();
            });

            checkOutDate.addEventListener('change', refreshBookingForm);
            checkInTime.addEventListener('change', refreshBookingForm);

            // Date picker chung của project phát project-date-change khi người dùng chọn ngày.
            // Nghe sự kiện này để UI thật luôn refresh tồn phòng, không phụ thuộc native change.
            [checkInDate, checkOutDate].filter(Boolean).forEach(function (field) {
                field.addEventListener('project-date-change', function () {
                    if (field === checkInDate) {
                        autoSetCheckoutDate();
                    }
                    refreshBookingForm();
                    queueManualRoomOptionsRefresh();
                });
            });

            if (hourlyCheckOutTime) {
                hourlyCheckOutTime.addEventListener('change', refreshBookingForm);
            }

            roomCategorySelect.addEventListener('change', function () {
                updateAdjacentRoomBox();
                updateHourlyPreview();
                updateWalkInOvernightPreview();
                updateEstimatedTotal();
            });
            roomQuantity.addEventListener('input', refreshBookingForm);

            if (paymentMethod) {
                paymentMethod.addEventListener('change', refreshBookingForm);
            }

            if (paymentType) {
                paymentType.addEventListener('change', refreshBookingForm);
            }

            serviceRows.forEach(function (row) {
                const checkbox = row.querySelector('.service-check');
                const quantityInput = row.querySelector('.service-quantity');

                if (checkbox) {
                    checkbox.addEventListener('change', refreshBookingForm);
                }

                if (quantityInput) {
                    quantityInput.addEventListener('input', refreshBookingForm);
                    quantityInput.addEventListener('change', refreshBookingForm);
                }
            });

            setMinDates();

            if (checkInDate.value) {
                autoSetCheckoutDate();
            }

            refreshBookingForm();


            function resetAdjacentFallbackConfirmation() {
                if (confirmAdjacentFallback) {
                    confirmAdjacentFallback.value = '0';
                }
            }

            [roomCategorySelect, roomQuantity, checkInDate, checkOutDate, checkInTime, hourlyCheckOutTime, preferAdjacentRooms]
                .filter(Boolean)
                .forEach(function (field) {
                    field.addEventListener('change', resetAdjacentFallbackConfirmation);
                });

            const adjacentFallbackModalElement = document.getElementById('adjacentRoomFallbackModal');
            if (adjacentFallbackModalElement && window.bootstrap) {
                const adjacentFallbackModal = new bootstrap.Modal(adjacentFallbackModalElement, {
                    backdrop: 'static',
                    keyboard: true,
                });
                adjacentFallbackModal.show();

                const confirmButton = document.getElementById('confirmAdjacentFallbackButton');
                const continueWithoutAdjacentButton = document.getElementById('continueWithoutAdjacentButton');

                if (confirmButton) {
                    confirmButton.addEventListener('click', function () {
                        if (confirmAdjacentFallback) {
                            confirmAdjacentFallback.value = '1';
                        }
                        if (bookingCreateForm) {
                            bookingCreateForm.requestSubmit();
                        }
                    });
                }

                if (continueWithoutAdjacentButton) {
                    continueWithoutAdjacentButton.addEventListener('click', function () {
                        if (preferAdjacentRooms) {
                            preferAdjacentRooms.checked = false;
                        }
                        resetAdjacentFallbackConfirmation();
                        if (bookingCreateForm) {
                            bookingCreateForm.requestSubmit();
                        }
                    });
                }
            }

            function updateAdminSelectedPromotionCountText() {
                const text = document.getElementById('adminSelectedPromotionCountText');
                if (!text) {
                    return;
                }

                const selected = Array.from(document.querySelectorAll('.promotion-check:checked'))
                    .map(function (checkbox) {
                        return checkbox.dataset.code || checkbox.value;
                    });

                text.innerText = selected.length > 0
                    ? 'Đang chọn: ' + selected.join(', ')
                    : 'Chưa chọn mã nào';
            }

            function hasRequiredNotePromotion() {
                return Array.from(document.querySelectorAll('.promotion-check:checked')).some(function (checkbox) {
                    return checkbox.dataset.requiresNote === '1';
                });
            }

            function updatePromotionNoteRequirement() {
                const noteInput = document.getElementById('promotionNote');
                const requiredMark = document.getElementById('promotionNoteRequiredMark');
                const required = hasRequiredNotePromotion();

                if (noteInput) {
                    noteInput.required = required;
                }

                if (requiredMark) {
                    requiredMark.style.display = required ? 'inline' : 'none';
                }
            }

            const bookingForm = bookingCreateForm || document.querySelector('form');

            if (bookingForm) {
                bookingForm.addEventListener('submit', function (event) {
                    const noteInput = document.getElementById('promotionNote');

                    if (hasRequiredNotePromotion() && noteInput && noteInput.value.trim() === '') {
                        event.preventDefault();
                        noteInput.focus();
                        alert('Vui lòng nhập lý do khi chọn mã hỗ trợ khách.');
                    }
                });
            }

            updateAdminSelectedPromotionCountText();
            updatePromotionNoteRequirement();
            updateVisiblePromotionCounts();


            function updateVisiblePromotionCounts() {
                let totalVisible = 0;

                document.querySelectorAll('[data-promotion-group]').forEach(group => {
                    const visibleCards = Array.from(group.querySelectorAll('[data-promotion-card]'))
                        .filter(card => !card.classList.contains('d-none'));
                    const count = visibleCards.length;
                    totalVisible += count;
                    group.classList.toggle('d-none', count === 0);
                    const countBadge = group.querySelector('[data-promotion-group-count]');
                    if (countBadge) countBadge.textContent = String(count);
                });

                if (eligiblePromotionCount) eligiblePromotionCount.textContent = String(totalVisible);
            }

            function renderCustomerAccountLookupNotice(type, message) {
                if (!customerAccountLookupNotice) return;

                customerAccountLookupNotice.classList.remove('d-none', 'alert-warning', 'alert-success', 'alert-secondary');
                customerAccountLookupNotice.classList.add(type);
                customerAccountLookupNotice.textContent = message;
            }

            async function checkCustomerAccount() {
                if (!customerEmail || !customerAccountLookupNotice) return;

                const email = (customerEmail.value || '').trim().toLowerCase();
                currentCustomerHasAccount = false;

                if (!email || !customerEmail.checkValidity()) {
                    if (customerAccountLookupAbortController) customerAccountLookupAbortController.abort();
                    customerAccountLookupNotice.classList.add('d-none');
                    customerAccountLookupNotice.textContent = '';
                    return;
                }

                if (customerAccountLookupAbortController) customerAccountLookupAbortController.abort();
                customerAccountLookupAbortController = new AbortController();

                renderCustomerAccountLookupNotice('alert-secondary', 'Đang kiểm tra email khách...');

                try {
                    const response = await fetch(customerAccountLookupUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '',
                        },
                        body: JSON.stringify({ customer_email: email }),
                        signal: customerAccountLookupAbortController.signal,
                    });

                    if (!response.ok) throw new Error('Không thể kiểm tra tài khoản khách.');

                    const result = await response.json();
                    currentCustomerHasAccount = Boolean(result.has_account);
                    renderCustomerAccountLookupNotice(
                        currentCustomerHasAccount ? 'alert-warning' : 'alert-success',
                        result.message
                    );
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    currentCustomerHasAccount = false;
                    renderCustomerAccountLookupNotice(
                        'alert-secondary',
                        'Chưa kiểm tra được email có tài khoản hay không. Backend vẫn kiểm tra và thông báo sau khi tạo booking.'
                    );
                }
            }

            function scheduleCustomerAccountLookup() {
                clearTimeout(customerAccountLookupTimer);
                customerAccountLookupTimer = setTimeout(checkCustomerAccount, 350);
            }

            async function refreshEligiblePromotions() {
                const email = (customerEmail?.value || '').trim().toLowerCase();
                const phone = (customerPhone?.value || '').replace(/\s+/g, '');
                const cccd = (customerCccd?.value || '').replace(/\s+/g, '');
                const requestSequence = ++promotionEligibilityRequestSequence;

                if (promotionEligibilityAbortController) promotionEligibilityAbortController.abort();
                promotionEligibilityAbortController = new AbortController();

                // Lấy tổng ngay sau khi biểu mẫu vừa tính lại. CCCD là định danh chính để
                // kiểm tra lượt/điều kiện ưu đãi; email và SĐT chỉ là kênh liên hệ.
                const subtotalText = (promotionSubtotalText?.textContent || estimatedTotalText?.textContent || '0')
                    .replace(/[^0-9]/g, '');
                const currentSubtotal = Number(subtotalText || 0);
                const payload = {
                    customer_email: email || null,
                    customer_phone: phone || null,
                    customer_cccd: cccd || null,
                    subtotal_amount: currentSubtotal,
                    night_count: Math.max(1, Math.round(calculateNightCount() || 1)),
                    room_quantity: getRoomQuantity(),
                    check_in_at: checkInDate?.value && checkInTime?.value ? `${checkInDate.value} ${checkInTime.value}` : null,
                    check_out_at: checkOutDate?.value && (hourlyCheckOutTime?.value || policy.standardCheckOut) ? `${checkOutDate.value} ${bookingType?.value === 'hourly' ? hourlyCheckOutTime.value : policy.standardCheckOut}` : null,
                };

                try {
                    const response = await fetch(eligiblePromotionsUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '',
                        },
                        body: JSON.stringify(payload),
                        signal: promotionEligibilityAbortController.signal,
                    });
                    if (!response.ok) throw new Error('Không thể kiểm tra mã ưu đãi.');
                    const result = await response.json();

                    // Bỏ qua kết quả của lần kiểm tra cũ nếu người dùng đã thay đổi dữ liệu.
                    if (requestSequence !== promotionEligibilityRequestSequence) return;

                    const allowed = new Set(result.codes || []);
                    document.querySelectorAll('[data-promotion-card]').forEach(card => {
                        const code = card.dataset.promotionCode;
                        const visible = allowed.has(code);
                        card.classList.toggle('d-none', !visible);
                        const checkbox = card.querySelector('.promotion-check');
                        if (!visible && checkbox?.checked) {
                            checkbox.checked = false;
                            checkbox.dispatchEvent(new Event('change'));
                        }
                    });
                    updateVisiblePromotionCounts();
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    // Không được lộ mã chưa xác minh rồi để submit mới báo lỗi. Khi
                    // không kiểm tra được eligibility, chỉ giữ mã cũ đang được chọn
                    // để người dùng không mất dữ liệu; backend vẫn validate lần cuối.
                    document.querySelectorAll('[data-promotion-card]').forEach(card => {
                        const checkbox = card.querySelector('.promotion-check');
                        card.classList.toggle('d-none', !checkbox?.checked);
                    });
                    updateVisiblePromotionCounts();
                }
            }

            function scheduleEligiblePromotionRefresh() {
                clearTimeout(promotionEligibilityTimer);
                promotionEligibilityTimer = setTimeout(refreshEligiblePromotions, 450);
            }

            [customerEmail, customerPhone, customerCccd, checkInDate, checkOutDate, checkInTime, hourlyCheckOutTime, roomQuantity, roomCategorySelect]
                .filter(Boolean)
                .forEach(element => {
                    element.addEventListener('input', scheduleEligiblePromotionRefresh);
                    element.addEventListener('change', scheduleEligiblePromotionRefresh);
                });

            if (customerEmail) {
                customerEmail.addEventListener('input', scheduleCustomerAccountLookup);
                customerEmail.addEventListener('change', scheduleCustomerAccountLookup);
                customerEmail.addEventListener('blur', checkCustomerAccount);
                scheduleCustomerAccountLookup();
            }

            if (bookingCreateForm) {
                bookingCreateForm.addEventListener('submit', function (event) {
                    if (!currentCustomerHasAccount) return;

                    const confirmed = window.confirm(
                        'Email khách đã có tài khoản. Booking sau khi tạo sẽ xem trong tài khoản khách và KHÔNG xuất hiện ở mục tra cứu booking vãng lai. Vẫn tạo booking?'
                    );

                    if (!confirmed) event.preventDefault();
                });
            }

            scheduleEligiblePromotionRefresh();

            function enforcePromotionSelection(changedCheckbox) {
                if (!changedCheckbox.checked) {
                    return true;
                }

                const selected = Array.from(document.querySelectorAll('.promotion-check:checked'));
                const type = changedCheckbox.dataset.type;
                const group = changedCheckbox.closest('[data-promotion-group]');
                const limitRaw = group?.dataset.promotionLimit || '';
                const limit = limitRaw === '' ? null : Number(limitRaw);

                if (changedCheckbox.dataset.stackable === '0' && selected.length > 1) {
                    changedCheckbox.checked = false;
                    alert('Mã ' + (changedCheckbox.dataset.code || '') + ' chỉ được dùng một mình.');
                    return false;
                }

                const anotherSolo = selected.find(item => item !== changedCheckbox && item.dataset.stackable === '0');
                if (anotherSolo) {
                    changedCheckbox.checked = false;
                    alert('Mã ' + (anotherSolo.dataset.code || '') + ' đang được chọn và chỉ được dùng một mình.');
                    return false;
                }

                if (limit !== null) {
                    const selectedSameType = selected.filter(item => item.dataset.type === type);
                    if (selectedSameType.length > limit) {
                        changedCheckbox.checked = false;
                        const label = type === 'normal_discount' ? 'mã thường'
                            : type === 'event_discount' ? 'mã sự kiện'
                            : type === 'conditional_discount' ? 'mã điều kiện'
                            : 'mã cùng nhóm';
                        alert('Mỗi booking chỉ được chọn tối đa ' + limit + ' ' + label + '.');
                        return false;
                    }
                }

                return true;
            }

            promotionChecks.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    enforcePromotionSelection(checkbox);
                    updateAdminSelectedPromotionCountText();
                    updatePromotionNoteRequirement();
                    refreshBookingForm();
                });
            });

        });
    </script>

<?php echo $__env->make('partials.cccd-scanner-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/bookings/create.blade.php ENDPATH**/ ?>