@extends('guest-bookings.layout')
@section('content')
<div class="container py-5" style="max-width:760px">
    <div class="alert alert-success">
        <h4>Đã gửi thông tin đến muộn</h4>
        <p class="mb-0">Booking <strong>{{ $booking->booking_code }}</strong> đã ghi nhận lý do và giờ dự kiến đến. Khách sạn sẽ phản hồi sau khi quản lý xem xét.</p>
    </div>
</div>
@endsection
