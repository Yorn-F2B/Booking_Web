<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh Giá Dịch Vụ Đặt Phòng</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/boxicons.min.css') }}" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2rem; color: #ddd; cursor: pointer; transition: color 0.2s; }
        .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #ffc107; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4 mb-4 text-center">
                <h3 class="text-primary fw-bold">GỬI ĐÁNH GIÁ DỊCH VỤ</h3>
                <p class="text-muted">Vui lòng nhập mã đặt phòng để tiến hành đánh giá</p>
                <form action="{{ route('reviews.index') }}" method="GET" class="d-flex gap-2 justify-content-center">
                    <input type="text" name="code" class="form-control w-50" placeholder="Ví dụ: BK2026..." value="{{ $booking_code ?? '' }}" required>
                    <button type="submit" class="btn btn-primary px-4">Tìm kiếm đơn</button>
                </form>
            </div>

            @if(!empty($error))
                <div class="alert alert-danger text-center">{{ $error }}</div>
            @endif

            @if($already_reviewed)
                <div class="alert alert-warning text-center">Đơn đặt phòng này đã được gửi đánh giá trước đó rồi!</div>
            @endif

            @if($booking && !$already_reviewed)
                <div class="card p-4">
                    <h5 class="fw-bold mb-3 text-secondary">Thông tin phòng: <span class="text-dark">{{ $booking->room_category_name ?? 'Tiêu chuẩn' }}</span></h5>
                    <p class="mb-4 text-muted">Mã đơn: {{ $booking->booking_code }}</p>
                    
                    <form action="{{ route('reviews.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="booking_id" value="{{ $booking->id }}">

                        <div class="mb-4">
                            <label class="form-label fw-semibold">1. Đánh giá chất lượng khách sạn / phòng ở:</label>
                            <div class="star-rating mb-2">
                                <input type="radio" id="hotel-5" name="hotel_rating" value="5" required/><label for="hotel-5">★</label>
                                <input type="radio" id="hotel-4" name="hotel_rating" value="4"/><label for="hotel-4">★</label>
                                <input type="radio" id="hotel-3" name="hotel_rating" value="3"/><label for="hotel-3">★</label>
                                <input type="radio" id="hotel-2" name="hotel_rating" value="2"/><label for="hotel-2">★</label>
                                <input type="radio" id="hotel-1" name="hotel_rating" value="1"/><label for="hotel-1">★</label>
                            </div>
                            <textarea name="hotel_comment" class="form-control" rows="2" placeholder="Chia sẻ cảm nhận về phòng ở..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">2. Đánh giá thái độ phục vụ của nhân viên:</label>
                            <div class="star-rating mb-2">
                                <input type="radio" id="staff-5" name="staff_rating" value="5" required/><label for="staff-5">★</label>
                                <input type="radio" id="staff-4" name="staff_rating" value="4"/><label for="staff-4">★</label>
                                <input type="radio" id="staff-3" name="staff_rating" value="3"/><label for="staff-3">★</label>
                                <input type="radio" id="staff-2" name="staff_rating" value="2"/><label for="staff-2">★</label>
                                <input type="radio" id="staff-1" name="staff_rating" value="1"/><label for="staff-1">★</label>
                            </div>
                            <textarea name="staff_comment" class="form-control" rows="2" placeholder="Chia sẻ cảm nhận về nhân viên..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold">GỬI ĐÁNH GIÁ NGAY</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>