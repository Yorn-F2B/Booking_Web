@extends('layouts.admin')
@section('title', 'Sửa phụ thu / phí phát sinh')
@section('content')
<div class="admin-wrapper"><main class="admin-content">
    <p class="admin-breadcrumb mb-3"><a href="{{ route('surcharges.index') }}">Phụ thu / phí phát sinh</a> / Sửa</p>
    <div class="admin-page-head"><div><h2>Sửa phụ thu / phí phát sinh</h2><p>Cập nhật danh mục phí mà không ảnh hưởng snapshot lịch sử đã ghi trên booking.</p></div><a href="{{ route('surcharges.index') }}" class="btn btn-outline-secondary">Quay lại</a></div>
    <form action="{{ route('surcharges.update', $surcharge) }}" method="POST">@csrf @method('PUT') @include('admin.pages.surcharges._form')</form>
</main><footer class="admin-footer"><span>MCuong Hotel Admin</span></footer></div>
@endsection
