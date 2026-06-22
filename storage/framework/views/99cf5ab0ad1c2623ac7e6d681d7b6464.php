<?php $__env->startSection('title', 'Xác nhận đặt phòng'); ?>

<?php $__env->startSection('content'); ?>

    <style>
        .promotion-list {
            display: grid;
            gap: 10px;
        }

        .promotion-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 14px;
            background: #fff;
            transition: 0.15s ease;
        }

        .promotion-card:hover {
            border-color: #c7a14a;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .promotion-code {
            font-weight: 800;
            letter-spacing: 0.03em;
        }

        .promotion-meta {
            font-size: 13px;
            color: #6b7280;
        }

        .promotion-collapsible {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f9fafb;
            overflow: hidden;
        }

        .promotion-collapsible > summary {
            list-style: none;
            cursor: pointer;
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .promotion-collapsible > summary::-webkit-details-marker {
            display: none;
        }

        .promotion-collapsible .promotion-collapsible-body {
            border-top: 1px solid #e5e7eb;
            padding: 14px;
            background: #ffffff;
        }

        .promotion-selected-hint {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

    </style>

    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">
                Xác nhận đặt phòng
            </h1>

            <p class="text-muted mb-0">
                Kiểm tra thông tin cá nhân và thông tin đặt phòng trước khi hoàn tất.
            </p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">

            <?php if(session('error')): ?>
                <div class="alert alert-danger">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">
                        Vui lòng kiểm tra lại thông tin bên dưới.
                    </div>

                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('bookings.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <input type="hidden" name="room_category_id" value="<?php echo e($bookingData['room_category_id']); ?>">
                <input type="hidden" name="check_in_date" value="<?php echo e($bookingData['check_in_date']); ?>">
                <input type="hidden" name="check_out_date" value="<?php echo e($bookingData['check_out_date']); ?>">
                <input type="hidden" name="adult_count" value="<?php echo e($bookingData['adult_count']); ?>">
                <input type="hidden" name="child_count" value="<?php echo e($bookingData['child_count'] ?? 0); ?>">
                <input type="hidden" name="note" value="<?php echo e($bookingData['note'] ?? ''); ?>">

                <div class="row g-4">

                    <div class="col-lg-8">

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Thông tin khách hàng
                                </h2>

                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Họ
                                        </label>
                                        <input type="text" name="last_name" class="form-control"
                                            value="<?php echo e(old('last_name', $customer->last_name ?? '')); ?>" required>
                                        <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Tên
                                        </label>
                                        <input type="text" name="first_name" class="form-control"
                                            value="<?php echo e(old('first_name', $customer->first_name ?? '')); ?>" required>
                                        <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Số điện thoại
                                        </label>
                                        <input type="text" name="phone" class="form-control"
                                            value="<?php echo e(old('phone', $customer->phone ?? '')); ?>" required>
                                        <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            CCCD
                                        </label>
                                        <input type="text" name="cccd" class="form-control"
                                            value="<?php echo e(old('cccd', $customer->cccd ?? '')); ?>">
                                        <?php $__errorArgs = ['cccd'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">
                                            Email
                                        </label>
                                        <input type="email" name="email" class="form-control"
                                            value="<?php echo e(old('email', $customer->email ?? auth()->user()->email)); ?>">
                                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">
                                            Địa chỉ
                                        </label>
                                        <textarea name="address" rows="3"
                                            class="form-control"><?php echo e(old('address', $customer->address ?? '')); ?></textarea>
                                        <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <div class="text-danger small mt-1"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                </div>

                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Dịch vụ đặt thêm
                                </h2>

                                <p class="text-muted small mb-3">
                                    Chọn dịch vụ cần đặt trước. Nếu cần thêm dịch vụ trong thời gian lưu trú, vui lòng gọi
                                    lễ tân để được hỗ trợ.
                                </p>

                                <?php if($services->count() > 0): ?>

                                    <div class="row g-2 align-items-end mb-3">

                                        <div class="col-md-6">
                                            <label class="form-label small">Chọn dịch vụ</label>
                                            <select id="serviceSelect" class="form-select">
                                                <option value="">-- Chọn dịch vụ --</option>

                                                <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($service->id); ?>" data-name="<?php echo e($service->name); ?>"
                                                        data-price="<?php echo e($service->price); ?>" data-unit="<?php echo e($service->unit); ?>"
                                                        data-type="<?php echo e($service->type); ?>">
                                                        <?php echo e($service->name); ?>

                                                        -
                                                        <?php echo e(number_format($service->price, 0, ',', '.')); ?>đ / <?php echo e($service->unit); ?>

                                                        -
                                                        <?php echo e($service->type == 'minibar' ? 'Minibar' : 'Dịch vụ'); ?>

                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label small">Số lượng</label>
                                            <input type="number" id="serviceQuantity" class="form-control" value="1" min="1">
                                        </div>

                                        <div class="col-md-3">
                                            <button type="button" id="addServiceButton" class="btn btn-primary w-100">
                                                Thêm
                                            </button>
                                        </div>

                                        <div class="col-md-12">
                                            <label class="form-label small">Ghi chú</label>
                                            <input type="text" id="serviceNote" class="form-control"
                                                placeholder="Ví dụ: chuẩn bị trước khi nhận phòng">
                                        </div>

                                    </div>

                                    <div id="selectedServiceEmptyBox" class="alert alert-light border mb-0">
                                        Chưa chọn dịch vụ đặt thêm.
                                    </div>

                                    <div id="selectedServiceTableBox" class="table-responsive d-none">

                                        <table class="table table-sm align-middle mb-0">

                                            <thead class="table-light">
                                                <tr>
                                                    <th>Dịch vụ đã chọn</th>
                                                    <th>Loại</th>
                                                    <th>Đơn giá</th>
                                                    <th style="width: 110px;">Số lượng</th>
                                                    <th>Thành tiền</th>
                                                    <th>Ghi chú</th>
                                                    <th></th>
                                                </tr>
                                            </thead>

                                            <tbody id="selectedServiceTableBody"></tbody>

                                        </table>

                                    </div>

                                    <div id="selectedServiceInputs"></div>

                                <?php else: ?>

                                    <div class="alert alert-light border mb-0">
                                        Hiện chưa có dịch vụ đặt thêm.
                                    </div>

                                <?php endif; ?>

                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Mã ưu đãi có thể áp dụng
                                </h2>

                                <p class="text-muted small mb-3">
                                    Bạn có thể chọn nhiều mã nếu các mã cho phép dùng chung. Mã hỗ trợ chỉ do khách sạn áp dụng, không hiển thị tại đây.
                                </p>

                                <?php if(($availablePromotions ?? collect())->count() > 0): ?>
                                    <details class="promotion-collapsible" <?php echo e(!empty(old('promotion_codes', [])) ? 'open' : ''); ?>>
                                        <summary>
                                            <span>
                                                Có <?php echo e(($availablePromotions ?? collect())->count()); ?> mã phù hợp
                                                <span class="promotion-selected-hint d-block" id="selectedPromotionCountText">
                                                    Chưa chọn mã nào
                                                </span>
                                            </span>
                                            <span class="badge text-bg-light border">Bấm để xem / chọn</span>
                                        </summary>

                                        <div class="promotion-collapsible-body">
                                            <div class="promotion-list">
                                                <?php $__currentLoopData = $availablePromotions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promotion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php
                                                        $promotionTypeLabel = match ($promotion->promotion_type) {
                                                            'normal_discount' => 'Mã thường',
                                                            'event_discount' => 'Mã sự kiện',
                                                            'conditional_discount' => 'Mã điều kiện',
                                                            default => 'Mã ưu đãi',
                                                        };

                                                        $promotionBadgeClass = match ($promotion->promotion_type) {
                                                            'normal_discount' => 'bg-primary',
                                                            'event_discount' => 'bg-success',
                                                            'conditional_discount' => 'bg-warning text-dark',
                                                            default => 'bg-secondary',
                                                        };

                                                        $promotionDiscountText = $promotion->discount_type == 'percent'
                                                            ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, ',', '.'), '0'), ',') . '%'
                                                            : number_format((float) $promotion->discount_value, 0, ',', '.') . 'đ';

                                                        if ($promotion->discount_type == 'percent' && (float) $promotion->max_discount_amount > 0) {
                                                            $promotionDiscountText .= ' - tối đa ' . number_format((float) $promotion->max_discount_amount, 0, ',', '.') . 'đ';
                                                        }

                                                        $promotionServiceOffersJson = $promotion->serviceOffers->map(function ($offer) {
                                                            return [
                                                                'service_id' => $offer->service_id,
                                                                'service_name' => $offer->service->name ?? 'Dịch vụ',
                                                                'service_unit' => $offer->service->unit ?? '',
                                                                'service_price' => (float) ($offer->service->price ?? 0),
                                                                'service_type' => $offer->service->type ?? 'service',
                                                                'discount_type' => $offer->discount_type,
                                                                'discount_value' => (float) $offer->discount_value,
                                                                'quantity' => (int) $offer->quantity,
                                                                'auto_add_service' => (bool) $offer->auto_add_service,
                                                            ];
                                                        })->values()->toArray();
                                                    ?>

                                                    <label class="promotion-card mb-0">
                                                        <div class="form-check">
                                                            <input type="checkbox"
                                                                name="promotion_codes[]"
                                                                value="<?php echo e($promotion->code); ?>"
                                                                class="form-check-input promotion-check"
                                                                data-code="<?php echo e($promotion->code); ?>"
                                                                data-discount-type="<?php echo e($promotion->discount_type); ?>"
                                                                data-discount-value="<?php echo e((float) $promotion->discount_value); ?>"
                                                                data-max-discount="<?php echo e((float) $promotion->max_discount_amount); ?>"
                                                                data-service-offers='<?php echo json_encode($promotionServiceOffersJson, 15, 512) ?>'
                                                                <?php if(in_array($promotion->code, old('promotion_codes', []))): echo 'checked'; endif; ?>>

                                                            <div class="ms-1">
                                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                                    <div>
                                                                        <div class="promotion-code"><?php echo e($promotion->code); ?></div>
                                                                        <div class="fw-semibold"><?php echo e($promotion->name); ?></div>
                                                                    </div>
                                                                    <span class="badge <?php echo e($promotionBadgeClass); ?>"><?php echo e($promotionTypeLabel); ?></span>
                                                                </div>

                                                                <div class="promotion-meta mt-1">
                                                                    Giảm <?php echo e($promotionDiscountText); ?>

                                                                    <?php if((float) $promotion->min_booking_amount > 0): ?>
                                                                        · Đơn từ <?php echo e(number_format((float) $promotion->min_booking_amount, 0, ',', '.')); ?>đ
                                                                    <?php endif; ?>
                                                                    <?php if((int) $promotion->min_nights > 0): ?>
                                                                        · Từ <?php echo e((int) $promotion->min_nights); ?> đêm
                                                                    <?php endif; ?>
                                                                </div>

                                                                <?php if($promotion->serviceOffers->count() > 0): ?>
                                                                    <div class="promotion-meta mt-1 text-success">
                                                                        Dịch vụ ưu đãi:
                                                                        <?php echo e($promotion->serviceOffers->map(fn ($offer) => $offer->offer_label)->implode(' · ')); ?>

                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </label>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        </div>
                                    </details>
                                <?php else: ?>
                                    <div class="alert alert-light border mb-0">
                                        Hiện chưa có mã ưu đãi phù hợp với đơn này.
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Ghi chú đặt phòng
                                </h2>

                                <p class="mb-0 text-muted">
                                    <?php echo e($bookingData['note'] ?? 'Không có ghi chú.'); ?>

                                </p>

                            </div>
                        </div>

                    </div>

                    <div class="col-lg-4">

                        <div class="card border-0 shadow-sm sticky-top" style="top: 90px;">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Thông tin booking
                                </h2>

                                <div class="alert alert-info small mb-3">
                                    Giờ nhận phòng <strong>12:00 - 14:00</strong> <br>
                                    Giờ trả phòng <strong>12:00</strong>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Hạng phòng
                                    </div>
                                    <div class="fw-bold">
                                        <?php echo e($roomCategory->name); ?>

                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Nhận phòng
                                    </div>
                                    <div class="fw-bold">
                                        <?php echo e(date('d/m/Y', strtotime($bookingData['check_in_date']))); ?>

                                    </div>
                                    <div class="small text-muted">
                                        Nhận phòng linh hoạt 13:00–14:00 nếu phòng đã sẵn sàng
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Trả phòng
                                    </div>
                                    <div class="fw-bold">
                                        <?php echo e(date('d/m/Y', strtotime($bookingData['check_out_date']))); ?>

                                    </div>
                                    <div class="small text-muted">
                                        Trả phòng trước 12:00
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Số đêm
                                    </div>
                                    <div class="fw-bold">
                                        <?php echo e($nightCount); ?> đêm
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Số khách
                                    </div>
                                    <div class="fw-bold">
                                        <?php echo e($bookingData['adult_count']); ?> người lớn,
                                        <?php echo e($bookingData['child_count'] ?? 0); ?> trẻ em
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Số phòng
                                    </div>
                                    <div class="fw-bold">
                                        1 phòng
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Dịch vụ đặt thêm
                                    </div>
                                    <div class="fw-bold text-danger" id="selectedServiceTotalText">
                                        0đ
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Mã ưu đãi
                                    </div>
                                    <div class="fw-bold text-success" id="selectedPromotionDiscountText">
                                        -0đ
                                    </div>
                                    <div class="small text-muted" id="selectedPromotionBreakdownText">
                                        Chưa áp dụng ưu đãi.
                                    </div>
                                </div>

                                <hr>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold">
                                        Tạm tính
                                    </span>

                                    <span class="fw-bold text-primary fs-5" id="finalEstimatedTotalText"
                                        data-room-total="<?php echo e($estimatedTotal); ?>">
                                        <?php echo e(number_format($estimatedTotal, 0, ',', '.')); ?>đ
                                    </span>
                                </div>

                                <div class="border rounded-3 p-3 mb-3 bg-light">
                                    <div class="fw-bold mb-2">
                                        Chọn hình thức thanh toán
                                    </div>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_type"
                                            id="paymentDeposit30" value="deposit_30" checked>
                                        <label class="form-check-label" for="paymentDeposit30">
                                            Cọc 30%
                                            <strong id="depositAmountPreview">
                                                <?php echo e(number_format(round($estimatedTotal * 0.3), 0, ',', '.')); ?>đ
                                            </strong>
                                        </label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_type" id="paymentFull100"
                                            value="full_100">
                                        <label class="form-check-label" for="paymentFull100">
                                            Thanh toán 100%
                                            <strong id="fullAmountPreview">
                                                <?php echo e(number_format($estimatedTotal, 0, ',', '.')); ?>đ
                                            </strong>
                                        </label>
                                    </div>

                                    <?php $__errorArgs = ['payment_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="text-danger small mt-2"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                    <div class="small text-muted mt-2">
                                        Chính sách: đã cọc hoặc đã thanh toán thì khi hủy booking sẽ không hoàn tiền.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="bx bx-check-circle me-1"></i>
                                    Thanh toán
                                </button>

                                <a href="<?php echo e(route('rooms', [
        'check_in_date' => $bookingData['check_in_date'],
        'check_out_date' => $bookingData['check_out_date'],
        'adult_count' => $bookingData['adult_count'],
        'child_count' => $bookingData['child_count'] ?? 0,
        'room_category_id' => $roomCategory->id,
    ])); ?>" class="btn btn-outline-secondary w-100 mt-2">
                                    Quay lại danh sách phòng
                                </a>

                                <p class="small text-muted mt-3 mb-0">
                                    Nếu cần đặt nhiều phòng hoặc khách đoàn, vui lòng liên hệ hotline/lễ tân để được hỗ trợ.
                                </p>

                            </div>
                        </div>

                    </div>

                </div>

            </form>

        </div>
    </main>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const serviceSelect = document.getElementById('serviceSelect');
            const serviceQuantity = document.getElementById('serviceQuantity');
            const serviceNote = document.getElementById('serviceNote');
            const addServiceButton = document.getElementById('addServiceButton');

            const selectedServiceEmptyBox = document.getElementById('selectedServiceEmptyBox');
            const selectedServiceTableBox = document.getElementById('selectedServiceTableBox');
            const selectedServiceTableBody = document.getElementById('selectedServiceTableBody');
            const selectedServiceInputs = document.getElementById('selectedServiceInputs');

            const selectedServiceTotalText = document.getElementById('selectedServiceTotalText');
            const selectedPromotionDiscountText = document.getElementById('selectedPromotionDiscountText');
            const selectedPromotionBreakdownText = document.getElementById('selectedPromotionBreakdownText');
            const finalEstimatedTotalText = document.getElementById('finalEstimatedTotalText');
            const depositAmountPreview = document.getElementById('depositAmountPreview');
            const promotionChecks = document.querySelectorAll('.promotion-check');
            const fullAmountPreview = document.getElementById('fullAmountPreview');

            const selectedServices = new Map();

            function formatMoney(value) {
                return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
            }

            function getTypeLabel(type) {
                if (type === 'minibar') {
                    return 'Minibar';
                }

                return 'Dịch vụ';
            }

            function getRoomTotal() {
                if (!finalEstimatedTotalText) {
                    return 0;
                }

                return parseFloat(finalEstimatedTotalText.dataset.roomTotal || 0);
            }

            function getSelectedServiceQuantity(serviceId) {
                const key = String(serviceId);
                return selectedServices.has(key)
                    ? Math.max(0, parseInt(selectedServices.get(key).quantity || 0))
                    : 0;
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
                const autoServiceNames = [];

                promotionChecks.forEach(function (checkbox) {
                    if (!checkbox.checked) {
                        return;
                    }

                    parseServiceOffers(checkbox).forEach(function (offer) {
                        const price = parseFloat(offer.service_price || 0);
                        const offerQuantity = Math.max(1, parseInt(offer.quantity || 1));
                        let applicableQuantity = Math.min(offerQuantity, getSelectedServiceQuantity(offer.service_id));
                        const missingQuantity = Math.max(0, offerQuantity - applicableQuantity);

                        if (missingQuantity > 0 && offer.auto_add_service) {
                            autoServiceTotal += price * missingQuantity;
                            applicableQuantity += missingQuantity;
                            autoServiceNames.push((offer.service_name || 'Dịch vụ') + ' x' + missingQuantity);
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

                const totalDiscount = Math.min(subtotal, moneyDiscount + serviceDiscount);

                return {
                    subtotal: subtotal,
                    autoServiceTotal: autoServiceTotal,
                    autoServiceNames: autoServiceNames,
                    moneyDiscount: Math.min(moneyDiscount, subtotal),
                    serviceDiscount: Math.min(serviceDiscount, subtotal),
                    totalDiscount: totalDiscount,
                    finalTotal: Math.max(0, subtotal - totalDiscount),
                };
            }

            function renderSelectedServices() {
                if (!selectedServiceTableBody || !selectedServiceInputs) {
                    return;
                }

                selectedServiceTableBody.innerHTML = '';
                selectedServiceInputs.innerHTML = '';

                let serviceTotal = 0;
                let index = 0;

                selectedServices.forEach(function (service, serviceId) {
                    const total = service.price * service.quantity;
                    serviceTotal += total;

                    const row = document.createElement('tr');

                    row.innerHTML = `
                                                    <td class="fw-bold">${service.name}</td>
                                                    <td>
                                                        <span class="badge ${service.type === 'minibar' ? 'bg-warning text-dark' : 'bg-primary'}">
                                                            ${getTypeLabel(service.type)}
                                                        </span>
                                                    </td>
                                                    <td>${formatMoney(service.price)} / ${service.unit}</td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm selected-service-quantity"
                                                            value="${service.quantity}" min="1" data-service-id="${serviceId}">
                                                    </td>
                                                    <td class="fw-bold text-danger">${formatMoney(total)}</td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm selected-service-note"
                                                            value="${service.note}" data-service-id="${serviceId}" placeholder="Ghi chú">
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-service-button"
                                                            data-service-id="${serviceId}">
                                                            Xóa
                                                        </button>
                                                    </td>
                                                `;

                    selectedServiceTableBody.appendChild(row);

                    selectedServiceInputs.insertAdjacentHTML('beforeend', `
                                                    <input type="hidden" name="services[${index}][service_id]" value="${serviceId}">
                                                    <input type="hidden" name="services[${index}][quantity]" value="${service.quantity}">
                                                    <input type="hidden" name="services[${index}][note]" value="${service.note}">
                                                `);

                    index++;
                });

                if (selectedServiceEmptyBox && selectedServiceTableBox) {
                    if (selectedServices.size > 0) {
                        selectedServiceEmptyBox.classList.add('d-none');
                        selectedServiceTableBox.classList.remove('d-none');
                    } else {
                        selectedServiceEmptyBox.classList.remove('d-none');
                        selectedServiceTableBox.classList.add('d-none');
                    }
                }

                if (selectedServiceTotalText) {
                    selectedServiceTotalText.innerText = formatMoney(serviceTotal);
                }

                if (finalEstimatedTotalText) {
                    const totals = calculatePromotionTotals(getRoomTotal(), serviceTotal);
                    const finalTotal = totals.finalTotal;

                    if (selectedPromotionDiscountText) {
                        selectedPromotionDiscountText.innerText = '-' + formatMoney(totals.totalDiscount);
                    }

                    if (selectedPromotionBreakdownText) {
                        const parts = [];

                        if (totals.moneyDiscount > 0) {
                            parts.push('Giảm tiền ' + formatMoney(totals.moneyDiscount));
                        }

                        if (totals.serviceDiscount > 0) {
                            parts.push('Ưu đãi dịch vụ ' + formatMoney(totals.serviceDiscount));
                        }

                        if (totals.autoServiceTotal > 0) {
                            parts.push('Tự thêm ' + totals.autoServiceNames.join(', '));
                        }

                        selectedPromotionBreakdownText.innerText = parts.length > 0
                            ? parts.join(' · ')
                            : 'Chưa áp dụng ưu đãi.';
                    }

                    finalEstimatedTotalText.innerText = formatMoney(finalTotal);

                    if (depositAmountPreview) {
                        depositAmountPreview.innerText = formatMoney(Math.round(finalTotal * 0.3));
                    }

                    if (fullAmountPreview) {
                        fullAmountPreview.innerText = formatMoney(finalTotal);
                    }
                }
            }

            function addSelectedService() {
                if (!serviceSelect || !serviceQuantity) {
                    return;
                }

                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

                if (!selectedOption || !selectedOption.value) {
                    alert('Vui lòng chọn dịch vụ.');
                    return;
                }

                const serviceId = selectedOption.value;
                const quantity = Math.max(1, parseInt(serviceQuantity.value || 1));
                const note = serviceNote ? serviceNote.value.trim() : '';

                if (selectedServices.has(serviceId)) {
                    const currentService = selectedServices.get(serviceId);

                    currentService.quantity += quantity;

                    if (note !== '') {
                        currentService.note = currentService.note
                            ? currentService.note + '; ' + note
                            : note;
                    }

                    selectedServices.set(serviceId, currentService);
                } else {
                    selectedServices.set(serviceId, {
                        name: selectedOption.dataset.name || selectedOption.text,
                        price: parseFloat(selectedOption.dataset.price || 0),
                        unit: selectedOption.dataset.unit || '',
                        type: selectedOption.dataset.type || 'service',
                        quantity: quantity,
                        note: note,
                    });
                }

                serviceSelect.value = '';
                serviceQuantity.value = 1;

                if (serviceNote) {
                    serviceNote.value = '';
                }

                renderSelectedServices();
            }

            promotionChecks.forEach(function (checkbox) {
                checkbox.addEventListener('change', renderSelectedServices);
            });

            if (addServiceButton) {
                addServiceButton.addEventListener('click', addSelectedService);
            }

            if (selectedServiceTableBody) {
                selectedServiceTableBody.addEventListener('click', function (event) {
                    const button = event.target.closest('.remove-service-button');

                    if (!button) {
                        return;
                    }

                    selectedServices.delete(button.dataset.serviceId);
                    renderSelectedServices();
                });

                selectedServiceTableBody.addEventListener('input', function (event) {
                    const quantityInput = event.target.closest('.selected-service-quantity');
                    const noteInput = event.target.closest('.selected-service-note');

                    if (quantityInput) {
                        const service = selectedServices.get(quantityInput.dataset.serviceId);

                        if (service) {
                            service.quantity = Math.max(1, parseInt(quantityInput.value || 1));
                            selectedServices.set(quantityInput.dataset.serviceId, service);
                            renderSelectedServices();
                        }
                    }

                    if (noteInput) {
                        const service = selectedServices.get(noteInput.dataset.serviceId);

                        if (service) {
                            service.note = noteInput.value;
                            selectedServices.set(noteInput.dataset.serviceId, service);
                            renderSelectedServices();
                        }
                    }
                });
            }

            renderSelectedServices();


            function updateSelectedPromotionCountText() {
                const text = document.getElementById('selectedPromotionCountText');
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

            updateSelectedPromotionCountText();
            document.querySelectorAll('.promotion-check').forEach(function (checkbox) {
                checkbox.addEventListener('change', updateSelectedPromotionCountText);
            });

        });
    </script>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/user/pages/booking-confirm.blade.php ENDPATH**/ ?>