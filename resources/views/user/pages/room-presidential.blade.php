@extends('layouts.user')

@section('title', 'Room Presidential')

@section('content')
    <section class="page-header">
      <div class="container">
        <h1 class="display-6 fw-bold mb-1">Phòng Tổng thống</h1>
        <p class="text-muted mb-0">Trải nghiệm đẳng cấp với không gian suite cao cấp nhất tại MCuong Hotel.</p>
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
                    <img src="https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg" alt="Presidential Suite Living Room" />
                  </div>
                  <div class="swiper-slide">
                    <img src="https://images.pexels.com/photos/1457845/pexels-photo-1457845.jpeg" alt="Presidential Suite Bedroom" />
                  </div>
                  <div class="swiper-slide">
                    <img src="https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg" alt="Presidential Suite Dining Area" />
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
                    <span class="badge bg-danger-soft text-danger mb-2">Presidential Suite</span>
                    <h2 class="h5 fw-bold mb-1">8.500.000đ <span class="text-muted small">/đêm</span></h2>
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
                        <input type="date" class="form-control" value="2026-05-17" />
                      </div>
                    </div>
                    <div class="row g-2 mb-3">
                      <div class="col-6">
                        <label class="form-label small">Người lớn</label>
                        <select class="form-select">
                          <option>2</option>
                          <option selected>4</option>
                          <option>6</option>
                          <option>8</option>
                        </select>
                      </div>
                      <div class="col-6">
                        <label class="form-label small">Trẻ em</label>
                        <select class="form-select">
                          <option selected>0</option>
                          <option>2</option>
                          <option>4</option>
                        </select>
                      </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">
                      <i class="bx bx-calendar-check me-1"></i>Đặt phòng ngay
                    </button>
                  </form>
                </div>
                
                <div class="border-top pt-3">
                  <h3 class="h6 fw-bold mb-2">Chính sách đặc biệt</h3>
                  <ul class="list-unstyled small text-muted mb-0">
                    <li class="mb-1"><i class="bx bx-check text-success me-1"></i> Butler service 24/7</li>
                    <li class="mb-1"><i class="bx bx-check text-success me-1"></i> Miễn phí hủy trước 7 ngày</li>
                    <li class="mb-1"><i class="bx bx-check text-success me-1"></i> Private check-in/check-out</li>
                    <li><i class="bx bx-check text-success me-1"></i> Bữa sáng tại phòng miễn phí</li>
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
                          <div class="small text-muted">Tối đa 4 người</div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="d-flex align-items-center gap-2 p-2 border rounded">
                        <i class="bx bx-child text-primary"></i>
                        <div>
                          <div class="small fw-bold">Trẻ em</div>
                          <div class="small text-muted">Tối đa 2 trẻ em</div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <h5 class="small fw-bold mb-2">Tiện nghi đặc biệt</h5>
                  <ul class="amenity-list mb-0">
                    <li class="amenity-pill"><i class="bx bx-wifi me-1"></i> WiFi tốc độ cao</li>
                    <li class="amenity-pill"><i class="bx bx-tv me-1"></i> 3 Smart TV 65" 4K</li>
                    <li class="amenity-pill"><i class="bx bx-bed me-1"></i> Phòng ngủ Master</li>
                    <li class="amenity-pill"><i class="bx bx-wind me-1"></i> Hệ thống HVAC</li>
                    <li class="amenity-pill"><i class="bx bx-kitchen me-1"></i> Kitchenette đầy đủ</li>
                    <li class="amenity-pill"><i class="bx bx-sofa me-1"></i> Phòng khách rộng</li>
                    <li class="amenity-pill"><i class="bx bx-dining me-1"></i> Khu vực dining</li>
                    <li class="amenity-pill"><i class="bx bx-bath me-1"></i> Phòng tắm Jacuzzi</li>
                    <li class="amenity-pill"><i class="bx bx-shower me-1"></i> Steam shower</li>
                    <li class="amenity-pill"><i class="bx bx-wine me-1"></i> Private bar</li>
                  </ul>
                </div>
                
                <h4 class="h6 fw-bold mb-3">Thông tin chi tiết</h4>
                <p class="mb-2">Phòng Tổng thống với diện tích 120m² là suite cao cấp nhất tại MCuong Hotel, được thiết kế riêng cho những vị khách đặc biệt yêu cầu sự riêng tư, sang trọng và tiện nghi tối đa. Suite bao gồm phòng ngủ master rộng rãi, phòng khách sang trọng, khu vực dining riêng và kitchenette đầy đủ.</p>
                <p class="mb-2">Phòng ngủ master có giường King size cao cấp với hệ thống đệm chỉnh điện, tủ quần áo walk-in và phòng tắm riêng với bồn tắm Jacuzzi sang trọng. Phòng khách được bố trí sofa da cao cấp, bàn trà máy lạnh và hệ thống giải trí đa phương tiện với 3 TV 65" 4K.</p>
                <p class="mb-2">Khu vực dining riêng biệt có bàn ăn 6 chỗ, phù hợp cho các bữa tiệc nhỏ hoặc họp mặt gia đình. Kitchenette được trang bị đầy đủ từ tủ lạnh side-by-side, lò vi sóng, máy pha cà phê espresso đến bộ dụng cụ nấu ăn cơ bản. Private bar với rượu vang và đồ uống cao cấp.</p>
                <p class="mb-0">Phòng tắm master là điểm nhấn với bồn tắm Jacuzzi trung tâm, steam shower, bồn rửa mặt đôi và hệ thống gương thông minh. Toàn bộ suite được trang bị hệ thống âm thanh vòm, đèn điều chỉnh cảm ứng và rèm cửa tự động. Vị trí suite ở tầng cao nhất (tầng 25) với view toàn cảnh 360° thành phố và biển. Dịch vụ butler 24/7, private check-in/check-out và bữa sáng tại phòng miễn phí là những đặc quyền dành riêng cho khách ở Phòng Tổng thống.</p>
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
                      <div class="fw-bold text-primary">120m²</div>
                      <div class="small text-muted">Diện tích</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="text-center p-2 border rounded">
                      <div class="fw-bold text-primary">4+2</div>
                      <div class="small text-muted">Số người</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="text-center p-2 border rounded">
                      <div class="fw-bold text-primary">1 Master</div>
                      <div class="small text-muted">Phòng ngủ</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="text-center p-2 border rounded">
                      <div class="fw-bold text-primary">Tầng 25</div>
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