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

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">
                        Lọc phòng trống
                    </h2>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('rooms') }}">

                        <div class="row g-3 align-items-end">

                            <div class="col-md-3">
                                <label class="form-label">
                                    Nhận phòng
                                </label>

                                <input type="date" name="check_in_date" id="check_in_date" class="form-control"
                                    min="{{ date('Y-m-d') }}"
                                    value="{{ old('check_in_date', $searchData['check_in_date'] ?? '') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">
                                    Trả phòng
                                </label>

                                <input type="date" name="check_out_date" id="check_out_date" class="form-control"
                                    min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                    value="{{ old('check_out_date', $searchData['check_out_date'] ?? '') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">
                                    Người lớn
                                </label>

                                <select name="adult_count" class="form-select">
                                    <option value="">Tất cả</option>

                                    @for ($i = 1; $i <= 6; $i++)
                                        <option value="{{ $i }}" {{ (string) old('adult_count', $searchData['adult_count'] ?? '') === (string) $i ? 'selected' : '' }}>
                                            {{ $i }} người lớn
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">
                                    Trẻ em
                                </label>

                                <select name="child_count" class="form-select">
                                    <option value="">Tất cả</option>

                                    @for ($i = 0; $i <= 4; $i++)
                                        <option value="{{ $i }}" {{ (string) old('child_count', $searchData['child_count'] ?? '') === (string) $i ? 'selected' : '' }}>
                                            {{ $i }} trẻ em
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">
                                    Hạng phòng
                                </label>

                                <select name="room_category_id" class="form-select">
                                    <option value="">Tất cả</option>

                                    @foreach (($filterRoomCategories ?? collect()) as $filterCategory)
                                        <option value="{{ $filterCategory->id }}" {{ (string) old('room_category_id', $searchData['room_category_id'] ?? '') === (string) $filterCategory->id ? 'selected' : '' }}>
                                            {{ $filterCategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12">
                                <div class="alert alert-info mb-0 small">
                                    Hệ thống kiểm tra phòng trống theo chính sách:
                                    nhận phòng <strong>14:00 - 15:00</strong>,
                                    trả phòng <strong>trước 11:00</strong>.
                                </div>
                            </div>

                            <div class="col-md-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Kiểm tra phòng trống
                                </button>

                                <a href="{{ route('rooms') }}" class="btn btn-outline-secondary">
                                    Xóa lọc
                                </a>
                            </div>

                        </div>

                    </form>
                </div>
            </div>

            @if (!empty($searchData['check_in_date']) && !empty($searchData['check_out_date']))
                <div class="alert alert-info">
                    Đang hiển thị các hạng phòng còn phòng trống từ
                    <strong>{{ $searchData['check_in_time'] ?? '14:00' }}</strong>
                    ngày <strong>{{ date('d/m/Y', strtotime($searchData['check_in_date'])) }}</strong>
                    đến
                    <strong>{{ $searchData['check_out_time'] ?? '11:00' }}</strong>
                    ngày <strong>{{ date('d/m/Y', strtotime($searchData['check_out_date'])) }}</strong>.
                </div>
            @endif

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

                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <span class="badge bg-primary-soft text-primary">
                                                {{ $category->name }}
                                            </span>
                                        </div>

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

                                            <div class="d-flex gap-2">
                                                <a href="{{ route('rooms.show', $category->id) }}"
                                                    class="btn btn-outline-primary btn-sm">
                                                    Xem chi tiết
                                                </a>
                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </article>

                    </div>

                @empty

                    <div class="col-12">

                        <div class="alert alert-warning mb-0">
                            Không tìm thấy hạng phòng còn trống phù hợp với điều kiện đã chọn.
                        </div>

                    </div>

                @endforelse

            </div>

        </div>
    </main>

    <script>
        const checkInInput = document.getElementById('check_in_date');
        const checkOutInput = document.getElementById('check_out_date');

        function addOneDay(dateString) {
            const date = new Date(dateString);
            date.setDate(date.getDate() + 1);
            return date.toISOString().split('T')[0];
        }

        if (checkInInput && checkOutInput) {
            if (checkInInput.value) {
                checkOutInput.min = addOneDay(checkInInput.value);
            }

            checkInInput.addEventListener('change', function () {
                if (!this.value) {
                    checkOutInput.min = "{{ date('Y-m-d', strtotime('+1 day')) }}";
                    return;
                }

                const minCheckOutDate = addOneDay(this.value);
                checkOutInput.min = minCheckOutDate;

                if (checkOutInput.value && checkOutInput.value <= this.value) {
                    checkOutInput.value = minCheckOutDate;
                }
            });
        }
    </script>

@endsection