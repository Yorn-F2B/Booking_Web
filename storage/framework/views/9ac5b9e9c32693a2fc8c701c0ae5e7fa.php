<?php if (! $__env->hasRenderedOnce('0f096b0f-af34-4f15-9ecc-fd4c61e14222')): $__env->markAsRenderedOnce('0f096b0f-af34-4f15-9ecc-fd4c61e14222'); ?>
<style>
    .shared-camera-overlay[hidden]{display:none!important}
    .shared-camera-overlay{position:fixed;inset:0;z-index:2147482500;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(3,12,26,.82);backdrop-filter:blur(2px)}
    .shared-camera-panel{width:min(760px,100%);max-height:calc(100vh - 36px);overflow:auto;background:#fff;border-radius:18px;box-shadow:0 28px 80px rgba(0,0,0,.38);color:#10213a}
    .shared-camera-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:16px 18px;border-bottom:1px solid #e5eaf0}
    .shared-camera-head strong{display:block;font-size:18px;line-height:1.3}.shared-camera-head small{display:block;margin-top:3px;color:#667085;line-height:1.45}
    .shared-camera-close{flex:0 0 auto;width:36px;height:36px;border:0;border-radius:10px;background:#eef2f7;color:#334155;font:700 22px/1 Arial,sans-serif;cursor:pointer}
    .shared-camera-body{padding:16px 18px}.shared-camera-video-wrap{aspect-ratio:4/3;background:#07111f;border-radius:14px;overflow:hidden}
    .shared-camera-video-wrap video{display:block;width:100%;height:100%;object-fit:cover}.shared-camera-canvas{display:none}
    .shared-camera-status{min-height:20px;margin-top:9px;color:#667085;font-size:13px;line-height:1.5}
    .shared-camera-footer{display:flex;justify-content:flex-end;gap:9px;flex-wrap:wrap;padding:0 18px 18px}
    .shared-camera-footer button{min-height:40px;border-radius:10px;padding:8px 14px;font:700 14px/1.2 inherit;cursor:pointer}
    .shared-camera-switch{border:1px solid #cbd5e1;background:#fff;color:#334155}.shared-camera-take{border:1px solid #1d5fe9;background:#1d5fe9;color:#fff}.shared-camera-take:disabled{opacity:.5;cursor:not-allowed}
    body.shared-camera-open{overflow:hidden}
    @media(max-width:640px){.shared-camera-overlay{padding:10px}.shared-camera-panel{max-height:calc(100vh - 20px);border-radius:14px}.shared-camera-footer button{flex:1}}
</style>

<div class="shared-camera-overlay" id="sharedCameraOverlay" hidden aria-hidden="true">
    <div class="shared-camera-panel" role="dialog" aria-modal="true" aria-labelledby="sharedCameraTitle">
        <div class="shared-camera-head">
            <div>
                <strong id="sharedCameraTitle">Chụp ảnh</strong>
                <small>Ảnh được lấy trực tiếp từ camera. Nút này không mở thư viện hoặc trình chọn tệp.</small>
            </div>
            <button type="button" class="shared-camera-close" id="sharedCameraClose" aria-label="Đóng camera">×</button>
        </div>
        <div class="shared-camera-body">
            <div class="shared-camera-video-wrap">
                <video id="sharedCameraVideo" autoplay playsinline muted></video>
            </div>
            <canvas id="sharedCameraCanvas" class="shared-camera-canvas"></canvas>
            <div id="sharedCameraStatus" class="shared-camera-status" role="status"></div>
        </div>
        <div class="shared-camera-footer">
            <button type="button" class="shared-camera-switch" id="sharedCameraSwitch">Đổi camera</button>
            <button type="button" class="shared-camera-take" id="sharedCameraTake" disabled>Chụp ảnh</button>
        </div>
    </div>
</div>

<script>
(() => {
    'use strict';

    let stream = null;
    let targetInput = null;
    let facingMode = 'environment';
    let lastTrigger = null;

    const byId = id => document.getElementById(id);
    const overlay = () => byId('sharedCameraOverlay');
    const video = () => byId('sharedCameraVideo');
    const canvas = () => byId('sharedCameraCanvas');
    const status = () => byId('sharedCameraStatus');
    const takeButton = () => byId('sharedCameraTake');

    function setStatus(message = '') {
        if (status()) status().textContent = message;
    }

    function stopCamera() {
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null;
        if (video()) video().srcObject = null;
        if (takeButton()) takeButton().disabled = true;
    }

    function openOverlay() {
        const el = overlay();
        if (!el) return;
        el.hidden = false;
        el.setAttribute('aria-hidden', 'false');
        document.body.classList.add('shared-camera-open');
    }

    function closeOverlay() {
        stopCamera();
        const el = overlay();
        if (el) {
            el.hidden = true;
            el.setAttribute('aria-hidden', 'true');
        }
        document.body.classList.remove('shared-camera-open');
        targetInput = null;
        if (lastTrigger?.isConnected) lastTrigger.focus({ preventScroll: true });
        lastTrigger = null;
    }

    async function startCamera() {
        stopCamera();
        setStatus('Đang mở camera...');

        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            setStatus('Trình duyệt không hỗ trợ camera trực tiếp. Hãy dùng nút “Chọn ảnh/tệp có sẵn” nếu màn hình có cung cấp.');
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: facingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 960 }
                },
                audio: false
            });

            const cameraVideo = video();
            if (!cameraVideo) return;
            cameraVideo.srcObject = stream;
            await cameraVideo.play();

            if (!cameraVideo.videoWidth) {
                await new Promise(resolve => cameraVideo.addEventListener('loadedmetadata', resolve, { once: true }));
            }

            if (takeButton()) takeButton().disabled = false;
            setStatus('Camera đã sẵn sàng.');
        } catch (error) {
            console.error('Camera error:', error);
            const insecure = location.protocol !== 'https:' && !['localhost', '127.0.0.1'].includes(location.hostname);
            setStatus(insecure
                ? 'Camera trực tiếp cần HTTPS (hoặc localhost/127.0.0.1). Hệ thống sẽ không tự mở app File/Thư viện thay thế.'
                : 'Không mở được camera. Hãy cấp quyền Camera cho trình duyệt rồi thử lại.');
        }
    }

    function appendCapturedFile(file) {
        if (!targetInput) return false;
        const dt = new DataTransfer();
        if (targetInput.multiple) {
            Array.from(targetInput.files || []).forEach(existing => dt.items.add(existing));
        }
        dt.items.add(file);
        targetInput.files = dt.files;
        targetInput.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
    }

    async function takePhoto() {
        const cameraVideo = video();
        if (!stream || !targetInput || !cameraVideo || !cameraVideo.videoWidth || !cameraVideo.videoHeight) {
            setStatus('Camera chưa sẵn sàng.');
            return;
        }

        const c = canvas();
        if (!c) return;
        c.width = cameraVideo.videoWidth;
        c.height = cameraVideo.videoHeight;
        c.getContext('2d').drawImage(cameraVideo, 0, 0, c.width, c.height);

        const blob = await new Promise(resolve => c.toBlob(resolve, 'image/jpeg', 0.88));
        if (!blob) {
            setStatus('Không tạo được ảnh. Vui lòng thử lại.');
            return;
        }

        const added = appendCapturedFile(new File([blob], `camera-${Date.now()}.jpg`, {
            type: 'image/jpeg',
            lastModified: Date.now()
        }));

        if (added) closeOverlay();
    }

    document.addEventListener('click', async event => {
        const openButton = event.target.closest('.js-open-camera');
        if (openButton) {
            event.preventDefault();
            const selector = openButton.dataset.targetInput || '';
            let input = null;
            try { input = document.querySelector(selector); } catch (_) { input = null; }
            if (!input || input.type !== 'file') return;

            targetInput = input;
            lastTrigger = openButton;
            openOverlay();
            await startCamera();
            return;
        }

        if (event.target.closest('#sharedCameraClose')) {
            closeOverlay();
            return;
        }

        if (event.target === overlay()) {
            closeOverlay();
            return;
        }

        if (event.target.closest('#sharedCameraSwitch')) {
            facingMode = facingMode === 'environment' ? 'user' : 'environment';
            await startCamera();
            return;
        }

        if (event.target.closest('#sharedCameraTake')) {
            await takePhoto();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && overlay() && !overlay().hidden) closeOverlay();
    });

    window.addEventListener('pagehide', stopCamera);
})();
</script>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/partials/camera-capture.blade.php ENDPATH**/ ?>