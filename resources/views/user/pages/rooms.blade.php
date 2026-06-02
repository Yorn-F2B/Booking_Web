@extends('layouts.user')

@section('title', 'Rooms')

@section('content')
    <!-- Page header -->
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-2">Danh sach tat ca phòng tại MCuong Hotel</h1>
            <p class="text-muted mb-0">
                Lựa chọn đa dạng từ phòng tiêu chuẩn đến suite cao cấp, phù hợp cho
                cặp đôi, gia đình và khách công tác tại cùng một khách sạn.
            </p>
        </div>
    </section>

    <!-- Room list -->
    <main class="py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Deluxe Sea View -->
                <div class="col-12">
                    <article class="card room-card-horizontal border-0 shadow-sm">
                        <div class="row g-0 h-100">
                            <div class="col-md-4">
                                <div class="ratio ratio-4x3 h-100">
                                    <img src="https://images.pexels.com/photos/1579253/pexels-photo-1579253.jpeg"
                                        class="card-img-top h-100" alt="Deluxe Sea View" style="object-fit: cover;" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body h-100 d-flex flex-column">
                                    <span class="badge bg-primary-soft text-primary mb-2">Deluxe Sea View</span>
                                    <h2 class="h5">Phòng Deluxe view biển</h2>
                                    <p class="small text-muted mb-2">
                                        32m², giường King, ban công riêng nhìn trực diện biển Mỹ Khê.
                                    </p>
                                    <p class="small mb-2"><strong>Tối đa 2 người lớn, 1 trẻ em</strong></p>
                                    <ul class="amenity-list mb-3">
                                        <li class="amenity-pill">Buffet sáng</li>
                                        <li class="amenity-pill">Hồ bơi & phòng gym</li>
                                        <li class="amenity-pill">Miễn phí hủy 3 ngày</li>
                                    </ul>
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-bold text-primary fs-5">1.800.000đ</span>
                                            <span class="text-muted small">/đêm</span>
                                        </div>
                                        <a href="/room-deluxe-sea" class="btn btn-outline-primary btn-sm">Xem chi
                                            tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Premier City View -->
                <div class="col-12">
                    <article class="card room-card-horizontal border-0 shadow-sm">
                        <div class="row g-0 h-100">
                            <div class="col-md-4">
                                <div class="ratio ratio-4x3 h-100">
                                    <img src="https://images.pexels.com/photos/1571450/pexels-photo-1571450.jpeg"
                                        class="card-img-top h-100" alt="Premier City View" style="object-fit: cover;" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body h-100 d-flex flex-column">
                                    <span class="badge bg-warning-soft text-warning mb-2">Premier City View</span>
                                    <h2 class="h5">Phòng Premier view thành phố</h2>
                                    <p class="small text-muted mb-2">
                                        28m², giường Queen, cửa sổ lớn nhìn toàn cảnh thành phố.
                                    </p>
                                    <p class="small mb-2"><strong>Tối đa 2 người lớn</strong></p>
                                    <ul class="amenity-list mb-3">
                                        <li class="amenity-pill">Miễn phí đậu xe</li>
                                        <li class="amenity-pill">WiFi tốc độ cao</li>
                                        <li class="amenity-pill">Phù hợp công tác</li>
                                    </ul>
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-bold text-primary fs-5">1.400.000đ</span>
                                            <span class="text-muted small">/đêm</span>
                                        </div>
                                        <a href="/room-premier-city" class="btn btn-outline-primary btn-sm">Xem chi
                                            tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Family Suite -->
                <div class="col-12">
                    <article class="card room-card-horizontal border-0 shadow-sm">
                        <div class="row g-0 h-100">
                            <div class="col-md-4">
                                <div class="ratio ratio-4x3 h-100">
                                    <img src="https://images.pexels.com/photos/271639/pexels-photo-271639.jpeg"
                                        class="card-img-top h-100" alt="Family Suite" style="object-fit: cover;" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body h-100 d-flex flex-column">
                                    <span class="badge bg-success-soft text-success mb-2">Family Suite</span>
                                    <h2 class="h5">Suite gia đình 2 phòng ngủ</h2>
                                    <p class="small text-muted mb-2">
                                        60m², 2 phòng ngủ riêng, 1 phòng khách, ban công rộng.
                                    </p>
                                    <p class="small mb-2"><strong>Tối đa 4 người lớn, 2 trẻ em</strong></p>
                                    <ul class="amenity-list mb-3">
                                        <li class="amenity-pill">Bếp mini & bàn ăn</li>
                                        <li class="amenity-pill">Máy giặt</li>
                                        <li class="amenity-pill">Phù hợp nghỉ dài ngày</li>
                                    </ul>
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-bold text-primary fs-5">3.200.000đ</span>
                                            <span class="text-muted small">/đêm</span>
                                        </div>
                                        <a href="/room-family-suite" class="btn btn-outline-primary btn-sm">Xem chi
                                            tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Presidential Suite -->
                <div class="col-12">
                    <article class="card room-card-horizontal border-0 shadow-sm">
                        <div class="row g-0 h-100">
                            <div class="col-md-4">
                                <div class="ratio ratio-4x3 h-100">
                                    <img src="https://images.pexels.com/photos/276724/pexels-photo-276724.jpeg"
                                        class="card-img-top h-100" alt="Presidential Suite" style="object-fit: cover;" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body h-100 d-flex flex-column">
                                    <span class="badge bg-danger-soft text-danger mb-2">Presidential Suite</span>
                                    <h2 class="h5">Phòng Tổng thống</h2>
                                    <p class="small text-muted mb-2">
                                        120m², tầng cao nhất, view biển &amp; thành phố 270°.
                                    </p>
                                    <p class="small mb-2"><strong>2 phòng ngủ, phòng khách, phòng làm việc</strong></p>
                                    <ul class="amenity-list mb-3">
                                        <li class="amenity-pill">Phòng tắm jacuzzi</li>
                                        <li class="amenity-pill">Phòng xông hơi riêng</li>
                                        <li class="amenity-pill">Đưa đón sân bay limousine</li>
                                    </ul>
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-bold text-primary fs-5">8.500.000đ</span>
                                            <span class="text-muted small">/đêm</span>
                                        </div>
                                        <a href="/room-presidential" class="btn btn-outline-primary btn-sm">Xem chi
                                            tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <!-- Pagination (demo) -->
            <nav aria-label="Phân trang danh sách phòng" class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Trước</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Tiếp</a>
                    </li>
                </ul>
            </nav>
        </div>
    </main>
@endsection