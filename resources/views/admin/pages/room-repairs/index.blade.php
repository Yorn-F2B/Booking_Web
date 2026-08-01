@extends('layouts.admin')
@section('title','Phòng cần sửa')
@section('content')
<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3"><a href="{{ route('admin.dashboard') }}">Admin</a> / Phòng cần sửa</p>

        <div class="admin-page-head">
            <div>
                <h2>Phòng cần sửa</h2>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge text-bg-danger p-2">Đỏ: sửa gấp khi khách vẫn đang ở phòng</span>
            <span class="badge bg-white text-dark border p-2">Trắng: công việc sửa phòng thông thường</span>
        </div>

        <div class="settings-section mb-3">
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select" style="max-width:260px">
                    <option value="waiting" @selected($status==='waiting')>Đang chờ sửa</option>
                    <option value="completed" @selected($status==='completed')>Đã sửa xong</option>
                    <option value="all" @selected($status==='all')>Tất cả</option>
                </select>
                <button class="btn btn-primary">Lọc</button>
            </form>
        </div>

        <div class="settings-section">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Phòng cần sửa</th>
                            <th>Sự cố</th>
                            <th>Khách đã chuyển đến</th>
                            <th>Quản lý duyệt</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($issues as $issue)
                            @php
                                $isUrgentWhileOccupied = $issue->status === 'repair_only'
                                    && $issue->repair_status === 'waiting';
                            @endphp
                            <tr class="{{ $isUrgentWhileOccupied ? 'table-danger' : '' }}">
                                <td>
                                    <strong>Phòng {{ $issue->currentRoom?->room_number }}</strong>
                                    <div class="small text-muted">{{ $issue->currentRoom?->category?->name }}</div>
                                    <div class="small text-muted">Booking {{ $issue->booking?->booking_code }}</div>
                                </td>
                                <td style="max-width:360px">{{ \Illuminate\Support\Str::limit($issue->issue_description,120) }}</td>
                                <td>{{ $issue->approvedRoom?->room_number ? 'Phòng '.$issue->approvedRoom->room_number : 'Khách vẫn ở phòng cũ' }}</td>
                                <td>
                                    {{ $issue->reviewer?->name ?? '---' }}
                                    <div class="small text-muted">{{ $issue->reviewed_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</div>
                                </td>
                                <td>
                                    @if($issue->repair_status === 'completed')
                                        <span class="badge text-bg-success">Đã sửa xong</span>
                                    @elseif($isUrgentWhileOccupied)
                                        <span class="badge text-bg-danger">Sửa gấp - khách đang ở</span>
                                    @else
                                        <span class="badge bg-white text-dark border">Cần khắc phục</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.room-repairs.show',$issue) }}" class="btn btn-sm {{ $isUrgentWhileOccupied ? 'btn-danger' : 'btn-outline-primary' }}">
                                        Xem việc
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Không có phòng phù hợp.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $issues->links() }}</div>
        </div>
    </main>
</div>
@endsection
