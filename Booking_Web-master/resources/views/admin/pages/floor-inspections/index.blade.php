@extends('layouts.admin')

@section('title', 'Phòng cần kiểm tra')

@section('content')

    @php
        $statusLabels = [
            'pending' => 'Chờ kiểm tra',
            'reported' => 'Đã báo cáo - chờ admin duyệt',
            'confirmed' => 'Admin đã duyệt',
            'rejected' => 'Admin từ chối',
        ];

        $statusClasses = [
            'pending' => 'bg-warning text-dark',
            'reported' => 'bg-info',
            'confirmed' => 'bg-success',
            'rejected' => 'bg-danger',
        ];
    @endphp

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Phòng cần kiểm tra
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Phòng cần kiểm tra</h2>
                    <p>Quản lý tầng ghi nhận tình trạng phòng trước khi khách check-out</p>
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
                                <th>Hạng phòng</th>
                                <th>Ngày trả dự kiến</th>
                                <th>Kết quả</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>

                        </thead>

                        <tbody>

                            @forelse ($inspections as $inspection)

                                @php
                                    $customerName = trim(($inspection->booking->customer->last_name ?? '') . ' ' . ($inspection->booking->customer->first_name ?? ''));
                                    $statusClass = $statusClasses[$inspection->status] ?? 'bg-secondary';
                                    $statusLabel = $statusLabels[$inspection->status] ?? $inspection->status;
                                    $damageTotal = $inspection->items->sum('total');
                                @endphp

                                <tr>

                                    <td>
                                        <strong>{{ $inspection->booking->booking_code ?? '---' }}</strong>

                                        <div class="text-muted small">
                                            Tạo phiếu:
                                            {{ $inspection->created_at ? $inspection->created_at->format('d/m/Y H:i') : '---' }}
                                        </div>
                                    </td>

                                    <td>
                                        <strong>{{ $customerName ?: 'Chưa có tên' }}</strong>

                                        <div class="text-muted small">
                                            {{ $inspection->booking->customer->phone ?? '---' }}
                                        </div>
                                    </td>

                                    <td>
                                        <strong>
                                            Phòng {{ $inspection->room->room_number ?? '---' }}
                                        </strong>

                                        <div class="text-muted small">
                                            Tầng {{ $inspection->room->floor_number ?? '---' }}
                                        </div>
                                    </td>

                                    <td>
                                        {{ $inspection->booking->roomCategory->name ?? '---' }}
                                    </td>

                                    <td>
                                        {{ $inspection->booking->check_out_date ? date('d/m/Y', strtotime($inspection->booking->check_out_date)) : '---' }}
                                    </td>

                                    <td>
                                        @if ($inspection->status == 'pending')
                                            <span class="text-muted">
                                                Chưa kiểm tra
                                            </span>
                                        @else
                                            @if ($inspection->has_damage)
                                                <span class="badge bg-danger">
                                                    Có hư hại
                                                </span>

                                                <div class="text-muted small mt-1">
                                                    {{ $inspection->items->count() }} hạng mục -
                                                    {{ number_format((float) $damageTotal, 0, ',', '.') }}đ
                                                </div>
                                            @else
                                                <span class="badge bg-success">
                                                    Không hư hại
                                                </span>
                                            @endif

                                            @if ($inspection->inspected_at)
                                                <div class="text-muted small mt-1">
                                                    Kiểm tra:
                                                    {{ $inspection->inspected_at->format('d/m/Y H:i') }}
                                                </div>
                                            @endif
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>

                                        @if ($inspection->confirmed_at)
                                            <div class="text-muted small mt-1">
                                                Duyệt:
                                                {{ $inspection->confirmed_at->format('d/m/Y H:i') }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-end">

                                        @if (in_array($inspection->status, ['pending', 'reported', 'rejected']))
                                            <a href="{{ route('admin.floor-inspections.show', $inspection->id) }}"
                                                class="btn btn-sm btn-primary">
                                                Kiểm tra / Sửa
                                            </a>
                                        @else
                                            <a href="{{ route('admin.floor-inspections.show', $inspection->id) }}"
                                                class="btn btn-sm btn-outline-secondary">
                                                Xem
                                            </a>
                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        Không có phòng nào cần kiểm tra.
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