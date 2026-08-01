<footer class="site-footer pt-5 pb-3">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 col-md-6">
        <div class="footer-brand mb-3">
          <span class="logo-mark me-2"
            style="background:linear-gradient(135deg,#d4af37,#b8860b);color:#0a1931">MC</span>
          MC<span>uong</span> Hotel
        </div>
        <p class="small mb-2" style="color:rgba(255,255,255,.55)">
          12 Võ Nguyên Giáp, Phường Phước Mỹ, Quận Sơn Trà, Đà Nẵng.
        </p>
        <p class="small mb-1" style="color:rgba(255,255,255,.55)">
          <i class="bx bx-phone me-1 text-gold"></i>
          <a href="tel:+842363888999">(+84) 236 3888 999</a>
        </p>
        <p class="small mb-3" style="color:rgba(255,255,255,.55)">
          <i class="bx bx-envelope me-1 text-gold"></i>
          <a href="mailto:booking@mcuonghotel.vn">booking@mcuonghotel.vn</a>
        </p>
        <div class="d-flex gap-2">
          <a href="#" class="social-link"><i class="bx bxl-facebook"></i></a>
          <a href="#" class="social-link"><i class="bx bxl-instagram"></i></a>
          <a href="#" class="social-link"><i class="bx bxl-youtube"></i></a>
          <a href="#" class="social-link"><i class="bx bxl-tiktok"></i></a>
        </div>
      </div>
      <div class="col-lg-2 col-md-3 col-6">
        <p class="footer-heading">Khám phá</p>
        <ul class="list-unstyled small mb-0" style="line-height:2">
          <li><a href="{{ url('/') }}">Trang chủ</a></li>
          <li><a href="{{ url('rooms') }}">Hạng phòng</a></li>
          <li><a href="{{ url('about') }}">Giới thiệu</a></li>
          <li><a href="{{ url('contact') }}">Liên hệ</a></li>
        </ul>
      </div>
      <div class="col-lg-2 col-md-3 col-6">
        <p class="footer-heading">Tài khoản</p>
        <ul class="list-unstyled small mb-0" style="line-height:2">
          <li><a href="{{ route('login') }}">Đăng nhập</a></li>
          <li><a href="{{ route('register') }}">Đăng ký</a></li>
          <li><a href="{{ url('user-settings') }}">Cài đặt</a></li>
          <li><a href="{{ url('booking-history') }}">Lịch sử đặt phòng</a></li>
          <li><a href="{{ route('guest-bookings.index') }}">Tra cứu booking vãng lai</a></li>
        </ul>
      </div>
      <div class="col-lg-4 col-md-6">
        <p class="footer-heading">Thời gian &amp; chính sách</p>
        <ul class="list-unstyled small mb-0" style="line-height:2;color:rgba(255,255,255,.55)">
          <li><i class="bx bx-time me-1 text-gold"></i> Thời gian nhận phòng: 14:00 - 16:00</li>
          <li><i class="bx bx-time me-1 text-gold"></i> Thời gian trả phòng: 10:00 - 12:00</li>
          <li><i class="bx bx-headphone me-1 text-gold"></i> Lễ tân &amp; hỗ trợ 24/7</li>
          <li><i class="bx bx-refresh me-1 text-gold"></i> Hủy phòng không hoàn lại tiền đã thanh toán</li>
          <li><i class="bx bx-car me-1 text-gold"></i> Đưa đón theo yêu cầu</li>
        </ul>
      </div>
    </div>
    <hr class="my-4" />
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <p class="small mb-0" style="color:rgba(255,255,255,.4)">© 2026 MCuong Hotel. All rights reserved.</p>
      <p class="small mb-0" style="color:rgba(255,255,255,.4)">Khách sạn 4 sao — Đà Nẵng, Việt Nam</p>
    </div>
  </div>
</footer>