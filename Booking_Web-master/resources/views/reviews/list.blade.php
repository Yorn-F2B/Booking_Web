<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Đánh Giá Dịch Vụ</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/boxicons.min.css') }}" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Arial, sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .stat-box { background: #fff; padding: 20px; border-radius: 12px; text-align: center; }
        .text-warning { color: #ffc107 !important; font-size: 1.2rem; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold mb-0">TỔNG HỢP ĐÁNH GIÁ</h2>
        <a href="{{ route('reviews.index') }}" class="btn btn-success fw-bold px-4">Gửi đánh giá mới</a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-box card">
                <h6 class="text-muted text-uppercase fw-semibold">Tổng lượt phản hồi</h6>
                <h2 class="text-dark fw-bold my-2">{{ $stats->total ?? 0 }}</h2>
                <span class="text-muted small">Từ khách hàng đã lưu trú</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box card">
                <h6 class="text-muted text-uppercase fw-semibold">Điểm phòng & Khách sạn</h6>
                <h2 class="text-warning fw-bold my-2">
                    {{ number_format($stats->avg_hotel ?? 0, 1) }} ★
                </h2>
                <span class="text-muted small">Mức độ hài lòng chung</span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box card">
                <h6 class="text-muted text-uppercase fw-semibold">Điểm phục vụ của nhân viên</h6>
                <h2 class="text-warning fw-bold my-2">
                    {{ number_format($stats->avg_staff ?? 0, 1) }} ★
                </h2>
                <span class="text-muted small">Thái độ và sự chuyên nghiệp</span>
            </div>
        </div>
    </div>

    <div class="card p-4">
        <h5 class="fw-bold mb-4 text-secondary">Phản hồi chi tiết gần đây</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn phòng</th>
                        <th>Loại phòng</th>
                        <th>Đánh giá khách sạn</th>
                        <th>Đánh giá nhân viên</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr>
                            <td class="fw-bold text-primary">{{ $review->booking_code }}</td>
                            <td><span class="badge bg-secondary">{{ $review->room_name }}</span></td>
                            <td>
                                <div class="text-warning">
                                    {{ str_repeat('★', $review->hotel_rating) }}{{ str_repeat('☆', 5 - $review->hotel_rating) }}
                                </div>
                                <small class="text-muted d-block italic">"{{ $review->hotel_comment ?? 'Không có bình luận' }}"</small>
                            </td>
                            <td>
                                <div class="text-warning">
                                    {{ str_repeat('★', $review->staff_rating) }}{{ str_repeat('☆', 5 - $review->staff_rating) }}
                                </div>
                                <small class="text-muted d-block italic">"{{ $review->staff_comment ?? 'Không có bình luận' }}"</small>
                            </td>
                            <td class="text-muted small">{{ $review->created_at ?? now() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Chưa có lượt đánh giá nào được ghi nhận.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>