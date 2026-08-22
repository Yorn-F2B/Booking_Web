@extends('layouts.admin')

@section('title', 'Danh sách tiện ích')

@section('content')

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('admin.dashboard') }}">Admin</a> / Tiện ích
        </p>

        <div class="admin-page-head">

            <div>
                <h2>Danh sách tiện ích</h2>
                <p>Quản lý các tiện ích hiển thị cho hạng phòng</p>
            </div>

            <a href="{{ route('amenities.create') }}" class="btn btn-gold">
                <i class="bx bx-plus me-1"></i>
                Thêm tiện ích
            </a>

        </div>
<div class="settings-section">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Icon</th>
                            <th>Tên tiện ích</th>
                            <th>Ngày tạo</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($amenities as $amenity)

                            <tr>

                                <td>{{ $amenity->id }}</td>

                                <td>
                                    @if ($amenity->icon)

                                        <i class="{{ $amenity->icon }}" style="font-size: 24px;"></i>

                                    @else

                                        <span class="text-muted">Chưa có icon</span>

                                    @endif
                                </td>

                                <td>{{ $amenity->name }}</td>

                                <td>
                                    {{ $amenity->created_at?->format('d/m/Y H:i') }}
                                </td>

                                <td class="text-end text-nowrap">

                                    <a href="{{ route('amenities.show', $amenity->id) }}"
                                        class="btn btn-sm btn-outline-secondary">
                                        Xem
                                    </a>

                                    <a href="{{ route('amenities.edit', $amenity->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Sửa
                                    </a>

                                    <form action="{{ route('amenities.destroy', $amenity->id) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa tiện ích này không?')">

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
                                <td colspan="5" class="text-center text-muted">
                                    Chưa có tiện ích nào
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="mt-3">
                {{ $amenities->links() }}
            </div>

        </div>

    </main>

    <footer class="admin-footer">
        <span>MCuong Hotel Admin</span>
    </footer>

</div>

@endsection