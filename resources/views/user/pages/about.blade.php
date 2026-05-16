@extends('layouts.user')

@section('title', 'About')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-2">Về MCuong Hotel</h1>
            <p class="text-muted mb-0">
                Khách sạn hiện đại tại trung tâm thành phố, phục vụ nhu cầu lưu trú
                công tác và nghỉ dưỡng cho khách trong nước lẫn quốc tế.
            </p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <div class="row g-4 align-items-center mb-5">
                <div class="col-lg-6">
                    <h2 class="h4 fw-bold mb-3">Câu chuyện thương hiệu</h2>
                    <p class="text-muted">
                        MCuong Hotel được khai trương vào năm 2018, là một khách sạn duy
                        nhất (không chi nhánh), đặt tại khu vực thuận tiện di chuyển và
                        tiếp cận nhanh các điểm tham quan, trung tâm thương mại, khu hành
                        chính.
                    </p>
                    <p class="text-muted">
                        Với hơn 150 phòng &amp; suite, nhà hàng, hồ bơi, phòng gym và khu
                        hội nghị, MCuong Hotel tập trung vào trải nghiệm đặt phòng minh
                        bạch, nhanh, rõ giá như các nền tảng đặt phòng hiện đại.
                    </p>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg"
                        class="img-fluid rounded-4 mb-3" alt="Khách sạn MCuong" />
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="p-4 border rounded-4 h-100">
                        <div class="text-primary fs-1 mb-2">
                            <i class="bx bx-medal"></i>
                        </div>
                        <h3 class="h6 fw-bold mb-2">Tiêu chuẩn 5 sao</h3>
                        <p class="small text-muted mb-0">
                            Dịch vụ đạt chuẩn quốc tế, đội ngũ nhân sự giàu kinh nghiệm,
                            luôn lắng nghe và nâng cao chất lượng phục vụ.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 border rounded-4 h-100">
                        <div class="text-primary fs-1 mb-2">
                            <i class="bx bx-map"></i>
                        </div>
                        <h3 class="h6 fw-bold mb-2">Vị trí thuận tiện</h3>
                        <p class="small text-muted mb-0">
                            Chỉ 15 phút tới sân bay, 10 phút tới trung tâm thành phố, thuận
                            tiện di chuyển tới Hội An, Bà Nà Hills, bán đảo Sơn Trà.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 border rounded-4 h-100">
                        <div class="text-primary fs-1 mb-2">
                            <i class="bx bx-heart"></i>
                        </div>
                        <h3 class="h6 fw-bold mb-2">Tận tâm &amp; chu đáo</h3>
                        <p class="small text-muted mb-0">
                            Mọi chi tiết nhỏ đều được chăm chút để mang lại cảm giác thoải
                            mái và an tâm tuyệt đối cho khách hàng.
                        </p>
                    </div>
                </div>
            </div>

            <section class="mb-5">
                <h2 class="h4 fw-bold mb-3">Một vài con số ấn tượng</h2>
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">150+</div>
                            <div class="stat-label">Phòng &amp; suite</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">2.300+</div>
                            <div class="stat-label">Đánh giá tích cực</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">4.8/5</div>
                            <div class="stat-label">Điểm trung bình</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Hỗ trợ khách hàng</div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
@endsection