@extends('layouts.admin')
@section('title', 'Chi tiết sự cố phòng')
@section('content')
@php
    $issue=$roomIssueRequest;
    $typeLabels=['normal_discount'=>'Mã thường','event_discount'=>'Mã sự kiện','conditional_discount'=>'Mã điều kiện','support_discount'=>'Mã hỗ trợ'];
    $promotionTypeConfig = [
        'normal_discount' => [
            'label' => 'Mã thường',
            'badge' => 'text-bg-secondary',
            'icon' => 'bx-purchase-tag',
            'hint' => 'Ưu đãi thông thường đang hoạt động.',
        ],
        'event_discount' => [
            'label' => 'Mã sự kiện',
            'badge' => 'text-bg-danger',
            'icon' => 'bx-calendar-star',
            'hint' => 'Ưu đãi theo chương trình hoặc sự kiện.',
        ],
        'conditional_discount' => [
            'label' => 'Mã điều kiện',
            'badge' => 'text-bg-primary',
            'icon' => 'bx-check-shield',
            'hint' => 'Chỉ áp dụng khi booking đáp ứng đủ điều kiện.',
        ],
        'support_discount' => [
            'label' => 'Mã hỗ trợ khách',
            'badge' => 'text-bg-warning',
            'icon' => 'bx-gift',
            'hint' => 'Mã bù đắp hoặc chăm sóc khách do sự cố.',
        ],
    ];
    $promotionGroups = $promotions->groupBy('promotion_type');
    $statusLabels=['pending'=>'Chờ quản lý duyệt','approved'=>'Đã đổi phòng/hạng','repair_only'=>'Không còn phòng - sửa gấp','rejected'=>'Đã từ chối'];
