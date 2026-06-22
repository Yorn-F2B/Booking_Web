@extends('layouts.admin')

@section('title', 'Sửa thông tin khách hàng')

@section('content')
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.customers.index') }}">Khách hàng</a> /
                Sửa thông tin
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Sửa khách hàng #{{ $customer->id }}</h2>
                    <p>Cập nhật thông tin khách hàng</p>
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

            <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="settings-section mb-4">
                    <h5 class="mb-4">Thông tin cơ bản</h5>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Tên <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $customer->first_name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Họ đệm <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $customer->last_name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CCCD/Passport</label>
                            <input type="text" name="cccd" class="form-control" value="{{ old('cccd', $customer->cccd) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ngày sinh</label>
                            <input type="date" name="birthday" class="form-control" value="{{ old('birthday', $customer->birthday) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Giới tính</label>
                            <select name="gender" class="form-select">
                                <option value="">-- Chọn giới tính --</option>
                                <option value="male" {{ old('gender', $customer->gender) === 'male' ? 'selected' : '' }}>Nam</option>
                                <option value="female" {{ old('gender', $customer->gender) === 'female' ? 'selected' : '' }}>Nữ</option>
                                <option value="other" {{ old('gender', $customer->gender) === 'other' ? 'selected' : '' }}>Khác</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Hoạt động</option>
                                <option value="blacklist" {{ old('status', $customer->status) === 'blacklist' ? 'selected' : '' }}>Danh sách đen</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Địa chỉ</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $customer->address) }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="note" class="form-control" rows="3">{{ old('note', $customer->note) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Cập nhật khách hàng</button>
                    </div>
                </div>

            </form>

        </main>
    </div>
@endsection
