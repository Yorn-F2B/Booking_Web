@extends('layouts.user')

@section('title', 'Xác nhận mật khẩu - MCuong Hotel')

@section('content')
<section class="page-header">
    <div class="container">
        <h1 class="display-6 fw-bold mb-1">Xác nhận mật khẩu</h1>
        <p class="text-muted mb-0">Vui lòng xác nhận mật khẩu trước khi tiếp tục thao tác bảo mật.</p>
    </div>
</section>

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('password.confirm') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="password">Mật khẩu</label>
                                <input id="password" type="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Xác nhận</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
