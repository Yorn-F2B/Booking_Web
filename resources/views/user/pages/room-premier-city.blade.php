@extends('layouts.user')

@section('title', 'Room Premier City')

@section('content')
    <section class="page-header">
      <div class="container">
        <h1 class="display-6 fw-bold mb-1">Phòng Premier hướng phố</h1>
        <p class="text-muted mb-0">View toàn cảnh thành phố Đà Nẵng từ cửa sổ lớn.</p>
      </div>
    </section>

    <main class="py-5">
      <div class="container">
        <!-- Room Gallery -->
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="room-gallery">
              <div class="swiper roomGallerySwiper">
                <div class="swiper-wrapper">
                  <div class="swiper-slide">
                    <img src="https://images.pexels.com/photos/1571450/pexels-photo-1571450.jpeg" alt="Premier City View Room" />
                  </div>
                  <div class="swiper-slide">
                    <img src="https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg" alt="Premier City View Bathroom" />
                  </div>
                  <div class="swiper-slide">
                    <img src="https://images.pexels.com/photos/271619/pexels-photo-271619.jpeg" alt="Premier City View Workspace" />
                  </div>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
              </div>
            </div>
          </div>
          
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <div>
                    <span class="badge bg-warning-soft text-warning mb-2">Premier City View</span>
                    <h2 class="h5 fw-bold mb-1">1.400.000đ <span class="text-muted small">/đêm</span></h2>
                    <p class="small text-muted mb-0">Giá đã bao gồm thuế VAT 10%</p>
                  </div>
                </div>
                
                <div class="mb-4">
                  <h3 class="h6 fw-bold mb-2">Đặt phòng nhanh</h3>
                  <form>
                    <div class="row g-2 mb-3">
                      <div class="col-6">
                        <label class="form-label small">Nhận phòng</label>
                        <input type="date" class="form-control" value="2026-05-15" />
                      </div>
                      <div class="col-6">
                        <label class="form-label small">Trả phòng</label>
                        <input type="date" class="form-control" value="2026-05-16" />
                      </div>
                    </div>
                    <div class="row g-2 mb-3">
                      <div class="col-6">
                        <label class="form-label small">Người lớn</label>
                        <select class="form-select">
                          <option>1</option>
                          <option selected>2</option>
                          <option>3</option>
                        </select>
                      </div>
                      <div class="col-6">
                        <label class="form-label small">Trẻ em</label>
                        <select class="form-select">
                          <option selected>0</option>
                          <option>1</option>
                          <option>2</option>
                        </select>
                      </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">
                      <i class="bx bx-calendar-check me-1"></i>Đặt phòng ngay
                    </button>
                  </form>
                </div>
                
                <div class="border-top pt-3">
                  <h3 class="h6 fw-bold mb-2">Chính sách</h3>
                  <ul class="list-unstyled small text-muted mb-0">
                    <li class="mb-1"><i class="bx bx-check text-success me-1"></i> Miễn phí hủy trước 3 ngày</li>
                    <li class="mb-1"><i class="bx bx-check text-success me-1"></i> Không cần thanh toán trước</li>
                    <li class="mb-1"><i class="bx bx-check text-success me-1"></i> Buffet sáng miễn phí</li>
                    <li><i class="bx bx-check text-success me-1"></i> WiFi tốc độ cao</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Room Details -->
        <div class="row g-4 mt-4">
          <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
              <div class="card-body">
                <h3 class="h5 fw-bold mb-3">Thông tin phòng</h3>
                <div class="mb-4">
                  <h4 class="h6 fw-bold mb-3">Sức chứa & Tiện nghi</h4>
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <div class="d-flex align-items-center gap-2 p-2 border rounded">
                        <i class="bx bx-user text-primary"></i>
                        <div>
                          <div class="small fw-bold">Người lớn</div>
                          <div class="small text-muted">Tối đa 2 người</div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="d-flex align-items-center gap-2 p-2 border rounded">
                        <i class="bx bx-child text-primary"></i>
                        <div>
                          <div class="small fw-bold">Trẻ em</div>
                          <div class="small text-muted">Tối đa 1 trẻ em</div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <h5 class="small fw-bold mb-2">Tiện nghi phòng</h5>
                  <ul class="amenity-list mb-0">
                    <li class="amenity-pill"><i class="bx bx-wifi me-1"></i> WiFi tốc độ cao</li>
                    <li class="amenity-pill"><i class="bx bx-tv me-1"></i> Smart TV 55"</li>
                    <li class="amenity-pill"><i class="bx bx-bed me-1"></i> Giường Queen size</li>
                    <li class="amenity-pill"><i class="bx bx-wind me-1"></i> Điều hòa 2 chiều</li>
                    <li class="amenity-pill"><i class="bx bx-briefcase me-1"></i> Bàn làm việc rộng</li>
                    <li class="amenity-pill"><i class="bx bx-city me-1"></i> View thành phố</li>
                    <li class="amenity-pill"><i class="bx bx-coffee me-1"></i> Minibar cơ bản</li>
                    <li class="amenity-pill"><i class="bx bx-shower me-1"></i> Vòi sen hiện đại</li>
                    <li class="amenity-pill"><i class="bx bx-hair me-1"></i> Máy sấy tóc</li>
                    <li class="amenity-pill"><i class="bx bx-parking me-1"></i> Miễn phí đậu xe</li>
                  </ul>
                </div>
                
                <h4 class="h6 fw-bold mb-3">Thông tin chi tiết</h4>
                <p class="mb-2">Phòng Premier hướng phố với diện tích 28m², được thiết kế tối ưu cho khách công tác và du lịch ngắn ngày. Phòng có cửa sổ lớn từ trần đến sàn, mang đến view toàn cảnh thành phố Đà Nẵng nhộn nhịp, đặc biệt đẹp vào buổi tối khi thành phố lên đèn.</p>
                <p class="mb-2">Nội thất phòng theo phong cách hiện đại, tối giản với tông màu trung tính, tạo cảm giác rộng rãi và thoáng đãng. Giường Queen size với đệm êm ái, kết hợp hệ thống đèn ngủ thông minh giúp thư giãn tối đa sau một ngày làm việc hoặc tham quan.</p>
                <p class="mb-2">Khu vực bàn làm việc được bố trí ngay cạnh cửa sổ, với không gian đủ rộng cho laptop và tài liệu, cùng nhiều ổ cắm điện đa năng và đèn bàn điều chỉnh độ sáng. Đây là điểm cộng lớn cho khách công tác cần làm việc tại phòng.</p>
                <p class="mb-0">Phòng tắm được trang bị vòi sen hiện đại với hệ thống nước nóng lạnh ổn định, cùng đầy đủ đồ vệ sinh cá nhân thiết yếu. Vị trí phòng ở các tầng trung (tầng 6-10) cho view thành phố đẹp mà vẫn đảm bảo sự yên tĩnh tương đối. Phòng có hệ thống cách âm tốt, hạn chế tiếng ồn từ đường phố.</p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body">
                <h3 class="h6 fw-bold mb-3">Thông số phòng</h3>
                <div class="row g-2">
                  <div class="col-6">
                    <div class="text-center p-2 border rounded">
                      <div class="fw-bold text-primary">28m²</div>
                      <div class="small text-muted">Diện tích</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="text-center p-2 border rounded">
                      <div class="fw-bold text-primary">2+1</div>
                      <div class="small text-muted">Số người</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="text-center p-2 border rounded">
                      <div class="fw-bold text-primary">1</div>
                      <div class="small text-muted">Giường</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="text-center p-2 border rounded">
                      <div class="fw-bold text-primary">Tầng 6-10</div>
                      <div class="small text-muted">Vị trí</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="card border-0 shadow-sm">
              <div class="card-body">
                <h3 class="h6 fw-bold mb-3">Các phòng tương tự</h3>
                <div class="d-flex align-items-start gap-2 mb-3">
                  <img src="https://images.pexels.com/photos/1579253/pexels-photo-1579253.jpeg" alt="Deluxe Sea" class="rounded" width="60" height="60" style="object-fit: cover;" />
                  <div>
                    <h4 class="small fw-bold mb-0">Phòng Deluxe Sea</h4>
                    <p class="small text-muted mb-1">1.800.000đ/đêm</p>
                    <a href="room-deluxe-sea.html" class="small">Xem chi tiết →</a>
                  </div>
                </div>
                <div class="d-flex align-items-start gap-2">
                  <img src="https://images.pexels.com/photos/271639/pexels-photo-271639.jpeg" alt="Family Suite" class="rounded" width="60" height="60" style="object-fit: cover;" />
                  <div>
                    <h4 class="small fw-bold mb-0">Suite Gia đình</h4>
                    <p class="small text-muted mb-1">3.200.000đ/đêm</p>
                    <a href="room-family-suite.html" class="small">Xem chi tiết →</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
@endsection