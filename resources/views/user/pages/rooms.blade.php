@extends('layouts.user')

@section('title', 'Rooms')

@section('content')
    <!-- Page header -->
    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-2">Hạng phòng tại MCuong Hotel</h1>
            <p class="text-muted mb-0">
                Lựa chọn đa dạng từ phòng tiêu chuẩn đến suite cao cấp, phù hợp cho
                cặp đôi, gia đình và khách công tác tại cùng một khách sạn.
            </p>
        </div>
    </section>

    <!-- Room list -->
    <main class="py-5">
        <div class="container">
            <div class="row g-4">
                @forelse($roomCategories ?? [] as $category)
                <div class="col-12">
                    <article class="card room-card-horizontal border-0 shadow-sm">
                        <div class="row g-0 h-100">
                            <div class="col-md-4">
                                <div class="ratio ratio-4x3 h-100">
                                    <img src="{{ $category->thumbnail ?? 'https://images.pexels.com/photos/1579253/pexels-photo-1579253.jpeg' }}"
                                        class="card-img-top h-100" alt="{{ $category->name }}" style="object-fit: cover;" />
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body h-100 d-flex flex-column">
                                    <span class="badge bg-primary-soft text-primary mb-2">{{ $category->name }}</span>
                                    <h2 class="h5">{{ $category->name }}</h2>
                                    <p class="small text-muted mb-2">
                                        {{ $category->description ?? $category->area . 'm², tối đa ' . $category->max_people . ' người.' }}
                                    </p>
                                    <p class="small mb-2"><strong>Tối đa {{ $category->max_people }} người lớn</strong></p>
                                    <ul class="amenity-list mb-3">
                                        <li class="amenity-pill">WiFi tốc độ cao</li>
                                        <li class="amenity-pill">Phù hợp nghỉ dưỡng</li>
                                    </ul>
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-bold text-primary fs-5">{{ number_format($category->price, 0, ',', '.') }}đ</span>
                                            <span class="text-muted small">/đêm</span>
                                        </div>
                                        <a href="/room-{{ $category->id }}" class="btn btn-outline-primary btn-sm">Xem chi
                                            tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
                @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Đang chờ dữ liệu từ Backend...</p>
                </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="mt-5 d-flex justify-content-center">
                {{ isset($roomCategories) ? $roomCategories->links('pagination::bootstrap-5') : '' }}
            </div>>
        </div>
    </main>
@endsection