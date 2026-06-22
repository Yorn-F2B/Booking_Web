@extends('layouts.user')

@section('title', 'Trang chủ')

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

        $maxAdultCapacity = max(1, (int) ($maxAdultCapacity ?? 1));
        $maxChildCapacity = max(0, (int) ($maxChildCapacity ?? 0));

        $homeSelectedAdultCount = old(
            'adult_count',
            request('adult_count', min(2, $maxAdultCapacity))
        );

        $homeSelectedChildCount = old(
            'child_count',
            request('child_count', 0)
        );
    @endphp
    <!-- Hero + Booking Form -->
    <section class="hero-section position-relative">
        <div class="hero-overlay"></div>
        <div class="container position-relative z-1">
            <div class="row align-items-center min-vh-75">
                <div class="col-lg-6 text-white" data-aos="fade-right">
                    <p class="text-uppercase small mb-2 letter-spacing-2">
                        Khách sạn 4 sao trung tâm thành phố
                    </p>
                    <h1 class="display-4 fw-bold mb-3">
                        Đặt phòng trực tiếp tại MCuong Hotel nhanh và minh bạch.
                    </h1>
                    <p class="lead mb-4">
                        Không gian lưu trú hiện đại, vị trí thuận tiện, dịch vụ chỉn chu
                        và quy trình đặt phòng đơn giản trong vài bước.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="/rooms" class="btn btn-light btn-lg">
                            Khám phá hạng phòng
                        </a>
                        <a href="#booking-section" class="btn btn-outline-light btn-lg">
                            Đặt phòng nhanh
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 ms-lg-auto mt-5 mt-lg-0" data-aos="fade-left" id="booking-section">
                    <div class="card booking-card border-0 shadow-lg">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-3">Tìm phòng trống</h2>
                            <p class="small text-muted mb-3">
                                Nhận phòng linh hoạt: <strong>13:00 - 14:00</strong> nếu phòng sẵn sàng · Trả phòng:
                                <strong>trước 12:00</strong>
                            </p>

                            @if ($onlineBookingClosedToday)
                                <div class="alert alert-warning small mb-3">
                                    Hôm nay đã quá mốc giữ phòng online lúc <strong>14:00</strong>.
                                    Ngày nhận phòng sớm nhất có thể chọn là
                                    <strong>{{ \Carbon\Carbon::parse($minOnlineCheckInDate)->format('d/m/Y') }}</strong>.
                                </div>
                            @endif

                            <form method="GET" action="{{ route('rooms') }}">

                                <div class="row g-2 mb-3">

                                    <div class="col-6">
                                        <label class="form-label">
                                            Nhận phòng
                                        </label>

                                        <input type="date" name="check_in_date" id="home_check_in_date"
                                            class="form-control js-online-check-in" min="{{ $minOnlineCheckInDate }}"
                                            data-min-check-in="{{ $minOnlineCheckInDate }}"
                                            value="{{ request('check_in_date') && request('check_in_date') >= $minOnlineCheckInDate ? request('check_in_date') : '' }}"
                                            required>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">
                                            Trả phòng
                                        </label>

                                        <input type="date" name="check_out_date" id="home_check_out_date"
                                            class="form-control js-online-check-out" min="{{ $minOnlineCheckOutDate }}"
                                            data-min-check-out="{{ $minOnlineCheckOutDate }}"
                                            value="{{ request('check_out_date') && request('check_out_date') >= $minOnlineCheckOutDate ? request('check_out_date') : '' }}"
                                            required>
                                    </div>

                                </div>

                                <div class="row g-2 mb-3">

                                    <div class="col-6">
                                        <label class="form-label">
                                            Người lớn
                                        </label>

                                        <select name="adult_count" id="home_adult_count" class="form-select" required>
                                            <option value="" disabled {{ empty($homeSelectedAdultCount) ? 'selected' : '' }}>
                                                Chọn số người lớn
                                            </option>

                                            @for ($i = 1; $i <= $maxAdultCapacity; $i++)
                                                <option value="{{ $i }}" {{ (string) $homeSelectedAdultCount === (string) $i ? 'selected' : '' }}>
                                                    {{ $i }} người lớn
                                                </option>
                                            @endfor
                                        </select>
                                    </div>

                                    <div class="col-6">
                                        <label class="form-label">
                                            Trẻ em
                                        </label>

                                        <select name="child_count" id="home_child_count" class="form-select" required>
                                            <option value="" disabled {{ $homeSelectedChildCount === '' || $homeSelectedChildCount === null ? 'selected' : '' }}>
                                                Chọn số trẻ em
                                            </option>

                                            @for ($i = 0; $i <= $maxChildCapacity; $i++)
                                                <option value="{{ $i }}" {{ (string) $homeSelectedChildCount === (string) $i ? 'selected' : '' }}>
                                                    {{ $i }} trẻ em
                                                </option>
                                            @endfor
                                        </select>
                                    </div>

                                </div>

                                <div class="row g-2 mb-3">

                                    <div class="col-12">
                                        <label class="form-label">
                                            Hạng phòng
                                        </label>

                                        <select name="room_category_id" id="home_room_category_id" class="form-select">
    <option value="">
        Tất cả hạng phòng
    </option>

    @foreach ($featuredRoomCategories as $category)
        <option value="{{ $category->id }}"
            data-adult-capacity="{{ (int) $category->adult_capacity }}"
            data-child-capacity="{{ (int) $category->child_capacity }}"
            {{ (string) request('room_category_id') === (string) $category->id ? 'selected' : '' }}>
            {{ $category->name }}
        </option>
    @endforeach
