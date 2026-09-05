<?php $__env->startSection('title', $roomCategory->name); ?>

<?php $__env->startSection('content'); ?>

    <?php
        $now = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $policyService = app(\App\Services\HotelPolicyService::class);
        $standardCheckIn = substr((string) $policyService->get('stay.standard_check_in_time', '14:00'), 0, 5);
        $standardCheckOut = substr((string) $policyService->get('stay.standard_check_out_time', '12:00'), 0, 5);
        $earlyFreeFrom = substr((string) $policyService->get('stay.early_checkin_free_from', '12:00'), 0, 5);
        [$checkInHour, $checkInMinute] = array_map('intval', explode(':', $standardCheckIn));
        $checkInLimitToday = $now->copy()->setTime($checkInHour, $checkInMinute, 0);

        $minOnlineCheckInDate = $now->greaterThanOrEqualTo($checkInLimitToday)
            ? $now->copy()->addDay()->toDateString()
            : $now->toDateString();

        $minOnlineCheckOutDate = \Carbon\Carbon::parse($minOnlineCheckInDate)
            ->addDay()
            ->toDateString();

        $onlineBookingClosedToday = $now->greaterThanOrEqualTo($checkInLimitToday);
    ?>

    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">
                <?php echo e($roomCategory->name); ?>

            </h1>

            <p class="text-muted mb-0">
                Xem chi tiết hạng phòng, tiện ích và đặt phòng nhanh.
            </p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">

            <div class="row g-4 align-items-start">

                <div class="col-lg-8">

