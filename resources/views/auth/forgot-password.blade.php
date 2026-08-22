@extends('layouts.user')

@section('title', 'Quên mật khẩu - MCuong Hotel')

@section('content')
<section class="page-header">
    <div class="container">
        <h1 class="display-6 fw-bold mb-1">Quên mật khẩu</h1>
        <p class="text-muted mb-0">Nhập email tài khoản để nhận liên kết đặt lại mật khẩu.</p>
    </div>
</section>

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        @if (session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="email">Email</label>
                                <input id="email" type="email" name="email" value="{{ old('email') }}"
                                    class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="email">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Gửi liên kết đặt lại mật khẩu</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="{{ route('login') }}">Quay lại đăng nhập</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
