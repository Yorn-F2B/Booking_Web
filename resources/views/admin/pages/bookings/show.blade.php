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

        $bookingStatusClass = $bookingStatusClasses[$booking->status] ?? 'booking-status-cancelled';
        $paymentStatusClass = $paymentStatusClasses[$booking->payment_status] ?? 'payment-status-unpaid';

        $customerName = trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? ''));

        $nightCount = max(
            1,
            (strtotime($booking->check_out_date) - strtotime($booking->check_in_date)) / 86400
        );

        $assignedRooms = $booking->bookingRooms
            ->pluck('room')
            ->filter();
    @endphp

    <style>
        .detail-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            padding: 18px;
            height: 100%;
        }

        .detail-card-title {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 14px;
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
            min-width: 130px;
        }

        .info-value {
            font-weight: 700;
            text-align: right;
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

        .booking-code-box {
            border-radius: 18px;
            padding: 20px;
            background: linear-gradient(135deg, #fff7ed, #ffffff);
            border: 1px solid #fed7aa;
        }

        .booking-code-box h3 {
            font-size: 14px;
            color: #9a3412;
            margin-bottom: 6px;
        }

        .booking-code-box strong {
            font-size: 26px;
            letter-spacing: 0.5px;
        }

        .room-box {
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 14px;
            background: #f8fafc;
        }

        .quick-form-box {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            padding: 18px;
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
                    <p>Theo dõi thông tin khách, phòng được gán, thanh toán và trạng thái booking</p>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">
                        Quay lại
                    </a>
                </div>

            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    Vui lòng kiểm tra lại thông tin cập nhật.
                </div>
            @endif

            <div class="row g-4 mb-4">

                <div class="col-lg-8">

                    <div class="booking-code-box h-100 d-flex flex-column justify-content-between">

                        <h3>Mã booking</h3>

                        <strong>{{ $booking->booking_code }}</strong>

                        <div class="mt-3 d-flex gap-2 flex-wrap">
                            <span class="booking-badge {{ $bookingStatusClass }}">
                                {{ $bookingStatusLabels[$booking->status] ?? $booking->status }}
                            </span>

                            <span class="booking-badge {{ $paymentStatusClass }}">
                                {{ $paymentStatusLabels[$booking->payment_status] ?? $booking->payment_status }}
                            </span>
                        </div>

                        @if ($booking->status == 'confirmed')

                            <form action="{{ route('admin.bookings.check-in', $booking->id) }}" method="POST" class="mt-3"
                                onsubmit="return confirm('Xác nhận cho khách nhận phòng?')">

                                @csrf
                                @method('PATCH')

                                <button type="submit" class="btn btn-success">
                                    <i class="bx bx-log-in-circle me-1"></i>
                                    Nhận phòng
                                </button>

                            </form>

                        @endif

                        @if ($booking->status == 'checked_in' && !$hasInspection)
                            <form action="{{ route('admin.bookings.request-inspection', $booking->id) }}" method="POST"
                                class="mt-3" onsubmit="return confirm('Chuyển phòng sang trạng thái chờ kiểm tra?')">

                                @csrf
                                @method('PATCH')

                                <button type="submit" class="btn btn-warning">
                                    <i class="bx bx-search-alt me-1"></i>
                                    Yêu cầu kiểm tra phòng
                                </button>

                            </form>

                        @endif

                        @if ($booking->status == 'checked_in' && $allInspectionsConfirmed)
                            <form action="{{ route('admin.bookings.check-out', $booking->id) }}" method="POST" class="mt-3"
                                onsubmit="return confirm('Xác nhận check-out cho booking này?')">

                                @csrf
                                @method('PATCH')

                                <button type="submit" class="btn btn-danger">
                                    <i class="bx bx-log-out-circle me-1"></i>
                                    Check-out
                                </button>

                            </form>

                        @endif

                        @if ($booking->status == 'checked_in' && $hasInspection && !$allInspectionsConfirmed)

                            <div class="alert alert-warning mt-3 mb-0">
                                Phòng đã được yêu cầu kiểm tra. Cần quản lý tầng báo cáo và admin duyệt xong mới được check-out.
                            </div>

                        @endif

                        <p class="text-muted small mb-0 mt-3">
                            Tạo lúc:
                            {{ $booking->created_at ? $booking->created_at->format('d/m/Y H:i') : '---' }}
                        </p>

                        <div class="col-lg-12">

                            <div class="detail-card">

                                <h5 class="detail-card-title">
                                    Ghi chú booking
                                </h5>

                                <p class="mb-0" style="white-space: pre-line;">
                                    {{ $booking->note ?: 'Không có ghi chú.' }}
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-lg-4">

                    <div class="quick-form-box">

                        <h5 class="fw-bold mb-3">
                            Cập nhật nhanh
                        </h5>

                        <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST">

                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">
                                    Trạng thái booking
                                </label>

                                <select name="status" class="form-select" required>
                                    @foreach ($bookingStatusLabels as $key => $label)
                                        <option value="{{ $key }}" @selected($booking->status == $key)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('status')
                                    <div class="text-danger small mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Trạng thái thanh toán
                                </label>

                                <select name="payment_status" class="form-select" required>
                                    @foreach ($paymentStatusLabels as $key => $label)
                                        <option value="{{ $key }}" @selected($booking->payment_status == $key)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('payment_status')
                                    <div class="text-danger small mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Ghi chú nội bộ
                                </label>

                                <textarea name="note" rows="4" class="form-control"
                                    placeholder="Nhập ghi chú cho booking nếu có">{{ old('note', $booking->note) }}</textarea>

                                @error('note')
                                    <div class="text-danger small mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-gold w-100">
                                Lưu cập nhật
                            </button>

                        </form>

                    </div>

                </div>

            </div>

            <div class="row g-4">

                <div class="col-lg-4">

                    <div class="detail-card">

                        <h5 class="detail-card-title">
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

                </div>

                <div class="col-lg-4">

                    <div class="detail-card">

                        <h5 class="detail-card-title">
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
                                {{ $booking->adult_count }} người lớn /
                                {{ $booking->child_count }} trẻ em
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Số phòng</div>
                            <div class="info-value">{{ $booking->room_quantity }} phòng</div>
                        </div>

                    </div>

                </div>

                <div class="col-lg-4">

                    <div class="detail-card">

                        <h5 class="detail-card-title">
                            Thanh toán
                        </h5>

                        @php
                            $roomTotal = max(0, $booking->estimated_total - $approvedDamageTotal);
                            $finalTotal = $booking->estimated_total;
                            $remainingTotal = max(0, $finalTotal - $booking->deposit_amount);
                        @endphp

                        <div class="info-row">
                            <div class="info-label">Tiền phòng</div>
                            <div class="info-value">
                                {{ number_format($roomTotal, 0, ',', '.') }}đ
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
                            <div class="info-value">
                                -{{ number_format($booking->deposit_amount, 0, ',', '.') }}đ
                            </div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Còn lại cần thanh toán</div>
                            <div class="info-value text-danger">
                                {{ number_format($remainingTotal, 0, ',', '.') }}đ
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

                </div>

                <div class="col-lg-6">

                    <div class="detail-card">

                        <h5 class="detail-card-title">
                            Phòng được gán
                        </h5>

                        @if ($assignedRooms->count() > 0)

                            <div class="d-flex flex-column gap-3">

                                @foreach ($assignedRooms as $assignedRoom)

                                    <div class="room-box">

                                        <div class="d-flex justify-content-between align-items-start gap-3">

                                            <div>
                                                <div class="fw-bold fs-5">
                                                    Phòng {{ $assignedRoom->room_number }}
                                                </div>

                                                <div class="text-muted small">
                                                    Tầng {{ $assignedRoom->floor_number ?? '---' }}
                                                </div>

                                                <div class="text-muted small">
                                                    Trạng thái hiện tại:
                                                    {{ $assignedRoom->status }}
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

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

@endsection