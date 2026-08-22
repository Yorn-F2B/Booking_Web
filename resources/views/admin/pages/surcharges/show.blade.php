@extends('layouts.admin')
@section('title', 'Chi tiết phụ thu / phí phát sinh')
@section('content')
<div class="admin-wrapper"><main class="admin-content">
    <p class="admin-breadcrumb mb-3"><a href="{{ route('surcharges.index') }}">Phụ thu / phí phát sinh</a> / Chi tiết</p>
    <div class="admin-page-head"><div><h2>{{ $surcharge->name }}</h2><p>Thông tin danh mục phụ thu / phí phát sinh.</p></div><a href="{{ route('surcharges.edit', $surcharge) }}" class="btn btn-gold"><i class="bx bx-edit me-1"></i>Chỉnh sửa</a></div>
    <div class="settings-section">
        <div class="row g-4">
            <div class="col-md-6"><div class="text-muted small">Loại phí</div><strong>{{ $surcharge->type_label }}</strong></div>
            <div class="col-md-6"><div class="text-muted small">Mức mặc định</div><strong>{{ number_format((float)$surcharge->price,0,',','.') }}đ / {{ $surcharge->unit }}</strong></div>
            <div class="col-md-6"><div class="text-muted small">Trạng thái</div>@if($surcharge->status==='active')<span class="badge bg-success">Hoạt động</span>@else<span class="badge bg-secondary">Ngừng hoạt động</span>@endif</div>
            <div class="col-md-6"><div class="text-muted small">Cách tính</div><strong>Một lần / theo số lượng ghi nhận</strong></div>
            <div class="col-12"><div class="text-muted small mb-1">Mô tả</div><div>{{ $surcharge->description ?: 'Chưa có mô tả.' }}</div></div>
        </div>
        <div class="mt-4 d-flex gap-2"><a href="{{ route('surcharges.edit',$surcharge) }}" class="btn btn-primary">Chỉnh sửa</a><a href="{{ route('surcharges.index') }}" class="btn btn-outline-secondary">Quay lại</a></div>
    </div>
</main><footer class="admin-footer"><span>MCuong Hotel Admin</span></footer></div>
@endsection
