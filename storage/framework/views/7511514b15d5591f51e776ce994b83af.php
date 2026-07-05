<?php $__env->startSection('title', 'Chi tiết đơn phòng'); ?>

<?php $__env->startSection('content'); ?>

    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">
                Chi tiết đơn phòng
            </h1>

            <p class="text-muted mb-0">
                Theo dõi thông tin đặt phòng, trạng thái xác nhận và phòng được gán.
            </p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">

            <div class="mb-4">
                <a href="<?php echo e(route('home')); ?>#bookings" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i>
                    Quay lại trang chủ
                </a>
            </div>

            <?php if(session('success')): ?>
                <div class="alert alert-success">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Vui lòng kiểm tra lại thông tin.</div>
                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if($booking->status == 'pending' && $booking->payment_status == 'unpaid'): ?>
                <?php
                    $deposit30Amount = round((float) $booking->estimated_total * 0.3);
                    $fullAmount = (float) $booking->estimated_total;
                    $selectedPaymentType = old('payment_type', $defaultPaymentType ?? 'deposit_30');
                ?>

                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <div class="fw-bold mb-1">
                                Đơn này chưa thanh toán
                            </div>

                            <div class="small">
                                Booking đã được tạo tạm thời nhưng chưa giữ phòng cụ thể. Vui lòng thanh toán cọc hoặc thanh toán đủ qua VNPay để hệ thống gán phòng còn trống và xác nhận đơn.
                            </div>

                            <?php if(!empty($latestPayment)): ?>
                                <div class="small text-muted mt-2">
                                    Giao dịch gần nhất:
                                    <?php echo e(number_format((float) $latestPayment->amount, 0, ',', '.')); ?>đ
                                    -
                                    <?php if($latestPayment->status == 'pending'): ?>
                                        đang chờ thanh toán
                                    <?php elseif($latestPayment->status == 'failed'): ?>
                                        không thành công
                                    <?php elseif($latestPayment->status == 'success'): ?>
                                        thành công
                                    <?php else: ?>
                                        <?php echo e($latestPayment->status); ?>

                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <form action="<?php echo e(route('payment.vnpay.create', $booking->id)); ?>" method="POST" style="min-width: 280px;">
                            <?php echo csrf_field(); ?>

                            <div class="border rounded-3 p-3 bg-white mb-2">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_type"
                                        id="continueDeposit30" value="deposit_30"
                                        <?php echo e($selectedPaymentType == 'deposit_30' ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="continueDeposit30">
                                        Cọc 30%
                                        <strong><?php echo e(number_format($deposit30Amount, 0, ',', '.')); ?>đ</strong>
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_type"
                                        id="continueFull100" value="full_100"
                                        <?php echo e($selectedPaymentType == 'full_100' ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="continueFull100">
                                        Thanh toán 100%
                                        <strong><?php echo e(number_format($fullAmount, 0, ',', '.')); ?>đ</strong>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bx bx-credit-card me-1"></i>
                                Tiếp tục thanh toán VNPay
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-4">

                <div class="col-lg-8">

                    <div class="settings-section mb-4">

                        <div class="d-flex justify-content-between align-items-start mb-3">

                            <div>
                                <h2 class="h5 fw-bold mb-1">
                                    <?php echo e($booking->booking_code); ?>

                                </h2>

                                <p class="text-muted small mb-0">
                                    <?php echo e($booking->roomCategory->name ?? 'Không xác định'); ?>

                                </p>
                            </div>

                            <div>
                                <?php if($booking->status == 'pending' && $booking->payment_status == 'unpaid'): ?>
                                    <span class="badge text-bg-warning">Chờ thanh toán</span>
                                <?php elseif($booking->status == 'pending'): ?>
                                    <span class="badge text-bg-warning">Chờ xác nhận</span>
                                <?php elseif($booking->status == 'confirmed'): ?>
                                    <span class="badge text-bg-primary">Đã xác nhận</span>
                                <?php elseif($booking->status == 'checked_in'): ?>
                                    <span class="badge text-bg-info">Đã nhận phòng</span>
                                <?php elseif($booking->status == 'checked_out'): ?>
                                    <span class="badge text-bg-success">Đã trả phòng</span>
                                <?php elseif($booking->status == 'completed'): ?>
                                    <span class="badge text-bg-success">Đã hoàn tất</span>
                                <?php elseif($booking->status == 'inspection_requested'): ?>
                                    <span class="badge text-bg-secondary">Đang kiểm tra phòng</span>
                                <?php elseif($booking->status == 'cancelled'): ?>
                                    <span class="badge text-bg-danger">Đã hủy</span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary"><?php echo e($booking->status); ?></span>
                                <?php endif; ?>
                            </div>

                        </div>

                        <div class="table-responsive">

                            <table class="table table-bordered align-middle mb-0">

                                <tbody>

                                    <tr>
                                        <th width="220">Mã booking</th>
                                        <td><?php echo e($booking->booking_code); ?></td>
                                    </tr>

                                    <tr>
                                        <th>Hạng phòng</th>
                                        <td><?php echo e($booking->roomCategory->name ?? 'Không xác định'); ?></td>
                                    </tr>

                                    <tr>
                                        <th>Ngày nhận phòng</th>
                                        <td>
                                            <?php echo e(date('d/m/Y', strtotime($booking->check_in_date))); ?>

                                            <div class="small text-muted">Nhận phòng linh hoạt 13:00–14:00 nếu phòng đã sẵn sàng</div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Ngày trả phòng</th>
                                        <td>
                                            <?php echo e(date('d/m/Y', strtotime($booking->check_out_date))); ?>

                                            <div class="small text-muted">Trả phòng trước 12:00</div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Số người lớn</th>
                                        <td><?php echo e($booking->adult_count); ?></td>
                                    </tr>

                                    <tr>
                                        <th>Số trẻ em</th>
                                        <td><?php echo e($booking->child_count); ?></td>
                                    </tr>

                                    <tr>
                                        <th>Số phòng đặt</th>
                                        <td><?php echo e($booking->room_quantity); ?></td>
                                    </tr>

                                    <tr>
                                        <th>Yêu cầu phòng gần nhau</th>
                                        <td><?php echo e($booking->prefer_adjacent_rooms ? 'Có' : 'Không'); ?></td>
                                    </tr>

                                    <?php if((float) ($booking->discount_amount ?? 0) > 0): ?>
                                        <tr>
                                            <th>Tổng trước ưu đãi</th>
                                            <td><?php echo e(number_format((float) ($booking->subtotal_amount ?? ($booking->estimated_total + $booking->discount_amount)), 0, ',', '.')); ?>đ</td>
                                        </tr>

                                        <tr>
                                            <th>Ưu đãi đã áp dụng</th>
                                            <td class="text-success fw-bold">-<?php echo e(number_format((float) $booking->discount_amount, 0, ',', '.')); ?>đ</td>
                                        </tr>
                                    <?php endif; ?>

                                    <tr>
                                        <th>Tổng tiền tạm tính</th>
                                        <td><?php echo e(number_format($booking->estimated_total, 0, ',', '.')); ?>đ</td>
                                    </tr>

                                    <tr>
                                        <th>Tiền cọc</th>
                                        <td><?php echo e(number_format($booking->deposit_amount, 0, ',', '.')); ?>đ</td>
                                    </tr>

                                    <tr>
                                        <th>Trạng thái thanh toán</th>
                                        <td>
                                            <?php if($booking->payment_status == 'unpaid'): ?>
                                                Chưa thanh toán
                                            <?php elseif($booking->payment_status == 'partial'): ?>
                                                Đã cọc / thanh toán một phần
                                            <?php elseif($booking->payment_status == 'paid'): ?>
                                                Đã thanh toán
                                            <?php else: ?>
                                                Không xác định
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>Ghi chú</th>
                                        <td><?php echo e($booking->note ?? 'Không có ghi chú'); ?></td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>




                    <?php if(($booking->bookingPromotions ?? collect())->count() > 0): ?>
                        <div class="settings-section mb-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <h3 class="h6 fw-bold mb-1">
                                        Mã ưu đãi đã áp dụng
                                    </h3>
                                    <p class="text-muted small mb-0">
                                        Các mã bên dưới đã được tính vào tổng tiền của đơn. Một số mã có thể do khách sạn áp dụng để hỗ trợ trải nghiệm của bạn.
                                    </p>
                                </div>
                                <span class="badge text-bg-success">
                                    -<?php echo e(number_format((float) $booking->bookingPromotions->sum('discount_amount'), 0, ',', '.')); ?>đ
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mã</th>
                                            <th>Loại</th>
                                            <th>Nguồn áp dụng</th>
                                            <th class="text-end">Số tiền giảm</th>
                                            <th>Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $booking->bookingPromotions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingPromotion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo e($bookingPromotion->code_snapshot); ?></td>
                                                <td><?php echo e($bookingPromotion->type_label); ?></td>
                                                <td>
                                                    <?php if($bookingPromotion->applied_channel == 'admin'): ?>
                                                        Khách sạn hỗ trợ
                                                    <?php else: ?>
                                                        Khách tự chọn
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end text-success fw-bold">
                                                    -<?php echo e(number_format((float) $bookingPromotion->discount_amount, 0, ',', '.')); ?>đ
                                                </td>
                                                <td class="small text-muted">
                                                    <?php if($bookingPromotion->applied_channel == 'admin' && $bookingPromotion->promotion_type_snapshot == \App\Models\Promotion::TYPE_SUPPORT): ?>
                                                        Mã hỗ trợ được khách sạn áp dụng cho đơn này.
                                                    <?php else: ?>
                                                        <?php echo e($bookingPromotion->note ?: '---'); ?>

                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                        $canCustomerAddService = in_array($booking->status, ['confirmed', 'checked_in'])
                            && $booking->payment_status !== 'paid';
                    ?>

                    <div class="settings-section mb-4">

                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h3 class="h6 fw-bold mb-1">
                                    Tự thêm dịch vụ
                                </h3>
                                <p class="text-muted small mb-0">
                                    Chọn thêm dịch vụ cần dùng, khách sạn sẽ ghi nhận trực tiếp trên đơn phòng này.
                                </p>
                            </div>

                            <?php if($canCustomerAddService): ?>
                                <span class="badge text-bg-success">Đang mở</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Đã khóa</span>
                            <?php endif; ?>
                        </div>

                        <?php if($canCustomerAddService): ?>
                            <?php if(($availableServices ?? collect())->count() > 0): ?>
                                <form action="<?php echo e(route('bookings.services.store', $booking->id)); ?>" method="POST" id="customerAddServiceForm">
                                    <?php echo csrf_field(); ?>

                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label small">Dịch vụ</label>
                                            <select name="service_id" id="customerServiceSelect" class="form-select" required>
                                                <option value="">-- Chọn dịch vụ --</option>
                                                <?php $__currentLoopData = $availableServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($service->id); ?>"
                                                        data-name="<?php echo e($service->name); ?>"
                                                        data-price="<?php echo e($service->price); ?>"
                                                        data-unit="<?php echo e($service->unit); ?>"
                                                        data-type="<?php echo e($service->type); ?>"
                                                        data-group="<?php echo e($service->service_group ?? 'general'); ?>">
                                                        <?php echo e($service->name); ?> - <?php echo e(number_format((float) $service->price, 0, ',', '.')); ?>đ / <?php echo e($service->unit); ?>

                                                        - <?php echo e($service->group_label ?? ($service->type == 'minibar' ? 'Minibar' : 'Dịch vụ')); ?>

                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label small">Số lượng</label>
                                            <input type="number" name="quantity" id="customerServiceQuantity" class="form-control" value="1" min="1" max="50" required>
                                        </div>

                                        <div class="col-md-5">
                                            <label class="form-label small">Ghi chú</label>
                                            <input type="text" name="note" class="form-control" placeholder="Ví dụ: giao lên phòng sau 19:00">
                                        </div>

                                        <div class="col-md-8">
                                            <div class="alert alert-light border mb-0 small" id="customerServicePreview">
                                                Chọn dịch vụ để xem tạm tính.
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bx bx-plus-circle me-1"></i>
                                                Thêm dịch vụ
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-light border mb-0">
                                    Hiện chưa có dịch vụ nào đang mở bán.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-light border mb-0 small">
                                Chỉ có thể tự thêm dịch vụ sau khi đơn đã thanh toán cọc/thanh toán đủ và được xác nhận.
                            </div>
                        <?php endif; ?>

                    </div>

                    <?php if($booking->serviceItems->count() > 0): ?>
                        <div class="settings-section mb-4">

                            <h3 class="h6 fw-bold mb-3">
                                Dịch vụ / phụ thu phát sinh
                            </h3>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tên khoản thu</th>
                                            <th>Loại</th>
                                            <th>Đơn giá</th>
                                            <th>Số lượng</th>
                                            <th>Thực dùng</th>
                                            <th>Trạng thái</th>
                                            <th>Thành tiền</th>
                                            <th>Ghi chú</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php $__currentLoopData = $booking->serviceItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td><?php echo e($item->name); ?></td>

                                                <td>
                                                    <?php if($item->type == 'service'): ?>
                                                        <span class="badge text-bg-primary">Dịch vụ</span>
                                                    <?php elseif($item->type == 'minibar'): ?>
                                                        <span class="badge text-bg-warning">Minibar</span>
                                                    <?php elseif($item->type == 'damage_fee'): ?>
                                                        <span class="badge text-bg-danger">Hư hại</span>
                                                    <?php elseif($item->type == 'occupancy_fee'): ?>
                                                        <span class="badge text-bg-info">Phụ thu số người</span>
                                                    <?php elseif($item->type == 'policy_violation_fee'): ?>
                                                        <span class="badge text-bg-dark">Vi phạm nội quy</span>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-secondary"><?php echo e($item->type); ?></span>
                                                    <?php endif; ?>
                                                </td>

                                                <td><?php echo e(number_format((float) $item->unit_price, 0, ',', '.')); ?>đ</td>

                                                <td><?php echo e($item->quantity); ?></td>

                                                <td>
                                                    <?php if($item->type == 'minibar'): ?>
                                                        <?php echo e($item->used_quantity ?? 0); ?>/<?php echo e($item->quantity); ?>

                                                    <?php else: ?>
                                                        <?php echo e($item->used_quantity ?? $item->quantity); ?>

                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?php if(($item->billing_status ?? null) == 'confirmed'): ?>
                                                        <span class="badge text-bg-success">Đã tính</span>
                                                    <?php elseif(($item->billing_status ?? null) == 'pending'): ?>
                                                        <span class="badge text-bg-warning">Chờ xác nhận</span>
                                                    <?php elseif(($item->billing_status ?? null) == 'unused'): ?>
                                                        <span class="badge text-bg-secondary">Không dùng</span>
                                                    <?php elseif(($item->billing_status ?? null) == 'cancelled'): ?>
                                                        <span class="badge text-bg-danger">Đã hủy</span>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-light">---</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td class="fw-bold text-danger">
                                                    <?php echo e(number_format((float) $item->total, 0, ',', '.')); ?>đ
                                                </td>

                                                <td><?php echo e($item->note ?: '---'); ?></td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    <?php endif; ?>

                </div>

                <div class="col-lg-4">

                    <div class="settings-section mb-4">

                        <h3 class="h6 fw-bold mb-3">
                            Phòng đã được gán
                        </h3>

                        <?php if($booking->status == 'pending' && $booking->payment_status == 'unpaid'): ?>
                            <div class="alert alert-warning small mb-3">
                                Đơn này chưa thanh toán nên khách sạn chưa gán phòng cụ thể. Sau khi thanh toán cọc/thanh toán đủ thành công, hệ thống sẽ tự động gán phòng còn trống.
                            </div>
                        <?php endif; ?>

                        <?php $__empty_1 = true; $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                            <div class="border rounded p-3 mb-2">
                                <div class="fw-bold">
                                    Phòng <?php echo e($bookingRoom->room->room_number ?? 'Không xác định'); ?>

                                </div>

                                <div class="small text-muted">
                                    Tầng <?php echo e($bookingRoom->room->floor_number ?? '---'); ?>

                                </div>
                            </div>

                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                            <div class="alert alert-warning mb-0">
                                Khách sạn chưa gán phòng cụ thể cho đơn này.
                            </div>

                        <?php endif; ?>

                    </div>


                    <?php
                        $review = $booking->hotelReview ?? null;
                        $reviewEligible = $canReviewBooking ?? in_array($booking->status, ['checked_out', 'completed'], true);
                    ?>

                    <div class="settings-section mb-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h3 class="h6 fw-bold mb-1">Đánh giá khách sạn</h3>
                                <p class="text-muted small mb-0">Đánh giá chỉ mở sau khi đơn đã trả phòng/hoàn tất.</p>
                            </div>
                            <?php if($review): ?>
                                <span class="badge <?php echo e($review->status_badge_class); ?>"><?php echo e($review->status_label); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if($review): ?>
                            <div class="border rounded-3 p-3">
                                <div class="text-warning mb-1"><?php echo e($review->star_text); ?> <span class="text-muted small"><?php echo e(number_format((float) $review->rating, 1)); ?>/5</span></div>
                                <?php if($review->title): ?>
                                    <div class="fw-semibold mb-1"><?php echo e($review->title); ?></div>
                                <?php endif; ?>
                                <p class="small text-muted mb-2"><?php echo e($review->comment); ?></p>

                                <?php if($review->admin_reply): ?>
                                    <div class="alert alert-info small mb-2">
                                        <div class="fw-semibold mb-1">Phản hồi từ khách sạn</div>
                                        <?php echo e($review->admin_reply); ?>

                                    </div>
                                <?php endif; ?>

                                <a href="<?php echo e(route('reviews.edit', $review)); ?>" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="bx bx-edit me-1"></i>
                                    Chỉnh sửa đánh giá
                                </a>
                            </div>
                        <?php elseif($reviewEligible): ?>
                            <a href="<?php echo e(route('bookings.reviews.create', $booking)); ?>" class="btn btn-primary w-100">
                                <i class="bx bx-star me-1"></i>
                                Đánh giá kỳ lưu trú
                            </a>
                        <?php else: ?>
                            <div class="alert alert-light border small mb-0">
                                Bạn sẽ có thể đánh giá sau khi đơn phòng được trả phòng/hoàn tất.
                            </div>
                        <?php endif; ?>
                    </div>



                    <?php if(in_array($booking->status, ['pending', 'confirmed'])): ?>

                        <div class="settings-section">

                            <h3 class="h6 fw-bold mb-3">
                                Thao tác
                            </h3>

                            <form action="<?php echo e(route('bookings.cancel', $booking->id)); ?>" method="POST"
                                onsubmit="return confirm('Bạn có chắc muốn hủy đơn đặt phòng này không?')">

                                <?php echo csrf_field(); ?>

                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="bx bx-x-circle me-1"></i>
                                    Hủy đơn đặt phòng
                                </button>

                            </form>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>
    </main>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const serviceSelect = document.getElementById('customerServiceSelect');
            const quantityInput = document.getElementById('customerServiceQuantity');
            const previewBox = document.getElementById('customerServicePreview');

            if (!serviceSelect || !quantityInput || !previewBox) {
                return;
            }

            function formatMoney(value) {
                return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
            }

            function updatePreview() {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const quantity = Math.max(1, parseInt(quantityInput.value || 1));

                if (!selectedOption || !selectedOption.value) {
                    previewBox.innerHTML = 'Chọn dịch vụ để xem tạm tính.';
                    return;
                }

                const price = parseFloat(selectedOption.dataset.price || 0);
                const unit = selectedOption.dataset.unit || '';
                const type = selectedOption.dataset.type || 'service';
                const total = price * quantity;

                if (type === 'minibar') {
                    previewBox.innerHTML = '<strong>' + selectedOption.dataset.name + '</strong> x ' + quantity
                        + ' · Đơn giá ' + formatMoney(price) + ' / ' + unit
                        + '<br><span class="text-muted">Minibar sẽ được ghi nhận và xác nhận số lượng thực dùng khi trả phòng.</span>';
                    return;
                }

                previewBox.innerHTML = '<strong>' + selectedOption.dataset.name + '</strong> x ' + quantity
                    + ' · Tạm tính thêm <strong class="text-danger">' + formatMoney(total) + '</strong>';
            }

            serviceSelect.addEventListener('change', updatePreview);
            quantityInput.addEventListener('input', updatePreview);
            updatePreview();
        });
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/user/pages/booking-detail.blade.php ENDPATH**/ ?>