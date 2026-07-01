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

        .hourly-preview-box {
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            border-radius: 14px;
            padding: 14px;
        }

        .hourly-preview-box.warning {
            border-color: #facc15;
            background: #fefce8;
        }

        .hourly-preview-box.safe {
            border-color: #86efac;
            background: #f0fdf4;
        }

        .hourly-preview-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }


        .promotion-list {
            display: grid;
            gap: 10px;
        }

        .promotion-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 12px;
            background: #ffffff;
            transition: 0.15s ease;
        }

        .promotion-card:hover {
            border-color: #d4af37;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }

        .promotion-card .form-check-input {
            margin-top: 4px;
        }

        .promotion-code {
            font-weight: 800;
            letter-spacing: 0.03em;
            color: #111827;
        }

        .promotion-meta {
            font-size: 12px;
            color: #64748b;
        }

        .promotion-total-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 14px;
            margin-top: 8px;
        }

        .promotion-total-row strong {
            font-size: 16px;
        }

        .promotion-collapsible {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #f8fafc;
            overflow: hidden;
        }

        .promotion-collapsible > summary {
            list-style: none;
            cursor: pointer;
            padding: 13px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-weight: 800;
        }

        .promotion-collapsible > summary::-webkit-details-marker {
            display: none;
        }

        .promotion-collapsible-body {
            border-top: 1px solid #e5e7eb;
            padding: 14px;
            background: #fff;
        }

        .promotion-selected-hint {
            display: block;
            margin-top: 2px;
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
        }


        @media (max-width: 767px) {
            .hourly-preview-grid {
                grid-template-columns: 1fr;
            }
        }

        .hourly-preview-item span {
            display: block;
            font-size: 12px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .hourly-preview-item strong {
            display: block;
            font-size: 14px;
            color: #111827;
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

                    <strong>Không thể tạo booking:</strong>

                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

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
                                    <label class="form-label">Hình thức tạo booking <span
                                            class="text-danger">*</span></label>

                                    <select name="booking_mode" id="bookingMode" class="form-select" required>
                                        <option value="advance" {{ old('booking_mode', 'advance') == 'advance' ? 'selected' : '' }}>
                                            Đặt trước
                                        </option>

                                        <option value="walk_in" {{ old('booking_mode') == 'walk_in' ? 'selected' : '' }}>
                                            Ở ngay
                                        </option>
                                    </select>

                                    <div class="booking-help-text mt-1" id="bookingModeHelpText">
                                        Đặt trước giữ phòng theo giờ chuẩn 14:00 → 12:00.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Loại lưu trú <span class="text-danger">*</span></label>
                                    <select name="booking_type" id="bookingType" class="form-select" required>
                                        <option value="overnight" {{ old('booking_type', 'overnight') == 'overnight' ? 'selected' : '' }}>
                                            Qua đêm
                                        </option>

                                        <option value="hourly" {{ old('booking_type') == 'hourly' ? 'selected' : '' }}>
                                            Theo giờ
                                        </option>
                                    </select>

                                    <div class="booking-help-text mt-1" id="bookingTypeHelpText">
                                        Qua đêm giữ phòng theo giờ chuẩn 14:00 → 12:00. Khách có thể nhận từ 13:00 nếu phòng
                                        đã sẵn sàng.
                                    </div>
                                </div>

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
                                    <label class="form-label">Ngày nhận <span class="text-danger">*</span></label>
                                    <input type="date" name="check_in_date" id="checkInDate"
                                        class="form-control @error('check_in_date') is-invalid @enderror"
                                        value="{{ old('check_in_date') }}" required>

                                    @error('check_in_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3" id="checkInTimeBox">
                                    <label class="form-label">Giờ vào</label>
                                    <input type="text" name="check_in_time" id="checkInTime"
                                        class="form-control @error('check_in_time') is-invalid @enderror"
                                        value="{{ old('check_in_time', now('Asia/Ho_Chi_Minh')->format('H:i')) }}"
                                        placeholder="Ví dụ: 13:30">

                                    @error('check_in_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3" id="checkOutDateBox">
                                    <label class="form-label">Ngày trả <span class="text-danger">*</span></label>
                                    <input type="date" name="check_out_date" id="checkOutDate"
                                        class="form-control @error('check_out_date') is-invalid @enderror"
                                        value="{{ old('check_out_date') }}" required>

                                    @error('check_out_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-3 d-none" id="hourlyCheckOutTimeBox">
                                    <label class="form-label">Giờ ra dự kiến <span class="text-danger">*</span></label>

                                    <input type="text" name="check_out_time" id="hourlyCheckOutTime"
                                        class="form-control @error('check_out_time') is-invalid @enderror"
                                        value="{{ old('check_out_time') }}" placeholder="Ví dụ: 16:30">

                                    @error('check_out_time')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror

                                    <div class="booking-help-text mt-1">
                                        Nếu giờ ra nhỏ hơn giờ vào, hệ thống hiểu là trả phòng sang ngày hôm sau.
                                    </div>
                                </div>


                                <div class="col-md-12 d-none" id="hourlyPreviewWrapper">
                                    <div class="hourly-preview-box" id="hourlyPreviewBox">
                                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                                            <div>
                                                <strong>Dự kiến thuê theo giờ</strong>
                                                <div class="booking-help-text">
                                                    Hệ thống tự tính giờ trả và thời gian dọn phòng trước khi tạo booking.
                                                </div>
                                            </div>
                                            <span class="badge bg-primary" id="hourlyPreviewBadge">Đang tính</span>
                                        </div>

                                        <div class="hourly-preview-grid">

                                            <div class="small fw-semibold mb-2 d-none" id="hourlyPreviewStockText">
                                                Còn -- phòng trống trong khung giờ đã chọn.
                                            </div>
                                            <div class="hourly-preview-item">
                                                <span>Nhận phòng</span>
                                                <strong id="hourlyPreviewCheckIn">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Trả phòng dự kiến</span>
                                                <strong id="hourlyPreviewCheckOut">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Dọn phòng đến</span>
                                                <strong id="hourlyPreviewCleaningUntil">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Tiền phòng</span>
                                                <strong id="hourlyPreviewRoomFee">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Tổng tạm tính</span>
                                                <strong id="hourlyPreviewTotalFee">---</strong>
                                            </div>
                                        </div>

                                        <div class="booking-help-text mt-2" id="hourlyPreviewMessage">
                                            Chọn ngày, giờ vào và giờ ra để hệ thống tính tiền, thời gian dọn phòng và cảnh
                                            báo tồn kho.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 d-none" id="lowStockConfirmWrapper">
                                    <div class="alert alert-warning small mb-0">
                                        <div class="form-check">
                                            <input type="checkbox" name="confirm_low_stock" value="1" id="confirmLowStock"
                                                class="form-check-input" @checked(old('confirm_low_stock'))>

                                            <label for="confirmLowStock" class="form-check-label fw-semibold">
                                                Tôi xác nhận vẫn tạo booking ở ngay theo giờ dù hạng phòng này đang còn rất
                                                ít phòng trống.
                                            </label>
                                        </div>

                                        <div class="mt-1">
                                            Trường hợp cố nhận khách khi chỉ còn 1–2 phòng, rủi ro mất cơ hội bán phòng qua
                                            đêm thuộc quyết định vận hành của khách sạn.
                                        </div>
                                    </div>
                                </div>


                                <div class="col-md-12 d-none" id="walkInOvernightPolicyWrapper">
                                    <div class="hourly-preview-box safe" id="walkInOvernightPolicyBox">
                                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                                            <div>
                                                <strong>Dự kiến ở ngay qua đêm</strong>
                                                <div class="booking-help-text">
                                                    Hệ thống tự tính giờ trả phòng và phụ thu theo ca giờ vào.
                                                </div>
                                            </div>
                                            <span class="badge bg-success" id="walkInOvernightPolicyBadge">Đang tính</span>
                                        </div>

                                        <div class="hourly-preview-grid">
                                            <div class="hourly-preview-item">
                                                <span>Nhận phòng thực tế</span>
                                                <strong id="walkInPolicyCheckIn">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Trả phòng dự kiến</span>
                                                <strong id="walkInPolicyCheckOut">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Phụ thu theo ca</span>
                                                <strong id="walkInPolicyExtraFee">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Tiền phòng</span>
                                                <strong id="walkInPolicyBaseFee">---</strong>
                                            </div>

                                            <div class="hourly-preview-item">
                                                <span>Tổng tạm tính</span>
                                                <strong id="walkInPolicyTotalFee">---</strong>
                                            </div>
                                        </div>

                                        <div class="booking-help-text mt-2" id="walkInPolicyMessage">
                                            Chọn ngày nhận, giờ vào và hạng phòng để xem cách tính giá.
                                        </div>
                                    </div>
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

                                <div class="col-md-4">
                                    <label class="form-label">Phương thức thanh toán</label>
                                    <select name="payment_method" id="paymentMethod"
                                        class="form-select @error('payment_method') is-invalid @enderror">
                                        <option value="none" {{ old('payment_method', 'none') == 'none' ? 'selected' : '' }}>
                                            Chưa thu tiền
                                        </option>
                                        <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>
                                            Tiền mặt tại quầy
                                        </option>
                                        <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>
                                            Chuyển khoản tại quầy
                                        </option>
                                        <option value="vnpay" {{ old('payment_method') == 'vnpay' ? 'selected' : '' }}>
                                            Online VNPay
                                        </option>
                                    </select>

                                    @error('payment_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="booking-help-text mt-1">
                                        Có thể tạo booking chưa thu tiền, thu trực tiếp, hoặc chuyển khách sang VNPay.
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Kiểu thanh toán</label>
                                    <select name="payment_type" id="paymentType"
                                        class="form-select @error('payment_type') is-invalid @enderror">
                                        <option value="" {{ old('payment_type') === null ? 'selected' : '' }}>
                                            -- Chọn sau --
                                        </option>
                                        <option value="deposit_30" {{ old('payment_type') == 'deposit_30' ? 'selected' : '' }}>
                                            Thu cọc 30%
                                        </option>
                                        <option value="full_100" {{ old('payment_type') == 'full_100' ? 'selected' : '' }}>
                                            Thu đủ 100%
                                        </option>
                                        <option value="custom" {{ old('payment_type') == 'custom' ? 'selected' : '' }}>
                                            Nhập số tiền thực thu
                                        </option>
                                    </select>

                                    @error('payment_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="booking-help-text mt-1" id="paymentTypeHelp">
                                        Chọn phương thức thanh toán để hệ thống tự tính số tiền cần thu.
                                    </div>
                                </div>

                                <div class="col-md-4" id="customPaymentAmountBox">
                                    <label class="form-label">Số tiền thu thực tế</label>
                                    <input type="number" name="deposit_amount" id="depositAmount"
                                        class="form-control @error('deposit_amount') is-invalid @enderror"
                                        value="{{ old('deposit_amount', 0) }}" min="0" step="1000">

                                    @error('deposit_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    <div class="booking-help-text mt-1" id="paymentAmountHelp">
                                        Chỉ nhập khi chọn kiểu "Nhập số tiền thực thu".
                                    </div>
                                </div>

                                <div class="col-md-12">
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

                            <h5>Dịch vụ đặt trước</h5>

                            <p class="booking-help-text">
                                Lễ tân có thể thêm dịch vụ khách yêu cầu ngay khi tạo booking.
                            </p>

                            @foreach ($services as $index => $service)
                                <div class="border rounded p-2 mb-2 service-row" data-price="{{ $service->price }}">

                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="services[{{ $index }}][service_id]"
                                            value="{{ $service->id }}" class="form-check-input service-check"
                                            id="service{{ $service->id }}">

                                        <label for="service{{ $service->id }}" class="form-check-label">
                                            <strong>{{ $service->name }}</strong>
                                            -
                                            {{ number_format($service->price, 0, ',', '.') }}đ / {{ $service->unit }}
                                            <span
                                                class="badge bg-{{ ($service->service_group ?? '') == 'vehicle' ? 'dark' : (($service->type == 'minibar') ? 'warning text-dark' : 'primary') }}">
                                                {{ $service->group_label ?? ($service->type == 'minibar' ? 'Minibar' : 'Dịch vụ') }}
                                            </span>
                                        </label>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-4">
                                            <input type="number" name="services[{{ $index }}][quantity]"
                                                class="form-control form-control-sm service-quantity" value="1" min="1">
                                        </div>

                                        <div class="col-8">
                                            <input type="text" name="services[{{ $index }}][note]"
                                                class="form-control form-control-sm" placeholder="Ghi chú nếu có">
                                        </div>
                                    </div>

                                </div>
                            @endforeach

                            <div class="booking-total-box mt-3">
                                <div class="booking-help-text">Tổng dịch vụ đặt trước</div>
                                <strong id="serviceTotalText">0đ</strong>
                            </div>

                        </div>

                        <div class="booking-form-card">

                            <h5>Mã ưu đãi</h5>

                            <p class="booking-help-text">
                                Hệ thống có 4 loại mã: mã thường, mã sự kiện, mã điều kiện và mã hỗ trợ khách.
                                Trường hợp khách đến sớm, hạng phòng cũ chưa sẵn sàng, cần đổi phòng/đổi hạng kèm ưu đãi
                                thì chọn mã thuộc loại <strong>mã hỗ trợ khách</strong>, không tạo thêm loại riêng.
                            </p>

                            @php
                                $promotionTypeDisplayConfig = [
                                    'normal_discount' => [
                                        'label' => 'Mã thường',
                                        'badge' => 'bg-primary',
                                        'hint' => 'Mã phổ thông dùng cho giảm tiền hoặc tặng/giảm dịch vụ cơ bản.',
                                    ],
                                    'event_discount' => [
                                        'label' => 'Mã sự kiện',
                                        'badge' => 'bg-success',
                                        'hint' => 'Mã theo chiến dịch, mùa lễ, combo hoặc chương trình bán hàng.',
                                    ],
                                    'conditional_discount' => [
                                        'label' => 'Mã điều kiện',
                                        'badge' => 'bg-warning text-dark',
                                        'hint' => 'Mã chỉ áp dụng khi booking đạt điều kiện về tổng tiền, số đêm, số phòng hoặc lịch sử khách.',
                                    ],
                                    'support_discount' => [
                                        'label' => 'Mã hỗ trợ khách',
                                        'badge' => 'bg-danger',
                                        'hint' => 'Dùng cho nghiệp vụ hỗ trợ như khách đến sớm, phòng chưa sẵn sàng, đổi phòng/đổi hạng, khách chờ lâu hoặc phát sinh bất tiện.',
                                    ],
                                ];

                                $availablePromotionGroups = collect($availablePromotions ?? collect())->groupBy('promotion_type');
                            @endphp

                            @if (($availablePromotions ?? collect())->count() > 0)
                                <details class="promotion-collapsible" {{ !empty(old('promotion_codes', [])) ? 'open' : '' }}>
                                    <summary>
                                        <span>
                                            Có {{ ($availablePromotions ?? collect())->count() }} mã có thể áp dụng
                                            <span class="promotion-selected-hint" id="adminSelectedPromotionCountText">
                                                Chưa chọn mã nào
                                            </span>
                                        </span>
                                        <span class="badge bg-light text-dark border">Bấm để xem / chọn</span>
                                    </summary>

                                    <div class="promotion-collapsible-body">
                                        @foreach ($promotionTypeDisplayConfig as $promotionType => $typeConfig)
                                            @php
                                                $groupPromotions = $availablePromotionGroups->get($promotionType, collect());
                                            @endphp

                                            @if ($groupPromotions->count() > 0)
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                                        <div>
                                                            <div class="fw-bold">
                                                                {{ $typeConfig['label'] }}
                                                                <span class="badge {{ $typeConfig['badge'] }} ms-1">
                                                                    {{ $groupPromotions->count() }}
                                                                </span>
                                                            </div>
                                                            <div class="promotion-meta">
                                                                {{ $typeConfig['hint'] }}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="promotion-list">
                                                        @foreach ($groupPromotions as $promotion)
                                                            @php
                                                                $promotionDiscountText = $promotion->discount_type == 'percent'
                                                                    ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, ',', '.'), '0'), ',') . '%'
                                                                    : number_format((float) $promotion->discount_value, 0, ',', '.') . 'đ';

                                                                if ($promotion->discount_type == 'percent' && (float) $promotion->max_discount_amount > 0) {
                                                                    $promotionDiscountText .= ' - tối đa ' . number_format((float) $promotion->max_discount_amount, 0, ',', '.') . 'đ';
                                                                }


                                                                $promotionServiceOffersPayload = $promotion->serviceOffers
                                                                    ->map(function ($offer) {
                                                                        return [
                                                                            'service_id' => $offer->service_id,
                                                                            'service_name' => $offer->service->name ?? 'Dịch vụ',
                                                                            'service_unit' => $offer->service->unit ?? '',
                                                                            'service_price' => (float) ($offer->service->price ?? 0),
                                                                            'service_type' => $offer->service->type ?? 'service',
                                                                            'discount_type' => $offer->discount_type,
                                                                            'discount_value' => (float) $offer->discount_value,
                                                                            'quantity' => (int) $offer->quantity,
                                                                            'auto_add_service' => (bool) $offer->auto_add_service,
                                                                        ];
                                                                    })
                                                                    ->values();

                                                                $promotionServiceOffersJson = $promotionServiceOffersPayload->toJson(
                                                                    JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
                                                                );
                                                            @endphp

                                                            <label class="promotion-card mb-0">
                                                                <div class="form-check">
                                                                    <input type="checkbox"
                                                                        name="promotion_codes[]"
                                                                        value="{{ $promotion->code }}"
                                                                        class="form-check-input promotion-check"
                                                                        data-code="{{ $promotion->code }}"
                                                                        data-type="{{ $promotion->promotion_type }}"
                                                                        data-requires-note="{{ $promotion->requires_note || $promotion->promotion_type == 'support_discount' ? 1 : 0 }}"
                                                                        data-discount-type="{{ $promotion->discount_type }}"
                                                                        data-discount-value="{{ (float) $promotion->discount_value }}"
                                                                        data-max-discount="{{ (float) $promotion->max_discount_amount }}"
                                                                        data-service-offers="{{ e($promotionServiceOffersJson) }}"
                                                                        @checked(in_array($promotion->code, old('promotion_codes', [])))>

                                                                    <div class="ms-1">
                                                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                                                            <div>
                                                                                <div class="promotion-code">{{ $promotion->code }}</div>
                                                                                <div class="fw-semibold">{{ $promotion->name }}</div>
                                                                            </div>
                                                                            <span class="badge {{ $typeConfig['badge'] }}">{{ $typeConfig['label'] }}</span>
                                                                        </div>

                                                                        <div class="promotion-meta mt-1">
                                                                            Giảm {{ $promotionDiscountText }}
                                                                            @if ((float) $promotion->min_booking_amount > 0)
                                                                                · Đơn từ {{ number_format((float) $promotion->min_booking_amount, 0, ',', '.') }}đ
                                                                            @endif
                                                                            @if ((int) $promotion->min_nights > 0)
                                                                                · Từ {{ (int) $promotion->min_nights }} đêm
                                                                            @endif
                                                                            @if ((int) $promotion->min_rooms > 0)
                                                                                · Từ {{ (int) $promotion->min_rooms }} phòng
                                                                            @endif
                                                                            @if ($promotion->requires_note || $promotion->promotion_type == 'support_discount')
                                                                                · Cần nhập lý do
                                                                            @endif
                                                                        </div>

                                                                        @if ($promotion->serviceOffers->count() > 0)
                                                                            <div class="promotion-meta mt-1 text-success">
                                                                                Dịch vụ ưu đãi:
                                                                                {{ $promotion->serviceOffers->map(fn ($offer) => $offer->offer_label)->implode(' · ') }}
                                                                            </div>
                                                                        @endif
                                                                        @if ($promotion->roomUpgradeOffers->count() > 0)
                                                                            <div class="promotion-meta mt-1 text-primary">
                                                                                Nâng hạng:
                                                                                {{ $promotion->roomUpgradeOffers->map(fn ($offer) => $offer->kind_label . ' - ' . $offer->cover_label)->implode(' · ') }}
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach

                                        <div class="mt-3">
                                            <label class="form-label">
                                                Lý do hỗ trợ nếu chọn mã hỗ trợ khách
                                                <span class="text-danger" id="promotionNoteRequiredMark" style="display:none">*</span>
                                            </label>
                                            <textarea name="promotion_note" id="promotionNote" rows="3" class="form-control"
                                                placeholder="Ví dụ: khách đến sớm nhưng hạng phòng chưa sẵn, hỗ trợ đổi hạng và tặng dịch vụ.">{{ old('promotion_note') }}</textarea>
                                            <div class="booking-help-text mt-1">
                                                Bắt buộc khi chọn mã hỗ trợ khách hoặc mã được cấu hình yêu cầu lý do.
                                            </div>
                                        </div>
                                    </div>
                                </details>

                                <div class="booking-total-box mt-3">
                                    <div class="promotion-total-row">
                                        <span>Tổng trước giảm</span>
                                        <strong id="promotionSubtotalText">0đ</strong>
                                    </div>
                                    <div class="promotion-total-row text-success">
                                        <span>Ưu đãi</span>
                                        <strong id="promotionDiscountText">-0đ</strong>
                                    </div>
                                    <div class="promotion-total-row text-danger">
                                        <span>Sau ưu đãi</span>
                                        <strong id="promotionFinalText">0đ</strong>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-light border mb-0">
                                    Chưa có mã ưu đãi đang hoạt động.
                                </div>
                            @endif

                        </div>
                        <div class="booking-form-card">

                            <h5>Tóm tắt xử lý</h5>

                            <p class="booking-help-text">
                                Khi bấm tạo booking, hệ thống sẽ:
                            </p>

                            <ol class="booking-help-text ps-3">
                                <li>Kiểm tra phòng trống theo hạng phòng và thời gian lưu trú.</li>
                                <li>Nếu đặt nhiều phòng và có chọn cạnh nhau, hệ thống ưu tiên cùng tầng và liền số.</li>
                                <li>Nếu đủ phòng, tạo booking và gán phòng thật.</li>
                                <li>Đặt trước: phòng chuyển sang <strong>reserved</strong>.</li>
                                <li>Ở ngay: booking chuyển sang <strong>checked_in</strong>, phòng chuyển sang
                                    <strong>occupied</strong>.
                                </li>
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
        document.addEventListener('DOMContentLoaded', function () {
            const bookingMode = document.getElementById('bookingMode');
            const bookingModeHelpText = document.getElementById('bookingModeHelpText');
            const bookingType = document.getElementById('bookingType');
            const bookingTypeHelpText = document.getElementById('bookingTypeHelpText');
            const roomCategorySelect = document.getElementById('roomCategorySelect');

            const checkInDate = document.getElementById('checkInDate');
            const checkOutDate = document.getElementById('checkOutDate');
            const checkOutDateBox = document.getElementById('checkOutDateBox');

            const checkInTime = document.getElementById('checkInTime');
            const checkInTimeBox = document.getElementById('checkInTimeBox');

            const hourlyCheckOutTime = document.getElementById('hourlyCheckOutTime');
            const hourlyCheckOutTimeBox = document.getElementById('hourlyCheckOutTimeBox');

            const lowStockConfirmWrapper = document.getElementById('lowStockConfirmWrapper');
            const confirmLowStock = document.getElementById('confirmLowStock');

            const roomQuantity = document.getElementById('roomQuantity');
            const adjacentRoomBox = document.getElementById('adjacentRoomBox');
            const preferAdjacentRooms = document.getElementById('preferAdjacentRooms');

            const estimatedTotalText = document.getElementById('estimatedTotalText');
            const nightCountText = document.getElementById('nightCountText');

            const serviceRows = document.querySelectorAll('.service-row');
            const serviceTotalText = document.getElementById('serviceTotalText');

            const promotionChecks = document.querySelectorAll('.promotion-check');
            const promotionSubtotalText = document.getElementById('promotionSubtotalText');
            const promotionDiscountText = document.getElementById('promotionDiscountText');
            const promotionFinalText = document.getElementById('promotionFinalText');

            const paymentMethod = document.getElementById('paymentMethod');
            const paymentType = document.getElementById('paymentType');
            const depositAmount = document.getElementById('depositAmount');
            const customPaymentAmountBox = document.getElementById('customPaymentAmountBox');
            const paymentTypeHelp = document.getElementById('paymentTypeHelp');
            const paymentAmountHelp = document.getElementById('paymentAmountHelp');

            const hourlyPreviewWrapper = document.getElementById('hourlyPreviewWrapper');
            const hourlyPreviewBox = document.getElementById('hourlyPreviewBox');
            const hourlyPreviewBadge = document.getElementById('hourlyPreviewBadge');
            const hourlyPreviewCheckIn = document.getElementById('hourlyPreviewCheckIn');
            const hourlyPreviewCheckOut = document.getElementById('hourlyPreviewCheckOut');
            const hourlyPreviewCleaningUntil = document.getElementById('hourlyPreviewCleaningUntil');
            const hourlyPreviewRoomFee = document.getElementById('hourlyPreviewRoomFee');
            const hourlyPreviewTotalFee = document.getElementById('hourlyPreviewTotalFee');
            const hourlyPreviewMessage = document.getElementById('hourlyPreviewMessage');
            const hourlyPreviewStockText = document.getElementById('hourlyPreviewStockText');

            const walkInOvernightPolicyWrapper = document.getElementById('walkInOvernightPolicyWrapper');
            const walkInOvernightPolicyBox = document.getElementById('walkInOvernightPolicyBox');
            const walkInOvernightPolicyBadge = document.getElementById('walkInOvernightPolicyBadge');
            const walkInPolicyCheckIn = document.getElementById('walkInPolicyCheckIn');
            const walkInPolicyCheckOut = document.getElementById('walkInPolicyCheckOut');
            const walkInPolicyExtraFee = document.getElementById('walkInPolicyExtraFee');
            const walkInPolicyBaseFee = document.getElementById('walkInPolicyBaseFee');
            const walkInPolicyTotalFee = document.getElementById('walkInPolicyTotalFee');
            const walkInPolicyMessage = document.getElementById('walkInPolicyMessage');

            const cleaningBufferMinutes = 60;
            const hourlyInventoryCheckUrl = "{{ route('admin.bookings.hourly-inventory-check') }}";

            function formatMoney(value) {
                return new Intl.NumberFormat('vi-VN').format(Math.max(0, Number(value || 0))) + 'đ';
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

            function parseDateTime(dateValue, timeValue) {
                if (!dateValue || !timeValue) {
                    return null;
                }

                const parts = timeValue.split(':');
                const hour = String(parts[0] || '00').padStart(2, '0');
                const minute = String(parts[1] || '00').padStart(2, '0');

                return new Date(`${dateValue}T${hour}:${minute}:00`);
            }

            function formatDateTimeVn(date) {
                if (!date || Number.isNaN(date.getTime())) {
                    return '---';
                }

                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                const hour = String(date.getHours()).padStart(2, '0');
                const minute = String(date.getMinutes()).padStart(2, '0');

                return `${day}/${month}/${year} ${hour}:${minute}`;
            }

            function getSelectedRoomPrice() {
                if (!roomCategorySelect) {
                    return 0;
                }

                const selectedOption = roomCategorySelect.options[roomCategorySelect.selectedIndex];

                return selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
            }

            function getRoomQuantity() {
                return Math.max(1, parseInt(roomQuantity.value || 1));
            }

            function calculateServiceTotal() {
                let total = 0;

                serviceRows.forEach(function (row) {
                    const checkbox = row.querySelector('.service-check');
                    const quantityInput = row.querySelector('.service-quantity');

                    if (!checkbox || !quantityInput || !checkbox.checked) {
                        return;
                    }

                    const price = parseFloat(row.dataset.price || 0);
                    const quantity = Math.max(1, parseInt(quantityInput.value || 1));

                    total += price * quantity;
                });

                if (serviceTotalText) {
                    serviceTotalText.innerText = formatMoney(total);
                }

                return total;
            }

            function getSelectedServiceQuantity(serviceId) {
                let quantity = 0;

                serviceRows.forEach(function (row) {
                    const checkbox = row.querySelector('.service-check');
                    const quantityInput = row.querySelector('.service-quantity');

                    if (!checkbox || !quantityInput || !checkbox.checked) {
                        return;
                    }

                    if (String(checkbox.value) === String(serviceId)) {
                        quantity += Math.max(1, parseInt(quantityInput.value || 1));
                    }
                });

                return quantity;
            }

            function parseServiceOffers(checkbox) {
                try {
                    return JSON.parse(checkbox.dataset.serviceOffers || '[]');
                } catch (error) {
                    return [];
                }
            }

            function calculatePromotionTotals(roomTotal, serviceTotal) {
                let autoServiceTotal = 0;
                let serviceDiscount = 0;

                promotionChecks.forEach(function (checkbox) {
                    if (!checkbox.checked) {
                        return;
                    }

                    parseServiceOffers(checkbox).forEach(function (offer) {
                        const price = parseFloat(offer.service_price || 0);
                        const offerQuantity = Math.max(1, parseInt(offer.quantity || 1));
                        let applicableQuantity = Math.min(offerQuantity, getSelectedServiceQuantity(offer.service_id));
                        const missingQuantity = Math.max(0, offerQuantity - applicableQuantity);

                        if (missingQuantity > 0 && offer.auto_add_service) {
                            autoServiceTotal += price * missingQuantity;
                            applicableQuantity += missingQuantity;
                        }

                        if (applicableQuantity <= 0 || price <= 0) {
                            return;
                        }

                        const originalAmount = price * applicableQuantity;
                        let discountAmount = 0;

                        if (offer.discount_type === 'percent') {
                            discountAmount = Math.round(originalAmount * parseFloat(offer.discount_value || 0) / 100);
                        } else {
                            discountAmount = parseFloat(offer.discount_value || 0) * applicableQuantity;
                        }

                        serviceDiscount += Math.min(Math.max(0, discountAmount), originalAmount);
                    });
                });

                const subtotal = roomTotal + serviceTotal + autoServiceTotal;
                let moneyDiscount = 0;

                promotionChecks.forEach(function (checkbox) {
                    if (!checkbox.checked) {
                        return;
                    }

                    const discountType = checkbox.dataset.discountType;
                    const discountValue = parseFloat(checkbox.dataset.discountValue || 0);
                    const maxDiscount = parseFloat(checkbox.dataset.maxDiscount || 0);
                    let amount = 0;

                    if (discountType === 'percent') {
                        amount = Math.round(subtotal * discountValue / 100);

                        if (maxDiscount > 0) {
                            amount = Math.min(amount, maxDiscount);
                        }
                    } else {
                        amount = discountValue;
                    }

                    moneyDiscount += Math.max(0, amount);
                });

                const totalDiscount = Math.min(subtotal, moneyDiscount + serviceDiscount);

                if (promotionSubtotalText) {
                    promotionSubtotalText.innerText = formatMoney(subtotal);
                }

                if (promotionDiscountText) {
                    promotionDiscountText.innerText = '-' + formatMoney(totalDiscount);
                }

                if (promotionFinalText) {
                    promotionFinalText.innerText = formatMoney(Math.max(0, subtotal - totalDiscount));
                }

                return {
                    subtotal: subtotal,
                    totalDiscount: totalDiscount,
                    finalTotal: Math.max(0, subtotal - totalDiscount),
                };
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

            function calculateWalkInHourlyPrice(price, quantity, durationMinutes) {
                const durationHours = Math.max(1, Math.ceil(durationMinutes / 60));

                const baseHours = 2;
                const basePercent = 0.5;
                const extraPercentPerHour = 0.1;
                const capPercent = 0.8;

                let chargedPercent = basePercent;
                let policyText = 'Block tối thiểu 2 giờ đầu = 50% giá qua đêm.';

                if (durationHours > baseHours) {
                    chargedPercent = basePercent + ((durationHours - baseHours) * extraPercentPerHour);

                    if (durationHours > 12) {
                        chargedPercent = 1;
                        policyText = 'Vượt quá 12 giờ, tính 100% giá qua đêm.';
                    } else if (chargedPercent >= capPercent) {
                        chargedPercent = capPercent;
                        policyText = 'Đạt ngưỡng 80% giá qua đêm, áp dụng giá nửa ngày/day-use.';
                    } else {
                        policyText = '2 giờ đầu = 50% giá qua đêm, mỗi giờ tiếp theo +10%.';
                    }
                }

                const amount = Math.round(price * quantity * chargedPercent);

                return {
                    durationHours,
                    chargedPercent,
                    amount,
                    policyText,
                };
            }

            function calculateWalkInOvernightPolicy() {
                const checkInDateTime = parseDateTime(checkInDate.value, checkInTime.value);
                const price = getSelectedRoomPrice();
                const quantity = getRoomQuantity();

                if (!checkInDateTime || Number.isNaN(checkInDateTime.getTime())) {
                    return null;
                }

                const hour = checkInDateTime.getHours();
                let extraPercent = 0;
                let policyText = '';
                let checkOutDateTime = new Date(checkInDateTime.getTime());

                if (hour >= 0 && hour < 6) {
                    checkOutDateTime.setHours(12, 0, 0, 0);
                    extraPercent = 0;
                    policyText = 'Khách vào từ 00:00 đến trước 06:00, tính 1 đêm, trả phòng 12:00 cùng ngày.';
                } else if (hour >= 6 && hour < 9) {
                    checkOutDateTime.setDate(checkOutDateTime.getDate() + 1);
                    checkOutDateTime.setHours(12, 0, 0, 0);
                    extraPercent = 0.5;
                    policyText = 'Khách vào từ 06:00 đến trước 09:00, tính 1 đêm và phụ thu nhận phòng sớm 50%, trả phòng 12:00 ngày hôm sau.';
                } else if (hour >= 9 && hour < 12) {
                    checkOutDateTime.setDate(checkOutDateTime.getDate() + 1);
                    checkOutDateTime.setHours(12, 0, 0, 0);
                    extraPercent = 0.3;
                    policyText = 'Khách vào từ 09:00 đến trước 12:00, tính 1 đêm và phụ thu nhận phòng sớm 30%, trả phòng 12:00 ngày hôm sau.';
                } else if (hour >= 12 && hour < 13) {
                    checkOutDateTime.setDate(checkOutDateTime.getDate() + 1);
                    checkOutDateTime.setHours(12, 0, 0, 0);
                    extraPercent = 0;
                    policyText = 'Khách đến từ 12:00 đến trước 13:00 là giai đoạn phòng vừa trả/dọn. Nếu phòng đang dọn, lễ tân gửi yêu cầu dọn ưu tiên; chỉ cho nhận phòng khi phòng đã sẵn sàng. Không phụ thu tự động, trả phòng 12:00 ngày hôm sau.';
                } else if (hour >= 13 && hour < 14) {
                    checkOutDateTime.setDate(checkOutDateTime.getDate() + 1);
                    checkOutDateTime.setHours(12, 0, 0, 0);
                    extraPercent = 0;
                    policyText = 'Khách vào từ 13:00 đến trước 14:00 thuộc khung check-in linh hoạt. Cho nhận phòng nếu phòng đã sẵn sàng; nếu chưa sẵn sàng thì yêu cầu buồng phòng ưu tiên dọn. Không phụ thu tự động, trả phòng 12:00 ngày hôm sau.';
                } else {
                    checkOutDateTime.setDate(checkOutDateTime.getDate() + 1);
                    checkOutDateTime.setHours(12, 0, 0, 0);
                    extraPercent = 0;
                    policyText = 'Khách vào từ 14:00 trở đi, tính 1 đêm tiêu chuẩn, trả phòng 12:00 ngày hôm sau.';
                }

                const baseTotal = price * quantity;
                const extraFee = Math.round(baseTotal * extraPercent);
                const total = baseTotal + extraFee;

                return {
                    checkInDateTime,
                    checkOutDateTime,
                    nightCount: 1,
                    extraPercent,
                    baseTotal,
                    extraFee,
                    total,
                    policyText,
                };
            }

            function setMinDates() {
                const today = formatDateInput(new Date());

                if (checkInDate) {
                    checkInDate.min = today;
                }

                if (!checkOutDate) {
                    return;
                }

                if (bookingMode.value === 'advance') {
                    checkOutDate.min = checkInDate.value ? addDays(checkInDate.value, 1) : today;
                    return;
                }

                checkOutDate.min = checkInDate.value || today;
            }

            function autoSetCheckoutDate() {
                if (!checkInDate.value || !checkOutDate) {
                    return;
                }

                if (bookingMode.value === 'advance') {
                    const minCheckoutDate = addDays(checkInDate.value, 1);

                    checkOutDate.min = minCheckoutDate;

                    if (!checkOutDate.value || checkOutDate.value <= checkInDate.value) {
                        checkOutDate.value = minCheckoutDate;
                    }

                    return;
                }

                checkOutDate.required = false;
                checkOutDate.value = checkInDate.value;
                checkOutDate.min = checkInDate.value;
            }

            function updateBookingTypeUi() {
                const isAdvance = bookingMode.value === 'advance';
                const isWalkIn = bookingMode.value === 'walk_in';
                const isHourly = bookingType.value === 'hourly';
                const hourlyOption = bookingType.querySelector('option[value="hourly"]');

                if (isAdvance) {
                    bookingType.value = 'overnight';

                    if (hourlyOption) {
                        hourlyOption.disabled = true;
                    }

                    checkInTimeBox.classList.add('d-none');
                    hourlyCheckOutTimeBox.classList.add('d-none');
                    hourlyPreviewWrapper.classList.add('d-none');
                    walkInOvernightPolicyWrapper.classList.add('d-none');

                    checkOutDateBox.classList.remove('d-none');
                    checkOutDate.required = true;

                    if (hourlyCheckOutTime) {
                        hourlyCheckOutTime.required = false;
                    }

                    if (lowStockConfirmWrapper) {
                        lowStockConfirmWrapper.classList.add('d-none');
                    }

                    if (confirmLowStock) {
                        confirmLowStock.checked = false;
                    }

                    bookingModeHelpText.innerText = 'Đặt trước giữ phòng theo giờ chuẩn 14:00 → 12:00.';
                    bookingTypeHelpText.innerText = 'Đặt trước luôn là booking qua đêm; khách có thể nhận từ 13:00 nếu phòng đã sẵn sàng, hệ thống giữ phòng theo mốc 14:00.';
                    return;
                }

                if (hourlyOption) {
                    hourlyOption.disabled = false;
                }

                if (isWalkIn && isHourly) {
                    checkInTimeBox.classList.remove('d-none');
                    hourlyCheckOutTimeBox.classList.remove('d-none');
                    hourlyPreviewWrapper.classList.remove('d-none');
                    walkInOvernightPolicyWrapper.classList.add('d-none');

                    checkOutDateBox.classList.add('d-none');
                    checkOutDate.required = false;

                    if (hourlyCheckOutTime) {
                        hourlyCheckOutTime.required = true;

                        if (!hourlyCheckOutTime.value && checkInTime.value) {
                            const parts = checkInTime.value.split(':');
                            const defaultOut = new Date();

                            defaultOut.setHours(parseInt(parts[0] || '0'), parseInt(parts[1] || '0'), 0, 0);
                            defaultOut.setHours(defaultOut.getHours() + 2);

                            const hour = String(defaultOut.getHours()).padStart(2, '0');
                            const minute = String(defaultOut.getMinutes()).padStart(2, '0');

                            hourlyCheckOutTime.value = `${hour}:${minute}`;
                        }
                    }

                    bookingModeHelpText.innerText = 'Ở ngay: hệ thống lấy giờ vào thực tế để tính giờ chiếm phòng.';
                    bookingTypeHelpText.innerText = 'Theo giờ linh hoạt: lễ tân chọn giờ ra, hệ thống tự tính tiền theo block 2 giờ đầu + giờ phát sinh.';
                    return;
                }

                checkInTimeBox.classList.remove('d-none');
                hourlyCheckOutTimeBox.classList.add('d-none');
                hourlyPreviewWrapper.classList.add('d-none');
                walkInOvernightPolicyWrapper.classList.remove('d-none');

                checkOutDateBox.classList.add('d-none');
                checkOutDate.required = false;

                if (hourlyCheckOutTime) {
                    hourlyCheckOutTime.required = false;
                }

                if (lowStockConfirmWrapper) {
                    lowStockConfirmWrapper.classList.add('d-none');
                }

                if (confirmLowStock) {
                    confirmLowStock.checked = false;
                }

                bookingModeHelpText.innerText = 'Ở ngay qua đêm: giờ nhận là giờ thực tế. Từ 12:00 có thể yêu cầu dọn ưu tiên; từ 13:00–14:00 cho nhận nếu phòng đã sẵn sàng.';
                bookingTypeHelpText.innerText = 'Qua đêm ở ngay: hệ thống tự tính giờ trả phòng theo 12:00 và chỉ phụ thu các ca vào quá sớm trước 12:00.';
            }

            function updateAdjacentRoomBox() {
                const quantity = parseInt(roomQuantity.value || 1);

                if (quantity >= 2 && bookingType.value === 'overnight') {
                    adjacentRoomBox.style.display = 'block';
                } else {
                    adjacentRoomBox.style.display = 'none';

                    if (preferAdjacentRooms) {
                        preferAdjacentRooms.checked = false;
                    }
                }
            }

            function resetHourlyStockText() {
                if (!hourlyPreviewStockText) {
                    return;
                }

                hourlyPreviewStockText.className = 'small fw-semibold mb-2 d-none';
                hourlyPreviewStockText.innerText = 'Còn -- phòng trống trong khung giờ đã chọn.';
            }

            function updateHourlyStockText(data, selectedAvailable) {
                if (!hourlyPreviewStockText) {
                    return;
                }

                hourlyPreviewStockText.classList.remove('d-none');

                if (data.blocked || selectedAvailable <= 0) {
                    hourlyPreviewStockText.className = 'small fw-semibold mb-2 text-danger';
                    hourlyPreviewStockText.innerText = 'Hết phòng trong khung giờ đã chọn.';
                    return;
                }

                if (selectedAvailable === 1) {
                    hourlyPreviewStockText.className = 'small fw-semibold mb-2 text-danger';
                    hourlyPreviewStockText.innerText = 'Chỉ còn 1 phòng trống trong khung giờ đã chọn.';
                    return;
                }

                if (selectedAvailable === 2) {
                    hourlyPreviewStockText.className = 'small fw-semibold mb-2 text-warning';
                    hourlyPreviewStockText.innerText = 'Còn 2 phòng trống trong khung giờ đã chọn.';
                    return;
                }

                hourlyPreviewStockText.className = 'small fw-semibold mb-2 text-success';
                hourlyPreviewStockText.innerText = 'Còn ' + selectedAvailable + ' phòng trống trong khung giờ đã chọn.';
            }

            function updateHourlyPreview() {
                if (!hourlyPreviewWrapper || bookingMode.value !== 'walk_in' || bookingType.value !== 'hourly') {
                    if (lowStockConfirmWrapper) {
                        lowStockConfirmWrapper.classList.add('d-none');
                    }

                    if (confirmLowStock) {
                        confirmLowStock.checked = false;
                    }

                    resetHourlyStockText();
                    return;
                }

                const checkInDateTime = parseDateTime(checkInDate.value, checkInTime.value);
                const checkOutDateTime = parseDateTime(checkInDate.value, hourlyCheckOutTime.value);

                hourlyPreviewBox.classList.remove('safe', 'warning');
                resetHourlyStockText();

                if (lowStockConfirmWrapper) {
                    lowStockConfirmWrapper.classList.add('d-none');
                }

                if (!checkInDateTime || Number.isNaN(checkInDateTime.getTime()) || !checkOutDateTime || Number.isNaN(checkOutDateTime.getTime())) {
                    hourlyPreviewCheckIn.innerText = '---';
                    hourlyPreviewCheckOut.innerText = '---';
                    hourlyPreviewCleaningUntil.innerText = '---';
                    hourlyPreviewRoomFee.innerText = '---';
                    hourlyPreviewTotalFee.innerText = '---';
                    hourlyPreviewBadge.className = 'badge bg-secondary';
                    hourlyPreviewBadge.innerText = 'Chưa đủ dữ liệu';
                    hourlyPreviewMessage.innerText = 'Chọn ngày, giờ vào và giờ ra để hệ thống tính tiền, thời gian dọn phòng và cảnh báo tồn kho.';
                    return;
                }

                if (checkOutDateTime.getTime() === checkInDateTime.getTime()) {
                    hourlyPreviewBadge.className = 'badge bg-danger';
                    hourlyPreviewBadge.innerText = 'Sai giờ';
                    hourlyPreviewMessage.innerText = 'Giờ ra phải khác giờ vào.';
                    return;
                }

                if (checkOutDateTime < checkInDateTime) {
                    checkOutDateTime.setDate(checkOutDateTime.getDate() + 1);
                }

                const durationMinutes = Math.ceil((checkOutDateTime - checkInDateTime) / (1000 * 60));

                if (durationMinutes < 30) {
                    hourlyPreviewBadge.className = 'badge bg-danger';
                    hourlyPreviewBadge.innerText = 'Quá ngắn';
                    hourlyPreviewMessage.innerText = 'Thời gian ở theo giờ phải tối thiểu 30 phút.';
                    return;
                }

                const cleaningUntil = new Date(checkOutDateTime.getTime());
                cleaningUntil.setMinutes(cleaningUntil.getMinutes() + cleaningBufferMinutes);

                const price = getSelectedRoomPrice();
                const quantity = getRoomQuantity();
                const serviceTotal = calculateServiceTotal();
                const hourlyPrice = calculateWalkInHourlyPrice(price, quantity, durationMinutes);

                hourlyPreviewCheckIn.innerText = formatDateTimeVn(checkInDateTime);
                hourlyPreviewCheckOut.innerText = formatDateTimeVn(checkOutDateTime);
                hourlyPreviewCleaningUntil.innerText = formatDateTimeVn(cleaningUntil);
                hourlyPreviewRoomFee.innerText = price > 0
                    ? formatMoney(hourlyPrice.amount) + ' (' + Math.round(hourlyPrice.chargedPercent * 100) + '% giá đêm, làm tròn ' + hourlyPrice.durationHours + ' giờ)'
                    : 'Chọn hạng phòng';
                hourlyPreviewTotalFee.innerText = price > 0
                    ? formatMoney(hourlyPrice.amount + serviceTotal)
                    : '---';

                if (!roomCategorySelect.value || !checkInDate.value || !checkInTime.value || !hourlyCheckOutTime.value || !roomQuantity.value) {
                    hourlyPreviewBox.classList.add('warning');
                    hourlyPreviewBadge.className = 'badge bg-warning text-dark';
                    hourlyPreviewBadge.innerText = 'Cần kiểm tra';
                    hourlyPreviewMessage.innerText = 'Chọn đủ hạng phòng, ngày nhận, giờ vào, giờ ra và số phòng để kiểm tra tồn kho.';
                    return;
                }

                hourlyPreviewBox.classList.add('warning');
                hourlyPreviewBadge.className = 'badge bg-warning text-dark';
                hourlyPreviewBadge.innerText = 'Đang kiểm tra...';
                hourlyPreviewMessage.innerText = 'Đang kiểm tra phòng trống thật trong khung giờ này.';

                fetch(hourlyInventoryCheckUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        room_category_id: roomCategorySelect.value,
                        check_in_date: checkInDate.value,
                        check_in_time: checkInTime.value,
                        check_out_time: hourlyCheckOutTime.value,
                        room_quantity: roomQuantity.value,
                    }),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Không kiểm tra được tồn kho.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        const selectedAvailable = Number(data.available_for_selected_period || 0);
                        const remainingOvernight = Number(data.remaining_after_hourly || 0);

                        hourlyPreviewMessage.innerText = data.message || 'Đã kiểm tra phòng trống theo khung giờ.';

                        if (data.check_in_at) {
                            hourlyPreviewCheckIn.innerText = data.check_in_at;
                        }

                        if (data.check_out_at) {
                            hourlyPreviewCheckOut.innerText = data.check_out_at;
                        }

                        if (data.occupied_until) {
                            hourlyPreviewCleaningUntil.innerText = data.occupied_until;
                        }

                        if (typeof data.room_fee !== 'undefined') {
                            hourlyPreviewRoomFee.innerText = formatMoney(data.room_fee)
                                + ' (' + Math.round((data.charged_percent || 0) * 100)
                                + '% giá đêm, làm tròn '
                                + (data.duration_hours || hourlyPrice.durationHours)
                                + ' giờ)';

                            hourlyPreviewTotalFee.innerText = formatMoney(Number(data.room_fee || 0) + serviceTotal);
                        }

                        updateHourlyStockText(data, selectedAvailable);

                        const mustConfirmLowStock = !data.blocked
                            && (
                                selectedAvailable === 1
                                || (data.affects_overnight && remainingOvernight <= 1)
                            );

                        if (mustConfirmLowStock && lowStockConfirmWrapper) {
                            lowStockConfirmWrapper.classList.remove('d-none');
                        } else if (lowStockConfirmWrapper) {
                            lowStockConfirmWrapper.classList.add('d-none');

                            if (confirmLowStock) {
                                confirmLowStock.checked = false;
                            }
                        }

                        if (data.blocked || selectedAvailable <= 0) {
                            hourlyPreviewBox.classList.remove('safe');
                            hourlyPreviewBox.classList.add('warning');
                            hourlyPreviewBadge.className = 'badge bg-danger';
                            hourlyPreviewBadge.innerText = 'Hết phòng';
                            return;
                        }

                        if (selectedAvailable === 1 || (data.affects_overnight && remainingOvernight <= 1)) {
                            hourlyPreviewBox.classList.remove('safe');
                            hourlyPreviewBox.classList.add('warning');
                            hourlyPreviewBadge.className = 'badge bg-danger';
                            hourlyPreviewBadge.innerText = 'Cấp thiết';
                            return;
                        }

                        if (selectedAvailable === 2 || (data.affects_overnight && remainingOvernight === 2)) {
                            hourlyPreviewBox.classList.remove('safe');
                            hourlyPreviewBox.classList.add('warning');
                            hourlyPreviewBadge.className = 'badge bg-warning text-dark';
                            hourlyPreviewBadge.innerText = 'Gần hết phòng';
                            return;
                        }

                        hourlyPreviewBox.classList.remove('warning');
                        hourlyPreviewBox.classList.add('safe');
                        hourlyPreviewBadge.className = 'badge bg-success';
                        hourlyPreviewBadge.innerText = 'An toàn';
                    })
                    .catch(function () {
                        hourlyPreviewBox.classList.remove('safe');
                        hourlyPreviewBox.classList.add('warning');
                        hourlyPreviewBadge.className = 'badge bg-danger';
                        hourlyPreviewBadge.innerText = 'Lỗi kiểm tra';
                        hourlyPreviewMessage.innerText = 'Không kiểm tra được tồn kho. Vui lòng thử lại hoặc bấm tạo để hệ thống kiểm tra lại ở backend.';
                    });
            }

            function updateWalkInOvernightPreview() {
                if (!walkInOvernightPolicyWrapper || bookingMode.value !== 'walk_in' || bookingType.value !== 'overnight') {
                    return;
                }

                const policy = calculateWalkInOvernightPolicy();

                walkInOvernightPolicyBox.classList.remove('safe', 'warning');

                if (!policy) {
                    walkInPolicyCheckIn.innerText = '---';
                    walkInPolicyCheckOut.innerText = '---';
                    walkInPolicyExtraFee.innerText = '---';
                    walkInPolicyBaseFee.innerText = '---';
                    walkInPolicyTotalFee.innerText = '---';
                    walkInOvernightPolicyBadge.className = 'badge bg-secondary';
                    walkInOvernightPolicyBadge.innerText = 'Chưa đủ dữ liệu';
                    walkInPolicyMessage.innerText = 'Chọn ngày nhận, giờ vào và hạng phòng để xem cách tính giá.';
                    return;
                }

                walkInPolicyCheckIn.innerText = formatDateTimeVn(policy.checkInDateTime);
                walkInPolicyCheckOut.innerText = formatDateTimeVn(policy.checkOutDateTime);
                walkInPolicyExtraFee.innerText = policy.extraPercent > 0
                    ? Math.round(policy.extraPercent * 100) + '% = ' + formatMoney(policy.extraFee)
                    : 'Không phụ thu';
                walkInPolicyBaseFee.innerText = formatMoney(policy.baseTotal) + ' (1 đêm)';
                walkInPolicyTotalFee.innerText = formatMoney(policy.total + calculateServiceTotal());

                walkInOvernightPolicyBox.classList.add(policy.extraPercent > 0 ? 'warning' : 'safe');
                walkInOvernightPolicyBadge.className = policy.extraPercent > 0
                    ? 'badge bg-warning text-dark'
                    : 'badge bg-success';
                walkInOvernightPolicyBadge.innerText = policy.extraPercent > 0 ? 'Có phụ thu' : 'Tiêu chuẩn';
                walkInPolicyMessage.innerText = policy.policyText;
            }

            function updateEstimatedTotal() {
                const serviceTotal = calculateServiceTotal();
                const price = getSelectedRoomPrice();
                const quantity = getRoomQuantity();

                let roomTotal = 0;
                let summaryText = 'Chọn hạng phòng và thời gian lưu trú để tính tiền.';

                if (price > 0) {
                    if (bookingMode.value === 'walk_in' && bookingType.value === 'hourly') {
                        const checkInDateTime = parseDateTime(checkInDate.value, checkInTime.value);
                        const checkOutDateTime = parseDateTime(checkInDate.value, hourlyCheckOutTime.value);

                        if (checkInDateTime && checkOutDateTime && !Number.isNaN(checkInDateTime.getTime()) && !Number.isNaN(checkOutDateTime.getTime())) {
                            if (checkOutDateTime < checkInDateTime) {
                                checkOutDateTime.setDate(checkOutDateTime.getDate() + 1);
                            }

                            const durationMinutes = Math.ceil((checkOutDateTime - checkInDateTime) / (1000 * 60));
                            const hourlyPrice = calculateWalkInHourlyPrice(price, quantity, durationMinutes);

                            roomTotal = hourlyPrice.amount;
                            summaryText = quantity
                                + ' phòng x ở ngay theo giờ, làm tròn '
                                + hourlyPrice.durationHours
                                + ' giờ x '
                                + Math.round(hourlyPrice.chargedPercent * 100)
                                + '% giá/đêm';
                        }
                    } else if (bookingMode.value === 'walk_in' && bookingType.value === 'overnight') {
                        const policy = calculateWalkInOvernightPolicy();

                        if (policy) {
                            roomTotal = policy.total;
                            summaryText = quantity + ' phòng x 1 đêm';

                            if (policy.extraPercent > 0) {
                                summaryText += ' + phụ thu nhận phòng sớm ' + Math.round(policy.extraPercent * 100) + '%';
                            }
                        }
                    } else {
                        const nights = calculateNightCount();

                        if (nights > 0) {
                            roomTotal = price * quantity * nights;
                            summaryText = quantity + ' phòng x ' + nights + ' đêm';
                        }
                    }
                }

                const promotionTotals = calculatePromotionTotals(roomTotal, serviceTotal);
                const total = promotionTotals.finalTotal;

                estimatedTotalText.innerText = formatMoney(total);
                updatePaymentUi(total);

                if (roomTotal <= 0 && serviceTotal > 0) {
                    nightCountText.innerText = 'Đã cộng dịch vụ đặt trước. Chọn hạng phòng và thời gian lưu trú để tính tiền phòng.';
                } else {
                    nightCountText.innerText = summaryText;
                }
            }

            function updatePaymentUi(total) {
                if (!paymentMethod || !paymentType || !depositAmount) {
                    return;
                }

                const method = paymentMethod.value || 'none';
                const type = paymentType.value || '';
                const customOption = paymentType.querySelector('option[value="custom"]');
                const deposit30 = Math.round(Number(total || 0) * 0.3);
                const full100 = Math.max(0, Number(total || 0));

                if (method === 'none') {
                    paymentType.value = '';
                    paymentType.disabled = true;
                    depositAmount.disabled = true;
                    depositAmount.value = 0;

                    if (customPaymentAmountBox) {
                        customPaymentAmountBox.classList.add('d-none');
                    }

                    if (paymentTypeHelp) {
                        paymentTypeHelp.innerText = 'Booking sẽ được tạo ở trạng thái chưa thanh toán.';
                    }

                    return;
                }

                paymentType.disabled = false;

                if (!paymentType.value) {
                    paymentType.value = 'deposit_30';
                }

                if (method === 'vnpay' && customOption) {
                    customOption.disabled = true;

                    if (paymentType.value === 'custom') {
                        paymentType.value = 'deposit_30';
                    }
                } else if (customOption) {
                    customOption.disabled = false;
                }

                const activeType = paymentType.value;
                const isCustom = activeType === 'custom' && method !== 'vnpay';

                if (customPaymentAmountBox) {
                    customPaymentAmountBox.classList.toggle('d-none', !isCustom);
                }

                depositAmount.disabled = !isCustom;

                if (!isCustom) {
                    depositAmount.value = 0;
                }

                if (paymentTypeHelp) {
                    if (method === 'vnpay') {
                        paymentTypeHelp.innerText = activeType === 'full_100'
                            ? 'Sau khi tạo booking, hệ thống sẽ chuyển sang VNPay để khách thanh toán đủ ' + formatMoney(full100) + '.'
                            : 'Sau khi tạo booking, hệ thống sẽ chuyển sang VNPay để khách đặt cọc 30% khoảng ' + formatMoney(deposit30) + '.';
                    } else if (activeType === 'full_100') {
                        paymentTypeHelp.innerText = 'Ghi nhận thu đủ số còn lại: ' + formatMoney(full100) + '.';
                    } else if (activeType === 'custom') {
                        paymentTypeHelp.innerText = 'Lễ tân nhập đúng số tiền khách thực trả tại quầy.';
                    } else {
                        paymentTypeHelp.innerText = 'Ghi nhận cọc 30% khoảng ' + formatMoney(deposit30) + '.';
                    }
                }

                if (paymentAmountHelp) {
                    paymentAmountHelp.innerText = isCustom
                        ? 'Số tiền nhập không được lớn hơn tổng tiền sau giảm giá.'
                        : 'Ô này chỉ bật khi chọn kiểu nhập số tiền thực thu.';
                }
            }

            function refreshBookingForm() {
                updateBookingTypeUi();
                setMinDates();
                updateAdjacentRoomBox();
                updateHourlyPreview();
                updateWalkInOvernightPreview();
                updateEstimatedTotal();
            }

            if (typeof flatpickr !== 'undefined' && checkInTime) {
                flatpickr(checkInTime, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                    minuteIncrement: 1,
                    locale: 'vn',
                });
            }

            if (typeof flatpickr !== 'undefined' && hourlyCheckOutTime) {
                flatpickr(hourlyCheckOutTime, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                    minuteIncrement: 1,
                    locale: 'vn',
                });
            }

            bookingMode.addEventListener('change', function () {
                autoSetCheckoutDate();
                refreshBookingForm();
            });

            bookingType.addEventListener('change', function () {
                autoSetCheckoutDate();
                refreshBookingForm();
            });

            checkInDate.addEventListener('change', function () {
                autoSetCheckoutDate();
                refreshBookingForm();
            });

            checkOutDate.addEventListener('change', refreshBookingForm);
            checkInTime.addEventListener('change', refreshBookingForm);

            if (hourlyCheckOutTime) {
                hourlyCheckOutTime.addEventListener('change', refreshBookingForm);
            }

            roomCategorySelect.addEventListener('change', refreshBookingForm);
            roomQuantity.addEventListener('input', refreshBookingForm);

            if (paymentMethod) {
                paymentMethod.addEventListener('change', refreshBookingForm);
            }

            if (paymentType) {
                paymentType.addEventListener('change', refreshBookingForm);
            }

            serviceRows.forEach(function (row) {
                const checkbox = row.querySelector('.service-check');
                const quantityInput = row.querySelector('.service-quantity');

                if (checkbox) {
                    checkbox.addEventListener('change', refreshBookingForm);
                }

                if (quantityInput) {
                    quantityInput.addEventListener('input', refreshBookingForm);
                    quantityInput.addEventListener('change', refreshBookingForm);
                }
            });

            setMinDates();

            if (checkInDate.value) {
                autoSetCheckoutDate();
            }

            refreshBookingForm();


            function updateAdminSelectedPromotionCountText() {
                const text = document.getElementById('adminSelectedPromotionCountText');
                if (!text) {
                    return;
                }

                const selected = Array.from(document.querySelectorAll('.promotion-check:checked'))
                    .map(function (checkbox) {
                        return checkbox.dataset.code || checkbox.value;
                    });

                text.innerText = selected.length > 0
                    ? 'Đang chọn: ' + selected.join(', ')
                    : 'Chưa chọn mã nào';
            }

            function hasRequiredNotePromotion() {
                return Array.from(document.querySelectorAll('.promotion-check:checked')).some(function (checkbox) {
                    return checkbox.dataset.requiresNote === '1';
                });
            }

            function updatePromotionNoteRequirement() {
                const noteInput = document.getElementById('promotionNote');
                const requiredMark = document.getElementById('promotionNoteRequiredMark');
                const required = hasRequiredNotePromotion();

                if (noteInput) {
                    noteInput.required = required;
                }

                if (requiredMark) {
                    requiredMark.style.display = required ? 'inline' : 'none';
                }
            }

            const bookingForm = document.querySelector('form');

            if (bookingForm) {
                bookingForm.addEventListener('submit', function (event) {
                    const noteInput = document.getElementById('promotionNote');

                    if (hasRequiredNotePromotion() && noteInput && noteInput.value.trim() === '') {
                        event.preventDefault();
                        noteInput.focus();
                        alert('Vui lòng nhập lý do khi chọn mã hỗ trợ khách.');
                    }
                });
            }

            updateAdminSelectedPromotionCountText();
            updatePromotionNoteRequirement();

            promotionChecks.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    updateAdminSelectedPromotionCountText();
                    updatePromotionNoteRequirement();
                    refreshBookingForm();
                });
            });

        });
    </script>

@endsection