@extends('layouts.user')

@section('title', $roomCategory->name)

@section('content')

    @php
        $now = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
        $checkInLimitToday = $now->copy()->setTime(14, 0, 0);

        $minOnlineCheckInDate = $now->greaterThanOrEqualTo($checkInLimitToday)
            ? $now->copy()->addDay()->toDateString()
            : $now->toDateString();

        $minOnlineCheckOutDate = \Carbon\Carbon::parse($minOnlineCheckInDate)
            ->addDay()
            ->toDateString();

        $onlineBookingClosedToday = $now->greaterThanOrEqualTo($checkInLimitToday);
    @endphp

    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">
                {{ $roomCategory->name }}
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

                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <div class="fw-semibold mb-1">
                                    Vui lòng kiểm tra lại thông tin đặt phòng.
                                </div>

                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif

                    <div class="room-gallery mb-4">

                        <div class="swiper roomGallerySwiper rounded-4 overflow-hidden">

                            <div class="swiper-wrapper">

                                @if ($roomCategory->thumbnail)
                                    <div class="swiper-slide">
                                        <img src="{{ asset('storage/' . $roomCategory->thumbnail) }}"
                                            alt="{{ $roomCategory->name }}"
                                            style="width: 100%; height: 420px; object-fit: cover;">
                                    </div>
                                @endif

                                @forelse ($roomCategory->images as $image)
                                    <div class="swiper-slide">
                                        <img src="{{ asset('storage/' . $image->image) }}" alt="{{ $roomCategory->name }}"
                                            style="width: 100%; height: 420px; object-fit: cover;">
                                    </div>
                                @empty
                                    @if (!$roomCategory->thumbnail)
                                        <div class="swiper-slide">
                                            <div class="bg-light d-flex align-items-center justify-content-center"
                                                style="height: 420px;">
                                                <span class="text-muted">
                                                    Chưa có ảnh
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                @endforelse

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
                                {{ $roomCategory->description ?: 'Hạng phòng này hiện chưa có mô tả chi tiết.' }}
                            </p>

                            <div class="mb-4">

                                <h4 class="h6 fw-bold mb-3">
                                    Sức chứa & Tiện nghi
                                </h4>

                                <div class="row g-3 mb-3">

                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2 p-3 border rounded">
                                            <i class="bx bx-user text-primary fs-4"></i>

                                            <div>
                                                <div class="small fw-bold">
                                                    Người lớn
                                                </div>

                                                <div class="small text-muted">
                                                    Tối đa {{ $roomCategory->adult_capacity }} người
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
                                                    Tối đa {{ $roomCategory->child_capacity }} trẻ em
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <h5 class="small fw-bold mb-2">
                                    Tiện nghi phòng
                                </h5>

                                <ul class="amenity-list mb-0">

                                    @forelse ($roomCategory->amenities as $amenity)

                                        <li class="amenity-pill">

                                            @if ($amenity->icon)
                                                <i class="{{ $amenity->icon }} me-1"></i>
                                            @endif

                                            {{ $amenity->name }}

                                        </li>

                                    @empty

                                        <li class="amenity-pill">
                                            Chưa có tiện ích
                                        </li>

                                    @endforelse

                                </ul>

                            </div>

                            <h4 class="h6 fw-bold mb-3">
                                Mô tả chi tiết
                            </h4>

                            <p class="mb-0">
                                {{ $roomCategory->description ?: 'Thông tin chi tiết sẽ được cập nhật sau.' }}
                            </p>

                        </div>

                    </div>


                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                                <div>
                                    <h3 class="h5 fw-bold mb-1">Đánh giá hạng phòng</h3>
                                    <p class="text-muted small mb-0">Các đánh giá đã được xác thực từ khách từng lưu trú.</p>
                                </div>

                                @if (($reviewStats->review_count ?? 0) > 0)
                                    <div class="text-end">
                                        <div class="text-warning fs-5">★ {{ number_format((float) $reviewStats->average_rating, 1) }}/5</div>
                                        <div class="small text-muted">{{ (int) $reviewStats->review_count }} đánh giá</div>
                                    </div>
                                @endif
                            </div>

                            @if (($reviewStats->review_count ?? 0) > 0)
                                <div class="row g-2 mb-4 small">
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-2 text-center">
                                            <div class="text-muted">Vệ sinh</div>
                                            <div class="fw-bold">{{ number_format((float) $reviewStats->cleanliness_average, 1) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-2 text-center">
                                            <div class="text-muted">Dịch vụ</div>
                                            <div class="fw-bold">{{ number_format((float) $reviewStats->service_average, 1) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-2 text-center">
                                            <div class="text-muted">Vị trí</div>
                                            <div class="fw-bold">{{ number_format((float) $reviewStats->location_average, 1) }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="border rounded-3 p-2 text-center">
                                            <div class="text-muted">Giá trị</div>
                                            <div class="fw-bold">{{ number_format((float) $reviewStats->value_average, 1) }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @forelse (($approvedReviews ?? collect()) as $review)
                                <div class="border rounded-3 p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                                style="width:40px;height:40px;">
                                                {{ $review->guest_initials }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $review->guest_name }}</div>
                                                <div class="small text-muted">{{ optional($review->approved_at ?? $review->created_at)->format('d/m/Y') }}</div>
                                            </div>
                                        </div>
                                        <div class="text-warning small text-nowrap">{{ $review->star_text }}</div>
                                    </div>

                                    @if ($review->title)
                                        <div class="fw-semibold mb-1">{{ $review->title }}</div>
                                    @endif

                                    <p class="text-muted small mb-2">{{ $review->comment }}</p>

                                    @if ($review->admin_reply)
                                        <div class="alert alert-info small mb-0">
                                            <div class="fw-semibold mb-1">Phản hồi từ khách sạn</div>
                                            {{ $review->admin_reply }}
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="alert alert-info mb-0">
                                    Hạng phòng này chưa có đánh giá công khai.
                                </div>
                            @endforelse

                            @if (($approvedReviews ?? null) && $approvedReviews->hasPages())
                                <div class="mt-3">
                                    {{ $approvedReviews->links() }}
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                <div class="col-lg-4">

                    <div class="card border-0 shadow-sm sticky-top mb-4" style="top: 90px;">

                        <div class="card-body">

                            <div class="mb-3">

                                <span class="badge bg-primary-soft text-primary mb-2">
                                    {{ $roomCategory->name }}
                                </span>

                                <h2 class="h5 fw-bold mb-1">
                                    {{ number_format($roomCategory->price, 0, ',', '.') }}đ
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

                                <form action="{{ route('bookings.confirm') }}" method="GET">

                                    <input type="hidden" name="room_category_id" value="{{ $roomCategory->id }}">

                                    <div class="row g-2 mb-3">

                                        <div class="col-6">
                                            <label class="form-label small">
                                                Nhận phòng
                                            </label>

                                            <input type="text" name="check_in_date" id="detail_check_in_date"
                                                class="form-control js-online-check-in" min="{{ $minOnlineCheckInDate }}"
                                                data-min-check-in="{{ $minOnlineCheckInDate }}"
                                                value="{{ old('check_in_date') && old('check_in_date') >= $minOnlineCheckInDate ? old('check_in_date') : '' }}"
                                                required>
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label small">
                                                Trả phòng
                                            </label>
                                            <input type="text" name="check_out_date" id="detail_check_out_date"
                                                class="form-control js-online-check-out" min="{{ $minOnlineCheckOutDate }}"
                                                data-min-check-out="{{ $minOnlineCheckOutDate }}"
                                                value="{{ old('check_out_date') && old('check_out_date') >= $minOnlineCheckOutDate ? old('check_out_date') : '' }}"
                                                required>
                                        </div>

                                    </div>

                                    @if ($onlineBookingClosedToday)
                                        <div class="alert alert-warning small mb-3">
                                            Hôm nay đã quá giờ nhận phòng online lúc 14:00.
                                            Ngày nhận phòng sớm nhất có thể chọn là
                                            <strong>{{ \Carbon\Carbon::parse($minOnlineCheckInDate)->format('d/m/Y') }}</strong>.
                                        </div>
                                    @endif

                                    <div class="row g-2 mb-3">

                                        <div class="col-6">
                                            <label class="form-label small">
                                                Người lớn
                                            </label>

                                            <select name="adult_count" class="form-select" required>
                                                @for ($i = 1; $i <= $roomCategory->adult_capacity; $i++)
                                                    <option value="{{ $i }}">
                                                        {{ $i }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label small">
                                                Trẻ em
                                            </label>

                                            <select name="child_count" class="form-select">
                                                @for ($i = 0; $i <= $roomCategory->child_capacity; $i++)
                                                    <option value="{{ $i }}">
                                                        {{ $i }}
                                                    </option>
                                                @endfor
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

                                        const minCheckInDate = "{{ $minOnlineCheckInDate }}";
                                        const defaultMinCheckOutDate = "{{ $minOnlineCheckOutDate }}";

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
                                        Nhận phòng từ 14:00 đến 15:00
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
                                            {{ $roomCategory->area ?? '---' }}m²
                                        </div>

                                        <div class="small text-muted">
                                            Diện tích
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <div class="fw-bold text-primary">
                                            {{ $roomCategory->adult_capacity + $roomCategory->child_capacity }}
                                        </div>

                                        <div class="small text-muted">
                                            Số người tối đa
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <div class="fw-bold text-primary">
                                            {{ $roomCategory->bed_count ?? 1 }}
                                        </div>

                                        <div class="small text-muted">
                                            Giường
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <div class="fw-bold text-primary">
                                            {{ $roomCategory->rooms->count() }}
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

@endsection