</select>
                                    </div>

                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    Kiểm tra phòng trống
                                </button>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Trust / OTA Style -->
    <section class="py-4 bg-white border-bottom">
        <div class="container">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="trust-pill">
                        <i class="bx bx-check-circle"></i>
                        <span>Khách sạn chính thức MCuong</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="trust-pill">
                        <i class="bx bx-time"></i>
                        <span>Xác nhận phòng nhanh</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="trust-pill">
                        <i class="bx bx-shield"></i>
                        <span>Giá minh bạch theo ngày</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="trust-pill">
                        <i class="bx bx-refresh"></i>
                        <span>Chính sách hủy linh hoạt</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Today's Deals -->
    <section class="py-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h4 fw-bold mb-1">Ưu đãi hôm nay tại MCuong</h2>
                    <p class="text-muted mb-0">
                        Phong cách đặt phòng hiện đại: rõ giá gốc, giá ưu đãi, quyền lợi đi kèm.
                    </p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <article class="deal-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge text-bg-danger">-18%</span>
                            <small class="text-muted">Áp dụng CN - T5</small>
                        </div>
                        <h3 class="h6 fw-bold mb-1">Deal ở 2 đêm tiết kiệm</h3>
                        <p class="small text-muted mb-2">Giảm trực tiếp cho hạng Deluxe và Premier.</p>
                        <p class="mb-0 small"><strong>1.476.000đ</strong> <span
                                class="text-muted text-decoration-line-through">1.800.000đ</span></p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="deal-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge text-bg-success">Breakfast Included</span>
                            <small class="text-muted">Không phụ thu</small>
                        </div>
                        <h3 class="h6 fw-bold mb-1">Gói công tác linh hoạt</h3>
                        <p class="small text-muted mb-2">Bao gồm ăn sáng + check-out trễ tới 14:00.</p>
                        <p class="mb-0 small"><strong>Từ 1.550.000đ/đêm</strong></p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="deal-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge text-bg-primary">Best Value</span>
                            <small class="text-muted">Gia đình</small>
                        </div>
                        <h3 class="h6 fw-bold mb-1">Family combo 3N2Đ</h3>
                        <p class="small text-muted mb-2">Tặng 1 bữa tối set menu cho 4 người.</p>
                        <p class="mb-0 small"><strong>Từ 5.900.000đ/gói</strong></p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Rooms -->
    <section class="py-5 bg-light">
        <div class="container">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>
                    <h2 class="h3 fw-bold mb-1" data-aos="fade-right">
                        Hạng phòng nổi bật
                    </h2>

                    <p class="text-muted mb-0" data-aos="fade-right" data-aos-delay="100">
                        Lựa chọn phòng phù hợp cho chuyến đi của bạn.
                    </p>
                </div>

                <a href="{{ route('rooms') }}" class="btn btn-outline-primary d-none d-md-inline-flex" data-aos="fade-left">
                    Xem tất cả phòng
                </a>

            </div>

            <div class="swiper roomsSwiper" data-aos="fade-up">

                <div class="swiper-wrapper">

                    @forelse ($featuredRoomCategories as $category)

                        <div class="swiper-slide">

                            <article class="card room-card h-100 border-0 shadow-sm">

                                <div class="ratio ratio-4x3">

                                    @if ($category->thumbnail)

                                        <img src="{{ asset('storage/' . $category->thumbnail) }}" class="card-img-top"
                                            alt="{{ $category->name }}" style="object-fit: cover;">

                                    @elseif ($category->images->count())

                                        <img src="{{ asset('storage/' . $category->images->first()->image) }}" class="card-img-top"
                                            alt="{{ $category->name }}" style="object-fit: cover;">

                                    @else

                                        <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                            <span class="text-muted">
                                                Chưa có ảnh
                                            </span>
                                        </div>

                                    @endif

                                </div>

                                <div class="card-body">

                                    <span class="badge bg-primary-soft text-primary mb-2">
                                        {{ $category->name }}
                                    </span>

                                    <h3 class="h5">
                                        {{ $category->name }}
                                    </h3>

                                    <p class="small text-muted mb-2">
                                        • {{ $category->area ?? '---' }}m²
                                        • {{ $category->bed_count ?? 1 }} giường
                                    </p>

                                    <p class="small mb-2">
                                        <strong>
                                            Tối đa {{ $category->adult_capacity }} người lớn,
                                            {{ $category->child_capacity }} trẻ em
                                        </strong>
                                    </p>

                                    <ul class="amenity-list mb-3">

                                        @forelse ($category->amenities->take(3) as $amenity)

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

                                    <div class="d-flex justify-content-between align-items-center">

                                        <div>

                                            <span class="fw-bold text-primary fs-5">
                                                {{ number_format($category->price, 0, ',', '.') }}đ
                                            </span>

                                            <span class="text-muted small">
                                                /đêm
                                            </span>

                                        </div>

                                        <a href="{{ route('rooms.show', $category->id) }}"
                                            class="btn btn-outline-primary btn-sm">
                                            Xem chi tiết
                                        </a>

                                    </div>

                                </div>

                            </article>

                        </div>

                    @empty

                        <div class="swiper-slide">

                            <div class="alert alert-info mb-0">
                                Hiện chưa có hạng phòng nào.
                            </div>

                        </div>

                    @endforelse

                </div>

                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>

            </div>

        </div>
    </section>

    <!-- Facilities -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-5" data-aos="fade-right">
                    <h2 class="h3 fw-bold mb-3">Tiện ích &amp; dịch vụ tại MCuong Hotel</h2>
                    <p class="text-muted mb-3">
                        Chúng tôi mang đến trải nghiệm lưu trú trọn vẹn với hệ thống tiện
                        ích đa dạng, phục vụ mọi nhu cầu nghỉ dưỡng và công tác.
                    </p>
                    <ul class="list-unstyled mb-0 text-muted">
                        <li class="mb-2">
                            <i class="bx bx-swim text-primary me-2"></i> Hồ bơi vô cực tầng
                            25, view toàn cảnh biển.
                        </li>
                        <li class="mb-2">
                            <i class="bx bx-spa text-primary me-2"></i> Royal Spa &amp; Sauna
                            chuẩn quốc tế.
                        </li>
                        <li class="mb-2">
                            <i class="bx bx-restaurant text-primary me-2"></i> Nhà hàng
                            Á-Âu, bar sky lounge.
                        </li>
                        <li class="mb-2">
                            <i class="bx bx-briefcase text-primary me-2"></i> Phòng họp
                            &amp; hội nghị tối đa 300 khách.
                        </li>
                    </ul>
                </div>
                <div class="col-lg-7" data-aos="fade-left">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="service-card h-100">
                                <div class="service-icon"><i class="bx bx-swim"></i></div>
                                <h3 class="h6 fw-bold mb-2">Hồ bơi vô cực</h3>
                                <p class="small text-muted mb-0">
                                    Không gian hồ bơi rooftop thư giãn, mở cửa 06:00 - 21:00.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="service-card h-100">
                                <div class="service-icon"><i class="bx bx-restaurant"></i></div>
                                <h3 class="h6 fw-bold mb-2">Nhà hàng &amp; cafe</h3>
                                <p class="small text-muted mb-0">
                                    Thực đơn Á - Âu, buffet sáng và quầy cafe mở tới 22:30.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="service-card h-100">
                                <div class="service-icon"><i class="bx bx-dumbbell"></i></div>
                                <h3 class="h6 fw-bold mb-2">Phòng gym</h3>
                                <p class="small text-muted mb-0">
                                    Trang bị máy chạy, tạ đơn, khu tập cardio dành cho khách lưu trú.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="service-card h-100">
                                <div class="service-icon"><i class="bx bx-briefcase"></i></div>
                                <h3 class="h6 fw-bold mb-2">Phòng họp sự kiện</h3>
                                <p class="small text-muted mb-0">
                                    Không gian hội họp linh hoạt cho doanh nghiệp và sự kiện cá nhân.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Reviews -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="h3 fw-bold mb-1" data-aos="fade-up">
                    Khách hàng nói gì về chúng tôi
                </h2>
                <p class="text-muted" data-aos="fade-up" data-aos-delay="100">
                    Hơn 4.8/5 điểm đánh giá từ 2.300+ lượt đặt phòng.
                </p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="card border-0 shadow-sm h-100 review-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://images.pexels.com/photos/614810/pexels-photo-614810.jpeg"
                                    class="rounded-circle me-3" alt="Khách hàng" width="48" height="48" />
                                <div>
                                    <h3 class="h6 mb-0">Nguyễn Minh Anh</h3>
                                    <small class="text-muted">Gia đình từ Hà Nội</small>
                                </div>
                            </div>
                            <p class="small text-muted mb-2">
                                “Phòng sạch sẽ, view biển rất đẹp. Hồ bơi vô cực siêu chill,
                                buffet sáng đa dạng. Nhân viên cực kỳ dễ thương và hỗ trợ
                                nhiệt tình.”
                            </p>
                            <div class="text-warning small">
                                ★★★★★ <span class="text-muted ms-1">5.0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card border-0 shadow-sm h-100 review-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://images.pexels.com/photos/415829/pexels-photo-415829.jpeg"
                                    class="rounded-circle me-3" alt="Khách hàng" width="48" height="48" />
                                <div>
                                    <h3 class="h6 mb-0">Trần Quốc Huy</h3>
                                    <small class="text-muted">Công tác từ TP.HCM</small>
                                </div>
                            </div>
                            <p class="small text-muted mb-2">
                                “Vị trí đẹp, di chuyển thuận tiện. Phòng họp đầy đủ thiết bị,
                                wifi mạnh. Dịch vụ phòng nhanh, rất phù hợp cho khách công
                                tác.”
                            </p>
                            <div class="text-warning small">
                                ★★★★☆ <span class="text-muted ms-1">4.7</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card border-0 shadow-sm h-100 review-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="https://images.pexels.com/photos/2379004/pexels-photo-2379004.jpeg"
                                    class="rounded-circle me-3" alt="Khách hàng" width="48" height="48" />
                                <div>
                                    <h3 class="h6 mb-0">Lê Bảo Trâm</h3>
                                    <small class="text-muted">Cặp đôi tuần trăng mật</small>
                                </div>
                            </div>
                            <p class="small text-muted mb-2">
                                “Trang trí phòng honeymoon rất dễ thương, có bánh kem &amp;
                                hoa. Nhìn chung trải nghiệm tuyệt vời, sẽ quay lại.”
                            </p>
                            <div class="text-warning small">
                                ★★★★★ <span class="text-muted ms-1">4.9</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="py-5">
        <div class="container">
            <div class="row align-items-center g-3">
                <div class="col-lg-6" data-aos="fade-right">
                    <h2 class="h4 fw-bold mb-2">
                        Nhận ưu đãi độc quyền qua email
                    </h2>
                    <p class="text-muted mb-0">
                        Đăng ký để nhận voucher giảm giá đến 20% cho lần đặt phòng tiếp
                        theo.
                    </p>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <form class="row g-2 justify-content-lg-end" id="newsletterForm">
                        <div class="col-8 col-sm-9">
                            <input type="email" class="form-control" placeholder="Nhập email của bạn" required />
                        </div>
                        <div class="col-4 col-sm-3">
                            <button class="btn btn-dark w-100" type="submit">
                                Đăng ký
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

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
                            ? flatpickr.l10ns.vn
                            : 'default',
                        dateFormat: 'Y-m-d',
                        minDate: minCheckInDate,
                        disableMobile: false,
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
                                ? flatpickr.l10ns.vn
                                : 'default',
                            dateFormat: 'Y-m-d',
                            minDate: minCheckOutDate,
                            disableMobile: false,
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
                                ? flatpickr.l10ns.vn
                                : 'default',
                            dateFormat: 'Y-m-d',
                            minDate: defaultMinCheckOutDate,
                            disableMobile: false,
                            allowInput: false
                        });
                    }
                }
            });

             const categorySelect = document.getElementById('home_room_category_id');
        const adultSelect = document.getElementById('home_adult_count');
        const childSelect = document.getElementById('home_child_count');

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