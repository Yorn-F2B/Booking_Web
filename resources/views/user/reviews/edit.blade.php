@extends('layouts.user')

@section('title', 'Chỉnh sửa đánh giá')

@section('content')
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">Chỉnh sửa đánh giá</h1>
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
                            <span class="badge {{ $review->status_badge_class }} mb-2">{{ $review->status_label }}</span>
                            <h2 class="h5 fw-bold mb-2">{{ $booking->booking_code }}</h2>
                            <p class="text-muted small mb-2">{{ $booking->roomCategory->name ?? 'Hạng phòng' }}</p>
                            <div class="text-warning fs-5 mb-2">{{ $review->star_text }}</div>
                            @if ($review->admin_reply)
                                <div class="alert alert-info small mb-0">
                                    <div class="fw-semibold mb-1">Phản hồi khách sạn</div>
                                    {{ $review->admin_reply }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <form action="{{ route('reviews.update', $review) }}" method="POST" class="card border-0 shadow-sm">
                        @csrf
                        @method('PUT')

                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-3">Cập nhật đánh giá</h2>
                            @include('user.reviews._form', ['review' => $review])
                        </div>

                        <div class="card-footer bg-white border-0 px-4 pb-4 d-flex flex-wrap gap-2 justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>
                                Lưu chỉnh sửa
                            </button>
                        </div>
                    </form>

                    <form action="{{ route('reviews.destroy', $review) }}" method="POST" class="mt-3 text-end"
                        onsubmit="return confirm('Bạn có chắc muốn xóa đánh giá này không?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bx bx-trash me-1"></i>
                            Xóa đánh giá
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
@endsection
