@extends('layouts.admin')

@section('title', 'Quản lý đánh giá')

@section('content')
    <div class="admin-wrapper">
        <div class="admin-content">
            <div class="admin-page-head">
                <div>
                    <div class="admin-breadcrumb">
                        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                        <span>/</span>
                        <span>Đánh giá khách sạn</span>
                    </div>
                    <h2>Quản lý đánh giá khách sạn</h2>
                    <p>Theo dõi và phản hồi các đánh giá đã được hệ thống tự động hiển thị sau khi lọc từ cấm.</p>
                </div>
            </div>

<div class="row g-3 mb-4">
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Tổng</div>
                            <div class="h4 fw-bold mb-0">{{ $stats['total'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Chờ duyệt</div>
                            <div class="h4 fw-bold mb-0 text-warning">{{ $stats['pending'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Đã duyệt</div>
                            <div class="h4 fw-bold mb-0 text-success">{{ $stats['approved'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Đã ẩn</div>
                            <div class="h4 fw-bold mb-0 text-secondary">{{ $stats['hidden'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="small text-muted">Điểm trung bình công khai</div>
                            <div class="h4 fw-bold mb-0 text-warning">★ {{ number_format((float) $stats['average'], 1) }}/5</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reviews.index') }}" class="row g-2 align-items-end">
                        <div class="col-lg-4">
                            <label class="form-label small fw-semibold">Tìm kiếm</label>
                            <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Mã booking, tên khách, nội dung...">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label small fw-semibold">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                                <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                                <option value="hidden" {{ $status === 'hidden' ? 'selected' : '' }}>Đã ẩn</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label small fw-semibold">Số sao</label>
                            <select name="rating" class="form-select">
                                <option value="">Tất cả</option>
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" {{ (string) $rating === (string) $i ? 'selected' : '' }}>{{ $i }} sao</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-lg-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">Lọc</button>
                            <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Khách</th>
                                <th>Booking</th>
                                <th>Đánh giá</th>
                                <th>Nội dung</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reviews as $review)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $review->guest_name }}</div>
                                        <div class="small text-muted">{{ $review->customer->phone ?? $review->customer->email ?? '---' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $review->booking->booking_code ?? '---' }}</div>
                                        <div class="small text-muted">{{ $review->booking->roomCategory->name ?? '---' }}</div>
                                    </td>
                                    <td>
                                        <div class="text-warning small">{{ $review->star_text }}</div>
                                        <div class="small text-muted">{{ number_format((float) $review->rating, 1) }}/5</div>
                                    </td>
                                    <td style="max-width: 360px;">
                                        @if ($review->title)
                                            <div class="fw-semibold small">{{ $review->title }}</div>
                                        @endif
                                        <div class="small text-muted text-truncate">{{ $review->comment }}</div>
                                        @if ($review->admin_reply)
                                            <span class="badge text-bg-info mt-1">Đã phản hồi</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $review->status_badge_class }}">{{ $review->status_label }}</span>
                                        <div class="small text-muted mt-1">{{ optional($review->created_at)->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            <a href="{{ route('admin.reviews.show', $review) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">Chưa có đánh giá phù hợp.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($reviews->hasPages())
                    <div class="card-footer bg-white">
                        {{ $reviews->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
