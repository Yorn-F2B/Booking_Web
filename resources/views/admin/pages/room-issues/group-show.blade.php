@extends('layouts.admin')

@section('title', 'Xử lý nhóm sự cố phòng')

@section('content')
@php
    $workflowLabels = [
        'pending' => 'Chờ phương án',
        'proposal_ready' => 'Đã giữ phòng - chờ gửi lễ tân',
        'waiting_guest_confirmation' => 'Chờ lễ tân trao đổi với khách',
        'guest_accepted' => 'Khách đã chọn phương án - chờ quản lý xác nhận',
        'guest_requested_change' => 'Khách yêu cầu phương án khác',
        'approved' => 'Đã xác nhận xử lý',
        'completed' => 'Đã hoàn tất',
        'rejected' => 'Đã từ chối',
    ];
    $resolutionLabels = [
        'same_category' => 'Đổi phòng cùng hạng',
        'upgrade_category' => 'Đổi sang hạng cao hơn',
        'repair_only' => 'Giữ nguyên phòng, sửa gấp',
    ];
    $leaderStatus = $leader->workflow_status ?: 'pending';
    $guestResponseSnapshot = optional($issues->max('guest_responded_at'))->format('Y-m-d H:i:s');
    $oldIssuePromotionCodes = collect(old('issue_promotion_codes', []));
    $selectedPromotionCodesByIssue = $issues->mapWithKeys(function ($issue) use ($oldIssuePromotionCodes) {
        $oldCodes = $oldIssuePromotionCodes->get((string) $issue->id, $oldIssuePromotionCodes->get($issue->id));
        $codes = $oldCodes !== null ? $oldCodes : ($issue->promotion_codes ?? []);

        return [
            (int) $issue->id => collect($codes)
                ->map(fn ($code) => strtoupper(trim((string) $code)))
                ->filter()
                ->unique()
                ->values(),
        ];
    });
    $canRebuildProposal = in_array($leaderStatus, [
        'pending',
        'proposal_ready',
        'waiting_guest_confirmation',
        'guest_requested_change',
        'guest_accepted',
    ], true);
@endphp

