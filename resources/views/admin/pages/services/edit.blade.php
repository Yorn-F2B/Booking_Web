@extends('layouts.admin')

@section('title', 'Sửa dịch vụ')

@section('content')

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('services.index') }}">Admin</a> / Sửa dịch vụ
        </p>

        <div class="admin-page-head">

            <div>
                <h2>Sửa dịch vụ</h2>
                <p>Cập nhật thông tin dịch vụ</p>
            </div>

        </div>

        <form action="{{ route('services.update', $service->id) }}" method="POST">

            @csrf
            @method('PUT')

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

                            <label class="form-label">Tên dịch vụ</label>

                            <input type="text"
                                name="name"
                                class="form-control"
                                value="{{ old('name', $service->name) }}">

                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror

                        </div>

                    </div>

                    <div class="col-lg-6">

                        <div class="mb-3">

                            <label class="form-label">Loại</label>

                            <select name="type" class="form-select">

                                <option value="service"
                                    {{ old('type', $service->type) == 'service' ? 'selected' : '' }}>
                                    Dịch vụ
                                </option>

                                <option value="minibar"
                                    {{ old('type', $service->type) == 'minibar' ? 'selected' : '' }}>
                                    Minibar
                                </option>

                                <option value="damage_fee"
                                    {{ old('type', $service->type) == 'damage_fee' ? 'selected' : '' }}>
                                    Phí hư hại
                                </option>

                            </select>

                            @error('type')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror

                        </div>

                    </div>

                </div>

                <div class="row">

                    <div class="col-lg-6">

                        <div class="mb-3">

                            <label class="form-label">Giá</label>

                            <input type="number"
                                name="price"
                                class="form-control"
                                value="{{ old('price', $service->price) }}">

                            @error('price')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror

                        </div>

                    </div>

                    <div class="col-lg-6">

                        <div class="mb-3">

                            <label class="form-label">Đơn vị</label>

                            <input type="text"
                                name="unit"
                                class="form-control"
                                value="{{ old('unit', $service->unit) }}">

                            @error('unit')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror

                        </div>

                    </div>

                </div>

                <div class="mb-3">

                    <label class="form-label">Trạng thái</label>

                    <select name="status" class="form-select">

                        <option value="active"
                            {{ old('status', $service->status) == 'active' ? 'selected' : '' }}>
                            Hoạt động
                        </option>

                        <option value="inactive"
                            {{ old('status', $service->status) == 'inactive' ? 'selected' : '' }}>
                            Tạm ẩn
                        </option>

                    </select>

                    @error('status')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                </div>

                <div class="mb-4">

                    <label class="form-label">Mô tả</label>

                    <textarea name="description"
                        rows="5"
                        class="form-control">{{ old('description', $service->description) }}</textarea>

                    @error('description')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                </div>

                <div class="d-flex gap-2">

                    <button type="submit" class="btn btn-gold">
                        Cập nhật dịch vụ
                    </button>

                    <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">
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