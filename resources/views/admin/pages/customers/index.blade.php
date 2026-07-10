@extends('layouts.admin')

@section('title', 'Quản lý khách hàng')

@section('content')
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Khách hàng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Quản lý khách hàng</h2>
                    <p>Danh sách khách hàng và lịch sử đặt phòng</p>
                </div>

            </div>

            <!-- Filter -->
            <div class="bg-white p-4 rounded-3 mb-4 border">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" 
                               name="keyword" 
                               class="form-control" 
                               placeholder="Tìm theo tên, SĐT, email, CCCD..."
                               value="{{ request('keyword') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Tất cả trạng thái</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="blacklist" {{ request('status') === 'blacklist' ? 'selected' : '' }}>Blacklist</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Lọc</button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-3 border overflow-hidden">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tên khách hàng</th>
                            <th>SĐT</th>
                            <th>Email</th>
                            <th>CCCD</th>
                            <th>Số booking</th>
                            <th>Tổng chi tiêu</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $customer->first_name }} {{ $customer->last_name }}</div>
                                    @if($customer->gender)
                                        <small class="text-muted">{{ $customer->gender === 'male' ? 'Nam' : ($customer->gender === 'female' ? 'Nữ' : 'Khác') }}</small>
                                    @endif
                                </td>
                                <td>{{ $customer->phone }}</td>
                                <td>{{ $customer->email ?? '-' }}</td>
                                <td>{{ $customer->cccd ?? '-' }}</td>
                                <td>{{ $customer->bookings->count() }}</td>
                                <td>{{ number_format($customer->bookings->sum('estimated_total'), 0, ',', '.') }}đ</td>
                                <td>
                                    @if($customer->status === 'active')
                                        <span class="badge bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-danger">Blacklist</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-outline-primary">
                                        Chi tiết
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if($customers->hasPages())
                    <div class="p-3 border-top">
                        {{ $customers->links() }}
                    </div>
                @endif
            </div>

        </main>

    </div>
@endsection
