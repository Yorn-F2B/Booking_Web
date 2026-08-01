@extends('guest-bookings.layout')
@section('title','Đã hủy booking')
@section('heading','Đã hủy booking thành công')
@section('subheading','Phòng đã được giải phóng và email xác nhận hủy đã được gửi tới khách hàng.')
@section('content')
<div class="alert alert-success">Booking <strong>{{ $bookingCode }}</strong> đã được hủy. Toàn bộ tiền đã thanh toán được giữ lại theo chính sách không hoàn tiền và không bảo lưu.</div>
<a class="btn btn-light" href="{{ route('guest-bookings.index') }}">Tra cứu booking khác</a>
@endsection
