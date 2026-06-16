@extends('layouts.admin')

@section('title', 'Tra cứu phòng trống')

@section('content')

    <div class="admin-wrapper">
        <main class="admin-content">

            <h2 class="mb-4">
                Tra cứu phòng trống
            </h2>

            <form method="GET">

                <div class="row g-3 mb-4">

                    <div class="col-md-3">
                        <label>Ngày nhận phòng</label>

                        <input type="date" name="check_in_date" id="checkInDate" class="form-control"
                            min="{{ date('Y-m-d') }}" value="{{ request('check_in_date') }}" required>
                    </div>

                    <div class="col-md-3">
                        <label>Ngày trả phòng</label>

                        <input type="date" name="check_out_date" id="checkOutDate" class="form-control"
                            min="{{ date('Y-m-d', strtotime('+1 day')) }}" value="{{ request('check_out_date') }}" required>
                    </div>

                    <div class="col-md-12">
                        <div class="alert alert-info mb-0 small">
                            Hệ thống kiểm tra phòng trống theo chính sách:
                            nhận phòng <strong>14:00 - 15:00</strong>,
                            trả phòng <strong>trước 11:00</strong>.
                        </div>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100">
                            Kiểm tra
                        </button>
                    </div>

                </div>

            </form>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (!empty($searchData['check_in_date']) && !empty($searchData['check_out_date']))
                <div class="alert alert-info">
                    Đang tra cứu phòng trống từ
                    <strong>14:00</strong>
                    ngày <strong>{{ date('d/m/Y', strtotime($searchData['check_in_date'])) }}</strong>
                    đến
                    <strong>11:00</strong>
                    ngày <strong>{{ date('d/m/Y', strtotime($searchData['check_out_date'])) }}</strong>.
                </div>
            @endif

            @if (isset($roomCategories) && $roomCategories->count())

                <div class="row">

                    @foreach ($roomCategories as $category)

                            <div class="col-md-4 mb-3">

                                <div class="card shadow-sm">

                                    <div class="card-body">

                                        <h5>
                                            {{ $category->name }}
                                        </h5>

                                        <p>
                                            Giá:
                                            {{ number_format($category->price, 0, ',', '.') }}đ
                                        </p>

                                        <p>
                                            Còn:
                                            <strong>
                                                {{ $category->available_rooms_count }}
                                            </strong>
                                            phòng
                                        </p>

                                        <a href="{{ route('admin.bookings.create', [
                            'room_category_id' => $category->id,
                            'check_in_date' => request('check_in_date'),
                            'check_out_date' => request('check_out_date'),
                        ]) }}" class="btn btn-success">

                                            Tạo booking

                                        </a>

                                    </div>

                                </div>

                            </div>

                    @endforeach

                </div>

            @endif

        </main>
    </div>

    <script>
        const checkInDate = document.getElementById('checkInDate');
        const checkOutDate = document.getElementById('checkOutDate');

        function formatDateInput(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function addDays(dateValue, days) {
            const date = new Date(dateValue);
            date.setDate(date.getDate() + days);

            return formatDateInput(date);
        }

        if (checkInDate && checkOutDate) {
            checkInDate.addEventListener('change', function () {
                if (!this.value) {
                    return;
                }

                const minCheckoutDate = addDays(this.value, 1);

                checkOutDate.min = minCheckoutDate;

                if (!checkOutDate.value || checkOutDate.value <= this.value) {
                    checkOutDate.value = minCheckoutDate;
                }
            });

            if (checkInDate.value) {
                checkOutDate.min = addDays(checkInDate.value, 1);
            }
        }
    </script>

@endsection