@endphp
<style>
    .issue-promotion-picker {
        border: 1px solid #dbe3ed;
        border-radius: 12px;
        background: #fff;
        overflow: hidden;
    }

    .issue-promotion-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        border-bottom: 1px solid #e5eaf0;
        background: #f8fafc;
    }

    .issue-promotion-count {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
    }

    .issue-promotion-count span {
        display: inline-flex;
        min-width: 24px;
        height: 24px;
        padding: 0 7px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #2563eb;
        color: #fff;
    }

    .issue-promotion-scroll {
        max-height: 330px;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .issue-promotion-group + .issue-promotion-group {
        border-top: 1px solid #e9edf2;
    }

    .issue-promotion-group-title {
        position: sticky;
        top: 0;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 8px 14px;
        background: #f8fafc;
        border-bottom: 1px solid #edf1f5;
        color: #475569;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .035em;
    }

    .issue-promotion-row {
        display: grid;
        grid-template-columns: 24px minmax(150px, .8fr) minmax(220px, 1.4fr) auto;
        align-items: center;
        gap: 10px;
        min-height: 54px;
        margin: 0;
        padding: 8px 14px;
        border-bottom: 1px solid #eef2f6;
        background: #fff;
        cursor: pointer;
        transition: background .15s ease;
    }

    .issue-promotion-row:last-child { border-bottom: 0; }
    .issue-promotion-row:hover { background: #f8fbff; }
    .issue-promotion-row.is-selected { background: #eef6ff; }

    .issue-promotion-row .form-check-input {
        width: 18px;
        height: 18px;
        margin: 0;
        cursor: pointer;
    }

    .issue-promotion-code {
        color: #172033;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .issue-promotion-name {
        color: #475569;
        font-size: 13px;
        line-height: 1.35;
    }

    .issue-promotion-value {
        color: #047857;
        font-size: 12px;
        font-weight: 700;
        text-align: right;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .issue-promotion-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }
        .issue-promotion-row {
            grid-template-columns: 24px 1fr;
        }
        .issue-promotion-name,
        .issue-promotion-value {
            grid-column: 2;
            text-align: left;
            white-space: normal;
        }
    }
</style>
<div class="admin-wrapper"><main class="admin-content">
    <p class="admin-breadcrumb mb-3"><a href="{{ route('admin.dashboard') }}">Admin</a> / <a href="{{ route('admin.room-issues.index') }}">Sự cố phòng</a> / Chi tiết</p>
    <div class="admin-page-head d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div><h2>Sự cố phòng {{ $issue->currentRoom?->room_number }}</h2><p>Booking {{ $issue->booking?->booking_code }} · gửi {{ $issue->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</p></div>
        <a href="{{ route('admin.room-issues.index') }}" class="btn btn-outline-secondary">Quay lại</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="settings-section mb-3">
                <div class="d-flex justify-content-between gap-3 mb-3"><div><h5 class="fw-bold mb-1">Thông tin khách báo</h5><div class="text-muted small">Khách: {{ $issue->booking?->booked_customer_name }}</div></div><span class="badge text-bg-warning">{{ $statusLabels[$issue->status] ?? $issue->status }}</span></div>
                <div class="border rounded p-3 bg-light mb-3">{{ $issue->issue_description }}</div>
                <div class="d-flex flex-wrap gap-2">
                    @forelse($issue->attachments as $attachment)
                        <a class="js-image-lightbox" href="{{ route('admin.room-issue-attachments.show',$attachment) }}" target="_blank">
                            <img src="{{ route('admin.room-issue-attachments.show',$attachment) }}" alt="Ảnh sự cố" style="width:150px;height:110px;object-fit:cover;border-radius:10px;border:1px solid #dbe2ea">
                        </a>
                    @empty
                        <span class="text-muted">Khách không gửi ảnh.</span>
                    @endforelse
                </div>
            </div>

            <div class="settings-section">
                <h5 class="fw-bold mb-3">Phương án xử lý</h5>
                <div class="alert {{ $proposal['type']==='same_category'?'alert-success':($proposal['type']==='upgrade_category'?'alert-primary':'alert-warning') }}">
                    <div class="fw-bold fs-5">{{ $proposal['label'] }}</div>
                    <div>{{ $proposal['description'] ?? '' }}</div>
                    @if($proposal['room'])
                        <div class="mt-2"><strong>Phòng tự chọn:</strong> {{ $proposal['room']->room_number }} · {{ $proposal['room']->category?->name }}</div>
                    @endif
                </div>

                @if($issue->status==='pending')
                    <form method="POST" action="{{ route('admin.room-issues.approve',$issue) }}" onsubmit="return confirm('Xác nhận duyệt phương án xử lý?')">
                        @csrf @method('PATCH')
                        <label class="form-label fw-semibold mb-2">Mã bù đắp cho khách <span class="text-muted fw-normal">(không bắt buộc)</span></label>

                        @if($promotions->isNotEmpty())
                            <div class="issue-promotion-picker" data-issue-promotion-picker>
                                <div class="issue-promotion-toolbar">
                                    <div>
                                        <div class="fw-bold text-dark">Chọn mã bù đắp</div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <div class="issue-promotion-count">Đã chọn <span data-selected-promotion-count>0</span></div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-select-all-promotions>Chọn tất cả</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-clear-promotions>Bỏ chọn</button>
                                    </div>
                                </div>

                                <div class="issue-promotion-scroll">
                                    @foreach($promotionTypeConfig as $promotionType => $config)
                                        @php
                                            $groupPromotions = $promotionGroups->get($promotionType, collect());
                                        @endphp
                                        @if($groupPromotions->isNotEmpty())
                                            <section class="issue-promotion-group">
                                                <div class="issue-promotion-group-title">
                                                    <i class="bx {{ $config['icon'] }}"></i>
                                                    <span>{{ $config['label'] }}</span>
                                                    <span class="badge {{ $config['badge'] }}">{{ $groupPromotions->count() }}</span>
                                                </div>

                                                @foreach($groupPromotions as $promotion)
                                                    @php
                                                        $selectedCodes = old('promotion_codes', []);
                                                        $discountText = $promotion->discount_type === 'percent'
                                                            ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2, ',', '.'), '0'), ',') . '%'
                                                            : number_format((float) $promotion->discount_value, 0, ',', '.') . 'đ';
                                                        if ($promotion->discount_type === 'percent' && (float) $promotion->max_discount_amount > 0) {
                                                            $discountText .= ' · tối đa ' . number_format((float) $promotion->max_discount_amount, 0, ',', '.') . 'đ';
                                                        }
                                                        $benefits = collect();
                                                        if ((float) $promotion->discount_value > 0) $benefits->push('Giảm ' . $discountText);
                                                        if ($promotion->serviceOffers->isNotEmpty()) $benefits->push($promotion->serviceOffers->map(fn($offer) => $offer->offer_label)->implode(' · '));
                                                        if ($promotion->roomUpgradeOffers->isNotEmpty()) $benefits->push($promotion->roomUpgradeOffers->map(fn($offer) => $offer->cover_label)->implode(' · '));
                                                    @endphp

                                                    <label class="issue-promotion-row" data-promotion-row>
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            name="promotion_codes[]"
                                                            value="{{ $promotion->code }}"
                                                            @checked(in_array($promotion->code, $selectedCodes, true))
                                                            data-promotion-checkbox
                                                        >
                                                        <span class="issue-promotion-code">{{ $promotion->code }}</span>
                                                        <span class="issue-promotion-name">{{ $promotion->name }}</span>
                                                        <span class="issue-promotion-value">{{ $benefits->filter()->implode(' · ') ?: 'Quyền lợi hỗ trợ' }}</span>
                                                    </label>
                                                @endforeach
                                            </section>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="alert alert-light border mb-0">Booking này hiện không có mã nào đủ điều kiện sử dụng.</div>
                        @endif

                        <label class="form-label fw-semibold mt-3">Ghi chú xử lý và nội dung báo khách</label>
                        <textarea name="admin_note" class="form-control" rows="4" maxlength="2000" required placeholder="Ví dụ: xác nhận lỗi điều hòa; đã đổi phòng miễn phí và tặng mã hỗ trợ..."></textarea>
                        <button class="btn btn-primary w-100 mt-3"><i class="bx bx-check-shield me-1"></i>Xác nhận phê duyệt</button>
                    </form>
                @else
                    <div class="row g-2">
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="text-muted small">Kết quả</span><div class="fw-bold">{{ ['same_category'=>'Đổi phòng cùng hạng','upgrade_category'=>'Đổi hạng miễn phí','no_room'=>'Giữ phòng - sửa gấp'][$issue->resolution_type] ?? '---' }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="text-muted small">Phòng mới</span><div class="fw-bold">{{ $issue->approvedRoom?->room_number ?? 'Không có' }}</div></div></div>
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="text-muted small">Mã bù đắp</span><div class="fw-bold">{{ collect($issue->promotion_codes)->implode(', ') ?: 'Không áp dụng' }}</div></div></div>
                    </div>
                    <div class="border rounded p-3 mt-3 bg-light">{{ $issue->admin_note }}</div>
                @endif
            </div>
        </div>
        <div class="col-xl-4">
            <div class="settings-section mb-3">
                <h5 class="fw-bold mb-3">Booking / phòng</h5>
                <div class="d-grid gap-2 small">
                    <div><span class="text-muted d-block">Booking</span><a href="{{ route('admin.bookings.show',$issue->booking) }}" class="fw-bold">{{ $issue->booking?->booking_code }}</a></div>
                    <div><span class="text-muted d-block">Phòng cũ</span><strong>{{ $issue->currentRoom?->room_number }} · {{ $issue->currentRoom?->category?->name }}</strong></div>
                    <div><span class="text-muted d-block">Thời gian còn lại</span><strong>Đến {{ $issue->booking?->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</strong></div>
                </div>
            </div>
            @if($issue->repair_status)
            <div class="settings-section">
                <h5 class="fw-bold mb-3">Khắc phục phòng cũ</h5>
                <span class="badge {{ $issue->repair_status==='completed'?'text-bg-success':'text-bg-warning' }}">{{ $issue->repair_status==='completed'?'Đã sửa xong':'Đang khắc phục' }}</span>
                @if($issue->repair_note)<div class="mt-3 small">{{ $issue->repair_note }}</div>@endif
            </div>
            @endif
        </div>
    </div>
</main></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const picker = document.querySelector('[data-issue-promotion-picker]');
        if (!picker) return;

        const checkboxes = Array.from(picker.querySelectorAll('[data-promotion-checkbox]'));
        const countNode = picker.querySelector('[data-selected-promotion-count]');
        const selectAllButton = picker.querySelector('[data-select-all-promotions]');
        const clearButton = picker.querySelector('[data-clear-promotions]');

        function syncPromotionRows() {
            const selectedCount = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            }).length;

            checkboxes.forEach(function (checkbox) {
                const row = checkbox.closest('[data-promotion-row]');
                if (row) row.classList.toggle('is-selected', checkbox.checked);
            });

            if (countNode) countNode.textContent = selectedCount;
            if (clearButton) clearButton.disabled = selectedCount === 0;
            if (selectAllButton) selectAllButton.disabled = selectedCount === checkboxes.length;
        }

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', syncPromotionRows);
        });

        if (selectAllButton) {
            selectAllButton.addEventListener('click', function () {
                checkboxes.forEach(function (checkbox) { checkbox.checked = true; });
                syncPromotionRows();
            });
        }

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                checkboxes.forEach(function (checkbox) { checkbox.checked = false; });
                syncPromotionRows();
            });
        }

        syncPromotionRows();
    });
</script>
<script src="{{ asset('assets/js/image-lightbox.js') }}?v={{ filemtime(public_path('assets/js/image-lightbox.js')) }}"></script>
@endsection
