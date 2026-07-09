@extends('layouts.admin')

@section('title', 'Chi tiết hóa đơn')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Chi tiết hóa đơn {{ $invoice->invoice_code }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.invoices.print', $invoice) }}" class="btn btn-primary" target="_blank">
                            <i class="fas fa-print"></i> In hóa đơn
                        </a>
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="invoice-container">
                        <!-- Header -->
                        <div class="row mb-4">
                            <div class="col-6">
                                <h4>HÓA ĐƠN THANH TOÁN</h4>
                                <p class="text-muted">Mã hóa đơn: {{ $invoice->invoice_code }}</p>
                            </div>
                            <div class="col-6 text-right">
                                <p><strong>Ngày xuất:</strong> {{ $invoice->issued_at ? \Carbon\Carbon::parse($invoice->issued_at)->format('d/m/Y H:i') : '---' }}</p>
                                <p><strong>Người xuất:</strong> {{ $invoice->creator->name ?? '---' }}</p>
                            </div>
                        </div>

                        <!-- Thông tin khách hàng -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Thông tin khách hàng</h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Tên khách:</strong> {{ $invoice->customer_name }}</p>
                                                <p><strong>Mã booking:</strong> {{ $invoice->booking->booking_code ?? '---' }}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Phòng:</strong> {{ $invoice->room_numbers }}</p>
                                                <p><strong>Trạng thái thanh toán:</strong> 
                                                    @php
                                                        $paymentStatusClass = match($invoice->payment_status) {
                                                            'unpaid' => 'badge-danger',
                                                            'partial' => 'badge-warning',
                                                            'paid' => 'badge-success',
                                                            default => 'badge-secondary',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $paymentStatusClass }}">{{ $invoice->payment_status_label }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin thời gian -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Thời gian lưu trú</h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Ngày nhận phòng (dự kiến):</strong> {{ \Carbon\Carbon::parse($invoice->check_in_date)->format('d/m/Y') }}</p>
                                                <p><strong>Ngày trả phòng (dự kiến):</strong> {{ \Carbon\Carbon::parse($invoice->check_out_date)->format('d/m/Y') }}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Check-in thực tế:</strong> {{ $invoice->actual_check_in ? \Carbon\Carbon::parse($invoice->actual_check_in)->format('d/m/Y H:i') : '---' }}</p>
                                                <p><strong>Check-out thực tế:</strong> {{ $invoice->actual_check_out ? \Carbon\Carbon::parse($invoice->actual_check_out)->format('d/m/Y H:i') : '---' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chi tiết phí -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Chi tiết phí</h5>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Khoản mục</th>
                                                    <th class="text-right">Số tiền</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Tiền phòng</td>
                                                    <td class="text-right">{{ number_format($invoice->room_charge, 0, ',', '.') }}đ</td>
                                                </tr>
                                                <tr>
                                                    <td>Tiền dịch vụ</td>
                                                    <td class="text-right">{{ number_format($invoice->service_charge, 0, ',', '.') }}đ</td>
                                                </tr>
                                                <tr>
                                                    <td>Tiền minibar</td>
                                                    <td class="text-right">{{ number_format($invoice->minibar_charge, 0, ',', '.') }}đ</td>
                                                </tr>
                                                <tr>
                                                    <td>Phụ thu (khách thừa, check-in sớm, check-out muộn, v.v.)</td>
                                                    <td class="text-right">{{ number_format($invoice->extra_charge, 0, ',', '.') }}đ</td>
                                                </tr>
                                                <tr>
                                                    <td>Tiền hư hại (nếu có)</td>
                                                    <td class="text-right">{{ number_format($invoice->damage_fee, 0, ',', '.') }}đ</td>
                                                </tr>
                                                <tr class="table-primary">
                                                    <td><strong>Tổng cộng</strong></td>
                                                    <td class="text-right"><strong>{{ number_format($invoice->total_amount, 0, ',', '.') }}đ</strong></td>
                                                </tr>
                                                <tr class="table-success">
                                                    <td>Tiền cọc đã thanh toán</td>
                                                    <td class="text-right">-{{ number_format($invoice->deposit_amount, 0, ',', '.') }}đ</td>
                                                </tr>
                                                <tr class="table-warning">
                                                    <td><strong>Số tiền còn lại cần thanh toán</strong></td>
                                                    <td class="text-right"><strong>{{ number_format($invoice->remaining_amount, 0, ',', '.') }}đ</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ghi chú -->
                        @if($invoice->notes)
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Ghi chú</h5>
                                        <p>{{ $invoice->notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection