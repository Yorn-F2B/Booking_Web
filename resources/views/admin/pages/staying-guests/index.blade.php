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
                    <p>Danh sách các phòng đang có khách ở tại khách sạn</p>
                </div>
            </div>

            <div class="settings-section mb-4">
                <form action="{{ route('admin.staying-guests.index') }}" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Tìm tên, SĐT khách, số phòng, mã booking..." value="{{ request('search') }}">
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
                                <th>Khách đại diện</th>
                                <th>Thông tin liên hệ</th>
                                <th>SL Khách</th>
                                <th>Nhận phòng</th>
                                <th>Dự kiến trả phòng</th>
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
                                        @if($booking->customer)
                                            <a href="{{ route('admin.customers.show', $booking->customer->id) }}" class="fw-bold">
                                                {{ $booking->customer->last_name }} {{ $booking->customer->first_name }}
                                            </a>
                                            @if($booking->guests && $booking->guests->count() > 0)
                                                <div class="mt-2 small text-muted">
                                                    <div class="fw-bold mb-1">Khai báo lưu trú:</div>
                                                    <ul class="mb-0 ps-3">
                                                    @foreach($booking->guests as $guest)
                                                        <li>{{ $guest->full_name }} ({{ $guest->cccd ?: 'Chưa có CCCD' }})</li>
                                                    @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">Chưa có thông tin</span>
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
                                        {{ $booking->actual_adult_count ?? $booking->adult_count }} NL 
                                        @if(($booking->actual_child_count ?? $booking->child_count) > 0)
                                            / {{ $booking->actual_child_count ?? $booking->child_count }} TE
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
