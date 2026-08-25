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

                                        <input name="last_name" type="text" class="form-control" value="{{ old('last_name') }}" autocomplete="family-name" required />

                                    </div>

                                    {{-- TÊN --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Tên
                                        </label>

                                        <input name="first_name" type="text" class="form-control" value="{{ old('first_name') }}" autocomplete="given-name" required />

                                    </div>

                                    <div class="col-12">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                <button type="button" id="registerCccdButton" class="btn btn-outline-primary" onclick="document.getElementById('registerCccdImage').click()"><i class="bx bx-image-add me-1"></i> Quét CCCD từ ảnh</button>
                                                <input type="file" id="registerCccdImage" class="d-none js-cccd-image" accept="image/*"
                                                    data-button="#registerCccdButton" data-status="#registerCccdStatus"
                                                    data-target-cccd="input[name='cccd']" data-target-first-name="input[name='first_name']"
                                                    data-target-last-name="input[name='last_name']" data-target-birthday="#reg_birthday"
                                                    data-target-gender="select[name='gender']" data-target-address="textarea[name='address']"
                                                    data-required-fields="cccd,full_name,birthday,gender,address">
                                                <small id="registerCccdStatus" class="text-muted"></small>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- CCCD --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Số CCCD
                                        </label>

                                        <input name="cccd" type="text" class="form-control" maxlength="12" inputmode="numeric"
                                            pattern="[0-9]{12}" value="{{ old('cccd') }}" autocomplete="off" required />

                                    </div>

                                    {{-- PHONE --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Số điện thoại
                                        </label>

                                        <input name="phone" type="tel" class="form-control" pattern="0[0-9]{9}" value="{{ old('phone') }}" autocomplete="tel" required />

                                    </div>

                                    {{-- EMAIL --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Email
                                        </label>

                                        <input name="email" type="email" class="form-control" value="{{ old('email') }}" autocomplete="email" required />

                                    </div>

                                    {{-- NGÀY SINH --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Ngày sinh
                                        </label>

                                        @php
                                            $bdVal = old('birthday', '');
                                        @endphp
                                        <input type="date" name="birthday" id="reg_birthday"
                                            class="form-control" value="{{ $bdVal }}"
                                            min="1900-01-01" max="{{ now('Asia/Ho_Chi_Minh')->toDateString() }}"
                                            data-birth-date autocomplete="bday">

                                    </div>

                                    {{-- GIỚI TÍNH --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Giới tính
                                        </label>

                                        <select name="gender" class="form-select" autocomplete="sex">

                                            <option value="male" @selected(old('gender', 'male') === 'male')>
                                                Nam
                                            </option>

                                            <option value="female" @selected(old('gender') === 'female')>
                                                Nữ
                                            </option>

                                            <option value="other" @selected(old('gender') === 'other')>
                                                Khác
                                            </option>

                                        </select>

                                    </div>

                                    {{-- ĐỊA CHỈ --}}
                                    <div class="col-12">
                                        <label class="form-label">Địa chỉ liên hệ</label>
                                        <textarea name="address" class="form-control" rows="2" autocomplete="street-address">{{ old('address') }}</textarea>
                                    </div>

                                    {{-- PASSWORD --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Mật khẩu
                                        </label>

                                        <input name="password" type="password" class="form-control" autocomplete="new-password" required />

                                    </div>

                                    {{-- CONFIRM PASSWORD --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Xác nhận mật khẩu
                                        </label>

                                        <input name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required />

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
    <style>
        .bd-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.18);
        }
    </style>
@include('partials.cccd-scanner-script')
@endsection
