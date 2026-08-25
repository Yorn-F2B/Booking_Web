@extends('layouts.admin')
@section('title', 'Kiểm tra sự cố phòng')
@section('content')
<div class="admin-wrapper"><main class="admin-content">
    <p class="admin-breadcrumb mb-3"><a href="{{ route('admin.dashboard') }}">Admin</a> / Kiểm tra sự cố phòng</p>
    <div class="admin-page-head">
        <div>
            <h2>Kiểm tra sự cố phòng</h2>
            <p>Buồng phòng xác minh thực tế trước khi chuyển phiếu sang quản lý.</p>
        </div>
    </div>

    <div class="settings-section mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Trạng thái</label>
                <select name="status" class="form-select">
                    <option value="waiting" @selected($status === 'waiting')>Chờ kiểm tra</option>
                    <option value="verified" @selected($status === 'verified')>Đã kiểm tra</option>
                    <option value="all" @selected($status === 'all')>Tất cả</option>
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-primary w-100">Lọc</button></div>
        </form>
    </div>

    <div class="settings-section">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Phòng</th><th>Booking</th><th>Nội dung khách báo</th><th>Kết quả kiểm tra</th><th>Thời gian</th><th></th></tr></thead>
                <tbody>
                @forelse($issues as $issue)
                    <tr>
                        <td><strong>{{ $issue->currentRoom?->room_number ?? '---' }}</strong><div class="small text-muted">Tầng {{ $issue->currentRoom?->floor_number ?? '---' }}</div></td>
                        <td>{{ $issue->booking?->booking_code ?? '---' }}</td>
                        <td style="max-width:380px">{{ \Illuminate\Support\Str::limit($issue->issue_description, 130) }}</td>
                        <td>
                            @if($issue->workflow_status === 'awaiting_housekeeping')
                                <span class="badge text-bg-warning">Chờ kiểm tra</span>
                            @elseif($issue->housekeeping_verdict === 'confirmed')
                                <span class="badge text-bg-success">Có sự cố</span>
                                @if($issue->housekeeping_can_repair_in_room)<span class="badge text-bg-info">Có thể sửa tại phòng</span>@endif
                            @else
                                <span class="badge text-bg-secondary">Không phát hiện lỗi</span>
                            @endif
                        </td>
                        <td>{{ $issue->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.room-issue-verifications.show', $issue) }}">Xem / kiểm tra</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Không có phiếu phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $issues->links() }}</div>
    </div>
</main></div>
@endsection
