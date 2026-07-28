@extends('layouts.admin')
@section('title', 'Thêm từ cấm')
@section('content')
<div class="container-fluid py-4"><div class="row justify-content-center"><div class="col-lg-8">
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<h3>Thêm từ cấm</h3><p class="text-muted">Có thể nhập nhiều từ, mỗi từ một dòng hoặc ngăn cách bằng dấu phẩy.</p>
<form method="POST" action="{{ route('admin.banned-words.store') }}">@csrf
<textarea name="words" rows="10" class="form-control @error('words') is-invalid @enderror" placeholder="Ví dụ:&#10;từ thứ nhất&#10;từ thứ hai">{{ old('words') }}</textarea>
@error('words')<div class="invalid-feedback">{{ $message }}</div>@enderror
<div class="d-flex gap-2 mt-3"><button class="btn btn-primary">Lưu danh sách</button><a class="btn btn-light" href="{{ route('admin.banned-words.index') }}">Quay lại</a></div>
</form></div></div></div></div></div>
@endsection
