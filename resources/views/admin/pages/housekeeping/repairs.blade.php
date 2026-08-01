@extends('layouts.admin')

@section('title', 'Phòng cần sửa')

@section('content')
<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('admin.dashboard') }}">Admin</a> / Buồng phòng / Phòng cần sửa
        </p>

        <div class="admin-page-head">
            <div>
                <h2>Phòng cần sửa</h2>
            </div>
            <span class="badge bg-danger fs-6">{{ $pendingCount }} việc chưa xong</span>
        </div>

        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="settings-section mb-4">
            <div class="btn-group" role="group">
                <a href="{{ route('admin.housekeeping.repairs', ['status' => 'pending']) }}" class="btn {{ $status === 'pending' ? 'btn-primary' : 'btn-outline-primary' }}">Chưa sửa</a>
                <a href="{{ route('admin.housekeeping.repairs', ['status' => 'completed']) }}" class="btn {{ $status === 'completed' ? 'btn-primary' : 'btn-outline-primary' }}">Đã sửa</a>
                <a href="{{ route('admin.housekeeping.repairs', ['status' => 'all']) }}" class="btn {{ $status === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">Tất cả</a>
            </div>
        </div>

        <div class="settings-section">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Phòng cần sửa</th>
                            <th>Sự cố</th>
                            <th>Booking</th>
                            <th>Quản lý duyệt</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($issues as $issue)
                            <tr>
                                <td>
                                    <div class="fw-bold fs-5">Phòng {{ $issue->currentRoom?->room_number ?? '---' }}</div>
                                    <div class="small text-muted">Tầng {{ $issue->currentRoom?->floor_number ?? '---' }} · {{ $issue->currentRoom?->category?->name ?? '---' }}</div>
                                </td>
                                <td style="min-width:280px">
                                    <div>{{ $issue->issue_description }}</div>
                                    @if ($issue->attachments->isNotEmpty())
                                        <div class="d-flex gap-1 flex-wrap mt-2">
                                            @foreach ($issue->attachments->take(3) as $attachment)
                                                <a href="{{ route('admin.room-issues.attachments.show', $attachment) }}" target="_blank">
                                                    <img src="{{ route('admin.room-issues.attachments.show', $attachment) }}" alt="Ảnh sự cố"
                                                        style="width:64px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #ddd">
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $issue->booking?->booking_code }}</div>
                                    <div class="small text-muted">{{ $issue->booking?->booked_customer_name }}</div>
                                </td>
                                <td>
                                    <div>{{ $issue->reviewer?->name ?? '---' }}</div>
                                    <div class="small text-muted">{{ optional($issue->reviewed_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</div>
                                </td>
                                <td>
                                    @if ($issue->repair_status === 'completed')
                                        <span class="badge bg-success">Đã sửa xong</span>
                                        <div class="small text-muted mt-1">{{ $issue->repairCompleter?->name }}<br>{{ optional($issue->repair_completed_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</div>
                                    @else
                                        <span class="badge bg-danger">Cần xử lý ngay</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($issue->repair_status === 'waiting')
                                        <form action="{{ route('admin.housekeeping.repairs.complete', $issue) }}" method="POST"
                                            onsubmit="return confirm('Xác nhận đã khắc phục xong sự cố phòng này?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-success btn-sm" type="submit">Đã sửa xong</button>
                                        </form>
                                    @else
                                        <span class="text-muted">Hoàn tất</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Không có phòng cần sửa trong bộ lọc này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $issues->links() }}</div>
        </div>
    </main>
</div>
@endsection
