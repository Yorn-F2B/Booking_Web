@extends('layouts.user')

@section('title', 'Phòng trống')

@section('content')


  @php
    $now = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
    $checkInLimitToday = $now->copy()->setTime(14, 0, 0);

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

    $hasCompleteBookingSearch = $hasCompleteBookingSearch ?? (
        !empty($searchData['check_in_date'])
        && !empty($searchData['check_out_date'])
        && !empty($searchData['adult_count'])
        && array_key_exists('child_count', $searchData ?? [])
        && $searchData['child_count'] !== null
    );
@endphp

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

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('rooms') }}">

                        <div class="row g-3 align-items-end">

                            <div class="col-md-3">
                                <label class="form-label">
                                    Nhận phòng
                                </label>

                                @php
    $currentCheckInDate = old('check_in_date', $searchData['check_in_date'] ?? '');
@endphp

<input type="text"
       name="check_in_date"
       id="rooms_check_in_date"
       class="form-control js-online-check-in"
       min="{{ $minOnlineCheckInDate }}"
       data-min-check-in="{{ $minOnlineCheckInDate }}"
       value="{{ $currentCheckInDate && $currentCheckInDate >= $minOnlineCheckInDate ? $currentCheckInDate : '' }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    Trả phòng
                                </label>

                               @php
    $currentCheckOutDate = old('check_out_date', $searchData['check_out_date'] ?? '');
@endphp

<input type="text"
       name="check_out_date"
       id="rooms_check_out_date"
       class="form-control js-online-check-out"
       min="{{ $minOnlineCheckOutDate }}"
       data-min-check-out="{{ $minOnlineCheckOutDate }}"
       value="{{ $currentCheckOutDate && $currentCheckOutDate >= $minOnlineCheckOutDate ? $currentCheckOutDate : '' }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">
                                    Người lớn
                                </label>
<select name="adult_count" id="rooms_adult_count" class="form-select" required>
    <option value="" disabled {{ empty($currentAdultCount) ? 'selected' : '' }}>
        Chọn số người lớn
    </option>

    @for ($i = 1; $i <= $maxAdultCapacity; $i++)
        <option value="{{ $i }}" {{ (string) $currentAdultCount === (string) $i ? 'selected' : '' }}>
            {{ $i }} người lớn
        </option>
    @endfor
</select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">
                                    Trẻ em
                                </label>

                <select name="child_count" id="rooms_child_count" class="form-select" required>
    <option value="" disabled {{ $currentChildCount === '' || $currentChildCount === null ? 'selected' : '' }}>
        Chọn số trẻ em
    </option>

    @for ($i = 0; $i <= $maxChildCapacity; $i++)
        <option value="{{ $i }}" {{ (string) $currentChildCount === (string) $i ? 'selected' : '' }}>
            {{ $i }} trẻ em
        </option>
    @endfor
</select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">
                                    Hạng phòng
                                </label>

                                <select name="room_category_id" id="rooms_room_category_id" class="form-select">
    <option value="">Tất cả</option>

    @foreach (($filterRoomCategories ?? collect()) as $filterCategory)
        <option value="{{ $filterCategory->id }}"
            data-adult-capacity="{{ (int) $filterCategory->adult_capacity }}"
            data-child-capacity="{{ (int) $filterCategory->child_capacity }}"
            {{ (string) old('room_category_id', $searchData['room_category_id'] ?? '') === (string) $filterCategory->id ? 'selected' : '' }}>
            {{ $filterCategory->name }}
        </option>
    @endforeach
