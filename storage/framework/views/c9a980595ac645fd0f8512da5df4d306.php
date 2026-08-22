<?php
    $roomIssueHoldMinutes = max(5, (int) app(\App\Services\HotelPolicyService::class)
        ->forBooking($booking ?? null, 'room_issue.proposal_hold_minutes', 30));
?>


<?php $__env->startSection('title', 'Xử lý nhóm sự cố phòng'); ?>

<?php $__env->startSection('content'); ?>
<?php
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
?>

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
    .promo-picker { border: 1px solid #dfe5ec; border-radius: 14px; overflow: hidden; background: #fff; }
    .promo-picker-head { padding: 10px 11px; border-bottom: 1px solid #e9edf2; background: #f8fafc; }
    .promo-picker-tools { display: flex; gap: 8px; align-items: center; }
    .promo-picker-tools .form-control { min-width: 0; }
    .promo-selected-count { white-space: nowrap; font-size: 11px; font-weight: 800; color: #315c8a; background: #eaf3ff; border-radius: 999px; padding: 5px 8px; }
    .promo-scroll { max-height: 220px; overflow-y: auto; overscroll-behavior: contain; }
    .promo-row {
        display: grid;
        grid-template-columns: 22px minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 11px;
        border-bottom: 1px solid #edf0f4;
        cursor: pointer;
        transition: background .15s ease, border-color .15s ease;
    }
    .promo-row:last-child { border-bottom: 0; }
    .promo-row:hover { background: #f8fafc; }
    .promo-row:has(input:checked) { background: #eef6ff; box-shadow: inset 3px 0 0 #3b82f6; }
    .promo-main { min-width: 0; }
    .promo-code { display: block; font-weight: 850; color: #10233f; overflow-wrap: anywhere; }
    .promo-name { display: block; margin-top: 2px; font-size: 11px; color: #6d788b; line-height: 1.35; overflow-wrap: anywhere; }
    .promo-value { align-self: center; white-space: nowrap; font-size: 12px; font-weight: 850; color: #1769c2; background: #eff6ff; border-radius: 999px; padding: 5px 8px; }
    .promo-no-result { padding: 18px 12px; text-align: center; font-size: 12px; color: #7a8699; display: none; }
    @media (max-width: 1200px) { .promo-row { grid-template-columns: 22px minmax(0,1fr); } .promo-value { grid-column: 2; justify-self: start; margin-top: -4px; } }
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
        position: relative;
        scroll-margin-top: 92px;
        border: 2px solid #22a447 !important;
        box-shadow: 0 12px 30px rgba(22, 163, 74, .16);
        overflow: visible;
    }
    .finalize-action-box {
        position: sticky;
        top: 82px;
        z-index: 20;
        margin-bottom: 14px;
        padding: 12px;
        border: 1px solid #b9ddc2;
        border-radius: 12px;
        background: rgba(255, 255, 255, .98);
        box-shadow: 0 8px 22px rgba(15, 94, 45, .12);
        backdrop-filter: blur(4px);
    }
    .finalize-button {
        min-height: 50px;
        font-size: .96rem;
        font-weight: 800;
    }
    @media (max-width: 1199.98px) {
        .finalize-action-box { position: static; }
    }
</style>

<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a>
            /
            <a href="<?php echo e(route('admin.room-issues.index')); ?>">Sự cố phòng</a>
            /
            <?php echo e($booking->booking_code); ?>

        </p>

        <div class="admin-page-head">
            <div>
                <h2>Phiếu sự cố booking <?php echo e($booking->booking_code); ?></h2>
                <p><?php echo e($issues->count()); ?> phòng trong cùng một lần khách báo; mỗi phòng lỗi có danh sách mã bù đắp riêng, không ảnh hưởng phòng bình thường.</p>
            </div>
            <span class="badge text-bg-primary fs-6">
                <?php echo e($workflowLabels[$leaderStatus] ?? $leaderStatus); ?>

            </span>
        </div>

<div id="roomIssueLiveUpdate" class="alert alert-danger d-none position-sticky" style="top:8px;z-index:1030;">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div><strong>Có cập nhật mới từ lễ tân hoặc khách.</strong> Tải lại trước khi xác nhận để tránh dùng phương án cũ.</div>
                <button type="button" class="btn btn-danger btn-sm" onclick="window.location.reload()">Tải lại cập nhật</button>
            </div>
        </div>

        <?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if($leaderStatus === 'guest_accepted'): ?>
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
        <?php endif; ?>

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

                    <?php if($leader->guest_response_note): ?>
                        <div class="alert <?php echo e($leader->guest_response === 'accepted' ? 'alert-success' : 'alert-warning'); ?> mt-3 mb-0">
                            <strong>Ghi chú từ lễ tân/khách:</strong>
                            <?php echo e($leader->guest_response_note); ?>

                        </div>
                    <?php endif; ?>
                </div>

                <?php $__currentLoopData = $issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
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
                    ?>

                    <div class="issue-card">
                        <div class="d-flex justify-content-between gap-3 flex-wrap mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">
                                    Phòng <?php echo e($issue->currentRoom?->room_number ?? '---'); ?>

                                    · <?php echo e($issue->currentRoom?->category?->name ?? '---'); ?>

                                </h5>
                                <div class="text-muted"><?php echo e($issue->issue_description); ?></div>
                            </div>
                            <span class="badge text-bg-light border align-self-start">
                                Yêu cầu #<?php echo e($issue->id); ?>

                            </span>
                        </div>

                        <?php if($issue->attachments->isNotEmpty()): ?>
                            <div class="d-flex gap-2 flex-wrap mb-3">
                                <?php $__currentLoopData = $issue->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <a href="<?php echo e(route('admin.room-issue-attachments.show', $attachment)); ?>" target="_blank">
                                        <img
                                            src="<?php echo e(route('admin.room-issue-attachments.show', $attachment)); ?>"
                                            class="issue-photo"
                                            alt="Ảnh sự cố phòng"
                                        >
                                    </a>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>

                        <?php if($preview): ?>
                            <div class="proposal-box">
                                <div class="d-flex justify-content-between gap-2 flex-wrap">
                                    <div>
                                        <div class="fw-bold text-primary">
                                            <?php echo e($preview['label'] ?? ($resolutionLabels[$preview['type']] ?? '---')); ?>

                                        </div>
                                    </div>

                                    <?php if($preview['room'] ?? null): ?>
                                        <span class="badge text-bg-info align-self-start">
                                            Phòng <?php echo e($preview['room']->room_number); ?>

                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if($preview['room'] ?? null): ?>
                                    <div class="mt-2">
                                        Chuyển sang <strong>phòng <?php echo e($preview['room']->room_number); ?></strong>
                                        · <?php echo e($preview['room']->category?->name ?? '---'); ?>.
                                    </div>
                                <?php else: ?>
                                    <div class="mt-2">
                                        Khách tiếp tục ở phòng hiện tại; buồng phòng nhận việc sửa gấp riêng.
                                    </div>
                                <?php endif; ?>

                                <?php if(!empty($preview['description'])): ?>
                                    <div class="small text-muted mt-1"><?php echo e($preview['description']); ?></div>
                                <?php endif; ?>

                                <?php if(($preview['room'] ?? null) && abs($issuePriceDifferenceTotal) > 0.01): ?>
                                    <div class="border rounded-3 bg-white p-3 mt-3">
                                        <div class="fw-semibold mb-1">Tiền phòng của riêng phòng này</div>
                                        <div class="small">
                                            Giá đang tính: <strong><?php echo e(number_format($issueOldNightPrice, 0, ',', '.')); ?>đ/đêm</strong><br>
                                            Giá phòng thay thế: <strong><?php echo e(number_format($issueNewNightPrice, 0, ',', '.')); ?>đ/đêm</strong><br>
                                            <?php if($issuePriceDifferenceTotal > 0): ?>
                                                Phần tăng: <strong class="text-danger"><?php echo e(number_format($issuePriceDifferencePerNight, 0, ',', '.')); ?>đ × <?php echo e($issueRemainingNights); ?> đêm = <?php echo e(number_format($issuePriceDifferenceTotal, 0, ',', '.')); ?>đ</strong>
                                                <div class="text-muted mt-1">Nếu không chọn mã hỗ trợ đủ giá trị, phần còn lại sẽ được cộng vào tiền khách phải trả.</div>
                                            <?php else: ?>
                                                Phần giảm: <strong class="text-success"><?php echo e(number_format(abs($issuePriceDifferenceTotal), 0, ',', '.')); ?>đ</strong>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if($issue->proposed_room_id && $issue->proposal_expires_at): ?>
                                    <div class="small text-danger mt-2">
                                        Giữ phòng đến
                                        <strong><?php echo e($issue->proposal_expires_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')); ?></strong>.
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if($guestChoice): ?>
                            <div class="choice-box mt-3">
                                <div class="small text-muted">Khách đã chọn</div>
                                <div class="fw-bold text-success">
                                    <?php echo e($resolutionLabels[$guestChoice] ?? $guestChoice); ?>

                                    <?php if($guestChoice !== 'repair_only' && $issue->proposedRoom): ?>
                                        · phòng <?php echo e($issue->proposedRoom->room_number); ?>

                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>


                        <?php if($issueSelectedPromotionCodes->isNotEmpty()): ?>
                            <div class="alert alert-info py-2 mt-3 mb-0 small">
                                <strong>Mã bù đắp riêng phòng này:</strong>
                                <?php echo e($issueSelectedPromotionCodes->implode(', ')); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if($canRebuildProposal): ?>
                    <form
                        id="roomIssueProposalForm"
                        method="POST"
                        action="<?php echo e(route('admin.room-issues.proposal', $leader)); ?>"
                        class="settings-section mt-3"
                    >
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>

                        <?php if($leaderStatus === 'proposal_ready'): ?>
                            <h5 class="fw-bold mb-2">Phương án đã được giữ ngay khi khách báo</h5>
                            <p class="small text-muted mb-3">
                            </p>
                        <?php elseif($leaderStatus === 'pending'): ?>
                            <p class="small text-muted mb-3">
                            </p>
                        <?php else: ?>
                            <h5 class="fw-bold mb-2">Gửi lại đầy đủ lựa chọn cho lễ tân</h5>
                            <p class="small text-muted mb-3">
                            </p>
                        <?php endif; ?>

                        <?php if($selectedPromotionCodesByIssue->flatten()->isNotEmpty()): ?>
                            <div class="alert alert-info py-2">
                                <strong>Mã bù đắp đang được giữ theo từng phòng:</strong>
                                <div class="small mt-1">
                                    <?php $__currentLoopData = $issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $codes = $selectedPromotionCodesByIssue->get((int) $issue->id, collect());
                                        ?>
                                        <div>
                                            Phòng <?php echo e($issue->currentRoom?->room_number ?? '---'); ?>:
                                            <?php echo e($codes->isEmpty() ? 'không chọn mã' : $codes->implode(', ')); ?>

                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <button class="btn btn-primary w-100 mt-2">
                            <i class="bx bx-send me-1"></i>
                            <?php if($leaderStatus === 'proposal_ready'): ?>
                                Gửi phương án đã giữ sang lễ tân
                            <?php elseif($leaderStatus === 'pending'): ?>
                                Tạo phương án và gửi lễ tân
                            <?php else: ?>
                                Gửi lại đầy đủ phương án và làm mới giữ phòng <?php echo e($roomIssueHoldMinutes); ?> phút
                            <?php endif; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="col-xl-4">
                <div class="settings-section mb-3">
                    <h5 class="fw-bold mb-3">Booking</h5>
                    <div class="d-grid gap-2 small">
                        <div>
                            <span class="text-muted d-block">Khách</span>
                            <strong><?php echo e($booking->booked_customer_name); ?></strong>
                        </div>
                        <div>
                            <span class="text-muted d-block">Thời gian lưu trú</span>
                            <strong>
                                <?php echo e($booking->check_in_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?>

                                →
                                <?php echo e($booking->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?>

                            </strong>
                        </div>
                        <a href="<?php echo e(route('admin.bookings.show', $booking)); ?>" class="btn btn-outline-secondary">
                            Mở chi tiết booking
                        </a>
                    </div>
                </div>

                <?php if($leaderStatus === 'guest_accepted'): ?>
                    <form
                        id="finalize-room-issue"
                        method="POST"
                        action="<?php echo e(route('admin.room-issues.finalize', $leader)); ?>"
                        class="settings-section finalize-panel"
                    >
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        <input type="hidden" name="issue_promotion_codes_present" value="1">
                        <input type="hidden" name="guest_response_snapshot" value="<?php echo e($guestResponseSnapshot); ?>">

                        <h5 class="fw-bold mb-2">Xác nhận cuối</h5>
                        <p class="small text-muted">
                            Lễ tân đã ghi nhận lựa chọn của khách cho từng phòng. Quản lý kiểm tra lại rồi thực hiện đồng thời.
                        </p>

                        <p class="small text-muted mb-2">
                            Mỗi phòng lỗi có mã riêng. Giá phòng mới được tính thật; mã hỗ trợ chỉ bù cho đúng phòng này. Phần chênh chưa được mã bù sẽ do khách thanh toán.
                        </p>

                        <div class="finalize-action-box">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bx bx-check-shield text-success fs-4"></i>
                                <strong>Hoàn tất xử lý</strong>
                            </div>
                            <label class="form-label fw-semibold mb-1">Ghi chú xác nhận cuối</label>
                            <textarea name="admin_note" class="form-control" rows="2" required><?php echo e(old('admin_note', $leader->admin_note)); ?></textarea>

                            <button class="btn btn-success finalize-button w-100 mt-2">
                                <i class="bx bx-check-shield me-1"></i>
                                Xác nhận và thực hiện toàn bộ phương án
                            </button>
                            <div class="small text-muted mt-2">Có thể chọn mã bù đắp ở các thẻ phía dưới trước khi xác nhận.</div>
                        </div>

                        <div class="small fw-semibold text-muted mb-2">Mã bù đắp theo từng phòng</div>

                        <div class="d-grid gap-3 mb-3">
                            <?php $__currentLoopData = $issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $issueCodes = $selectedPromotionCodesByIssue->get((int) $issue->id, collect());
                                ?>
                                <div class="border rounded-3 p-3 bg-white">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap mb-2">
                                        <div>
                                            <strong>Phòng <?php echo e($issue->currentRoom?->room_number ?? '---'); ?></strong>
                                            <div class="small text-muted">
                                                <?php echo e($resolutionLabels[$issue->guest_selected_resolution_type] ?? 'Chưa chọn'); ?>

                                                <?php if($issue->guest_selected_resolution_type !== 'repair_only' && $issue->proposedRoom): ?>
                                                    → phòng <?php echo e($issue->proposedRoom->room_number); ?>

                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="badge text-bg-primary align-self-start">Mã chỉ cho phòng này</span>
                                    </div>

                                    <?php if($issueCodes->isNotEmpty()): ?>
                                        <div class="alert alert-primary py-2 mb-2">
                                            <strong>Mã đã gắn cho lần gửi này:</strong>
                                            <?php echo e($issueCodes->implode(', ')); ?>

                                            <div class="small mt-1">Mã đã khóa, không thể chọn lại ở lần gửi sau.</div>
                                            <?php $__currentLoopData = $issueCodes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lockedCode): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <input type="hidden" name="issue_promotion_codes[<?php echo e($issue->id); ?>][]" value="<?php echo e($lockedCode); ?>">
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if($promotions->isNotEmpty()): ?>
                                        <div class="small text-muted mb-2">
                                            Không giới hạn số mã ở bước bồi thường sự cố. Hệ thống đã tự ẩn các mã khách này từng sử dụng trước đây.
                                        </div>
                                        <div class="promo-picker" data-promo-picker>
                                            <div class="promo-picker-head">
                                                <div class="promo-picker-tools">
                                                    <div class="input-group input-group-sm flex-grow-1">
                                                        <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                                                        <input type="search" class="form-control" placeholder="Tìm mã hoặc tên ưu đãi..." data-promo-search>
                                                    </div>
                                                    <span class="promo-selected-count" data-promo-count>0 đã chọn</span>
                                                </div>
                                            </div>
                                            <div class="promo-scroll">
                                                <?php $__currentLoopData = $promotions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promotion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <label class="promo-row" data-promo-row data-promo-text="<?php echo e(mb_strtolower($promotion->code . ' ' . $promotion->name)); ?>">
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input room-issue-promotion-checkbox"
                                                            name="issue_promotion_codes[<?php echo e($issue->id); ?>][]"
                                                            value="<?php echo e($promotion->code); ?>"
                                                        >
                                                        <span class="promo-main">
                                                            <span class="promo-code"><?php echo e($promotion->code); ?></span>
                                                            <span class="promo-name"><?php echo e($promotion->name); ?></span>
                                                        </span>
                                                        <span class="promo-value">
                                                            <?php if($promotion->discount_type === 'percent'): ?>
                                                                <?php echo e(rtrim(rtrim(number_format((float) $promotion->discount_value, 2, ',', '.'), '0'), ',')); ?>%
                                                            <?php else: ?>
                                                                <?php echo e(number_format((float) $promotion->discount_value, 0, ',', '.')); ?>đ
                                                            <?php endif; ?>
                                                        </span>
                                                    </label>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                <div class="promo-no-result" data-promo-empty>Không tìm thấy mã phù hợp.</div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-light border mb-0">Không còn mã đủ điều kiện hoặc khách đã dùng hết các mã phù hợp trước đó.</div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                    </form>
                <?php elseif($leaderStatus === 'waiting_guest_confirmation'): ?>
                    <div class="settings-section">
                        <div class="alert alert-info mb-2">
                            Đang chờ lễ tân cho khách chọn phương án của từng phòng.
                        </div>
                        <?php if($selectedPromotionCodesByIssue->flatten()->isNotEmpty()): ?>
                            <div class="small mb-2">
                                <strong>Mã đã lưu theo phòng:</strong>
                                <?php $__currentLoopData = $issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                            $codes = $selectedPromotionCodesByIssue->get((int) $issue->id, collect());
                                        ?>
                                    <div>Phòng <?php echo e($issue->currentRoom?->room_number ?? '---'); ?>: <?php echo e($codes->isEmpty() ? 'không có' : $codes->implode(', ')); ?></div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                        <a href="<?php echo e(route('admin.bookings.room-issue-proposal', $booking)); ?>" class="btn btn-outline-primary w-100">
                            Xem màn lễ tân
                        </a>
                    </div>
                <?php elseif($leaderStatus === 'guest_requested_change'): ?>
                    <div class="settings-section">
                        <div class="alert alert-warning mb-0">
                            Khách muốn trao đổi lại. Bấm gửi lại để lễ tân vẫn thấy đầy đủ phương án đổi phòng/nâng hạng và giữ nguyên sửa gấp; phòng đang giữ sẽ được làm mới thêm <?php echo e($roomIssueHoldMinutes); ?> phút.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script>
window.addEventListener('booking:updated', function (event) {
    const detail = event.detail || {};
    if (Number(detail.id || detail.booking_id || 0) !== <?php echo e((int) $booking->id); ?>) return;
    if (!['room_issue_guest_updated', 'room_issue_proposal_sent'].includes(detail.action)) return;
    const banner = document.getElementById('roomIssueLiveUpdate');
    if (banner) banner.classList.remove('d-none');
    document.querySelectorAll('#finalize-room-issue input, #finalize-room-issue textarea, #finalize-room-issue button')
        .forEach(function (element) { element.disabled = true; });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-promo-picker]').forEach(function (picker) {
        const search = picker.querySelector('[data-promo-search]');
        const rows = Array.from(picker.querySelectorAll('[data-promo-row]'));
        const count = picker.querySelector('[data-promo-count]');
        const empty = picker.querySelector('[data-promo-empty]');

        const refresh = function () {
            const term = (search?.value || '').trim().toLocaleLowerCase('vi');
            let visible = 0;
            let selected = 0;
            rows.forEach(function (row) {
                const checkbox = row.querySelector('input[type="checkbox"]');
                if (checkbox?.checked) selected += 1;
                const haystack = (row.dataset.promoText || '').toLocaleLowerCase('vi');
                const show = term === '' || haystack.includes(term);
                row.hidden = !show;
                if (show) visible += 1;
            });
            if (count) count.textContent = selected + ' đã chọn';
            if (empty) empty.style.display = visible === 0 ? 'block' : 'none';
        };

        search?.addEventListener('input', refresh);
        picker.addEventListener('change', function (event) {
            if (event.target.matches('.room-issue-promotion-checkbox')) refresh();
        });
        refresh();
    });

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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/room-issues/group-show.blade.php ENDPATH**/ ?>