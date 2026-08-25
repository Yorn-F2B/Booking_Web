<?php $__env->startSection('title', 'Phòng trống'); ?>

<?php $__env->startSection('content'); ?>


  <?php
    $now = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
    $roomSearchPolicy = app(\App\Services\HotelPolicyService::class);
    $searchStandardCheckIn = substr((string) $roomSearchPolicy->get('stay.standard_check_in_time', '14:00'), 0, 5);
    $searchStandardCheckOut = substr((string) $roomSearchPolicy->get('stay.standard_check_out_time', '12:00'), 0, 5);
    [$checkInHour, $checkInMinute] = array_map('intval', explode(':', $searchStandardCheckIn));
    $checkInLimitToday = $now->copy()->setTime($checkInHour, $checkInMinute, 0);

    $minOnlineCheckInDate = $minOnlineCheckInDate ?? (
        $now->greaterThanOrEqualTo($checkInLimitToday)
            ? $now->copy()->addDay()->toDateString()
            : $now->toDateString()
    );

    $minOnlineCheckOutDate = $minOnlineCheckOutDate ?? \Carbon\Carbon::parse($minOnlineCheckInDate)
        ->addDay()
        ->toDateString();

    $onlineBookingClosedToday = $onlineBookingClosedToday ?? $now->greaterThanOrEqualTo($checkInLimitToday);

    $maxAdultCapacity = max(1, (int) ($maxAdultCapacity ?? 1));
    $maxChildCapacity = max(0, (int) ($maxChildCapacity ?? 0));

    $currentAdultCount = old(
        'adult_count',
        $searchData['adult_count'] ?? min(2, $maxAdultCapacity)
    );

    $currentChildCount = old(
        'child_count',
        $searchData['child_count'] ?? 0
    );

    $currentRoomQuantity = old(
    );

    $hasCompleteBookingSearch = $hasCompleteBookingSearch ?? (
        !empty($searchData['check_in_date'])
        && !empty($searchData['check_out_date'])
        && !empty($searchData['adult_count'])
        && array_key_exists('child_count', $searchData ?? [])
        && $searchData['child_count'] !== null
    );
?>

    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-2">
                Danh sách tất cả phòng tại MCuong Hotel
            </h1>

            <p class="text-muted mb-0">
                Lựa chọn đa dạng từ phòng tiêu chuẩn đến suite cao cấp, phù hợp cho
                cặp đôi, gia đình và khách công tác.
            </p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">
                        Lọc phòng trống
                    </h2>

                    <?php if($errors->any()): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="GET" action="<?php echo e(route('rooms')); ?>">

                        <div class="row g-3 align-items-end">

                            <div class="col-md-3">
                                <label class="form-label">
                                    Nhận phòng
                                </label>

                                <?php
    $currentCheckInDate = old('check_in_date', $searchData['check_in_date'] ?? '');
?>

<input type="text"
       name="check_in_date"
       id="rooms_check_in_date"
       class="form-control js-online-check-in"
       min="<?php echo e($minOnlineCheckInDate); ?>"
       data-min-check-in="<?php echo e($minOnlineCheckInDate); ?>"
       value="<?php echo e($currentCheckInDate && $currentCheckInDate >= $minOnlineCheckInDate ? $currentCheckInDate : ''); ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    Trả phòng
                                </label>

                               <?php
    $currentCheckOutDate = old('check_out_date', $searchData['check_out_date'] ?? '');
?>

<input type="text"
       name="check_out_date"
       id="rooms_check_out_date"
       class="form-control js-online-check-out"
       min="<?php echo e($minOnlineCheckOutDate); ?>"
       data-min-check-out="<?php echo e($minOnlineCheckOutDate); ?>"
       value="<?php echo e($currentCheckOutDate && $currentCheckOutDate >= $minOnlineCheckOutDate ? $currentCheckOutDate : ''); ?>">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">
                                    Người lớn
                                </label>
<select name="adult_count" id="rooms_adult_count" class="form-select" required>
    <option value="" disabled <?php echo e(empty($currentAdultCount) ? 'selected' : ''); ?>>
        Số người lớn
    </option>

    <?php for($i = 1; $i <= $maxAdultCapacity; $i++): ?>
        <option value="<?php echo e($i); ?>" <?php echo e((string) $currentAdultCount === (string) $i ? 'selected' : ''); ?>>
            <?php echo e($i); ?>

        </option>
    <?php endfor; ?>
