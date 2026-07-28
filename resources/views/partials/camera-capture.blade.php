@once
<div class="modal fade" id="sharedCameraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Chụp ảnh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-4x3 bg-dark rounded overflow-hidden">
                    <video id="sharedCameraVideo" autoplay playsinline muted style="object-fit:cover"></video>
                </div>
                <canvas id="sharedCameraCanvas" class="d-none"></canvas>
                <div id="sharedCameraStatus" class="small text-muted mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="sharedCameraSwitch">Đổi camera</button>
                <button type="button" class="btn btn-primary" id="sharedCameraTake" disabled>Chụp ảnh</button>
            </div>
        </div>
    </div>
</div>
<script>
(() => {
    'use strict';

    let stream = null;
    let targetInput = null;
    let facingMode = 'environment';
    let modal = null;

    const el = id => document.getElementById(id);
    const video = () => el('sharedCameraVideo');
    const canvas = () => el('sharedCameraCanvas');
    const status = () => el('sharedCameraStatus');
    const takeButton = () => el('sharedCameraTake');

    function setStatus(message = '') {
        if (status()) status().textContent = message;
    }

    function stopCamera() {
        if (stream) stream.getTracks().forEach(track => track.stop());
        stream = null;
        if (video()) video().srcObject = null;
        if (takeButton()) takeButton().disabled = true;
    }

    async function startCamera() {
        stopCamera();
        setStatus('Đang mở camera...');

        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('Trình duyệt không hỗ trợ camera.');
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: facingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });

            const cameraVideo = video();
            cameraVideo.srcObject = stream;
            await cameraVideo.play();

            if (!cameraVideo.videoWidth) {
                await new Promise(resolve => cameraVideo.addEventListener('loadedmetadata', resolve, { once: true }));
            }

            if (takeButton()) takeButton().disabled = false;
            setStatus('Camera đã sẵn sàng.');
        } catch (error) {
            console.error('Camera error:', error);
            setStatus('Không mở được camera. Hãy cấp quyền camera cho trình duyệt.');
        }
    }

    function appendCapturedFile(file) {
        if (!targetInput) return;
        const dt = new DataTransfer();
        if (targetInput.multiple) {
            [...targetInput.files].forEach(existing => dt.items.add(existing));
        }
        dt.items.add(file);
        targetInput.files = dt.files;
        targetInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    async function takePhoto() {
        const cameraVideo = video();
        if (!stream || !targetInput || !cameraVideo?.videoWidth || !cameraVideo?.videoHeight) {
            setStatus('Camera chưa sẵn sàng.');
            return;
        }

        const c = canvas();
        c.width = cameraVideo.videoWidth;
        c.height = cameraVideo.videoHeight;
        c.getContext('2d').drawImage(cameraVideo, 0, 0, c.width, c.height);

        const blob = await new Promise(resolve => c.toBlob(resolve, 'image/jpeg', 0.85));
        if (!blob) {
            setStatus('Không tạo được ảnh.');
            return;
        }

        appendCapturedFile(new File([blob], `camera-${Date.now()}.jpg`, {
            type: 'image/jpeg',
            lastModified: Date.now()
        }));

        modal?.hide();
    }

    document.addEventListener('click', async event => {
        const openButton = event.target.closest('.js-open-camera');
        if (openButton) {
            event.preventDefault();
            targetInput = document.querySelector(openButton.dataset.targetInput || '');
            if (!targetInput) return;

            const modalElement = el('sharedCameraModal');
            if (!modalElement || typeof bootstrap === 'undefined') return;

            modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modal.show();
            await startCamera();
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

    const bindModal = () => {
        el('sharedCameraModal')?.addEventListener('hidden.bs.modal', stopCamera);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindModal, { once: true });
    } else {
        bindModal();
    }

    window.addEventListener('pagehide', stopCamera);
})();
</script>
@endonce
