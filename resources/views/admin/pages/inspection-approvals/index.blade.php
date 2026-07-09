@extends('layouts.admin')

@section('title', 'Duyệt kiểm tra phòng')

@section('content')

    @php
        $statusLabels = [
            'reported' => 'Chờ admin duyệt',
            'confirmed' => 'Đã duyệt',
            'rejected' => 'Đã từ chối',
        ];

        $statusClasses = [
            'reported' => 'bg-info',
            'confirmed' => 'bg-success',
            'rejected' => 'bg-danger',
        ];
    @endphp

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Duyệt kiểm tra phòng
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Duyệt kiểm tra phòng</h2>
                    <p>Admin xem chi tiết và duyệt từng hạng mục hư hại trước khi tính tiền checkout</p>
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

            <div class="settings-section">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">

                            <tr>
                                <th>Mã booking</th>
                                <th>Khách hàng</th>
                                <th>Phòng</th>
                                <th>Kết quả quản lý tầng</th>
                                <th>Hư hại đề xuất</th>
                                <th>Trạng thái duyệt</th>
                                <th class="text-end">Thao tác</th>
                            </tr>

                        </thead>

                        <tbody>

                            @forelse ($inspections as $inspection)

                                @php
                                    $customerName = trim(($inspection->booking->customer->last_name ?? '') . ' ' . ($inspection->booking->customer->first_name ?? ''));
                                    $statusClass = $statusClasses[$inspection->status] ?? 'bg-secondary';
                                    $statusLabel = $statusLabels[$inspection->status] ?? $inspection->status;
                                    $proposedTotal = $inspection->items->sum('total');
                                    $approvedTotal = $inspection->items->where('status', 'approved')->sum('total');
                                @endphp

                                <tr>

                                    <td>
                                        <strong>{{ $inspection->booking->booking_code ?? '---' }}</strong>

                                        <div class="text-muted small">
                                            Báo cáo:
                                            {{ $inspection->inspected_at ? $inspection->inspected_at->format('d/m/Y H:i') : '---' }}
                                        </div>
                                    </td>

                                    <td>
                                        <strong>{{ $customerName ?: 'Chưa có tên' }}</strong>

                                        <div class="text-muted small">
                                            {{ $inspection->booking->customer->phone ?? '---' }}
                                        </div>
                                    </td>

                                    <td>
                                        <strong>Phòng {{ $inspection->room->room_number ?? '---' }}</strong>

                                        <div class="text-muted small">
                                            Tầng {{ $inspection->room->floor_number ?? '---' }}
                                        </div>
                                    </td>

                                    <td>
                                        @if ($inspection->has_damage)
                                            <span class="badge bg-danger">
                                                Có hư hại
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                Không hư hại
                                            </span>
                                        @endif

                                        <div class="text-muted small mt-1">
                                            Người kiểm tra:
                                            {{ $inspection->inspector->name ?? '---' }}
                                        </div>
                                    </td>

                                    <td>
                                        @if ($inspection->has_damage)
                                            <div>
                                                {{ $inspection->items->count() }} hạng mục
                                            </div>

                                            <div class="text-muted small">
                                                Đề xuất:
                                                {{ number_format((float) $proposedTotal, 0, ',', '.') }}đ
                                            </div>

                                            @if ($inspection->status == 'confirmed')
                                                <div class="text-muted small">
                                                    Đã duyệt:
                                                    {{ number_format((float) $approvedTotal, 0, ',', '.') }}đ
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">
                                                Không phát sinh phí
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>

                                        @if ($inspection->confirmed_at)
                                            <div class="text-muted small mt-1">
                                                {{ $inspection->confirmed_at->format('d/m/Y H:i') }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-end">

                                        <a href="{{ route('admin.inspection-approvals.show', $inspection->id) }}"
                                            class="btn btn-sm {{ $inspection->status == 'reported' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                            {{ $inspection->status == 'reported' ? 'Xem / Duyệt' : 'Xem chi tiết' }}
                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        Không có phiếu kiểm tra nào cần hiển thị.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    {{ $inspections->links() }}
                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

@endsection