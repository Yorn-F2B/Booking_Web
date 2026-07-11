@extends('layouts.admin')

@section('title', 'Chi tiết phòng')

@section('content')

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.rooms.index') }}">
                    Admin
                </a>

                / Chi tiết phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Chi tiết phòng</h2>
                    <p>Thông tin chi tiết phòng khách sạn</p>
                </div>

                <a href="{{ route('admin.rooms.index', ['edit_room' => $room->id]) }}" class="btn btn-gold">
                    <i class="bx bx-edit me-1"></i>
                    Chỉnh sửa
                </a>

            </div>

            <div class="settings-section">

                <div class="row">

                    <div class="col-lg-12">

                        <div class="row">

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    ID phòng
                                </label>

                                <h5>
                                    {{ $room->id }}
                                </h5>

                            </div>

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    Số phòng
                                </label>

                                <h5>
                                    {{ $room->room_number }}
                                </h5>

                            </div>

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    Tầng
                                </label>

                                <h5>
                                    {{ $room->floor_number ?? 'Chưa cập nhật' }}
                                </h5>

                            </div>

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    Loại phòng
                                </label>

                                <h5>
                                    {{ $room->category->name ?? 'Không xác định' }}
                                </h5>

                            </div>

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    Trạng thái
                                </label>

                                <div>

                                    @if($room->status == 'available')

                                        <span class="badge bg-success">
                                            Còn trống
                                        </span>

                                    @elseif($room->status == 'reserved')

                                        <span class="badge bg-warning text-dark">
                                            Đã đặt trước
                                        </span>

                                    @elseif($room->status == 'occupied')

                                        <span class="badge bg-danger">
                                            Đang có khách
                                        </span>

                                    @elseif($room->status == 'cleaning')

                                        <span class="badge bg-info text-dark">
                                            Đang dọn phòng
                                        </span>

                                    @else

                                        <span class="badge bg-secondary">
                                            Bảo trì
                                        </span>

                                    @endif

                                </div>

                            </div>

                            <div class="col-md-4 mb-4">

                                <label class="text-muted small">
                                    Ngày tạo
                                </label>

                                <h5>
                                    {{ $room->created_at?->format('d/m/Y H:i') }}
                                </h5>

                            </div>

                        </div>

                    </div>

                </div>

                <hr class="my-4">

                <div>

                    <label class="text-muted small mb-2">
                        Ghi chú
                    </label>

                    <div class="lh-lg">

                        {{ $room->note ?: 'Không có ghi chú' }}

                    </div>

                </div>

                <div class="mt-4 d-flex gap-2">

                    <a href="{{ route('admin.rooms.index', ['edit_room' => $room->id]) }}"
                        class="btn btn-primary">
                        Chỉnh sửa
                    </a>

                    <a href="{{ route('admin.rooms.index') }}"
                        class="btn btn-outline-secondary">
                        Quay lại
                    </a>

                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

@endsection