<style>
    .issue-card {
        border: 1px solid #dfe6ee;
        border-radius: 14px;
        background: #fff;
        padding: 16px;
    }
    .issue-card + .issue-card { margin-top: 14px; }
    .issue-photo {
        width: 76px;
        height: 76px;
        object-fit: cover;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }
    .proposal-box {
        border: 1px solid #d7e5f5;
        border-radius: 12px;
        background: #f7fbff;
        padding: 13px 14px;
    }
    .choice-box {
        border: 1px solid #cfe8d6;
        border-radius: 12px;
        background: #f4fbf6;
        padding: 13px 14px;
    }
    .status-card { border-left: 4px solid #2563eb; }
    .promo-scroll {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #dfe5ec;
        border-radius: 12px;
    }
    .promo-row {
        display: grid;
        grid-template-columns: 24px 125px 1fr auto;
        gap: 10px;
        align-items: center;
        padding: 10px 12px;
        border-bottom: 1px solid #edf0f4;
        cursor: pointer;
    }
    .promo-row:last-child { border-bottom: 0; }
    .promo-row:hover { background: #f8fafc; }
    .promo-row:has(input:checked) { background: #eef6ff; }
    .priority-flow {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        font-size: 13px;
    }
    .priority-step {
        padding: 5px 9px;
        border: 1px solid #dfe5ec;
        border-radius: 999px;
        background: #fff;
        font-weight: 600;
    }
    .final-action-banner {
        border: 2px solid #22a447;
        border-radius: 14px;
        background: #effcf3;
        padding: 14px 16px;
        box-shadow: 0 8px 22px rgba(22, 163, 74, .12);
    }
    .finalize-panel {
        position: sticky;
        top: 86px;
        border: 2px solid #22a447 !important;
        box-shadow: 0 12px 30px rgba(22, 163, 74, .16);
    }
    .finalize-button {
        min-height: 54px;
        font-size: 1.02rem;
        font-weight: 800;
    }
</style>

<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('admin.dashboard') }}">Admin</a>
            /
            <a href="{{ route('admin.room-issues.index') }}">Sự cố phòng</a>
            /
            {{ $booking->booking_code }}
        </p>

        <div class="admin-page-head">
            <div>
                <h2>Phiếu sự cố booking {{ $booking->booking_code }}</h2>
                <p>{{ $issues->count() }} phòng trong cùng một lần khách báo; mỗi phòng lỗi có danh sách mã bù đắp riêng, không ảnh hưởng phòng bình thường.</p>
            </div>
            <span class="badge text-bg-primary fs-6">
                {{ $workflowLabels[$leaderStatus] ?? $leaderStatus }}
            </span>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div id="roomIssueLiveUpdate" class="alert alert-danger d-none position-sticky" style="top:8px;z-index:1030;">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div><strong>Có cập nhật mới từ lễ tân hoặc khách.</strong> Tải lại trước khi xác nhận để tránh dùng phương án cũ.</div>
                <button type="button" class="btn btn-danger btn-sm" onclick="window.location.reload()">Tải lại cập nhật</button>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($leaderStatus === 'guest_accepted')
            <div class="final-action-banner mb-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <div class="fw-bold text-success fs-5">Lễ tân đã ghi nhận lựa chọn của khách</div>
                    <div class="small text-muted">Kiểm tra các phòng, mã bù đắp rồi xác nhận thực hiện toàn bộ ở khung bên phải.</div>
                </div>
                <a href="#finalize-room-issue" class="btn btn-success btn-lg px-4">
                    <i class="bx bx-check-shield me-1"></i>
                    Đi tới xác nhận cuối
                </a>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="settings-section mb-3 status-card">
                    <div class="priority-flow">
                        <span class="priority-step">1. Phòng cùng hạng</span>
                        <i class="bx bx-right-arrow-alt"></i>
                        <span class="priority-step">2. Hạng cao hơn gần nhất</span>
                        <i class="bx bx-right-arrow-alt"></i>
                        <span class="priority-step">3. Giữ nguyên, sửa gấp</span>
                    </div>
                    <div class="small text-muted mt-2">
                    </div>

                    @if ($leader->guest_response_note)
                        <div class="alert {{ $leader->guest_response === 'accepted' ? 'alert-success' : 'alert-warning' }} mt-3 mb-0">
                            <strong>Ghi chú từ lễ tân/khách:</strong>
                            {{ $leader->guest_response_note }}
                        </div>
                    @endif
                </div>

                @foreach ($issues as $issue)
                    @php
                        $preview = $issue->proposed_resolution_type
                            ? [
                                'type' => $issue->proposed_resolution_type,
                                'room' => $issue->proposedRoom,
                                'label' => $resolutionLabels[$issue->proposed_resolution_type] ?? 'Chưa có phương án',
                                'description' => null,
                            ]
                            : $automaticProposals->get($issue->id);
                        $guestChoice = $issue->guest_selected_resolution_type;
                        $issueSelectedPromotionCodes = $selectedPromotionCodesByIssue->get((int) $issue->id, collect());
                        $issueBookingRoom = $booking->bookingRooms->firstWhere('room_id', $issue->current_room_id);
                        $issueOldNightPrice = (float) ($issueBookingRoom?->price_at_booking ?? $issue->currentRoom?->category?->price ?? 0);
                        $issueNewNightPrice = (float) (($preview['room'] ?? null)?->category?->price ?? $issueOldNightPrice);
                        $issueRemainingNights = max(1, now('Asia/Ho_Chi_Minh')->startOfDay()->diffInDays($booking->check_out_at->copy()->timezone('Asia/Ho_Chi_Minh')->startOfDay()));
                        $issuePriceDifferencePerNight = $issueNewNightPrice - $issueOldNightPrice;
                        $issuePriceDifferenceTotal = $issuePriceDifferencePerNight * $issueRemainingNights;
                    @endphp

                    <div class="issue-card">
                        <div class="d-flex justify-content-between gap-3 flex-wrap mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">
                                    Phòng {{ $issue->currentRoom?->room_number ?? '---' }}
                                    · {{ $issue->currentRoom?->category?->name ?? '---' }}
                                </h5>
                                <div class="text-muted">{{ $issue->issue_description }}</div>
                            </div>
                            <span class="badge text-bg-light border align-self-start">
                                Yêu cầu #{{ $issue->id }}
                            </span>
                        </div>

                        @if ($issue->attachments->isNotEmpty())
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                @foreach ($issue->attachments as $attachment)
                                    <a href="{{ route('admin.room-issue-attachments.show', $attachment) }}" target="_blank">
                                        <img
                                            src="{{ route('admin.room-issue-attachments.show', $attachment) }}"
                                            class="issue-photo"
                                            alt="Ảnh sự cố phòng"
                                        >
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if ($preview)
                            <div class="proposal-box">
                                <div class="d-flex justify-content-between gap-2 flex-wrap">
                                    <div>
                                        <div class="fw-bold text-primary">
                                            {{ $preview['label'] ?? ($resolutionLabels[$preview['type']] ?? '---') }}
                                        </div>
                                    </div>

                                    @if ($preview['room'] ?? null)
                                        <span class="badge text-bg-info align-self-start">
                                            Phòng {{ $preview['room']->room_number }}
                                        </span>
                                    @endif
                                </div>

                                @if ($preview['room'] ?? null)
                                    <div class="mt-2">
                                        Chuyển sang <strong>phòng {{ $preview['room']->room_number }}</strong>
                                        · {{ $preview['room']->category?->name ?? '---' }}.
                                    </div>
                                @else
                                    <div class="mt-2">
                                        Khách tiếp tục ở phòng hiện tại; buồng phòng nhận việc sửa gấp riêng.
                                    </div>
                                @endif

                                @if (!empty($preview['description']))
                                    <div class="small text-muted mt-1">{{ $preview['description'] }}</div>
                                @endif

                                @if (($preview['room'] ?? null) && abs($issuePriceDifferenceTotal) > 0.01)
                                    <div class="border rounded-3 bg-white p-3 mt-3">
                                        <div class="fw-semibold mb-1">Tiền phòng của riêng phòng này</div>
                                        <div class="small">
                                            Giá đang tính: <strong>{{ number_format($issueOldNightPrice, 0, ',', '.') }}đ/đêm</strong><br>
                                            Giá phòng thay thế: <strong>{{ number_format($issueNewNightPrice, 0, ',', '.') }}đ/đêm</strong><br>
                                            @if ($issuePriceDifferenceTotal > 0)
                                                Phần tăng: <strong class="text-danger">{{ number_format($issuePriceDifferencePerNight, 0, ',', '.') }}đ × {{ $issueRemainingNights }} đêm = {{ number_format($issuePriceDifferenceTotal, 0, ',', '.') }}đ</strong>
                                                <div class="text-muted mt-1">Nếu không chọn mã hỗ trợ đủ giá trị, phần còn lại sẽ được cộng vào tiền khách phải trả.</div>
                                            @else
                                                Phần giảm: <strong class="text-success">{{ number_format(abs($issuePriceDifferenceTotal), 0, ',', '.') }}đ</strong>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if ($issue->proposed_room_id && $issue->proposal_expires_at)
                                    <div class="small text-danger mt-2">
                                        Giữ phòng đến
                                        <strong>{{ $issue->proposal_expires_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</strong>.
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if ($guestChoice)
                            <div class="choice-box mt-3">
                                <div class="small text-muted">Khách đã chọn</div>
                                <div class="fw-bold text-success">
                                    {{ $resolutionLabels[$guestChoice] ?? $guestChoice }}
                                    @if ($guestChoice !== 'repair_only' && $issue->proposedRoom)
                                        · phòng {{ $issue->proposedRoom->room_number }}
                                    @endif
                                </div>
                            </div>
                        @endif


                        @if ($issueSelectedPromotionCodes->isNotEmpty())
                            <div class="alert alert-info py-2 mt-3 mb-0 small">
                                <strong>Mã bù đắp riêng phòng này:</strong>
                                {{ $issueSelectedPromotionCodes->implode(', ') }}
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($canRebuildProposal)
                    <form
                        id="roomIssueProposalForm"
                        method="POST"
                        action="{{ route('admin.room-issues.proposal', $leader) }}"
                        class="settings-section mt-3"
                    >
                        @csrf
                        @method('PATCH')

                        @if ($leaderStatus === 'proposal_ready')
                            <h5 class="fw-bold mb-2">Phương án đã được giữ ngay khi khách báo</h5>
                            <p class="small text-muted mb-3">
                            </p>
                        @elseif ($leaderStatus === 'pending')
                            <p class="small text-muted mb-3">
                            </p>
                        @else
                            <h5 class="fw-bold mb-2">Gửi lại đầy đủ lựa chọn cho lễ tân</h5>
                            <p class="small text-muted mb-3">
                            </p>
                        @endif

                        @if ($selectedPromotionCodesByIssue->flatten()->isNotEmpty())
                            <div class="alert alert-info py-2">
                                <strong>Mã bù đắp đang được giữ theo từng phòng:</strong>
                                <div class="small mt-1">
                                    @foreach ($issues as $issue)
                                        @php($codes = $selectedPromotionCodesByIssue->get((int) $issue->id, collect()))
                                        <div>
                                            Phòng {{ $issue->currentRoom?->room_number ?? '---' }}:
                                            {{ $codes->isEmpty() ? 'không chọn mã' : $codes->implode(', ') }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <button class="btn btn-primary w-100 mt-2">
                            <i class="bx bx-send me-1"></i>
                            @if ($leaderStatus === 'proposal_ready')
                                Gửi phương án đã giữ sang lễ tân
                            @elseif ($leaderStatus === 'pending')
                                Tạo phương án và gửi lễ tân
                            @else
                                Gửi lại đầy đủ phương án và làm mới giữ phòng 30 phút
                            @endif
                        </button>
                    </form>
                @endif
            </div>

            <div class="col-xl-4">
                <div class="settings-section mb-3">
                    <h5 class="fw-bold mb-3">Booking</h5>
                    <div class="d-grid gap-2 small">
                        <div>
                            <span class="text-muted d-block">Khách</span>
                            <strong>{{ $booking->booked_customer_name }}</strong>
                        </div>
                        <div>
                            <span class="text-muted d-block">Thời gian lưu trú</span>
                            <strong>
                                {{ $booking->check_in_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                                →
                                {{ $booking->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                            </strong>
                        </div>
                        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-outline-secondary">
                            Mở chi tiết booking
                        </a>
                    </div>
                </div>

                @if ($leaderStatus === 'guest_accepted')
                    <form
                        id="finalize-room-issue"
                        method="POST"
                        action="{{ route('admin.room-issues.finalize', $leader) }}"
                        class="settings-section finalize-panel"
                    >
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="issue_promotion_codes_present" value="1">
                        <input type="hidden" name="guest_response_snapshot" value="{{ $guestResponseSnapshot }}">

                        <div class="alert alert-success py-2 fw-semibold">
                        </div>
                        <h5 class="fw-bold mb-2">Xác nhận cuối</h5>
                        <p class="small text-muted">
                            Lễ tân đã ghi nhận lựa chọn của khách cho từng phòng. Quản lý kiểm tra lại rồi thực hiện đồng thời.
                        </p>

                        <p class="small text-muted mb-2">
                            Mỗi phòng lỗi có mã riêng. Giá phòng mới được tính thật; mã hỗ trợ chỉ bù cho đúng phòng này. Phần chênh chưa được mã bù sẽ do khách thanh toán.
                        </p>

                        <div class="d-grid gap-3 mb-3">
                            @foreach ($issues as $issue)
                                @php($issueCodes = $selectedPromotionCodesByIssue->get((int) $issue->id, collect()))
                                <div class="border rounded-3 p-3 bg-white">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap mb-2">
                                        <div>
                                            <strong>Phòng {{ $issue->currentRoom?->room_number ?? '---' }}</strong>
                                            <div class="small text-muted">
                                                {{ $resolutionLabels[$issue->guest_selected_resolution_type] ?? 'Chưa chọn' }}
                                                @if ($issue->guest_selected_resolution_type !== 'repair_only' && $issue->proposedRoom)
                                                    → phòng {{ $issue->proposedRoom->room_number }}
                                                @endif
                                            </div>
                                        </div>
                                        <span class="badge text-bg-primary align-self-start">Mã chỉ cho phòng này</span>
                                    </div>

                                    @if ($issueCodes->isNotEmpty())
                                        <div class="alert alert-primary py-2 mb-2">
                                            <strong>Mã đã gắn cho lần gửi này:</strong>
                                            {{ $issueCodes->implode(', ') }}
                                            <div class="small mt-1">Mã đã khóa, không thể chọn lại ở lần gửi sau.</div>
                                            @foreach ($issueCodes as $lockedCode)
                                                <input type="hidden" name="issue_promotion_codes[{{ $issue->id }}][]" value="{{ $lockedCode }}">
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($promotions->isNotEmpty())
                                        <div class="promo-scroll">
                                            @foreach ($promotions as $promotion)
                                                <label class="promo-row">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input room-issue-promotion-checkbox"
                                                        name="issue_promotion_codes[{{ $issue->id }}][]"
                                                        value="{{ $promotion->code }}"
                                                    >
                                                    <strong>{{ $promotion->code }}</strong>
                                                    <span>{{ $promotion->name }}</span>
                                                    <span class="text-primary fw-semibold">
                                                        @if ($promotion->discount_type === 'percent')
                                                            {{ rtrim(rtrim(number_format((float) $promotion->discount_value, 2, ',', '.'), '0'), ',') }}%
                                                        @else
                                                            {{ number_format((float) $promotion->discount_value, 0, ',', '.') }}đ
                                                        @endif
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-light border mb-0">Phòng này hiện không có mã đủ điều kiện.</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <label class="form-label fw-semibold">Ghi chú xác nhận cuối</label>
                        <textarea name="admin_note" class="form-control" rows="4" required>{{ old('admin_note', $leader->admin_note) }}</textarea>

                        <button class="btn btn-success btn-lg finalize-button w-100 mt-3">
                            <i class="bx bx-check-shield me-1"></i>
                            Xác nhận và thực hiện toàn bộ phương án
                        </button>
                    </form>
                @elseif ($leaderStatus === 'waiting_guest_confirmation')
                    <div class="settings-section">
                        <div class="alert alert-info mb-2">
                            Đang chờ lễ tân cho khách chọn phương án của từng phòng.
                        </div>
                        @if ($selectedPromotionCodesByIssue->flatten()->isNotEmpty())
                            <div class="small mb-2">
                                <strong>Mã đã lưu theo phòng:</strong>
                                @foreach ($issues as $issue)
                                    @php($codes = $selectedPromotionCodesByIssue->get((int) $issue->id, collect()))
                                    <div>Phòng {{ $issue->currentRoom?->room_number ?? '---' }}: {{ $codes->isEmpty() ? 'không có' : $codes->implode(', ') }}</div>
                                @endforeach
                            </div>
                        @endif
                        <a href="{{ route('admin.bookings.room-issue-proposal', $booking) }}" class="btn btn-outline-primary w-100">
                            Xem màn lễ tân
                        </a>
                    </div>
                @elseif ($leaderStatus === 'guest_requested_change')
                    <div class="settings-section">
                        <div class="alert alert-warning mb-0">
                            Khách muốn trao đổi lại. Bấm gửi lại để lễ tân vẫn thấy đầy đủ phương án đổi phòng/nâng hạng và giữ nguyên sửa gấp; phòng đang giữ sẽ được làm mới thêm 30 phút.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>
</div>
<script>
window.addEventListener('booking:updated', function (event) {
    const detail = event.detail || {};
    if (Number(detail.id || detail.booking_id || 0) !== {{ (int) $booking->id }}) return;
    if (!['room_issue_guest_updated', 'room_issue_proposal_sent'].includes(detail.action)) return;
    const banner = document.getElementById('roomIssueLiveUpdate');
    if (banner) banner.classList.remove('d-none');
    document.querySelectorAll('#finalize-room-issue input, #finalize-room-issue textarea, #finalize-room-issue button')
        .forEach(function (element) { element.disabled = true; });
});

document.addEventListener('DOMContentLoaded', function () {
    const proposalForm = document.getElementById('roomIssueProposalForm');
    if (!proposalForm) {
        return;
    }

    proposalForm.addEventListener('submit', function () {
        proposalForm.querySelectorAll('[data-draft-promotion-input]').forEach(function (input) {
            input.remove();
        });

        const finalizeForm = document.getElementById('finalize-room-issue');
        if (!finalizeForm) {
            return;
        }

        const promotionCheckboxes = finalizeForm.querySelectorAll('.room-issue-promotion-checkbox');
        const marker = document.createElement('input');
        marker.type = 'hidden';
        marker.name = 'issue_promotion_codes_present';
        marker.value = '1';
        marker.dataset.draftPromotionInput = '1';
        proposalForm.appendChild(marker);

        promotionCheckboxes.forEach(function (checkbox) {
            if (!checkbox.checked) {
                return;
            }

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = checkbox.name;
            hidden.value = checkbox.value;
            hidden.dataset.draftPromotionInput = '1';
            proposalForm.appendChild(hidden);
        });

        const adminNote = finalizeForm.querySelector('textarea[name="admin_note"]');
        if (adminNote) {
            const noteDraft = document.createElement('input');
            noteDraft.type = 'hidden';
            noteDraft.name = 'admin_note_draft';
            noteDraft.value = adminNote.value;
            noteDraft.dataset.draftPromotionInput = '1';
            proposalForm.appendChild(noteDraft);
        }
    });
});
</script>
@endsection
