<?php $__env->startSection('title', 'Tra cứu phòng trống'); ?>

<?php $__env->startSection('content'); ?>

    <div class="admin-wrapper">
        <main class="admin-content">

            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
                <div>
                    <h2 class="mb-1">Tra cứu phòng trống</h2>
                    <div class="text-muted small">
                    </div>
                </div>
            </div>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <strong>Không thể tra cứu:</strong>
                    <ul class="mb-0 mt-2">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div id="roomAvailabilityConfig"
                data-today="<?php echo e($uiData['today']); ?>"
                data-rounded-now-date="<?php echo e($uiData['rounded_now_date']); ?>"
                data-rounded-now-time="<?php echo e($uiData['rounded_now_time']); ?>"
                data-default-checkout-date="<?php echo e($uiData['default_checkout_date']); ?>"
                data-default-checkout-time="<?php echo e($uiData['default_checkout_time']); ?>"
                data-cleaning-buffer-minutes="<?php echo e($uiData['cleaning_buffer_minutes']); ?>">
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="<?php echo e(route('admin.room-availability.index')); ?>">
                        <div class="row g-3 align-items-end">

                            <div class="col-md-3">
                                <label class="form-label">Ngày nhận phòng</label>
                                <input type="text"
                                    name="check_in_date"
                                    id="checkInDate"
                                    class="form-control js-date-picker"
                                    value="<?php echo e(old('check_in_date', $searchData['check_in_date'] ?? request('check_in_date'))); ?>"
                                    autocomplete="off"
                                    required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Giờ nhận</label>
                                <input type="text"
                                    name="check_in_time"
                                    id="checkInTime"
                                    class="form-control js-time-picker"
                                    value="<?php echo e(old('check_in_time', $searchData['check_in_time'] ?? request('check_in_time'))); ?>"
                                    autocomplete="off"
                                    required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Ngày trả phòng</label>
                                <input type="text"
                                    name="check_out_date"
                                    id="checkOutDate"
                                    class="form-control js-date-picker"
                                    value="<?php echo e(old('check_out_date', $searchData['check_out_date'] ?? request('check_out_date'))); ?>"
                                    autocomplete="off"
                                    required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Giờ trả</label>
                                <input type="text"
                                    name="check_out_time"
                                    id="checkOutTime"
                                    class="form-control js-time-picker"
                                    value="<?php echo e(old('check_out_time', $searchData['check_out_time'] ?? request('check_out_time'))); ?>"
                                    autocomplete="off"
                                    required>
                            </div>

                            <div class="col-md-2">
                                <button class="btn btn-primary w-100">
                                    Kiểm tra
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if(!empty($searchData['searched'])): ?>
                <div class="alert alert-info">
                    Đang tra cứu phòng trống từ
                    <strong><?php echo e($searchData['check_in_at']->format('d/m/Y H:i')); ?></strong>
                    đến
                    <strong><?php echo e($searchData['check_out_at']->format('d/m/Y H:i')); ?></strong>.
                </div>

                <?php
                    $totalAvailableRooms = $roomCategories->sum('available_rooms_count');
                    $availableCategoryCount = $roomCategories->where('available_rooms_count', '>', 0)->count();
                    $soldOutCategoryCount = $roomCategories->where('available_rooms_count', '<=', 0)->count();
                ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Tổng phòng trống</div>
                                <div class="fs-3 fw-bold"><?php echo e($totalAvailableRooms); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Số hạng còn phòng</div>
                                <div class="fs-3 fw-bold"><?php echo e($availableCategoryCount); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Số hạng hết phòng</div>
                                <div class="fs-3 fw-bold"><?php echo e($soldOutCategoryCount); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if($roomCategories->count()): ?>
                    <div class="row g-3">
                        <?php $__currentLoopData = $roomCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $hasAvailableRoom = $category->available_rooms_count > 0;

                                $createParams = [
                                    'room_category_id' => $category->id,
                                    'booking_type' => $searchData['quick_booking_type'] ?? 'hourly',
                                    'booking_mode' => $searchData['quick_booking_mode'] ?? 'walk_in',
                                    'check_in_date' => $searchData['check_in_date'],
                                    'check_in_time' => $searchData['check_in_time'],
                                    'check_out_date' => $searchData['check_out_date'],
                                    'check_out_time' => $searchData['check_out_time'],
                                ];
                            ?>

                            <div class="col-lg-4 col-md-6">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <h5 class="mb-1"><?php echo e($category->name); ?></h5>
                                                <div class="text-muted small">
                                                    Giá niêm yết: <?php echo e(number_format($category->price, 0, ',', '.')); ?>đ / đêm
                                                </div>
                                            </div>

                                            <?php if($hasAvailableRoom): ?>
                                                <span class="badge bg-success">Còn phòng</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Hết phòng</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mb-3 flex-grow-1">
                                            <div class="text-muted small">Số phòng trống trong khoảng này</div>
                                            <div class="fs-2 fw-bold">
                                                <?php echo e($category->available_rooms_count); ?>

                                            </div>
                                        </div>

                                        <?php if($hasAvailableRoom): ?>
                                            <a href="<?php echo e(route('admin.bookings.create', $createParams)); ?>" class="btn btn-success mt-auto">
                                                Tạo booking hạng này
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-secondary mt-auto" disabled>
                                                Không thể tạo booking
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Chưa có hạng phòng đang hoạt động để tra cứu.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </main>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>

    <script>
        const configElement = document.getElementById('roomAvailabilityConfig');
        const config = configElement ? configElement.dataset : {};

        const checkInDate = document.getElementById('checkInDate');
        const checkInTime = document.getElementById('checkInTime');
        const checkOutDate = document.getElementById('checkOutDate');
        const checkOutTime = document.getElementById('checkOutTime');

        function padNumber(number) {
            return String(number).padStart(2, '0');
        }

        function formatDateInput(date) {
            const year = date.getFullYear();
            const month = padNumber(date.getMonth() + 1);
            const day = padNumber(date.getDate());

            return `${year}-${month}-${day}`;
        }

        function formatTimeInput(date) {
            return `${padNumber(date.getHours())}:${padNumber(date.getMinutes())}`;
        }

        function parseDateInput(value) {
            if (!value) {
                return null;
            }

            const parts = value.split('-').map(Number);

            if (parts.length !== 3 || parts.some(isNaN)) {
                return null;
            }

            return new Date(parts[0], parts[1] - 1, parts[2]);
        }

        function parseDateTime(dateValue, timeValue) {
            const date = parseDateInput(dateValue);

            if (!date || !timeValue) {
                return null;
            }

            const timeParts = timeValue.split(':').map(Number);

            if (timeParts.length < 2 || timeParts.some(isNaN)) {
                return null;
            }

            date.setHours(timeParts[0], timeParts[1], 0, 0);

            return date;
        }

        function addHours(date, hours) {
            const clonedDate = new Date(date.getTime());
            clonedDate.setHours(clonedDate.getHours() + hours);

            return clonedDate;
        }

        function setFlatpickrDate(input, value) {
            if (!input || !value) {
                return;
            }

            if (input._flatpickr) {
                input._flatpickr.setDate(value, false, 'Y-m-d');
            } else {
                input.value = value;
            }
        }

        function setFlatpickrTime(input, value) {
            if (!input || !value) {
                return;
            }

            if (input._flatpickr) {
                input._flatpickr.setDate(value, false, 'H:i');
            } else {
                input.value = value;
            }
        }

        function setDateMin(input, minDate) {
            if (!input || !minDate) {
                return;
            }

            input.setAttribute('min', minDate);

            if (input._flatpickr) {
                input._flatpickr.set('minDate', minDate);
            }
        }

        function initFlatpickr() {
            if (typeof flatpickr === 'undefined') {
                return;
            }

            const locale = flatpickr.l10ns && flatpickr.l10ns.vn ? 'vn' : 'default';

            document.querySelectorAll('.js-date-picker').forEach(function (input) {
                flatpickr(input, {
                    locale: locale,
                    altInput: true,
                    altFormat: 'd/m/Y',
                    dateFormat: 'Y-m-d',
                    allowInput: false,
                    disableMobile: true,
                });
            });

            document.querySelectorAll('.js-time-picker').forEach(function (input) {
                flatpickr(input, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                    minuteIncrement: 15,
                    allowInput: false,
                    disableMobile: true,
                });
            });
        }

        function ensureDefaultValues() {
            const defaultCheckInDate = config.roundedNowDate || config.today;
            const defaultCheckInTime = config.roundedNowTime || '14:00';
            const defaultCheckOutDate = config.defaultCheckoutDate || defaultCheckInDate;
            const defaultCheckOutTime = config.defaultCheckoutTime || '16:00';

            if (checkInDate && !checkInDate.value) {
                setFlatpickrDate(checkInDate, defaultCheckInDate);
            }

            if (checkInTime && !checkInTime.value) {
                setFlatpickrTime(checkInTime, defaultCheckInTime);
            }

            if (checkOutDate && !checkOutDate.value) {
                setFlatpickrDate(checkOutDate, defaultCheckOutDate);
            }

            if (checkOutTime && !checkOutTime.value) {
                setFlatpickrTime(checkOutTime, defaultCheckOutTime);
            }
        }

        function normalizeCheckout() {
            if (!checkInDate || !checkInTime || !checkOutDate || !checkOutTime) {
                return;
            }

            setDateMin(checkInDate, config.today);
            setDateMin(checkOutDate, checkInDate.value || config.today);

            const checkInAt = parseDateTime(checkInDate.value, checkInTime.value);
            const checkOutAt = parseDateTime(checkOutDate.value, checkOutTime.value);

            if (!checkInAt) {
                return;
            }

            if (!checkOutAt || checkOutAt <= checkInAt) {
                const nextCheckoutAt = addHours(checkInAt, 2);
                setFlatpickrDate(checkOutDate, formatDateInput(nextCheckoutAt));
                setFlatpickrTime(checkOutTime, formatTimeInput(nextCheckoutAt));
            }
        }

        initFlatpickr();
        ensureDefaultValues();
        normalizeCheckout();

        [checkInDate, checkInTime, checkOutDate, checkOutTime].forEach(function (input) {
            if (!input) {
                return;
            }

            input.addEventListener('change', normalizeCheckout);
        });
    </script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\room-availability\index.blade.php ENDPATH**/ ?>