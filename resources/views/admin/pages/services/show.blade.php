@extends('layouts.admin')

@section('title', 'Chi tiết dịch vụ')

@section('content')

<div class="admin-wrapper">

    <main class="admin-content">

        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('services.index') }}">Admin</a> / Chi tiết dịch vụ
        </p>

        <div class="admin-page-head">

            <div>
                <h2>Chi tiết dịch vụ</h2>
                <p>Thông tin chi tiết dịch vụ</p>
            </div>

            <a href="{{ route('services.edit', $service->id) }}" class="btn btn-gold">
                <i class="bx bx-edit me-1"></i>
                Chỉnh sửa
            </a>

        </div>

        <div class="settings-section">

            <div class="row">

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Tên dịch vụ</label>

                    <h5>{{ $service->name }}</h5>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Loại</label>

                    <div>
                        <span class="badge {{ $service->type === 'service' ? 'bg-primary' : ($service->type === 'minibar_order' ? 'bg-info text-dark' : 'bg-warning text-dark') }}">
                            {{ $service->type_label }}
                        </span>
                    </div>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Giá</label>

                    <h5>{{ number_format($service->price, 0, ',', '.') }}đ</h5>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Đơn vị</label>

                    <h5>{{ $service->unit }}</h5>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Cách tính khi lịch lưu trú thay đổi</label>

                    <h5>{{ $service->billing_rule_label }}</h5>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Trạng thái</label>

                    <div>
                        @if ($service->status == 'active')
                            <span class="badge bg-success">Hoạt động</span>
                        @else
                            <span class="badge bg-secondary">Tạm ẩn</span>
                        @endif
                    </div>

                </div>

                <div class="col-md-6 mb-4">

                    <label class="text-muted small">Ngày tạo</label>

                    <h5>{{ $service->created_at?->format('d/m/Y H:i') }}</h5>

                </div>

            </div>

            <hr class="my-4">

            <div>

                <label class="text-muted small mb-2">Mô tả</label>

                <div class="lh-lg">
                    {{ $service->description ?: 'Chưa có mô tả' }}
                </div>

            </div>

            <div class="mt-4 d-flex gap-2">

                <a href="{{ route('services.edit', $service->id) }}" class="btn btn-primary">
                    Chỉnh sửa
                </a>

                <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">
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