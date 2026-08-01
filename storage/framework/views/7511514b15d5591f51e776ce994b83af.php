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
                                Booking đang chờ thanh toán để xác nhận.
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

                                                        - <?php echo e($service->type === 'minibar_order' ? 'Minibar gọi thêm' : ($service->group_label ?? 'Dịch vụ')); ?>

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
                                                    <?php elseif($item->type == 'minibar_order'): ?>
                                                        <span class="badge bg-info text-dark">Minibar gọi thêm</span>
                                                    <?php elseif($item->type == 'minibar'): ?>
                                                        <span class="badge text-bg-warning">Minibar kiểm kê</span>
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
                                Đơn này đang chờ thanh toán để xác nhận.
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


                    <?php if(in_array($booking->status, ['checked_in', 'inspection_requested'], true) && $booking->actual_check_in): ?>
                        <div class="settings-section mb-4" id="room-issue-request">
                            <?php
                                $roomIssueGroup = $latestRoomIssueRequest ? $booking->roomIssueRequests->where('group_uuid', $latestRoomIssueRequest->group_uuid)->sortBy('id') : collect();
                                $issueRepairCompleted = $roomIssueGroup->isNotEmpty() && $roomIssueGroup->every(fn($i) => $i->repair_status === 'completed');
                                $issueDisplayStatus = $issueRepairCompleted
                                    ? 'repair_completed'
                                    : ($latestRoomIssueRequest?->status ?? null);
                                $issueStatusLabels = [
                                    'pending' => match($latestRoomIssueRequest?->workflow_status) {
                                        'waiting_guest_confirmation' => 'Đang trao đổi phương án',
                                        'guest_accepted' => 'Khách đã chọn phương án',
                                        'guest_requested_change' => 'Đang điều chỉnh phương án',
                                        default => 'Đang chờ quản lý',
                                    },
                                    'approved' => 'Đã đổi phòng',
                                    'repair_only' => 'Đang khắc phục',
                                    'repair_completed' => 'Đã sửa xong',
                                    'rejected' => 'Đã từ chối',
                                ];
                                $issueStatusClasses = [
                                    'pending' => 'text-bg-warning',
                                    'approved' => 'text-bg-success',
                                    'repair_only' => 'text-bg-info',
                                    'repair_completed' => 'text-bg-success',
                                    'rejected' => 'text-bg-secondary',
                                ];
                                $issueResolutionLabels = [
                                    'same_category' => 'Đổi phòng cùng hạng',
                                    'upgrade_category' => 'Nâng hạng phòng miễn phí',
                                    'no_room' => 'Giữ phòng hiện tại và sửa tại chỗ',
                                ];
                            ?>

                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <h3 class="h6 fw-bold mb-1">
                                        <?php echo e($latestRoomIssueRequest ? 'Yêu cầu hỗ trợ phòng' : 'Phòng đang sử dụng có sự cố?'); ?>

                                    </h3>
                                    <p class="text-muted small mb-0">
                                        <?php echo e($latestRoomIssueRequest
                                            ? 'Trạng thái mới nhất và kết quả xử lý được cập nhật tại đây.'
                                            : 'Báo ngay để khách sạn kiểm tra đổi phòng hoặc hỗ trợ khắc phục.'); ?>

                                    </p>
                                </div>
                                <?php if($latestRoomIssueRequest): ?>
                                    <span class="badge <?php echo e($issueStatusClasses[$issueDisplayStatus] ?? 'text-bg-secondary'); ?>">
                                        <?php echo e($issueStatusLabels[$issueDisplayStatus] ?? $issueDisplayStatus); ?>

                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if($latestRoomIssueRequest): ?>
                                <button type="button" class="btn btn-outline-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#roomIssueDetailModal">
                                    <i class="bx bx-detail me-1"></i> Xem chi tiết sự cố gần nhất
                                </button>
                            <?php endif; ?>
                            <?php if($canRequestRoomIssue): ?>
                                <button type="button" class="btn btn-danger w-100 mt-2" data-bs-toggle="modal" data-bs-target="#roomIssueModal">
                                    <i class="bx bx-error-circle me-1"></i>
                                    <?php echo e($latestRoomIssueRequest ? 'Báo thêm sự cố phòng khác' : 'Báo sự cố / yêu cầu đổi phòng'); ?>

                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if($latestRoomIssueRequest): ?>
                            <div class="modal fade" id="roomIssueDetailModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                        <div class="modal-header border-0 bg-light px-4 py-3">
                                            <div>
                                                <h5 class="modal-title fw-bold mb-1">Chi tiết sự cố phòng</h5>
                                                <div class="small text-muted">Yêu cầu gửi lúc <?php echo e(optional($latestRoomIssueRequest->created_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?></div>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <span class="badge <?php echo e($issueStatusClasses[$issueDisplayStatus] ?? 'text-bg-secondary'); ?>"><?php echo e($issueStatusLabels[$issueDisplayStatus] ?? $issueDisplayStatus); ?></span>
                                                                                            </div>
                                            <?php $__currentLoopData = $roomIssueGroup; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupIssue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $displayResolution = $groupIssue->resolution_type ?: $groupIssue->guest_selected_resolution_type ?: $groupIssue->proposed_resolution_type;
                                                    $targetRoom = $groupIssue->approvedRoom ?: $groupIssue->proposedRoom;
                                                ?>
                                                <div class="border rounded-3 p-3 mb-3">
                                                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                                                        <div><strong>Phòng <?php echo e($groupIssue->currentRoom?->room_number ?? '---'); ?></strong><div class="small text-muted"><?php echo e($groupIssue->issue_description); ?></div></div>
                                                        <span class="badge text-bg-light border"><?php echo e($issueResolutionLabels[$displayResolution] ?? ($displayResolution==='repair_only'?'Giữ nguyên phòng và sửa gấp':'Chưa có phương án')); ?></span>
                                                    </div>
                                                    <?php if($targetRoom): ?><div class="alert alert-info py-2 mt-3 mb-0"><?php echo e($groupIssue->status==='pending'?'Dự kiến đổi':'Đã đổi'); ?> sang phòng <strong><?php echo e($targetRoom->room_number); ?></strong> · <?php echo e($targetRoom->category?->name); ?></div><?php else: ?><div class="alert alert-warning py-2 mt-3 mb-0">Giữ nguyên phòng và chuyển buồng phòng sửa gấp.</div><?php endif; ?>
                                                    <?php if($groupIssue->repair_status === 'completed'): ?>
                                                        <div class="alert alert-success py-2 mt-2 mb-0">
                                                            Đã sửa xong
                                                            <?php if($groupIssue->repair_note): ?>
                                                                : <?php echo e($groupIssue->repair_note); ?>

                                                            <?php endif; ?>
                                                        </div>
                                                    <?php elseif($groupIssue->repair_status === 'waiting'): ?>
                                                        <div class="alert alert-secondary py-2 mt-2 mb-0">Buồng phòng đang xử lý riêng phòng này.</div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                        <div class="modal-footer border-0 pt-0 px-4 pb-4">
                                            <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Đóng</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if($canRequestRoomIssue): ?>
                            <div class="modal fade" id="roomIssueModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content border-0 shadow">
                                        <form action="<?php echo e(route('bookings.room-issues.store', $booking)); ?>" method="POST" enctype="multipart/form-data" id="userRoomIssueForm">
                                            <?php echo csrf_field(); ?>
                                            <div class="modal-header">
                                                <div>
                                                    <h5 class="modal-title fw-bold">Báo sự cố phòng</h5>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-check border rounded-3 p-3 mb-3 bg-light">
                                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="userSelectAllIssueRooms">
                                                    <label class="form-check-label fw-semibold" for="userSelectAllIssueRooms">Chọn tất cả phòng có thể báo sự cố</label>
                                                </div>

                                                <div class="row g-3">
                                                    <?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php if(!$bookingRoom->room) continue; ?>
                                                        <?php
                                                            $roomId = (int) $bookingRoom->room_id;
                                                            $blocked = $activeRoomIssueRoomIds->contains($roomId);
                                                            $selected = in_array($roomId, array_map('intval', old('selected_room_ids', [])), true);
                                                        ?>
                                                        <div class="col-12">
                                                            <div class="border rounded-3 overflow-hidden <?php echo e($blocked ? 'bg-light opacity-75' : ''); ?>">
                                                                <label class="d-flex align-items-start gap-2 p-3 mb-0 <?php echo e($blocked ? '' : 'cursor-pointer'); ?>">
                                                                    <input type="checkbox"
                                                                           class="form-check-input mt-1 js-user-room-issue-selector"
                                                                           name="selected_room_ids[]"
                                                                           value="<?php echo e($roomId); ?>"
                                                                           data-room-id="<?php echo e($roomId); ?>"
                                                                           <?php if($selected && !$blocked): echo 'checked'; endif; ?>
                                                                           <?php if($blocked): echo 'disabled'; endif; ?>>
                                                                    <span>
                                                                        <strong>Phòng <?php echo e($bookingRoom->room->room_number); ?></strong>
                                                                        <span class="text-muted">· <?php echo e($bookingRoom->room->category?->name ?? $booking->roomCategory?->name); ?></span>
                                                                        <?php if($blocked): ?>
                                                                            <span class="d-block small text-warning mt-1">Phòng này đang có yêu cầu sự cố chưa hoàn tất.</span>
                                                                        <?php endif; ?>
                                                                    </span>
                                                                </label>

                                                                <?php if(!$blocked): ?>
                                                                    <div class="border-top bg-light p-3 d-none" id="userRoomIssueDetail<?php echo e($roomId); ?>">
                                                                        <label class="form-label fw-semibold">Sự cố của phòng <?php echo e($bookingRoom->room->room_number); ?></label>
                                                                        <textarea name="issues[<?php echo e($roomId); ?>][description]"
                                                                                  class="form-control mb-3"
                                                                                  rows="4" minlength="10" maxlength="2000"
                                                                                  placeholder="Mô tả rõ sự cố riêng của phòng <?php echo e($bookingRoom->room->room_number); ?>..."
                                                                                  disabled><?php echo e(old("issues.$roomId.description")); ?></textarea>

                                                                        <label class="form-label fw-semibold">Ảnh minh chứng của phòng <?php echo e($bookingRoom->room->room_number); ?> <span class="text-muted fw-normal">(tối đa 5 ảnh)</span></label>
                                                                        <input type="file"
                                                                               id="userRoomIssueImages<?php echo e($roomId); ?>"
                                                                               name="issues[<?php echo e($roomId); ?>][images][]"
                                                                               class="form-control js-camera-capture-input"
                                                                               accept="image/jpeg,image/png,image/webp"
                                                                               multiple
                                                                               data-persistent-files
                                                                               data-camera-button="#userRoomIssueCameraButton<?php echo e($roomId); ?>"
                                                                               data-scan-side="photo"
                                                                               disabled>
                                                                        <div class="d-flex gap-2 align-items-center mt-2 flex-wrap">
                                                                            <button type="button"
                                                                                    id="userRoomIssueCameraButton<?php echo e($roomId); ?>"
                                                                                    class="btn btn-outline-primary btn-sm js-open-camera js-user-room-camera"
                                                                                    data-target-input="#userRoomIssueImages<?php echo e($roomId); ?>"
                                                                                    disabled>
                                                                                <i class="bx bx-camera me-1"></i> Chụp bằng camera
                                                                            </button>
                                                                            <span class="small text-muted">Có thể chọn ảnh có sẵn hoặc chụp trực tiếp.</span>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Để sau</button>
                                                <button type="submit" class="btn btn-danger" id="userSubmitRoomIssues" disabled>
                                                    Xác nhận gửi quản lý (<span id="userSelectedRoomCount">0</span> yêu cầu)
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    const selectors = Array.from(document.querySelectorAll('.js-user-room-issue-selector:not(:disabled)'));
                                    const selectAll = document.getElementById('userSelectAllIssueRooms');
                                    const submit = document.getElementById('userSubmitRoomIssues');
                                    const count = document.getElementById('userSelectedRoomCount');

                                    function syncRoom(checkbox) {
                                        const detail = document.getElementById('userRoomIssueDetail' + checkbox.dataset.roomId);
                                        if (!detail) return;
                                        detail.classList.toggle('d-none', !checkbox.checked);
                                        detail.querySelectorAll('textarea,input[type="file"]').forEach(function (field) {
                                            field.disabled = !checkbox.checked;
                                            if (field.tagName === 'TEXTAREA') field.required = checkbox.checked;
                                        });
                                        detail.querySelectorAll('.js-user-room-camera').forEach(function (button) {
                                            button.disabled = !checkbox.checked;
                                        });
                                    }

                                    function syncAll() {
                                        selectors.forEach(syncRoom);
                                        const selected = selectors.filter(function (item) { return item.checked; }).length;
                                        if (count) count.textContent = selected;
                                        if (submit) submit.disabled = selected === 0;
                                        if (selectAll) {
                                            selectAll.checked = selectors.length > 0 && selected === selectors.length;
                                            selectAll.indeterminate = selected > 0 && selected < selectors.length;
                                        }
                                    }

                                    selectors.forEach(function (checkbox) {
                                        checkbox.addEventListener('change', syncAll);
                                    });
                                    if (selectAll) {
                                        selectAll.addEventListener('change', function () {
                                            selectors.forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
                                            syncAll();
                                        });
                                    }
                                    syncAll();
                                });
                            </script>
                        <?php endif; ?>
                    <?php endif; ?>


                    <?php
                        $review = $booking->hotelReview ?? null;
                        $reviewEligible = $canReviewBooking ?? in_array($booking->status, ['checked_out', 'completed'], true);
                    ?>

                    <div class="settings-section mb-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <h3 class="h6 fw-bold mb-1">Đánh giá khách sạn</h3>
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



                    <?php if($canUseLateArrivalFlow ?? false): ?>
                        <div class="settings-section mb-4" id="late-arrival-request">
                            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                <h4 class="mb-0">Đến muộn</h4>
                                <a class="btn btn-primary" href="<?php echo e(route('bookings.customer-requests.create', $booking)); ?>">
                                    <?php echo e(now('Asia/Ho_Chi_Minh')->hour >= 18 ? 'Cập nhật thời gian check-in' : 'Báo đến muộn'); ?>

                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if($canCustomerCancel ?? false): ?>
                        <div class="settings-section" id="cancel-policy">
                            <h3 class="h6 fw-bold mb-3">Thao tác hủy đơn</h3>
                            <form action="<?php echo e(route('bookings.cancel', $booking->id)); ?>" method="POST"
                                class="js-cancel-booking-form"
                                data-mode="direct"
                                data-policy="<?php echo e($cancellationPolicy['label'] ?? 'Theo chính sách hủy'); ?>"
                                data-paid="<?php echo e($cancellationPolicy['paid_amount'] ?? 0); ?>"
                                data-forfeit="<?php echo e($cancellationPolicy['forfeit_amount'] ?? 0); ?>"
                                data-credit="0"
                                data-cutoff="<?php echo e(optional($cancellationCutoff)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?>">
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

