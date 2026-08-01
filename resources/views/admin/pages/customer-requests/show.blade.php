@extends('layouts.admin')
@section('content')
<div class="admin-wrapper">
<main class="admin-content">
<div class="container-fluid px-0">
    <div class="admin-page-head d-flex align-items-center justify-content-between">
        <h1 class="mb-0">Chi tiết yêu cầu đến muộn</h1>
        <a class="btn btn-outline-secondary" href="{{ route('admin.customer-requests.index') }}">Quay lại</a>
    </div>
    <div id="lateArrivalUpdateBanner" class="alert alert-warning d-flex align-items-center justify-content-between gap-2 flex-wrap {{ ($hasUnseenUpdate && $customerRequest->status === 'pending') ? '' : 'd-none' }}">
        <div><strong>Khách vừa cập nhật yêu cầu.</strong></div>
        @if($customerRequest->status === 'pending')
            <form id="lateArrivalAcknowledgeForm" method="POST" action="{{ route('admin.customer-requests.acknowledge', $customerRequest) }}">
                @csrf
                <input id="lateArrivalAcknowledgeVersion" type="hidden" name="version" value="{{ $pageVersion }}">
                <button id="lateArrivalAcknowledgeButton" class="btn btn-warning" type="submit">Cập nhật dữ liệu mới nhất</button>
            </form>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <p>Booking: <a href="{{ route('admin.bookings.show',$customerRequest->booking) }}"><strong>{{ $customerRequest->booking?->booking_code }}</strong></a></p>
                    <dl class="row">
                        <dt class="col-sm-3">Khách</dt><dd class="col-sm-9">{{ $customerRequest->customer_name }} · {{ $customerRequest->customer_email }}</dd>
                        <dt class="col-sm-3">Nguồn gửi</dt><dd class="col-sm-9">{{ $customerRequest->source==='customer_web' ? 'Website khách hàng' : 'Biểu mẫu email khách vãng lai' }}</dd>
                        <dt class="col-sm-3">Giờ dự kiến đến</dt><dd class="col-sm-9">{{ optional($customerRequest->expected_arrival_at)->format('d/m/Y H:i') }}</dd>
                        <dt class="col-sm-3">Lý do</dt><dd class="col-sm-9" style="white-space:pre-wrap">{{ $customerRequest->reason }}</dd>
                    </dl>

                    <h5>Tệp minh chứng</h5>
                    <div class="d-flex flex-wrap gap-3">
                    @forelse($customerRequest->attachments as $attachment)
                        @php
                            $attachmentUrl = route('admin.customer-requests.attachment', [$customerRequest, $attachment->id]);
                            $isImage = str_starts_with((string) $attachment->mime_type, 'image/');
                        @endphp
                        @if($isImage)
                            <a class="js-image-lightbox text-decoration-none" href="{{ $attachmentUrl }}" target="_blank" title="Bấm để xem ảnh lớn">
                                <div class="border rounded-3 p-2 bg-white" style="width:170px">
                                    <img src="{{ $attachmentUrl }}" alt="{{ $attachment->original_name }}"
                                         style="width:100%;height:115px;object-fit:cover;border-radius:8px;cursor:zoom-in">
                                    <div class="small text-truncate mt-2" title="{{ $attachment->original_name }}">{{ $attachment->original_name }}</div>
                                </div>
                            </a>
                        @else
                            <a class="text-decoration-none" target="_blank" href="{{ $attachmentUrl }}">
                                <div class="border rounded-3 p-3 bg-light" style="width:170px;min-height:150px;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center">
                                    <strong>PDF / Tệp</strong>
                                    <span class="small text-break mt-2">{{ $attachment->original_name }}</span>
                                </div>
                            </a>
                        @endif
                    @empty
                        <p class="text-muted">Không có tệp đính kèm.</p>
                    @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Ghi chú của lễ tân</h5>
                    <form method="POST" action="{{ route('admin.customer-requests.receptionist-note',$customerRequest) }}">
                        @csrf @method('PATCH')
                        <textarea name="receptionist_note" class="form-control mb-2" rows="4" required>{{ $customerRequest->receptionist_note }}</textarea>
                        <button class="btn btn-outline-primary">Lưu ghi chú</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Quản lý duyệt</h5>
                    <p>Trạng thái: <strong>{{ $customerRequest->status_label }}</strong></p>
                    @if($customerRequest->status==='pending' && in_array(auth()->user()?->role, ['super_admin','manager'], true))
                        <form method="POST" action="{{ route('admin.customer-requests.approve',$customerRequest) }}" class="mb-3">
                            @csrf @method('PATCH')
                            <input type="hidden" name="version" value="{{ $pageVersion }}">
                            <textarea name="admin_note" class="form-control mb-2" rows="3" placeholder="Ghi chú duyệt"></textarea>
                            <button class="btn btn-success" @disabled($hasUnseenUpdate)>Duyệt và ghi nhận giờ đến</button>
                        </form>
                        <form method="POST" action="{{ route('admin.customer-requests.reject',$customerRequest) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="version" value="{{ $pageVersion }}">
                            <textarea name="admin_note" class="form-control mb-2" rows="3" required placeholder="Lý do từ chối"></textarea>
                            <button class="btn btn-danger" @disabled($hasUnseenUpdate)>Từ chối</button>
                        </form>
                    @else
                        <p style="white-space:pre-wrap">{{ $customerRequest->admin_note ?: 'Chưa có ghi chú.' }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</main>
</div>
<script src="{{ asset('assets/js/image-lightbox.js') }}?v={{ filemtime(public_path('assets/js/image-lightbox.js')) }}"></script>
<script>
document.body.setAttribute('data-realtime-local-only', 'true');

const LATE_REQUEST_PAGE_VERSION = {{ (int) $pageVersion }};
const lateRequestUpdatesUrl = @json(route('admin.customer-requests.updates', $customerRequest));
const lateRequestBanner = document.getElementById('lateArrivalUpdateBanner');
const acknowledgeForm = document.getElementById('lateArrivalAcknowledgeForm');
const acknowledgeVersionInput = document.getElementById('lateArrivalAcknowledgeVersion');
const acknowledgeButton = document.getElementById('lateArrivalAcknowledgeButton');

function lockLateRequestActions() {
    lateRequestBanner?.classList.remove('d-none');
    document.querySelectorAll('form[action*="/approve"] button, form[action*="/reject"] button').forEach(el => el.disabled = true);
}

async function getLatestRequestVersion() {
    const response = await fetch(lateRequestUpdatesUrl, {
        headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
        cache: 'no-store',
    });
    if (!response.ok) throw new Error('Không tải được phiên bản mới nhất.');
    return response.json();
}

async function pollLateRequestUpdates() {
    try {
        const data = await getLatestRequestVersion();
        if (Number(data.current_version || 0) > LATE_REQUEST_PAGE_VERSION) lockLateRequestActions();
    } catch (e) {}
}

acknowledgeForm?.addEventListener('submit', async function (event) {
    event.preventDefault();
    acknowledgeButton.disabled = true;
    try {
        const data = await getLatestRequestVersion();
        acknowledgeVersionInput.value = Number(data.current_version || LATE_REQUEST_PAGE_VERSION);
        acknowledgeForm.submit();
    } catch (error) {
        acknowledgeButton.disabled = false;
        alert('Không thể tải dữ liệu mới nhất. Vui lòng thử lại.');
    }
});

window.addEventListener('booking:updated', function (event) {
    const detail = event.detail || {};
    if (Number(detail.id || detail.booking_id || 0) !== {{ (int) $customerRequest->booking_id }}) return;
    if (detail.action === 'late_arrival_request_updated') {
        event.stopImmediatePropagation();
        lockLateRequestActions();
    }
});

setInterval(pollLateRequestUpdates, 10000);
</script>
@endsection
