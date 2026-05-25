@extends('layouts.admin')

@section('title', 'Chi tiết loại phòng')

@section('content')

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('room-categories.index') }}">
                    Admin
                </a>

                / Chi tiết loại phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Chi tiết loại phòng</h2>
                    <p>Thông tin chi tiết hạng phòng khách sạn</p>
                </div>

                <a href="{{ route('room-categories.edit', $roomCategory->id) }}" class="btn btn-gold">
                    <i class="bx bx-edit me-1"></i>
                    Chỉnh sửa
                </a>

            </div>

            <div class="settings-section">

                <div class="row">

                    <div class="col-lg-5">

                        @if($roomCategory->thumbnail)

                                        <img src="{{ asset('storage/' . $roomCategory->thumbnail) }}" class="w-100" style="
                                height: 320px;
                                object-fit: cover;
                                border-radius: 14px;
                            ">

                        @elseif($roomCategory->images->count())

                                        <img src="{{ asset('storage/' . $roomCategory->images->first()->image) }}" class="w-100" style="
                                height: 320px;
                                object-fit: cover;
                                border-radius: 14px;
                            ">

                        @else

                                        <div class="d-flex align-items-center justify-content-center bg-light" style="
                                height: 320px;
                                border-radius: 14px;
                            ">

                                            <span class="text-muted">
                                                Chưa có ảnh
                                            </span>

                                        </div>

                        @endif

                    </div>

                    <div class="col-lg-7">

                        <div class="row">

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Tên loại phòng
                                </label>

                                <h5>
                                    {{ $roomCategory->name }}
                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Giá phòng
                                </label>

                                <h5>
                                    {{ number_format($roomCategory->price, 0, ',', '.') }}đ
                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Người lớn tối đa
                                </label>

                                <h5>
                                    {{ $roomCategory->adult_capacity }} người
                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Trẻ em tối đa
                                </label>

                                <h5>
                                    {{ $roomCategory->child_capacity }} trẻ em
                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Diện tích
                                </label>

                                <h5>
                                    {{ $roomCategory->area }} m²
                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Số giường
                                </label>

                                <h5>
                                    {{ $roomCategory->bed_count }} giường
                                </h5>

                            </div>

                            <div class="col-md-6 mb-4">

                                <label class="text-muted small">
                                    Trạng thái
                                </label>

                                <div>

                                    @if($roomCategory->status == 'active')

                                        <span class="badge bg-success">
                                            Đang hoạt động
                                        </span>

                                    @else

                                        <span class="badge bg-secondary">
                                            Tạm ẩn
                                        </span>

                                    @endif

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <hr class="my-4">

                <div>

                    <label class="text-muted small mb-2">
                        Mô tả loại phòng
                    </label>

                    <div class="lh-lg">

                        {{ $roomCategory->description ?: 'Chưa có mô tả' }}

                    </div>

                </div>

                @if($roomCategory->images->count())

                    <hr class="my-4">

                    <div>

                        <h5 class="mb-3">
                            Album ảnh
                        </h5>

                        <div class="row g-3">

                            @foreach($roomCategory->images as $image)

                                <div class="col-md-3">

                                    <img src="{{ asset('storage/' . $image->image) }}" class="w-100" style="
                                                    height: 180px;
                                                    object-fit: cover;
                                                    border-radius: 12px;
                                                ">

                                </div>

                            @endforeach

                        </div>

                    </div>

                @endif

                <div class="mt-4 d-flex gap-2">

                    <a href="{{ route('room-categories.edit', $roomCategory->id) }}" class="btn btn-primary">
                        Chỉnh sửa
                    </a>

                    <a href="{{ route('room-categories.index') }}" class="btn btn-outline-secondary">
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