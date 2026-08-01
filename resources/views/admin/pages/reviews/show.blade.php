@extends('layouts.admin')

@section('title', 'Chi tiết đánh giá')

@section('content')
    <div class="admin-wrapper">
        <div class="admin-content">
            <div class="admin-page-head">
                <div>
                    <div class="admin-breadcrumb">
                        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                        <span>/</span>
                        <a href="{{ route('admin.reviews.index') }}">Đánh giá khách sạn</a>
                        <span>/</span>
                        <span>Chi tiết</span>
                    </div>
                    <h2>Chi tiết đánh giá</h2>
                    <p>Kiểm tra nội dung, duyệt/ẩn và phản hồi khách.</p>
                </div>
                <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>
                    Quay lại
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <span class="badge {{ $review->status_badge_class }} mb-2">{{ $review->status_label }}</span>
                                    <h3 class="h5 fw-bold mb-1">{{ $review->title ?: 'Đánh giá kỳ lưu trú' }}</h3>
                                    <div class="text-warning fs-5">{{ $review->star_text }} <span class="text-muted small">{{ number_format((float) $review->rating, 1) }}/5</span></div>
                                </div>
                                <div class="text-end small text-muted">
                                    Gửi lúc<br>
                                    <strong>{{ optional($review->created_at)->format('d/m/Y H:i') }}</strong>
                                </div>
                            </div>

                            <p class="mb-4">{{ $review->comment }}</p>

                            <div class="row g-2 mb-4 small">
                                <div class="col-6 col-md-3">
                                    <div class="border rounded-3 p-2 text-center">
                                        <div class="text-muted">Vệ sinh</div>
                                        <div class="fw-bold">{{ $review->cleanliness_rating }}/5</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded-3 p-2 text-center">
                                        <div class="text-muted">Dịch vụ</div>
                                        <div class="fw-bold">{{ $review->service_rating }}/5</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded-3 p-2 text-center">
                                        <div class="text-muted">Vị trí</div>
                                        <div class="fw-bold">{{ $review->location_rating }}/5</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded-3 p-2 text-center">
                                        <div class="text-muted">Giá trị</div>
                                        <div class="fw-bold">{{ $review->value_rating }}/5</div>
                                    </div>
                                </div>
                            </div>

                            @if ($review->admin_reply)
                                <div class="alert alert-info mb-0">
                                    <div class="fw-semibold mb-1">Phản hồi hiện tại</div>
                                    {{ $review->admin_reply }}
                                    <div class="small text-muted mt-2">
                                        {{ $review->replier->name ?? 'Admin' }} · {{ optional($review->replied_at)->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h3 class="h6 fw-bold mb-3">Phản hồi khách hàng</h3>
                            <form action="{{ route('admin.reviews.reply', $review) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <textarea name="admin_reply" class="form-control mb-3" rows="5" maxlength="2000"
                                    placeholder="Nhập phản hồi lịch sự, ngắn gọn...">{{ old('admin_reply', $review->admin_reply) }}</textarea>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-message-square-dots me-1"></i>
                                    Lưu phản hồi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h3 class="h6 fw-bold mb-3">Thông tin khách / booking</h3>

                            <div class="mb-3">
                                <div class="small text-muted">Khách hàng</div>
                                <div class="fw-semibold">{{ $review->guest_name }}</div>
                                <div class="small text-muted">{{ $review->customer->phone ?? '---' }} · {{ $review->customer->email ?? '---' }}</div>
                            </div>

                            <div class="mb-3">
                                <div class="small text-muted">Booking</div>
                                <a href="{{ route('admin.bookings.show', $review->booking_id) }}" class="fw-semibold text-decoration-none">
                                    {{ $review->booking->booking_code ?? '---' }}
                                </a>
                                <div class="small text-muted">{{ $review->booking->roomCategory->name ?? '---' }}</div>
                            </div>

                            <div class="mb-3">
                                <div class="small text-muted">Thời gian lưu trú</div>
                                <div class="small">
                                    {{ optional($review->booking->check_in_at ?? null)->format('d/m/Y H:i') }}
                                    -
                                    {{ optional($review->booking->check_out_at ?? null)->format('d/m/Y H:i') }}
                                </div>
                            </div>

                            @if ($review->hidden_reason)
                                <div class="alert alert-warning small mb-0">
                                    <div class="fw-semibold">Lý do ẩn</div>
                                    {{ $review->hidden_reason }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h6 fw-bold mb-3">Thao tác duyệt</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
