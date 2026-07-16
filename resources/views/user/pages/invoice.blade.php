@extends('layouts.user')

@section('title', 'Hóa đơn booking')

@section('content')
    @php
        $paymentStatusClass = match ($invoice->effective_payment_status) {
            'paid' => 'bg-success',
            'partial' => 'bg-warning text-dark',
            default => 'bg-danger',
        };
        $resolvedCheckInDate = $invoice->resolved_check_in_date;
        $resolvedCheckOutDate = $invoice->resolved_check_out_date;
        $resolvedActualCheckIn = $invoice->resolved_actual_check_in;
        $resolvedActualCheckOut = $invoice->resolved_actual_check_out;
    @endphp

    <section class="page-header">
        <div class="container">
            <h1 class="display-6 fw-bold mb-1">Hóa đơn thanh toán</h1>
            <p class="text-muted mb-0">Khách sạn đã phát hành hóa đơn cho booking của bạn sau khi trả phòng.</p>
        </div>
    </section>

    <main class="py-5">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>
                    Quay lại booking
                </a>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('bookings.invoice.print', $booking) }}" class="btn btn-primary" target="_blank">
                        <i class="bx bx-printer me-1"></i>
                        In hóa đơn
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <h5 class="mb-3">Thông tin hóa đơn</h5>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="text-muted small">Mã hóa đơn</div>
                                    <div class="fw-semibold">{{ $invoice->invoice_code }}</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted small">Mã booking</div>
                                    <div class="fw-semibold">{{ $invoice->booking->booking_code ?? $booking->booking_code }}</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted small">Ngày xuất</div>
                                    <div class="fw-semibold">{{ $invoice->issued_at?->format('d/m/Y H:i') ?? '---' }}</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted small">Trạng thái thanh toán</div>
                                    <div>
                                        <span class="badge {{ $paymentStatusClass }}">{{ $invoice->payment_status_label }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <h5 class="mb-3">Thông tin lưu trú</h5>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="text-muted small">Khách hàng</div>
                                    <div class="fw-semibold">{{ $invoice->resolved_customer_name }}</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted small">Phòng</div>
                                    <div class="fw-semibold">{{ $invoice->resolved_room_numbers }}</div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted small">Nhận phòng dự kiến</div>
                                    <div class="fw-semibold">
                                        {{ $resolvedCheckInDate ? \Carbon\Carbon::parse($resolvedCheckInDate)->format('d/m/Y') : '---' }}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted small">Trả phòng dự kiến</div>
                                    <div class="fw-semibold">
                                        {{ $resolvedCheckOutDate ? \Carbon\Carbon::parse($resolvedCheckOutDate)->format('d/m/Y') : '---' }}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted small">Check-in thực tế</div>
                                    <div class="fw-semibold">
                                        {{ $resolvedActualCheckIn ? \Carbon\Carbon::parse($resolvedActualCheckIn)->format('d/m/Y H:i') : '---' }}
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="text-muted small">Check-out thực tế</div>
                                    <div class="fw-semibold">
                                        {{ $resolvedActualCheckOut ? \Carbon\Carbon::parse($resolvedActualCheckOut)->format('d/m/Y H:i') : '---' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Chi tiết thanh toán</h5>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Khoản mục</th>
                                    <th class="text-end">Số tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Tiền phòng</td>
                                    <td class="text-end">{{ number_format($invoice->resolved_room_charge, 0, ',', '.') }}đ</td>
                                </tr>
                                @if ($invoice->resolved_service_charge > 0)
                                    <tr>
                                        <td>Dịch vụ</td>
                                        <td class="text-end">{{ number_format($invoice->resolved_service_charge, 0, ',', '.') }}đ</td>
                                    </tr>
                                @endif
                                @if ($invoice->resolved_inspection_charge > 0)
                                    <tr>
                                        <td>Minibar / hư hại đã duyệt</td>
                                        <td class="text-end">{{ number_format($invoice->resolved_inspection_charge, 0, ',', '.') }}đ</td>
                                    </tr>
                                @endif
                                @if ($invoice->resolved_discount_amount > 0)
                                    <tr>
                                        <td>Khuyến mãi</td>
                                        <td class="text-end text-success">-{{ number_format($invoice->resolved_discount_amount, 0, ',', '.') }}đ</td>
                                    </tr>
                                @endif
                                <tr class="table-light">
                                    <td><strong>Tổng cuối</strong></td>
                                    <td class="text-end"><strong>{{ number_format($invoice->resolved_final_total, 0, ',', '.') }}đ</strong></td>
                                </tr>
                                <tr>
                                    <td>Đã thanh toán</td>
                                    <td class="text-end">{{ number_format($invoice->resolved_total_paid, 0, ',', '.') }}đ</td>
                                </tr>
                                @if ($invoice->resolved_remaining_amount > 0)
                                    <tr class="table-warning">
                                        <td><strong>Còn thiếu</strong></td>
                                        <td class="text-end"><strong>{{ number_format($invoice->resolved_remaining_amount, 0, ',', '.') }}đ</strong></td>
                                    </tr>
                                @endif
                                @if ($invoice->resolved_overpayment_amount > 0)
                                    <tr class="table-success">
                                        <td><strong>Trả dư</strong></td>
                                        <td class="text-end"><strong>{{ number_format($invoice->resolved_overpayment_amount, 0, ',', '.') }}đ</strong></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($invoice->booking && $invoice->booking->payments->where('status', 'success')->count() > 0)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Lịch sử thanh toán</h5>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Phương thức</th>
                                        <th>Mã giao dịch</th>
                                        <th class="text-end">Số tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->booking->payments->where('status', 'success') as $payment)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($payment->paid_at ?? $payment->created_at)->format('d/m/Y H:i') }}</td>
                                            <td>
                                                @switch($payment->provider)
                                                    @case('cash')
                                                        Tiền mặt
                                                        @break
                                                    @case('bank_transfer')
                                                        Chuyển khoản
                                                        @break
                                                    @case('vnpay')
                                                    @case('admin_vnpay')
                                                        VNPay
                                                        @break
                                                    @default
                                                        {{ $payment->provider ?? '---' }}
                                                @endswitch
                                            </td>
                                            <td>{{ $payment->txn_ref ?? '---' }}</td>
                                            <td class="text-end">{{ number_format((float) $payment->amount, 0, ',', '.') }}đ</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if ($invoice->notes)
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="mb-2">Ghi chú</h5>
                        <div class="text-muted">{{ $invoice->notes }}</div>
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection
