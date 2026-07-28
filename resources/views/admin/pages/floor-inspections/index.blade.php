@extends('layouts.admin')

@section('title', 'Phòng cần kiểm tra')

@section('content')
@php
    $stageLabels = [
        'housekeeping_report' => 'Chờ kiểm tra ban đầu',
        'guest_consultation' => 'Chờ lễ tân trao đổi',
        'housekeeping_recheck' => 'Cần kiểm tra lại',
        'admin_approval' => 'Chờ admin xác nhận',
        'completed' => 'Đã hoàn tất',
    ];
    $stageClasses = [
        'housekeeping_report' => 'bg-warning text-dark',
        'guest_consultation' => 'bg-info text-dark',
        'housekeeping_recheck' => 'bg-danger',
        'admin_approval' => 'bg-primary',
        'completed' => 'bg-success',
    ];
@endphp

<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3"><a href="{{ route('admin.dashboard') }}">Admin</a> / Phòng cần kiểm tra</p>
        <div class="admin-page-head">
            <div><h2>Phòng cần kiểm tra</h2><p>Buồng phòng kiểm tra ban đầu và xử lý riêng các khoản khách yêu cầu xác minh lại.</p></div>
        </div>

        @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

        <div class="settings-section">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Mã booking</th><th>Khách hàng</th><th>Phòng</th><th>Kết quả tạm tính</th><th>Bước hiện tại</th><th>Cập nhật</th><th class="text-end">Thao tác</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($inspections as $inspection)
                            @php
                                $customerName = trim(($inspection->booking->customer->last_name ?? '') . ' ' . ($inspection->booking->customer->first_name ?? ''));
                                $stage = $inspection->workflow_stage ?? 'housekeeping_report';
                                $total = $inspection->items->sum('total');
                                $disputed = $inspection->items->where('guest_response', 'disputed')->count();
                            @endphp
                            <tr class="{{ $stage === 'housekeeping_recheck' ? 'table-danger' : '' }}">
                                <td><strong>{{ $inspection->booking->booking_code ?? '---' }}</strong><div class="small text-muted">Phiếu #{{ $inspection->id }} · bản {{ $inspection->version }}</div></td>
                                <td><strong>{{ $customerName ?: 'Chưa có tên' }}</strong><div class="small text-muted">{{ $inspection->booking->customer->phone ?? '---' }}</div></td>
                                <td><strong>Phòng {{ $inspection->room->room_number ?? '---' }}</strong><div class="small text-muted">Tầng {{ $inspection->room->floor_number ?? '---' }}</div></td>
                                <td>
                                    @if ($inspection->items->isEmpty())
                                        <span class="text-muted">Không phát sinh</span>
                                    @else
                                        <strong>{{ number_format((float) $total, 0, ',', '.') }}đ</strong>
                                        <div class="small text-muted">{{ $inspection->items->count() }} hạng mục</div>
                                        @if ($disputed > 0)<div class="small text-danger">{{ $disputed }} mục khách phản hồi</div>@endif
                                    @endif
                                </td>
                                <td><span class="badge {{ $stageClasses[$stage] ?? 'bg-secondary' }}">{{ $stageLabels[$stage] ?? $stage }}</span></td>
                                <td><div class="small">{{ $inspection->last_revision_at?->format('d/m/Y H:i:s') ?? $inspection->updated_at?->format('d/m/Y H:i:s') }}</div><div class="small text-muted text-truncate" style="max-width:260px">{{ $inspection->last_update_summary }}</div></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.floor-inspections.show', $inspection->id) }}" class="btn btn-sm {{ in_array($stage, ['housekeeping_report','housekeeping_recheck']) ? 'btn-primary' : 'btn-outline-secondary' }}">
                                        {{ $stage === 'housekeeping_recheck' ? 'Kiểm tra lại' : (in_array($stage, ['housekeeping_report']) ? 'Kiểm tra' : 'Xem') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Không có phòng nào cần kiểm tra.</td></tr>
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
