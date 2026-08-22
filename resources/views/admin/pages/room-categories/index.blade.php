@extends('layouts.admin')
@section('title', 'Danh sách loại phòng')

@section('content')

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Loại phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Danh sách loại phòng</h2>
                    <p>Quản lý các hạng phòng khách sạn</p>
                </div>

                <a href="{{ route('room-categories.create') }}" class="btn btn-gold">
                    <i class="bx bx-plus me-1"></i>
                    Thêm loại phòng
                </a>

            </div>
<div class="settings-section mb-3">
                <form method="GET" action="{{ route('room-categories.index') }}" class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tìm hạng phòng</label>
                        <input type="text" name="search" value="{{ $search }}" class="form-control"
                            placeholder="Nhập tên hạng phòng...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="all" @selected($status === 'all')>Tất cả</option>
                            <option value="active" @selected($status === 'active')>Đang hoạt động</option>
                            <option value="inactive" @selected($status === 'inactive')>Tạm ẩn</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1"><i class="bx bx-filter-alt me-1"></i>Lọc</button>
                        <a href="{{ route('room-categories.index') }}" class="btn btn-outline-secondary">Đặt lại</a>
                    </div>
                </form>
            </div>

            <div class="settings-section">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Ảnh</th>
                                <th>Tên loại phòng</th>
                                <th>Giá</th>
                                <th>Số người</th>
                                <th>Diện tích</th>
                                <th>Giường</th>
                                <th>Tổng số phòng</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($roomCategories as $category)

                                <tr>

                                    <td>{{ $category->id }}</td>

                                    <td>

                                        @if ($category->thumbnail)

                                            <img src="{{ asset('storage/' . $category->thumbnail) }}" width="90" height="60"
                                                style="object-fit: cover; border-radius: 8px;">

                                        @elseif($category->images->count())

                                            <img src="{{ asset('storage/' . $category->images->first()->image) }}" width="90"
                                                height="60" style="object-fit: cover; border-radius: 8px;">

                                        @else

                                            <span class="text-muted">
                                                Chưa có ảnh
                                            </span>

                                        @endif

                                    </td>

                                    <td>
                                        {{ $category->name }}
                                    </td>

                                    <td>
                                        {{ number_format($category->price, 0, ',', '.') }}đ
                                    </td>

                                    <td>

                                        {{ $category->adult_capacity }} NL

                                        <br>

                                        <small class="text-muted">
                                            {{ $category->child_capacity }} TE
                                        </small>

                                    </td>

                                    <td>
                                        {{ $category->area }} m²
                                    </td>

                                    <td>
                                        {{ $category->bed_count }} giường
                                    </td>

                                    <td>
                                        <span class="fw-bold">{{ $category->rooms_count }}</span> phòng
                                    </td>

                                    <td>

                                        @if ($category->status === 'active')

                                            <span class="badge bg-success">
                                                Hoạt động
                                            </span>

                                        @else

                                            <span class="badge bg-secondary">
                                                Tạm ẩn
                                            </span>

                                        @endif

                                    </td>

                                    <td class="text-end text-nowrap">

                                        <a href="{{ route('room-categories.show', $category->id) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            Xem
                                        </a>


                                        <a href="{{ route('room-categories.edit', $category->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Sửa
                                        </a>

                                        <form action="{{ route('room-categories.destroy', $category->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Bạn có chắc muốn xóa loại phòng này không?')">

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
                                    <td colspan="10" class="text-center text-muted">
                                        Chưa có loại phòng nào
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    {{ $roomCategories->links() }}
                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

@endsection