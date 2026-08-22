<?php
    $roomIssueHoldMinutes = max(5, (int) app(\App\Services\HotelPolicyService::class)
        ->forBooking($booking ?? null, 'room_issue.proposal_hold_minutes', 30));
?>


<?php $__env->startSection('title', 'Trao đổi phương án sự cố với khách'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $labels = [
        'same_category' => 'Đổi phòng cùng hạng',
        'upgrade_category' => 'Nâng hạng miễn phí',
        'repair_only' => 'Giữ nguyên phòng, sửa gấp',
    ];
    $isWaiting = $leader->workflow_status === 'waiting_guest_confirmation';
    $hasHeldRoom = $issues->contains(fn ($issue) => in_array($issue->proposed_resolution_type, ['same_category', 'upgrade_category'], true));
    $groupHoldExpiresAt = $issues
        ->filter(fn ($issue) => $issue->proposed_room_id && $issue->proposal_expires_at)
        ->sortBy(fn ($issue) => $issue->proposal_expires_at->timestamp)
        ->first()?->proposal_expires_at;
?>

<style>
    .proposal-item {
        border: 1px solid #dfe6ee;
        border-radius: 14px;
        background: #fff;
        padding: 16px;
    }
    .proposal-item + .proposal-item { margin-top: 14px; }
    .option-row {
        display: block;
        border: 1px solid #dfe5ec;
        border-radius: 12px;
        padding: 12px 14px 12px 44px;
        position: relative;
        cursor: pointer;
        background: #fff;
    }
    .option-row + .option-row { margin-top: 9px; }
    .option-row:hover { background: #f8fafc; }
    .option-row:has(input:checked) {
        border-color: #5b9fe8;
        background: #eef6ff;
    }
    .option-row .form-check-input {
        position: absolute;
        left: 15px;
        top: 14px;
        margin: 0;
    }
    .choice-summary {
        border: 1px solid #cfe8d6;
        border-radius: 12px;
        background: #f4fbf6;
        padding: 12px 14px;
    }
</style>

<div class="admin-wrapper">
    <main class="admin-content">
        <p class="admin-breadcrumb mb-3">
            <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a>
            /
            <a href="<?php echo e(route('admin.bookings.show', $booking)); ?>"><?php echo e($booking->booking_code); ?></a>
            /
            Phương án sự cố
        </p>

        <div class="admin-page-head">
            <div>
                <h2>Cho khách chọn phương án xử lý</h2>
            </div>
        </div>

<div id="roomIssueReceptionLiveUpdate" class="alert alert-primary d-none position-sticky" style="top:8px;z-index:1030;">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div><strong>Quản lý vừa gửi phương án mới.</strong> Tải lại để xem phòng, mã hỗ trợ và thời hạn giữ mới nhất.</div>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.location.reload()">Tải lại phương án</button>
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

        <form method="POST" action="<?php echo e(route('admin.bookings.room-issue-proposal.respond', $booking)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <div class="row g-3">
                <div class="col-xl-8">
                    <?php $__currentLoopData = $issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $proposedType = $issue->proposed_resolution_type;
                            $selectedChoice = old(
                                "items.{$issue->id}.choice",
                                $issue->guest_selected_resolution_type
                                    ?: ($proposedType ?: 'repair_only')
                            );
                        ?>

                        <div class="proposal-item">
                            <div class="d-flex justify-content-between gap-2 flex-wrap mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1">
                                        Phòng <?php echo e($issue->currentRoom?->room_number ?? '---'); ?>

                                        · <?php echo e($issue->currentRoom?->category?->name ?? '---'); ?>

                                    </h5>
                                    <div class="text-muted"><?php echo e($issue->issue_description); ?></div>
                                </div>
                                <span class="badge text-bg-primary align-self-start">
                                    <?php echo e($labels[$proposedType] ?? 'Chưa có phương án'); ?>

                                </span>
                            </div>

                            <?php
                                $supportCodes = collect($issue->promotion_codes ?? [])->filter()->values();
                            ?>
                            <div class="alert <?php echo e($supportCodes->isNotEmpty() ? 'alert-success' : 'alert-warning'); ?> py-2">
                                <?php if($supportCodes->isNotEmpty()): ?>
                                    <strong>Hỗ trợ khách đã được quản lý gửi:</strong> <?php echo e($supportCodes->implode(', ')); ?>

                                    <div class="small mt-1">Hãy nói rõ với khách đây là mã miễn/giảm chi phí đi kèm phương án của phòng này.</div>
                                <?php else: ?>
                                    <strong>Chưa có mã hỗ trợ.</strong>
                                    <div class="small mt-1">Nếu phương án cần miễn hoặc giảm chi phí, hãy nhắc quản lý bổ sung trước khi khách xác nhận.</div>
                                <?php endif; ?>
                            </div>

                            <?php if($isWaiting): ?>
                                <?php if($issue->guest_selected_resolution_type): ?>
                                    <div class="alert alert-light border py-2 small">
                                        Lựa chọn lần trước đang được đánh dấu sẵn; khách vẫn có thể chuyển sang phương án còn lại.
                                    </div>
                                <?php endif; ?>

                                <?php if(in_array($proposedType, ['same_category', 'upgrade_category'], true) && $issue->proposedRoom): ?>
                                    <label class="option-row">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="items[<?php echo e($issue->id); ?>][choice]"
                                            value="<?php echo e($proposedType); ?>"
                                            <?php if($selectedChoice === $proposedType): echo 'checked'; endif; ?>
                                            required
                                        >
                                        <span class="fw-bold d-block">
                                            <?php echo e($labels[$proposedType]); ?> sang phòng <?php echo e($issue->proposedRoom->room_number); ?>

                                        </span>
                                        <span class="small text-muted">
                                            <?php echo e($issue->proposedRoom->category?->name ?? '---'); ?>.
                                            <?php if($proposedType === 'upgrade_category'): ?>
                                                Khách sạn chịu phần chênh lệch do sự cố.
                                            <?php endif; ?>
                                        </span>
                                    </label>

                                    <label class="option-row">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="items[<?php echo e($issue->id); ?>][choice]"
                                            value="repair_only"
                                            <?php if($selectedChoice === 'repair_only'): echo 'checked'; endif; ?>
                                            required
                                        >
                                        <span class="fw-bold d-block">Giữ nguyên phòng và sửa gấp</span>
                                        <span class="small text-muted">
                                            Khách tiếp tục ở phòng hiện tại; buồng phòng nhận việc khẩn riêng cho phòng này.
                                        </span>
                                    </label>
                                <?php else: ?>
                                    <label class="option-row">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="items[<?php echo e($issue->id); ?>][choice]"
                                            value="repair_only"
                                            checked
                                            required
                                        >
                                        <span class="fw-bold d-block">Giữ nguyên phòng và sửa gấp</span>
                                        <span class="small text-muted">
                                        </span>
                                    </label>
                                <?php endif; ?>
                            <?php elseif($issue->guest_selected_resolution_type): ?>
                                <div class="choice-summary">
                                    <div class="small text-muted">Khách đã chọn</div>
                                    <div class="fw-bold text-success">
                                        <?php echo e($labels[$issue->guest_selected_resolution_type] ?? $issue->guest_selected_resolution_type); ?>

                                        <?php if($issue->guest_selected_resolution_type !== 'repair_only' && $issue->proposedRoom): ?>
                                            → phòng <?php echo e($issue->proposedRoom->room_number); ?>

                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    Phương án đang được quản lý điều chỉnh lại.
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div class="col-xl-4">
                    <?php if($hasHeldRoom): ?>
                        <div class="settings-section mb-3">
                            <h5 class="fw-bold">Thời hạn giữ phòng</h5>
                            <div class="fs-5 fw-bold text-danger">
                                <?php echo e($groupHoldExpiresAt?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') ?? '---'); ?>

                            </div>
                            <div class="small text-muted mt-1">
                                Phòng thay thế đã được giữ ngay từ lúc khách báo sự cố. Mỗi lần quản lý gửi lại phương án, thời hạn giữ được làm mới thêm <?php echo e($roomIssueHoldMinutes); ?> phút.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if($isWaiting): ?>
                        <div class="settings-section">
                            <h5 class="fw-bold mb-2">Xác nhận với khách</h5>
                            <p class="small text-muted">
                                Chọn riêng phương án của từng phòng theo ý khách rồi gửi cho quản lý xác nhận cuối.
                            </p>

                            <label class="form-label fw-semibold">Ghi chú thêm của khách</label>
                            <textarea
                                name="response_note"
                                class="form-control"
                                rows="4"
                                placeholder="Chỉ nhập khi có yêu cầu hoặc lưu ý thêm..."
                            ><?php echo e(old('response_note')); ?></textarea>

                            <button class="btn btn-primary w-100 mt-3" name="response" value="accepted">
                                <i class="bx bx-check me-1"></i>
                                Ghi nhận các lựa chọn của khách
                            </button>

                            <div class="small text-muted mt-2">
                                hoặc giữ nguyên phòng để sửa gấp.
                            </div>
                        </div>
                    <?php elseif($leader->workflow_status === 'guest_accepted'): ?>
                        <div class="settings-section">
                            <div class="alert alert-success mb-0">
                                Đã ghi nhận lựa chọn của khách. Đang chờ quản lý xác nhận và thực hiện.
                            </div>
                        </div>
                    <?php elseif($leader->workflow_status === 'guest_requested_change'): ?>
                        <div class="settings-section">
                            <div class="alert alert-warning mb-0">
                                Đã gửi yêu cầu điều chỉnh cho quản lý.
                            </div>
                        </div>
                    <?php elseif(in_array($leader->workflow_status, ['approved', 'completed'], true)): ?>
                        <div class="settings-section">
                            <div class="alert alert-success mb-0">
                                Phương án đã được quản lý xác nhận thực hiện.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </main>
<script>
window.addEventListener('booking:updated', function (event) {
    const detail = event.detail || {};
    if (Number(detail.id || detail.booking_id || 0) !== <?php echo e((int) $booking->id); ?>) return;
    if (detail.action !== 'room_issue_proposal_sent') return;
    const banner = document.getElementById('roomIssueReceptionLiveUpdate');
    if (banner) banner.classList.remove('d-none');
    document.querySelectorAll('form input, form textarea, form button').forEach(function (element) {
        element.disabled = true;
    });
});
</script>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/bookings/room-issue-proposal.blade.php ENDPATH**/ ?>