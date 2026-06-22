@extends('layouts.admin')

@section('title', 'Thêm khách hàng mới')

@section('content')
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.customers.index') }}">Khách hàng</a> /
                Thêm mới
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Thêm khách hàng</h2>
                    <p>Nhập thông tin khách hàng mới</p>
                </div>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Quay lại
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.customers.store') }}" method="POST">
                @csrf

                <div class="settings-section mb-4">
                    <h5 class="mb-4">Thông tin cơ bản</h5>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Tên <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Họ đệm <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CCCD/Passport</label>
                            <input type="text" name="cccd" class="form-control" value="{{ old('cccd') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ngày sinh</label>
                            <input type="date" name="birthday" class="form-control" value="{{ old('birthday') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Giới tính</label>
                            <select name="gender" class="form-select">
                                <option value="">-- Chọn giới tính --</option>
                                <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>Nam</option>
                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Nữ</option>
                                <option value="other" {{ old('gender') === 'other' ? 'selected' : '' }}>Khác</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Hoạt động</option>
                                <option value="blacklist" {{ old('status') === 'blacklist' ? 'selected' : '' }}>Danh sách đen</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Địa chỉ</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Lưu khách hàng</button>
                    </div>
                </div>

            </form>

        </main>
    </div>
@endsection
