<?php $__env->startSection('title', 'Xác nhận kiểm tra phòng'); ?>

<?php $__env->startSection('content'); ?>
<?php
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
?>

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
            <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> /
            <a href="<?php echo e(route('admin.inspection-approvals.index')); ?>">Duyệt kiểm tra phòng</a> /
            Phòng <?php echo e($roomInspection->room->room_number ?? '---'); ?>

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

        <?php if($hasUnseenUpdate && $roomInspection->status === 'reported'): ?>
            <div class="alert alert-warning inspection-update-bar">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div><strong>Có kết quả mới từ lễ tân/buồng phòng.</strong></div>
                    <a href="#inspectionChanges" class="btn btn-warning btn-sm">Xem thay đổi</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="admin-page-head">
            <div>
                <h2>Xác nhận phí kiểm tra phòng <?php echo e($roomInspection->room->room_number ?? '---'); ?></h2>
                <p>Đối chiếu kết quả hiện tại với ý kiến khách. Chỉ những khoản được tích chọn mới được cộng vào hóa đơn.</p>
            </div>
            <a href="<?php echo e(route('admin.inspection-approvals.index')); ?>" class="btn btn-outline-secondary">Quay lại</a>
        </div>

        <?php if(session('success')): ?> <div class="alert alert-success"><?php echo e(session('success')); ?></div> <?php endif; ?>
        <?php if(session('error')): ?> <div class="alert alert-danger"><?php echo e(session('error')); ?></div> <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="alert alert-danger"><strong>Chưa thể xác nhận:</strong><ul class="mb-0 mt-1"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
        <?php endif; ?>

        <div class="row g-3 inspection-test-layout">
            <div class="col-12">
                <div class="settings-section h-100">
                    <h5 class="fw-bold mb-3">Thông tin kiểm tra</h5>
                    <div class="mb-3">
                        <div class="small text-muted">Tình trạng hiện tại</div>
                        <span class="badge <?php echo e($stageClasses[$roomInspection->workflow_stage] ?? 'bg-secondary'); ?>"><?php echo e($stageLabels[$roomInspection->workflow_stage] ?? $roomInspection->workflow_stage); ?></span>
                    </div>
                    <div class="mb-3"><div class="small text-muted">Booking</div><strong><?php echo e($roomInspection->booking->booking_code ?? '---'); ?></strong></div>
                    <div class="mb-3"><div class="small text-muted">Khách</div><strong><?php echo e($customerName ?: 'Chưa có tên'); ?></strong><div class="small text-muted"><?php echo e($roomInspection->booking->customer->phone ?? '---'); ?></div></div>
                    <div class="mb-3"><div class="small text-muted">Phòng</div><strong><?php echo e($roomInspection->room->room_number ?? '---'); ?></strong> · Tầng <?php echo e($roomInspection->room->floor_number ?? '---'); ?></div>
                    <div class="mb-3"><div class="small text-muted">Buồng phòng báo cáo</div><?php echo e($roomInspection->inspector->name ?? '---'); ?><div class="small text-muted"><?php echo e($roomInspection->inspected_at?->format('d/m/Y H:i:s')); ?></div></div>
                    <?php if($roomInspection->guestConsultant): ?>
                        <div class="mb-3"><div class="small text-muted">Lễ tân trao đổi gần nhất</div><?php echo e($roomInspection->guestConsultant->name); ?><div class="small text-muted"><?php echo e($roomInspection->guest_consulted_at?->format('d/m/Y H:i:s')); ?></div></div>
                    <?php endif; ?>
                    <div class="mb-0"><div class="small text-muted">Cập nhật gần nhất</div><?php echo e($roomInspection->last_revision_at?->format('d/m/Y H:i:s') ?? 'Chưa có'); ?></div>
                </div>
            </div>

            <div class="col-12">
                <div class="settings-section mb-4">
                    <?php if($roomInspection->workflow_stage === 'guest_consultation'): ?>
                        <div class="alert alert-info"><strong>Đang chờ lễ tân trao đổi lại với khách.</strong> Admin chỉ xem tiến độ, chưa được chốt phí.</div>
                    <?php elseif($roomInspection->workflow_stage === 'housekeeping_recheck'): ?>
                        <div class="alert alert-warning"><strong>Khách vẫn chưa đồng ý một số khoản.</strong> Buồng phòng đang kiểm tra lại; sau đó lễ tân phải trao đổi tiếp với khách.</div>
                    <?php elseif($roomInspection->workflow_stage === 'admin_approval'): ?>
                        <div class="alert alert-success"><strong>Khách đã đồng ý toàn bộ kết quả hiện tại.</strong> Admin kiểm tra các khoản dưới đây và xác nhận cuối.</div>
                    <?php elseif($roomInspection->status === 'confirmed'): ?>
                        <div class="alert alert-success"><strong>Phiếu đã hoàn tất.</strong> Tổng phí được duyệt: <?php echo e(number_format((float) $roomInspection->approved_total, 0, ',', '.')); ?>đ.</div>
                    <?php else: ?>
                        <div class="alert alert-secondary">Phiếu chưa tới bước admin xác nhận.</div>
                    <?php endif; ?>

                    <div class="row g-2 mb-4">
                        <div class="col-6 col-md-3"><div class="inspection-stat"><div class="small text-muted">Hạng mục</div><strong><?php echo e($roomInspection->items->count()); ?></strong></div></div>
                        <div class="col-6 col-md-3"><div class="inspection-stat"><div class="small text-muted">Khách đã đồng ý</div><strong><?php echo e($acceptedCount); ?>/<?php echo e($roomInspection->items->count()); ?></strong></div></div>
                        <div class="col-6 col-md-3"><div class="inspection-stat"><div class="small text-muted">Khoản bằng 0đ</div><strong><?php echo e($zeroChargeCount); ?></strong></div></div>
                        <div class="col-6 col-md-3"><div class="inspection-stat"><div class="small text-muted">Tổng hiện tại</div><strong class="text-danger"><?php echo e(number_format($currentTotal, 0, ',', '.')); ?>đ</strong></div></div>
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
                                    <?php $__empty_1 = true; $__currentLoopData = $roomInspection->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr class="<?php echo e($item->guest_claimed_quantity !== null && (int) $item->guest_claimed_quantity === (int) $item->quantity ? 'table-success' : 'table-warning'); ?>">
                                            <td><strong><?php echo e($item->name); ?></strong><div class="small text-muted"><?php echo e(number_format((float) $item->price, 0, ',', '.')); ?>đ / <?php echo e($item->unit ?: 'đơn vị'); ?></div></td>
                                            <td>
                                                <?php if($item->guest_claimed_quantity !== null): ?>
                                                    <strong><?php echo e((int) $item->guest_claimed_quantity); ?> <?php echo e($item->unit ?: 'đơn vị'); ?></strong>
                                                    <?php if($item->guest_response_note): ?><div class="small"><?php echo e($item->guest_response_note); ?></div><?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Chưa có ý kiến</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?php echo e((int) $item->quantity); ?> <?php echo e($item->unit ?: 'đơn vị'); ?></strong><?php if($item->recheck_note): ?><div class="small"><?php echo e($item->recheck_note); ?></div><?php endif; ?></td>
                                            <td>
                                                <?php if($item->guest_claimed_quantity !== null && (int) $item->guest_claimed_quantity === (int) $item->quantity): ?>
                                                    <span class="badge bg-success">Đã khớp</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Cần đối chiếu lại</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr><td colspan="4" class="text-center text-muted">Không có khoản phát sinh.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if(!$allComparedValuesMatch && $roomInspection->status === 'reported'): ?>
                            <div class="alert alert-warning mb-0">Chưa thể xác nhận cuối vì khách và buồng phòng còn dữ liệu chưa khớp.</div>
                        <?php endif; ?>
                    </div>

                    <form action="<?php echo e(route('admin.inspection-approvals.approve', $roomInspection->id)); ?>" method="POST" id="approvalForm">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="viewed_version" value="<?php echo e($pageVersion); ?>">
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
                                    <?php $__empty_1 = true; $__currentLoopData = $roomInspection->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <tr class="<?php echo e((float) $item->total <= 0 ? 'table-success' : ''); ?>">
                                            <td class="text-center">
                                                <input
                                                    type="checkbox"
                                                    name="approved_item_ids[]"
                                                    value="<?php echo e($item->id); ?>"
                                                    class="form-check-input approval-checkbox"
                                                    data-note-id="rejectNote<?php echo e($item->id); ?>"
                                                    <?php if(
                                                        old('approved_item_ids')
                                                            ? in_array($item->id, old('approved_item_ids', []))
                                                            : (
                                                                (float) $item->total > 0
                                                                && $item->guest_response === 'accepted'
                                                                && $item->recheck_decision !== 'remove_charge'
                                                            )
                                                    ): echo 'checked'; endif; ?>
                                                    <?php echo e((!$canApprove || (float) $item->total <= 0) ? 'disabled' : ''); ?>

                                                >
                                                <div class="small text-muted mt-1">Tích = cộng</div>
                                            </td>
                                            <td>
                                                <strong><?php echo e($item->name); ?></strong>
                                                <div class="small text-muted"><?php echo e($item->type === 'minibar' ? 'Minibar / đồ dùng' : 'Hư hại / mất đồ'); ?></div>
                                            </td>
                                            <td>
                                                <strong>
                                                    <?php echo e((int) (
                                                        $item->original_total > 0 && $item->price > 0
                                                            ? round($item->original_total / $item->price)
                                                            : $item->quantity
                                                    )); ?>

                                                    <?php echo e($item->unit ?: 'đơn vị'); ?>

                                                </strong>
                                                <div class="small text-muted"><?php echo e(number_format((float) $item->price, 0, ',', '.')); ?>đ / <?php echo e($item->unit ?: 'đơn vị'); ?></div>
                                            </td>
                                            <td>
                                                <?php if($item->guest_claimed_quantity !== null): ?>
                                                    <strong class="<?php echo e($item->guest_response === 'accepted' ? 'text-success' : 'text-danger'); ?>">
                                                        <?php echo e((int) $item->guest_claimed_quantity); ?> <?php echo e($item->unit ?: 'đơn vị'); ?>

                                                    </strong>
                                                <?php else: ?>
                                                    <span class="text-muted">Chưa ghi nhận số lượng</span>
                                                <?php endif; ?>
                                                <?php if($item->guest_response === 'accepted'): ?>
                                                    <div><span class="badge bg-success mt-1">Khách đồng ý</span></div>
                                                <?php elseif($item->guest_response === 'disputed'): ?>
                                                    <div><span class="badge bg-danger mt-1">Khách vẫn chưa đồng ý</span></div>
                                                <?php else: ?>
                                                    <div><span class="badge bg-secondary mt-1">Chờ khách xác nhận lại</span></div>
                                                <?php endif; ?>
                                                <?php if($item->guest_response_note): ?><div class="small mt-1"><?php echo e($item->guest_response_note); ?></div><?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo e((int) $item->quantity); ?> <?php echo e($item->unit ?: 'đơn vị'); ?></strong>
                                                <div class="mt-1">
                                                    <?php echo e((int) $item->quantity); ?> × <?php echo e(number_format((float) $item->price, 0, ',', '.')); ?>đ
                                                    = <strong class="<?php echo e((float) $item->total > 0 ? 'text-danger' : 'text-success'); ?>"><?php echo e(number_format((float) $item->total, 0, ',', '.')); ?>đ</strong>
                                                </div>
                                                <?php if($item->guest_claimed_quantity !== null): ?>
                                                    <?php if((int) $item->quantity === (int) $item->guest_claimed_quantity): ?>
                                                        <div class="small text-success fw-semibold mt-1">Khớp số lượng khách xác nhận</div>
                                                    <?php elseif((int) $item->quantity > (int) $item->guest_claimed_quantity): ?>
                                                        <div class="small text-danger fw-semibold mt-1">Cao hơn ý kiến khách <?php echo e((int) $item->quantity - (int) $item->guest_claimed_quantity); ?> <?php echo e($item->unit ?: 'đơn vị'); ?></div>
                                                    <?php else: ?>
                                                        <div class="small text-primary fw-semibold mt-1">Thấp hơn ý kiến khách <?php echo e(abs((int) $item->quantity - (int) $item->guest_claimed_quantity)); ?> <?php echo e($item->unit ?: 'đơn vị'); ?></div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <?php if($item->recheck_note): ?><div class="small text-muted mt-1"><?php echo e($item->recheck_note); ?></div><?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($canApprove): ?>
                                                    <input
                                                        type="text"
                                                        name="rejection_notes[<?php echo e($item->id); ?>]"
                                                        id="rejectNote<?php echo e($item->id); ?>"
                                                        class="form-control form-control-sm rejection-note"
                                                        value="<?php echo e(old(
                                                            'rejection_notes.' . $item->id,
                                                            $item->admin_note ?: (
                                                                $item->recheck_decision === 'remove_charge'
                                                                    ? 'Số lượng xác minh cuối bằng 0 nên không cộng phí. ' . $item->recheck_note
                                                                    : ''
                                                            )
                                                        )); ?>"
                                                        placeholder="Nhập lý do không cộng khoản này"
                                                    >
                                                <?php else: ?>
                                                    <?php echo e($item->admin_note ?: (
                                                        $item->recheck_decision === 'remove_charge'
                                                            ? 'Số lượng xác minh cuối bằng 0 nên không cộng phí. ' . $item->recheck_note
                                                            : '---'
                                                    )); ?>

                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr><td colspan="6" class="text-center text-muted">Không có khoản phát sinh. Admin chỉ cần xác nhận phiếu không phát sinh phí.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if($canApprove): ?>
                            <div class="mb-3">
                                <label class="form-label">Ghi chú chung của admin</label>
                                <textarea name="admin_note" rows="3" class="form-control" placeholder="Ghi rõ căn cứ nếu có quyết định khác kết quả buồng phòng"><?php echo e(old('admin_note', $roomInspection->admin_note)); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100" id="btnSubmitApproval" onclick="if(confirm('Xác nhận các khoản đang được tích chọn và cộng vào hóa đơn?')) { this.disabled=true; this.innerText='Đang xử lý...'; this.form.submit(); } return false;">
                                Xác nhận các khoản được chọn
                            </button>
                        <?php elseif($roomInspection->status === 'reported' && $roomInspection->workflow_stage === 'admin_approval'): ?>
                            <div class="alert alert-warning mb-0"><strong>Nút xác nhận đang khóa.</strong> Xem phần cập nhật bên dưới rồi bấm “Tôi đã xem các cập nhật mới”.</div>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="settings-section" id="inspectionChanges">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">Lịch sử trao đổi và kiểm tra</h5>
                            <div class="small text-muted">Theo dõi rõ khách đã xác nhận bao nhiêu, buồng phòng kiểm tra lại bao nhiêu và ai đã cập nhật.</div>
                        </div>
                        <?php if($hasUnseenUpdate && $roomInspection->status === 'reported' && $roomInspection->workflow_stage === 'admin_approval'): ?>
                            <form action="<?php echo e(route('admin.inspection-approvals.acknowledge', $roomInspection->id)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="version" value="<?php echo e($pageVersion); ?>">
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Xác nhận bạn đã đọc các cập nhật mới nhất?')">Tôi đã xem các cập nhật mới</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if($roomInspection->last_update_summary): ?>
                        <div class="alert alert-primary"><strong>Thay đổi gần nhất:</strong> <?php echo e($roomInspection->last_update_summary); ?></div>
                    <?php endif; ?>

                    <?php $__empty_1 = true; $__currentLoopData = $revisionGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $version => $revisions): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <details class="compact-panel mb-3">
                            <summary>
                                <span><?php echo e($eventLabels[$revisions->first()?->event_type] ?? 'Cập nhật kết quả kiểm tra'); ?></span>
                                <span class="badge-clean status-muted"><?php echo e($revisions->first()?->created_at?->format('d/m/Y H:i:s')); ?> · <?php echo e($revisions->count()); ?> thay đổi</span>
                            </summary>
                            <div class="compact-panel-body">
                                <?php $__currentLoopData = $revisions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $revision): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="inspection-change-card rounded p-3 mb-2">
                                        <div class="d-flex justify-content-between gap-2">
                                            <strong><?php echo e($revision->summary); ?></strong>
                                            <span class="small text-muted"><?php echo e($revision->changer->name ?? 'Hệ thống'); ?></span>
                                        </div>
                                        <?php if(!empty($revision->before_data) || !empty($revision->after_data)): ?>
                                            <div class="row g-2 mt-2">
                                                <div class="col-md-6">
                                                    <div class="inspection-before rounded p-2 h-100">
                                                        <strong>Trước đó</strong>
                                                        <div class="small mt-1">
                                                            <?php if(isset($revision->before_data['quantity'])): ?> Số lượng: <?php echo e($revision->before_data['quantity']); ?><br><?php endif; ?>
                                                            <?php if(isset($revision->before_data['total'])): ?> Số tiền: <?php echo e(number_format((float)$revision->before_data['total'],0,',','.')); ?>đ<br><?php endif; ?>
                                                            <?php if(isset($revision->before_data['guest_claimed_quantity']) && $revision->before_data['guest_claimed_quantity'] !== null): ?> Khách xác nhận: <?php echo e($revision->before_data['guest_claimed_quantity']); ?><br><?php endif; ?>
                                                            <?php if(!empty($revision->before_data['guest_response_note'])): ?> Ghi chú khách: <?php echo e($revision->before_data['guest_response_note']); ?><br><?php endif; ?>
                                                            <?php if(!empty($revision->before_data['recheck_note'])): ?> Kết quả kiểm tra: <?php echo e($revision->before_data['recheck_note']); ?><?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="inspection-after rounded p-2 h-100">
                                                        <strong>Sau cập nhật</strong>
                                                        <div class="small mt-1">
                                                            <?php if(isset($revision->after_data['quantity'])): ?> Số lượng: <?php echo e($revision->after_data['quantity']); ?><br><?php endif; ?>
                                                            <?php if(isset($revision->after_data['total'])): ?> Số tiền: <?php echo e(number_format((float)$revision->after_data['total'],0,',','.')); ?>đ<br><?php endif; ?>
                                                            <?php if(isset($revision->after_data['guest_claimed_quantity']) && $revision->after_data['guest_claimed_quantity'] !== null): ?> Khách xác nhận: <?php echo e($revision->after_data['guest_claimed_quantity']); ?><br><?php endif; ?>
                                                            <?php if(!empty($revision->after_data['guest_response_note'])): ?> Ghi chú khách: <?php echo e($revision->after_data['guest_response_note']); ?><br><?php endif; ?>
                                                            <?php if(!empty($revision->after_data['recheck_note'])): ?> Kết quả kiểm tra: <?php echo e($revision->after_data['recheck_note']); ?><?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </details>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-muted">Chưa có cập nhật nào.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <footer class="admin-footer"><span>MCuong Hotel Admin</span></footer>
</div>

<script>
const PAGE_VERSION = <?php echo e($pageVersion); ?>;
const updatesUrl = <?php echo json_encode(route('admin.inspection-approvals.updates', $roomInspection->id), 512) ?>;
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
    if (Number(detail.id || detail.inspection_id || 0) !== <?php echo e((int) $roomInspection->id); ?>) return;
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\inspection-approvals\show.blade.php ENDPATH**/ ?>