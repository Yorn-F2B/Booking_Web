<?php $__env->startSection('content'); ?>
<div class="container py-4" style="max-width:820px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h3 class="mb-2">Báo đến sau giờ G</h3>
            <p class="text-muted">Booking <strong><?php echo e($booking->booking_code); ?></strong> · Giờ G: 18:00 ngày nhận phòng.</p>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger"><?php echo e($errors->first()); ?></div>
            <?php endif; ?>

            <?php if($pendingRequest): ?>
                <div class="alert alert-info py-2">Yêu cầu đang chờ xử lý.</div>
            <?php endif; ?>

            <form id="lateArrivalRequestForm" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Họ tên</label>
                        <input name="customer_name" class="form-control" value="<?php echo e(old('customer_name', $booking->booked_customer_name)); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input name="customer_email" type="email" class="form-control" value="<?php echo e(old('customer_email', $booking->booked_customer_email)); ?>">
                    </div>
                    <?php
                        $oldArrivalDate = old('expected_arrival_date', $pendingRequest?->expected_arrival_at?->format('Y-m-d') ?? \Carbon\Carbon::parse($booking->check_in_date)->format('Y-m-d'));
                        $oldArrivalTime = old('expected_arrival_time', $pendingRequest?->expected_arrival_at?->format('H:i') ?? '18:30');
                    ?>
                    <div class="col-md-6">
                        <label class="form-label">Ngày dự kiến đến <span class="text-danger">*</span></label>
                        <input id="expectedArrivalDate" name="expected_arrival_date" type="date" class="form-control" required
                            value="<?php echo e($oldArrivalDate); ?>" min="<?php echo e(\Carbon\Carbon::parse($booking->check_in_date)->format('Y-m-d')); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Giờ dự kiến đến <span class="text-danger">*</span></label>
                        <input id="expectedArrivalTime" name="expected_arrival_time" type="text" class="form-control" required
                            value="<?php echo e($oldArrivalTime); ?>" data-project-time-picker placeholder="HH:mm">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Lý do đến muộn <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="4" class="form-control" required maxlength="2000"><?php echo e(old('reason', $pendingRequest?->reason)); ?></textarea>
                    </div>
                    <?php if($pendingRequest && $pendingRequest->attachments->isNotEmpty()): ?>
                        <div class="col-12">
                            <label class="form-label">Ảnh/PDF hiện tại</label>
                            <div class="row g-2" id="existingLateArrivalAttachments">
                                <?php $__currentLoopData = $pendingRequest->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attachment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $isImage = str_starts_with((string) $attachment->mime_type, 'image/');
                                        $fileUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('guest-customer-requests.attachment', now()->addMinutes(30), [$booking, $attachment]);
                                    ?>
                                    <div class="col-6 col-md-3 js-existing-attachment" data-attachment-id="<?php echo e($attachment->id); ?>">
                                        <div class="border rounded p-2 h-100 position-relative">
                                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 js-remove-existing-attachment" style="z-index:2" title="Xóa">×</button>
                                            <?php if($isImage): ?>
                                                <a href="<?php echo e($fileUrl); ?>" target="_blank">
                                                    <img src="<?php echo e($fileUrl); ?>" alt="<?php echo e($attachment->original_name); ?>" class="img-fluid rounded w-100" style="height:110px;object-fit:cover">
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo e($fileUrl); ?>" target="_blank" class="d-flex align-items-center justify-content-center text-decoration-none" style="height:110px">
                                                    <span class="fw-semibold">PDF</span>
                                                </a>
                                            <?php endif; ?>
                                            <div class="small text-truncate mt-1" title="<?php echo e($attachment->original_name); ?>"><?php echo e($attachment->original_name); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <div id="removedAttachmentInputs"></div>
                        </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <label class="form-label">Ảnh/PDF minh chứng <span class="text-muted">(không bắt buộc, tối đa 5 tệp)</span></label>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input id="lateArrivalAttachments" name="attachments[]" type="file" data-persistent-files multiple class="form-control" accept="image/*,.pdf">
                            <button type="button" class="btn btn-outline-primary js-open-camera" data-target-input="#lateArrivalAttachments">
                                <i class="bx bx-camera me-1"></i> Chụp bằng camera
                            </button>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary mt-4"><?php echo e($pendingRequest ? 'Cập nhật yêu cầu' : 'Gửi yêu cầu'); ?></button>
            </form>
        </div>
    </div>
</div>
<?php echo $__env->make('partials.camera-capture', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<script src="<?php echo e(asset('assets/js/persistent-file-inputs.js')); ?>?v=<?php echo e(filemtime(public_path('assets/js/persistent-file-inputs.js'))); ?>"></script>

<script>
document.addEventListener('click', function (event) {
    const button = event.target.closest('.js-remove-existing-attachment');
    if (!button) return;

    const item = button.closest('.js-existing-attachment');
    const id = item?.dataset.attachmentId;
    if (!id) return;

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'remove_attachment_ids[]';
    input.value = id;
    document.getElementById('removedAttachmentInputs')?.appendChild(input);
    item.remove();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('guest-bookings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\Booking_Web-master\Booking_Web-master\resources\views\guest-customer-requests\form.blade.php ENDPATH**/ ?>