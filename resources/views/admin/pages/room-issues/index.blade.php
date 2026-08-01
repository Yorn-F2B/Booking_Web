@extends('layouts.admin')
@section('title', 'Sự cố phòng')
@section('content')
<div class="admin-wrapper"><main class="admin-content">
    <p class="admin-breadcrumb mb-3"><a href="{{ route('admin.dashboard') }}">Admin</a> / Sự cố phòng</p>
    <div class="admin-page-head">
        <div><h2>Sự cố phòng</h2><p>Quản lý duyệt các yêu cầu khách báo sau khi đã nhận phòng.</p></div>
        <span class="badge text-bg-warning fs-6">{{ $pendingCount }} chờ duyệt</span>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="settings-section mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6"><label class="form-label">Tìm kiếm</label><input name="search" value="{{ $search }}" class="form-control" placeholder="Phòng, mã booking, nội dung sự cố..."></div>
            <div class="col-md-3"><label class="form-label">Trạng thái</label><select name="status" class="form-select">
                <option value="pending" @selected($status==='pending')>Chờ duyệt</option>
                <option value="approved" @selected($status==='approved')>Đã đổi phòng</option>
                <option value="repair_only" @selected($status==='repair_only')>Không còn phòng - sửa gấp</option>
                <option value="all" @selected($status==='all')>Tất cả</option>
            </select></div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill">Lọc</button><a href="{{ route('admin.room-issues.index') }}" class="btn btn-outline-secondary">Xóa</a></div>
        </form>
    </div>

    <div class="settings-section">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Phòng</th><th>Booking</th><th>Khách báo</th><th>Nội dung</th><th>Trạng thái</th><th>Thời gian</th><th></th></tr></thead>
            <tbody>
            @forelse($issues as $issue)
                @php $displayStatus=$issue->repair_status==='completed'?'repair_completed':$issue->status; $labels=['pending'=>'Chờ quản lý duyệt','approved'=>'Đã đổi phòng/hạng','repair_only'=>'Đang khắc phục','repair_completed'=>'Đã sửa xong','rejected'=>'Đã từ chối']; @endphp
                <tr>
                    <td><strong>{{ $issue->currentRoom?->room_number ?? '---' }}</strong><div class="small text-muted">{{ $issue->currentRoom?->category?->name }}</div></td>
                    <td><a href="{{ route('admin.bookings.show',$issue->booking) }}" class="fw-semibold">{{ $issue->booking?->booking_code }}</a></td>
                    <td>{{ $issue->booking?->booked_customer_name ?: '---' }}</td>
                    <td style="max-width:360px">{{ \Illuminate\Support\Str::limit($issue->issue_description,110) }}</td>
                    <td><span class="badge {{ $displayStatus==='pending'?'text-bg-warning':($displayStatus==='repair_completed'?'text-bg-success':($issue->status==='approved'?'text-bg-success':'text-bg-info')) }}">{{ $labels[$displayStatus] ?? $displayStatus }}</span></td>
                    <td>{{ $issue->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</td>
                    <td class="text-end"><a href="{{ route('admin.room-issues.show',$issue) }}" class="btn btn-sm btn-outline-primary">Xem chi tiết</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Không có yêu cầu phù hợp.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $issues->links() }}</div>
    </div>
</main></div>
@endsection
