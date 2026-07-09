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

        <form action="{{ route('room-categories.update', $roomCategory->id) }}" method="POST" enctype="multipart/form-data">
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

            <div class="settings-section mb-4">
                <h5 class="mb-3">Thông tin cơ bản</h5>

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Tên loại phòng</label>
                        <input type="text" name="name" class="form-control"
                            value="{{ old('name', $roomCategory->name) }}">
                        @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Giá phòng</label>
                        <input type="number" name="price" class="form-control"
                            value="{{ old('price', $roomCategory->price) }}">
                        @error('price') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label class="form-label">Người lớn tối đa</label>
                        <input type="number" name="adult_capacity" class="form-control"
                            value="{{ old('adult_capacity', $roomCategory->adult_capacity) }}">
                        @error('adult_capacity') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label class="form-label">Trẻ em tối đa</label>
                        <input type="number" name="child_capacity" class="form-control"
                            value="{{ old('child_capacity', $roomCategory->child_capacity) }}">
                        @error('child_capacity') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label class="form-label">Số giường</label>
                        <input type="number" name="bed_count" class="form-control"
                            value="{{ old('bed_count', $roomCategory->bed_count) }}">
                        @error('bed_count') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Diện tích</label>
                        <input type="number" step="0.01" name="area" class="form-control"
                            value="{{ old('area', $roomCategory->area) }}">
                        @error('area') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ old('status', $roomCategory->status) == 'active' ? 'selected' : '' }}>
                                Đang hoạt động
                            </option>
                            <option value="inactive" {{ old('status', $roomCategory->status) == 'inactive' ? 'selected' : '' }}>
                                Tạm ẩn
                            </option>
                        </select>
                        @error('status') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <div class="settings-section mb-4">
                <h5 class="mb-3">Tiện ích</h5>

                <div class="row">
                    @forelse ($amenities as $amenity)
                        <div class="col-md-4 col-lg-3 mb-3">
                            <label class="d-flex align-items-center gap-2 p-3 border rounded h-100">
                                <input type="checkbox"
                                    name="amenities[]"
                                    value="{{ $amenity->id }}"
                                    {{ in_array($amenity->id, old('amenities', $roomCategory->amenities->pluck('id')->toArray())) ? 'checked' : '' }}>

                                @if ($amenity->icon)
                                    <i class="{{ $amenity->icon }}" style="font-size: 20px;"></i>
                                @endif

                                <span>{{ $amenity->name }}</span>
                            </label>
                        </div>
                    @empty
                        <div class="col-12">
                            <span class="text-muted">Chưa có tiện ích nào</span>
                        </div>
                    @endforelse
                </div>

                @error('amenities') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="settings-section mb-4">
                <h5 class="mb-3">Hình ảnh</h5>

                <div class="mb-3">
                    <label class="form-label">Ảnh thumbnail</label>
                    <input type="file" name="thumbnail" class="form-control" accept="image/*">

                    @if ($roomCategory->thumbnail)
                        <div class="mt-3">
                            <p class="text-muted mb-2">Ảnh hiện tại:</p>
                            <img src="{{ asset('storage/' . $roomCategory->thumbnail) }}" width="180" height="120"
                                style="object-fit: cover; border-radius: 10px;">
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Thêm ảnh album</label>
                    <input type="file" name="images[]" multiple class="form-control" accept="image/*">
                </div>

                @if ($roomCategory->images->count())
                    <div class="mb-3">
                        <label class="form-label">Album hiện tại</label>

                        <div class="row g-3">
                            @foreach ($roomCategory->images as $image)
                                <div class="col-md-3">
                                    <img src="{{ asset('storage/' . $image->image) }}" class="w-100"
                                        style="height: 160px; object-fit: cover; border-radius: 12px;">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="settings-section mb-4">
                <h5 class="mb-3">Mô tả</h5>

                <textarea name="description" rows="5"
                    class="form-control">{{ old('description', $roomCategory->description) }}</textarea>

                @error('description') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-gold">
                    Cập nhật loại phòng
                </button>

                <a href="{{ route('room-categories.index') }}" class="btn btn-outline-secondary">
                    Quay lại
                </a>
            </div>

        </form>

    </main>

    <footer class="admin-footer">
        <span>MCuong Hotel Admin</span>
    </footer>

</div>

@endsection