@extends('layouts.admin')

@section('title', 'Duyệt kiểm tra phòng')

@section('content')
@php
    $stageLabels = [
        'housekeeping_report' => 'Buồng phòng đang kiểm tra',
        'guest_consultation' => 'Lễ tân đang trao đổi với khách',
        'housekeeping_recheck' => 'Buồng phòng đang kiểm tra lại',
        'admin_approval' => 'Khách đã đồng ý · chờ admin xác nhận',
        'completed' => 'Đã hoàn tất',
    ];
    $stageClasses = [
        'housekeeping_report' => 'bg-secondary',
        'guest_consultation' => 'bg-info text-dark',
        'housekeeping_recheck' => 'bg-warning text-dark',
        'admin_approval' => 'bg-primary',
        'completed' => 'bg-success',
    ];
@endphp

<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3"><a href="{{ route('admin.dashboard') }}">Admin</a> / Duyệt kiểm tra phòng</p>
        <div class="admin-page-head">
            <div>
                <h2>Duyệt kiểm tra phòng</h2>
                <p>Admin chỉ xác nhận khi khách đã đồng ý kết quả hiện tại. Phiếu còn tranh luận sẽ tiếp tục quay lại lễ tân và buồng phòng.</p>
            </div>
        </div>
        @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <div class="settings-section">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Booking</th><th>Phòng</th><th>Khoản hiện tại</th><th>Tình trạng</th><th>Cập nhật gần nhất</th><th class="text-end">Thao tác</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($inspections as $inspection)
                            @php
                                $stage = $inspection->workflow_stage ?? 'admin_approval';
                                $customerName = trim(($inspection->booking->customer->last_name ?? '') . ' ' . ($inspection->booking->customer->first_name ?? ''));
                                $proposedTotal = $inspection->items->sum('total');
                                $unseen = (int) $inspection->admin_acknowledged_version < (int) $inspection->version;
                            @endphp
                            <tr class="{{ $stage === 'admin_approval' && $unseen ? 'table-warning' : '' }}">
                                <td><strong>{{ $inspection->booking->booking_code ?? '---' }}</strong><div class="small text-muted">{{ $customerName ?: 'Chưa có tên' }}</div></td>
                                <td><strong>{{ $inspection->room->room_number ?? '---' }}</strong><div class="small text-muted">Tầng {{ $inspection->room->floor_number ?? '---' }}</div></td>
                                <td><strong>{{ number_format((float) $proposedTotal, 0, ',', '.') }}đ</strong><div class="small text-muted">{{ $inspection->items->count() }} hạng mục</div></td>
                                <td><span class="badge {{ $stageClasses[$stage] ?? 'bg-secondary' }}">{{ $stageLabels[$stage] ?? $stage }}</span></td>
                                <td>
                                    @if ($unseen && $inspection->status === 'reported')
                                        <span class="badge bg-danger">Có cập nhật mới</span>
                                    @else
                                        <span class="badge bg-success">Đã xem cập nhật</span>
                                    @endif
                                    <div class="small text-muted mt-1">{{ $inspection->last_revision_at?->format('d/m/Y H:i:s') ?: 'Chưa có' }}</div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.inspection-approvals.show', $inspection->id) }}" class="btn btn-sm {{ $stage === 'admin_approval' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                        {{ $stage === 'admin_approval' ? 'Xem và xác nhận' : 'Xem tiến độ' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Không có phiếu kiểm tra nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $inspections->links() }}</div>
        </div>
    </main>
    <footer class="admin-footer"><span>MCuong Hotel Admin</span></footer>
</div>
@endsection
