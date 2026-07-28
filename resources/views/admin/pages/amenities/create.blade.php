@extends('layouts.admin')

@section('title', 'Thêm tiện ích')

@section('content')

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('amenities.index') }}">Admin</a> / Thêm tiện ích
        </p>

        <div class="admin-page-head">

            <div>
                <h2>Thêm tiện ích</h2>
                <p>Thêm tiện ích mới cho hạng phòng</p>
            </div>

        </div>

        <form action="{{ route('amenities.store') }}" method="POST">

            @csrf

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

                <div class="mb-3">

                    <label class="form-label">Tên tiện ích</label>

                    <input type="text"
                        name="name"
                        class="form-control"
                        value="{{ old('name') }}">

                    @error('name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                </div>

                <div class="mb-4">

                    <label class="form-label">Icon</label>

                    <input type="text"
                        name="icon"
                        class="form-control"
                        value="{{ old('icon') }}"
                        placeholder="Ví dụ: bx bx-wifi, bx bx-swim, bx bx-tv">

                    <small class="text-muted">
                        Dùng class icon của Boxicons, ví dụ: bx bx-wifi
                    </small>

                    @error('icon')
                        <small class="text-danger d-block">{{ $message }}</small>
                    @enderror

                </div>

                <div class="d-flex gap-2">

                    <button type="submit" class="btn btn-gold">
                        Lưu tiện ích
                    </button>

                    <a href="{{ route('amenities.index') }}" class="btn btn-outline-secondary">
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