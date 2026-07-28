@extends('guest-bookings.layout')
@section('title','Tra cứu và yêu cầu hủy booking')
@section('heading','Tra cứu và yêu cầu hủy booking')
@section('subheading','Tra cứu booking')
@section('content')
<form method="post" action="{{ route('guest-bookings.send-otp') }}">
    @csrf
    <div class="field"><label>Mã booking</label><input class="input" name="booking_code" value="{{ $bookingCode }}" placeholder="Ví dụ: BK202607200001" autocomplete="off" required></div>
    <div class="field"><label>Email đặt phòng</label><input class="input" type="email" name="email" value="{{ $email }}" placeholder="email@example.com" required></div>
    <button class="btn btn-primary" type="submit">Gửi mã OTP</button>
</form>
@endsection
