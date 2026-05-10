@extends('layouts.user')

@section('title', 'Login')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">Đăng nhập tài khoản</h1>
            <p class="text-muted mb-0">Quản lý lịch sử đặt phòng và thông tin cá nhân của bạn.</p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-3">Thông tin đăng nhập</h2>
                            <form id="loginForm">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" placeholder="email@domain.com" required />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Mật khẩu</label>
                                    <input type="password" class="form-control" placeholder="Nhập mật khẩu" required />
                                </div>
                                <div class="d-flex justify-content-between small mb-3">
                                    <a href="#" class="text-primary">Quên mật khẩu?</a>
                                    <a href="register.html" class="text-primary">Chưa có tài khoản?</a>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection