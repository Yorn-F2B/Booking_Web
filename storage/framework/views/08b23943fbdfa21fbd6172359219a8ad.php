<?php if (! $__env->hasRenderedOnce('9728d61f-da68-4d10-b4dc-158b1f6c4dd9')): $__env->markAsRenderedOnce('9728d61f-da68-4d10-b4dc-158b1f6c4dd9'); ?>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script>
(function () {
    const aiScanUrl = <?php echo json_encode(route('cccd.scan'), 15, 512) ?>;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function startGeminiProgress(status) {
        let percent = 4;
        if (status) status.textContent = `Gemini đang đọc toàn bộ mặt CCCD: ${percent}%`;
        const timer = setInterval(() => {
            const step = percent < 35 ? 7 : (percent < 70 ? 4 : 2);
            percent = Math.min(92, percent + step);
            if (status) status.textContent = `Gemini đang đọc toàn bộ mặt CCCD: ${percent}%`;
        }, 420);
        return {
            success() {
                clearInterval(timer);
                if (status) status.textContent = 'Gemini đang hoàn tất dữ liệu: 100%';
            },
            stop() { clearInterval(timer); },
        };
    }
    function normalizeText(value) {
        return (value || '').replace(/\r/g, '').replace(/[ \t]+/g, ' ').trim();
    }

    function setValue(selector, value) {
        if (!selector || value === undefined || value === null || value === '') return;
        const el = document.querySelector(selector);
        if (!el) return;

        // Flatpickr đổi input ngày gốc thành type="hidden" khi dùng altInput,
        // nên không được kiểm tra input[type="date"]. Chỉ cần có _flatpickr.
        if (el._flatpickr && /^\d{4}-\d{2}-\d{2}$/.test(String(value))) {
            el._flatpickr.setDate(String(value), true, 'Y-m-d');
            if (el._flatpickr.altInput && el._flatpickr.selectedDates[0]) {
                el._flatpickr.altInput.value = el._flatpickr.formatDate(
                    el._flatpickr.selectedDates[0],
                    el._flatpickr.config.altFormat || 'd/m/Y'
                );
            }
        } else {
            el.value = value;
        }

        // Báo cho các đoạn validate/đồng bộ khác biết dữ liệu đã được thay đổi.
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
        el.dispatchEvent(new CustomEvent('project-date-change', { bubbles: true, detail: { value: String(value) } }));
    }


    function compactIdentity(value) {
        return String(value || '').replace(/\s+/g, '').toUpperCase();
    }

    function isValidIdentity(value) {
        const compact = compactIdentity(value);
        return /^\d{12}$/.test(compact) || /^[A-Z0-9]{6,20}$/.test(compact);
    }

    function syncCheckInSubmitState() {
        const submitButton = document.getElementById('checkInSubmitButton');
        const cccdInput = document.getElementById('checkInCccd');
        const fullNameInput = document.getElementById('checkInScannedFullName');
        const status = document.getElementById('checkInCccdStatus');

        if (!submitButton || !cccdInput || !fullNameInput) return;

        const policyDisabled = submitButton.dataset.policyDisabled === '1';
        const currentCccd = compactIdentity(cccdInput.value);
        const expectedCccd = compactIdentity(cccdInput.dataset.bookingCccd || '');
        const hasValidData = isValidIdentity(currentCccd) && fullNameInput.value.trim().length >= 2;

        submitButton.disabled = policyDisabled || !hasValidData;

        if (policyDisabled || !status) return;

        if (!hasValidData) {
            status.textContent = 'Nhập họ tên và CCCD 12 số (hoặc hộ chiếu hợp lệ) để mở khóa check-in.';
            status.className = 'small text-muted d-block mb-3';
            return;
        }

        if (expectedCccd && currentCccd !== expectedCccd) {
            status.textContent = 'CCCD thực tế khác CCCD người đứng tên lúc đặt. Lễ tân vẫn có thể check-in; thông tin vừa nhập sẽ được lưu vào khai báo lưu trú của booking này.';
            status.className = 'small text-warning d-block mb-3';
            return;
        }

        status.textContent = 'Thông tin hợp lệ. Có thể xác nhận check-in.';
        status.className = 'small text-success d-block mb-3';
    }

    function parseDate(text) {
        const match = text.match(/\b(0?[1-9]|[12]\d|3[01])[\/\-.](0?[1-9]|1[0-2])[\/\-.]((?:19|20)\d{2})\b/);
        if (!match) return '';
        return `${match[3]}-${String(match[2]).padStart(2, '0')}-${String(match[1]).padStart(2, '0')}`;
    }

    function cleanOcrValue(value) {
        return normalizeText(value)
            .replace(/^[/\\|:;,.\-–—\s]+/, '')
            .replace(/[/\\|:;,.\-–—\s]+$/, '')
            .trim();
    }

    function isLabelOnly(value) {
        const normalized = cleanOcrValue(value).toLowerCase();
        return /^(họ\s*và\s*tên|ho\s*va\s*ten|full\s*name|ngày\s*sinh|date\s*of\s*birth|giới\s*tính|sex|nơi\s*thường\s*trú|place\s*of\s*residence|quê\s*quán|place\s*of\s*origin)$/.test(normalized);
    }

    function looksLikePersonName(value) {
        const clean = cleanOcrValue(value);
        if (!clean || isLabelOnly(clean)) return false;
        if (/\d/.test(clean)) return false;
        if (/căn\s*cước|identity|citizen|việt\s*nam|socialist|republic|độc\s*lập|freedom/i.test(clean)) return false;
        const words = clean.split(/\s+/).filter(Boolean);
        return words.length >= 2 && words.length <= 8 && clean.length >= 5;
    }

    function looksLikeAddress(value) {
        const clean = cleanOcrValue(value);
        if (!clean || isLabelOnly(clean)) return false;
        if (/^(full\s*name|date\s*of\s*birth|sex)$/i.test(clean)) return false;
        return clean.length >= 4;
    }

    function valueAfterLabel(lines, labelRegex, validator) {
        for (let i = 0; i < lines.length; i++) {
            if (!labelRegex.test(lines[i])) continue;
            const sameLine = cleanOcrValue(lines[i].replace(labelRegex, ''));
            if (sameLine && (!validator || validator(sameLine))) return sameLine;
            for (let offset = 1; offset <= 2; offset++) {
                const candidate = cleanOcrValue(lines[i + offset] || '');
                if (candidate && !isLabelOnly(candidate) && (!validator || validator(candidate))) return candidate;
            }
        }
        return '';
    }

    function parseCccd(text) {
        const raw = String(text || '').replace(/\r/g, '');
        const lines = raw.split('\n').map(cleanOcrValue).filter(Boolean);
        const clean = normalizeText(raw);
        const cccd = (clean.match(/\b\d{12}\b/) || [''])[0];

        const birthdayLabel = clean.match(/(?:ngày\s*sinh|date\s*of\s*birth)[^0-9]{0,24}((?:0?[1-9]|[12]\d|3[01])[\/\-.](?:0?[1-9]|1[0-2])[\/\-.](?:19|20)\d{2})/i);
        const birthday = birthdayLabel ? parseDate(birthdayLabel[1]) : '';

        let fullName = valueAfterLabel(
            lines,
            /^.*?(?:họ\s*và\s*tên|ho\s*va\s*ten|full\s*name)\s*[:\-]?\s*/i,
            looksLikePersonName
        );
        if (!fullName) {
            fullName = lines.find(line => looksLikePersonName(line) && /^[A-ZÀ-ỸĐ]/.test(line)) || '';
        }
        fullName = cleanOcrValue(fullName).replace(/[^A-Za-zÀ-ỹĐđ\s]/g, '').replace(/\s+/g, ' ').trim();
        if (!looksLikePersonName(fullName)) fullName = '';

        const parts = fullName.split(' ').filter(Boolean);
        const firstName = parts.length ? parts.pop() : '';
        const lastName = parts.join(' ');

        let gender = '';
        if (/giới\s*tính\s*[:\-]?\s*nam|sex\s*[:\-]?\s*male/i.test(clean)) gender = 'male';
        if (/giới\s*tính\s*[:\-]?\s*nữ|sex\s*[:\-]?\s*female/i.test(clean)) gender = 'female';

        let address = valueAfterLabel(
            lines,
            /^.*?(?:nơi\s*thường\s*trú|place\s*of\s*residence)\s*[:\-]?\s*/i,
            looksLikeAddress
        );
        address = cleanOcrValue(address)
            .replace(/^(?:nơi\s*thường\s*trú|place\s*of\s*residence)\s*[:\-]?\s*/i, '')
            .trim();
        if (!looksLikeAddress(address)) address = '';

        return { cccd, fullName, firstName, lastName, birthday, gender, address };
    }


    const requiredFieldLabels = {
        cccd: 'số CCCD',
        full_name: 'họ tên',
        birthday: 'ngày sinh',
        gender: 'giới tính',
        nationality: 'quốc tịch',
        place_of_origin: 'quê quán',
        address: 'nơi thường trú/địa chỉ',
        expiry_date: 'ngày hết hạn',
    };

    function getRequiredFields(input) {
        return String(input?.dataset?.requiredFields || '')
            .split(',')
            .map(field => field.trim())
            .filter(Boolean);
    }

    function missingRequiredFields(input, data) {
        const source = {
            cccd: data.cccd,
            full_name: data.fullName,
            birthday: data.birthday,
            gender: data.gender,
            nationality: data.nationality,
            place_of_origin: data.placeOfOrigin,
            address: data.address,
            expiry_date: data.expiryDate,
        };
        return getRequiredFields(input).filter(field => !String(source[field] || '').trim());
    }

    function missingFieldText(fields) {
        return fields.map(field => requiredFieldLabels[field] || field).join(', ');
    }

    function parseCccdQr(raw) {
        const parts = String(raw || '').split('|').map(v => v.trim());
        if (parts.length < 6 || !/^\d{9,12}$/.test(parts[0] || '')) return null;
        const fullName = parts[2] || '';
        const dobRaw = parts[3] || '';
        let birthday = '';
        if (/^\d{8}$/.test(dobRaw)) birthday = `${dobRaw.slice(4)}-${dobRaw.slice(2,4)}-${dobRaw.slice(0,2)}`;
        const nameParts = fullName.split(/\s+/).filter(Boolean);
        const firstName = nameParts.pop() || '';
        const lastName = nameParts.join(' ');
        const genderRaw = (parts[4] || '').toLowerCase();
        const gender = /nam|male/.test(genderRaw) ? 'male' : (/nữ|nu|female/.test(genderRaw) ? 'female' : '');
        return { cccd: parts[0], fullName, firstName, lastName, birthday, gender, address: parts[5] || '' };
    }

    function applyData(input, data, sourceLabel) {
        setValue(input.dataset.targetCccd, data.cccd);
        setValue(input.dataset.targetFullName, data.fullName);
        setValue(input.dataset.targetFirstName, data.firstName);
        setValue(input.dataset.targetLastName, data.lastName);
        setValue(input.dataset.targetBirthday, data.birthday);
        setValue(input.dataset.targetGender, data.gender);
        setValue(input.dataset.targetAddress, data.address);
        setValue(input.dataset.targetNationality, data.nationality);
        setValue(input.dataset.targetPlaceOfOrigin, data.placeOfOrigin);
        setValue(input.dataset.targetExpiryDate, data.expiryDate);
        const status = input.dataset.status ? document.querySelector(input.dataset.status) : null;
        syncCheckInSubmitState();

        const checkInCccd = document.getElementById('checkInCccd');
        const expectedCccd = compactIdentity(checkInCccd?.dataset.bookingCccd || '');
        const currentCccd = compactIdentity(checkInCccd?.value || data.cccd);

        if (status && (!expectedCccd || expectedCccd === currentCccd)) {
            status.textContent = `${sourceLabel || 'Đã đọc ảnh'}: ${data.cccd}${data.fullName ? ' - ' + data.fullName : ''}. Vui lòng kiểm tra lại trước khi xác nhận.`;
            status.className = 'small text-success d-block mb-3';
        }

        return true;
    }

    document.addEventListener('cccd-qr-data', async function(event) {
        const input = event.detail?.input;
        const data = parseCccdQr(event.detail?.raw);
        if (!input || !data) return;
        applyData(input, data, 'Đã đọc QR CCCD');
        const key = storageKey(input);
        if (key) {
            let dataUrl = '';
            try { if (input.files?.[0]) dataUrl = await fileToDataUrl(input.files[0]); } catch(e) {}
            sessionStorage.setItem(key, JSON.stringify({source: 'qr', data, dataUrl, name: input.files?.[0]?.name || 'cccd-qr.jpg'}));
        }
    });
    function storageKey(input) { return input.dataset.persistKey ? 'cccd-scan:' + input.dataset.persistKey : ''; }
    function fileToDataUrl(file) { return new Promise((resolve,reject)=>{ const r=new FileReader(); r.onload=()=>resolve(r.result); r.onerror=reject; r.readAsDataURL(file); }); }
    function dataUrlToFile(dataUrl, name='cccd-scan.jpg') { const [meta,b64]=dataUrl.split(','); const mime=(meta.match(/:(.*?);/)||[])[1]||'image/jpeg'; const bytes=atob(b64); const arr=new Uint8Array(bytes.length); for(let i=0;i<bytes.length;i++) arr[i]=bytes.charCodeAt(i); return new File([arr],name,{type:mime}); }
    document.addEventListener('DOMContentLoaded', function(){
        const profileSaved = document.body?.dataset?.profileSaved === '1';
        document.querySelectorAll('.js-cccd-image[data-persist-key]').forEach(input=>{
            if (profileSaved) {
                try { sessionStorage.removeItem(storageKey(input)); } catch(e) {}
                return;
            }
            try { const raw=sessionStorage.getItem(storageKey(input)); if(!raw) return; const saved=JSON.parse(raw); if(saved.dataUrl){ const dt=new DataTransfer(); dt.items.add(dataUrlToFile(saved.dataUrl,saved.name)); input.files=dt.files; } if(saved.data){ const d=saved.data; setValue(input.dataset.targetCccd,d.cccd); if(saved.source === 'qr' || saved.source === 'gemini'){ setValue(input.dataset.targetFullName,d.fullName); setValue(input.dataset.targetFirstName,d.firstName); setValue(input.dataset.targetLastName,d.lastName); setValue(input.dataset.targetBirthday,d.birthday); setValue(input.dataset.targetGender,d.gender); setValue(input.dataset.targetAddress,d.address); setValue(input.dataset.targetNationality,d.nationality); setValue(input.dataset.targetPlaceOfOrigin,d.placeOfOrigin); setValue(input.dataset.targetExpiryDate,d.expiryDate); } const status=input.dataset.status?document.querySelector(input.dataset.status):null; if(status) status.textContent=saved.source === 'qr' ? 'Đã khôi phục thông tin từ QR CCCD.' : (saved.source === 'gemini' ? 'Đã khôi phục thông tin Gemini vừa quét.' : 'Đã khôi phục số CCCD vừa quét; các thông tin khác được giữ nguyên.'); } } catch(e){}
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const cccdInput = document.getElementById('checkInCccd');
        const fullNameInput = document.getElementById('checkInScannedFullName');

        [cccdInput, fullNameInput].forEach(function (field) {
            if (!field) return;
            field.addEventListener('input', syncCheckInSubmitState);
            field.addEventListener('change', syncCheckInSubmitState);
        });

        syncCheckInSubmitState();
    });

    document.addEventListener('change', async function (event) {
        const input = event.target.closest('.js-cccd-image');
        if (!input || !input.files || !input.files[0]) return;
        const scanSide = input.dataset.scanSide || 'ocr';

        const button = input.dataset.button ? document.querySelector(input.dataset.button) : null;
        const status = input.dataset.status ? document.querySelector(input.dataset.status) : null;
        const oldText = button ? button.innerHTML : '';

        try {
            if (button) {
                button.disabled = true;
                button.innerHTML = 'Đang quét...';
            }
            if (status) status.textContent = 'Đang kiểm tra mã QR trên ảnh CCCD...';

            if (scanSide === 'qr') try {
                if (typeof window.jsQR !== 'function') {
                    await new Promise((resolve, reject) => {
                        const existing = document.querySelector('script[data-jsqr-fallback]');
                        if (existing) {
                            if (typeof window.jsQR === 'function') return resolve();
                            existing.addEventListener('load', resolve, { once: true });
                            existing.addEventListener('error', reject, { once: true });
                            return;
                        }
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
                        script.dataset.jsqrFallback = '1';
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                }
                const bitmap = await createImageBitmap(input.files[0]);
                const qrCanvas = document.createElement('canvas');
                qrCanvas.width = bitmap.width;
                qrCanvas.height = bitmap.height;
                const qrCtx = qrCanvas.getContext('2d', { willReadFrequently: true });
                qrCtx.drawImage(bitmap, 0, 0);
                const qrImage = qrCtx.getImageData(0, 0, qrCanvas.width, qrCanvas.height);
                const qrResult = window.jsQR(qrImage.data, qrImage.width, qrImage.height, { inversionAttempts: 'attemptBoth' });
                const qrData = qrResult ? parseCccdQr(qrResult.data) : null;
                if (qrData) {
                    applyData(input, qrData, 'Đã đọc QR CCCD');
                    const key = storageKey(input);
                    if (key) {
                        const dataUrl = await fileToDataUrl(input.files[0]);
                        sessionStorage.setItem(key, JSON.stringify({source: 'qr', data: qrData, dataUrl, name: input.files[0].name}));
                    }
                    return;
                }
            } catch (qrError) {
                console.warn('Không đọc được QR từ ảnh mặt trước.', qrError);
            }

            if (scanSide === 'back') {
                const key = storageKey(input);
                if (key) {
                    const dataUrl = await fileToDataUrl(input.files[0]);
                    sessionStorage.setItem(key, JSON.stringify({source: 'back-image', dataUrl, name: input.files[0].name}));
                }
                if (status) status.textContent = 'Đã nhận ảnh mặt sau.';
                return;
            }

            const geminiProgress = startGeminiProgress(status);

            try {
                const formData = new FormData();
                formData.append('image', input.files[0]);
                formData.append('required_fields', input.dataset.requiredFields || '');

                const aiResponse = await fetch(aiScanUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const aiPayload = await aiResponse.json().catch(() => ({}));
                if (!aiResponse.ok || !aiPayload.ok) {
                    throw new Error(aiPayload.message || 'Gemini chưa nhận diện được ảnh.');
                }

                geminiProgress.success();
                const aiData = aiPayload.data || {};
                const fullName = String(aiData.full_name || '').trim();
                const nameParts = fullName.split(/\s+/).filter(Boolean);
                const firstName = nameParts.length ? nameParts.pop() : '';
                const lastName = nameParts.join(' ');
                const data = {
                    cccd: aiData.cccd || '',
                    fullName,
                    firstName,
                    lastName,
                    birthday: aiData.birthday || '',
                    gender: aiData.gender || '',
                    nationality: aiData.nationality || '',
                    placeOfOrigin: aiData.place_of_origin || '',
                    address: aiData.address || '',
                    expiryDate: aiData.expiry_date || '',
                };

                if (!data.cccd) {
                    throw new Error('Gemini chưa đọc được số CCCD. Hãy chụp rõ đủ bốn góc.');
                }

                const missingFields = missingRequiredFields(input, data);
                const missingNotice = missingFields.length
                    ? `\n\nChưa đọc được: ${missingFieldText(missingFields)}. Các ô này sẽ giữ nguyên để bạn kiểm tra/nhập tay.`
                    : '';

                if (input.dataset.confirmApply === '1' && !confirm(`Gemini đã đọc được CCCD: ${data.cccd}.${data.fullName ? `\nHọ tên: ${data.fullName}` : ''}${data.birthday ? `\nNgày sinh: ${data.birthday}` : ''}${data.gender ? `\nGiới tính: ${data.gender}` : ''}${data.address ? `\nĐịa chỉ: ${data.address}` : ''}${missingNotice}\n\nÁp dụng các thông tin đọc được?`)) {
                    if (status) status.textContent = 'Đã quét nhưng chưa áp dụng. Thông tin hiện tại được giữ nguyên.';
                    input.value = '';
                    return;
                }

                applyData(input, data, 'Gemini đã nhận diện');
                if (missingFields.length && status) {
                    status.textContent = `Gemini đã điền các trường đọc được nhưng còn thiếu: ${missingFieldText(missingFields)}. Vui lòng kiểm tra và nhập tay.`;
                    status.className = 'small text-warning d-block mb-3';
                }
                const key = storageKey(input);
                if (key) {
                    const dataUrl = await fileToDataUrl(input.files[0]);
                    sessionStorage.setItem(key, JSON.stringify({source: 'gemini', data, dataUrl, name: input.files[0].name}));
                }
                return;
            } catch (aiError) {
                geminiProgress.stop();
                const aiMessage = String(aiError?.message || 'Gemini tạm lỗi');
                const isAuthFailure = /HTTP\s*401|không xác thực|authentication credentials|unauthenticated|access_token_type_unsupported|api key/i.test(aiMessage);

                // Không âm thầm rơi xuống OCR khi Gemini lỗi xác thực. OCR cục bộ có thể
                // đọc sai họ tên/ngày sinh nhưng lại trông như một lần quét thành công.
                if (isAuthFailure) {
                    console.error('Gemini authentication failed.', aiError);
                    if (status) {
                        status.textContent = `${aiMessage} Không dùng OCR dự phòng để tránh điền sai thông tin CCCD.`;
                        status.className = 'small text-danger d-block mb-3';
                    }
                    throw new Error(aiMessage);
                }

                console.warn('Gemini OCR thất bại, chuyển sang OCR dự phòng.', aiError);
                if (status) status.textContent = `${aiMessage} Đang thử OCR dự phòng trên thiết bị...`;
            }

            const result = await Tesseract.recognize(input.files[0], 'vie+eng', {
                logger: progress => {
                    if (status && progress.status === 'recognizing text') {
                        status.textContent = `Đang nhận diện: ${Math.round((progress.progress || 0) * 100)}%`;
                    }
                }
            });

            const data = parseCccd(result.data.text || '');

            const key = storageKey(input);
            if (key) {
                const dataUrl = await fileToDataUrl(input.files[0]);
                sessionStorage.setItem(key, JSON.stringify({source: 'ocr', data: { cccd: data.cccd }, dataUrl, name: input.files[0].name}));
            }

            if (!data.cccd) {
                throw new Error('Không đọc được đủ 12 số CCCD. Hãy chụp rõ, đủ sáng và không bị lóa.');
            }

            if (input.dataset.confirmApply === '1') {
                if (!confirm(`Đã đọc được số CCCD: ${data.cccd}.

Áp dụng các thông tin đọc được từ ảnh này?`)) {
                    if (status) status.textContent = 'Đã quét nhưng chưa áp dụng. Thông tin hiện tại được giữ nguyên.';
                    input.value = '';
                    return;
                }
            }

            // Ảnh CCCD dùng OCR để điền nhanh toàn bộ thông tin đọc được.
            // Người dùng/lễ tân luôn cần kiểm tra và có thể sửa lại trước khi lưu.
            applyData(input, data, 'Đã đọc ảnh CCCD');

            syncCheckInSubmitState();
        } catch (error) {
            if (status) status.textContent = error.message || 'Không thể quét ảnh CCCD.';
            alert(error.message || 'Không thể quét ảnh CCCD.');
        } finally {
            if (button) {
                button.disabled = false;
                button.innerHTML = oldText;
            }
        }
    });
})();
</script>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\booking-web\resources\views/partials/cccd-scanner-script.blade.php ENDPATH**/ ?>