@extends('layouts.admin')

@section('title', 'Chi tiết đặt phòng')

@section('content')

    @php
        $bookingStatusLabels = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'checked_in' => 'Đã nhận phòng',
            'checked_out' => 'Đã trả phòng',
            'cancelled' => 'Đã hủy',
        ];

        $bookingStatusClasses = [
            'pending' => 'booking-status-pending',
            'confirmed' => 'booking-status-confirmed',
            'checked_in' => 'booking-status-checked-in',
            'checked_out' => 'booking-status-checked-out',
            'cancelled' => 'booking-status-cancelled',
        ];

        $paymentStatusLabels = [
            'unpaid' => 'Chưa thanh toán',
            'partial' => 'Đã cọc',
            'paid' => 'Đã thanh toán',
            'refunded' => 'Đã hoàn tiền',
        ];

        $paymentStatusClasses = [
            'unpaid' => 'payment-status-unpaid',
            'partial' => 'payment-status-partial',
            'paid' => 'payment-status-paid',
            'refunded' => 'payment-status-refunded',
        ];

        $roomStatusLabels = [
            'available' => 'Trống',
            'reserved' => 'Đã giữ',
            'occupied' => 'Đang ở',
            'cleaning' => 'Cần dọn',
            'maintenance' => 'Bảo trì',
        ];

        $bookingStatusClass = $bookingStatusClasses[$booking->status] ?? 'booking-status-cancelled';
        $paymentStatusClass = $paymentStatusClasses[$booking->payment_status] ?? 'payment-status-unpaid';

        $customerName = trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? ''));

        $nightCount = max(
            1,
            (strtotime($booking->check_out_date) - strtotime($booking->check_in_date)) / 86400
        );

        $assignedRooms = $booking->bookingRooms->pluck('room')->filter();

        $serviceItemTotal = $serviceItemTotal ?? 0;
        $approvedDamageTotal = $approvedDamageTotal ?? 0;
        $approvedMinibarTotal = $approvedMinibarTotal ?? 0;
        $approvedInspectionTotal = $approvedInspectionTotal ?? ($approvedDamageTotal + $approvedMinibarTotal);

        $roomTotal = $booking->bookingRooms->sum(function ($bookingRoom) use ($nightCount) {
            return (float) $bookingRoom->price_at_booking * $nightCount;
        });

        if ($roomTotal <= 0) {
            $roomTotal = max(0, (float) $booking->estimated_total - $serviceItemTotal - $approvedInspectionTotal);
        }

        $finalTotal = $roomTotal + $serviceItemTotal + $approvedInspectionTotal;
        $remainingTotal = max(0, $finalTotal - (float) $booking->deposit_amount);
    @endphp

    <style>
        .booking-page-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 360px;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 1199px) {
            .booking-page-grid {
                grid-template-columns: 1fr;
            }
        }

        .detail-card {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #fff;
            padding: 18px;
            height: 100%;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .detail-card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 17px;
            font-weight: 800;
            margin-bottom: 14px;
            color: #111827;
        }

        .detail-card-title i {
            color: #d4af37;
            font-size: 21px;
        }

        .booking-hero {
            border-radius: 22px;
            padding: 22px;
            background: linear-gradient(135deg, #fff7ed, #ffffff 65%);
            border: 1px solid #fed7aa;
            box-shadow: 0 12px 30px rgba(154, 52, 18, 0.06);
        }

        .booking-code-label {
            font-size: 13px;
            color: #9a3412;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .booking-code-value {
            font-size: 30px;
            font-weight: 900;
            letter-spacing: .5px;
            color: #111827;
        }

        .booking-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 6px 11px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .booking-status-pending {
            color: #664d03;
            background-color: #fff3cd;
            border: 1px solid #ffda6a;
        }

        .booking-status-confirmed {
            color: #084298;
            background-color: #cfe2ff;
            border: 1px solid #9ec5fe;
        }

        .booking-status-checked-in {
            color: #055160;
            background-color: #cff4fc;
            border: 1px solid #9eeaf9;
        }

        .booking-status-checked-out {
            color: #0f5132;
            background-color: #d1e7dd;
            border: 1px solid #a3cfbb;
        }

        .booking-status-cancelled {
            color: #842029;
            background-color: #f8d7da;
            border: 1px solid #f1aeb5;
        }

        .payment-status-unpaid {
            color: #41464b;
            background-color: #e2e3e5;
            border: 1px solid #c4c8cb;
        }

        .payment-status-partial {
            color: #664d03;
            background-color: #fff3cd;
            border: 1px solid #ffda6a;
        }

        .payment-status-paid {
            color: #0f5132;
            background-color: #d1e7dd;
            border: 1px solid #a3cfbb;
        }

        .payment-status-refunded {
            color: #055160;
            background-color: #cff4fc;
            border: 1px solid #9eeaf9;
        }

        .quick-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        @media (max-width: 991px) {
            .quick-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575px) {
            .quick-stat-grid {
                grid-template-columns: 1fr;
            }
        }

        .quick-stat-item {
            border: 1px solid #eef2f7;
            background: #fff;
            border-radius: 16px;
            padding: 14px;
        }

        .quick-stat-item span {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .quick-stat-item strong {
            display: block;
            color: #111827;
            font-size: 15px;
        }

        .section-stack {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 10px 0;
            border-bottom: 1px solid #eef2f7;
        }

        .info-row:last-child {
            border-bottom: 0;
        }

        .info-label {
            color: #64748b;
            font-size: 13px;
            min-width: 118px;
        }

        .info-value {
            font-weight: 700;
            text-align: right;
        }

        .operation-box {
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #fff;
            padding: 18px;
        }

        .operation-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 14px;
            margin-bottom: 14px;
        }

        .operation-header h5 {
            font-size: 18px;
            font-weight: 900;
            margin-bottom: 4px;
        }

        .operation-header p {
            color: #64748b;
            margin-bottom: 0;
            font-size: 13px;
        }

        .sub-panel {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #f8fafc;
            padding: 16px;
        }

        .room-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 14px;
            background: #fff;
        }

        .room-number {
            font-size: 18px;
            font-weight: 900;
            color: #111827;
        }

        .booking-log-box {
            max-height: 430px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .booking-log-item {
            border-left: 3px solid #d4af37;
            padding: 0 0 14px 14px;
            margin-bottom: 14px;
        }

        .booking-log-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .side-sticky {
            position: sticky;
            top: 92px;
        }

        @media (max-width: 1199px) {
            .side-sticky {
                position: static;
            }
        }
    </style>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.bookings.index') }}">Đặt phòng</a> /
                Chi tiết
            </p>

            <div class="admin-page-head">
                <div>
                    <h2>Chi tiết đặt phòng</h2>
                    <p>Xử lý đúng quy trình: check-in, kiểm tra phòng, check-out, phụ thu và ghi chú nội bộ</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
                        Quay lại
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Không thể xử lý yêu cầu:</strong>

                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="booking-hero mb-4">
                <div class="d-flex justify-content-between gap-3 flex-wrap align-items-start mb-3">
                    <div>
                        <div class="booking-code-label">Mã booking</div>
                        <div class="booking-code-value">{{ $booking->booking_code }}</div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <span class="booking-badge {{ $bookingStatusClass }}">
                            {{ $bookingStatusLabels[$booking->status] ?? $booking->status }}
                        </span>

                        <span class="booking-badge {{ $paymentStatusClass }}">
                            {{ $paymentStatusLabels[$booking->payment_status] ?? $booking->payment_status }}
                        </span>
                    </div>
                </div>

                <div class="quick-stat-grid">
                    <div class="quick-stat-item">
                        <span>Khách hàng</span>
                        <strong>{{ $customerName ?: 'Chưa có tên' }}</strong>
                    </div>

                    <div class="quick-stat-item">
                        <span>Hạng phòng</span>
                        <strong>{{ $booking->roomCategory->name ?? 'Không xác định' }}</strong>
                    </div>

                    <div class="quick-stat-item">
                        <span>Thời gian lưu trú</span>
                        <strong>
                            {{ date('d/m/Y', strtotime($booking->check_in_date)) }}
                            -
                            {{ date('d/m/Y', strtotime($booking->check_out_date)) }}
                        </strong>
                    </div>

                    <div class="quick-stat-item">
                        <span>Còn lại cần thanh toán</span>
                        <strong class="text-danger">{{ number_format($remainingTotal, 0, ',', '.') }}đ</strong>
                    </div>
                </div>
            </div>

            <div class="booking-page-grid">

                <div class="section-stack">

                    <div class="operation-box">
                        <div class="operation-header">
                            <div>
                                <h5>Thao tác nghiệp vụ</h5>
                                <p>Không đổi trạng thái booking bằng dropdown. Trạng thái chỉ thay đổi qua các bước bên
                                    dưới.</p>
                            </div>
                            <span class="booking-badge {{ $bookingStatusClass }}">
                                {{ $bookingStatusLabels[$booking->status] ?? $booking->status }}
                            </span>
                        </div>

                        @if ($booking->status == 'confirmed')
                            @php
                                $currentAdultCapacity = $booking->bookingRooms->sum(function ($bookingRoom) {
                                    return $bookingRoom->room->category->adult_capacity ?? 0;
                                });

                                $currentChildCapacity = $booking->bookingRooms->sum(function ($bookingRoom) {
                                    return $bookingRoom->room->category->child_capacity ?? 0;
                                });

                                $extraGuestServices = \App\Models\Service::where('type', 'violation_fee')
                                    ->where('status', 'active')
                                    ->orderBy('name')
                                    ->get();

                                $roomCategoriesForCheckIn = \App\Models\RoomCategory::where('status', 'active')
                                    ->withCount([
                                        'rooms as available_rooms_count' => function ($query) {
                                            $query->where('status', 'available');
                                        },
                                    ])
                                    ->orderBy('price')
                                    ->get();
                            @endphp

                            <div class="sub-panel">
                                <h6 class="fw-bold mb-2">Check-in thực tế</h6>

                                <div class="alert alert-info py-2 mb-3">
                                    <strong>Sức chứa hang phong:</strong>
                                    {{ $currentAdultCapacity }} người lớn /
                                    {{ $currentChildCapacity }} trẻ em

                                    @if ($booking->room_quantity > 1)
                                        <br>
                                        <small>
                                            (Tổng sức chứa của {{ $booking->room_quantity }} phòng đã gán)
                                        </small>
                                    @endif
                                </div>

                                <form action="{{ route('admin.bookings.check-in', $booking->id) }}" method="POST"
                                    id="checkInForm" onsubmit="return confirm('Xác nhận check-in cho booking này?')">

                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden" id="adultCapacity" value="{{ $currentAdultCapacity }}">
                                    <input type="hidden" id="childCapacity" value="{{ $currentChildCapacity }}">

                                    <div class="row g-2 mb-3">

                                        <div class="col-md-4">
                                            <label class="form-label small">Người lớn thực tế</label>
                                            <input type="number" name="actual_adult_count" id="actualAdultCount"
                                                class="form-control"
                                                value="{{ old('actual_adult_count', $booking->adult_count) }}" min="1" required>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label small">Trẻ em thực tế</label>
                                            <input type="number" name="actual_child_count" id="actualChildCount"
                                                class="form-control"
                                                value="{{ old('actual_child_count', $booking->child_count) }}" min="0">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label small">Em bé phát sinh</label>
                                            <input type="number" name="actual_baby_count" id="actualBabyCount"
                                                class="form-control" value="{{ old('actual_baby_count', 0) }}" min="0">
                                        </div>

                                    </div>

                                    <div id="normalCheckInBox" class="alert alert-success small">
                                        Số khách thực tế không vượt sức chứa. Có thể check-in bình thường.
                                    </div>

                                    <div id="overCapacityBox" class="d-none">
                                        <div class="alert alert-warning small">
                                            Số khách thực tế vượt sức chứa phòng hiện tại. Vui lòng chọn cách xử lý.
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Cách xử lý</label>
                                            <select name="over_capacity_action" id="overCapacityAction" class="form-select">
                                                <option value="">-- Chọn cách xử lý --</option>
                                                <option value="extra_fee">Khách ở phòng hiện tại và thu phụ phí</option>
                                                <option value="add_room">Đặt thêm phòng vào booking</option>
                                                <option value="change_category">Đổi sang hạng phòng khác phù hợp</option>
                                            </select>
                                        </div>

                                        <div id="extraFeeBox" class="d-none border rounded p-3 mb-3">

                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="fw-bold mb-0">Các khoản phụ thu khi check-in</h6>

                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    id="addExtraFeeRow">
                                                    + Thêm phụ thu
                                                </button>
                                            </div>

                                            <div id="extraFeeRows">

                                                <div class="extra-fee-row border rounded p-3 mb-3">

                                                    <div class="row g-2 align-items-end">

                                                        <div class="col-md-4">
                                                            <label class="form-label">Loại phụ thu</label>
                                                            <select name="extra_service_ids[]"
                                                                class="form-select extra-service-select">
                                                                <option value="">-- Chọn phụ thu --</option>

                                                                @foreach ($extraGuestServices as $service)
                                                                    <option value="{{ $service->id }}"
                                                                        data-price="{{ $service->price }}"
                                                                        data-unit="{{ $service->unit }}">
                                                                        {{ $service->name }}
                                                                        - {{ number_format($service->price, 0, ',', '.') }}đ /
                                                                        {{ $service->unit }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Số lượng</label>
                                                            <input type="number" name="extra_quantities[]"
                                                                class="form-control extra-quantity-input" value="1" min="1">
                                                        </div>

                                                        <div class="col-md-3">
                                                            <label class="form-label">Tạm tính</label>
                                                            <input type="text" class="form-control extra-total-text" value="0đ"
                                                                readonly>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <button type="button"
                                                                class="btn btn-outline-danger w-100 remove-extra-fee-row">
                                                                Xóa dòng
                                                            </button>
                                                        </div>

                                                        <div class="col-md-12">
                                                            <label class="form-label">Ghi chú</label>
                                                            <input type="text" name="extra_fee_notes[]" class="form-control"
                                                                placeholder="Ví dụ: Phụ thu thêm trẻ em / em bé / vượt sức chứa">
                                                        </div>

                                                    </div>

                                                </div>

                                            </div>

                                            <div class="alert alert-light border mb-0">
                                                Tổng phụ thu tạm tính:
                                                <strong id="allExtraFeeTotalText">0đ</strong>
                                            </div>

                                        </div>

                                        <div id="addRoomBox" class="d-none border rounded p-3 mb-3 bg-white">
                                            <h6 class="fw-bold mb-3">Đặt thêm phòng vào booking</h6>

                                            <div class="row g-2">
                                                <div class="col-md-8">
                                                    <label class="form-label">Chọn hạng phòng cần thêm</label>
                                                    <select name="additional_room_category_id" class="form-select">
                                                        <option value="">-- Chọn hạng phòng --</option>

                                                        @foreach ($roomCategoriesForCheckIn as $category)
                                                            <option value="{{ $category->id }}">
                                                                {{ $category->name }}
                                                                - Còn {{ $category->available_rooms_count }} phòng
                                                                - Sức chứa {{ $category->adult_capacity }} NL /
                                                                {{ $category->child_capacity }} TE
                                                                - {{ number_format($category->price, 0, ',', '.') }}đ/đêm
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label">Số phòng thêm</label>
                                                    <input type="number" name="additional_room_quantity" class="form-control"
                                                        value="1" min="1">
                                                </div>
                                            </div>

                                            <div class="form-check mt-3">
                                                <input type="checkbox" name="prefer_near_current_rooms" value="1"
                                                    class="form-check-input" id="preferNearCurrentRooms">
                                                <label class="form-check-label" for="preferNearCurrentRooms">
                                                    Ưu tiên phòng cùng tầng / gần phòng hiện tại
                                                </label>
                                            </div>

                                            <div class="mt-3">
                                                <label class="form-label">Lý do thêm phòng</label>
                                                <input type="text" name="add_room_reason" class="form-control"
                                                    placeholder="Ví dụ: Khách đi thêm người, phòng hiện tại không đủ sức chứa">
                                            </div>
                                        </div>

                                        <div id="changeCategoryBox" class="d-none border rounded p-3 mb-3 bg-white">
                                            <h6 class="fw-bold mb-3">Đổi sang hạng phòng khác</h6>

                                            <div class="mb-3">
                                                <label class="form-label">Chọn hạng phòng mới</label>
                                                <select name="new_room_category_id" class="form-select">
                                                    <option value="">-- Chọn hạng phòng phù hợp --</option>

                                                    @foreach ($roomCategoriesForCheckIn as $category)
                                                        <option value="{{ $category->id }}">
                                                            {{ $category->name }}
                                                            - Còn {{ $category->available_rooms_count }} phòng
                                                            - Sức chứa {{ $category->adult_capacity }} NL /
                                                            {{ $category->child_capacity }} TE
                                                            - {{ number_format($category->price, 0, ',', '.') }}đ/đêm
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="form-label">Lý do đổi hạng phòng</label>
                                                <input type="text" name="change_category_reason" class="form-control"
                                                    placeholder="Ví dụ: Số khách thực tế vượt sức chứa hạng phòng cũ">
                                            </div>
                                        </div>
                                    </div>

                                    @php
                                        $nowVn = \Carbon\Carbon::now('Asia/Ho_Chi_Minh');
                                        $checkInAt = $booking->check_in_at
                                            ? \Carbon\Carbon::parse($booking->check_in_at, 'Asia/Ho_Chi_Minh')
                                            : null;

                                        $lateMinutes = 0;

                                        if ($checkInAt && $nowVn->greaterThan($checkInAt)) {
                                            $lateMinutes = $checkInAt->diffInMinutes($nowVn);
                                        }

                                        $lateHours = $lateMinutes > 0 ? round($lateMinutes / 60, 2) : 0;
                                        $latePercent = 0;

                                        if ($lateHours >= 2 && $lateHours < 4) {
                                            $latePercent = 20;
                                        } elseif ($lateHours >= 4 && $lateHours < 6) {
                                            $latePercent = 50;
                                        } elseif ($lateHours >= 6) {
                                            $latePercent = 100;
                                        }

                                        $roomPrice = $booking->roomCategory->price ?? 0;
                                        $lateFeePreview = ($roomPrice * $latePercent) / 100;
                                    @endphp

                                    @if ($booking->check_in_at && $lateHours > 0)
                                        <div class="alert alert-warning small">
                                            <div>
                                                Khách đang đến muộn khoảng
                                                <strong>{{ $lateHours }} giờ</strong>
                                            </div>

                                            @if ($lateHours < 2)
                                                <div class="mt-1">Miễn phí do trễ dưới 2 giờ.</div>
                                            @elseif ($lateHours < 4)
                                                <div class="mt-1">
                                                    Phụ thu 20% giá/đêm
                                                    <strong
                                                        class="text-danger">({{ number_format($lateFeePreview, 0, ',', '.') }}đ)</strong>
                                                </div>
                                            @elseif ($lateHours < 6)
                                                <div class="mt-1">
                                                    Phụ thu 50% giá/đêm
                                                    <strong
                                                        class="text-danger">({{ number_format($lateFeePreview, 0, ',', '.') }}đ)</strong>
                                                </div>
                                            @else
                                                <div class="mt-1 text-danger fw-bold">
                                                    Phụ thu 100% giá/đêm
                                                    ({{ number_format($lateFeePreview, 0, ',', '.') }}đ)
                                                </div>
                                                <div>Cần xác nhận khách vẫn đang đến hoặc hủy phòng.</div>
                                            @endif
                                        </div>

                                        @if ($lateHours >= 6)
                                            <div class="form-check mb-3">
                                                <input type="checkbox" name="late_arrival_action" value="confirm_arriving"
                                                    class="form-check-input" id="lateArrivalConfirm">
                                                <label class="form-check-label" for="lateArrivalConfirm">
                                                    Đã gọi xác nhận khách vẫn đang đến, chấp nhận phụ thu 100% giá/đêm
                                                </label>
                                            </div>
                                        @endif
                                    @endif

                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bx bx-log-in-circle me-1"></i>
                                        Xác nhận check-in
                                    </button>
                                </form>

                                @if ($booking->status == 'confirmed' && $booking->check_in_at)
                                    @php
                                        $lateCancelMinutes = $booking->check_in_at->diffInMinutes(now(), false);
                                    @endphp

                                    @if ($lateCancelMinutes > 360)
                                        <form action="{{ route('admin.bookings.cancel-late-arrival', $booking->id) }}" method="POST"
                                            class="mt-2"
                                            onsubmit="return confirm('Hủy phòng do khách đến muộn quá 6 giờ và không hoàn tiền cọc?')">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="btn btn-outline-danger w-100">
                                                Hủy do đến muộn quá 6 giờ
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>

                        @elseif ($booking->status == 'checked_in' && !$hasInspection)
                            <div class="sub-panel mb-3">
                                <h6 class="fw-bold mb-2">Gia hạn lưu trú</h6>

                                <form action="{{ route('admin.bookings.extend-stay', $booking->id) }}" method="POST"
                                    onsubmit="return confirm('Kiểm tra phòng và xác nhận gia hạn lưu trú?')">
                                    @csrf
                                    @method('PATCH')

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Ngày trả phòng mới</label>

                                            <input
                                                type="date"
                                                name="new_check_out_date"
                                                class="form-control"
                                                min="{{ $booking->check_out_at ? $booking->check_out_at->format('Y-m-d') : date('Y-m-d') }}"
                                                value="{{ old('new_check_out_date', $booking->check_out_at ? $booking->check_out_at->format('Y-m-d') : $booking->check_out_date) }}"
                                                required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Giờ trả phòng mới</label>

                                            <input
                                                type="text"
                                                name="new_check_out_time"
                                                id="extendCheckOutTime"
                                                class="form-control"
                                                value="{{ old('new_check_out_time', $booking->check_out_at ? $booking->check_out_at->format('H:i') : '11:00') }}"
                                                placeholder="Ví dụ: 14:00"
                                                required>
                                        </div>
                                    </div>

                                    <div class="alert alert-info small mb-3">
                                        <strong>Chính sách gia hạn:</strong>
                                        <br>
                                        • Gia hạn cùng ngày dưới hoặc bằng 3 giờ: phụ thu 30% giá/đêm.
                                        <br>
                                        • Gia hạn cùng ngày trên 3 đến 6 giờ: phụ thu 50% giá/đêm.
                                        <br>
                                        • Gia hạn cùng ngày trên 6 giờ hoặc qua ngày mới: tính thêm đêm.
                                        <br>
                                        • Nếu phòng đã có booking mới trong ngày trả phòng, hệ thống sẽ chặn gia hạn kể cả chỉ thêm 1 giờ. Khi đó cần tạo booking mới hoặc chuyển khách sang phòng khác.
                                    </div>

                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        <i class="bx bx-time-five me-1"></i>
                                        Gia hạn lưu trú
                                    </button>
                                </form>
                            </div>

                            <form action="{{ route('admin.bookings.request-inspection', $booking->id) }}" method="POST"
                                onsubmit="return confirm('Chuyển phòng sang trạng thái chờ kiểm tra?')">
                                @csrf
                                @method('PATCH')

                                <button type="submit" class="btn btn-warning">
                                    <i class="bx bx-search-alt me-1"></i>
                                    Yêu cầu kiểm tra phòng
                                </button>
                            </form>
                        @elseif ($booking->status == 'checked_in' && $allInspectionsConfirmed)
                            <form action="{{ route('admin.bookings.check-out', $booking->id) }}" method="POST"
                                onsubmit="return confirm('Xác nhận check-out cho booking này?')">
                                @csrf
                                @method('PATCH')

                                <button type="submit" class="btn btn-danger">
                                    <i class="bx bx-log-out-circle me-1"></i>
                                    Check-out
                                </button>
                            </form>
                        @elseif ($booking->status == 'checked_in' && $hasInspection && !$allInspectionsConfirmed)
                            <div class="alert alert-warning mb-0">
                                Phòng đã được yêu cầu kiểm tra. Cần quản lý tầng báo cáo và admin duyệt xong mới được check-out.
                            </div>
                        @else
                            <div class="alert alert-light border mb-0">
                                Booking hiện không có thao tác nghiệp vụ cần xử lý ở bước này.
                            </div>
                        @endif
                    </div>

                                        <div class="detail-card">
                        <h5 class="detail-card-title">
                            <i class="bx bx-search-alt"></i>
                            Minibar / hư hại đã duyệt từ kiểm tra phòng
                        </h5>

                        @php
                            $approvedInspectionItems = $booking->roomInspections
                                ->flatMap->items
                                ->where('status', 'approved');
                        @endphp

                        @if ($approvedInspectionItems->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Loại</th>
                                            <th>Hạng mục</th>
                                            <th>Đơn giá</th>
                                            <th>Số lượng</th>
                                            <th>Thành tiền</th>
                                            <th>Ghi chú admin</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($approvedInspectionItems as $inspectionItem)
                                            <tr>
                                                <td>
                                                    @if ($inspectionItem->type == 'minibar')
                                                        <span class="badge bg-warning text-dark">Minibar</span>
                                                    @elseif ($inspectionItem->type == 'damage_fee')
                                                        <span class="badge bg-danger">Hư hại</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $inspectionItem->type }}</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <strong>{{ $inspectionItem->name }}</strong>
                                                    <div class="text-muted small">
                                                        Đơn vị: {{ $inspectionItem->unit ?? '---' }}
                                                    </div>
                                                </td>

                                                <td>{{ number_format((float) $inspectionItem->price, 0, ',', '.') }}đ</td>

                                                <td>{{ $inspectionItem->quantity }}</td>

                                                <td class="fw-bold text-danger">
                                                    {{ number_format((float) $inspectionItem->total, 0, ',', '.') }}đ
                                                </td>

                                                <td>{{ $inspectionItem->admin_note ?: '---' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-light border mb-0">
                                Chưa có minibar hoặc hư hại nào được duyệt từ kiểm tra phòng.
                            </div>
                        @endif
                    </div>

                    <div class="detail-card">
                        <h5 class="detail-card-title">
                            <i class="bx bx-receipt"></i>
                            Dịch vụ / minibar / phụ thu phát sinh
                        </h5>

                        @if ($booking->serviceItems->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tên khoản thu</th>
                                            <th>Loại</th>
                                            <th>Đơn giá</th>
                                            <th>Đăng ký</th>
                                            <th>Thực dùng</th>
                                            <th>Thành tiền</th>
                                            <th>Ghi chú</th>
                                            <th></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($booking->serviceItems as $item)
                                            <tr>
                                                <td>{{ $item->name }}</td>
                                                <td>
                                                    @if ($item->type == 'violation_fee')
                                                        <span class="badge bg-dark">Phí vi phạm</span>
                                                    @elseif ($item->type == 'minibar')
                                                        <span class="badge bg-warning text-dark">Minibar</span>
                                                    @elseif ($item->type == 'damage_fee')
                                                        <span class="badge bg-danger">Phí hư hại</span>
                                                    @else
                                                        <span class="badge bg-primary">Dịch vụ</span>
                                                    @endif
                                                </td>
                                                <td>{{ number_format($item->unit_price, 0, ',', '.') }}đ</td>
                                                <td style="min-width: 120px;">
                                                    @if (
                                                            in_array($booking->status, ['pending', 'confirmed', 'checked_in'])
                                                            && in_array($item->type, ['service', 'minibar'])
                                                        )
                                                        <form
                                                            action="{{ route('admin.bookings.service-items.update', [$booking->id, $item->id]) }}"
                                                            method="POST" class="d-flex gap-1 align-items-center">
                                                            @csrf
                                                            @method('PATCH')

                                                            <input type="number" name="quantity" class="form-control form-control-sm"
                                                                value="{{ $item->quantity }}" min="1" style="width: 75px;">

                                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                                Lưu
                                                            </button>
                                                        </form>
                                                    @else
                                                        {{ $item->quantity }}
                                                    @endif
                                                </td>
                                                <td>
    @if ($item->type == 'minibar')
        <span class="badge bg-warning text-dark">
            {{ $item->used_quantity ?? 0 }}/{{ $item->quantity }}
        </span>
    @else
        <span class="badge bg-success">
            {{ $item->used_quantity ?? $item->quantity }}
        </span>
    @endif
</td>
                                                <td class="fw-bold text-danger">{{ number_format($item->total, 0, ',', '.') }}đ</td>
                                                <td>{{ $item->note ?: '---' }}</td>
                                                <td class="text-end">
                                                    @if (
                                                            in_array($booking->status, ['pending', 'confirmed', 'checked_in'])
                                                            && in_array($item->type, ['service', 'minibar'])
                                                        )
                                                        <form
                                                            action="{{ route('admin.bookings.service-items.destroy', [$booking->id, $item->id]) }}"
                                                            method="POST" onsubmit="return confirm('Xóa dịch vụ này khỏi booking?')">
                                                            @csrf
                                                            @method('DELETE')

                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-light border mb-0">
                                Chưa có phụ thu hoặc dịch vụ phát sinh.
                            </div>
                        @endif

                        @if (in_array($booking->status, ['pending', 'confirmed', 'checked_in']))
                            <hr>

                            <form action="{{ route('admin.bookings.service-items.store', $booking->id) }}" method="POST">
                                @csrf

                                <h6 class="fw-bold mb-3">Thêm dịch vụ / minibar vào booking</h6>

                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label">Dịch vụ</label>
                                        <select name="service_id" id="serviceItemSelect" class="form-select" required>
                                            <option value="">-- Chọn dịch vụ --</option>

                                            @foreach ($availableServices as $service)
                                                <option value="{{ $service->id }}" data-price="{{ $service->price }}"
                                                    data-unit="{{ $service->unit }}">
                                                    {{ $service->name }}
                                                    - {{ $service->type == 'minibar' ? 'Minibar' : 'Dịch vụ' }}
                                                    - {{ number_format($service->price, 0, ',', '.') }}đ /
                                                    {{ $service->unit }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">Số lượng</label>
                                        <input type="number" name="quantity" id="serviceItemQuantity" class="form-control"
                                            value="1" min="1" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Tạm tính</label>
                                        <input type="text" id="serviceItemTotalText" class="form-control" value="0đ" readonly>
                                    </div>

                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Thêm</button>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label">Ghi chú</label>
                                        <input type="text" name="note" class="form-control"
                                            placeholder="Ví dụ: Khách gọi lễ tân yêu cầu thêm nước suối">
                                    </div>
                                </div>
                            </form>
                        @else
                            <div class="alert alert-secondary mt-3 mb-0">
                                Booking đã kết thúc hoặc đã hủy nên không thể thêm dịch vụ.
                            </div>
                        @endif
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="detail-card">
                                <h5 class="detail-card-title">
                                    <i class="bx bx-bed"></i>
                                    Phòng được gán
                                </h5>

                                @if ($assignedRooms->count() > 0)
                                    <div class="d-flex flex-column gap-3">
                                        @foreach ($assignedRooms as $assignedRoom)
                                            <div class="room-card">
                                                <div class="d-flex justify-content-between align-items-start gap-3">
                                                    <div>
                                                        <div class="room-number">Phòng {{ $assignedRoom->room_number }}</div>
                                                        <div class="text-muted small">Tầng
                                                            {{ $assignedRoom->floor_number ?? '---' }}
                                                        </div>
                                                        <div class="text-muted small">
                                                            Trạng thái:
                                                            {{ $roomStatusLabels[$assignedRoom->status] ?? $assignedRoom->status }}
                                                        </div>
                                                    </div>

                                                    <a href="{{ route('admin.rooms.show', $assignedRoom->id) }}"
                                                        class="btn btn-sm btn-outline-secondary">
                                                        Xem phòng
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        Booking này chưa có phòng thật được gán.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="detail-card">
                                <h5 class="detail-card-title">
                                    <i class="bx bx-transfer-alt"></i>
                                    Báo phòng sự cố / Đổi phòng
                                </h5>

                                @if (in_array($booking->status, ['pending', 'confirmed', 'checked_in']))
                                    <form action="{{ route('admin.bookings.change-room', $booking->id) }}" method="POST">
                                        @csrf

                                        <div class="mb-3">
                                            <label class="form-label">Phòng bị sự cố</label>
                                            <select name="old_room_id" class="form-select" required>
                                                <option value="">-- Chọn phòng đang gán --</option>

                                                @foreach ($assignedRooms as $assignedRoom)
                                                    <option value="{{ $assignedRoom->id }}">
                                                        Phòng {{ $assignedRoom->room_number }}
                                                        - Tầng {{ $assignedRoom->floor_number ?? '---' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Phòng thay thế</label>
                                            <select name="new_room_id" class="form-select" required>
                                                <option value="">-- Chọn phòng trống cùng hạng --</option>

                                                @foreach ($availableRooms as $room)
                                                    @if (!in_array($room->id, $assignedRoomIds))
                                                        <option value="{{ $room->id }}">
                                                            Phòng {{ $room->room_number }}
                                                            - Tầng {{ $room->floor_number ?? '---' }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Trạng thái phòng cũ</label>
                                            <select name="old_room_new_status" class="form-select" required>
                                                <option value="maintenance">Bảo trì</option>
                                                <option value="cleaning">Cần dọn</option>
                                                <option value="available">Trống</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Lý do đổi phòng</label>
                                            <input type="text" name="change_reason" class="form-control"
                                                placeholder="Ví dụ: Hỏng điều hòa, chưa dọn xong, khóa lỗi..." required>
                                        </div>

                                        <button type="submit" class="btn btn-warning w-100"
                                            onclick="return confirm('Xác nhận đổi phòng cho booking này?')">
                                            Đổi phòng
                                        </button>
                                    </form>
                                @else
                                    <div class="alert alert-secondary mb-0">
                                        Booking đã kết thúc hoặc đã hủy nên không thể đổi phòng.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="detail-card">
                        <h5 class="detail-card-title">
                            <i class="bx bx-history"></i>
                            Lịch sử thao tác
                        </h5>

                        <div class="booking-log-box">
                            @forelse ($booking->logs as $log)
                                <div class="booking-log-item">
                                    <div class="d-flex justify-content-between gap-3 flex-wrap">
                                        <div class="fw-bold">
                                            {{ $log->created_at ? $log->created_at->format('d/m/Y - H:i') : '---' }}
                                            -
                                            {{ $log->user?->name ?? 'Hệ thống' }}
                                        </div>
                                    </div>

                                    <div class="text-muted mt-1" style="white-space: pre-line;">
                                        {{ $log->description }}
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">Chưa có lịch sử thao tác.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <aside class="section-stack side-sticky">
                    <div class="detail-card">
                        <h5 class="detail-card-title">
                            <i class="bx bx-user"></i>
                            Thông tin khách hàng
                        </h5>

                        <div class="info-row">
                            <div class="info-label">Họ tên</div>
                            <div class="info-value">{{ $customerName ?: 'Chưa có tên' }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Số điện thoại</div>
                            <div class="info-value">{{ $booking->customer->phone ?? '---' }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Email</div>
                            <div class="info-value">{{ $booking->customer->email ?? '---' }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">CCCD</div>
                            <div class="info-value">{{ $booking->customer->cccd ?? '---' }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Địa chỉ</div>
                            <div class="info-value">{{ $booking->customer->address ?? '---' }}</div>
                        </div>
                    </div>

                    <div class="detail-card">
                        <h5 class="detail-card-title">
                            <i class="bx bx-calendar"></i>
                            Thông tin lưu trú
                        </h5>

                        <div class="info-row">
                            <div class="info-label">Hạng phòng</div>
                            <div class="info-value">{{ $booking->roomCategory->name ?? 'Không xác định' }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Nhận phòng</div>
                            <div class="info-value">{{ date('d/m/Y', strtotime($booking->check_in_date)) }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Trả phòng</div>
                            <div class="info-value">{{ date('d/m/Y', strtotime($booking->check_out_date)) }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Số đêm</div>
                            <div class="info-value">{{ $nightCount }} đêm</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Số khách</div>
                            <div class="info-value">
                                {{ $booking->adult_count }} NL /
                                {{ $booking->child_count }} TE
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Số phòng</div>
                            <div class="info-value">{{ $booking->room_quantity }} phòng</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Tạo lúc</div>
                            <div class="info-value">
                                {{ $booking->created_at ? $booking->created_at->format('d/m/Y H:i') : '---' }}
                            </div>
                        </div>
                    </div>

                    <div class="detail-card">
                        <h5 class="detail-card-title">
                            <i class="bx bx-wallet"></i>
                            Thanh toán
                        </h5>

                        <div class="info-row">
                            <div class="info-label">Tiền phòng</div>
                            <div class="info-value">{{ number_format($roomTotal, 0, ',', '.') }}đ</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Dịch vụ đã xác nhận dùng</div>
                            <div class="info-value {{ $serviceItemTotal > 0 ? 'text-danger' : '' }}">
                                {{ $serviceItemTotal > 0 ? '+' : '' }}{{ number_format((float) $serviceItemTotal, 0, ',', '.') }}đ
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Minibar kiểm tra phòng đã duyệt</div>
                            <div class="info-value {{ $approvedMinibarTotal > 0 ? 'text-danger' : '' }}">
                                {{ $approvedMinibarTotal > 0 ? '+' : '' }}{{ number_format((float) $approvedMinibarTotal, 0, ',', '.') }}đ
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Phí hư hại đã duyệt</div>
                            <div class="info-value {{ $approvedDamageTotal > 0 ? 'text-danger' : '' }}">
                                {{ $approvedDamageTotal > 0 ? '+' : '' }}{{ number_format((float) $approvedDamageTotal, 0, ',', '.') }}đ
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Tiền cọc</div>
                            <div class="info-value">-{{ number_format($booking->deposit_amount, 0, ',', '.') }}đ</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Còn lại</div>
                            <div class="info-value text-danger fs-5">{{ number_format($remainingTotal, 0, ',', '.') }}đ
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Trạng thái</div>
                            <div class="info-value">
                                <span class="booking-badge {{ $paymentStatusClass }}">
                                    {{ $paymentStatusLabels[$booking->payment_status] ?? $booking->payment_status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-card">
                        <h5 class="detail-card-title">
                            <i class="bx bx-note"></i>
                            Ghi chú nội bộ
                        </h5>

                        <form action="{{ route('admin.bookings.update-note', $booking->id) }}" method="POST">
                            @csrf
                            @method('PATCH')

                            <textarea name="note" rows="5" class="form-control"
                                placeholder="Nhập ghi chú nội bộ cho booking nếu có">{{ old('note', $booking->note) }}</textarea>

                            @error('note')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror

                            <button type="submit" class="btn btn-gold w-100 mt-3">
                                Lưu ghi chú
                            </button>
                        </form>
                    </div>
                </aside>
            </div>
        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function numberFormat(value) {
                return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
            }

            const adultCapacityInput = document.getElementById('adultCapacity');
            const childCapacityInput = document.getElementById('childCapacity');
            const actualAdultInput = document.getElementById('actualAdultCount');
            const actualChildInput = document.getElementById('actualChildCount');

            const normalCheckInBox = document.getElementById('normalCheckInBox');
            const overCapacityBox = document.getElementById('overCapacityBox');
            const overCapacityAction = document.getElementById('overCapacityAction');

            const extraFeeBox = document.getElementById('extraFeeBox');
            const addRoomBox = document.getElementById('addRoomBox');
            const changeCategoryBox = document.getElementById('changeCategoryBox');

            function hideAllActionBoxes() {
                if (extraFeeBox) {
                    extraFeeBox.classList.add('d-none');
                }

                if (addRoomBox) {
                    addRoomBox.classList.add('d-none');
                }

                if (changeCategoryBox) {
                    changeCategoryBox.classList.add('d-none');
                }
            }

            function checkCapacity() {
                if (
                    !adultCapacityInput
                    || !childCapacityInput
                    || !actualAdultInput
                    || !actualChildInput
                    || !normalCheckInBox
                    || !overCapacityBox
                    || !overCapacityAction
                ) {
                    return;
                }

                const adultCapacity = parseInt(adultCapacityInput.value || 0);
                const childCapacity = parseInt(childCapacityInput.value || 0);

                const actualAdult = parseInt(actualAdultInput.value || 0);
                const actualChild = parseInt(actualChildInput.value || 0);

                const isOver = actualAdult > adultCapacity || actualChild > childCapacity;

                if (isOver) {
                    normalCheckInBox.classList.add('d-none');
                    overCapacityBox.classList.remove('d-none');
                } else {
                    normalCheckInBox.classList.remove('d-none');
                    overCapacityBox.classList.add('d-none');
                    overCapacityAction.value = '';
                    hideAllActionBoxes();
                }
            }

            function toggleActionBox() {
                if (!overCapacityAction) {
                    return;
                }

                hideAllActionBoxes();

                if (overCapacityAction.value === 'extra_fee' && extraFeeBox) {
                    extraFeeBox.classList.remove('d-none');
                }

                if (overCapacityAction.value === 'add_room' && addRoomBox) {
                    addRoomBox.classList.remove('d-none');
                }

                if (overCapacityAction.value === 'change_category' && changeCategoryBox) {
                    changeCategoryBox.classList.remove('d-none');
                }
            }

            const extraFeeRows = document.getElementById('extraFeeRows');
            const addExtraFeeRowButton = document.getElementById('addExtraFeeRow');
            const allExtraFeeTotalText = document.getElementById('allExtraFeeTotalText');

            function updateAllExtraFeeTotals() {
                if (!extraFeeRows || !allExtraFeeTotalText) {
                    return;
                }

                let grandTotal = 0;

                extraFeeRows.querySelectorAll('.extra-fee-row').forEach(function (row) {
                    const serviceSelect = row.querySelector('.extra-service-select');
                    const quantityInput = row.querySelector('.extra-quantity-input');
                    const totalText = row.querySelector('.extra-total-text');

                    if (!serviceSelect || !quantityInput || !totalText) {
                        return;
                    }

                    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                    const price = selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
                    const quantity = parseInt(quantityInput.value || 1);
                    const total = price * quantity;

                    totalText.value = numberFormat(total);
                    grandTotal += total;
                });

                allExtraFeeTotalText.textContent = numberFormat(grandTotal);
            }

            function bindExtraFeeRow(row) {
                const serviceSelect = row.querySelector('.extra-service-select');
                const quantityInput = row.querySelector('.extra-quantity-input');
                const removeButton = row.querySelector('.remove-extra-fee-row');

                if (serviceSelect) {
                    serviceSelect.addEventListener('change', updateAllExtraFeeTotals);
                }

                if (quantityInput) {
                    quantityInput.addEventListener('input', updateAllExtraFeeTotals);
                }

                if (removeButton) {
                    removeButton.addEventListener('click', function () {
                        const rowCount = extraFeeRows.querySelectorAll('.extra-fee-row').length;

                        if (rowCount <= 1) {
                            row.querySelectorAll('select, input').forEach(function (input) {
                                if (input.tagName === 'SELECT') {
                                    input.value = '';
                                } else if (input.type === 'number') {
                                    input.value = 1;
                                } else {
                                    input.value = '';
                                }
                            });

                            updateAllExtraFeeTotals();
                            return;
                        }

                        row.remove();
                        updateAllExtraFeeTotals();
                    });
                }
            }

            if (extraFeeRows) {
                extraFeeRows.querySelectorAll('.extra-fee-row').forEach(bindExtraFeeRow);
            }

            if (addExtraFeeRowButton && extraFeeRows) {
                addExtraFeeRowButton.addEventListener('click', function () {
                    const firstRow = extraFeeRows.querySelector('.extra-fee-row');

                    if (!firstRow) {
                        return;
                    }

                    const newRow = firstRow.cloneNode(true);

                    newRow.querySelectorAll('select, input').forEach(function (input) {
                        if (input.tagName === 'SELECT') {
                            input.value = '';
                        } else if (input.type === 'number') {
                            input.value = 1;
                        } else {
                            input.value = '';
                        }
                    });

                    const totalInput = newRow.querySelector('.extra-total-text');
                    if (totalInput) {
                        totalInput.value = '0đ';
                    }

                    extraFeeRows.appendChild(newRow);
                    bindExtraFeeRow(newRow);
                    updateAllExtraFeeTotals();
                });
            }

            updateAllExtraFeeTotals();

            if (actualAdultInput) {
                actualAdultInput.addEventListener('input', checkCapacity);
            }

            if (actualChildInput) {
                actualChildInput.addEventListener('input', checkCapacity);
            }

            if (overCapacityAction) {
                overCapacityAction.addEventListener('change', toggleActionBox);
            }

            checkCapacity();
            const serviceItemSelect = document.getElementById('serviceItemSelect');
            const serviceItemQuantity = document.getElementById('serviceItemQuantity');
            const serviceItemTotalText = document.getElementById('serviceItemTotalText');

            function updateServiceItemTotal() {
                if (!serviceItemSelect || !serviceItemQuantity || !serviceItemTotalText) {
                    return;
                }

                const selectedOption = serviceItemSelect.options[serviceItemSelect.selectedIndex];
                const price = selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
                const quantity = parseInt(serviceItemQuantity.value || 1);
                const total = price * quantity;

                serviceItemTotalText.value = numberFormat(total);
            }

            if (serviceItemSelect) {
                serviceItemSelect.addEventListener('change', updateServiceItemTotal);
            }

            if (serviceItemQuantity) {
                serviceItemQuantity.addEventListener('input', updateServiceItemTotal);
            }

            updateServiceItemTotal();

            if (document.getElementById('extendCheckOutTime')) {
                flatpickr('#extendCheckOutTime', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    time_24hr: true,
                    minuteIncrement: 30,
                    locale: 'vn'
                });
            }
        });
    </script>
@endsection