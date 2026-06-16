@extends('layouts.admin')

@section('title', 'Chi tiết duyệt kiểm tra phòng')

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

        $customerName = trim(($roomInspection->booking->customer->last_name ?? '') . ' ' . ($roomInspection->booking->customer->first_name ?? ''));

        $proposedTotal = $roomInspection->items->sum('total');
        $approvedTotal = $roomInspection->items->where('status', 'approved')->sum('total');
    @endphp

    <div class="admin-wrapper">

        <main class="admin-content">

            <p class="admin-breadcrumb mb-3">
                <a href="{{ route('admin.dashboard') }}">Admin</a> /
                <a href="{{ route('admin.inspection-approvals.index') }}">Duyệt kiểm tra phòng</a> /
                Chi tiết duyệt
            </p>

            <div class="admin-page-head">

                <div>
                    <h2>Duyệt kiểm tra phòng {{ $roomInspection->room->room_number ?? '---' }}</h2>
<p>Admin xác nhận từng hạng mục minibar và hư hại trước khi cộng vào đơn thanh toán</p>
                </div>

                <a href="{{ route('admin.inspection-approvals.index') }}" class="btn btn-outline-secondary">
                    Quay lại
                </a>

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

            @if ($errors->any())
                <div class="alert alert-danger">
                    Vui lòng kiểm tra lại thông tin duyệt.
                </div>
            @endif

            <div class="row g-4">

                <div class="col-lg-4">

                    <div class="settings-section h-100">

                        <h5 class="fw-bold mb-3">
                            Thông tin phiếu
                        </h5>

                        <div class="mb-3">
                            <div class="text-muted small">Trạng thái duyệt</div>
                            <span class="badge {{ $statusClasses[$roomInspection->status] ?? 'bg-secondary' }}">
                                {{ $statusLabels[$roomInspection->status] ?? $roomInspection->status }}
                            </span>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Mã booking</div>
                            <strong>{{ $roomInspection->booking->booking_code ?? '---' }}</strong>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Khách hàng</div>
                            <strong>{{ $customerName ?: 'Chưa có tên' }}</strong>
                            <div class="text-muted small">
                                {{ $roomInspection->booking->customer->phone ?? '---' }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Phòng</div>
                            <strong>Phòng {{ $roomInspection->room->room_number ?? '---' }}</strong>
                            <div class="text-muted small">
                                Tầng {{ $roomInspection->room->floor_number ?? '---' }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Người kiểm tra</div>
                            <strong>{{ $roomInspection->inspector->name ?? '---' }}</strong>
                            <div class="text-muted small">
                                {{ $roomInspection->inspected_at ? $roomInspection->inspected_at->format('d/m/Y H:i') : '---' }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">Ghi chú kiểm tra</div>
                            <div style="white-space: pre-line;">
                                {{ $roomInspection->inspection_note ?: 'Không có ghi chú.' }}
                            </div>
                        </div>

                        <hr>

                        <div class="mb-2">
                            <div class="text-muted small">Tổng đề xuất</div>
                            <strong>{{ number_format((float) $proposedTotal, 0, ',', '.') }}đ</strong>
                        </div>

                        <div>
                            <div class="text-muted small">Tổng đã duyệt</div>
                            <strong>{{ number_format((float) $approvedTotal, 0, ',', '.') }}đ</strong>
                        </div>

                    </div>

                </div>

                <div class="col-lg-8">

                    <div class="settings-section">

                        <h5 class="fw-bold mb-3">
    Hạng mục minibar / hư hại
</h5>

                        @if (!$roomInspection->has_damage)

                            <div class="alert alert-success">
                                Quản lý tầng báo phòng không có hư hại.
                            </div>

                            @if ($roomInspection->status == 'reported')

                                <form action="{{ route('admin.inspection-approvals.approve', $roomInspection->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Xác nhận phòng không phát sinh phí hư hại?')">

                                    @csrf

                                    <div class="mb-3">
                                        <label class="form-label">
                                            Ghi chú admin
                                        </label>

                                        <textarea name="admin_note"
                                            rows="3"
                                            class="form-control"
                                            placeholder="Ghi chú nếu có"></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-success">
                                        Xác nhận không phát sinh phí
                                    </button>

                                </form>

                            @endif

                        @else

                            @if ($roomInspection->items->count() == 0)

                                <div class="alert alert-warning">
                                    Phiếu này báo có hư hại nhưng chưa có hạng mục nào.
                                </div>

                            @else

                                <form action="{{ route('admin.inspection-approvals.approve', $roomInspection->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Xác nhận duyệt các hạng mục đã chọn?')">

                                    @csrf

                                    <div class="table-responsive">

                                        <table class="table table-bordered align-middle">

                                            <thead class="table-light">

                                                <tr>
                                                    <th style="width: 70px;">Duyệt</th>
<th>Loại</th>
<th>Hạng mục</th>
                                                    <th>Đơn giá</th>
                                                    <th>Số lượng</th>
                                                    <th>Tổng</th>
                                                    <th>Lý do không duyệt</th>
                                                    <th>Trạng thái</th>
                                                </tr>

                                            </thead>

                                            <tbody>

                                                @foreach ($roomInspection->items as $item)

                                                    <tr>

                                                        <td class="text-center">
                                                            <input type="checkbox"
                                                                name="approved_item_ids[]"
                                                                value="{{ $item->id }}"
                                                                class="form-check-input approval-checkbox"
                                                                data-note-id="rejectNote{{ $item->id }}"
                                                                @checked($item->status == 'approved')
                                                                {{ $roomInspection->status != 'reported' ? 'disabled' : '' }}>
                                                        </td>

                                                        <td>
    @if ($item->type == 'minibar')
        <span class="badge bg-primary">Minibar</span>
    @elseif ($item->type == 'damage_fee')
        <span class="badge bg-danger">Hư hại</span>
    @else
        <span class="badge bg-secondary">{{ $item->type }}</span>
    @endif
</td>

                                                        <td>
                                                            <strong>{{ $item->name }}</strong>

                                                            @if ($item->unit)
                                                                <div class="text-muted small">
                                                                    Đơn vị: {{ $item->unit }}
                                                                </div>
                                                            @endif
                                                        </td>

                                                        <td>
                                                            {{ number_format((float) $item->price, 0, ',', '.') }}đ
                                                        </td>

                                                        <td>
                                                            {{ $item->quantity }}
                                                        </td>

                                                        <td>
                                                            <strong>
                                                                {{ number_format((float) $item->total, 0, ',', '.') }}đ
                                                            </strong>
                                                        </td>

                                                        <td style="min-width: 220px;">
                                                            @if ($roomInspection->status == 'reported')
                                                                <input type="text"
                                                                    name="rejection_notes[{{ $item->id }}]"
                                                                    id="rejectNote{{ $item->id }}"
                                                                    class="form-control form-control-sm rejection-note"
                                                                    value="{{ old('rejection_notes.' . $item->id, $item->admin_note) }}"
                                                                    placeholder="Nhập lý do nếu không duyệt">
                                                            @else
                                                                {{ $item->admin_note ?: '---' }}
                                                            @endif
                                                        </td>

                                                        <td>
                                                            @if ($item->status == 'approved')
                                                                <span class="badge bg-success">
                                                                    Đã duyệt
                                                                </span>
                                                            @elseif ($item->status == 'rejected')
                                                                <span class="badge bg-danger">
                                                                    Không duyệt
                                                                </span>
                                                            @else
                                                                <span class="badge bg-warning text-dark">
                                                                    Chờ duyệt
                                                                </span>
                                                            @endif
                                                        </td>

                                                    </tr>

                                                @endforeach

                                            </tbody>

                                        </table>

                                    </div>

                                    @if ($roomInspection->status == 'reported')

                                        <div class="mb-3">
                                            <label class="form-label">
                                                Ghi chú chung của admin
                                            </label>

                                            <textarea name="admin_note"
                                                rows="3"
                                                class="form-control"
                                                placeholder="Ví dụ: Chỉ duyệt các hạng mục đã xác minh với khách"></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-success">
                                            Xác nhận duyệt
                                        </button>

                                    @else

                                        <div class="alert alert-secondary mb-0">
                                            Phiếu này đã được xử lý nên không thể duyệt lại.
                                        </div>

                                    @endif

                                </form>

                            @endif

                        @endif

                    </div>

                </div>

            </div>

        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>

    </div>

    <script>
        document.querySelectorAll('.approval-checkbox').forEach(function (checkbox) {
            const noteId = checkbox.dataset.noteId;
            const noteInput = document.getElementById(noteId);

            function updateNoteState() {
                if (!noteInput) {
                    return;
                }

                if (checkbox.checked) {
                    noteInput.value = '';
                    noteInput.disabled = true;
                    noteInput.placeholder = 'Đã duyệt nên không cần lý do';
                } else {
                    noteInput.disabled = false;
                    noteInput.placeholder = 'Nhập lý do nếu không duyệt';
                }
            }

            checkbox.addEventListener('change', updateNoteState);
            updateNoteState();
        });
    </script>

@endsection