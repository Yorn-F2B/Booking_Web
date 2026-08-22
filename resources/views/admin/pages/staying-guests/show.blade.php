@extends('layouts.admin')

@section('title', 'Chi tiết khách đang lưu trú')

@section('content')
<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('admin.dashboard') }}">Admin</a> /
            <a href="{{ route('admin.staying-guests.index') }}">Khách đang lưu trú</a> / Chi tiết
        </p>

        <div class="admin-page-head d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>Chi tiết lưu trú</h2>
                <p>Booking {{ $booking->booking_code }} · toàn bộ người thực tế đã được khai theo từng phòng</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.bookings.show', $booking) }}#stayingGuestsPanel" class="btn btn-outline-primary">Quản lý hồ sơ khách</a>
                <a href="{{ route('admin.staying-guests.index') }}" class="btn btn-outline-secondary">Quay lại</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Kỳ lưu trú</h5>
                <div class="d-grid gap-2 small">
                    <div class="d-flex justify-content-between"><span class="text-muted">Nhận thực tế</span><strong>{{ $booking->actual_check_in?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '---' }}</strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Trả dự kiến</span><strong>{{ $booking->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '---' }}</strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Khách hiện tại</span><strong>{{ $booking->guests->where('status', 'checked_in')->count() }} người</strong></div>
                </div>
            </div></div>
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Đại diện đoàn</h5>
                @php
                    $representative = $booking->guests->firstWhere('is_booking_representative', true);
                @endphp
                <div class="d-grid gap-2 small">
                    <div><span class="text-muted d-block">Họ tên</span><strong>{{ $representative?->full_name ?? 'Chưa chọn' }}</strong></div>
                    <div><span class="text-muted d-block">Giấy tờ</span><strong>{{ $representative?->display_document ?? '---' }}</strong></div>
                    <div><span class="text-muted d-block">Phòng</span><strong>{{ $representative?->bookingRoom?->room?->room_number ?? '---' }}</strong></div>
                </div>
            </div></div>
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Phòng đang giữ</h5>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($booking->bookingRooms as $bookingRoom)
                        <span class="badge text-bg-light border p-2">Phòng {{ $bookingRoom->room?->room_number ?? '---' }} · {{ $booking->guests->where('booking_room_id', $bookingRoom->id)->count() }} khách</span>
                    @endforeach
                </div>
            </div></div>
        </div>

        @foreach($booking->bookingRooms as $bookingRoom)
            @php
                $roomGuests = $booking->guests->where('booking_room_id', $bookingRoom->id);
            @endphp
            <div class="settings-section mb-3">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Phòng {{ $bookingRoom->room?->room_number ?? '---' }}</h5>
                        <div class="text-muted small">{{ $bookingRoom->room?->category?->name ?? '---' }}</div>
                    </div>
                    <span class="badge text-bg-primary">{{ $roomGuests->count() }} khách</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>Khách</th><th>Nhóm tuổi</th><th>Giấy tờ</th><th>Ngày sinh</th><th>Người giám hộ</th><th>Trạng thái</th>
                        </tr></thead>
                        <tbody>
                            @forelse($roomGuests as $guest)
                                <tr>
                                    <td><strong>{{ $guest->full_name }}</strong>@if($guest->is_booking_representative)<div><span class="badge text-bg-primary">Đại diện đoàn</span></div>@endif</td>
                                    <td>{{ ['adult'=>'Người lớn','child'=>'Trẻ em','infant'=>'Em bé'][$guest->guest_type] ?? '---' }}</td>
                                    <td>{{ $guest->display_document ?: 'Chưa xuất trình' }}</td>
                                    <td>{{ $guest->birthday?->format('d/m/Y') ?? '---' }}</td>
                                    <td>{{ $guest->guardian?->full_name ?? '---' }}@if($guest->guardian_relationship)<div class="small text-muted">{{ $guest->guardian_relationship }}</div>@endif</td>
                                    <td>{{ ['registered'=>'Chưa đến','checked_in'=>'Đang lưu trú','checked_out'=>'Đã rời đi'][$guest->status] ?? $guest->status }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">Phòng này chưa có khách thực tế nhận phòng.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </main>
</div>
@endsection
