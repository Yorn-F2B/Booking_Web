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
                            <img id="avatarPreview" src="https://images.pexels.com/photos/771742/pexels-photo-771742.jpeg"
                                alt="avatar" class="avatar-lg" />
                            <label for="avatarInput" class="avatar-upload-overlay" title="Đổi ảnh đại diện">
                                <i class="bx bx-camera"></i>
                            </label>
                            <input type="file" id="avatarInput" accept="image/*" class="d-none" />
                        </div>
                        <h2 class="h6 fw-bold mb-1" style="font-family:'DM Serif Display',serif">Nguyễn Văn A</h2>
                        <p class="text-muted small mb-1">nguyenvana@email.com</p>
                        <hr class="my-3" />
                        <ul class="list-unstyled text-start small text-muted mb-0" style="line-height:2">
                            <li><i class="bx bx-calendar-check me-2 text-gold"></i>Thành viên từ: 01/2024</li>
                            <li><i class="bx bx-hotel me-2 text-gold"></i>Đã đặt: 7 lần</li>
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
                                    <i class="bx bx-user"></i> Thông tin cá nhân
                                </h3>
                                <form id="settingsForm">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Họ và tên</label>
                                            <input type="text" class="form-control" value="Nguyễn Văn A" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Số CCCD</label>
                                            <input type="text" class="form-control" value="079203001234" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Số điện thoại</label>
                                            <input type="tel" class="form-control" value="0988 666 999" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Email</label>
                                            <input type="email" class="form-control" value="nguyenvana@email.com" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Ngày sinh</label>
                                            <input type="date" class="form-control" value="1995-09-15" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Giới tính</label>
                                            <select class="form-select">
                                                <option selected>Nam</option>
                                                <option>Nữ</option>
                                                <option>Khác</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">Địa chỉ liên hệ</label>
                                            <textarea class="form-control" rows="2">Sơn Trà, Đà Nẵng</textarea>
                                        </div>
                                    </div>
                                    <div class="mt-3 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="bx bx-save me-1"></i>Lưu thay đổi
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary px-4">Hủy</button>
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
                                <form id="passwordForm">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label small fw-semibold">Mật khẩu hiện tại</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="currentPwd"
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
                                                <input type="password" class="form-control" id="newPwd"
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
                                                <input type="password" class="form-control" id="confirmPwd"
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

                        <!-- Tab 3: Đơn phòng (cho phép hủy đơn) -->
                        <div class="tab-pane fade" id="bookings" role="tabpanel">
                            <div class="settings-section">
                                <h3 class="settings-section-title">
                                    <i class="bx bx-calendar"></i> Đơn phòng của bạn
                                </h3>
                                <p class="small text-muted mb-3">Quản lý các đơn đặt phòng hiện tại và tương lai. Bạn có thể
                                    hủy đơn nếu còn trong thời gian cho phép.</p>

                                <div class="card border-0 shadow-sm mb-3">
                                    <div class="card-body">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                                            <div>
                                                <h4 class="h6 fw-bold mb-0">MC2026-001</h4>
                                                <p class="small text-muted mb-0">Phòng Deluxe hướng biển • 2 đêm</p>
                                            </div>
                                            <span class="badge text-bg-success">Đã xác nhận</span>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <p class="small mb-1"><strong>Nhận phòng:</strong> 08/05/2026, 14:00</p>
                                                <p class="small mb-0"><strong>Trả phòng:</strong> 10/05/2026, 12:00</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="small mb-1"><strong>Tổng tiền:</strong> 3.600.000đ</p>
                                                <p class="small mb-0"><strong>Hủy miễn phí trước:</strong> 05/05/2026</p>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#cancelModal">
                                                <i class="bx bx-x-circle me-1"></i>Hủy đơn
                                            </button>
                                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#bookingDetailModal" data-booking-id="MC2026-001">
                                                <i class="bx bx-detail me-1"></i>Xem chi tiết
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                                            <div>
                                                <h4 class="h6 fw-bold mb-0">MC2026-004</h4>
                                                <p class="small text-muted mb-0">Suite gia đình • 3 đêm</p>
                                            </div>
                                            <span class="badge text-bg-warning">Chờ xác nhận</span>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <p class="small mb-1"><strong>Nhận phòng:</strong> 20/05/2026, 14:00</p>
                                                <p class="small mb-0"><strong>Trả phòng:</strong> 23/05/2026, 12:00</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="small mb-1"><strong>Tổng tiền:</strong> 9.600.000đ</p>
                                                <p class="small mb-0"><strong>Hủy miễn phí trước:</strong> 17/05/2026</p>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#cancelModal">
                                                <i class="bx bx-x-circle me-1"></i>Hủy đơn
                                            </button>
                                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#bookingDetailModal" data-booking-id="MC2026-004">
                                                <i class="bx bx-detail me-1"></i>Xem chi tiết
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
@endsection