</select>
                            </div>

                            <div class="col-md-12">
                                <div class="alert alert-info mb-0 small">
                                    Hệ thống kiểm tra phòng trống theo chính sách:
                                    nhận phòng linh hoạt <strong>13:00 - 14:00</strong> nếu phòng sẵn sàng,
                                    hệ thống giữ phòng theo mốc <strong>14:00</strong>,
                                    trả phòng <strong>trước 12:00</strong>.

                                    @if ($onlineBookingClosedToday)
                                        <br>
                                        <span class="text-danger fw-semibold">
                                            Hôm nay đã quá mốc giữ phòng online lúc 14:00.
                                            Ngày nhận phòng sớm nhất có thể chọn là
                                            {{ \Carbon\Carbon::parse($minOnlineCheckInDate)->format('d/m/Y') }}.
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Kiểm tra phòng trống
                                </button>

                                <a href="{{ route('rooms') }}" class="btn btn-outline-secondary">
                                    Xóa lọc
                                </a>
                            </div>

                        </div>

                    </form>
                </div>
            </div>

            @if (!empty($searchData['check_in_date']) && !empty($searchData['check_out_date']))
                <div class="alert alert-info">
                    Đang hiển thị các hạng phòng còn phòng trống từ
                    <strong>{{ $searchData['check_in_time'] ?? '14:00' }}</strong>
                    ngày <strong>{{ date('d/m/Y', strtotime($searchData['check_in_date'])) }}</strong>
                    đến
                    <strong>{{ $searchData['check_out_time'] ?? '12:00' }}</strong>
                    ngày <strong>{{ date('d/m/Y', strtotime($searchData['check_out_date'])) }}</strong>.
                </div>
            @endif

            <div class="row g-4">

                @forelse ($roomCategories as $category)

                    <div class="col-12">

                        <article class="card room-card-horizontal border-0 shadow-sm">

                            <div class="row g-0 h-100">

                                <div class="col-md-4">

                                    <div class="ratio ratio-4x3 h-100">

                                        @if ($category->thumbnail)

                                            <img src="{{ asset('storage/' . $category->thumbnail) }}" class="card-img-top h-100"
                                                alt="{{ $category->name }}" style="object-fit: cover;">

                                        @elseif ($category->images->count())

                                            <img src="{{ asset('storage/' . $category->images->first()->image) }}"
                                                class="card-img-top h-100" alt="{{ $category->name }}" style="object-fit: cover;">

                                        @else

                                            <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                                <span class="text-muted">
                                                    Chưa có ảnh
                                                </span>
                                            </div>

                                        @endif

                                    </div>

                                </div>

                                <div class="col-md-8">

                                    <div class="card-body h-100 d-flex flex-column">

                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <span class="badge bg-primary-soft text-primary">
                                                {{ $category->name }}
                                            </span>

                                            @if (!empty($searchData['check_in_date']) && !empty($searchData['check_out_date']))
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    Còn {{ $category->available_rooms_count }} phòng trống
                                                </span>
                                            @endif
                                        </div>

                                        <h2 class="h5">
                                            {{ $category->name }}
                                        </h2>

                                        <p class="small text-muted mb-2">
                                            {{ $category->area ?? 'Chưa cập nhật' }}m²,
                                            {{ $category->bed_count ?? 1 }} giường
                                        </p>

                                        <p class="small mb-2">
                                            <strong>
                                                Tối đa {{ $category->adult_capacity }} người lớn,
                                                {{ $category->child_capacity }} trẻ em
                                            </strong>
                                        </p>

                                        <ul class="amenity-list mb-3">

                                            @forelse ($category->amenities->take(4) as $amenity)

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

                                        <div class="mt-auto d-flex justify-content-between align-items-center">

                                            <div>
                                                <span class="fw-bold text-primary fs-5">
                                                    {{ number_format($category->price, 0, ',', '.') }}đ
                                                </span>

                                                <span class="text-muted small">
                                                    /đêm
                                                </span>
                                            </div>

                                         <div class="d-flex gap-2 flex-wrap">
    <a href="{{ route('rooms.show', $category->id) }}"
        class="btn btn-outline-primary btn-sm">
        Xem chi tiết
    </a>

    @if ($hasCompleteBookingSearch && ($category->available_rooms_count ?? 0) > 0)
        <form method="GET" action="{{ route('bookings.confirm') }}" class="m-0">
            <input type="hidden" name="room_category_id" value="{{ $category->id }}">
            <input type="hidden" name="check_in_date" value="{{ $searchData['check_in_date'] }}">
            <input type="hidden" name="check_out_date" value="{{ $searchData['check_out_date'] }}">
            <input type="hidden" name="adult_count" value="{{ $searchData['adult_count'] }}">
            <input type="hidden" name="child_count" value="{{ $searchData['child_count'] ?? 0 }}">

            @auth
    <button type="submit" class="btn btn-primary btn-sm">
        Đặt phòng
    </button>
@else
    <button type="submit" class="btn btn-primary btn-sm">
        Đăng nhập để đặt phòng
    </button>
@endauth
        </form>
    @else
        <a href="#rooms_check_in_date" class="btn btn-primary btn-sm">
            Chọn ngày để đặt
        </a>
    @endif
</div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </article>

                    </div>

                @empty

                    <div class="col-12">

                        <div class="alert alert-warning mb-0">
                            <div class="fw-semibold mb-1">Không tìm thấy hạng phòng phù hợp.</div>
                            <div class="small">
                                Có thể hạng phòng đã kín từ mốc giữ phòng 14:00 ngày nhận đến 12:00 ngày trả, hoặc số khách vượt sức chứa.
                                Vui lòng đổi ngày, giảm số khách, hoặc chọn hạng phòng khác.
                            </div>
                        </div>

                    </div>

                @endforelse

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

@endsection