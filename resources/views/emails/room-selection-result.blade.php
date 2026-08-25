@php
    $roomNumbers = $booking->bookingRooms->pluck('room.room_number')->filter()->values()->implode(', ');
    $customerHasAccount = (bool) ($booking->customer?->user_id);
    $responseUrl = $customerHasAccount
        ? route('bookings.show', $booking)
        : route('guest-bookings.index', [
            'booking_code' => $booking->booking_code,
            'email' => $booking->booked_customer_email,
        ]);
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $fulfilled ? 'Đã xác nhận phòng theo yêu cầu' : 'Cần xác nhận phòng dự phòng' }}</title>
</head>
<body style="font-family:Arial,sans-serif;color:#172033;line-height:1.6;background:#f5f7fb;padding:24px;">
    <div style="max-width:680px;margin:0 auto;background:#fff;border-radius:14px;padding:28px;border:1px solid #e6eaf0;">
        <h2 style="margin-top:0;">MCuong Hotel</h2>
        <p>Xin chào <strong>{{ $booking->booked_customer_name }}</strong>,</p>

        @if($fulfilled)
            <p>Khách sạn đã <strong>đáp ứng yêu cầu chọn phòng</strong> của booking <strong>{{ $booking->booking_code }}</strong>.</p>
            <p>
                Phòng được xác nhận:
                <strong>{{ $roomNumbers ?: '---' }}</strong>.
            </p>
            <p>Phí đảm bảo yêu cầu phòng: <strong>{{ number_format((float) $booking->room_selection_fee, 0, ',', '.') }}đ</strong>.</p>
            <p>Tổng tiền booking sau cập nhật: <strong>{{ number_format((float) $booking->estimated_total, 0, ',', '.') }}đ</strong>.</p>
        @else
            <p>Khách sạn <strong>không thể đáp ứng đầy đủ yêu cầu chọn phòng</strong> của booking <strong>{{ $booking->booking_code }}</strong>.</p>
            <p>Để tránh bán vượt phòng, khách sạn vẫn đang giữ tạm phòng dự phòng sau cho quý khách:</p>
            <p style="font-size:18px;"><strong>Phòng dự phòng: {{ $roomNumbers ?: '---' }}</strong></p>
            <p>Phòng này <strong>chưa được xem là quý khách đã đồng ý nhận</strong> và không phát sinh phí đảm bảo yêu cầu phòng.</p>
            <p>Vui lòng vào trang booking để chọn một trong hai phương án:</p>
            <ul>
                <li><strong>Đồng ý:</strong> tiếp tục booking bằng phòng dự phòng đang giữ.</li>
                <li><strong>Từ chối:</strong> booking được hủy vì khách sạn không đáp ứng yêu cầu; toàn bộ số tiền đã thanh toán phải được hoàn lại cho quý khách.</li>
            </ul>
            <p style="margin:20px 0;">
                <a href="{{ $responseUrl }}" style="display:inline-block;background:#0f766e;color:#fff;text-decoration:none;font-weight:700;padding:11px 16px;border-radius:9px;">Xem và xác nhận phương án</a>
            </p>
            @if(!$customerHasAccount)
                <p style="font-size:13px;color:#667085;">Booking được tạo không gắn tài khoản khách hàng. Liên kết trên mở trang tra cứu; vui lòng xác thực OTP bằng email đặt phòng để phản hồi.</p>
            @endif
        @endif

        @if($handlingNote !== '')
            <p><strong>Ghi chú từ lễ tân:</strong> {{ $handlingNote }}</p>
        @endif

        <p><strong>Yêu cầu quý khách đã ghi:</strong> {{ $booking->room_selection_request ?: '---' }}</p>
        <p style="margin-bottom:0;color:#667085;">Nếu cần hỗ trợ thêm, vui lòng liên hệ lễ tân MCuong Hotel.</p>
    </div>
</body>
</html>
