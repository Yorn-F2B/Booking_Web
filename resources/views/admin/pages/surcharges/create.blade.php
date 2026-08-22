@extends('layouts.admin')
@section('title', 'Thêm phụ thu / phí phát sinh')
@section('content')
<div class="admin-wrapper"><main class="admin-content">
    <p class="admin-breadcrumb mb-3"><a href="{{ route('surcharges.index') }}">Phụ thu / phí phát sinh</a> / Thêm mới</p>
    <div class="admin-page-head"><div><h2>Thêm phụ thu / phí phát sinh</h2><p>Tạo danh mục phí dùng cho hư hại, chính sách hoặc khoản phát sinh thủ công.</p></div><a href="{{ route('surcharges.index') }}" class="btn btn-outline-secondary">Quay lại</a></div>
    <form action="{{ route('surcharges.store') }}" method="POST">@csrf @include('admin.pages.surcharges._form')</form>
</main><footer class="admin-footer"><span>MCuong Hotel Admin</span></footer></div>
@endsection
