@extends('layouts.admin')

@section('title', 'Tạo đặt phòng')

@section('content')

    <style>
        .booking-form-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            padding: 18px;
            margin-bottom: 18px;
        }

        .booking-form-card h5 {
            font-weight: 800;
            margin-bottom: 16px;
        }

        .booking-help-text {
            font-size: 13px;
            color: #64748b;
        }

        .booking-total-box {
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 14px;
            background: #f8fafc;
        }

        .booking-total-box strong {
            font-size: 22px;
        }

        .adjacent-room-box {
            display: none;
        }
    </style>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.bookings.index') }}">Đặt phòng</a> /
                Tạo mới
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Tạo đặt phòng</h2>
                    <p>Lễ tân tạo booking, hệ thống tự tìm phòng trống và gán phòng thật</p>
                </div>

                <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
                    Quay lại
                </a>

            </div>

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    Vui lòng kiểm tra lại thông tin trong form.
                </div>
            @endif

            <form action="{{ route('admin.bookings.store') }}" method="POST">

                @csrf

                <div class="row g-4">

                    <div class="col-lg-8">

                        <div class="booking-form-card">

                            <h5>Thông tin khách hàng</h5>

                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Họ tên khách hàng <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_name"
                                        class="form-control @error('customer_name') is-invalid @enderror"
                                        value="{{ old('customer_name') }}" placeholder="Ví dụ: Nguyễn Văn An" required>

                                    @error('customer_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="text" name="customer_phone"
                                        class="form-control @error('customer_phone') is-invalid @enderror"
                                        value="{{ old('customer_phone') }}" placeholder="Ví dụ: 0987654321" required>

                                    @error('customer_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">CCCD</label>
                                    <input type="text" name="customer_cccd"
                                        class="form-control @error('customer_cccd') is-invalid @enderror"
                                        value="{{ old('customer_cccd') }}" placeholder="Nhập CCCD nếu có">

                                    @error('customer_cccd')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="customer_email"
                                        class="form-control @error('customer_email') is-invalid @enderror"
                                        value="{{ old('customer_email') }}" placeholder="email@example.com">

                                    @error('customer_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Địa chỉ</label>
                                    <input type="text" name="customer_address"
                                        class="form-control @error('customer_address') is-invalid @enderror"
                                        value="{{ old('customer_address') }}" placeholder="Nhập địa chỉ nếu có">

                                    @error('customer_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>

                        </div>

                        <div class="booking-form-card">

                            <h5>Thông tin đặt phòng</h5>

                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Hạng phòng <span class="text-danger">*</span></label>
                                    <select name="room_category_id" id="roomCategorySelect"
                                        class="form-select @error('room_category_id') is-invalid @enderror" required>

                                        <option value="">-- Chọn hạng phòng --</option>

                                        @foreach ($roomCategories as $roomCategory)
                                            <option value="{{ $roomCategory->id }}" data-price="{{ $roomCategory->price }}"
                                                @selected(old('room_category_id') == $roomCategory->id)>
                                                {{ $roomCategory->name }}
                                                -
                                                {{ number_format($roomCategory->price, 0, ',', '.') }}đ/đêm
                                            </option>
                                        @endforeach

                                    </select>

                                    @error('room_category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Ngày nhận phòng <span class="text-danger">*</span></label>
                                    <input type="date" name="check_in_date" id="checkInDate"
                                        class="form-control @error('check_in_date') is-invalid @enderror"
                                        value="{{ old('check_in_date') }}" required>

                                    @error('check_in_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Ngày trả phòng <span class="text-danger">*</span></label>
                                    <input type="date" name="check_out_date" id="checkOutDate"
                                        class="form-control @error('check_out_date') is-invalid @enderror"
                                        value="{{ old('check_out_date') }}" required>

                                    @error('check_out_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Người lớn <span class="text-danger">*</span></label>
                                    <input type="number" name="adult_count" id="adultCount"
                                        class="form-control @error('adult_count') is-invalid @enderror"
                                        value="{{ old('adult_count', 1) }}" min="1" required>

                                    @error('adult_count')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Trẻ em</label>
                                    <input type="number" name="child_count" id="childCount"
                                        class="form-control @error('child_count') is-invalid @enderror"
                                        value="{{ old('child_count', 0) }}" min="0">

                                    @error('child_count')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Số phòng <span class="text-danger">*</span></label>
                                    <input type="number" name="room_quantity" id="roomQuantity"
                                        class="form-control @error('room_quantity') is-invalid @enderror"
                                        value="{{ old('room_quantity', 1) }}" min="1" required>

                                    @error('room_quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3 adjacent-room-box" id="adjacentRoomBox">
                                    <label class="form-label d-block">Tùy chọn phòng</label>

                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="prefer_adjacent_rooms" value="1"
                                            id="preferAdjacentRooms" class="form-check-input"
                                            @checked(old('prefer_adjacent_rooms'))>

                                        <label for="preferAdjacentRooms" class="form-check-label">
                                            Ưu tiên phòng cạnh nhau
                                        </label>
                                    </div>

                                    <div class="booking-help-text">
                                        Chỉ áp dụng khi đặt từ 2 phòng trở lên.
                                    </div>
                                </div>

                            </div>

                        </div>

                        <div class="booking-form-card">

                            <h5>Thanh toán và ghi chú</h5>

                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Tiền cọc</label>
                                    <input type="number" name="deposit_amount" id="depositAmount"
                                        class="form-control @error('deposit_amount') is-invalid @enderror"
                                        value="{{ old('deposit_amount', 0) }}" min="0">

                                    @error('deposit_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="booking-help-text mt-1">
                                        Nếu nhập lớn hơn 0, trạng thái thanh toán sẽ là "Đã cọc".
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="booking-total-box">
                                        <div class="booking-help-text">Tổng tiền tạm tính</div>
                                        <strong id="estimatedTotalText">0đ</strong>
                                        <div class="booking-help-text mt-1" id="nightCountText">
                                            Chọn hạng phòng và ngày lưu trú để tính tiền.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Ghi chú</label>
                                    <textarea name="note" rows="4" class="form-control @error('note') is-invalid @enderror"
                                        placeholder="Ví dụ: khách muốn tầng thấp, đến muộn, cần hỗ trợ hành lý...">{{ old('note') }}</textarea>

                                    @error('note')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="col-lg-4">

                        <div class="booking-form-card">

                            <h5>Tóm tắt xử lý</h5>

                            <p class="booking-help-text">
                                Khi bấm tạo booking, hệ thống sẽ:
                            </p>

                            <ol class="booking-help-text ps-3">
                                <li>Kiểm tra phòng trống theo hạng phòng và ngày lưu trú.</li>
                                <li>Nếu đặt nhiều phòng và có chọn cạnh nhau, hệ thống ưu tiên cùng tầng và liền số.</li>
                                <li>Nếu đủ phòng, tạo booking và gán phòng thật.</li>
                                <li>Đổi trạng thái phòng sang <strong>reserved</strong>.</li>
                            </ol>

                            <hr>

                            <button type="submit" class="btn btn-gold w-100">
                                Tạo booking
                            </button>

                            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary w-100 mt-2">
                                Hủy
                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

    <script>
        const roomCategorySelect = document.getElementById('roomCategorySelect');
        const checkInDate = document.getElementById('checkInDate');
        const checkOutDate = document.getElementById('checkOutDate');
        const roomQuantity = document.getElementById('roomQuantity');
        const adjacentRoomBox = document.getElementById('adjacentRoomBox');
        const preferAdjacentRooms = document.getElementById('preferAdjacentRooms');
        const estimatedTotalText = document.getElementById('estimatedTotalText');
        const nightCountText = document.getElementById('nightCountText');

        function formatMoney(value) {
            return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
        }

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

        function setMinDates() {
            const today = formatDateInput(new Date());

            checkInDate.min = today;

            if (checkInDate.value) {
                checkOutDate.min = addDays(checkInDate.value, 1);
            }
        }

        function autoSetCheckoutDate() {
            if (!checkInDate.value) {
                return;
            }

            const minCheckoutDate = addDays(checkInDate.value, 1);

            checkOutDate.min = minCheckoutDate;

            if (!checkOutDate.value || checkOutDate.value <= checkInDate.value) {
                checkOutDate.value = minCheckoutDate;
            }
        }

        function calculateNightCount() {
            if (!checkInDate.value || !checkOutDate.value) {
                return 0;
            }

            const checkIn = new Date(checkInDate.value);
            const checkOut = new Date(checkOutDate.value);
            const diffTime = checkOut - checkIn;
            const diffDays = diffTime / (1000 * 60 * 60 * 24);

            return diffDays > 0 ? diffDays : 0;
        }

        function updateAdjacentRoomBox() {
            const quantity = parseInt(roomQuantity.value || 1);

            if (quantity >= 2) {
                adjacentRoomBox.style.display = 'block';
            } else {
                adjacentRoomBox.style.display = 'none';
                preferAdjacentRooms.checked = false;
            }
        }

        function updateEstimatedTotal() {
            const selectedOption = roomCategorySelect.options[roomCategorySelect.selectedIndex];
            const price = selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
            const quantity = parseInt(roomQuantity.value || 1);
            const nights = calculateNightCount();

            if (!price || !quantity || !nights) {
                estimatedTotalText.innerText = '0đ';
                nightCountText.innerText = 'Chọn hạng phòng và ngày lưu trú để tính tiền.';
                return;
            }

            const total = price * quantity * nights;

            estimatedTotalText.innerText = formatMoney(total);
            nightCountText.innerText = quantity + ' phòng x ' + nights + ' đêm';
        }

        function refreshBookingForm() {
            setMinDates();
            updateAdjacentRoomBox();
            updateEstimatedTotal();
        }

        checkInDate.addEventListener('change', function () {
            autoSetCheckoutDate();
            refreshBookingForm();
        });

        checkOutDate.addEventListener('change', refreshBookingForm);
        roomCategorySelect.addEventListener('change', refreshBookingForm);
        roomQuantity.addEventListener('input', refreshBookingForm);

        setMinDates();

        if (checkInDate.value) {
            autoSetCheckoutDate();
        }

        refreshBookingForm();
    </script>

@endsection