<?php $__env->startSection('content'); ?>
<div class="admin-wrapper">
<main class="admin-content">
<div class="container-fluid px-0">
    <div class="admin-page-head d-flex align-items-center justify-content-between">
        <h1 class="mb-0">Chi tiết yêu cầu đến muộn</h1>
        <a class="btn btn-outline-secondary" href="<?php echo e(route('admin.customer-requests.index')); ?>">Quay lại</a>
    </div>
    <div id="lateArrivalUpdateBanner" class="alert alert-warning d-flex align-items-center justify-content-between gap-2 flex-wrap <?php echo e(($hasUnseenUpdate && $customerRequest->status === 'pending') ? '' : 'd-none'); ?>">
        <div><strong>Khách vừa cập nhật yêu cầu.</strong></div>
        <?php if($customerRequest->status === 'pending'): ?>
            <form id="lateArrivalAcknowledgeForm" method="POST" action="<?php echo e(route('admin.customer-requests.acknowledge', $customerRequest)); ?>">
                <?php echo csrf_field(); ?>
                <input id="lateArrivalAcknowledgeVersion" type="hidden" name="version" value="<?php echo e($pageVersion); ?>">
                <button id="lateArrivalAcknowledgeButton" class="btn btn-warning" type="submit">Cập nhật dữ liệu mới nhất</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <p>Booking: <a href="<?php echo e(route('admin.bookings.show',$customerRequest->booking)); ?>"><strong><?php echo e($customerRequest->booking?->booking_code); ?></strong></a></p>
                    <dl class="row">
                        <dt class="col-sm-3">Khách</dt><dd class="col-sm-9"><?php echo e($customerRequest->customer_name); ?> · <?php echo e($customerRequest->customer_email); ?></dd>
                        <dt class="col-sm-3">Nguồn gửi</dt><dd class="col-sm-9"><?php echo e($customerRequest->source==='customer_web' ? 'Website khách hàng' : 'Biểu mẫu email khách vãng lai'); ?></dd>
                        <dt class="col-sm-3">Giờ dự kiến đến</dt><dd class="col-sm-9"><?php echo e(optional($customerRequest->expected_arrival_at)->format('d/m/Y H:i')); ?></dd>
                        <dt class="col-sm-3">Lý do</dt><dd class="col-sm-9" style="white-space:pre-wrap"><?php echo e($customerRequest->reason); ?></dd>
                    </dl>

                    <h5>Tệp minh chứng</h5>
                    <div class="d-flex flex-wrap gap-3">
                    <?php $__empty_1 = true; $__currentLoopData = $customerRequest->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $attachmentUrl = route('admin.customer-requests.attachment', [$customerRequest, $attachment->id]);
                            $isImage = str_starts_with((string) $attachment->mime_type, 'image/');
                        ?>
                        <?php if($isImage): ?>
                            <a class="js-image-lightbox text-decoration-none" href="<?php echo e($attachmentUrl); ?>" target="_blank" title="Bấm để xem ảnh lớn">
                                <div class="border rounded-3 p-2 bg-white" style="width:170px">
                                    <img src="<?php echo e($attachmentUrl); ?>" alt="<?php echo e($attachment->original_name); ?>"
                                         style="width:100%;height:115px;object-fit:cover;border-radius:8px;cursor:zoom-in">
                                    <div class="small text-truncate mt-2" title="<?php echo e($attachment->original_name); ?>"><?php echo e($attachment->original_name); ?></div>
                                </div>
                            </a>
                        <?php else: ?>
                            <a class="text-decoration-none" target="_blank" href="<?php echo e($attachmentUrl); ?>">
                                <div class="border rounded-3 p-3 bg-light" style="width:170px;min-height:150px;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center">
                                    <strong>PDF / Tệp</strong>
                                    <span class="small text-break mt-2"><?php echo e($attachment->original_name); ?></span>
                                </div>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-muted">Không có tệp đính kèm.</p>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h5>Ghi chú của lễ tân</h5>
                    <form method="POST" action="<?php echo e(route('admin.customer-requests.receptionist-note',$customerRequest)); ?>">
                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                        <textarea name="receptionist_note" class="form-control mb-2" rows="4" required><?php echo e($customerRequest->receptionist_note); ?></textarea>
                        <button class="btn btn-outline-primary">Lưu ghi chú</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5>Quản lý duyệt</h5>
                    <p>Trạng thái: <strong><?php echo e($customerRequest->status_label); ?></strong></p>
                    <?php if($customerRequest->status==='pending' && in_array(auth()->user()?->role, ['super_admin','manager'], true)): ?>
                        <form method="POST" action="<?php echo e(route('admin.customer-requests.approve',$customerRequest)); ?>" class="mb-3">
                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <input type="hidden" name="version" value="<?php echo e($pageVersion); ?>">
                            <textarea name="admin_note" class="form-control mb-2" rows="3" placeholder="Ghi chú duyệt"></textarea>
                            <button class="btn btn-success" <?php if($hasUnseenUpdate): echo 'disabled'; endif; ?>>Duyệt và ghi nhận giờ đến</button>
                        </form>
                        <form method="POST" action="<?php echo e(route('admin.customer-requests.reject',$customerRequest)); ?>">
                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <input type="hidden" name="version" value="<?php echo e($pageVersion); ?>">
                            <textarea name="admin_note" class="form-control mb-2" rows="3" required placeholder="Lý do từ chối"></textarea>
                            <button class="btn btn-danger" <?php if($hasUnseenUpdate): echo 'disabled'; endif; ?>>Từ chối</button>
                        </form>
                    <?php else: ?>
                        <p style="white-space:pre-wrap"><?php echo e($customerRequest->admin_note ?: 'Chưa có ghi chú.'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</main>
</div>
<script src="<?php echo e(asset('assets/js/image-lightbox.js')); ?>?v=<?php echo e(filemtime(public_path('assets/js/image-lightbox.js'))); ?>"></script>
<script>
document.body.setAttribute('data-realtime-local-only', 'true');

const LATE_REQUEST_PAGE_VERSION = <?php echo e((int) $pageVersion); ?>;
const lateRequestUpdatesUrl = <?php echo json_encode(route('admin.customer-requests.updates', $customerRequest), 512) ?>;
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
    if (Number(detail.id || detail.booking_id || 0) !== <?php echo e((int) $customerRequest->booking_id); ?>) return;
    if (detail.action === 'late_arrival_request_updated') {
        event.stopImmediatePropagation();
        lockLateRequestActions();
    }
});

setInterval(pollLateRequestUpdates, 10000);
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\admin\pages\customer-requests\show.blade.php ENDPATH**/ ?>