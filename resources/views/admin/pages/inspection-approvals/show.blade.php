@extends('layouts.admin')

@section('title', 'Xác nhận kiểm tra phòng')

@section('content')
@php
    $stageLabels = [
        'housekeeping_report' => 'Chờ buồng phòng kiểm tra',
        'guest_consultation' => 'Chờ lễ tân trao đổi với khách',
        'housekeeping_recheck' => 'Chờ buồng phòng kiểm tra lại',
        'admin_approval' => 'Khách đã đồng ý · chờ admin xác nhận',
        'completed' => 'Đã hoàn tất',
    ];
    $stageClasses = [
        'housekeeping_report' => 'bg-secondary',
        'guest_consultation' => 'bg-info text-dark',
        'housekeeping_recheck' => 'bg-warning text-dark',
        'admin_approval' => 'bg-primary',
        'completed' => 'bg-success',
    ];
    $eventLabels = [
        'inspection_reported' => 'Buồng phòng gửi kết quả kiểm tra ban đầu',
        'guest_consultation' => 'Lễ tân ghi nhận ý kiến của khách',
        'housekeeping_recheck' => 'Buồng phòng cập nhật sau khi kiểm tra lại',
        'admin_approval' => 'Admin xác nhận các khoản cuối cùng',
    ];

    $customerName = trim(($roomInspection->booking->customer->last_name ?? '') . ' ' . ($roomInspection->booking->customer->first_name ?? ''));
    $pageVersion = (int) $roomInspection->version; // Chỉ dùng nội bộ để khóa duyệt dữ liệu cũ.
    $hasUnseenUpdate = (int) $roomInspection->admin_acknowledged_version < $pageVersion;
    $allComparedValuesMatch = $roomInspection->items->every(fn ($item) => $item->guest_claimed_quantity !== null && (int) $item->guest_claimed_quantity === (int) $item->quantity);
    $canApprove = $roomInspection->status === 'reported'
        && $roomInspection->workflow_stage === 'admin_approval'
        && !$hasUnseenUpdate
        && $allComparedValuesMatch;
    $revisionGroups = $roomInspection->revisions->groupBy('version')->sortKeysDesc();
    $currentTotal = (float) $roomInspection->items->sum('total');
    $acceptedCount = $roomInspection->items->where('guest_response', 'accepted')->count();
    $zeroChargeCount = $roomInspection->items->filter(fn ($item) => (float) $item->total <= 0)->count();
@endphp

