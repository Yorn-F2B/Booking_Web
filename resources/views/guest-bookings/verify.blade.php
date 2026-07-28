@extends('guest-bookings.layout')
@section('title','Xác thực OTP')
@section('heading','Nhập mã OTP')
@section('subheading','Mã gồm 6 chữ số đã được gửi tới email của booking.')
@section('content')
<form method="post" action="{{ route('guest-bookings.verify') }}">
    @csrf
    <input type="hidden" name="booking_code" value="{{ $bookingCode }}">
    <input type="hidden" name="email" value="{{ $email }}">
    <div class="field"><label>Mã booking</label><input class="input" value="{{ $bookingCode }}" disabled></div>
    <div class="field"><label>Mã OTP</label><input class="input" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" autocomplete="one-time-code" autofocus required></div>
    <div class="actions"><button class="btn btn-primary" type="submit">Xác thực và xem booking</button><a class="btn btn-light" href="{{ route('guest-bookings.index') }}">Tra cứu lại</a></div>
</form>
@endsection
