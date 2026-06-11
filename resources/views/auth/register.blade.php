@extends('layouts.user')

@section('title', 'Register')

@section('content')

    <section class="page-header">
        <div class="container">

            <h1 class="display-6 fw-bold mb-1">
                Đăng ký tài khoản khách hàng
            </h1>

            <p class="text-muted mb-0">
                Nhập đầy đủ thông tin để đặt phòng
                và quản lý hồ sơ nhanh hơn.
            </p>

        </div>
    </section>

    <main class="py-5">

        <div class="container">

            <div class="row justify-content-center">

                <div class="col-lg-8">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body p-4">

                            <form method="POST" action="{{ route('register') }}" id="registerForm">

                                @csrf

                                @if ($errors->any())
                                    <div class="alert alert-danger mb-3">
                                        <strong>Có lỗi xảy ra:</strong>
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="row g-3">

                                    {{-- HỌ --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Họ
                                        </label>

                                        <input name="first_name" type="text" class="form-control" required />

                                    </div>

                                    {{-- TÊN --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Tên
                                        </label>

                                        <input name="last_name" type="text" class="form-control" required />

                                    </div>

                                    {{-- CCCD --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Số CCCD
                                        </label>

                                        <input name="cccd" type="text" class="form-control" maxlength="12"
                                            pattern="[0-9]{12}" required />

                                    </div>

                                    {{-- PHONE --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Số điện thoại
                                        </label>

                                        <input name="phone" type="tel" class="form-control" pattern="0[0-9]{9}" required />

                                    </div>

                                    {{-- EMAIL --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Email
                                        </label>

                                        <input name="email" type="email" class="form-control" required />

                                    </div>

                                    {{-- NGÀY SINH --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Ngày sinh
                                        </label>

                                        <input name="birthday" type="date" class="form-control" />

                                    </div>

                                    {{-- GIỚI TÍNH --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Giới tính
                                        </label>

                                        <select name="gender" class="form-select">

                                            <option value="male">
                                                Nam
                                            </option>

                                            <option value="female">
                                                Nữ
                                            </option>

                                            <option value="other">
                                                Khác
                                            </option>

                                        </select>

                                    </div>

                                    {{-- PASSWORD --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Mật khẩu
                                        </label>

                                        <input name="password" type="password" class="form-control" required />

                                    </div>

                                    {{-- CONFIRM PASSWORD --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Xác nhận mật khẩu
                                        </label>

                                        <input name="password_confirmation" type="password" class="form-control" required />

                                    </div>

                                    {{-- ADDRESS --}}
                                    <div class="col-12">

                                        <label class="form-label">
                                            Địa chỉ liên hệ
                                        </label>

                                        <textarea name="address" class="form-control" rows="2"></textarea>

                                    </div>

                                </div>

                                {{-- POLICY --}}
                                <div class="form-check mt-3">

                                    <input class="form-check-input" type="checkbox" id="policyCheck" required />

                                    <label class="form-check-label small" for="policyCheck">

                                        Tôi đồng ý với điều khoản sử dụng
                                        và chính sách bảo mật.

                                    </label>

                                </div>

                                {{-- BUTTON --}}
                                <div class="d-flex gap-2 mt-3">

                                    <button type="submit" class="btn btn-primary">

                                        Tạo tài khoản

                                    </button>

                                    <a href="{{ route('login') }}" class="btn btn-outline-primary">

                                        Đã có tài khoản

                                    </a>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>

@endsection