<?php $__env->startSection('title', $roomCategory->name); ?>

<?php $__env->startSection('content'); ?>

    <?php
        $now = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInLimitToday = $now->copy()->setTime(14, 0, 0);

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
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-2 text-center">
                                            <div class="text-muted">Vệ sinh</div>
                                            <div class="fw-bold"><?php echo e(number_format((float) $reviewStats->cleanliness_average, 1)); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-2 text-center">
                                            <div class="text-muted">Dịch vụ</div>
                                            <div class="fw-bold"><?php echo e(number_format((float) $reviewStats->service_average, 1)); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-2 text-center">
                                            <div class="text-muted">Vị trí</div>
                                            <div class="fw-bold"><?php echo e(number_format((float) $reviewStats->location_average, 1)); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-2 text-center">
                                            <div class="text-muted">Giá trị</div>
                                            <div class="fw-bold"><?php echo e(number_format((float) $reviewStats->value_average, 1)); ?></div>
                                        </div>
                                    </div>
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

                                <form action="<?php echo e(route('bookings.confirm')); ?>" method="GET">

                                    <input type="hidden" name="room_category_id" value="<?php echo e($roomCategory->id); ?>">

                                    <div class="alert alert-light border small mb-3">
                                        <i class="bx bx-calendar-check me-1"></i>
                                        Ngày bị làm mờ đã kín phòng.
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

                                    <div class="row g-2 mb-3">

                                        <div class="col-4">
                                            <label class="form-label small">
                                                Người lớn
                                            </label>

                                            <select name="adult_count" class="form-select" required>
                                                <?php for($i = 1; $i <= $roomCategory->adult_capacity; $i++): ?>
                                                    <option value="<?php echo e($i); ?>">
                                                        <?php echo e($i); ?>

                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>

                                        <div class="col-4">
                                            <label class="form-label small">
                                                Trẻ em
                                            </label>

                                            <select name="child_count" class="form-select">
                                                <?php for($i = 0; $i <= $roomCategory->child_capacity; $i++): ?>
                                                    <option value="<?php echo e($i); ?>">
                                                        <?php echo e($i); ?>

                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>

                                    </div>

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

                                        const minCheckInDate = "<?php echo e($minOnlineCheckInDate); ?>";
                                        const defaultMinCheckOutDate = "<?php echo e($minOnlineCheckOutDate); ?>";
                                        const fullyBookedDates = <?php echo json_encode($fullyBookedDates ?? [], 15, 512) ?>;
                                        const fullyBookedSet = new Set(fullyBookedDates);
                                        const checkoutBlockedDates = fullyBookedDates.filter(function (dateString) {
                                            const d = new Date(dateString + 'T00:00:00');
                                            d.setDate(d.getDate() - 1);
                                            const prev = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
                                            return fullyBookedSet.has(prev);
                                        });

                                        function addOneDay(dateString) {
                                            const parts = dateString.split('-');

                                            const date = new Date(
                                                Number(parts[0]),
                                                Number(parts[1]) - 1,
                                                Number(parts[2])
                                            );

                                            date.setDate(date.getDate() + 1);

                                            const yyyy = date.getFullYear();
                                            const mm = String(date.getMonth() + 1).padStart(2, '0');
                                            const dd = String(date.getDate()).padStart(2, '0');

                                            return `${yyyy}-${mm}-${dd}`;
                                        }

                                        if (!checkIn || !checkOut) {
                                            return;
                                        }

                                        checkIn.min = minCheckInDate;
                                        checkOut.min = defaultMinCheckOutDate;

                                        function applyUnavailableDates() {
                                            if (checkIn._flatpickr) {
                                                checkIn._flatpickr.set('disable', fullyBookedDates);
                                            }
                                            if (checkOut._flatpickr) {
                                                checkOut._flatpickr.set('disable', checkoutBlockedDates);
                                            }
                                        }
                                        setTimeout(applyUnavailableDates, 0);

                                        if (checkIn.value && checkIn.value < minCheckInDate) {
                                            checkIn.value = '';
                                        }

                                        if (checkOut.value && checkOut.value < defaultMinCheckOutDate) {
                                            checkOut.value = '';
                                        }

                                        if (checkIn.value) {
                                            checkOut.min = addOneDay(checkIn.value);
                                        }

                                        checkIn.addEventListener('change', function () {
                                            if (!this.value) {
                                                checkOut.min = defaultMinCheckOutDate;
                                                checkOut.value = '';
                                                return;
                                            }

                                            if (this.value < minCheckInDate) {
                                                this.value = '';
                                                checkOut.value = '';
                                                checkOut.min = defaultMinCheckOutDate;
                                                return;
                                            }

                                            const nextDay = addOneDay(this.value);

                                            checkOut.min = nextDay;
                                            if (checkOut._flatpickr) {
                                                checkOut._flatpickr.set('minDate', nextDay);
                                                checkOut._flatpickr.set('disable', checkoutBlockedDates);
                                            }

                                            if (!checkOut.value || checkOut.value <= this.value) {
                                                checkOut.value = nextDay;
                                            }
                                        });

                                        checkOut.addEventListener('change', function () {
                                            if (!checkIn.value) {
                                                return;
                                            }

                                            const minCheckOutDate = addOneDay(checkIn.value);

                                            if (this.value && this.value < minCheckOutDate) {
                                                this.value = minCheckOutDate;
                                            }
                                        });
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
                                        Nhận phòng linh hoạt 13:00-14:00 nếu phòng sẵn sàng
                                    </li>

                                    <li class="mb-1">
                                        <i class="bx bx-time-five text-success me-1"></i>
                                        Trả phòng trước 12:00
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

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\user\pages\room-detail.blade.php ENDPATH**/ ?>