@extends('layouts.admin')

@section('title', 'Danh sách dịch vụ')

@section('content')

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('admin.dashboard') }}">Admin</a> / Dịch vụ
        </p>

        <div class="admin-page-head">

            <div>
                <h2>Danh sách dịch vụ</h2>
                <p>Quản lý dịch vụ, minibar và phí phát sinh</p>
            </div>

            <a href="{{ route('services.create') }}" class="btn btn-gold">
                <i class="bx bx-plus me-1"></i>
                Thêm dịch vụ
            </a>

        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="settings-section">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tên dịch vụ</th>
                            <th>Loại</th>
                            <th>Giá</th>
                            <th>Đơn vị</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($services as $service)

                            <tr>

                                <td>{{ $service->id }}</td>

                                <td>{{ $service->name }}</td>

                                <td>
                                    @if ($service->type == 'service')
                                        <span class="badge bg-primary">Dịch vụ</span>
                                    @elseif ($service->type == 'minibar')
                                        <span class="badge bg-warning text-dark">Minibar</span>
                                    @elseif ($service->type == 'damage_fee')
                                        <span class="badge bg-danger">Phí hư hại</span>
                                    @elseif ($service->type == 'violation_fee')
                                        <span class="badge bg-dark">Phí vi phạm</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $service->type }}</span>
                                    @endif
                                </td>

                                <td>
                                    {{ number_format($service->price, 0, ',', '.') }}đ
                                </td>

                                <td>{{ $service->unit }}</td>

                                <td>
                                    @if ($service->status == 'active')
                                        <span class="badge bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-secondary">Tạm ẩn</span>
                                    @endif
                                </td>

                                <td class="text-end text-nowrap">

                                    <a href="{{ route('services.show', $service->id) }}"
                                        class="btn btn-sm btn-outline-secondary">
                                        Xem
                                    </a>

                                    <a href="{{ route('services.edit', $service->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Sửa
                                    </a>

                                    <form action="{{ route('services.destroy', $service->id) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa dịch vụ này không?')">

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
                                <td colspan="7" class="text-center text-muted">
                                    Chưa có dịch vụ nào
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="mt-3">
                {{ $services->links() }}
            </div>

        </div>

    </main>

    <footer class="admin-footer">
        <span>MCuong Hotel Admin</span>
    </footer>

</div>

@endsection