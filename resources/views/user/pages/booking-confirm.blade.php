@extends('layouts.user')

@section('title', 'Xác nhận đặt phòng')

@section('content')

    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">
                Xác nhận đặt phòng
            </h1>

            <p class="text-muted mb-0">
                Kiểm tra thông tin cá nhân và thông tin đặt phòng trước khi hoàn tất.
            </p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    Vui lòng kiểm tra lại thông tin bên dưới.
                </div>
            @endif

            <form action="{{ route('bookings.store') }}" method="POST">
                @csrf

                <input type="hidden" name="room_category_id" value="{{ $bookingData['room_category_id'] }}">
                <input type="hidden" name="check_in_date" value="{{ $bookingData['check_in_date'] }}">
                <input type="hidden" name="check_out_date" value="{{ $bookingData['check_out_date'] }}">
                <input type="hidden" name="adult_count" value="{{ $bookingData['adult_count'] }}">
                <input type="hidden" name="child_count" value="{{ $bookingData['child_count'] ?? 0 }}">
                <input type="hidden" name="note" value="{{ $bookingData['note'] ?? '' }}">

                <div class="row g-4">

                    <div class="col-lg-8">

                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Thông tin khách hàng
                                </h2>

                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Họ
                                        </label>
                                        <input type="text" name="last_name" class="form-control"
                                            value="{{ old('last_name', $customer->last_name ?? '') }}" required>
                                        @error('last_name')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Tên
                                        </label>
                                        <input type="text" name="first_name" class="form-control"
                                            value="{{ old('first_name', $customer->first_name ?? '') }}" required>
                                        @error('first_name')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            Số điện thoại
                                        </label>
                                        <input type="text" name="phone" class="form-control"
                                            value="{{ old('phone', $customer->phone ?? '') }}" required>
                                        @error('phone')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">
                                            CCCD
                                        </label>
                                        <input type="text" name="cccd" class="form-control"
                                            value="{{ old('cccd', $customer->cccd ?? '') }}">
                                        @error('cccd')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">
                                            Email
                                        </label>
                                        <input type="email" name="email" class="form-control"
                                            value="{{ old('email', $customer->email ?? auth()->user()->email) }}">
                                        @error('email')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">
                                            Địa chỉ
                                        </label>
                                        <textarea name="address" rows="3"
                                            class="form-control">{{ old('address', $customer->address ?? '') }}</textarea>
                                        @error('address')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>

                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Ghi chú đặt phòng
                                </h2>

                                <p class="mb-0 text-muted">
                                    {{ $bookingData['note'] ?? 'Không có ghi chú.' }}
                                </p>

                            </div>
                        </div>

                    </div>

                    <div class="col-lg-4">

                        <div class="card border-0 shadow-sm sticky-top" style="top: 90px;">
                            <div class="card-body">

                                <h2 class="h5 fw-bold mb-3">
                                    Thông tin booking
                                </h2>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Hạng phòng
                                    </div>
                                    <div class="fw-bold">
                                        {{ $roomCategory->name }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Nhận phòng
                                    </div>
                                    <div class="fw-bold">
                                        {{ date('d/m/Y', strtotime($bookingData['check_in_date'])) }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Trả phòng
                                    </div>
                                    <div class="fw-bold">
                                        {{ date('d/m/Y', strtotime($bookingData['check_out_date'])) }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Số đêm
                                    </div>
                                    <div class="fw-bold">
                                        {{ $nightCount }} đêm
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Số khách
                                    </div>
                                    <div class="fw-bold">
                                        {{ $bookingData['adult_count'] }} người lớn,
                                        {{ $bookingData['child_count'] ?? 0 }} trẻ em
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="small text-muted">
                                        Số phòng
                                    </div>
                                    <div class="fw-bold">
                                        1 phòng
                                    </div>
                                </div>

                                <hr>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-bold">
                                        Tạm tính
                                    </span>

                                    <span class="fw-bold text-primary fs-5">
                                        {{ number_format($estimatedTotal, 0, ',', '.') }}đ
                                    </span>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="bx bx-check-circle me-1"></i>
                                    Xác nhận đặt phòng
                                </button>

                                <a href="{{ route('rooms.show', $roomCategory->id) }}"
                                    class="btn btn-outline-secondary w-100 mt-2">
                                    Quay lại
                                </a>

                                <p class="small text-muted mt-3 mb-0">
                                    Nếu cần đặt nhiều phòng hoặc khách đoàn, vui lòng liên hệ hotline/lễ tân để được hỗ trợ.
                                </p>

                            </div>
                        </div>

                    </div>

                </div>

            </form>

        </div>
    </main>

@endsection