<style>
    .inspection-update-bar{position:sticky;top:8px;z-index:1030;border:1px solid #f59e0b;box-shadow:0 4px 14px rgba(0,0,0,.08);padding:10px 14px}
    .inspection-update-bar .small{display:none}
    .inspection-change-card{border:1px solid #dbeafe;background:#fff}
    .inspection-before{background:#fff7f7}.inspection-after{background:#f3fff7}
    .inspection-stat{background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:10px;height:100%}
    .inspection-stat strong{font-size:1.05rem}
    .inspection-test-layout > .col-12:first-child .settings-section{display:grid;grid-template-columns:repeat(5,minmax(150px,1fr));gap:10px;padding:14px}
    .inspection-test-layout > .col-12:first-child .settings-section > h5{grid-column:1/-1;margin-bottom:0!important}
    .inspection-test-layout > .col-12:first-child .settings-section > div{margin-bottom:0!important;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;background:#fafafa}
    #inspectionChanges{max-height:360px;overflow:auto}
    #inspectionChanges .compact-panel:not([open]){margin-bottom:8px!important}
    .approval-table{min-width:1050px}
    @media(max-width:1199.98px){.inspection-test-layout > .col-12:first-child .settings-section{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="{{ route('admin.dashboard') }}">Admin</a> /
            <a href="{{ route('admin.inspection-approvals.index') }}">Duyệt kiểm tra phòng</a> /
            Phòng {{ $roomInspection->room->room_number ?? '---' }}
        </p>

        <div id="liveUpdateBanner" class="alert alert-danger inspection-update-bar d-none">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <strong>Kết quả kiểm tra vừa được cập nhật.</strong>
                    Lễ tân hoặc buồng phòng đã thay đổi dữ liệu sau khi bạn mở trang. Hãy tải lại để xem nội dung mới trước khi xác nhận.
                </div>
                <button type="button" class="btn btn-danger btn-sm" onclick="window.location.reload()">Tải lại và xem cập nhật</button>
            </div>
        </div>

        @if ($hasUnseenUpdate && $roomInspection->status === 'reported')
            <div class="alert alert-warning inspection-update-bar">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div><strong>Có kết quả mới từ lễ tân/buồng phòng.</strong></div>
                    <a href="#inspectionChanges" class="btn btn-warning btn-sm">Xem thay đổi</a>
                </div>
            </div>
        @endif

        <div class="admin-page-head">
            <div>
                <h2>Xác nhận phí kiểm tra phòng {{ $roomInspection->room->room_number ?? '---' }}</h2>
                <p>Đối chiếu kết quả hiện tại với ý kiến khách. Chỉ những khoản được tích chọn mới được cộng vào hóa đơn.</p>
            </div>
            <a href="{{ route('admin.inspection-approvals.index') }}" class="btn btn-outline-secondary">Quay lại</a>
        </div>

        @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
        @if ($errors->any())
            <div class="alert alert-danger"><strong>Chưa thể xác nhận:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="row g-3 inspection-test-layout">
            <div class="col-12">
                <div class="settings-section h-100">
                    <h5 class="fw-bold mb-3">Thông tin kiểm tra</h5>
                    <div class="mb-3">
                        <div class="small text-muted">Tình trạng hiện tại</div>
                        <span class="badge {{ $stageClasses[$roomInspection->workflow_stage] ?? 'bg-secondary' }}">{{ $stageLabels[$roomInspection->workflow_stage] ?? $roomInspection->workflow_stage }}</span>
                    </div>
                    <div class="mb-3"><div class="small text-muted">Booking</div><strong>{{ $roomInspection->booking->booking_code ?? '---' }}</strong></div>
                    <div class="mb-3"><div class="small text-muted">Khách</div><strong>{{ $customerName ?: 'Chưa có tên' }}</strong><div class="small text-muted">{{ $roomInspection->booking->customer->phone ?? '---' }}</div></div>
                    <div class="mb-3"><div class="small text-muted">Phòng</div><strong>{{ $roomInspection->room->room_number ?? '---' }}</strong> · Tầng {{ $roomInspection->room->floor_number ?? '---' }}</div>
                    <div class="mb-3"><div class="small text-muted">Buồng phòng báo cáo</div>{{ $roomInspection->inspector->name ?? '---' }}<div class="small text-muted">{{ $roomInspection->inspected_at?->format('d/m/Y H:i:s') }}</div></div>
                    @if ($roomInspection->guestConsultant)
                        <div class="mb-3"><div class="small text-muted">Lễ tân trao đổi gần nhất</div>{{ $roomInspection->guestConsultant->name }}<div class="small text-muted">{{ $roomInspection->guest_consulted_at?->format('d/m/Y H:i:s') }}</div></div>
                    @endif
                    <div class="mb-0"><div class="small text-muted">Cập nhật gần nhất</div>{{ $roomInspection->last_revision_at?->format('d/m/Y H:i:s') ?? 'Chưa có' }}</div>
                </div>
            </div>

            <div class="col-12">
                <div class="settings-section mb-4">
                    @if ($roomInspection->workflow_stage === 'guest_consultation')
                        <div class="alert alert-info"><strong>Đang chờ lễ tân trao đổi lại với khách.</strong> Admin chỉ xem tiến độ, chưa được chốt phí.</div>
                    @elseif ($roomInspection->workflow_stage === 'housekeeping_recheck')
                        <div class="alert alert-warning"><strong>Khách vẫn chưa đồng ý một số khoản.</strong> Buồng phòng đang kiểm tra lại; sau đó lễ tân phải trao đổi tiếp với khách.</div>
                    @elseif ($roomInspection->workflow_stage === 'admin_approval')
                        <div class="alert alert-success"><strong>Khách đã đồng ý toàn bộ kết quả hiện tại.</strong> Admin kiểm tra các khoản dưới đây và xác nhận cuối.</div>
                    @elseif ($roomInspection->status === 'confirmed')
                        <div class="alert alert-success"><strong>Phiếu đã hoàn tất.</strong> Tổng phí được duyệt: {{ number_format((float) $roomInspection->approved_total, 0, ',', '.') }}đ.</div>
                    @else
                        <div class="alert alert-secondary">Phiếu chưa tới bước admin xác nhận.</div>
                    @endif

                    <div class="row g-2 mb-4">
                        <div class="col-6 col-md-3"><div class="inspection-stat"><div class="small text-muted">Hạng mục</div><strong>{{ $roomInspection->items->count() }}</strong></div></div>
                        <div class="col-6 col-md-3"><div class="inspection-stat"><div class="small text-muted">Khách đã đồng ý</div><strong>{{ $acceptedCount }}/{{ $roomInspection->items->count() }}</strong></div></div>
                        <div class="col-6 col-md-3"><div class="inspection-stat"><div class="small text-muted">Khoản bằng 0đ</div><strong>{{ $zeroChargeCount }}</strong></div></div>
                        <div class="col-6 col-md-3"><div class="inspection-stat"><div class="small text-muted">Tổng hiện tại</div><strong class="text-danger">{{ number_format($currentTotal, 0, ',', '.') }}đ</strong></div></div>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold mb-2">Bảng đối chiếu khách và buồng phòng</h5>
                        <div class="small text-muted mb-2">Dữ liệu được cập nhật theo thời gian thực. Admin chỉ xác nhận khi tất cả dòng đều khớp.</div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-2">
                                <thead>
                                    <tr>
                                        <th>Hạng mục</th>
                                        <th>Khách đối chiếu</th>
                                        <th>Buồng phòng kiểm tra</th>
                                        <th>Kết quả</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($roomInspection->items as $item)
                                        <tr class="{{ $item->guest_claimed_quantity !== null && (int) $item->guest_claimed_quantity === (int) $item->quantity ? 'table-success' : 'table-warning' }}">
                                            <td><strong>{{ $item->name }}</strong><div class="small text-muted">{{ number_format((float) $item->price, 0, ',', '.') }}đ / {{ $item->unit ?: 'đơn vị' }}</div></td>
                                            <td>
                                                @if ($item->guest_claimed_quantity !== null)
                                                    <strong>{{ (int) $item->guest_claimed_quantity }} {{ $item->unit ?: 'đơn vị' }}</strong>
                                                    @if($item->guest_response_note)<div class="small">{{ $item->guest_response_note }}</div>@endif
                                                @else
                                                    <span class="text-muted">Chưa có ý kiến</span>
                                                @endif
                                            </td>
                                            <td><strong>{{ (int) $item->quantity }} {{ $item->unit ?: 'đơn vị' }}</strong>@if($item->recheck_note)<div class="small">{{ $item->recheck_note }}</div>@endif</td>
                                            <td>
                                                @if($item->guest_claimed_quantity !== null && (int) $item->guest_claimed_quantity === (int) $item->quantity)
                                                    <span class="badge bg-success">Đã khớp</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Cần đối chiếu lại</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted">Không có khoản phát sinh.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if(!$allComparedValuesMatch && $roomInspection->status === 'reported')
                            <div class="alert alert-warning mb-0">Chưa thể xác nhận cuối vì khách và buồng phòng còn dữ liệu chưa khớp.</div>
                        @endif
                    </div>

                    <form action="{{ route('admin.inspection-approvals.approve', $roomInspection->id) }}" method="POST" id="approvalForm">
                        @csrf
                        <input type="hidden" name="viewed_version" value="{{ $pageVersion }}">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle approval-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:92px">Cộng phí</th>
                                        <th>Hạng mục</th>
                                        <th style="min-width:180px">Buồng phòng báo</th>
                                        <th style="min-width:170px">Khách xác nhận</th>
                                        <th style="min-width:230px">Kết quả xác minh cuối</th>
                                        <th style="min-width:230px">Lý do không cộng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($roomInspection->items as $item)
                                        <tr class="{{ (float) $item->total <= 0 ? 'table-success' : '' }}">
                                            <td class="text-center">
                                                <input
                                                    type="checkbox"
                                                    name="approved_item_ids[]"
                                                    value="{{ $item->id }}"
                                                    class="form-check-input approval-checkbox"
                                                    data-note-id="rejectNote{{ $item->id }}"
                                                    @checked(
                                                        old('approved_item_ids')
                                                            ? in_array($item->id, old('approved_item_ids', []))
                                                            : (
                                                                (float) $item->total > 0
                                                                && $item->guest_response === 'accepted'
                                                                && $item->recheck_decision !== 'remove_charge'
                                                            )
                                                    )
                                                    {{ (!$canApprove || (float) $item->total <= 0) ? 'disabled' : '' }}
                                                >
                                                <div class="small text-muted mt-1">Tích = cộng</div>
                                            </td>
                                            <td>
                                                <strong>{{ $item->name }}</strong>
                                                <div class="small text-muted">{{ $item->type === 'minibar' ? 'Minibar / đồ dùng' : 'Hư hại / mất đồ' }}</div>
                                            </td>
                                            <td>
                                                <strong>
                                                    {{ (int) (
                                                        $item->original_total > 0 && $item->price > 0
                                                            ? round($item->original_total / $item->price)
                                                            : $item->quantity
                                                    ) }}
                                                    {{ $item->unit ?: 'đơn vị' }}
                                                </strong>
                                                <div class="small text-muted">{{ number_format((float) $item->price, 0, ',', '.') }}đ / {{ $item->unit ?: 'đơn vị' }}</div>
                                            </td>
                                            <td>
                                                @if ($item->guest_claimed_quantity !== null)
                                                    <strong class="{{ $item->guest_response === 'accepted' ? 'text-success' : 'text-danger' }}">
                                                        {{ (int) $item->guest_claimed_quantity }} {{ $item->unit ?: 'đơn vị' }}
                                                    </strong>
                                                @else
                                                    <span class="text-muted">Chưa ghi nhận số lượng</span>
                                                @endif
                                                @if ($item->guest_response === 'accepted')
                                                    <div><span class="badge bg-success mt-1">Khách đồng ý</span></div>
                                                @elseif ($item->guest_response === 'disputed')
                                                    <div><span class="badge bg-danger mt-1">Khách vẫn chưa đồng ý</span></div>
                                                @else
                                                    <div><span class="badge bg-secondary mt-1">Chờ khách xác nhận lại</span></div>
                                                @endif
                                                @if ($item->guest_response_note)<div class="small mt-1">{{ $item->guest_response_note }}</div>@endif
                                            </td>
                                            <td>
                                                <strong>{{ (int) $item->quantity }} {{ $item->unit ?: 'đơn vị' }}</strong>
                                                <div class="mt-1">
                                                    {{ (int) $item->quantity }} × {{ number_format((float) $item->price, 0, ',', '.') }}đ
                                                    = <strong class="{{ (float) $item->total > 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float) $item->total, 0, ',', '.') }}đ</strong>
                                                </div>
                                                @if ($item->guest_claimed_quantity !== null)
                                                    @if ((int) $item->quantity === (int) $item->guest_claimed_quantity)
                                                        <div class="small text-success fw-semibold mt-1">Khớp số lượng khách xác nhận</div>
                                                    @elseif ((int) $item->quantity > (int) $item->guest_claimed_quantity)
                                                        <div class="small text-danger fw-semibold mt-1">Cao hơn ý kiến khách {{ (int) $item->quantity - (int) $item->guest_claimed_quantity }} {{ $item->unit ?: 'đơn vị' }}</div>
                                                    @else
                                                        <div class="small text-primary fw-semibold mt-1">Thấp hơn ý kiến khách {{ abs((int) $item->quantity - (int) $item->guest_claimed_quantity) }} {{ $item->unit ?: 'đơn vị' }}</div>
                                                    @endif
                                                @endif
                                                @if ($item->recheck_note)<div class="small text-muted mt-1">{{ $item->recheck_note }}</div>@endif
                                            </td>
                                            <td>
                                                @if ($canApprove)
                                                    <input
                                                        type="text"
                                                        name="rejection_notes[{{ $item->id }}]"
                                                        id="rejectNote{{ $item->id }}"
                                                        class="form-control form-control-sm rejection-note"
                                                        value="{{ old(
                                                            'rejection_notes.' . $item->id,
                                                            $item->admin_note ?: (
                                                                $item->recheck_decision === 'remove_charge'
                                                                    ? 'Số lượng xác minh cuối bằng 0 nên không cộng phí. ' . $item->recheck_note
                                                                    : ''
                                                            )
                                                        ) }}"
                                                        placeholder="Nhập lý do không cộng khoản này"
                                                    >
                                                @else
                                                    {{ $item->admin_note ?: (
                                                        $item->recheck_decision === 'remove_charge'
                                                            ? 'Số lượng xác minh cuối bằng 0 nên không cộng phí. ' . $item->recheck_note
                                                            : '---'
                                                    ) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">Không có khoản phát sinh. Admin chỉ cần xác nhận phiếu không phát sinh phí.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($canApprove)
                            <div class="mb-3">
                                <label class="form-label">Ghi chú chung của admin</label>
                                <textarea name="admin_note" rows="3" class="form-control" placeholder="Ghi rõ căn cứ nếu có quyết định khác kết quả buồng phòng">{{ old('admin_note', $roomInspection->admin_note) }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100" onclick="return confirm('Xác nhận các khoản đang được tích chọn và cộng vào hóa đơn?')">
                                Xác nhận các khoản được chọn
                            </button>
                        @elseif ($roomInspection->status === 'reported' && $roomInspection->workflow_stage === 'admin_approval')
                            <div class="alert alert-warning mb-0"><strong>Nút xác nhận đang khóa.</strong> Xem phần cập nhật bên dưới rồi bấm “Tôi đã xem các cập nhật mới”.</div>
                        @endif
                    </form>
                </div>

                <div class="settings-section" id="inspectionChanges">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">Lịch sử trao đổi và kiểm tra</h5>
                            <div class="small text-muted">Theo dõi rõ khách đã xác nhận bao nhiêu, buồng phòng kiểm tra lại bao nhiêu và ai đã cập nhật.</div>
                        </div>
                        @if ($hasUnseenUpdate && $roomInspection->status === 'reported' && $roomInspection->workflow_stage === 'admin_approval')
                            <form action="{{ route('admin.inspection-approvals.acknowledge', $roomInspection->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="version" value="{{ $pageVersion }}">
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Xác nhận bạn đã đọc các cập nhật mới nhất?')">Tôi đã xem các cập nhật mới</button>
                            </form>
                        @endif
                    </div>

                    @if ($roomInspection->last_update_summary)
                        <div class="alert alert-primary"><strong>Thay đổi gần nhất:</strong> {{ $roomInspection->last_update_summary }}</div>
                    @endif

                    @forelse ($revisionGroups as $version => $revisions)
                        <details class="compact-panel mb-3">
                            <summary>
                                <span>{{ $eventLabels[$revisions->first()?->event_type] ?? 'Cập nhật kết quả kiểm tra' }}</span>
                                <span class="badge-clean status-muted">{{ $revisions->first()?->created_at?->format('d/m/Y H:i:s') }} · {{ $revisions->count() }} thay đổi</span>
                            </summary>
                            <div class="compact-panel-body">
                                @foreach ($revisions as $revision)
                                    <div class="inspection-change-card rounded p-3 mb-2">
                                        <div class="d-flex justify-content-between gap-2">
                                            <strong>{{ $revision->summary }}</strong>
                                            <span class="small text-muted">{{ $revision->changer->name ?? 'Hệ thống' }}</span>
                                        </div>
                                        @if (!empty($revision->before_data) || !empty($revision->after_data))
                                            <div class="row g-2 mt-2">
                                                <div class="col-md-6">
                                                    <div class="inspection-before rounded p-2 h-100">
                                                        <strong>Trước đó</strong>
                                                        <div class="small mt-1">
                                                            @if(isset($revision->before_data['quantity'])) Số lượng: {{ $revision->before_data['quantity'] }}<br>@endif
                                                            @if(isset($revision->before_data['total'])) Số tiền: {{ number_format((float)$revision->before_data['total'],0,',','.') }}đ<br>@endif
                                                            @if(isset($revision->before_data['guest_claimed_quantity']) && $revision->before_data['guest_claimed_quantity'] !== null) Khách xác nhận: {{ $revision->before_data['guest_claimed_quantity'] }}<br>@endif
                                                            @if(!empty($revision->before_data['guest_response_note'])) Ghi chú khách: {{ $revision->before_data['guest_response_note'] }}<br>@endif
                                                            @if(!empty($revision->before_data['recheck_note'])) Kết quả kiểm tra: {{ $revision->before_data['recheck_note'] }}@endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="inspection-after rounded p-2 h-100">
                                                        <strong>Sau cập nhật</strong>
                                                        <div class="small mt-1">
                                                            @if(isset($revision->after_data['quantity'])) Số lượng: {{ $revision->after_data['quantity'] }}<br>@endif
                                                            @if(isset($revision->after_data['total'])) Số tiền: {{ number_format((float)$revision->after_data['total'],0,',','.') }}đ<br>@endif
                                                            @if(isset($revision->after_data['guest_claimed_quantity']) && $revision->after_data['guest_claimed_quantity'] !== null) Khách xác nhận: {{ $revision->after_data['guest_claimed_quantity'] }}<br>@endif
                                                            @if(!empty($revision->after_data['guest_response_note'])) Ghi chú khách: {{ $revision->after_data['guest_response_note'] }}<br>@endif
                                                            @if(!empty($revision->after_data['recheck_note'])) Kết quả kiểm tra: {{ $revision->after_data['recheck_note'] }}@endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <div class="text-muted">Chưa có cập nhật nào.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </main>
    <footer class="admin-footer"><span>MCuong Hotel Admin</span></footer>
</div>

<script>
const PAGE_VERSION = {{ $pageVersion }};
const updatesUrl = @json(route('admin.inspection-approvals.updates', $roomInspection->id));
const liveBanner = document.getElementById('liveUpdateBanner');

async function pollInspectionUpdates() {
    try {
        const response = await fetch(updatesUrl + '?since_version=' + PAGE_VERSION, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
        if (!response.ok) return;
        const data = await response.json();
        if (Number(data.current_version || 0) > PAGE_VERSION) {
            liveBanner.classList.remove('d-none');
            document.querySelectorAll('#approvalForm input, #approvalForm textarea, #approvalForm button').forEach(el => el.disabled = true);
        }
    } catch (error) {
        // Polling chỉ là cảnh báo giao diện; backend vẫn chặn xác nhận trên dữ liệu cũ.
    }
}
window.addEventListener('inspection:updated', function (event) {
    const detail = event.detail || {};
    if (Number(detail.id || detail.inspection_id || 0) !== {{ (int) $roomInspection->id }}) return;
    liveBanner.classList.remove('d-none');
    document.querySelectorAll('#approvalForm input, #approvalForm textarea, #approvalForm button').forEach(el => el.disabled = true);
});
setInterval(pollInspectionUpdates, 10000);

function updateApprovalNote(checkbox) {
    const note = document.getElementById(checkbox.dataset.noteId);
    if (!note) return;
    if (checkbox.checked) {
        note.value = '';
        note.disabled = true;
        note.placeholder = 'Khoản được cộng nên không cần lý do';
    } else {
        note.disabled = false;
        note.placeholder = 'Nhập lý do không cộng khoản này';
    }
}
document.querySelectorAll('.approval-checkbox').forEach(function (checkbox) {
    checkbox.addEventListener('change', () => updateApprovalNote(checkbox));
    updateApprovalNote(checkbox);
});
</script>
@endsection
