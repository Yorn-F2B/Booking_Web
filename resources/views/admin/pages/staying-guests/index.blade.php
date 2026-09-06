@extends('layouts.admin')

@section('title', 'Danh sách khách đang lưu trú')

@section('content')
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Khách đang lưu trú
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Khách đang lưu trú</h2>
                    <p>Mỗi phòng chỉ quản lý một người đại diện; booking nhiều phòng có thêm một đại diện cả đoàn</p>
                </div>
            </div>

            <div class="settings-section mb-4">
                <form action="{{ route('admin.staying-guests.index') }}" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Tìm người đại diện, giấy tờ, SĐT, phòng, mã booking..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.staying-guests.index') }}" class="btn btn-outline-secondary w-100">Xóa lọc</a>
                    </div>
                </form>
            </div>

            <div class="settings-section">
                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Phòng</th>
                                <th>Mã Booking</th>
                                <th>Người đại diện phòng</th>
                                <th>Liên hệ booking</th>
                                <th>Nhận phòng</th>
                                <th>Dự kiến trả phòng</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($bookings as $booking)
                                @php
                                    $rooms = $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ');
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $rooms ?: 'Chưa gán' }}</strong>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.bookings.show', $booking->id) }}" class="fw-bold">
                                            {{ $booking->booking_code }}
                                        </a>
                                    </td>
                                    <td>
                                        @php
                                            $roomRepresentatives = $booking->bookingRooms->map(function ($bookingRoom) use ($booking) {
                                                return $booking->guests->where('booking_room_id', $bookingRoom->id)
                                                    ->first(fn ($guest) => $guest->guest_type === 'adult');
                                            })->filter();
                                        @endphp
                                        @if($roomRepresentatives->isNotEmpty())
                                            <ul class="mb-0 ps-3 small">
                                                @foreach($roomRepresentatives as $guest)
                                                    <li>
                                                        <strong>P.{{ $guest->bookingRoom?->room?->room_number ?? '---' }}</strong> · {{ $guest->full_name }}
                                                        @if($guest->is_booking_representative)<span class="text-primary fw-semibold"> · đại diện đoàn</span>@endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-warning">Chưa có người đại diện</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($booking->customer)
                                            <div><i class="bx bx-phone me-1"></i>{{ $booking->customer->phone ?? '-' }}</div>
                                            <div class="small text-muted"><i class="bx bx-id-card me-1"></i>{{ $booking->customer->cccd ?? '-' }}</div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        {{ $booking->actual_check_in ? \Carbon\Carbon::parse($booking->actual_check_in)->format('d/m/Y H:i') : ($booking->check_in_at ? \Carbon\Carbon::parse($booking->check_in_at)->format('d/m/Y H:i') : '-') }}
                                    </td>
                                    <td>
                                        <span class="text-danger fw-bold">
                                            {{ $booking->check_out_at ? \Carbon\Carbon::parse($booking->check_out_at)->format('d/m/Y H:i') : '-' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.staying-guests.show', $booking) }}" class="btn btn-sm btn-outline-primary">
                                            Xem chi tiết
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Hiện tại không có phòng nào đang có khách lưu trú.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>

                <div class="mt-3">
                    {{ $bookings->links() }}
                </div>
            </div>

        </main>

    </div>
@endsection
