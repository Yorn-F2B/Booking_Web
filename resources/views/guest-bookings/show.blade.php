@extends('guest-bookings.layout')
@section('title','Thông tin booking '.$booking->booking_code)
@section('heading','Thông tin booking')
@section('subheading','Phiên xác thực có hiệu lực trong thời gian ngắn. Không chia sẻ trang này cho người khác.')
@section('content')
@php
$statusLabels=['pending'=>'Chờ xác nhận','confirmed'=>'Đã xác nhận','checked_in'=>'Đang ở','inspection_requested'=>'Chờ kiểm tra phòng','checked_out'=>'Đã trả phòng','completed'=>'Hoàn tất','cancelled'=>'Đã hủy'];
@endphp
<div class="grid">
    <div class="info"><small>Mã booking</small><strong>{{ $booking->booking_code }}</strong></div>
    <div class="info"><small>Trạng thái</small><strong>{{ $statusLabels[$booking->status] ?? $booking->status }}</strong></div>
    <div class="info"><small>Khách hàng</small><strong>{{ $booking->booked_customer_name }}</strong></div>
    <div class="info"><small>Hạng phòng</small><strong>{{ $booking->roomCategory->name ?? '---' }}</strong></div>
    <div class="info"><small>Nhận phòng</small><strong>{{ optional($booking->check_in_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</strong></div>
    <div class="info"><small>Trả phòng</small><strong>{{ optional($booking->check_out_at)->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</strong></div>
    <div class="info"><small>Tổng dự kiến</small><strong>{{ number_format((float)$booking->estimated_total,0,',','.') }}đ</strong></div>
    <div class="info"><small>Đã thanh toán</small><strong>{{ number_format($paidAmount,0,',','.') }}đ</strong></div>
</div>

@if($booking->status === 'cancelled')
    <div class="alert alert-error" style="margin-top:18px;margin-bottom:0">Booking này đã được hủy. Tiền đã thanh toán không được hoàn lại hoặc bảo lưu.</div>
@elseif($canCancel)
    <div class="warning" style="margin-top:18px"><strong>Chính sách hủy:</strong> Hủy booking sẽ mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu. Phòng sẽ được mở bán lại ngay sau khi xác nhận hủy.</div>
    <form method="post" action="{{ route('guest-bookings.cancel',['token'=>$token]) }}" style="margin-top:18px" onsubmit="return confirm('Bạn chắc chắn muốn hủy booking? Thao tác này không thể hoàn tác.');">
        @csrf
        <div class="field"><label>Lý do hủy</label><textarea class="textarea" name="reason" placeholder="Ví dụ: thay đổi kế hoạch, nhập nhầm ngày..." required>{{ old('reason') }}</textarea></div>
        <label class="checkbox"><input type="checkbox" name="confirm_forfeit" value="1" required><span>Tôi xác nhận đã hiểu toàn bộ số tiền {{ number_format($paidAmount,0,',','.') }}đ đã thanh toán sẽ không được hoàn lại và booking không thể tự khôi phục sau khi hủy.</span></label>
        <button class="btn btn-danger" type="submit">Xác nhận hủy booking</button>
    </form>
@else
    <div class="warning" style="margin-top:18px">Booking hiện không thể tự hủy qua trang tra cứu. Vui lòng liên hệ lễ tân nếu cần hỗ trợ.</div>
@endif
@endsection
