@extends('layouts.user')

@section('title', 'User Settings')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">Cài đặt người dùng</h1>
            <p class="text-muted mb-0">Cập nhật hồ sơ cá nhân để làm thủ tục nhận phòng nhanh hơn.</p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <div class="row g-4 align-items-start">

                <!-- Sidebar: Avatar + thông tin nhanh -->
                <div class="col-lg-3">
                    <div class="settings-section text-center">
                        <!-- Avatar upload -->
                        <div class="avatar-upload-wrap mb-3 mx-auto" style="width:fit-content">
                            <img id="avatarPreview" src="{{ Auth::user()->avatar
        ? asset('storage/' . Auth::user()->avatar)
        : 'https://images.pexels.com/photos/771742/pexels-photo-771742.jpeg'
                                                            }}" alt="avatar" class="avatar-lg" />
                            <label for="avatarInput" class="avatar-upload-overlay" title="Đổi ảnh đại diện">
                                <i class="bx bx-camera"></i>
                            </label>
                            <input type="file" id="avatarInput" form="userSettingsForm" name="avatar" accept="image/*"
                                class="d-none" />
                        </div>
                        <h2 class="h6 fw-bold mb-1" style="font-family:'DM Serif Display',serif">{{ Auth::user()->name }}
                        </h2>
                        <p class="text-muted small mb-1">{{ Auth::user()->email }}</p>
                        <hr class="my-3" />
                        <ul class="list-unstyled text-start small text-muted mb-0" style="line-height:2">
                            <li><i class="bx bx-calendar-check me-2 text-gold"></i>Thành viên từ:
                                {{ Auth::user()->created_at }}
                            </li>
                            <li><i class="bx bx-hotel me-2 text-gold"></i>Đã đặt: {{ $bookingCount }} lần</li>
                        </ul>
                    </div>
                </div>

                <!-- Main: Thông tin cá nhân + Đổi mật khẩu + Đơn phòng -->
                <div class="col-lg-9">

                    <!-- Navigation tabs -->
                    <div class="settings-tabs mb-4">
                        <ul class="nav nav-tabs border-0" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab"
                                    data-bs-target="#profile" type="button" role="tab">
                                    <i class="bx bx-user me-1"></i>Thông tin cá nhân
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password"
                                    type="button" role="tab">
                                    <i class="bx bx-lock-alt me-1"></i>Mật khẩu
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings"
                                    type="button" role="tab">
                                    <i class="bx bx-calendar me-1"></i>Đơn phòng
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tab content -->
                    <div class="tab-content" id="settingsTabsContent">

                        <!-- Tab 1: Thông tin cá nhân -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <div class="settings-section">

                                <h3 class="settings-section-title">
                                    <i class="bx bx-user"></i>
                                    Thông tin cá nhân
                                </h3>

                                @if(session('success'))
                                    <div class="alert alert-success">
                                        {{ session('success') }}
                                    </div>
                                @endif

                                <form id="userSettingsForm" method="POST" action="{{ route('user.settings.update') }}"
                                    enctype="multipart/form-data">

                                    @csrf

                                    <div class="row g-3">

                                        {{-- HỌ --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">
                                                Họ
                                            </label>

                                            <input type="text" name="first_name" class="form-control"
                                                value="{{ $customer->first_name ?? ''}}" />
                                        </div>

                                        {{-- TÊN --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">
                                                Tên
                                            </label>

                                            <input type="text" name="last_name" class="form-control"
                                                value="{{ $customer->last_name ?? ''}}" />
                                        </div>

                                        {{-- CCCD --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">
                                                Số CCCD
                                            </label>

                                            <input type="text" name="cccd" class="form-control"
                                                value="{{ $customer->cccd ?? ''}}" />
                                        </div>

                                        {{-- PHONE --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">
                                                Số điện thoại
                                            </label>

                                            <input type="tel" name="phone" class="form-control"
                                                value="{{ $customer->phone ?? ''}}" />
                                        </div>

                                        {{-- EMAIL --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">
                                                Email
                                            </label>

                                            <input type="email" name="email" class="form-control"
                                                value="{{ $customer->email ?? ''}}" />
                                        </div>

                                        {{-- BIRTHDAY --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">
                                                Ngày sinh
                                            </label>

                                            <input type="date" name="birthday" class="form-control"
                                                value="{{ $customer->birthday ?? ''}}" />
                                        </div>

                                        {{-- GENDER --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">
                                                Giới tính
                                            </label>

                                            <select name="gender" class="form-select">

                                                <option value="male" {{ $customer->gender == 'male' ? 'selected' : '' }}>
                                                    Nam
                                                </option>

                                                <option value="female" {{ $customer->gender == 'female' ? 'selected' : '' }}>
                                                    Nữ
                                                </option>

                                                <option value="other" {{ $customer->gender == 'other' ? 'selected' : '' }}>
                                                    Khác
                                                </option>

                                            </select>
                                        </div>

                                        {{-- ADDRESS --}}
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">
                                                Địa chỉ liên hệ
                                            </label>

                                            <textarea name="address" class="form-control"
                                                rows="2">{{ $customer->address ?? ''}}</textarea>
                                        </div>

                                    </div>

                                    <div class="mt-3 d-flex gap-2">

                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bx bx-save me-1"></i>
                                            Lưu thay đổi
                                        </button>

                                        <button type="reset" class="btn btn-outline-secondary px-4">
                                            Hủy
                                        </button>

                                    </div>

                                </form>

                            </div>
                        </div>

                        <!-- Tab 2: Đổi mật khẩu -->
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <div class="settings-section">
                                <h3 class="settings-section-title">
                                    <i class="bx bx-lock-alt"></i> Đổi mật khẩu
                                </h3>
                                <p class="small text-muted mb-3">Để bảo mật tài khoản, hãy sử dụng mật khẩu mạnh gồm chữ
                                    hoa, chữ thường, số và ký tự đặc biệt.</p>
                                @if(session('success'))
                                    <div class="alert alert-success">
                                        {{ session('success') }}
                                    </div>
                                @endif
                                <form id="passwordForm" method="post" action="{{ route('user.password.update') }}">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label small fw-semibold">Mật khẩu hiện tại</label>
                                            <div class="input-group">
                                                <input name="pass_old" type="password" class="form-control" id="currentPwd"
                                                    placeholder="Nhập mật khẩu hiện tại" />
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePwd('currentPwd',this)">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Mật khẩu mới</label>
                                            <div class="input-group">
                                                <input name="pass_new" type="password" class="form-control" id="newPwd"
                                                    placeholder="Tối thiểu 8 ký tự"
                                                    oninput="checkPwdStrength(this.value)" />
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePwd('newPwd',this)">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            </div>
                                            <div class="pwd-strength mt-1">
                                                <div class="pwd-strength-bar" id="pwdStrengthBar"></div>
                                            </div>
                                            <small class="text-muted" id="pwdStrengthLabel"></small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Xác nhận mật khẩu mới</label>
                                            <div class="input-group">
                                                <input name="pass_re" type="password" class="form-control" id="confirmPwd"
                                                    placeholder="Nhập lại mật khẩu mới" />
                                                <button class="btn btn-outline-secondary" type="button"
                                                    onclick="togglePwd('confirmPwd',this)">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bx bx-lock-open-alt me-1"></i>Cập nhật mật khẩu
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Tab 3: Đơn phòng -->
                        <div class="tab-pane fade" id="bookings" role="tabpanel">

                            <div class="settings-section">

                                <h3 class="settings-section-title">
                                    <i class="bx bx-calendar"></i>
                                    Đơn phòng của bạn
                                </h3>

                                <p class="small text-muted mb-3">
                                    Theo dõi các đơn đặt phòng, trạng thái xác nhận và phòng đã được khách sạn gán.
                                </p>

                                @forelse ($bookings as $booking)

                                    <div class="card border-0 shadow-sm mb-3">

                                        <div class="card-body">

                                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">

                                                <div>
                                                    <h4 class="h6 fw-bold mb-1">
                                                        {{ $booking->booking_code }}
                                                    </h4>

                                                    <p class="small text-muted mb-0">
                                                        {{ $booking->roomCategory->name ?? 'Không xác định' }}
                                                        • {{ $booking->room_quantity }} phòng
                                                    </p>
                                                </div>

                                                <div class="d-flex gap-2">

                                                    @if ($booking->status == 'pending')
                                                        <span class="badge text-bg-warning">
                                                            Chờ xác nhận
                                                        </span>
                                                    @elseif ($booking->status == 'confirmed')
                                                        <span class="badge text-bg-primary">
                                                            Đã xác nhận
                                                        </span>
                                                    @elseif ($booking->status == 'checked_in')
                                                        <span class="badge text-bg-info">
                                                            Đã nhận phòng
                                                        </span>
                                                    @elseif ($booking->status == 'checked_out')
                                                        <span class="badge text-bg-success">
                                                            Đã trả phòng
                                                        </span>
                                                    @else
                                                        <span class="badge text-bg-danger">
                                                            Đã hủy
                                                        </span>
                                                    @endif

                                                </div>

                                            </div>

                                            <div class="row g-2 mb-3">

                                                <div class="col-md-6">
                                                    <p class="small mb-1">
                                                        <strong>Nhận phòng:</strong>
                                                        {{ date('d/m/Y', strtotime($booking->check_in_date)) }}
                                                    </p>

                                                    <p class="small mb-0">
                                                        <strong>Trả phòng:</strong>
                                                        {{ date('d/m/Y', strtotime($booking->check_out_date)) }}
                                                    </p>
                                                </div>

                                                <div class="col-md-6">
                                                    <p class="small mb-1">
                                                        <strong>Tổng tiền:</strong>
                                                        {{ number_format($booking->estimated_total, 0, ',', '.') }}đ
                                                    </p>

                                                    <p class="small mb-0">
                                                        <strong>Thanh toán:</strong>

                                                        @if ($booking->payment_status == 'unpaid')
                                                            Chưa thanh toán
                                                        @elseif ($booking->payment_status == 'partial')
                                                            Đã cọc
                                                        @elseif ($booking->payment_status == 'paid')
                                                            Đã thanh toán
                                                        @else
                                                            Đã hoàn tiền
                                                        @endif
                                                    </p>
                                                </div>

                                            </div>

                                            <div class="border-top pt-3">

                                                <p class="small fw-bold mb-2">
                                                    Phòng đã gán:
                                                </p>

                                                @forelse ($booking->bookingRooms as $bookingRoom)

                                                    <span class="badge text-bg-light border me-1 mb-1">
                                                        Phòng {{ $bookingRoom->room->room_number ?? 'Không xác định' }}
                                                    </span>

                                                @empty

                                                    <span class="small text-muted">
                                                        Khách sạn chưa gán phòng cụ thể cho đơn này.
                                                    </span>

                                                @endforelse

                                            </div>

                                            <div class="mt-3 d-flex gap-2">

                                                <a href="{{ route('bookings.show', $booking->id) }}"
                                                    class="btn btn-outline-primary btn-sm">

                                                    <i class="bx bx-show me-1"></i>
                                                    Chi tiết

                                                </a>

                                                @if (in_array($booking->status, ['pending', 'confirmed']))

                                                    <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST"
                                                        class="d-inline"
                                                        onsubmit="return confirm('Bạn có chắc muốn hủy đơn đặt phòng này không?')">

                                                        @csrf

                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i class="bx bx-x-circle me-1"></i>
                                                            Hủy đơn
                                                        </button>

                                                    </form>

                                                @endif

                                            </div>

                                        </div>

                                    </div>

                                @empty

                                <div class="alert alert-info mb-0">
                                    Bạn chưa có đơn đặt phòng nào.
                                </div>

                                @endforelse

                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
@endsection