</select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">
                                    Trẻ em
                                </label>

                <select name="child_count" id="rooms_child_count" class="form-select" required>
    <option value="" disabled <?php echo e($currentChildCount === '' || $currentChildCount === null ? 'selected' : ''); ?>>
        Số trẻ em
    </option>

    <?php for($i = 0; $i <= $maxChildCapacity; $i++): ?>
        <option value="<?php echo e($i); ?>" <?php echo e((string) $currentChildCount === (string) $i ? 'selected' : ''); ?>>
            <?php echo e($i); ?>

        </option>
    <?php endfor; ?>
</select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    Hạng phòng
                                </label>

                                <select name="room_category_id" id="rooms_room_category_id" class="form-select">
    <option value="">Tất cả</option>

    <?php $__currentLoopData = ($filterRoomCategories ?? collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filterCategory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($filterCategory->id); ?>"
            data-adult-capacity="<?php echo e((int) $filterCategory->adult_capacity); ?>"
            data-child-capacity="<?php echo e((int) $filterCategory->child_capacity); ?>"
            <?php echo e((string) old('room_category_id', $searchData['room_category_id'] ?? '') === (string) $filterCategory->id ? 'selected' : ''); ?>>
            <?php echo e($filterCategory->name); ?>

        </option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
                            </div>

                            <div class="col-md-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Kiểm tra phòng trống
                                </button>

                                <a href="<?php echo e(route('rooms')); ?>" class="btn btn-outline-secondary">
                                    Xóa lọc
                                </a>
                            </div>

                        </div>

                    </form>
                </div>
            </div>

            <?php if(!empty($searchData['check_in_date']) && !empty($searchData['check_out_date'])): ?>
                <div class="alert alert-info">
                    Đang hiển thị các hạng phòng còn phòng trống từ
                    <strong><?php echo e($searchData['check_in_time'] ?? $searchStandardCheckIn); ?></strong>
                    ngày <strong><?php echo e(date('d/m/Y', strtotime($searchData['check_in_date']))); ?></strong>
                    đến
                    <strong><?php echo e($searchData['check_out_time'] ?? $searchStandardCheckOut); ?></strong>
                    ngày <strong><?php echo e(date('d/m/Y', strtotime($searchData['check_out_date']))); ?></strong>.
                </div>
            <?php endif; ?>

            <div class="row g-4">

                <?php $__empty_1 = true; $__currentLoopData = $roomCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                    <div class="col-12">

                        <article class="card room-card-horizontal border-0 shadow-sm">

                            <div class="row g-0 h-100">

                                <div class="col-md-4">

                                    <div class="ratio ratio-4x3 h-100">

                                        <?php if($category->thumbnail): ?>

                                            <img src="<?php echo e(asset('storage/' . $category->thumbnail)); ?>" class="card-img-top h-100"
                                                alt="<?php echo e($category->name); ?>" style="object-fit: cover;">

                                        <?php elseif($category->images->count()): ?>

                                            <img src="<?php echo e(asset('storage/' . $category->images->first()->image)); ?>"
                                                class="card-img-top h-100" alt="<?php echo e($category->name); ?>" style="object-fit: cover;">

                                        <?php else: ?>

                                            <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                                <span class="text-muted">
                                                    Chưa có ảnh
                                                </span>
                                            </div>

                                        <?php endif; ?>

                                    </div>

                                </div>

                                <div class="col-md-8">

                                    <div class="card-body h-100 d-flex flex-column">

                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <span class="badge bg-primary-soft text-primary">
                                                <?php echo e($category->name); ?>

                                            </span>

                                            <?php if(!empty($searchData['check_in_date']) && !empty($searchData['check_out_date'])): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    Còn <?php echo e($category->available_rooms_count); ?> phòng trống
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <h2 class="h5">
                                            <?php echo e($category->name); ?>

                                        </h2>

                                        <?php
                                            $categoryReviewStats = ($roomCategoryReviewStats ?? collect())->get($category->id);
                                        ?>

                                        <?php if($categoryReviewStats && (int) $categoryReviewStats->review_count > 0): ?>
                                            <div class="text-warning small mb-2">
                                                ★ <?php echo e(number_format((float) $categoryReviewStats->average_rating, 1)); ?>

                                                <span class="text-muted">/ 5 · <?php echo e((int) $categoryReviewStats->review_count); ?> đánh giá</span>
                                            </div>
                                        <?php endif; ?>

                                        <p class="small text-muted mb-2">
                                            <?php echo e($category->area ?? 'Chưa cập nhật'); ?>m²,
                                            <?php echo e($category->bed_count ?? 1); ?> giường
                                        </p>

                                        <p class="small mb-2">
                                            <strong>
                                                Tối đa <?php echo e($category->adult_capacity); ?> người lớn,
                                                <?php echo e($category->child_capacity); ?> trẻ em
                                            </strong>
                                        </p>

                                        <ul class="amenity-list mb-3">

                                            <?php $__empty_2 = true; $__currentLoopData = $category->amenities->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $amenity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>

                                                <li class="amenity-pill">

                                                    <?php if($amenity->icon): ?>

                                                        <i class="<?php echo e($amenity->icon); ?> me-1"></i>

                                                    <?php endif; ?>

                                                    <?php echo e($amenity->name); ?>


                                                </li>

                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>

                                                <li class="amenity-pill">
                                                    Chưa có tiện ích
                                                </li>

                                            <?php endif; ?>

                                        </ul>

                                        <div class="mt-auto d-flex justify-content-between align-items-center">

                                            <div>
                                                <span class="fw-bold text-primary fs-5">
                                                    <?php echo e(number_format($category->price, 0, ',', '.')); ?>đ
                                                </span>

                                                <span class="text-muted small">
                                                    /đêm
                                                </span>
                                            </div>

                                         <div class="d-flex gap-2 flex-wrap">
    <a href="<?php echo e(route('rooms.show', $category->id)); ?>"
        class="btn btn-outline-primary btn-sm">
        Xem chi tiết
    </a>

    <?php if($hasCompleteBookingSearch && ($category->available_rooms_count ?? 0) > 0): ?>
        <form method="GET" action="<?php echo e(route('bookings.confirm')); ?>" class="m-0">
            <input type="hidden" name="room_category_id" value="<?php echo e($category->id); ?>">
            <input type="hidden" name="check_in_date" value="<?php echo e($searchData['check_in_date']); ?>">
            <input type="hidden" name="check_out_date" value="<?php echo e($searchData['check_out_date']); ?>">
            <input type="hidden" name="adult_count" value="<?php echo e($searchData['adult_count']); ?>">
            <input type="hidden" name="child_count" value="<?php echo e($searchData['child_count'] ?? 0); ?>">

            <?php if(auth()->guard()->check()): ?>
    <button type="submit" class="btn btn-primary btn-sm">
        Đặt phòng
    </button>
<?php else: ?>
    <button type="submit" class="btn btn-primary btn-sm">
        Đăng nhập để đặt phòng
    </button>
<?php endif; ?>
        </form>
    <?php else: ?>
        <a href="#rooms_check_in_date" class="btn btn-primary btn-sm">
            Chọn ngày để đặt
        </a>
    <?php endif; ?>
</div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </article>

                    </div>

                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                    <div class="col-12">

                        <div class="alert alert-warning mb-0">
                            <div class="fw-semibold mb-1">Không tìm thấy hạng phòng phù hợp.</div>
                            <div class="small">
                                Có thể hạng phòng đã kín từ mốc giữ phòng <?php echo e($searchStandardCheckIn); ?> ngày nhận đến <?php echo e($searchStandardCheckOut); ?> ngày trả, hoặc số khách vượt sức chứa.
                                Vui lòng đổi ngày, giảm số khách, hoặc chọn hạng phòng khác.
                            </div>
                        </div>

                    </div>

                <?php endif; ?>

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

         const categorySelect = document.getElementById('rooms_room_category_id');
        const adultSelect = document.getElementById('rooms_adult_count');
        const childSelect = document.getElementById('rooms_child_count');

        if (!categorySelect || !adultSelect || !childSelect) {
            return;
        }

        function applyCapacityFromSelectedCategory() {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];

            if (!selectedOption || !selectedOption.value) {
                adultSelect.value = '';
                childSelect.value = '';
                adultSelect.disabled = false;
                childSelect.disabled = false;
                return;
            }

            const adultCapacity = selectedOption.dataset.adultCapacity || '';
            const childCapacity = selectedOption.dataset.childCapacity || '0';

            adultSelect.value = adultCapacity;
            childSelect.value = childCapacity;

            adultSelect.disabled = false;
            childSelect.disabled = false;
        }

        categorySelect.addEventListener('change', applyCapacityFromSelectedCategory);

        if (categorySelect.value) {
            applyCapacityFromSelectedCategory();
        }
    });
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/user/pages/rooms.blade.php ENDPATH**/ ?>