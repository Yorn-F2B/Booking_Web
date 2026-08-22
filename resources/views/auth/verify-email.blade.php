@extends('layouts.user')

@section('title', 'Xác minh email - MCuong Hotel')

@section('content')
<section class="page-header">
    <div class="container">
        <h1 class="display-6 fw-bold mb-1">Xác minh email</h1>
        <p class="text-muted mb-0">Xác minh địa chỉ email để bảo vệ tài khoản và sử dụng đầy đủ chức năng.</p>
    </div>
</section>

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <p class="mb-3">Chúng tôi đã gửi liên kết xác minh tới email của bạn. Hãy mở email và nhấn vào liên kết để hoàn tất.</p>

                        @if (session('status') === 'verification-link-sent')
                            <div class="alert alert-success">Đã gửi lại liên kết xác minh email mới.</div>
                        @endif

                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">Gửi lại email xác minh</button>
                            </form>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary">Đăng xuất</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
