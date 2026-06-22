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
                    <h2>Thông tin khách hàng: {{ $customer->last_name }} {{ $customer->first_name }}</h2>
                    <p>Mã khách hàng: #{{ $customer->id }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Sửa thông tin
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Quay lại
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-5 mb-4">
                    <div class="settings-section h-100">
                        <h5 class="mb-4">Thông tin cá nhân</h5>
                        
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <th class="ps-0" style="width: 150px;">Họ và tên:</th>
                                    <td><strong>{{ $customer->last_name }} {{ $customer->first_name }}</strong></td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Số điện thoại:</th>
                                    <td>{{ $customer->phone ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0">CCCD/Passport:</th>
                                    <td>{{ $customer->cccd ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Email:</th>
                                    <td>{{ $customer->email ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Ngày sinh:</th>
                                    <td>{{ $customer->birthday ? \Carbon\Carbon::parse($customer->birthday)->format('d/m/Y') : '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Giới tính:</th>
                                    <td>
                                        @if($customer->gender == 'male') Nam
                                        @elseif($customer->gender == 'female') Nữ
                                        @elseif($customer->gender == 'other') Khác
                                        @else - @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Trạng thái:</th>
                                    <td>
                                        @if ($customer->status === 'active')
                                            <span class="badge bg-success">Hoạt động</span>
                                        @else
                                            <span class="badge bg-danger">Danh sách đen</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Địa chỉ:</th>
                                    <td>{{ $customer->address ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Ghi chú:</th>
                                    <td>{{ $customer->note ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Tài khoản User:</th>
                                    <td>
                                        @if($customer->user_id)
                                            <span class="badge bg-info">Đã liên kết (#{{ $customer->user_id }})</span>
                                        @else
                                            <span class="text-muted">Chưa có tài khoản</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="ps-0">Ngày tạo:</th>
                                    <td>{{ $customer->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-7 mb-4">
                    <div class="settings-section h-100">
                        <h5 class="mb-4">Lịch sử đặt phòng (10 đơn gần nhất)</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mã ĐP</th>
                                        <th>Loại phòng</th>
                                        <th>Ngày nhận</th>
                                        <th>Ngày trả</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($customer->bookings as $booking)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.bookings.show', $booking->id) }}">
                                                    {{ $booking->booking_code }}
                                                </a>
                                            </td>
                                            <td>{{ optional($booking->roomCategory)->name ?? '-' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($booking->check_in_date)->format('d/m/Y') }}</td>
                                            <td>{{ \Carbon\Carbon::parse($booking->check_out_date)->format('d/m/Y') }}</td>
                                            <td>{{ number_format($booking->estimated_total, 0, ',', '.') }}đ</td>
                                            <td>
                                                @if($booking->status == 'pending') <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                                @elseif($booking->status == 'confirmed') <span class="badge bg-primary">Đã xác nhận</span>
                                                @elseif($booking->status == 'checked_in') <span class="badge bg-info text-dark">Đang ở</span>
                                                @elseif($booking->status == 'checked_out') <span class="badge bg-success">Đã trả phòng</span>
                                                @elseif($booking->status == 'cancelled') <span class="badge bg-danger">Đã hủy</span>
                                                @else <span class="badge bg-secondary">{{ $booking->status }}</span> @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-3 text-muted">Khách hàng này chưa có đơn đặt phòng nào.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
@endsection