<?php if($errors->any()): ?>
                            <div class="alert alert-danger">
                                <div class="fw-semibold mb-1">
                                    Vui lòng kiểm tra lại thông tin đặt phòng.
                                </div>

                                <ul class="mb-0">
                                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><?php echo e($error); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </div>
                    <?php endif; ?>

                    <div class="room-gallery mb-4">

                        <div class="swiper roomGallerySwiper rounded-4 overflow-hidden">

                            <div class="swiper-wrapper">

                                <?php if($roomCategory->thumbnail): ?>
                                    <div class="swiper-slide">
                                        <img src="<?php echo e(asset('storage/' . $roomCategory->thumbnail)); ?>"
                                            alt="<?php echo e($roomCategory->name); ?>"
                                            style="width: 100%; height: 420px; object-fit: cover;">
                                    </div>
                                <?php endif; ?>

                                <?php $__empty_1 = true; $__currentLoopData = $roomCategory->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <div class="swiper-slide">
                                        <img src="<?php echo e(asset('storage/' . $image->image)); ?>" alt="<?php echo e($roomCategory->name); ?>"
                                            style="width: 100%; height: 420px; object-fit: cover;">
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <?php if(!$roomCategory->thumbnail): ?>
                                        <div class="swiper-slide">
                                            <div class="bg-light d-flex align-items-center justify-content-center"
                                                style="height: 420px;">
                                                <span class="text-muted">
                                                    Chưa có ảnh
                                                </span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                            </div>

                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-pagination"></div>

                        </div>

                    </div>

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-body">

                            <h3 class="h5 fw-bold mb-3">
                                Thông tin phòng
                            </h3>

                            <p class="mb-3">
                                <?php echo e($roomCategory->description ?: 'Hạng phòng này hiện chưa có mô tả chi tiết.'); ?>

                            </p>

                            <div class="mb-4">

                                <h4 class="h6 fw-bold mb-3">
                                    Thông số phòng
                                </h4>

                                <div class="row g-3 mb-3">

                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2 p-3 border rounded">
                                            <i class="bx bx-ruler text-primary fs-4"></i>

                                            <div>
                                                <div class="small fw-bold">
                                                    Diện tích
                                                </div>

                                                <div class="small text-muted">
                                                    <?php echo e($roomCategory->area ?? 'Chưa cập nhật'); ?>m²
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2 p-3 border rounded">
                                            <i class="bx bx-bed text-primary fs-4"></i>

                                            <div>
                                                <div class="small fw-bold">
                                                    Số giường
                                                </div>

                                                <div class="small text-muted">
                                                    <?php echo e($roomCategory->bed_count ?? 1); ?> giường
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2 p-3 border rounded">
                                            <i class="bx bx-user text-primary fs-4"></i>

                                            <div>
                                                <div class="small fw-bold">
                                                    Người lớn
                                                </div>

                                                <div class="small text-muted">
                                                    Tối đa <?php echo e($roomCategory->adult_capacity); ?> người
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2 p-3 border rounded">
                                            <i class="bx bx-child text-primary fs-4"></i>

                                            <div>
                                                <div class="small fw-bold">
                                                    Trẻ em
                                                </div>

                                                <div class="small text-muted">
                                                    Tối đa <?php echo e($roomCategory->child_capacity); ?> trẻ em
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <h5 class="small fw-bold mb-2">
                                    Tiện nghi phòng
                                </h5>

                                <ul class="amenity-list mb-0">

                                    <?php $__empty_1 = true; $__currentLoopData = $roomCategory->amenities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amenity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                                        <li class="amenity-pill">

                                            <?php if($amenity->icon): ?>
                                                <i class="<?php echo e($amenity->icon); ?> me-1"></i>
                                            <?php endif; ?>

                                            <?php echo e($amenity->name); ?>


                                        </li>

                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                                        <li class="amenity-pill">
                                            Chưa có tiện ích
                                        </li>

                                    <?php endif; ?>

                                </ul>

                            </div>

                            <h4 class="h6 fw-bold mb-3">
                                Mô tả chi tiết
                            </h4>

                            <p class="mb-0">
                                <?php echo e($roomCategory->description ?: 'Thông tin chi tiết sẽ được cập nhật sau.'); ?>

                            </p>

                        </div>

                    </div>


                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                                <div>
                                    <h3 class="h5 fw-bold mb-1">Đánh giá hạng phòng</h3>
                                </div>

                                <?php if(($reviewStats->review_count ?? 0) > 0): ?>
                                    <div class="text-end">
                                        <div class="text-warning fs-5">★ <?php echo e(number_format((float) $reviewStats->average_rating, 1)); ?>/5</div>
                                        <div class="small text-muted"><?php echo e((int) $reviewStats->review_count); ?> đánh giá</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if(($reviewStats->review_count ?? 0) > 0): ?>
                                <div class="row g-2 mb-4 small">
                                    <?php $__currentLoopData = [
                                        ['Sạch sẽ', $reviewStats->cleanliness_average],
                                        ['Chất lượng / tiện nghi phòng', $reviewStats->room_quality_average],
                                        ['Nhân viên', $reviewStats->staff_average],
                                        ['Dịch vụ', $reviewStats->service_average],
                                        ['Thoải mái', $reviewStats->comfort_average],
                                        ['Giá trị', $reviewStats->value_average],
                                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="col-6 col-md-4">
                                            <div class="border rounded-3 p-2 text-center h-100">
                                                <div class="text-muted"><?php echo e($label); ?></div>
                                                <div class="fw-bold"><?php echo e(number_format((float) $value, 1)); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>

                            <?php $__empty_1 = true; $__currentLoopData = ($approvedReviews ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <div class="border rounded-3 p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                                style="width:40px;height:40px;">
                                                <?php echo e($review->guest_initials); ?>

                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?php echo e($review->guest_name); ?></div>
                                                <div class="small text-muted"><?php echo e(optional($review->approved_at ?? $review->created_at)->format('d/m/Y')); ?></div>
                                            </div>
                                        </div>
                                        <div class="text-warning small text-nowrap"><?php echo e($review->star_text); ?></div>
                                    </div>

                                    <?php if($review->title): ?>
                                        <div class="fw-semibold mb-1"><?php echo e($review->title); ?></div>
                                    <?php endif; ?>

                                    <p class="text-muted small mb-2"><?php echo e($review->comment); ?></p>

                                    <?php if($review->admin_reply): ?>
                                        <div class="alert alert-info small mb-0">
                                            <div class="fw-semibold mb-1">Phản hồi từ khách sạn</div>
                                            <?php echo e($review->admin_reply); ?>

                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="alert alert-info mb-0">
                                    Hạng phòng này chưa có đánh giá công khai.
                                </div>
                            <?php endif; ?>

                            <?php if(($approvedReviews ?? null) && $approvedReviews->hasPages()): ?>
                                <div class="mt-3">
                                    <?php echo e($approvedReviews->links()); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <div class="col-lg-4">

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-body">

                            <div class="mb-3">

                                <span class="badge bg-primary-soft text-primary mb-2">
                                    <?php echo e($roomCategory->name); ?>

                                </span>

                                <h2 class="h5 fw-bold mb-1">
                                    <?php echo e(number_format($roomCategory->price, 0, ',', '.')); ?>đ
                                    <span class="text-muted small">
                                        /đêm
                                    </span>
                                </h2>

                                <p class="small text-muted mb-0">
                                    Giá tạm tính cho 1 đêm nghỉ
                                </p>

                            </div>

                            <div class="border-top pt-3 mb-4">

                                <h3 class="h6 fw-bold mb-3">
                                    Đặt phòng nhanh
                                </h3>

                                <form action="<?php echo e(route('bookings.confirm')); ?>" method="GET" id="roomDetailBookingForm">

                                    <input type="hidden" name="room_category_id" value="<?php echo e($roomCategory->id); ?>">

                                    <div class="alert alert-light border small mb-3" id="detail_availability_status">
                                        <i class="bx bx-calendar-check me-1"></i>
                                        Chọn ngày và số khách để kiểm tra số phòng trống thực tế.
                                    </div>

                                    <div class="row g-2 mb-3">

                                        <div class="col-6">
                                            <label class="form-label small">
                                                Nhận phòng
                                            </label>

                                            <input type="text" name="check_in_date" id="detail_check_in_date"
                                                class="form-control js-online-check-in" min="<?php echo e($minOnlineCheckInDate); ?>"
                                                data-min-check-in="<?php echo e($minOnlineCheckInDate); ?>"
                                                value="<?php echo e(old('check_in_date') && old('check_in_date') >= $minOnlineCheckInDate ? old('check_in_date') : ''); ?>"
                                                required>
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label small">
                                                Trả phòng
                                            </label>
                                            <input type="text" name="check_out_date" id="detail_check_out_date"
                                                class="form-control js-online-check-out" min="<?php echo e($minOnlineCheckOutDate); ?>"
                                                data-min-check-out="<?php echo e($minOnlineCheckOutDate); ?>"
                                                value="<?php echo e(old('check_out_date') && old('check_out_date') >= $minOnlineCheckOutDate ? old('check_out_date') : ''); ?>"
                                                required>
                                        </div>

                                    </div>

                                    <?php
                                        $maxBookingGuests = max(1, (int) app(\App\Services\HotelPolicyService::class)->get('booking.max_online_guests', 60));
                                        $maxBookingRooms = max(1, (int) app(\App\Services\HotelPolicyService::class)->get('booking.max_online_rooms', 30));
                                    ?>
                                    <div class="row g-2 mb-2">
                                        <div class="col-3">
                                            <label class="form-label small" for="detail_adult_count">Người lớn</label>
                                            <input type="number" id="detail_adult_count" name="adult_count" class="form-control" min="1" max="<?php echo e($maxBookingGuests); ?>" value="<?php echo e(old('adult_count', 2)); ?>" required>
                                        </div>

                                        <div class="col-3">
                                            <label class="form-label small" for="detail_child_count">Trẻ em</label>
                                            <input type="number" id="detail_child_count" name="child_count" class="form-control" min="0" max="<?php echo e($maxBookingGuests); ?>" value="<?php echo e(old('child_count', 0)); ?>">
                                        </div>

                                        <div class="col-3">
                                            <label class="form-label small" for="detail_baby_count">Em bé</label>
                                            <input type="number" id="detail_baby_count" name="baby_count" class="form-control" min="0" max="<?php echo e($maxBookingGuests); ?>" value="<?php echo e(old('baby_count', 0)); ?>">
                                        </div>

                                        <div class="col-3">
                                            <label class="form-label small" for="detail_room_quantity">Số phòng</label>
                                            <input type="number" id="detail_room_quantity" name="room_quantity" class="form-control" min="1" max="<?php echo e($maxBookingRooms); ?>" value="<?php echo e(old('room_quantity', 1)); ?>" required>
                                        </div>
                                    </div>
                                    <div id="detail_room_quantity_hint" class="form-text mb-3"></div>

                                    <div class="mb-3">
                                        <label class="form-label small">
                                            Ghi chú
                                        </label>

                                        <textarea name="note" rows="2" class="form-control"></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-2">

                                        <i class="bx bx-calendar-check me-1"></i>
                                        Đặt phòng ngay

                                    </button>

                                </form>

                                <script>
                                    document.addEventListener('DOMContentLoaded', function () {
                                        const checkIn = document.getElementById('detail_check_in_date');
                                        const checkOut = document.getElementById('detail_check_out_date');
                                        const adultInput = document.getElementById('detail_adult_count');
                                        const childInput = document.getElementById('detail_child_count');
                                        const babyInput = document.getElementById('detail_baby_count');
                                        const roomQuantityInput = document.getElementById('detail_room_quantity');
                                        const roomQuantityHint = document.getElementById('detail_room_quantity_hint');
                                        const availabilityStatus = document.getElementById('detail_availability_status');
                                        const bookingForm = document.getElementById('roomDetailBookingForm');
                                        const submitButton = bookingForm?.querySelector('button[type="submit"]');
                                        const adultCapacityPerRoom = <?php echo e(max(1, (int) $roomCategory->adult_capacity)); ?>;
                                        const childCapacityPerRoom = <?php echo e(max(0, (int) $roomCategory->child_capacity)); ?>;
                                        const maxBookingGuests = <?php echo e($maxBookingGuests ?? 60); ?>;
                                        const maxBookingRooms = <?php echo e($maxBookingRooms ?? 30); ?>;
                                        const minCheckInDate = <?php echo json_encode($minOnlineCheckInDate, 15, 512) ?>;
                                        const defaultMinCheckOutDate = <?php echo json_encode($minOnlineCheckOutDate, 15, 512) ?>;
                                        const availabilityUrl = <?php echo json_encode(route('rooms.availability', $roomCategory), 512) ?>;
                                        let availabilityData = null;
                                        let availabilityKey = null;
                                        let availabilityTimer = null;
                                        let availabilityRequest = null;

                                        function values() {
                                            return {
                                                adults: Math.max(1, parseInt(adultInput?.value || '1', 10)),
                                                children: Math.max(0, parseInt(childInput?.value || '0', 10)),
                                                babies: Math.max(0, parseInt(babyInput?.value || '0', 10)),
                                                rooms: Math.max(1, parseInt(roomQuantityInput?.value || '1', 10)),
                                            };
                                        }

                                        function minimumRoomsForGuests(adults, children, babies) {
                                            const roomsForAdults = Math.ceil(adults / adultCapacityPerRoom);
                                            const minors = children + babies;
                                            if (minors > 0 && childCapacityPerRoom < 1) {
                                                return Number.POSITIVE_INFINITY;
                                            }
                                            const roomsForChildren = minors > 0 ? Math.ceil(minors / childCapacityPerRoom) : 1;
                                            return Math.max(1, roomsForAdults, roomsForChildren);
                                        }

                                        function currentAvailabilityKey() {
                                            return [checkIn?.value || '', checkOut?.value || '', adultInput?.value || '', childInput?.value || '0', babyInput?.value || '0'].join('|');
                                        }

                                        function setStatus(message, type) {
                                            if (!availabilityStatus) return;
                                            availabilityStatus.className = `alert small mb-3 ${type === 'danger' ? 'alert-danger' : (type === 'success' ? 'alert-success' : 'alert-light border')}`;
                                            availabilityStatus.textContent = message;
                                        }

                                        function refreshRoomQuantityHint() {
                                            if (!adultInput || !childInput || !roomQuantityInput || !roomQuantityHint) return false;
                                            const { adults, children, babies, rooms } = values();
                                            const totalGuests = adults + children + babies;
                                            const minimumRooms = minimumRoomsForGuests(adults, children, babies);
                                            let error = null;

                                            if (totalGuests > maxBookingGuests) {
                                                error = `Tổng số khách không được vượt quá ${maxBookingGuests} người.`;
                                            } else if (!Number.isFinite(minimumRooms) || minimumRooms > maxBookingRooms) {
                                                error = childCapacityPerRoom < 1 && (children + babies) > 0
                                                    ? 'Hạng phòng này không nhận trẻ em/em bé theo sức chứa đã cấu hình. Vui lòng chọn hạng khác.'
                                                    : 'Số khách vượt giới hạn số phòng có thể đặt trong một booking.';
                                            } else if (rooms > adults) {
                                                error = `${rooms} phòng cần tối thiểu ${rooms} người lớn đại diện.`;
                                            } else if (rooms < minimumRooms || adults > adultCapacityPerRoom * rooms || (children + babies) > childCapacityPerRoom * rooms) {
                                                error = `Cần tối thiểu ${minimumRooms} phòng để đủ sức chứa cho đoàn khách.`;
                                            } else if (availabilityData && availabilityKey === currentAvailabilityKey() && (!availabilityData.capacity_possible || !availabilityData.inventory_enough)) {
                                                error = availabilityData.message || 'Hạng phòng này không đủ sức chứa hoặc không còn đủ phòng trong khoảng đã chọn.';
                                            } else if (availabilityData && availabilityKey === currentAvailabilityKey() && rooms > Number(availabilityData.max_bookable_rooms || 0)) {
                                                error = `Khoảng ngày này chỉ có thể đặt tối đa ${availabilityData.max_bookable_rooms} phòng của hạng này.`;
                                            }

                                            if (error) {
                                                roomQuantityHint.textContent = error;
                                                roomQuantityHint.className = 'form-text text-danger mb-3';
                                                if (submitButton) submitButton.disabled = true;
                                                return false;
                                            }

                                            const inventoryText = availabilityData && availabilityKey === currentAvailabilityKey()
                                                ? ` Còn ${availabilityData.available_rooms} phòng trống trong khoảng đã chọn.`
                                                : '';
                                            roomQuantityHint.textContent = `Gợi ý tối thiểu ${minimumRooms} phòng.${inventoryText}`;
                                            roomQuantityHint.className = 'form-text text-muted mb-3';
                                            if (submitButton) submitButton.disabled = false;
                                            return true;
                                        }

                                        function scheduleAvailabilityCheck() {
                                            clearTimeout(availabilityTimer);
                                            availabilityTimer = setTimeout(checkAvailability, 250);
                                        }

                                        async function checkAvailability() {
                                            if (!checkIn?.value || !checkOut?.value) {
                                                availabilityData = null;
                                                availabilityKey = null;
                                                setStatus('Chọn ngày nhận/trả phòng để kiểm tra tồn phòng thực tế.', 'neutral');
                                                refreshRoomQuantityHint();
                                                return;
                                            }

                                            const { adults, children, babies } = values();
                                            if (!refreshRoomQuantityHint() && adults + children + babies > maxBookingGuests) {
                                                return;
                                            }

                                            if (availabilityRequest) {
                                                availabilityRequest.abort();
                                            }
                                            availabilityRequest = new AbortController();
                                            const requestedKey = currentAvailabilityKey();
                                            const params = new URLSearchParams({
                                                check_in_date: checkIn.value,
                                                check_out_date: checkOut.value,
                                                adult_count: String(adults),
                                                child_count: String(children),
                                                baby_count: String(babies),
                                            });
                                            setStatus('Đang kiểm tra số phòng trống...', 'neutral');

                                            try {
                                                const response = await fetch(`${availabilityUrl}?${params.toString()}`, {
                                                    headers: { 'Accept': 'application/json' },
                                                    signal: availabilityRequest.signal,
                                                });
                                                const data = await response.json().catch(() => ({}));
                                                if (requestedKey !== currentAvailabilityKey()) return;
                                                if (!response.ok) {
                                                    availabilityData = null;
                                                    availabilityKey = null;
                                                    setStatus(data.message || 'Thông tin ngày hoặc số khách chưa hợp lệ.', 'danger');
                                                    refreshRoomQuantityHint();
                                                    return;
                                                }

                                                availabilityData = data;
                                                availabilityKey = requestedKey;
                                                const minimumRooms = Number(data.minimum_rooms || 1);
                                                const maxBookable = Number(data.max_bookable_rooms || 0);
                                                roomQuantityInput.max = String(Math.max(1, maxBookable));
                                                if (data.inventory_enough && maxBookable > 0) {
                                                    let currentRooms = Math.max(1, parseInt(roomQuantityInput.value || '1', 10));
                                                    if (currentRooms < minimumRooms) currentRooms = minimumRooms;
                                                    if (currentRooms > maxBookable) currentRooms = maxBookable;
                                                    roomQuantityInput.value = String(currentRooms);
                                                    setStatus(data.message, 'success');
                                                } else {
                                                    setStatus(data.message, 'danger');
                                                }
                                                refreshRoomQuantityHint();
                                            } catch (error) {
                                                if (error.name === 'AbortError') return;
                                                availabilityData = null;
                                                availabilityKey = null;
                                                setStatus('Không thể kiểm tra nhanh tồn phòng lúc này. Hệ thống vẫn sẽ kiểm tra lại khi bạn sang bước xác nhận.', 'neutral');
                                                refreshRoomQuantityHint();
                                            }
                                        }

                                        function addOneDay(dateString) {
                                            const parts = dateString.split('-');
                                            const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
                                            date.setDate(date.getDate() + 1);
                                            const yyyy = date.getFullYear();
                                            const mm = String(date.getMonth() + 1).padStart(2, '0');
                                            const dd = String(date.getDate()).padStart(2, '0');
                                            return `${yyyy}-${mm}-${dd}`;
                                        }

                                        if (checkIn && checkOut) {
                                            checkIn.min = minCheckInDate;
                                            checkOut.min = defaultMinCheckOutDate;
                                            if (checkIn.value && checkIn.value < minCheckInDate) checkIn.value = '';
                                            if (checkOut.value && checkOut.value < defaultMinCheckOutDate) checkOut.value = '';
                                            if (checkIn.value) checkOut.min = addOneDay(checkIn.value);

                                            checkIn.addEventListener('change', function () {
                                                if (!this.value || this.value < minCheckInDate) {
                                                    if (this.value < minCheckInDate) this.value = '';
                                                    checkOut.value = '';
                                                    checkOut.min = defaultMinCheckOutDate;
                                                    scheduleAvailabilityCheck();
                                                    return;
                                                }
                                                const nextDay = addOneDay(this.value);
                                                checkOut.min = nextDay;
                                                if (checkOut._flatpickr) checkOut._flatpickr.set('minDate', nextDay);
                                                if (!checkOut.value || checkOut.value <= this.value) checkOut.value = nextDay;
                                                scheduleAvailabilityCheck();
                                            });

                                            checkOut.addEventListener('change', function () {
                                                if (checkIn.value) {
                                                    const minimum = addOneDay(checkIn.value);
                                                    if (this.value && this.value < minimum) this.value = minimum;
                                                }
                                                scheduleAvailabilityCheck();
                                            });
                                        }

                                        [adultInput, childInput, babyInput].forEach(function (input) {
                                            input?.addEventListener('input', function () {
                                                availabilityData = null;
                                                availabilityKey = null;
                                                roomQuantityInput.max = String(maxBookingRooms);
                                                refreshRoomQuantityHint();
                                                scheduleAvailabilityCheck();
                                            });
                                        });
                                        roomQuantityInput?.addEventListener('input', refreshRoomQuantityHint);

                                        bookingForm?.addEventListener('submit', function (event) {
                                            const locallyValid = refreshRoomQuantityHint();
                                            const currentKey = currentAvailabilityKey();
                                            if (!locallyValid) {
                                                event.preventDefault();
                                                return;
                                            }
                                            if (availabilityData && availabilityKey === currentKey) {
                                                const rooms = values().rooms;
                                                if (!availabilityData.inventory_enough || rooms > Number(availabilityData.max_bookable_rooms || 0)) {
                                                    event.preventDefault();
                                                    setStatus(availabilityData.message || 'Không đủ phòng trống cho lựa chọn hiện tại.', 'danger');
                                                }
                                            }
                                        });

                                        refreshRoomQuantityHint();
                                        if (checkIn?.value && checkOut?.value) scheduleAvailabilityCheck();
                                    });
                                </script>

                            </div>

                            <div class="border-top pt-3">

                                <h3 class="h6 fw-bold mb-2">
                                    Chính sách
                                </h3>

                                <ul class="list-unstyled small text-muted mb-0">

                                    <li class="mb-1">
                                        <i class="bx bx-time text-success me-1"></i>
                                        Nhận phòng linh hoạt <?php echo e($earlyFreeFrom); ?>-<?php echo e($standardCheckIn); ?> nếu phòng sẵn sàng
                                    </li>

                                    <li class="mb-1">
                                        <i class="bx bx-time-five text-success me-1"></i>
                                        Trả phòng theo giờ chuẩn <?php echo e($standardCheckOut); ?>

                                    </li>

                                    <li class="mb-1">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Miễn phí kiểm tra tình trạng phòng
                                    </li>

                                    <li class="mb-1">
                                        <i class="bx bx-check text-success me-1"></i>
                                        Admin sẽ xác nhận booking sau khi đặt
                                    </li>

                                    <li>
                                        <i class="bx bx-check text-success me-1"></i>
                                        Có thể bổ sung dịch vụ sau
                                    </li>

                                </ul>

                            </div>

                        </div>

                    </div>

                    <div class="card border-0 shadow-sm mb-4">

                        <div class="card-body">

                            <h3 class="h6 fw-bold mb-3">
                                Thông số phòng
                            </h3>

                            <div class="row g-2">

                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <div class="fw-bold text-primary">
                                            <?php echo e($roomCategory->area ?? '---'); ?>m²
                                        </div>

                                        <div class="small text-muted">
                                            Diện tích
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <div class="fw-bold text-primary">
                                            <?php echo e($roomCategory->adult_capacity + $roomCategory->child_capacity); ?>

                                        </div>

                                        <div class="small text-muted">
                                            Số người tối đa
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <div class="fw-bold text-primary">
                                            <?php echo e($roomCategory->bed_count ?? 1); ?>

                                        </div>

                                        <div class="small text-muted">
                                            Giường
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <div class="fw-bold text-primary">
                                            <?php echo e($roomCategory->rooms->count()); ?>

                                        </div>

                                        <div class="small text-muted">
                                            Số phòng
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </main>


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-online-check-in').forEach(function (checkInInput) {
                const form = checkInInput.closest('form');
                if (!form) {
                    return;
                }

                const checkOutInput = form.querySelector('.js-online-check-out');
                const minCheckInDate = checkInInput.dataset.minCheckIn;

                checkInInput.min = minCheckInDate;

                if (checkInInput.value && checkInInput.value < minCheckInDate) {
                    checkInInput.value = '';
                }

                if (typeof flatpickr !== 'undefined') {
                    if (checkInInput._flatpickr) {
                        checkInInput._flatpickr.destroy();
                    }

                    flatpickr(checkInInput, {
                        locale: typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.vn
                            ? 'vn'
                            : 'default',
                        altInput: true,
                        altFormat: 'd/m/Y',
                        dateFormat: 'Y-m-d',
                        minDate: minCheckInDate,
                        disableMobile: true,
                        allowInput: false,
                        onChange: function (selectedDates, dateStr) {
                            syncCheckOutMinDate(dateStr);
                        }
                    });
                }

                function addOneDay(dateString) {
                    const parts = dateString.split('-');

                    const date = new Date(
                        Number(parts[0]),
                        Number(parts[1]) - 1,
                        Number(parts[2])
                    );

                    date.setDate(date.getDate() + 1);

                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');

                    return `${year}-${month}-${day}`;
                }

                function syncCheckOutMinDate(checkInDate) {
                    if (!checkOutInput || !checkInDate) {
                        return;
                    }

                    const minCheckOutDate = addOneDay(checkInDate);

                    checkOutInput.min = minCheckOutDate;
                    checkOutInput.dataset.minCheckOut = minCheckOutDate;

                    if (checkOutInput.value && checkOutInput.value < minCheckOutDate) {
                        checkOutInput.value = minCheckOutDate;
                    }

                    if (typeof flatpickr !== 'undefined') {
                        if (checkOutInput._flatpickr) {
                            checkOutInput._flatpickr.destroy();
                        }

                        flatpickr(checkOutInput, {
                            locale: typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.vn
                                ? 'vn'
                                : 'default',
                            altInput: true,
                            altFormat: 'd/m/Y',
                            dateFormat: 'Y-m-d',
                            minDate: minCheckOutDate,
                            disableMobile: true,
                            allowInput: false
                        });
                    }
                }

                if (checkInInput.value) {
                    syncCheckOutMinDate(checkInInput.value);
                } else if (checkOutInput) {
                    const defaultMinCheckOutDate = checkOutInput.dataset.minCheckOut || checkOutInput.min;

                    checkOutInput.min = defaultMinCheckOutDate;

                    if (checkOutInput.value && checkOutInput.value < defaultMinCheckOutDate) {
                        checkOutInput.value = '';
                    }

                    if (typeof flatpickr !== 'undefined') {
                        if (checkOutInput._flatpickr) {
                            checkOutInput._flatpickr.destroy();
                        }

                        flatpickr(checkOutInput, {
                            locale: typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.vn
                                ? 'vn'
                                : 'default',
                            altInput: true,
                            altFormat: 'd/m/Y',
                            dateFormat: 'Y-m-d',
                            minDate: defaultMinCheckOutDate,
                            disableMobile: true,
                            allowInput: false
                        });
                    }
                }
            });
        });
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/user/pages/room-detail.blade.php ENDPATH**/ ?>