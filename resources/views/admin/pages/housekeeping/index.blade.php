@extends('layouts.admin')

@section('title', 'Phòng cần dọn')

@section('content')

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Phòng cần dọn
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Phòng cần dọn</h2>
                    <p>Danh sách phòng đã check-out và đang chờ quản lý tầng xác nhận dọn xong</p>
                </div>

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

            <div class="settings-section">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th>Phòng</th>
                                <th>Hạng phòng</th>
                                <th>Tầng</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($rooms as $room)

                                <tr>

                                    <td>
                                        <strong>Phòng {{ $room->room_number }}</strong>
                                    </td>

                                    <td>
                                        {{ $room->category->name ?? 'Không xác định' }}
                                    </td>

                                    <td>
                                        {{ $room->floor_number ?? '---' }}
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">
                                            Đang dọn dẹp
                                        </span>
                                    </td>

                                    <td>
                                        {{ $room->note ?: 'Không có ghi chú' }}
                                    </td>

                                    <td class="text-end">

                                        <form action="{{ route('admin.housekeeping.mark-available', $room->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Xác nhận phòng đã dọn xong và sẵn sàng cho thuê?')">

                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="btn btn-sm btn-success">
                                                Dọn xong
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        Không có phòng nào cần dọn.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    {{ $rooms->links() }}
                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

@endsection