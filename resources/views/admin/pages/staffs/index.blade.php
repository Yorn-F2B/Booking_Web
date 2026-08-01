@extends('layouts.admin')

@section('title', 'Danh sách nhân viên')

@section('content')
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('staffs.index') }}">Admin</a> / Nhân viên
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Danh sách nhân viên</h2>
                    <p>Quản lý nhân viên khách sạn</p>
                </div>

                <a href="{{ route('staffs.create') }}" class="btn btn-gold">
                    <i class="bx bx-plus me-1"></i>Thêm nhân viên
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
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th>SĐT</th>
                                <th>Chức vụ</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($staffs as $staff)
                                <tr>
                                    <td>{{ $staff->id }}</td>
                                    <td>{{ $staff->full_name }}</td>
                                    <td>{{ $staff->user->email ?? 'Chưa có tài khoản' }}</td>
                                    <td>{{ $staff->phone ?? '-' }}</td>
                                    <td>{{ $staff->position ?? '-' }}</td>

                                    <td>
                                        @if ($staff->work_status === 'working')
                                            <span class="badge bg-success">Đang làm</span>
                                        @elseif ($staff->work_status === 'temporary_leave')
                                            <span class="badge bg-warning text-dark">Nghỉ tạm</span>
                                        @else
                                            <span class="badge bg-secondary">Đã nghỉ</span>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('staffs.show', $staff->id) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            Xem
                                        </a>

                                        <a href="{{ route('staffs.edit', $staff->id) }}" class="btn btn-sm btn-outline-primary">
                                            Sửa
                                        </a>

                                        <form action="{{ route('staffs.destroy', $staff->id) }}" method="POST" class="d-inline"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa nhân viên này không?')">
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
                                        Chưa có nhân viên nào
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>

                <div class="mt-3">
                    {{ $staffs->links() }}
                </div>
            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>
@endsection