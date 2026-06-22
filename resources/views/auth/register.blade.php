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

                                        <input name="birthday" type="text" class="form-control js-birthday-picker"
                                            value="{{ old('birthday') }}" placeholder="dd/mm/yyyy" autocomplete="off" />
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        .flatpickr-calendar {
            font-family: inherit;
        }

        .flatpickr-current-month {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            left: 0;
            width: 100%;
            height: 34px;
            padding: 0;
        }

        .flatpickr-current-month .flatpickr-monthDropdown-months {
            height: 32px;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            padding: 2px 8px;
            background: #fff;
            font-size: 15px;
            font-weight: 600;
        }

        .flatpickr-current-month .numInputWrapper {
            display: none !important;
        }

        .birthday-year-select {
            height: 32px;
            min-width: 88px;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            background: #fff;
            padding: 2px 8px;
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            outline: none;
        }

        .birthday-year-select:focus,
        .flatpickr-current-month .flatpickr-monthDropdown-months:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof flatpickr === 'undefined') {
                return;
            }

            const currentYear = new Date().getFullYear();
            const minYear = currentYear - 120;
            const defaultYear = currentYear - 18;

            function addYearSelect(instance) {
                const currentMonth = instance.calendarContainer.querySelector('.flatpickr-current-month');

                if (!currentMonth) {
                    return;
                }

                let select = currentMonth.querySelector('.birthday-year-select');

                if (!select) {
                    select = document.createElement('select');
                    select.className = 'birthday-year-select';

                    for (let year = currentYear; year >= minYear; year--) {
                        const option = document.createElement('option');
                        option.value = year;
                        option.textContent = year;
                        select.appendChild(option);
                    }

                    select.addEventListener('change', function () {
                        instance.changeYear(Number(this.value));
                    });

                    currentMonth.appendChild(select);
                }

                select.value = instance.currentYear;
            }

            flatpickr('.js-birthday-picker', {
                locale: flatpickr.l10ns.vn,
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: true,
                disableMobile: true,
                monthSelectorType: 'dropdown',
                maxDate: 'today',
                minDate: `${minYear}-01-01`,
                defaultDate: null,

                onReady: function (selectedDates, dateStr, instance) {
                    if (!selectedDates.length) {
                        instance.jumpToDate(new Date(defaultYear, 0, 1));
                    }

                    addYearSelect(instance);
                },

                onOpen: function (selectedDates, dateStr, instance) {
                    if (!selectedDates.length) {
                        instance.jumpToDate(new Date(defaultYear, 0, 1));
                    }

                    addYearSelect(instance);
                },

                onMonthChange: function (selectedDates, dateStr, instance) {
                    addYearSelect(instance);
                },

                onYearChange: function (selectedDates, dateStr, instance) {
                    addYearSelect(instance);
                }
            });
        });
    </script>
@endsection