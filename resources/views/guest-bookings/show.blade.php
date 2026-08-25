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

@if (($booking->room_selection_mode ?? 'automatic') === 'manual')
    @php($selectionStatus = $booking->room_selection_status ?? 'pending')
    @if ($selectionStatus === 'pending')
        <div class="warning" style="margin-top:18px">
            <strong>Yêu cầu chọn phòng:</strong> {{ $booking->room_selection_request ?: '---' }}<br>
            Lễ tân đang xử lý. Khách sạn vẫn giữ đủ số lượng phòng để tránh bán vượt nhưng chưa công bố số phòng dự phòng. Chưa phát sinh phí đảm bảo yêu cầu phòng.
        </div>
    @elseif ($selectionStatus === 'fulfilled')
        <div class="alert" style="margin-top:18px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:14px;border-radius:10px">
            <strong>Yêu cầu phòng đã được đáp ứng.</strong> Phí đảm bảo yêu cầu phòng: {{ number_format((float) ($booking->room_selection_fee ?? 0),0,',','.') }}đ.<br>
            <strong>Phòng xác nhận:</strong> {{ $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ') ?: '---' }}.
        </div>
    @elseif ($selectionStatus === 'awaiting_guest')
        <div class="warning" style="margin-top:18px">
            <strong>Khách sạn không thể đáp ứng đầy đủ yêu cầu phòng.</strong><br>
            @if ($booking->room_selection_handling_note)
                Lý do: {{ $booking->room_selection_handling_note }}<br>
            @endif
            <strong>Phòng dự phòng:</strong> {{ $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ') ?: '---' }}.<br>
            Phòng này vẫn đang được giữ và không thu phí đảm bảo yêu cầu phòng. Nếu từ chối, booking sẽ được hủy do khách sạn không đáp ứng yêu cầu và toàn bộ số đã thanh toán phải được hoàn lại.
        </div>
        <div style="display:grid;gap:10px;margin-top:14px">
            <form method="post" action="{{ route('guest-bookings.room-selection-fallback',['token'=>$token]) }}">
                @csrf
                <input type="hidden" name="decision" value="accept">
                <button class="btn" type="submit" onclick="return confirm('Bạn đồng ý sử dụng phòng dự phòng đang được giữ?');">Đồng ý sử dụng phòng dự phòng</button>
            </form>
            <form method="post" action="{{ route('guest-bookings.room-selection-fallback',['token'=>$token]) }}">
                @csrf
                <input type="hidden" name="decision" value="decline">
                <button class="btn btn-danger" type="submit" onclick="return confirm('Từ chối phòng dự phòng sẽ hủy booking và khách sạn phải hoàn lại toàn bộ số tiền đã thanh toán. Tiếp tục?');">Từ chối phòng dự phòng và hủy booking</button>
            </form>
            <a class="btn" href="{{ route('rooms') }}">Xem hạng phòng khác</a>
        </div>
    @elseif ($selectionStatus === 'fallback_accepted')
        <div class="alert" style="margin-top:18px;background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:14px;border-radius:10px">
            <strong>Bạn đã đồng ý sử dụng phòng dự phòng.</strong><br>
            Phòng xác nhận: {{ $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ') ?: '---' }}. Không thu phí đảm bảo yêu cầu phòng.
        </div>
    @elseif ($selectionStatus === 'fallback_declined')
        <div class="warning" style="margin-top:18px">
            <strong>Bạn đã từ chối phòng dự phòng và booking đã được hủy.</strong><br>
            @if ((float) ($booking->refund_due_amount ?? 0) > 0)
                Số tiền khách sạn phải hoàn lại: <strong>{{ number_format((float) $booking->refund_due_amount,0,',','.') }}đ</strong> · {{ $booking->refund_status === 'completed' ? 'Đã hoàn tất' : 'Đang chờ xử lý' }}.
            @else
                Booking chưa phát sinh khoản thanh toán cần hoàn.
            @endif
            <br><a href="{{ route('rooms') }}">Xem hạng phòng khác</a>.
        </div>
    @endif
@endif

@if($booking->status === 'cancelled')
    <div class="alert alert-error" style="margin-top:18px;margin-bottom:0">Booking này đã được hủy. Tiền đã thanh toán không được hoàn lại hoặc bảo lưu.</div>
@elseif($canCancel && (($booking->room_selection_status ?? null) !== 'awaiting_guest'))
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
