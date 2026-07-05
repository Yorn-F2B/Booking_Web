@extends('layouts.admin')

@section('title', 'Sửa phòng')

@section('content')

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.rooms.index') }}">Admin</a> / Sửa phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Sửa phòng</h2>
                    <p>Cập nhật thông tin phòng trong khách sạn</p>
                </div>

            </div>

            <form action="{{ route('admin.rooms.update', $room->id) }}" method="POST">

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

                                <label class="form-label">
                                    Số phòng
                                </label>

                                <input type="text" name="room_number" class="form-control"
                                    value="{{ old('room_number', $room->room_number) }}">

                                @error('room_number')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Loại phòng
                                </label>

                                <select name="room_category_id" class="form-select">

                                    <option value="">
                                        -- Chọn loại phòng --
                                    </option>

                                    @foreach ($categories as $category)

                                        <option value="{{ $category->id }}"
                                            {{ old('room_category_id', $room->room_category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>

                                    @endforeach

                                </select>

                                @error('room_category_id')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                    </div>

                    <div class="row">

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Tầng
                                </label>

                                <input type="number" name="floor_number" class="form-control"
                                    value="{{ old('floor_number', $room->floor_number) }}">

                                @error('floor_number')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                        <div class="col-lg-6">

                            <div class="mb-3">

                                <label class="form-label">
                                    Trạng thái
                                </label>

                                <select name="status" class="form-select">

                                    <option value="available"
                                        {{ old('status', $room->status) == 'available' ? 'selected' : '' }}>
                                        Còn trống
                                    </option>

                                    <option value="reserved"
                                        {{ old('status', $room->status) == 'reserved' ? 'selected' : '' }}>
                                        Đã đặt trước
                                    </option>

                                    <option value="occupied"
                                        {{ old('status', $room->status) == 'occupied' ? 'selected' : '' }}>
                                        Đang có khách
                                    </option>

                                    <option value="cleaning"
                                        {{ old('status', $room->status) == 'cleaning' ? 'selected' : '' }}>
                                        Đang dọn phòng
                                    </option>

                                    <option value="maintenance"
                                        {{ old('status', $room->status) == 'maintenance' ? 'selected' : '' }}>
                                        Bảo trì
                                    </option>

                                </select>

                                @error('status')
                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>
                                @enderror

                            </div>

                        </div>

                    </div>

                    <div class="mb-4">

                        <label class="form-label">
                            Ghi chú
                        </label>

                        <textarea name="note" rows="5"
                            class="form-control">{{ old('note', $room->note) }}</textarea>

                        @error('note')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>

                    <div class="d-flex gap-2">

                        <button type="submit" class="btn btn-gold">
                            Cập nhật phòng
                        </button>

                        <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline-secondary">
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