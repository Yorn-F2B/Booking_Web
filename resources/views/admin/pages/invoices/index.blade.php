@extends('layouts.admin')

@section('title', 'Danh sách hóa đơn')

@section('content')
<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('admin.dashboard') }}">Admin</a> / Hóa đơn
        </p>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Danh sách hóa đơn</h2>
        </div>

        <!-- Bộ lọc -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.invoices.index') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Ngày xuất</label>
                            <input type="date" name="date" class="form-control" value="{{ $filters['date'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mã booking</label>
                            <input type="text" name="booking_code" class="form-control" value="{{ $filters['booking_code'] ?? '' }}" placeholder="Tìm theo mã booking">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tên khách</label>
                            <input type="text" name="customer_name" class="form-control" value="{{ $filters['customer_name'] ?? '' }}" placeholder="Tìm theo tên khách">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Trạng thái thanh toán</label>
                            <select name="payment_status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="unpaid" {{ ($filters['payment_status'] ?? '') == 'unpaid' ? 'selected' : '' }}>Chưa thanh toán</option>
                                <option value="partial" {{ ($filters['payment_status'] ?? '') == 'partial' ? 'selected' : '' }}>Đã cọc</option>
                                <option value="paid" {{ ($filters['payment_status'] ?? '') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Lọc</button>
                            <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">Đặt lại</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bảng danh sách hóa đơn -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Mã hóa đơn</th>
                                <th>Mã booking</th>
                                <th>Tên khách</th>
                                <th>Phòng</th>
                                <th>Ngày nhận phòng</th>
                                <th>Ngày trả phòng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái thanh toán</th>
                                <th>Ngày xuất</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_code }}</td>
                                <td>{{ $invoice->booking->booking_code ?? '---' }}</td>
                                <td>{{ $invoice->resolved_customer_name }}</td>
                                <td>{{ $invoice->resolved_room_numbers }}</td>
                                <td>{{ $invoice->resolved_check_in_date ? \Carbon\Carbon::parse($invoice->resolved_check_in_date)->format('d/m/Y') : '---' }}</td>
                                <td>{{ $invoice->resolved_check_out_date ? \Carbon\Carbon::parse($invoice->resolved_check_out_date)->format('d/m/Y') : '---' }}</td>
                                <td>{{ number_format($invoice->resolved_final_total, 0, ',', '.') }}đ</td>
                                <td>
                                    @php
                                        $paymentStatusClass = match($invoice->effective_payment_status) {
                                            'unpaid' => 'bg-danger',
                                            'partial' => 'bg-warning text-dark',
                                            'paid' => 'bg-success',
                                            default => 'badge-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $paymentStatusClass }}">{{ $invoice->payment_status_label }}</span>
                                </td>
                                <td>{{ $invoice->issued_at ? \Carbon\Carbon::parse($invoice->issued_at)->format('d/m/Y H:i') : '---' }}</td>
                                <td>
                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Xem
                                    </a>
                                    <a href="{{ route('admin.invoices.print', $invoice) }}" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-print"></i> In
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center">Không có hóa đơn nào</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang -->
                <div class="d-flex justify-content-center mt-3">
                    {{ $invoices->links() }}
                </div>
            </div>
        </div>
    </main>

    <footer class="admin-footer">
        <span>MCuong Hotel Admin</span>
    </footer>
</div>
@endsection