<?php if (! $__env->hasRenderedOnce('74e6d64c-a12d-4f1a-a395-f4ff3b2679df')): $__env->markAsRenderedOnce('74e6d64c-a12d-4f1a-a395-f4ff3b2679df'); ?>
<div class="modal fade" id="cancelBookingPolicyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
    <div class="modal-header">
      <h5 class="modal-title" id="cancelModalTitle">Xác nhận hủy đơn</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div class="alert alert-warning" id="cancelModeMessage"></div>
      <div class="d-grid gap-2">
        <div class="d-flex justify-content-between gap-3"><span>Chính sách áp dụng</span><strong class="text-end" id="cancelPolicyLabel"></strong></div>
        <div class="d-flex justify-content-between"><span>Đã thanh toán</span><strong id="cancelPaid"></strong></div>
        <div class="d-flex justify-content-between text-danger"><span>Khách sạn giữ lại</span><strong id="cancelForfeit"></strong></div>
        <div class="d-flex justify-content-between"><span>Tiền hoàn lại</span><strong>0đ</strong></div>
      </div>
      <div class="mt-3 d-none" id="cancelReasonWrap">
        <label for="cancelReason" class="form-label fw-semibold">Lý do hủy <span class="text-muted fw-normal">(không bắt buộc)</span></label>
        <textarea id="cancelReason" class="form-control" rows="3" maxlength="1000" placeholder="Ví dụ: thay đổi lịch trình, không thể đến đúng kế hoạch..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Không hủy</button>
      <button type="button" class="btn btn-danger" id="confirmCancelBookingButton">Đồng ý hủy đơn</button>
    </div>
  </div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    let activeForm = null;
    const formatMoney = value => new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
    const modalElement = document.getElementById('cancelBookingPolicyModal');
    const confirmButton = document.getElementById('confirmCancelBookingButton');

    document.querySelectorAll('.js-cancel-booking-form').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            activeForm = form;

            const requestMode = form.dataset.mode === 'request';
            document.getElementById('cancelModalTitle').textContent = requestMode
                ? 'Xác nhận hủy qua email'
                : 'Xác nhận hủy đơn';
            document.getElementById('cancelModeMessage').textContent = requestMode
                ? 'Mã xác nhận sẽ được gửi về email.'
                : 'Hủy đơn sẽ mất toàn bộ tiền cọc 30% đã thanh toán. Khoản này không được hoàn lại và không được bảo lưu.';
            document.getElementById('cancelPolicyLabel').textContent = form.dataset.policy || 'Theo chính sách hủy';
            document.getElementById('cancelPaid').textContent = formatMoney(form.dataset.paid);
            document.getElementById('cancelForfeit').textContent = formatMoney(form.dataset.forfeit);
            document.getElementById('cancelReasonWrap').classList.toggle('d-none', !requestMode);
            confirmButton.textContent = requestMode ? 'Gửi mã xác nhận' : 'Đồng ý hủy đơn';

            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        });
    });

    confirmButton?.addEventListener('click', () => {
        if (!activeForm) return;
        const oldInput = activeForm.querySelector('input[name="reason"]');
        if (oldInput) oldInput.remove();

        if (activeForm.dataset.mode === 'request') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'reason';
            input.value = document.getElementById('cancelReason').value || '';
            activeForm.appendChild(input);
        }

        confirmButton.disabled = true;
        activeForm.submit();
    });
});
</script>
<?php endif; ?>


<?php echo $__env->make('partials.camera-capture', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


<script src="<?php echo e(asset('assets/js/persistent-file-inputs.js')); ?>?v=<?php echo e(filemtime(public_path('assets/js/persistent-file-inputs.js'))); ?>"></script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/user/pages/booking-detail.blade.php ENDPATH**/ ?>