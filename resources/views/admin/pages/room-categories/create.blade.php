@extends('layouts.admin')

@section('title', 'Thêm loại phòng')

@section('content')

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('room-categories.index') }}">
                    Admin
                </a>

                / Thêm loại phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Thêm loại phòng</h2>
                    <p>Thêm hạng phòng mới cho khách sạn</p>
                </div>

            </div>

            <form action="{{ route('room-categories.store') }}" method="POST" enctype="multipart/form-data">

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

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Có lỗi xảy ra:</strong>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="settings-section">

                    <div class="row">

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Tên loại phòng
                                </label>

                                <input type="text" name="name" class="form-control" value="{{ old('name') }}">

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

                                <input type="number" name="price" class="form-control" value="{{ old('price') }}">

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
                                    value="{{ old('adult_capacity') }}">

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
                                    value="{{ old('child_capacity', 0) }}">

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
                                    Diện tích
                                </label>

                                <input type="number" step="0.01" name="area" class="form-control" value="{{ old('area') }}">

                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Số giường
                                </label>

                                <input type="number" name="bed_count" class="form-control"
                                    value="{{ old('bed_count', 1) }}">

                            </div>

                        </div>

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Trạng thái
                                </label>

                                <select name="status" class="form-select">

                                    <option value="active">
                                        Đang hoạt động
                                    </option>

                                    <option value="inactive">
                                        Tạm ẩn
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Ảnh đại diện
                        </label>

                        <input type="file" name="thumbnail" class="form-control" accept="image/*">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Album ảnh
                        </label>

                        <input type="file" name="images[]" multiple class="form-control" accept="image/*">

                    </div>

                    <div class="mb-4">

                        <label class="form-label">
                            Mô tả
                        </label>

                        <textarea name="description" rows="5" class="form-control">{{ old('description') }}</textarea>

                    </div>

                    <div class="d-flex gap-2">

                        <button type="submit" class="btn btn-gold">
                            Lưu loại phòng
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