<?php $__env->startSection('title', 'Báo sự cố phòng'); ?>
<?php $__env->startSection('heading', 'Báo sự cố phòng'); ?>
<?php $__env->startSection('subheading', 'Chọn phòng gặp sự cố, nhập nội dung và tải ảnh minh chứng cho từng phòng.'); ?>

<?php $__env->startSection('content'); ?>
    <style>
        .booking-summary{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px}.room-list{display:grid;gap:12px;margin:14px 0}.room-choice{border:1px solid #dbe4ef;border-radius:14px;background:#fff;overflow:hidden}.room-choice.blocked{background:#f8fafc;opacity:.75}.room-selector{display:flex;align-items:center;gap:11px;padding:14px 15px;font-weight:800;cursor:pointer}.room-selector input{width:19px;height:19px}.room-detail{display:none;border-top:1px solid #e5e7eb;padding:15px;background:#f8fafc}.room-detail.active{display:block}.room-detail textarea{width:100%;min-height:110px;border:1px solid #cbd7e6;border-radius:12px;padding:12px;font:inherit;resize:vertical}.room-detail input[type=file]{width:100%;border:1px solid #cbd7e6;border-radius:12px;padding:10px;background:#fff}.select-all{display:flex;align-items:center;gap:9px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:12px 14px;font-weight:800;color:#1e3a8a}.select-all input{width:18px;height:18px}.help{font-size:13px;color:#64748b;line-height:1.55;margin-top:6px}.submit-count{display:inline-flex;min-width:23px;height:23px;border-radius:999px;align-items:center;justify-content:center;background:rgba(255,255,255,.2);margin-left:6px}.camera-actions{display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin-top:9px}.camera-note{font-size:13px;color:#64748b}.camera-overlay{position:fixed;inset:0;z-index:9999;background:rgba(5,15,30,.78);display:none;align-items:center;justify-content:center;padding:18px}.camera-overlay.active{display:flex}.camera-panel{width:min(760px,100%);background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 22px 70px rgba(0,0,0,.35)}.camera-head{display:flex;justify-content:space-between;gap:15px;padding:16px 18px;border-bottom:1px solid #e3eaf3}.camera-head strong{font-size:18px}.camera-close{border:0;background:#edf2f8;width:36px;height:36px;border-radius:10px;font-size:22px;cursor:pointer}.camera-body{padding:16px}.camera-video-wrap{aspect-ratio:4/3;background:#07111f;border-radius:14px;overflow:hidden}.camera-video-wrap video{width:100%;height:100%;object-fit:cover}.camera-status{font-size:13px;color:#64748b;margin-top:9px;line-height:1.5}.camera-footer{display:flex;justify-content:flex-end;gap:9px;padding:0 16px 16px}.btn[disabled]{opacity:.55;cursor:not-allowed}@media(max-width:640px){.booking-summary{grid-template-columns:1fr}.camera-footer .btn{flex:1}}
    </style>

    <div class="booking-summary">
        <div class="info"><small>Mã booking</small><strong><?php echo e($booking->booking_code); ?></strong></div>
        <div class="info"><small>Khách đặt</small><strong><?php echo e($booking->booked_customer_name ?: '---'); ?></strong></div>
    </div>

    <?php if(!$canSubmitAnyRoom): ?>
        <div class="warning">
            Tất cả phòng trong booking hiện đang có yêu cầu sự cố chưa hoàn tất. Quản lý hoặc buồng phòng cần xử lý xong trước khi gửi yêu cầu mới cho cùng phòng.
        </div>
    <?php else: ?>
        <form method="POST" action="<?php echo e(request()->fullUrl()); ?>" enctype="multipart/form-data" id="guestRoomIssueForm">
            <?php echo csrf_field(); ?>

            <label class="select-all">
                <input type="checkbox" id="selectAllIssueRooms">
                Chọn tất cả phòng có thể báo sự cố
            </label>

            <div class="room-list">
                <?php $__currentLoopData = $booking->bookingRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bookingRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(!$bookingRoom->room) continue; ?>
                    <?php
                        $roomId = (int) $bookingRoom->room_id;
                        $blocked = $activeIssueRoomIds->contains($roomId);
                        $selected = in_array($roomId, array_map('intval', old('selected_room_ids', [])), true);
                    ?>
                    <div class="room-choice <?php echo e($blocked ? 'blocked' : ''); ?>" data-room-card="<?php echo e($roomId); ?>">
                        <label class="room-selector">
                            <input type="checkbox"
                                   class="js-room-selector"
                                   name="selected_room_ids[]"
                                   value="<?php echo e($roomId); ?>"
                                   data-room-id="<?php echo e($roomId); ?>"
                                   <?php if($selected && !$blocked): echo 'checked'; endif; ?>
                                   <?php if($blocked): echo 'disabled'; endif; ?>>
                            <span>
                                Phòng <?php echo e($bookingRoom->room->room_number); ?>

                                <span style="font-weight:500;color:#64748b;">· <?php echo e($bookingRoom->room->category?->name ?? $booking->roomCategory?->name); ?></span>
                                <?php if($blocked): ?>
                                    <span style="display:block;color:#b45309;font-size:12px;margin-top:3px;">Đang có yêu cầu chưa hoàn tất</span>
                                <?php endif; ?>
                            </span>
                        </label>

                        <?php if(!$blocked): ?>
                            <div class="room-detail" id="roomIssueDetail<?php echo e($roomId); ?>">
                                <div class="field">
                                    <label>Sự cố của phòng <?php echo e($bookingRoom->room->room_number); ?></label>
                                    <textarea name="issues[<?php echo e($roomId); ?>][description]"
                                              minlength="10" maxlength="2000"
                                              placeholder="Mô tả rõ sự cố riêng của phòng <?php echo e($bookingRoom->room->room_number); ?>..."
                                              disabled><?php echo e(old("issues.$roomId.description")); ?></textarea>
                                    <div class="help">Nhập tối thiểu 10 ký tự. Mỗi phòng phải có nội dung riêng.</div>
                                </div>
                                <div class="field">
                                    <label>Ảnh minh chứng của phòng <?php echo e($bookingRoom->room->room_number); ?> <span style="font-weight:500;color:#64748b;">(tối đa 5 ảnh)</span></label>
                                    <input type="file"
                                           id="guestRoomIssueImages<?php echo e($roomId); ?>"
                                           name="issues[<?php echo e($roomId); ?>][images][]"
                                           accept="image/jpeg,image/png,image/webp"
                                           multiple
                                           data-persistent-files
                                           disabled>
                                    <div class="camera-actions">
                                        <button type="button"
                                                class="btn btn-light js-guest-open-camera"
                                                data-target-input="#guestRoomIssueImages<?php echo e($roomId); ?>"
                                                disabled>📷 Chụp bằng camera</button>
                                        <span class="camera-note">Có thể chọn ảnh có sẵn hoặc chụp trực tiếp.</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>


            <div class="actions">
                <button type="submit" class="btn btn-danger" id="submitRoomIssues" disabled>
                    Xác nhận gửi quản lý <span class="submit-count" id="selectedRoomCount">0</span>
                </button>
            </div>
        </form>
    <?php endif; ?>

    <div class="camera-overlay" id="guestIssueCameraOverlay" aria-hidden="true">
        <div class="camera-panel" role="dialog" aria-modal="true" aria-labelledby="guestIssueCameraTitle">
            <div class="camera-head">
                <div>
                    <strong id="guestIssueCameraTitle">Chụp ảnh sự cố</strong>
                    <div class="camera-note">Cho phép trình duyệt sử dụng camera. Trên host cần chạy HTTPS.</div>
                </div>
                <button type="button" class="camera-close" id="guestIssueCameraClose" aria-label="Đóng">×</button>
            </div>
            <div class="camera-body">
                <div class="camera-video-wrap">
                    <video id="guestIssueCameraVideo" autoplay playsinline muted></video>
                </div>
                <canvas id="guestIssueCameraCanvas" hidden></canvas>
                <div class="camera-status" id="guestIssueCameraStatus">Đang khởi động camera...</div>
            </div>
            <div class="camera-footer">
                <button type="button" class="btn btn-light" id="guestIssueCameraSwitch">Đổi camera</button>
                <button type="button" class="btn btn-primary" id="guestIssueCameraTake">Chụp ảnh</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const selectors = Array.from(document.querySelectorAll('.js-room-selector:not(:disabled)'));
            const selectAll = document.getElementById('selectAllIssueRooms');
            const submit = document.getElementById('submitRoomIssues');
            const count = document.getElementById('selectedRoomCount');

            const syncRoom = (checkbox) => {
                const detail = document.getElementById('roomIssueDetail' + checkbox.dataset.roomId);
                if (!detail) return;
                detail.classList.toggle('active', checkbox.checked);
                detail.querySelectorAll('textarea,input[type="file"]').forEach((field) => {
                    field.disabled = !checkbox.checked;
                    if (field.tagName === 'TEXTAREA') field.required = checkbox.checked;
                });
                detail.querySelectorAll('.js-guest-open-camera').forEach((button) => {
                    button.disabled = !checkbox.checked;
                });
            };

            const syncAll = () => {
                selectors.forEach(syncRoom);
                const selected = selectors.filter((item) => item.checked).length;
                if (count) count.textContent = selected;
                if (submit) submit.disabled = selected === 0;
                if (selectAll) {
                    selectAll.checked = selectors.length > 0 && selected === selectors.length;
                    selectAll.indeterminate = selected > 0 && selected < selectors.length;
                }
            };

            selectors.forEach((checkbox) => checkbox.addEventListener('change', syncAll));
            selectAll?.addEventListener('change', () => {
                selectors.forEach((checkbox) => checkbox.checked = selectAll.checked);
                syncAll();
            });

            const cameraOverlay = document.getElementById('guestIssueCameraOverlay');
            const cameraVideo = document.getElementById('guestIssueCameraVideo');
            const cameraCanvas = document.getElementById('guestIssueCameraCanvas');
            const cameraStatus = document.getElementById('guestIssueCameraStatus');
            let cameraStream = null;
            let cameraTargetInput = null;
            let facingMode = 'environment';

            const stopCamera = () => {
                if (cameraStream) cameraStream.getTracks().forEach((track) => track.stop());
                cameraStream = null;
                if (cameraVideo) cameraVideo.srcObject = null;
            };

            const closeCamera = () => {
                stopCamera();
                cameraOverlay?.classList.remove('active');
                cameraOverlay?.setAttribute('aria-hidden', 'true');
            };

            const startCamera = async () => {
                stopCamera();
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    cameraStatus.textContent = 'Trình duyệt không hỗ trợ camera trực tiếp. Hãy chọn ảnh từ thiết bị.';
                    return;
                }
                try {
                    cameraStatus.textContent = 'Đang khởi động camera...';
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: facingMode }, width: { ideal: 1920 }, height: { ideal: 1080 } },
                        audio: false
                    });
                    cameraVideo.srcObject = cameraStream;
                    cameraStatus.textContent = 'Camera đã sẵn sàng. Căn đúng khu vực cần chụp rồi bấm Chụp ảnh.';
                } catch (error) {
                    cameraStatus.textContent = location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1'
                        ? 'Camera trên host chỉ hoạt động qua HTTPS. Hãy bật SSL hoặc chọn ảnh từ thiết bị.'
                        : 'Không mở được camera. Hãy cấp quyền camera cho trình duyệt hoặc chọn ảnh từ thiết bị.';
                }
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.js-guest-open-camera');
                if (!button || button.disabled) return;
                cameraTargetInput = document.querySelector(button.dataset.targetInput || '');
                if (!cameraTargetInput || cameraTargetInput.disabled) return;
                cameraOverlay?.classList.add('active');
                cameraOverlay?.setAttribute('aria-hidden', 'false');
                startCamera();
            });

            document.getElementById('guestIssueCameraClose')?.addEventListener('click', closeCamera);
            cameraOverlay?.addEventListener('click', (event) => {
                if (event.target === cameraOverlay) closeCamera();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && cameraOverlay?.classList.contains('active')) closeCamera();
            });
            document.getElementById('guestIssueCameraSwitch')?.addEventListener('click', async () => {
                facingMode = facingMode === 'environment' ? 'user' : 'environment';
                await startCamera();
            });
            document.getElementById('guestIssueCameraTake')?.addEventListener('click', () => {
                if (!cameraStream || !cameraTargetInput || !cameraVideo.videoWidth) return;
                if (cameraTargetInput.files && cameraTargetInput.files.length >= 5) {
                    cameraStatus.textContent = 'Mỗi phòng chỉ được tải tối đa 5 ảnh.';
                    return;
                }
                cameraCanvas.width = cameraVideo.videoWidth;
                cameraCanvas.height = cameraVideo.videoHeight;
                cameraCanvas.getContext('2d').drawImage(cameraVideo, 0, 0, cameraCanvas.width, cameraCanvas.height);
                cameraCanvas.toBlob((blob) => {
                    if (!blob) return;
                    const file = new File([blob], 'camera-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                    const files = new DataTransfer();
                    Array.from(cameraTargetInput.files || []).forEach((existing) => files.items.add(existing));
                    files.items.add(file);
                    cameraTargetInput.files = files.files;
                    cameraTargetInput.dispatchEvent(new Event('change', { bubbles: true }));
                    closeCamera();
                }, 'image/jpeg', 0.92);
            });

            syncAll();
        })();
    </script>

<script src="<?php echo e(asset('assets/js/persistent-file-inputs.js')); ?>?v=<?php echo e(filemtime(public_path('assets/js/persistent-file-inputs.js'))); ?>"></script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('guest-bookings.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/guest-room-issues/form.blade.php ENDPATH**/ ?>