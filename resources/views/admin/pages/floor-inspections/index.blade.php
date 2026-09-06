@extends('layouts.admin')

@section('title', 'Phòng cần kiểm tra')

@section('content')
@php
    $stageLabels = [
        'housekeeping_report' => 'Chờ kiểm tra ban đầu',
        'guest_consultation' => 'Chờ lễ tân trao đổi',
        'housekeeping_recheck' => 'Cần kiểm tra lại',
        'completed' => 'Đã hoàn tất',
    ];
    $stageClasses = [
        'housekeeping_report' => 'bg-warning text-dark',
        'guest_consultation' => 'bg-info text-dark',
        'housekeeping_recheck' => 'bg-danger',
        'completed' => 'bg-success',
    ];
@endphp

<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3"><a href="{{ route('admin.dashboard') }}">Admin</a> / Phòng cần kiểm tra</p>
        <div class="admin-page-head">
            <div>
                <h2>Phòng cần kiểm tra</h2>
                <p>Mỗi booking được gom thành một nhóm. Hãy kiểm tra hết các phòng trong cùng booking trước khi chuyển sang đơn khác.</p>
            </div>
        </div>

        <div class="settings-section">
            @forelse ($bookingGroups as $group)
                @php
                    $booking = $group->booking;
                    $customerName = trim(($booking?->customer?->last_name ?? '') . ' ' . ($booking?->customer?->first_name ?? ''));
                    $hasActionable = $group->actionable_count > 0;
                    $roomsText = $group->inspections
                        ->map(fn ($inspection) => $inspection->room?->room_number)
                        ->filter()
                        ->implode(', ');
                @endphp

                <div class="border rounded-3 mb-3 overflow-hidden {{ $hasActionable ? 'border-warning' : '' }}">
                    <div class="p-3 bg-light d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <strong class="fs-5">{{ $booking?->booking_code ?? ('Booking #' . $group->booking_id) }}</strong>
                                @if ($hasActionable)
                                    <span class="badge bg-warning text-dark">{{ $group->actionable_count }} phòng cần xử lý</span>
                                @else
                                    <span class="badge bg-success">Đã xử lý hết</span>
                                @endif
                            </div>
                            <div class="small text-muted">
                                {{ $customerName ?: 'Chưa có tên khách' }}
                                @if ($booking?->customer?->phone)
                                    · {{ $booking->customer->phone }}
                                @endif
                                · {{ $group->total_rooms }} phòng
                                @if ($roomsText)
                                    · Phòng {{ $roomsText }}
                                @endif
                            </div>
                        </div>

                        @if ($group->next_inspection)
                            <a href="{{ route('admin.floor-inspections.show', $group->next_inspection->id) }}"
                               class="btn {{ $hasActionable ? 'btn-primary' : 'btn-outline-secondary' }}">
                                {{ $hasActionable ? 'Bắt đầu / tiếp tục kiểm tra' : 'Xem kết quả' }}
                            </a>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Phòng</th>
                                    <th>Kết quả tạm tính</th>
                                    <th>Trạng thái</th>
                                    <th>Cập nhật</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group->inspections as $inspection)
                                    @php
                                        $stage = $inspection->workflow_stage ?? 'housekeeping_report';
                                        $total = $inspection->items->sum('total');
                                        $disputed = $inspection->items->where('guest_response', 'disputed')->count();
                                        $actionable = ($stage === 'housekeeping_report' && in_array($inspection->status, ['pending', 'rejected']))
                                            || ($stage === 'housekeeping_recheck' && $inspection->status === 'reported');
                                    @endphp
                                    <tr class="{{ $stage === 'housekeeping_recheck' ? 'table-danger' : '' }}">
                                        <td>
                                            <strong>Phòng {{ $inspection->room?->room_number ?? '---' }}</strong>
                                            <div class="small text-muted">Tầng {{ $inspection->room?->floor_number ?? '---' }} · Phiếu #{{ $inspection->id }}</div>
                                        </td>
                                        <td>
                                            @if ($inspection->items->isEmpty())
                                                <span class="text-muted">Không phát sinh</span>
                                            @else
                                                <strong>{{ number_format((float) $total, 0, ',', '.') }}đ</strong>
                                                <div class="small text-muted">{{ $inspection->items->count() }} hạng mục</div>
                                                @if ($disputed > 0)
                                                    <div class="small text-danger">{{ $disputed }} mục khách phản hồi</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $stageClasses[$stage] ?? 'bg-secondary' }}">{{ $stageLabels[$stage] ?? $stage }}</span>
                                        </td>
                                        <td>
                                            <div class="small">{{ $inspection->last_revision_at?->format('d/m/Y H:i:s') ?? $inspection->updated_at?->format('d/m/Y H:i:s') }}</div>
                                            <div class="small text-muted text-truncate" style="max-width:340px">{{ $inspection->last_update_summary }}</div>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.floor-inspections.show', $inspection->id) }}"
                                               class="btn btn-sm {{ $actionable ? 'btn-primary' : 'btn-outline-secondary' }}">
                                                {{ $stage === 'housekeeping_recheck' ? 'Kiểm tra lại' : ($stage === 'housekeeping_report' ? 'Kiểm tra' : 'Xem') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">Không có booking nào có phòng cần kiểm tra.</div>
            @endforelse

            <div class="mt-3">{{ $bookingGroups->links() }}</div>
        </div>
    </main>
    <footer class="admin-footer"><span>MCuong Hotel Admin</span></footer>
</div>
@endsection
