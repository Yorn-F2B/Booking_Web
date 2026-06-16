@extends('layouts.admin')

@section('title', 'Danh sách đặt phòng')

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

        $bookingCollection = $bookings->getCollection();

        $totalBookings = $bookingCollection->count();
        $pendingBookings = $bookingCollection->where('status', 'pending')->count();
        $confirmedBookings = $bookingCollection->where('status', 'confirmed')->count();
        $checkedInBookings = $bookingCollection->where('status', 'checked_in')->count();
        $checkedOutBookings = $bookingCollection->where('status', 'checked_out')->count();
        $cancelledBookings = $bookingCollection->where('status', 'cancelled')->count();
    @endphp

    <style>
        .booking-stat-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 14px 16px;
            background: #fff;
            height: 100%;
        }

        .booking-stat-card span {
            display: block;
            font-size: 13px;
            color: #64748b;
        }

        .booking-stat-card strong {
            display: block;
            font-size: 24px;
            margin-top: 4px;
        }

        .booking-filter-box {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 16px;
            background: #fff;
            margin-bottom: 18px;
        }

        .booking-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
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

        .booking-code {
            font-weight: 800;
            color: #1f2937;
        }

        .booking-sub-text {
            font-size: 12px;
            color: #64748b;
        }

        .booking-action-group {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
            flex-wrap: wrap;
        }
    </style>

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Đặt phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Danh sách đặt phòng</h2>
                    <p>Theo dõi booking, trạng thái nhận phòng, thanh toán và xử lý vận hành</p>
                </div>

                <a href="{{ route('admin.bookings.create') }}" class="btn btn-gold">
                    <i class="bx bx-plus"></i>
                    Tạo booking
                </a>

            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="booking-filter-box">

                <form action="{{ route('admin.bookings.index') }}" method="GET">

                    <div class="row g-3 align-items-end">

                        <div class="col-md-3">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}"
                                placeholder="Mã booking, tên khách, SĐT...">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Trạng thái booking</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả</option>

                                @foreach ($bookingStatusLabels as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Thanh toán</label>
                            <select name="payment_status" class="form-select">
                                <option value="">Tất cả</option>

                                @foreach ($paymentStatusLabels as $key => $label)
                                    <option value="{{ $key }}" {{ request('payment_status') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Từ ngày nhận</label>
                            <input type="date" name="check_in_from" class="form-control"
                                value="{{ request('check_in_from') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Đến ngày nhận</label>
                            <input type="date" name="check_in_to" class="form-control" value="{{ request('check_in_to') }}">
                        </div>

                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                Lọc
                            </button>
                        </div>

                        <div class="col-md-12">
                            <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary btn-sm">
                                Reset bộ lọc
                            </a>
                        </div>

                    </div>

                </form>

            </div>

            <div class="settings-section">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>
                                <th>Mã booking</th>
                                <th>Khách hàng</th>
                                <th>Thông tin phòng</th>
                                <th>Thời gian lưu trú</th>
                                <th>Khách</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>

                        </thead>

                        <tbody>

                            @forelse ($bookings as $booking)

                                @php
                                    $bookingStatusClass = $bookingStatusClasses[$booking->status] ?? 'booking-status-cancelled';
                                    $paymentStatusClass = $paymentStatusClasses[$booking->payment_status] ?? 'payment-status-unpaid';

                                    $customerName = trim(($booking->customer->last_name ?? '') . ' ' . ($booking->customer->first_name ?? ''));
                                    $customerPhone = $booking->customer->phone ?? null;

                                    $roomNumbers = $booking->bookingRooms
                                        ? $booking->bookingRooms->pluck('room.room_number')->filter()->implode(', ')
                                        : '';
                                @endphp

                                <tr>

                                    <td>
                                        <div class="booking-code">
                                            {{ $booking->booking_code }}
                                        </div>

                                    </td>

                                    <td>
                                        <strong>
                                            {{ $customerName ?: 'Chưa có tên' }}
                                        </strong>


                                    </td>

                                    <td>
                                        <strong>
                                            {{ $booking->roomCategory->name ?? 'Không xác định' }}
                                        </strong>

                                        <div class="booking-sub-text">
                                            Phòng thực tế:
                                            {{ $roomNumbers ?: 'Chưa gán' }}
                                        </div>
                                    </td>

                                    <td>
                                        <strong>
                                            {{ date('d/m/Y', strtotime($booking->check_in_date)) }}
                                            -
                                            {{ date('d/m/Y', strtotime($booking->check_out_date)) }}
                                        </strong>

                                        <div class="booking-sub-text">
                                            @php
                                                $nightCount = max(
                                                    1,
                                                    (strtotime($booking->check_out_date) - strtotime($booking->check_in_date)) / 86400,
                                                );
                                            @endphp

                                            {{ $nightCount }} đêm
                                        </div>
                                    </td>

                                    <td>
                                        <strong>
                                            {{ $booking->adult_count }} NL
                                            /
                                            {{ $booking->child_count }} TE
                                        </strong>
                                    </td>

                                    <td>
                                        <strong>
                                            {{ number_format($booking->estimated_total, 0, ',', '.') }}đ
                                        </strong>

                                        <div class="booking-sub-text">
                                            Cọc:
                                            {{ number_format($booking->deposit_amount, 0, ',', '.') }}đ
                                        </div>
                                    </td>

                                    <td>
                                        @if (in_array($booking->payment_status, ['paid', 'refunded']))
                                            <span class="booking-badge {{ $paymentStatusClass }}">
                                                {{ $paymentStatusLabels[$booking->payment_status] ?? $booking->payment_status }}
                                            </span>
                                        @else
                                            <form action="{{ route('admin.bookings.update-payment-status', $booking->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('PATCH')

                                                <select name="payment_status"
                                                    class="form-select form-select-sm {{ $paymentStatusClass }}"
                                                    onchange="this.form.submit()">

                                                    @if ($booking->payment_status == 'unpaid')
                                                        <option value="unpaid" selected>Chưa thanh toán</option>
                                                        <option value="partial">Đã cọc</option>
                                                        <option value="paid">Đã thanh toán</option>
                                                        <option value="refunded">Đã hoàn tiền</option>
                                                    @elseif ($booking->payment_status == 'partial')
                                                        <option value="partial" selected>Đã cọc</option>
                                                        <option value="paid">Đã thanh toán</option>
                                                        <option value="refunded">Đã hoàn tiền</option>
                                                    @endif

                                                </select>
                                            </form>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="booking-badge {{ $bookingStatusClass }}">
                                            {{ $bookingStatusLabels[$booking->status] ?? $booking->status }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="booking-action-group">

                                            <a href="{{ route('admin.bookings.show', $booking->id) }}"
                                                class="btn btn-sm btn-outline-secondary">
                                                Xem
                                            </a>

                                            <form action="{{ route('admin.bookings.destroy', $booking->id) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Bạn có chắc muốn hủy booking này không?')">

                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Hủy
                                                </button>

                                            </form>

                                        </div>
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        Chưa có booking nào
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    {{ $bookings->appends(request()->query())->links() }}
                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

@endsection