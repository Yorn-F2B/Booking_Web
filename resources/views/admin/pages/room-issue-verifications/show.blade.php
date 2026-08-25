@extends('layouts.admin')
@section('title', 'Kiểm tra sự cố phòng')
@section('content')
<div class="admin-wrapper"><main class="admin-content">
    <p class="admin-breadcrumb mb-3"><a href="{{ route('admin.dashboard') }}">Admin</a> / <a href="{{ route('admin.room-issue-verifications.index') }}">Kiểm tra sự cố</a> / Phòng {{ $roomIssueRequest->currentRoom?->room_number ?? '---' }}</p>

    <div class="admin-page-head">
        <div>
            <h2>Kiểm tra phòng {{ $roomIssueRequest->currentRoom?->room_number ?? '---' }}</h2>
            <p>Booking {{ $roomIssueRequest->booking?->booking_code }} · Khách báo: {{ $roomIssueRequest->issue_description }}</p>
        </div>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="settings-section">
                <h5 class="fw-bold">Nội dung khách báo</h5>
                <p>{{ $roomIssueRequest->issue_description }}</p>
                @if($roomIssueRequest->attachments->isNotEmpty())
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach($roomIssueRequest->attachments as $attachment)
                            <a href="{{ route('admin.room-issue-attachments.show', $attachment) }}" target="_blank">
                                <img src="{{ route('admin.room-issue-attachments.show', $attachment) }}" alt="Ảnh sự cố" style="width:110px;height:110px;object-fit:cover;border-radius:10px;border:1px solid #ddd;">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-5">
            <div class="settings-section">
                @if($roomIssueRequest->workflow_status === 'awaiting_housekeeping')
                    <h5 class="fw-bold">Kết quả kiểm tra thực tế</h5>
                    <form method="POST" action="{{ route('admin.room-issue-verifications.verify', $roomIssueRequest) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Kết luận</label>
                            <select name="verdict" id="housekeepingVerdict" class="form-select" required>
                                <option value="">-- Chọn kết quả --</option>
                                <option value="confirmed" @selected(old('verdict') === 'confirmed')>Có sự cố đúng như khách báo / có lỗi thực tế</option>
                                <option value="not_found" @selected(old('verdict') === 'not_found')>Không phát hiện sự cố</option>
                            </select>
                        </div>

                        <div class="form-check mb-3" id="repairableBox">
                            <input class="form-check-input" type="checkbox" name="can_repair_in_room" value="1" id="canRepairInRoom" @checked(old('can_repair_in_room'))>
                            <label class="form-check-label" for="canRepairInRoom">Có thể sửa ngay tại phòng, chưa cần chuyển khách</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ghi kết quả kiểm tra</label>
                            <textarea name="housekeeping_note" rows="5" class="form-control" required placeholder="Ví dụ: điều hòa không lạnh, đã kiểm tra nguồn điện và remote; cần kỹ thuật xử lý...">{{ old('housekeeping_note') }}</textarea>
                        </div>

                        <button class="btn btn-primary w-100">Xác nhận kết quả và chuyển quản lý</button>
                    </form>
                @else
                    <h5 class="fw-bold">Đã kiểm tra</h5>
                    <div class="mb-2"><strong>Kết luận:</strong> {{ $roomIssueRequest->housekeeping_verdict === 'confirmed' ? 'Có sự cố' : 'Không phát hiện sự cố' }}</div>
                    @if($roomIssueRequest->housekeeping_verdict === 'confirmed')
                        <div class="mb-2"><strong>Có thể sửa tại phòng:</strong> {{ $roomIssueRequest->housekeeping_can_repair_in_room ? 'Có' : 'Không / chưa chắc chắn' }}</div>
                    @endif
                    <div class="mb-2"><strong>Ghi chú:</strong> {{ $roomIssueRequest->housekeeping_note }}</div>
                    <div class="small text-muted">{{ $roomIssueRequest->housekeepingVerifier?->name }} · {{ $roomIssueRequest->housekeeping_verified_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</div>
                @endif
            </div>
        </div>
    </div>
</main></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const verdict = document.getElementById('housekeepingVerdict');
    const repairBox = document.getElementById('repairableBox');
    const repairInput = document.getElementById('canRepairInRoom');
    const sync = () => {
        const show = verdict?.value === 'confirmed';
        repairBox?.classList.toggle('d-none', !show);
        if (!show && repairInput) repairInput.checked = false;
    };
    verdict?.addEventListener('change', sync);
    sync();
});
</script>
@endsection
