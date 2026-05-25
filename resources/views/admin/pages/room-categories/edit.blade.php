@extends('layouts.admin')

@section('title', 'Sửa loại phòng')

@section('content')

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('room-categories.index') }}">Admin</a> / Sửa loại phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Sửa loại phòng</h2>
                    <p>Cập nhật thông tin hạng phòng khách sạn</p>
                </div>

            </div>

            <form action="{{ route('room-categories.update', $roomCategory->id) }}" method="POST"
                enctype="multipart/form-data">

                @csrf
                @if ($errors->any())

                    <div class="alert alert-danger">

                        <ul class="mb-0">

                            @foreach ($errors->all() as $error)

                                <li>{{ $error }}</li>

                            @endforeach

                        </ul>

                    </div>

                @endif
                @method('PUT')

                <div class="settings-section">

                    <div class="row">

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Tên loại phòng
                                </label>

                                <input type="text" name="name" class="form-control"
                                    value="{{ old('name', $roomCategory->name) }}">

                                @error('name')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Giá phòng
                                </label>

                                <input type="number" name="price" class="form-control"
                                    value="{{ old('price', $roomCategory->price) }}">

                                @error('price')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-lg-4">

                            <div class="mb-3">

                                <label class="form-label">
                                    Người lớn tối đa
                                </label>

                                <input type="number" name="adult_capacity" class="form-control"
                                    value="{{ old('adult_capacity', $roomCategory->adult_capacity) }}">

                                @error('adult_capacity')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                        <div class="col-lg-4">

                            <div class="mb-3">

                                <label class="form-label">
                                    Trẻ em tối đa
                                </label>

                                <input type="number" name="child_capacity" class="form-control"
                                    value="{{ old('child_capacity', $roomCategory->child_capacity) }}">

                                @error('child_capacity')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                        <div class="col-lg-4">

                            <div class="mb-3">

                                <label class="form-label">
                                    Số giường
                                </label>

                                <input type="number" name="bed_count" class="form-control"
                                    value="{{ old('bed_count', $roomCategory->bed_count) }}">

                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Diện tích
                                </label>

                                <input type="number" step="0.01" name="area" class="form-control"
                                    value="{{ old('area', $roomCategory->area) }}">

                            </div>

                        </div>

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Trạng thái
                                </label>

                                <select name="status" class="form-select">

                                    <option value="active" {{ $roomCategory->status == 'active' ? 'selected' : '' }}>
                                        Đang hoạt động
                                    </option>

                                    <option value="inactive" {{ $roomCategory->status == 'inactive' ? 'selected' : '' }}>
                                        Tạm ẩn
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Ảnh thumbnail
                        </label>

                        <input type="file" name="thumbnail" class="form-control" accept="image/*">

                        @if($roomCategory->thumbnail)

                            <div class="mt-3">

                                <img src="{{ asset('storage/' . $roomCategory->thumbnail) }}" width="180" height="120"
                                    style="object-fit: cover; border-radius: 10px;">

                            </div>

                        @endif

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Thêm ảnh album
                        </label>

                        <input type="file" name="images[]" multiple class="form-control" accept="image/*">

                    </div>

                    @if($roomCategory->images->count())

                        <div class="mb-4">

                            <label class="form-label">
                                Album hiện tại
                            </label>

                            <div class="row g-3">

                                @foreach($roomCategory->images as $image)

                                    <div class="col-md-3">

                                        <img src="{{ asset('storage/' . $image->image) }}" class="w-100" style="
                                                    height: 160px;
                                                    object-fit: cover;
                                                    border-radius: 12px;
                                                ">

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    @endif

                    <div class="mb-4">

                        <label class="form-label">
                            Mô tả
                        </label>

                        <textarea name="description" rows="5"
                            class="form-control">{{ old('description', $roomCategory->description) }}</textarea>

                    </div>

                    <div class="d-flex gap-2">

                        <button type="submit" class="btn btn-gold">
                            Cập nhật loại phòng
                        </button>

                        <a href="{{ route('room-categories.index') }}" class="btn btn-outline-secondary">
                            Quay lại
                        </a>

                    </div>

                </div>

            </form>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

@endsection