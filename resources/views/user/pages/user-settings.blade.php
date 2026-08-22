@extends('layouts.user')

@section('title', 'Tài khoản')

@section('content')
    @php
        $isGoogleOnly = !empty($user->google_id) && empty($user->password);
    @endphp

    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">Cài đặt người dùng</h1>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            @include('user.partials.account-restriction')

            <div class="row g-4 align-items-start">

                <!-- Sidebar: Avatar + thông tin nhanh -->
                <div class="col-lg-3">
                    <div class="settings-section text-center">
                        <!-- Avatar upload -->
                        @php
                            $avatar = Auth::user()->avatar;

                            if ($avatar) {
                                $avatarUrl = \Illuminate\Support\Str::startsWith($avatar, ['http://', 'https://'])
                                    ? $avatar
                                    : asset('storage/' . $avatar);
                            } else {
                                $avatarUrl = 'https://ui-avatars.com/api/?name='
                                    . urlencode(Auth::user()->name)
                                    . '&size=200&background=e9ecef&color=495057';
                            }
                        @endphp

                        <div class="avatar-upload-wrap mb-3 mx-auto" style="width: fit-content">
                            <img id="avatarPreview" src="{{ $avatarUrl }}" alt="Ảnh đại diện" class="avatar-lg" />

                            <label for="avatarInput" class="avatar-upload-overlay" title="Đổi ảnh đại diện">
                                <i class="bx bx-camera"></i>
                            </label>

                            <input type="file" id="avatarInput" form="userSettingsForm" name="avatar" accept="image/*" data-persistent-files data-preview-target="#avatarFilePreview"
                                class="d-none" />
                            <div id="avatarFilePreview"></div>
                            @error('avatar')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <h2 class="h6 fw-bold mb-1" style="font-family:'DM Serif Display',serif">{{ Auth::user()->name }}
                        </h2>
                        <p class="text-muted small mb-1">{{ Auth::user()->email }}</p>
                        <hr class="my-3" />
                        <ul class="list-unstyled text-start small text-muted mb-0" style="line-height:2">
                            <li><i class="bx bx-calendar-check me-2 text-gold"></i>Thành viên từ:
                                {{ Auth::user()->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y') ?? '—' }}
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
                            @unless($isGoogleOnly)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="password-tab" data-bs-toggle="tab"
                                        data-bs-target="#password" type="button" role="tab">
                                        <i class="bx bx-lock-alt me-1"></i>Mật khẩu
                                    </button>
                                </li>
                            @endunless
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
@error('profile')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror

                                @if($errors->any() && !$errors->has('profile'))
                                    <div class="alert alert-danger">
                                        Vui lòng kiểm tra lại các trường được đánh dấu bên dưới.
                                    </div>
                                @endif

                                <form id="userSettingsForm" method="POST" action="{{ route('user.settings.update') }}"
                                    enctype="multipart/form-data">

                                    @csrf

                                    <div class="row g-3">

                                        {{-- HỌ --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Họ <span class="text-danger">*</span></label>
                                            <input type="text" name="last_name"
                                                class="form-control @error('last_name') is-invalid @enderror"
                                                value="{{ old('last_name', $customer->last_name ?? '') }}" required>
                                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        {{-- TÊN --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Tên <span class="text-danger">*</span></label>
                                            <input type="text" name="first_name"
                                                class="form-control @error('first_name') is-invalid @enderror"
                                                value="{{ old('first_name', $customer->first_name ?? '') }}" required>
                                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <div class="border rounded p-3 bg-light">
                                                <div class="fw-semibold mb-2">Đọc thông tin từ ảnh CCCD</div>
                                                <button type="button" id="settingsCccdImageButton" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('settingsCccdImage').click()">
                                                    <i class="bx bx-image-add me-1"></i> Quét CCCD từ ảnh
                                                </button>
                                                <input type="file" id="settingsCccdImage" class="d-none js-cccd-image"
                                                    accept="image/jpeg,image/png,image/webp"
                                                    data-button="#settingsCccdImageButton" data-status="#settingsCccdStatus"
                                                    data-target-cccd="input[name='cccd']"
                                                    data-target-first-name="input[name='first_name']"
                                                    data-target-last-name="input[name='last_name']"
                                                    data-target-birthday="#us_birthday"
                                                    data-target-gender="select[name='gender']"
                                                    data-target-address="textarea[name='address']"
                                                    data-required-fields="cccd,full_name,birthday,gender,address">
                                                <small id="settingsCccdStatus" class="text-muted d-block mt-2"></small>
                                            </div>
                                        </div>

                                        {{-- CCCD --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Số CCCD <span class="text-danger">*</span></label>
                                            <input type="text" name="cccd" inputmode="numeric" maxlength="12"
                                                class="form-control @error('cccd') is-invalid @enderror"
                                                value="{{ old('cccd', $customer->cccd ?? '') }}" required>
                                            @error('cccd')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        {{-- PHONE --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                                            <input type="tel" name="phone" inputmode="numeric" maxlength="10"
                                                class="form-control @error('phone') is-invalid @enderror"
                                                value="{{ old('phone', $customer->phone ?? '') }}" required>
                                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        {{-- EMAIL --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                                            <input type="email" name="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                value="{{ old('email', $customer->email ?: $user->email) }}" required>
                                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        {{-- BIRTHDAY --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Ngày sinh</label>
                                            @php
                                                $bdVal = old('birthday', $customer?->birthday ? \Carbon\Carbon::parse($customer->birthday)->format('Y-m-d') : '');
                                            @endphp
                                            <input type="date" name="birthday" id="us_birthday"
                                                data-birth-date data-year-select
                                                class="form-control @error('birthday') is-invalid @enderror"
                                                value="{{ $bdVal }}" min="1900-01-01" max="{{ now('Asia/Ho_Chi_Minh')->format('Y-m-d') }}">
                                            @error('birthday')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        {{-- GENDER --}}
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">
                                                Giới tính
                                            </label>

                                            <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>

                                                <option value="male" {{ old('gender', $customer->gender ?? '') == 'male' ? 'selected' : '' }}>
                                                    Nam
                                                </option>

                                                <option value="female" {{ old('gender', $customer->gender ?? '') == 'female' ? 'selected' : '' }}>
                                                    Nữ
                                                </option>

                                                <option value="other" {{ old('gender', $customer->gender ?? '') == 'other' ? 'selected' : '' }}>
                                                    Khác
                                                </option>

                                            </select>
                                            @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        {{-- ADDRESS --}}
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">
                                                Địa chỉ liên hệ <span class="text-danger">*</span>
                                            </label>

                                            <textarea name="address" class="form-control @error('address') is-invalid @enderror"
                                                rows="2" required>{{ old('address', $customer->address ?? '') }}</textarea>
                                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

                        @unless($isGoogleOnly)
                        <!-- Tab 2: Đổi mật khẩu -->
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <div class="settings-section">
                                <h3 class="settings-section-title">
                                    <i class="bx bx-lock-alt"></i> Đổi mật khẩu
                                </h3>
                                <p class="small text-muted mb-3">Để bảo mật tài khoản, hãy sử dụng mật khẩu mạnh gồm chữ
                                    hoa, chữ thường, số và ký tự đặc biệt.</p>
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

                        @endunless

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

                                                @if (in_array($booking->status, ['pending', 'confirmed']) && (!$booking->check_in_at || now('Asia/Ho_Chi_Minh')->lt($booking->check_in_at)))
                                                    <a href="{{ route('bookings.show', $booking->id) }}#cancel-policy"
                                                        class="btn btn-outline-danger btn-sm">
                                                        <i class="bx bx-info-circle me-1"></i>
                                                        Xem chính sách hủy
                                                    </a>
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
@include('partials.cccd-scanner-script')


<script src="{{ asset('assets/js/persistent-file-inputs.js') }}?v={{ filemtime(public_path('assets/js/persistent-file-inputs.js')) }}"></script>
@endsection
