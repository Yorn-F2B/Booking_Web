@extends('layouts.admin')

@section('title', 'Chi tiết khách đang lưu trú')

@section('content')
<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('admin.dashboard') }}">Admin</a> /
            <a href="{{ route('admin.staying-guests.index') }}">Khách đang lưu trú</a> / Chi tiết
        </p>

        @php
            $needsGroupRepresentative = $booking->bookingRooms->count() > 1;
            $groupRepresentative = $booking->guests->firstWhere('is_booking_representative', true);
            $actualTotal = (int) $booking->adult_count + (int) $booking->child_count + (int) ($booking->baby_count ?? 0);
        @endphp

        <div class="admin-page-head d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>Chi tiết lưu trú</h2>
                <p>Booking {{ $booking->booking_code }} · số người thực tế theo từng phòng; chỉ lưu hồ sơ người đại diện phòng</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.bookings.show', $booking) }}#stayingGuestsPanel" class="btn btn-outline-primary">Quản lý người đại diện</a>
                <a href="{{ route('admin.staying-guests.index') }}" class="btn btn-outline-secondary">Quay lại</a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Kỳ lưu trú</h5>
                <div class="d-grid gap-2 small">
                    <div class="d-flex justify-content-between"><span class="text-muted">Nhận thực tế</span><strong>{{ $booking->actual_check_in?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '---' }}</strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Trả dự kiến</span><strong>{{ $booking->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? '---' }}</strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Khách thực tế</span><strong>{{ $actualTotal }} người</strong></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Cơ cấu</span><strong>{{ (int) $booking->adult_count }} NL / {{ (int) $booking->child_count }} TE / {{ (int) ($booking->baby_count ?? 0) }} EB</strong></div>
                </div>
            </div></div>
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Đầu mối đoàn</h5>
                @if($needsGroupRepresentative)
                    <div class="d-grid gap-2 small">
                        <div><span class="text-muted d-block">Họ tên</span><strong>{{ $groupRepresentative?->full_name ?? 'Chưa chọn' }}</strong></div>
                        <div><span class="text-muted d-block">Giấy tờ</span><strong>{{ $groupRepresentative?->display_document ?? '---' }}</strong></div>
                        <div><span class="text-muted d-block">Phòng</span><strong>{{ $groupRepresentative?->bookingRoom?->room?->room_number ?? '---' }}</strong></div>
                    </div>
                @else
                    <div class="small text-muted">Booking chỉ có 1 phòng nên không cần thêm vai trò đại diện cả đoàn.</div>
                @endif
            </div></div>
            <div class="col-lg-4"><div class="settings-section h-100">
                <h5 class="fw-bold mb-3">Nguyên tắc lưu hồ sơ</h5>
                <div class="small">
                    <div class="mb-2"><strong>{{ $booking->bookingRooms->count() }} phòng</strong> → cần {{ $booking->bookingRooms->count() }} người đại diện phòng.</div>
                    <div>Không lưu toàn bộ danh sách khách. Số người thực tế nằm ở phân bổ từng phòng.</div>
                </div>
            </div></div>
        </div>

        @foreach($booking->bookingRooms as $bookingRoom)
            @php
                $roomRepresentative = $booking->guests->where('booking_room_id', $bookingRoom->id)
                    ->first(fn ($guest) => $guest->guest_type === 'adult');
                $roomOccupancy = (int) $bookingRoom->adult_count + (int) $bookingRoom->child_count + (int) ($bookingRoom->baby_count ?? 0);
            @endphp
            <div class="settings-section mb-3">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
                    <div>
                        <h5 class="fw-bold mb-1">Phòng {{ $bookingRoom->room?->room_number ?? '---' }}</h5>
                        <div class="text-muted small">{{ $bookingRoom->room?->category?->name ?? '---' }}</div>
                    </div>
                    <span class="badge text-bg-primary">{{ $roomOccupancy }} khách thực tế · {{ (int) $bookingRoom->adult_count }} NL / {{ (int) $bookingRoom->child_count }} TE / {{ (int) ($bookingRoom->baby_count ?? 0) }} EB</span>
                </div>

                @if($roomRepresentative)
                    <div class="row g-3">
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="small text-muted d-block">Người đại diện phòng</span><strong>{{ $roomRepresentative->full_name }}</strong>@if($roomRepresentative->is_booking_representative)<div class="mt-1"><span class="badge text-bg-primary">Đại diện cả đoàn</span></div>@endif</div></div>
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="small text-muted d-block">Giấy tờ</span><strong>{{ $roomRepresentative->display_document ?: 'Chưa xuất trình' }}</strong></div></div>
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="small text-muted d-block">Ngày sinh</span><strong>{{ $roomRepresentative->birthday?->format('d/m/Y') ?? '---' }}</strong></div></div>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">Phòng này chưa có hồ sơ người đại diện.</div>
                @endif
            </div>
        @endforeach
    </main>
</div>
@endsection
