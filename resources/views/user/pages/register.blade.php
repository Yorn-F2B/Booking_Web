@extends('layouts.user')

@section('title', 'Register')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">Đăng ký tài khoản khách hàng</h1>
            <p class="text-muted mb-0">Nhập đầy đủ thông tin để đặt phòng và quản lý hồ sơ nhanh hơn.</p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form id="registerForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Họ và tên</label>
                                        <input type="text" class="form-control" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Số CCCD</label>
                                        <input type="text" class="form-control" placeholder="12 số CCCD" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="tel" class="form-control" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Ngày sinh</label>
                                        <input type="date" class="form-control" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Giới tính</label>
                                        <select class="form-select">
                                            <option>Nam</option>
                                            <option>Nữ</option>
                                            <option>Khác</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mật khẩu</label>
                                        <input type="password" class="form-control" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Xác nhận mật khẩu</label>
                                        <input type="password" class="form-control" required />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Địa chỉ liên hệ</label>
                                        <textarea class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" id="policyCheck" required />
                                    <label class="form-check-label small" for="policyCheck">
                                        Tôi đồng ý với điều khoản sử dụng và chính sách bảo mật.
                                    </label>
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button type="submit" class="btn btn-primary">Tạo tài khoản</button>
                                    <a href="login.html" class="btn btn-outline-primary">Đã có tài khoản</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection