@extends('layouts.admin')

@section('title', 'Danh sách khách hàng')

@section('content')
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Khách hàng
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Danh sách khách hàng</h2>
                    <p>Quản lý thông tin khách lưu trú</p>
                </div>

                <a href="{{ route('admin.customers.create') }}" class="btn btn-gold">
                    <i class="bx bx-plus me-1"></i>Thêm khách hàng
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <div class="settings-section mb-4">
                <form action="{{ route('admin.customers.index') }}" method="GET" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" placeholder="Tìm tên, SĐT, CCCD, Email..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="blacklist" {{ request('status') === 'blacklist' ? 'selected' : '' }}>Danh sách đen</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Tìm kiếm</button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary w-100">Xóa lọc</a>
                    </div>
                </form>
            </div>

            <div class="settings-section">
                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Họ tên</th>
                                <th>SĐT</th>
                                <th>CCCD</th>
                                <th>Email</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($customers as $customer)
                                <tr>
                                    <td>{{ $customer->id }}</td>
                                    <td>{{ $customer->last_name }} {{ $customer->first_name }}</td>
                                    <td>{{ $customer->phone ?? '-' }}</td>
                                    <td>{{ $customer->cccd ?? '-' }}</td>
                                    <td>{{ $customer->email ?? '-' }}</td>

                                    <td>
                                        @if ($customer->status === 'active')
                                            <span class="badge bg-success">Hoạt động</span>
                                        @else
                                            <span class="badge bg-danger">Danh sách đen</span>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('admin.customers.show', $customer->id) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            Xem
                                        </a>

                                        <a href="{{ route('admin.customers.edit', $customer->id) }}" class="btn btn-sm btn-outline-primary">
                                            Sửa
                                        </a>

                                        <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa khách hàng này không?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Xóa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Không tìm thấy khách hàng nào.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>

                <div class="mt-3">
                    {{ $customers->links() }}
                </div>
            </div>

        </main>

    </div>
@endsection
