@extends('layouts.admin')

@section('title', 'Phòng cần dọn')

@section('content')

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> / Phòng cần dọn
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Phòng cần dọn</h2>
                    @if(auth()->user()?->role === 'housekeeping')
                        <p>Chỉ hiển thị phòng thuộc tầng bạn phụ trách lâu dài hoặc nhiệm vụ phòng được giao cho hôm nay.</p>
                    @else
                        <p>Danh sách toàn bộ phòng cần dọn để quản lý/trưởng buồng phòng điều phối. Phòng chưa có người phụ trách sẽ được đánh dấu rõ.</p>
                    @endif
                </div>

            </div>

<div class="settings-section">

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th>Phòng</th>
                                <th>Hạng phòng</th>
                                <th>Tầng</th>
                                <th>Trạng thái</th>
                                <th>Phụ trách</th>
                                <th>Ghi chú</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse ($rooms as $room)

                                <tr>

                                    <td>
                                        <strong>Phòng {{ $room->room_number }}</strong>
                                    </td>

                                    <td>
                                        {{ $room->category->name ?? 'Không xác định' }}
                                    </td>

                                    <td>
                                        {{ $room->floor_number ?? '---' }}
                                    </td>

                                    <td>
                                        <span class="badge bg-primary">Đang dọn dẹp</span>
                                        @if(str_contains((string) $room->note, '[PRIORITY_BOOKING:'))
                                            <span class="badge bg-danger ms-1">Ưu tiên - khách đang/chờ nhận phòng</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if(($room->housekeeping_assignees ?? collect())->isNotEmpty())
                                            @foreach($room->housekeeping_assignees as $assignee)
                                                <span class="badge bg-light text-dark border me-1 mb-1">{{ $assignee }}</span>
                                            @endforeach
                                        @else
                                            <span class="badge bg-warning text-dark">Chưa phân công</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $room->note ?: 'Không có ghi chú' }}
                                    </td>

                                    <td class="text-end">

                                        <form action="{{ route('admin.housekeeping.mark-available', $room->id) }}" method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('Xác nhận phòng đã dọn xong và sẵn sàng cho thuê?')">

                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="btn btn-sm btn-success">
                                                Dọn xong
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        Không có phòng nào cần dọn.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    {{ $rooms->links() }}
                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

@endsection