@extends('layouts.admin')

@section('title', 'Chỉnh sửa khách hàng')

@section('content')
    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / 
                <a href="{{ route('admin.customers.index') }}">Khách hàng</a> / 
                <a href="{{ route('admin.customers.show', $customer) }}">{{ $customer->first_name }} {{ $customer->last_name }}</a> / 
                Chỉnh sửa
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Chỉnh sửa khách hàng</h2>
                    <p>Cập nhật thông tin khách hàng</p>
                </div>

                <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-outline-secondary">
                    Quay lại
                </a>

            </div>

            <div class="bg-white p-4 rounded-3 border">
                <form method="POST" action="{{ route('admin.customers.update', $customer) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Họ <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="first_name" 
                                   class="form-control @error('first_name') is-invalid @enderror" 
                                   value="{{ old('first_name', $customer->first_name) }}" 
                                   required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tên <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="last_name" 
                                   class="form-control @error('last_name') is-invalid @enderror" 
                                   value="{{ old('last_name', $customer->last_name) }}" 
                                   required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" 
                                   name="phone" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   value="{{ old('phone', $customer->phone) }}" 
                                   required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" 
                                   name="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email', $customer->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CCCD</label>
                            <input type="text" 
                                   name="cccd" 
                                   class="form-control @error('cccd') is-invalid @enderror" 
                                   value="{{ old('cccd', $customer->cccd) }}">
                            @error('cccd')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày sinh</label>
                            <input type="date" 
                                   name="birthday" 
                                   class="form-control @error('birthday') is-invalid @enderror" 
                                   value="{{ old('birthday', $customer->birthday) }}">
                            @error('birthday')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giới tính</label>
                            <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                <option value="">Chưa chọn</option>
                                <option value="male" {{ old('gender', $customer->gender) === 'male' ? 'selected' : '' }}>Nam</option>
                                <option value="female" {{ old('gender', $customer->gender) === 'female' ? 'selected' : '' }}>Nữ</option>
                                <option value="other" {{ old('gender', $customer->gender) === 'other' ? 'selected' : '' }}>Khác</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Hoạt động</option>
                                <option value="blacklist" {{ old('status', $customer->status) === 'blacklist' ? 'selected' : '' }}>Blacklist</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Địa chỉ</label>
                            <textarea name="address" 
                                      class="form-control @error('address') is-invalid @enderror" 
                                      rows="2">{{ old('address', $customer->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="note" 
                                      class="form-control @error('note') is-invalid @enderror" 
                                      rows="3">{{ old('note', $customer->note) }}</textarea>
                            @error('note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                Lưu thay đổi
                            </button>
                            <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-outline-secondary">
                                Hủy
                            </a>
                        </div>
                    </div>
                </form>
            </div>

        </main>

    </div>
@endsection
