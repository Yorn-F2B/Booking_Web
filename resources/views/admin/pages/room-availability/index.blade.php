@extends('layouts.admin')

@section('title', 'Tra cứu phòng trống')

@section('content')

    <div class="admin-wrapper">
        <main class="admin-content">

            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
                <div>
                    <h2 class="mb-1">Tra cứu phòng trống</h2>
                    <div class="text-muted small">
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Không thể tra cứu:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="roomAvailabilityConfig"
                data-today="{{ $uiData['today'] }}"
                data-rounded-now-date="{{ $uiData['rounded_now_date'] }}"
                data-rounded-now-time="{{ $uiData['rounded_now_time'] }}"
                data-default-checkout-date="{{ $uiData['default_checkout_date'] }}"
                data-default-checkout-time="{{ $uiData['default_checkout_time'] }}"
                data-current-timestamp-ms="{{ $uiData['current_timestamp_ms'] ?? '' }}"
                data-auto-current-check-in="{{ !empty($uiData['auto_current_check_in']) ? '1' : '0' }}"
                data-cleaning-buffer-minutes="{{ $uiData['cleaning_buffer_minutes'] }}">
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.room-availability.index') }}">
                        <div class="row g-3 align-items-end">

                            <div class="col-md-2">
                                <label class="form-label">Ngày nhận phòng</label>
                                <input type="text"
                                    name="check_in_date"
                                    id="checkInDate"
                                    class="form-control js-date-picker"
                                    value="{{ old('check_in_date', $searchData['check_in_date'] ?? request('check_in_date')) }}"
                                    autocomplete="off"
                                    required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Giờ nhận</label>
                                <input type="text"
                                    name="check_in_time"
                                    id="checkInTime"
                                    class="form-control js-time-picker"
                                    value="{{ old('check_in_time', $searchData['check_in_time'] ?? request('check_in_time')) }}"
                                    autocomplete="off"
                                    required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Ngày trả phòng</label>
                                <input type="text"
                                    name="check_out_date"
                                    id="checkOutDate"
                                    class="form-control js-date-picker"
                                    value="{{ old('check_out_date', $searchData['check_out_date'] ?? request('check_out_date')) }}"
                                    autocomplete="off"
                                    required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Giờ trả</label>
                                <input type="text"
                                    name="check_out_time"
                                    id="checkOutTime"
                                    class="form-control js-time-picker"
                                    value="{{ old('check_out_time', $searchData['check_out_time'] ?? request('check_out_time')) }}"
                                    autocomplete="off"
                                    required>
                            </div>

                            <div class="col-sm-6 col-lg-2">
                                <label class="form-label">Người lớn</label>
                                <input type="number" name="adult_count" class="form-control" min="1" max="{{ (int) ($uiData['max_online_guests'] ?? 60) }}" value="{{ old('adult_count', $searchData['adult_count'] ?? 2) }}" required>
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <label class="form-label">Trẻ em</label>
                                <input type="number" name="child_count" class="form-control" min="0" max="{{ (int) ($uiData['max_online_guests'] ?? 60) }}" value="{{ old('child_count', $searchData['child_count'] ?? 0) }}" required>
                            </div>
                            <div class="col-lg-12">
                                <button class="btn btn-primary">Kiểm tra</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if (!empty($searchData['searched']))
                <div class="alert alert-info">
                    Đang tra cứu phòng trống từ
                    <strong>{{ $searchData['check_in_at']->format('d/m/Y H:i') }}</strong>
                    đến
                    <strong>{{ $searchData['check_out_at']->format('d/m/Y H:i') }}</strong>.
                </div>

                @php
                    $totalAvailableRooms = $roomCategories->sum('available_rooms_count');
                    $availableCategoryCount = $roomCategories->where('available_rooms_count', '>', 0)->count();
                    $soldOutCategoryCount = $roomCategories->where('available_rooms_count', '<=', 0)->count();
                @endphp

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Tổng phòng trống</div>
                                <div class="fs-3 fw-bold">{{ $totalAvailableRooms }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Số hạng còn phòng</div>
                                <div class="fs-3 fw-bold">{{ $availableCategoryCount }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">
                                <div class="text-muted small mb-1">Số hạng hết phòng</div>
                                <div class="fs-3 fw-bold">{{ $soldOutCategoryCount }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(($searchData['recommendations'] ?? collect())->isNotEmpty())
                    <div class="card shadow-sm border-0 mb-4"><div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <div><h4 class="mb-1">Phương án cho {{ $searchData['adult_count'] + $searchData['child_count'] }} khách</h4><div class="small text-muted">Chỉ gợi ý phương án còn đủ phòng thật trong toàn bộ khoảng đã tra cứu.</div></div>
                        </div>
                        <div class="row g-3">
                            @foreach($searchData['recommendations'] as $option)
                                @php
                                    $recommendedCreateParams = [
                                        'room_category_id'=>$option['room_category_id'], 'room_quantity'=>$option['room_quantity'],
                                        'adult_count'=>$searchData['adult_count'], 'child_count'=>$searchData['child_count'],
                                        'booking_type'=>$searchData['quick_booking_type'] ?? 'overnight', 'booking_mode'=>$searchData['quick_booking_mode'] ?? 'advance',
                                        'check_in_date'=>$searchData['check_in_date'], 'check_in_time'=>$searchData['check_in_time'],
                                        'check_out_date'=>$searchData['check_out_date'], 'check_out_time'=>$searchData['check_out_time'],
                                    ];
                                @endphp
                                <div class="col-lg-4"><div class="border rounded-3 p-3 h-100">
                                    <div class="d-flex gap-1 flex-wrap mb-2">@foreach($option['labels'] as $label)<span class="badge bg-warning-subtle text-dark">{{ $label }}</span>@endforeach</div>
                                    <div class="fw-bold fs-5">{{ $option['category_name'] }} × {{ $option['room_quantity'] }}</div>
                                    <div class="small text-muted">Còn {{ $option['available_rooms'] }} phòng phù hợp · dự kiến dùng {{ implode(', ', $option['room_numbers']) }}</div>
                                    <div class="fw-bold text-primary mt-2">{{ number_format($option['estimated_room_total'],0,',','.') }}đ tiền phòng dự kiến</div>
                                    @if($searchData['quick_booking_available'] ?? true)<a href="{{ route('admin.bookings.create',$recommendedCreateParams) }}" class="btn btn-primary w-100 mt-3">Tạo booking theo phương án</a>@endif
                                </div></div>
                            @endforeach
                        </div>
                    </div></div>
                @endif

                @if ($roomCategories->count())
                    <div class="row g-3">
                        @foreach ($roomCategories as $category)
                            @php
                                $hasAvailableRoom = $category->available_rooms_count > 0;
                                $matchingRecommendation = collect($searchData['recommendations'] ?? [])->firstWhere('room_category_id', (int) $category->id);
                                $canFitRequestedGuests = !empty($matchingRecommendation);

                                $createParams = [
                                    'room_category_id' => $category->id,
                                    'booking_type' => $searchData['quick_booking_type'] ?? 'hourly',
                                    'booking_mode' => $searchData['quick_booking_mode'] ?? 'walk_in',
                                    'check_in_date' => $searchData['check_in_date'],
                                    'check_in_time' => $searchData['check_in_time'],
                                    'check_out_date' => $searchData['check_out_date'],
                                    'check_out_time' => $searchData['check_out_time'],
                                    'adult_count' => $searchData['adult_count'],
                                    'child_count' => $searchData['child_count'],
                                    'room_quantity' => $matchingRecommendation['room_quantity'] ?? 1,
                                ];
                            @endphp

                            <div class="col-lg-4 col-md-6">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <h5 class="mb-1">{{ $category->name }}</h5>
                                                <div class="text-muted small">
                                                    Giá niêm yết: {{ number_format($category->price, 0, ',', '.') }}đ / đêm
                                                </div>
                                            </div>

                                            @if ($hasAvailableRoom)
                                                <span class="badge bg-success">Còn phòng</span>
                                            @else
                                                <span class="badge bg-secondary">Hết phòng</span>
                                            @endif
                                        </div>

                                        <div class="mb-3 flex-grow-1">
                                            <div class="text-muted small">Số phòng trống trong khoảng này</div>
                                            <div class="fs-2 fw-bold">
                                                {{ $category->available_rooms_count }}
                                            </div>
                                        </div>

                                        @if ($hasAvailableRoom && $canFitRequestedGuests && ($searchData['quick_booking_available'] ?? true))
                                            <a href="{{ route('admin.bookings.create', $createParams) }}" class="btn btn-success mt-auto">
                                                Tạo booking hạng này
                                            </a>
                                        @elseif ($hasAvailableRoom && $canFitRequestedGuests)
                                            <button type="button" class="btn btn-outline-secondary mt-auto" disabled>
                                                Ở ngay chỉ áp dụng hôm nay
                                            </button>
                                        @elseif ($hasAvailableRoom)
                                            <button type="button" class="btn btn-outline-warning mt-auto" disabled>
                                                Không đủ phòng/sức chứa cho số khách
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-outline-secondary mt-auto" disabled>
                                                Không thể tạo booking
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning">
                        Chưa có hạng phòng đang hoạt động để tra cứu.
                    </div>
                @endif
            @endif

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

        function addDays(date, days) {
            const clonedDate = new Date(date.getTime());
            clonedDate.setDate(clonedDate.getDate() + days);

            return clonedDate;
        }

        function getActualNow() {
            return new Date();
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
                    minuteIncrement: 1,
                    allowInput: false,
                    disableMobile: true,
                });
            });
        }

        let autoCurrentCheckIn = config.autoCurrentCheckIn === '1';
        let checkoutTimeWasManuallyChanged = false;

        function ensureDefaultValues() {
            const actualNow = getActualNow();
            const defaultCheckInDate = formatDateInput(actualNow);
            const defaultCheckInTime = formatTimeInput(actualNow);
            const defaultCheckOutDate = config.defaultCheckoutDate || formatDateInput(addDays(actualNow, 1));
            const defaultCheckOutTime = config.defaultCheckoutTime || '12:00';

            if (checkInDate && (autoCurrentCheckIn || !checkInDate.value)) {
                setFlatpickrDate(checkInDate, defaultCheckInDate);
            }

            if (checkInTime && (autoCurrentCheckIn || !checkInTime.value)) {
                setFlatpickrTime(checkInTime, defaultCheckInTime);
            }

            if (checkOutDate && !checkOutDate.value) {
                setFlatpickrDate(checkOutDate, defaultCheckOutDate);
            }

            if (checkOutTime && !checkOutTime.value) {
                setFlatpickrTime(checkOutTime, defaultCheckOutTime);
            }
        }

        function refreshCurrentCheckIn() {
            if (!autoCurrentCheckIn || !checkInDate || !checkInTime) {
                return;
            }

            const actualNow = getActualNow();
            setFlatpickrDate(checkInDate, formatDateInput(actualNow));
            setFlatpickrTime(checkInTime, formatTimeInput(actualNow));
            normalizeCheckout();
        }

        function normalizeCheckout() {
            if (!checkInDate || !checkInTime || !checkOutDate || !checkOutTime) {
                return;
            }

            const actualNow = getActualNow();
            setDateMin(checkInDate, formatDateInput(actualNow));
            setDateMin(checkOutDate, checkInDate.value || formatDateInput(actualNow));

            const checkInAt = parseDateTime(checkInDate.value, checkInTime.value);
            const checkOutAt = parseDateTime(checkOutDate.value, checkOutTime.value);

            if (!checkInAt) {
                return;
            }

            if (!checkOutAt || checkOutAt <= checkInAt) {
                const nextDay = addDays(checkInAt, 1);
                setFlatpickrDate(checkOutDate, formatDateInput(nextDay));
                if (!checkoutTimeWasManuallyChanged) {
                    setFlatpickrTime(checkOutTime, config.defaultCheckoutTime || '12:00');
                }
            }
        }

        initFlatpickr();
        ensureDefaultValues();
        normalizeCheckout();

        [checkInDate, checkInTime].forEach(function (input) {
            if (!input) {
                return;
            }

            input.addEventListener('change', function () {
                autoCurrentCheckIn = false;
                normalizeCheckout();
            });
        });

        if (checkOutDate) {
            checkOutDate.addEventListener('change', normalizeCheckout);
        }

        if (checkOutTime) {
            checkOutTime.addEventListener('change', function () {
                checkoutTimeWasManuallyChanged = true;
                normalizeCheckout();
            });
        }

        const availabilityForm = checkInDate ? checkInDate.closest('form') : null;
        if (availabilityForm) {
            availabilityForm.addEventListener('submit', function () {
                refreshCurrentCheckIn();
            });
        }

        if (autoCurrentCheckIn) {
            window.setInterval(refreshCurrentCheckIn, 30000);
        }
    </script>

@endsection
