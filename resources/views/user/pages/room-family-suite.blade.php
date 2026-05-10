@extends('layouts.user')

@section('title', 'Room Family Suite')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">Suite Gia đình</h1>
            <p class="text-muted mb-0">Không gian rộng rãi với 2 phòng ngủ riêng biệt, phù hợp cho gia đình.</p>
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
                                    <img src="https://images.pexels.com/photos/271639/pexels-photo-271639.jpeg"
                                        alt="Family Suite Living Room" />
                                </div>
                                <div class="swiper-slide">
                                    <img src="https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg"
                                        alt="Family Suite Bedroom" />
                                </div>
                                <div class="swiper-slide">
                                    <img src="https://images.pexels.com/photos/1457842/pexels-photo-1457842.jpeg"
                                        alt="Family Suite Kitchen" />
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
                                    <span class="badge bg-success-soft text-success mb-2">Family Suite</span>
                                    <h2 class="h5 fw-bold mb-1">3.200.000đ <span class="text-muted small">/đêm</span></h2>
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
                                            <input type="date" class="form-control" value="2026-05-18" />
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small">Người lớn</label>
                                            <select class="form-select">
                                                <option>2</option>
                                                <option selected>4</option>
                                                <option>5</option>
                                                <option>6</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Trẻ em</label>
                                            <select class="form-select">
                                                <option selected>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
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
                                    <li class="mb-1"><i class="bx bx-check text-success me-1"></i> Miễn phí hủy trước 3 ngày
                                    </li>
                                    <li class="mb-1"><i class="bx bx-check text-success me-1"></i> Không cần thanh toán
                                        trước</li>
                                    <li class="mb-1"><i class="bx bx-check text-success me-1"></i> Buffet sáng cho 4 người
                                    </li>
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

                                <h5 class="small fw-bold mb-2">Tiện nghi phòng</h5>
                                <ul class="amenity-list mb-0">
                                    <li class="amenity-pill"><i class="bx bx-wifi me-1"></i> WiFi tốc độ cao</li>
                                    <li class="amenity-pill"><i class="bx bx-tv me-1"></i> 2 Smart TV 55"</li>
                                    <li class="amenity-pill"><i class="bx bx-bed me-1"></i> 2 phòng ngủ riêng</li>
                                    <li class="amenity-pill"><i class="bx bx-wind me-1"></i> Điều hòa 2 chiều</li>
                                    <li class="amenity-pill"><i class="bx bx-kitchen me-1"></i> Pantry mini</li>
                                    <li class="amenity-pill"><i class="bx bx-sofa me-1"></i> Phòng khách riêng</li>
                                    <li class="amenity-pill"><i class="bx bx-bath me-1"></i> 2 phòng tắm riêng</li>
                                    <li class="amenity-pill"><i class="bx bx-shower me-1"></i> Vòi sen riêng</li>
                                    <li class="amenity-pill"><i class="bx bx-washing-machine me-1"></i> Máy giặt mini</li>
                                    <li class="amenity-pill"><i class="bx bx-fridge me-1"></i> Tủ lạnh lớn</li>
                                </ul>
                            </div>

                            <h4 class="h6 fw-bold mb-3">Thông tin chi tiết</h4>
                            <p class="mb-2">Suite Gia đình với diện tích rộng rãi 60m², được thiết kế đặc biệt cho các gia
                                đình có nhu cầu không gian riêng tư và tiện nghi đầy đủ. Suite bao gồm 2 phòng ngủ riêng
                                biệt, mỗi phòng có giường Queen size, phòng khách rộng rãi và pantry mini đầy đủ tiện nghi.
                            </p>
                            <p class="mb-2">Phòng ngủ chính có view thành phố, phòng ngủ phụ view sân vườn. Cả hai phòng đều
                                có tủ quần áo rộng rãi và bàn trang điểm. Phòng khách được bố trí sofa bành êm ái, bàn trà
                                và Smart TV 55" với đầy đủ kênh giải trí cho cả gia đình.</p>
                            <p class="mb-2">Pantry mini trang bị tủ lạnh lớn, lò vi sóng, ấm đun nước điện và bộ dụng cụ pha
                                chế cơ bản, thuận tiện cho việc chuẩn bị đồ ăn nhẹ cho trẻ em. Suite có 2 phòng tắm riêng
                                biệt, mỗi phòng đều có vòi sen hiện đại và đầy đủ đồ vệ sinh cá nhân.</p>
                            <p class="mb-0">Vị trí suite ở tầng thấp (tầng 3-5) thuận tiện cho gia đình có trẻ nhỏ và người
                                lớn tuổi. Hệ thống cách âm đặc biệt đảm bảo không gian yên tĩnh, phù hợp cho giấc ngủ của
                                trẻ em. Suite còn được trang bị máy giặt mini tiện lợi cho các chuyến đi dài ngày của gia
                                đình.</p>
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
                                        <div class="fw-bold text-primary">60m²</div>
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
                                        <div class="fw-bold text-primary">2</div>
                                        <div class="small text-muted">Phòng ngủ</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-2 border rounded">
                                        <div class="fw-bold text-primary">Tầng 3-5</div>
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
                                <img src="https://images.pexels.com/photos/1579253/pexels-photo-1579253.jpeg"
                                    alt="Deluxe Sea" class="rounded" width="60" height="60" style="object-fit: cover;" />
                                <div>
                                    <h4 class="small fw-bold mb-0">Phòng Deluxe Sea</h4>
                                    <p class="small text-muted mb-1">1.800.000đ/đêm</p>
                                    <a href="room-deluxe-sea.html" class="small">Xem chi tiết →</a>
                                </div>
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <img src="https://images.pexels.com/photos/1571450/pexels-photo-1571450.jpeg"
                                    alt="Premier City" class="rounded" width="60" height="60" style="object-fit: cover;" />
                                <div>
                                    <h4 class="small fw-bold mb-0">Phòng Premier City</h4>
                                    <p class="small text-muted mb-1">1.400.000đ/đêm</p>
                                    <a href="room-premier-city.html" class="small">Xem chi tiết →</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection