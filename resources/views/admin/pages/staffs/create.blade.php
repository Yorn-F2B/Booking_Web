@extends('layouts.admin')

@section('title', 'Thêm nhân viên')

@section('content')
    <div class="admin-wrapper">
        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('staffs.index') }}">Admin</a> /
                <a href="{{ route('staffs.index') }}">Nhân viên</a> /
                Thêm mới
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Thêm nhân viên</h2>
                    <p>Tạo tài khoản đăng nhập và thông tin nhân viên</p>
                </div>

                <a href="{{ route('staffs.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Quay lại
                </a>
            </div>

            <form class="settings-section" action="{{ route('staffs.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                @csrf

                <h5 class="mb-3">Thông tin tài khoản</h5>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email đăng nhập <span
                                class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}" required autocomplete="off">

                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                            required autocomplete="new-password">

                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <h5 class="mb-3">Thông tin nhân viên</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                            value="{{ old('full_name') }}" required>

                        @error('full_name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Số điện thoại</label>
                        <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone') }}">

                        @error('phone')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">CCCD</label>
                        <input type="text" name="cccd" class="form-control @error('cccd') is-invalid @enderror"
                            value="{{ old('cccd') }}">

                        @error('cccd')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Ngày sinh</label>
                        <input type="date" name="birthday" class="form-control" value="{{ old('birthday') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Giới tính</label>
                        <select name="gender" class="form-select">
                            <option value="">— Chọn —</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Nam</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Nữ</option>
                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Khác</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Ngày vào làm</label>
                        <input type="date" name="hire_date" class="form-control" value="{{ old('hire_date') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Trạng thái</label>
                        <select name="work_status" class="form-select">
                            <option value="working" {{ old('work_status') == 'working' ? 'selected' : '' }}>Đang làm việc
                            </option>
                            <option value="temporary_leave" {{ old('work_status') == 'temporary_leave' ? 'selected' : '' }}>
                                Nghỉ tạm</option>
                            <option value="resigned" {{ old('work_status') == 'resigned' ? 'selected' : '' }}>Đã nghỉ</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Chức vụ</label>
                        <select name="position" class="form-select">
                            <option value="">— Chọn chức vụ —</option>
                            <option value="Quản lý" {{ old('position') == 'Quản lý' ? 'selected' : '' }}>Quản lý</option>
                            <option value="Lễ tân" {{ old('position') == 'Lễ tân' ? 'selected' : '' }}>Lễ tân</option>
                            <option value="Housekeeping" {{ old('position') == 'Housekeeping' ? 'selected' : '' }}>
                                Housekeeping</option>
                            <option value="Kế toán" {{ old('position') == 'Kế toán' ? 'selected' : '' }}>Kế toán</option>
                            <option value="Bảo vệ" {{ old('position') == 'Bảo vệ' ? 'selected' : '' }}>Bảo vệ</option>
                            <option value="Kỹ thuật" {{ old('position') == 'Kỹ thuật' ? 'selected' : '' }}>Kỹ thuật</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Lương</label>
                        <input type="number" name="salary" class="form-control" min="0" step="1000"
                            value="{{ old('salary', 0) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Ảnh đại diện</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('staffs.index') }}" class="btn btn-outline-secondary">Hủy</a>
                    <button type="submit" class="btn btn-gold">
                        <i class="bx bx-save me-1"></i>Lưu
                    </button>
                </div>
            </form>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>
@endsection