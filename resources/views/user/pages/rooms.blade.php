@extends('layouts.user')

@section('title', 'Rooms')

@section('content')

    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-2">
                Danh sách tất cả phòng tại MCuong Hotel
            </h1>

            <p class="text-muted mb-0">
                Lựa chọn đa dạng từ phòng tiêu chuẩn đến suite cao cấp, phù hợp cho
                cặp đôi, gia đình và khách công tác.
            </p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">

            <div class="row g-4">

                @forelse ($roomCategories as $category)

                    <div class="col-12">

                        <article class="card room-card-horizontal border-0 shadow-sm">

                            <div class="row g-0 h-100">

                                <div class="col-md-4">

                                    <div class="ratio ratio-4x3 h-100">

                                        @if ($category->thumbnail)

                                            <img src="{{ asset('storage/' . $category->thumbnail) }}" class="card-img-top h-100"
                                                alt="{{ $category->name }}" style="object-fit: cover;">

                                        @elseif ($category->images->count())

                                            <img src="{{ asset('storage/' . $category->images->first()->image) }}"
                                                class="card-img-top h-100" alt="{{ $category->name }}" style="object-fit: cover;">

                                        @else

                                            <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                                <span class="text-muted">
                                                    Chưa có ảnh
                                                </span>
                                            </div>

                                        @endif

                                    </div>

                                </div>

                                <div class="col-md-8">

                                    <div class="card-body h-100 d-flex flex-column">

                                        <span class="badge bg-primary-soft text-primary mb-2">
                                            {{ $category->name }}
                                        </span>

                                        <h2 class="h5">
                                            {{ $category->name }}
                                        </h2>

                                        <p class="small text-muted mb-2">
                                            {{ $category->area ?? 'Chưa cập nhật' }}m²,
                                            {{ $category->bed_count ?? 1 }} giường
                                        </p>

                                        <p class="small mb-2">
                                            <strong>
                                                Tối đa {{ $category->adult_capacity }} người lớn,
                                                {{ $category->child_capacity }} trẻ em
                                            </strong>
                                        </p>

                                        <ul class="amenity-list mb-3">

                                            @forelse ($category->amenities->take(4) as $amenity)

                                                <li class="amenity-pill">

                                                    @if ($amenity->icon)

                                                        <i class="{{ $amenity->icon }} me-1"></i>

                                                    @endif

                                                    {{ $amenity->name }}

                                                </li>

                                            @empty

                                                <li class="amenity-pill">
                                                    Chưa có tiện ích
                                                </li>

                                            @endforelse

                                        </ul>

                                        <div class="mt-auto d-flex justify-content-between align-items-center">

                                            <div>
                                                <span class="fw-bold text-primary fs-5">
                                                    {{ number_format($category->price, 0, ',', '.') }}đ
                                                </span>

                                                <span class="text-muted small">
                                                    /đêm
                                                </span>
                                            </div>

                                            <a href="{{ route('rooms.show', $category->id) }}"
                                                class="btn btn-outline-primary btn-sm">
                                                Xem chi tiết
                                            </a>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </article>

                    </div>

                @empty

                    <div class="col-12">

                        <div class="alert alert-info mb-0">
                            Hiện chưa có hạng phòng nào được hiển thị.
                        </div>

                    </div>

                @endforelse

            </div>

        </div>
    </main>

@endsection