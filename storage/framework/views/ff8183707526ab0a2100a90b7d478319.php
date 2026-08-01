<?php $__env->startSection('title', 'Chi tiết sự cố phòng'); ?>
<?php $__env->startSection('content'); ?>
<?php
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
?>
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
    <p class="admin-breadcrumb mb-3"><a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / <a href="<?php echo e(route('admin.room-issues.index')); ?>">Sự cố phòng</a> / Chi tiết</p>
    <div class="admin-page-head d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div><h2>Sự cố phòng <?php echo e($issue->currentRoom?->room_number); ?></h2><p>Booking <?php echo e($issue->booking?->booking_code); ?> · gửi <?php echo e($issue->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?></p></div>
        <a href="<?php echo e(route('admin.room-issues.index')); ?>" class="btn btn-outline-secondary">Quay lại</a>
    </div>
    <?php if(session('success')): ?><div class="alert alert-success"><?php echo e(session('success')); ?></div><?php endif; ?>
    <?php if(session('error')): ?><div class="alert alert-danger"><?php echo e(session('error')); ?></div><?php endif; ?>
    <?php if($errors->any()): ?><div class="alert alert-danger"><ul class="mb-0"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div><?php endif; ?>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="settings-section mb-3">
                <div class="d-flex justify-content-between gap-3 mb-3"><div><h5 class="fw-bold mb-1">Thông tin khách báo</h5><div class="text-muted small">Khách: <?php echo e($issue->booking?->booked_customer_name); ?></div></div><span class="badge text-bg-warning"><?php echo e($statusLabels[$issue->status] ?? $issue->status); ?></span></div>
                <div class="border rounded p-3 bg-light mb-3"><?php echo e($issue->issue_description); ?></div>
                <div class="d-flex flex-wrap gap-2">
                    <?php $__empty_1 = true; $__currentLoopData = $issue->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <a class="js-image-lightbox" href="<?php echo e(route('admin.room-issue-attachments.show',$attachment)); ?>" target="_blank">
                            <img src="<?php echo e(route('admin.room-issue-attachments.show',$attachment)); ?>" alt="Ảnh sự cố" style="width:150px;height:110px;object-fit:cover;border-radius:10px;border:1px solid #dbe2ea">
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <span class="text-muted">Khách không gửi ảnh.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="settings-section">
                <h5 class="fw-bold mb-3">Phương án xử lý</h5>
                <div class="alert <?php echo e($proposal['type']==='same_category'?'alert-success':($proposal['type']==='upgrade_category'?'alert-primary':'alert-warning')); ?>">
                    <div class="fw-bold fs-5"><?php echo e($proposal['label']); ?></div>
                    <div><?php echo e($proposal['description'] ?? ''); ?></div>
                    <?php if($proposal['room']): ?>
                        <div class="mt-2"><strong>Phòng tự chọn:</strong> <?php echo e($proposal['room']->room_number); ?> · <?php echo e($proposal['room']->category?->name); ?></div>
                    <?php endif; ?>
                </div>

                <?php if($issue->status==='pending'): ?>
                    <form method="POST" action="<?php echo e(route('admin.room-issues.approve',$issue)); ?>" onsubmit="return confirm('Xác nhận duyệt phương án xử lý?')">
                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <label class="form-label fw-semibold mb-2">Mã bù đắp cho khách <span class="text-muted fw-normal">(không bắt buộc)</span></label>

                        <?php if($promotions->isNotEmpty()): ?>
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
                                    <?php $__currentLoopData = $promotionTypeConfig; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promotionType => $config): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $groupPromotions = $promotionGroups->get($promotionType, collect());
                                        ?>
                                        <?php if($groupPromotions->isNotEmpty()): ?>
                                            <section class="issue-promotion-group">
                                                <div class="issue-promotion-group-title">
                                                    <i class="bx <?php echo e($config['icon']); ?>"></i>
                                                    <span><?php echo e($config['label']); ?></span>
                                                    <span class="badge <?php echo e($config['badge']); ?>"><?php echo e($groupPromotions->count()); ?></span>
                                                </div>

                                                <?php $__currentLoopData = $groupPromotions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $promotion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php
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
                                                    ?>

                                                    <label class="issue-promotion-row" data-promotion-row>
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            name="promotion_codes[]"
                                                            value="<?php echo e($promotion->code); ?>"
                                                            <?php if(in_array($promotion->code, $selectedCodes, true)): echo 'checked'; endif; ?>
                                                            data-promotion-checkbox
                                                        >
                                                        <span class="issue-promotion-code"><?php echo e($promotion->code); ?></span>
                                                        <span class="issue-promotion-name"><?php echo e($promotion->name); ?></span>
                                                        <span class="issue-promotion-value"><?php echo e($benefits->filter()->implode(' · ') ?: 'Quyền lợi hỗ trợ'); ?></span>
                                                    </label>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </section>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border mb-0">Booking này hiện không có mã nào đủ điều kiện sử dụng.</div>
                        <?php endif; ?>

                        <label class="form-label fw-semibold mt-3">Ghi chú xử lý và nội dung báo khách</label>
                        <textarea name="admin_note" class="form-control" rows="4" maxlength="2000" required placeholder="Ví dụ: xác nhận lỗi điều hòa; đã đổi phòng miễn phí và tặng mã hỗ trợ..."></textarea>
                        <button class="btn btn-primary w-100 mt-3"><i class="bx bx-check-shield me-1"></i>Xác nhận phê duyệt</button>
                    </form>
                <?php else: ?>
                    <div class="row g-2">
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="text-muted small">Kết quả</span><div class="fw-bold"><?php echo e(['same_category'=>'Đổi phòng cùng hạng','upgrade_category'=>'Đổi hạng miễn phí','no_room'=>'Giữ phòng - sửa gấp'][$issue->resolution_type] ?? '---'); ?></div></div></div>
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="text-muted small">Phòng mới</span><div class="fw-bold"><?php echo e($issue->approvedRoom?->room_number ?? 'Không có'); ?></div></div></div>
                        <div class="col-md-4"><div class="border rounded p-3 h-100"><span class="text-muted small">Mã bù đắp</span><div class="fw-bold"><?php echo e(collect($issue->promotion_codes)->implode(', ') ?: 'Không áp dụng'); ?></div></div></div>
                    </div>
                    <div class="border rounded p-3 mt-3 bg-light"><?php echo e($issue->admin_note); ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="settings-section mb-3">
                <h5 class="fw-bold mb-3">Booking / phòng</h5>
                <div class="d-grid gap-2 small">
                    <div><span class="text-muted d-block">Booking</span><a href="<?php echo e(route('admin.bookings.show',$issue->booking)); ?>" class="fw-bold"><?php echo e($issue->booking?->booking_code); ?></a></div>
                    <div><span class="text-muted d-block">Phòng cũ</span><strong><?php echo e($issue->currentRoom?->room_number); ?> · <?php echo e($issue->currentRoom?->category?->name); ?></strong></div>
                    <div><span class="text-muted d-block">Thời gian còn lại</span><strong>Đến <?php echo e($issue->booking?->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')); ?></strong></div>
                </div>
            </div>
            <?php if($issue->repair_status): ?>
            <div class="settings-section">
                <h5 class="fw-bold mb-3">Khắc phục phòng cũ</h5>
                <span class="badge <?php echo e($issue->repair_status==='completed'?'text-bg-success':'text-bg-warning'); ?>"><?php echo e($issue->repair_status==='completed'?'Đã sửa xong':'Đang khắc phục'); ?></span>
                <?php if($issue->repair_note): ?><div class="mt-3 small"><?php echo e($issue->repair_note); ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
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
<script src="<?php echo e(asset('assets/js/image-lightbox.js')); ?>?v=<?php echo e(filemtime(public_path('assets/js/image-lightbox.js'))); ?>"></script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\room-issues\show.blade.php ENDPATH**/ ?>