@extends('layouts.admin')

@section('title', 'Chi tiết khách hàng')

@section('content')
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / 
                <a href="{{ route('admin.customers.index') }}">Khách hàng</a> / 
                Chi tiết
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>{{ $customer->first_name }} {{ $customer->last_name }}</h2>
                    <p>Thông tin chi tiết và lịch sử đặt phòng</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-outline-primary">
                        Chỉnh sửa
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
                        Quay lại
                    </a>
                </div>

            </div>

            <!-- Customer Info -->
            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="bg-white p-4 rounded-3 border">
                        <h5 class="mb-3">Thông tin cá nhân</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Họ tên</label>
                                <div class="fw-semibold">{{ $customer->first_name }} {{ $customer->last_name }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Số điện thoại</label>
                                <div class="fw-semibold">{{ $customer->phone }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Email</label>
                                <div>{{ $customer->email ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">CCCD</label>
                                <div>{{ $customer->cccd ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Ngày sinh</label>
                                <div>{{ $customer->birthday ? \Carbon\Carbon::parse($customer->birthday)->format('d/m/Y') : '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Giới tính</label>
                                <div>{{ $customer->gender ? ($customer->gender === 'male' ? 'Nam' : ($customer->gender === 'female' ? 'Nữ' : 'Khác')) : '-' }}</div>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small">Địa chỉ</label>
                                <div>{{ $customer->address ?? '-' }}</div>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small">Ghi chú</label>
                                <div>{{ $customer->note ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-white p-4 rounded-3 border">
                        <h5 class="mb-3">Thống kê</h5>
                        <div class="mb-3">
                            <label class="text-muted small">Tổng số booking</label>
                            <div class="fs-4 fw-bold">{{ $totalBookings }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Tổng chi tiêu</label>
                            <div class="fs-4 fw-bold text-success">{{ number_format($totalSpent, 0, ',', '.') }}đ</div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Đã thanh toán</label>
                            <div class="fs-4 fw-bold text-primary">{{ number_format($totalPaid, 0, ',', '.') }}đ</div>
                        </div>
                        <div>
                            <label class="text-muted small">Còn nợ</label>
                            <div class="fs-4 fw-bold text-warning">{{ number_format($totalSpent - $totalPaid, 0, ',', '.') }}đ</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking History -->
            <div class="bg-white p-4 rounded-3 border">
                <h5 class="mb-3">Lịch sử đặt phòng</h5>
                
                @if($customer->bookings->isEmpty())
                    <div class="text-center py-4 text-muted">
                        Chưa có booking nào
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Mã booking</th>
                                    <th>Hạng phòng</th>
                                    <th>Ngày nhận</th>
                                    <th>Ngày trả</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->bookings as $booking)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.bookings.show', $booking) }}" class="fw-semibold">
                                                {{ $booking->booking_code }}
                                            </a>
                                        </td>
                                        <td>{{ $booking->roomCategory->name ?? '-' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($booking->check_in_at)->format('d/m/Y H:i') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($booking->check_out_at)->format('d/m/Y H:i') }}</td>
                                        <td>{{ number_format($booking->estimated_total, 0, ',', '.') }}đ</td>
                                        <td>
                                            @switch($booking->status)
                                                @case('confirmed')
                                                    <span class="badge bg-warning">Đã xác nhận</span>
                                                    @break
                                                @case('checked_in')
                                                    <span class="badge bg-primary">Đã check-in</span>
                                                    @break
                                                @case('checked_out')
                                                    <span class="badge bg-success">Đã trả phòng</span>
                                                    @break
                                                @case('cancelled')
                                                    <span class="badge bg-danger">Đã hủy</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ $booking->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary">
                                                Xem
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </main>

    </div>
@endsection
