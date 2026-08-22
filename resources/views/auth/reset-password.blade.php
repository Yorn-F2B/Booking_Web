@extends('layouts.user')

@section('title', 'Đặt lại mật khẩu - MCuong Hotel')

@section('content')
<section class="page-header">
    <div class="container">
        <h1 class="display-6 fw-bold mb-1">Đặt lại mật khẩu</h1>
        <p class="text-muted mb-0">Tạo mật khẩu mới cho tài khoản MCuong Hotel.</p>
    </div>
</section>

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('password.store') }}">
                            @csrf
                            <input type="hidden" name="token" value="{{ $request->route('token') }}">

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="email">Email</label>
                                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                                    class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="password">Mật khẩu mới</label>
                                <input id="password" type="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="password_confirmation">Nhập lại mật khẩu mới</label>
                                <input id="password_confirmation" type="password" name="password_confirmation"
                                    class="form-control" required autocomplete="new-password">
                            </div>

                            <button class="btn btn-primary w-100" type="submit">Đặt lại mật khẩu</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
