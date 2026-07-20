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

                                        <input name="last_name" type="text" class="form-control" required />

                                    </div>

                                    {{-- TÊN --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Tên
                                        </label>

                                        <input name="first_name" type="text" class="form-control" required />

                                    </div>

                                    <div class="col-12">
                                        <div class="border rounded p-3 bg-light">
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                <button type="button" id="registerCccdButton" class="btn btn-outline-primary" onclick="document.getElementById('registerCccdImage').click()">Quét ảnh CCCD</button>
                                                <input type="file" id="registerCccdImage" class="d-none js-cccd-image" accept="image/*" capture="environment"
                                                    data-button="#registerCccdButton" data-status="#registerCccdStatus"
                                                    data-target-cccd="input[name='cccd']" data-target-first-name="input[name='first_name']"
                                                    data-target-last-name="input[name='last_name']" data-target-birthday="#reg_birthday_hidden"
                                                    data-target-gender="select[name='gender']" data-target-address="textarea[name='address']">
                                                <small id="registerCccdStatus" class="text-muted">Chụp hoặc chọn ảnh mặt trước CCCD để tự động điền.</small>
                                            </div>
                                        </div>
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

                                        @php
                                            $bdVal = old('birthday', '');
                                            $bdDay   = $bdVal ? (int)\Carbon\Carbon::parse($bdVal)->format('d') : '';
                                            $bdMonth = $bdVal ? (int)\Carbon\Carbon::parse($bdVal)->format('m') : '';
                                            $bdYear  = $bdVal ? (int)\Carbon\Carbon::parse($bdVal)->format('Y') : '';
                                        @endphp
                                        <input type="hidden" name="birthday" id="reg_birthday_hidden" value="{{ $bdVal }}">
                                        <div class="birthday-dropdowns" id="reg_birthday_dropdowns">
                                            <select class="bd-select" id="reg_bd_day">
                                                <option value="">Ngày</option>
                                                @for ($d = 1; $d <= 31; $d++)
                                                    <option value="{{ $d }}" {{ $bdDay == $d ? 'selected' : '' }}>{{ $d }}</option>
                                                @endfor
                                            </select>
                                            <select class="bd-select" id="reg_bd_month">
                                                <option value="">Tháng</option>
                                                @foreach(['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'] as $mi => $ml)
                                                    <option value="{{ $mi+1 }}" {{ $bdMonth == $mi+1 ? 'selected' : '' }}>{{ $ml }}</option>
                                                @endforeach
                                            </select>
                                            <select class="bd-select" id="reg_bd_year">
                                                <option value="">Năm</option>
                                                @for ($y = date('Y'); $y >= date('Y')-120; $y--)
                                                    <option value="{{ $y }}" {{ $bdYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                                @endfor
                                            </select>
                                        </div>

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
    <style>
        .birthday-dropdowns {
            display: flex;
            gap: 8px;
        }
        .bd-select {
            flex: 1;
            height: 42px;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            padding: 0 10px;
            background: #fff;
            font-size: 15px;
            color: #1f2937;
            appearance: auto;
            cursor: pointer;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .bd-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.18);
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function syncBirthday(dayId, monthId, yearId, hiddenId) {
                var d = document.getElementById(dayId);
                var m = document.getElementById(monthId);
                var y = document.getElementById(yearId);
                var h = document.getElementById(hiddenId);
                if (!d || !m || !y || !h) return;
                function update() {
                    if (d.value && m.value && y.value) {
                        var dd = String(d.value).padStart(2,'0');
                        var mm = String(m.value).padStart(2,'0');
                        h.value = y.value + '-' + mm + '-' + dd;
                    } else {
                        h.value = '';
                    }
                }
                d.addEventListener('change', update);
                m.addEventListener('change', update);
                y.addEventListener('change', update);

                // Reverse sync from hidden to dropdowns when changed programmatically
                h.addEventListener('change', function() {
                    if (h.value) {
                        var parts = h.value.split('-');
                        if (parts.length === 3) {
                            y.value = parseInt(parts[0], 10) || '';
                            m.value = parseInt(parts[1], 10) || '';
                            d.value = parseInt(parts[2], 10) || '';
                        }
                    } else {
                        y.value = ''; m.value = ''; d.value = '';
                    }
                });
            }
            syncBirthday('reg_bd_day','reg_bd_month','reg_bd_year','reg_birthday_hidden');
        });
    </script>
@include('partials.cccd-scanner-script')
@endsection
