@extends('layouts.admin')

@section('title', 'Chi tiết tiện ích')

@section('content')

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('amenities.index') }}">Admin</a> / Chi tiết tiện ích
        </p>

        <div class="admin-page-head">

            <div>
                <h2>Chi tiết tiện ích</h2>
                <p>Thông tin chi tiết tiện ích</p>
            </div>

            <a href="{{ route('amenities.edit', $amenity->id) }}" class="btn btn-gold">
                <i class="bx bx-edit me-1"></i>
                Chỉnh sửa
            </a>

        </div>

        <div class="settings-section">

            <div class="row">

                <div class="col-md-4 mb-4">

                    <label class="text-muted small">ID</label>

                    <h5>{{ $amenity->id }}</h5>

                </div>

                <div class="col-md-4 mb-4">

                    <label class="text-muted small">Tên tiện ích</label>

                    <h5>{{ $amenity->name }}</h5>

                </div>

                <div class="col-md-4 mb-4">

                    <label class="text-muted small">Icon</label>

                    <div>
                        @if ($amenity->icon)

                            <i class="{{ $amenity->icon }}" style="font-size: 32px;"></i>

                            <div class="text-muted small mt-2">
                                {{ $amenity->icon }}
                            </div>

                        @else

                            <span class="text-muted">Chưa có icon</span>

                        @endif
                    </div>

                </div>

                <div class="col-md-4 mb-4">

                    <label class="text-muted small">Ngày tạo</label>

                    <h5>{{ $amenity->created_at?->format('d/m/Y H:i') }}</h5>

                </div>

            </div>

            <div class="mt-4 d-flex gap-2">

                <a href="{{ route('amenities.edit', $amenity->id) }}" class="btn btn-primary">
                    Chỉnh sửa
                </a>

                <a href="{{ route('amenities.index') }}" class="btn btn-outline-secondary">
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