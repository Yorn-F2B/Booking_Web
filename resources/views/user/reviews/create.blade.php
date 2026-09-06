@extends('layouts.user')

@section('title', 'Đánh giá khách sạn')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">Đánh giá khách sạn</h1>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <div class="mb-4">
                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i>
                    Quay lại chi tiết đơn
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Vui lòng kiểm tra lại thông tin đánh giá.</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm sticky-top" style="top: 90px;">
                        <div class="card-body">
                            <span class="badge bg-primary-soft text-primary mb-2">{{ $booking->booking_code }}</span>
                            <h2 class="h5 fw-bold mb-2">{{ $booking->roomCategory->name ?? 'Hạng phòng' }}</h2>
                            <p class="small text-muted mb-3">
                                {{ optional($booking->check_in_at)->format('d/m/Y H:i') }}
                                -
                                {{ optional($booking->check_out_at)->format('d/m/Y H:i') }}
                            </p>

                            @forelse ($booking->bookingRooms as $bookingRoom)
                                <div class="border rounded-3 p-2 small mb-2">
                                    Phòng {{ $bookingRoom->room->room_number ?? '---' }}
                                    <span class="text-muted">· Tầng {{ $bookingRoom->room->floor_number ?? '---' }}</span>
                                </div>
                            @empty
                                <div class="alert alert-light border small mb-0">Không có thông tin phòng cụ thể.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <form action="{{ route('bookings.reviews.store', $booking) }}" method="POST" class="card border-0 shadow-sm" novalidate data-review-submit-form>
                        @csrf

                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-3">Bạn đánh giá kỳ lưu trú này thế nào?</h2>
                            @include('user.reviews._form')
                        </div>

                        <div class="card-footer bg-white border-0 px-4 pb-4 d-flex flex-wrap gap-2 justify-content-end">
                            <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline-secondary">Hủy</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-send me-1"></i>
                                Gửi đánh giá
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-review-submit-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.submitting === '1') {
                    event.preventDefault();
                    return;
                }

                form.dataset.submitting = '1';
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.dataset.originalText = button.innerHTML;
                    button.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Đang lưu...';
                }
            });
        });
    });
</script>

@endsection
