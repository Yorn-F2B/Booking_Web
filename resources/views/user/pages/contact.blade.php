@extends('layouts.user')

@section('title', 'Contact')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-2">Liên hệ với chúng tôi</h1>
            <p class="text-muted mb-0">
                Gửi yêu cầu đặt phòng, báo giá hội nghị, hoặc bất kỳ thắc mắc nào bạn
                đang quan tâm.
            </p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <h2 class="h5 fw-bold mb-3">Thông tin liên hệ</h2>
                    <ul class="list-unstyled small text-muted mb-4">
                        <li class="mb-2">
                            <i class="bx bx-map me-2 text-primary"></i>
                            12 Võ Nguyên Giáp, Phường Phước Mỹ, Quận Sơn Trà, Đà Nẵng.
                        </li>
                        <li class="mb-2">
                            <i class="bx bx bx-phone me-2 text-primary"></i>
                            Điện thoại: (+84) 236 3888 999
                        </li>
                        <li class="mb-2">
                            <i class="bx bx bx-envelope me-2 text-primary"></i>
                            Email: booking@mcuonghotel.vn
                        </li>
                        <li class="mb-2">
                            <i class="bx bx bx-time me-2 text-primary"></i>
                            Thời gian hoạt động: 24/7
                        </li>
                    </ul>
                    <h3 class="h6 fw-bold mb-2">Kết nối mạng xã hội</h3>
                    <div class="d-flex gap-2 mb-4">
                        <a href="#" class="social-link"><i class="bx bx bxl-facebook"></i></a>
                        <a href="#" class="social-link"><i class="bx bx bxl-instagram"></i></a>
                        <a href="#" class="social-link"><i class="bx bx bxl-youtube"></i></a>
                    </div>
                    <div class="ratio ratio-16x9 rounded-4 overflow-hidden">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3834.315884632835!2d108.24978037571308!3d16.048832684630897!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x314219dfcfab0503%3A0xd9cfcc927ae3c4c7!2zVsWpIMSQ4bqhaSBN4bu5IMSQw6A!5e0!3m2!1svi!2s!4v1700000000000!5m2!1svi!2s"
                            style="border: 0" allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-3">Gửi yêu cầu tư vấn</h2>
                            <form id="contactForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small">Họ và tên</label>
                                        <input type="text" class="form-control" placeholder="Nguyễn Văn A" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Email</label>
                                        <input type="email" class="form-control" placeholder="email@domain.com" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Số điện thoại</label>
                                        <input type="tel" class="form-control" placeholder="098x xxx xxx" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Loại yêu cầu</label>
                                        <select class="form-select">
                                            <option>Đặt phòng lẻ</option>
                                            <option>Đặt phòng đoàn</option>
                                            <option>Đặt phòng hội nghị</option>
                                            <option>Khác</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small">Nội dung</label>
                                        <textarea rows="4" class="form-control"
                                            placeholder="Ví dụ: cần báo giá phòng họp cho 50 khách vào ngày..."
                                            required></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mt-3">
                                    Gửi yêu cầu
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection