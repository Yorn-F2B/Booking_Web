@extends('layouts.user')

@section('title', 'Trang chủ')

@section('content')
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
                            <form id="quickBookingForm">
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Nhận phòng</label>
                                        <input type="date" class="form-control" id="checkin" required />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Trả phòng</label>
                                        <input type="date" class="form-control" id="checkout" required />
                                    </div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Người lớn</label>
                                        <select class="form-select" id="adults">
                                            <option value="1">1 người lớn</option>
                                            <option value="2" selected>2 người lớn</option>
                                            <option value="3">3 người lớn</option>
                                            <option value="4">4 người lớn</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Trẻ em</label>
                                        <select class="form-select" id="children">
                                            <option value="0" selected>0 trẻ em</option>
                                            <option value="1">1 trẻ em</option>
                                            <option value="2">2 trẻ em</option>
                                            <option value="3">3 trẻ em</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-12">
                                        <label class="form-label">Loại phòng</label>
                                        <select class="form-select" id="roomType">
                                            <option value="deluxe-sea">Phòng Deluxe hướng biển</option>
                                            <option value="premier-city">Phòng Premier hướng phố</option>
                                            <option value="family-suite">Suite gia đình</option>
                                            <option value="presidential-suite">Phòng Tổng thống</option>
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
                        Hang phing noi bat
                    </h2>
                    <p class="text-muted mb-0" data-aos="fade-right" data-aos-delay="100">
                        Lựa chọn phòng phù hợp cho chuyến đi của bạn.
                    </p>
                </div>
                <a href="/rooms" class="btn btn-outline-primary d-none d-md-inline-flex" data-aos="fade-left">
                    Xem tất cả phòng
                </a>
            </div>

            <div class="swiper roomsSwiper" data-aos="fade-up">
                <div class="swiper-wrapper">
                    <!-- Room 1 -->
                    <div class="swiper-slide">
                        <article class="card room-card h-100 border-0 shadow-sm">
                            <div class="ratio ratio-4x3">
                                <img src="https://images.pexels.com/photos/1579253/pexels-photo-1579253.jpeg"
                                    class="card-img-top" alt="Deluxe Sea View" />
                            </div>
                            <div class="card-body">
                                <span class="badge bg-primary-soft text-primary mb-2">Deluxe Sea View</span>
                                <h3 class="h5">Phòng Deluxe view biển</h3>
                                <p class="small text-muted mb-2">
                                    32m² • Ban công riêng • Hướng thẳng ra biển Mỹ Khê.
                                </p>
                                <p class="small mb-2"><strong>Tối đa 2 người lớn</strong></p>
                                <ul class="amenity-list mb-3">
                                    <li class="amenity-pill">WiFi tốc độ cao</li>
                                    <li class="amenity-pill">Buffet sáng</li>
                                    <li class="amenity-pill">Smart TV 55"</li>
                                </ul>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-bold text-primary fs-5">1.800.000đ</span>
                                        <span class="text-muted small">/đêm</span>
                                    </div>
                                    <a href="/room-deluxe-sea" class="btn btn-outline-primary btn-sm">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- Room 2 -->
                    <div class="swiper-slide">
                        <article class="card room-card h-100 border-0 shadow-sm">
                            <div class="ratio ratio-4x3">
                                <img src="https://images.pexels.com/photos/271639/pexels-photo-271639.jpeg"
                                    class="card-img-top" alt="Family Suite" />
                            </div>
                            <div class="card-body">
                                <span class="badge bg-success-soft text-success mb-2">Family Suite</span>
                                <h3 class="h5">Suite gia đình 2 phòng ngủ</h3>
                                <p class="small text-muted mb-2">
                                    60m² • 2 phòng ngủ • Phòng khách riêng, phù hợp gia đình.
                                </p>
                                <p class="small mb-2"><strong>Tối đa 4 người lớn, 2 trẻ em</strong></p>
                                <ul class="amenity-list mb-3">
                                    <li class="amenity-pill">Bồn tắm nằm &amp; đứng</li>
                                    <li class="amenity-pill">Minibar &amp; pantry</li>
                                    <li class="amenity-pill">2 phòng ngủ</li>
                                </ul>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-bold text-primary fs-5">3.200.000đ</span>
                                        <span class="text-muted small">/đêm</span>
                                    </div>
                                    <a href="/room-family-suite" class="btn btn-outline-primary btn-sm">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>

                    <!-- Room 3 -->
                    <div class="swiper-slide">
                        <article class="card room-card h-100 border-0 shadow-sm">
                            <div class="ratio ratio-4x3">
                                <img src="https://images.pexels.com/photos/1571450/pexels-photo-1571450.jpeg"
                                    class="card-img-top" alt="Premier City View" />
                            </div>
                            <div class="card-body">
                                <span class="badge bg-warning-soft text-warning mb-2">Premier City View</span>
                                <h3 class="h5">Phòng Premier view thành phố</h3>
                                <p class="small text-muted mb-2">
                                    28m² • Cửa sổ rộng toàn cảnh thành phố Đà Nẵng.
                                </p>
                                <p class="small mb-2"><strong>Tối đa 2 người lớn</strong></p>
                                <ul class="amenity-list mb-3">
                                    <li class="amenity-pill">Miễn phí đậu xe</li>
                                    <li class="amenity-pill">Smart TV 55"</li>
                                    <li class="amenity-pill">View thành phố</li>
                                </ul>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-bold text-primary fs-5">1.400.000đ</span>
                                        <span class="text-muted small">/đêm</span>
                                    </div>
                                    <a href="/room-premier-city" class="btn btn-outline-primary btn-sm">
                                        Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <!-- Swiper navigation -->
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
@endsection