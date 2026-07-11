@once
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script>
(function () {
    function normalizeText(value) {
        return (value || '').replace(/\r/g, '').replace(/[ \t]+/g, ' ').trim();
    }

    function setValue(selector, value) {
        if (!selector || !value) return;
        const el = document.querySelector(selector);
        if (!el) return;
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function parseDate(text) {
        const match = text.match(/\b(0?[1-9]|[12]\d|3[01])[\/\-.](0?[1-9]|1[0-2])[\/\-.]((?:19|20)\d{2})\b/);
        if (!match) return '';
        return `${match[3]}-${String(match[2]).padStart(2, '0')}-${String(match[1]).padStart(2, '0')}`;
    }

    function parseCccd(text) {
        const clean = normalizeText(text);
        const lines = clean.split('\n').map(normalizeText).filter(Boolean);
        const cccd = (clean.match(/\b\d{12}\b/) || [''])[0];
        const birthday = parseDate(clean);

        let fullName = '';
        for (let i = 0; i < lines.length; i++) {
            if (/họ\s*và\s*tên|ho\s*va\s*ten|full\s*name/i.test(lines[i])) {
                fullName = normalizeText(lines[i].replace(/^.*?(họ\s*và\s*tên|ho\s*va\s*ten|full\s*name)\s*[:\-]?\s*/i, ''));
                if (!fullName && lines[i + 1]) fullName = lines[i + 1];
                break;
            }
        }
        if (!fullName) {
            fullName = lines.find(line => /^[A-ZÀ-ỸĐ][A-ZÀ-ỸĐ\s]{5,}$/.test(line) && !/CĂN CƯỚC|CỘNG HÒA|VIỆT NAM|SOCIALIST|IDENTITY/i.test(line)) || '';
        }

        fullName = fullName.replace(/[^A-Za-zÀ-ỹĐđ\s]/g, '').replace(/\s+/g, ' ').trim();
        const parts = fullName.split(' ').filter(Boolean);
        const firstName = parts.length ? parts.pop() : '';
        const lastName = parts.join(' ');

        let gender = '';
        if (/giới\s*tính\s*[:\-]?\s*nam|sex\s*[:\-]?\s*male/i.test(clean)) gender = 'male';
        if (/giới\s*tính\s*[:\-]?\s*nữ|sex\s*[:\-]?\s*female/i.test(clean)) gender = 'female';

        let address = '';
        for (let i = 0; i < lines.length; i++) {
            if (/nơi\s*thường\s*trú|place\s*of\s*residence/i.test(lines[i])) {
                address = normalizeText(lines[i].replace(/^.*?(nơi\s*thường\s*trú|place\s*of\s*residence)\s*[:\-]?\s*/i, ''));
                if (!address && lines[i + 1]) address = lines[i + 1];
                break;
            }
        }

        return { cccd, fullName, firstName, lastName, birthday, gender, address };
    }

    document.addEventListener('change', async function (event) {
        const input = event.target.closest('.js-cccd-image');
        if (!input || !input.files || !input.files[0]) return;

        const button = input.dataset.button ? document.querySelector(input.dataset.button) : null;
        const status = input.dataset.status ? document.querySelector(input.dataset.status) : null;
        const oldText = button ? button.innerHTML : '';

        try {
            if (button) {
                button.disabled = true;
                button.innerHTML = 'Đang quét...';
            }
            if (status) status.textContent = 'Đang nhận diện ảnh CCCD, vui lòng chờ...';

            const result = await Tesseract.recognize(input.files[0], 'vie+eng', {
                logger: progress => {
                    if (status && progress.status === 'recognizing text') {
                        status.textContent = `Đang nhận diện: ${Math.round((progress.progress || 0) * 100)}%`;
                    }
                }
            });

            const data = parseCccd(result.data.text || '');

            if (!data.cccd) {
                throw new Error('Không đọc được đủ 12 số CCCD. Hãy chụp rõ, đủ sáng và không bị lóa.');
            }

            if (input.dataset.confirmApply === '1') {
                const summary = [
                    `CCCD: ${data.cccd}`,
                    data.fullName ? `Họ tên: ${data.fullName}` : '',
                    data.birthday ? `Ngày sinh: ${data.birthday.split('-').reverse().join('/')}` : '',
                    data.address ? `Địa chỉ: ${data.address}` : ''
                ].filter(Boolean).join('\n');

                if (!confirm(`Đã đọc được thông tin sau:\n\n${summary}\n\nDùng thông tin này và cập nhật hồ sơ khách hàng?`)) {
                    if (status) status.textContent = 'Đã quét nhưng chưa áp dụng. Thông tin hiện tại được giữ nguyên.';
                    input.value = '';
                    return;
                }
            }

            setValue(input.dataset.targetCccd, data.cccd);
            setValue(input.dataset.targetFullName, data.fullName);
            setValue(input.dataset.targetFirstName, data.firstName);
            setValue(input.dataset.targetLastName, data.lastName);
            setValue(input.dataset.targetBirthday, data.birthday);
            setValue(input.dataset.targetGender, data.gender);
            setValue(input.dataset.targetAddress, data.address);

            const expectedCccd = (input.dataset.expectedCccd || '').replace(/\D/g, '');
            const submitButton = input.dataset.submitButton ? document.querySelector(input.dataset.submitButton) : null;
            const policyDisabled = submitButton && submitButton.dataset.policyDisabled === '1';

            if (expectedCccd) {
                if (data.cccd === expectedCccd) {
                    if (status) {
                        status.textContent = `CCCD trùng khớp (${data.cccd}). Có thể xác nhận check-in.`;
                        status.classList.remove('text-danger', 'text-muted');
                        status.classList.add('text-success');
                    }
                    if (submitButton && !policyDisabled) submitButton.disabled = false;
                } else {
                    if (status) {
                        status.textContent = `CCCD không trùng booking. Đã quét ${data.cccd}; booking dùng CCCD kết thúc bằng ${expectedCccd.slice(-4)}.`;
                        status.classList.remove('text-success', 'text-muted');
                        status.classList.add('text-danger');
                    }
                    if (submitButton) submitButton.disabled = true;
                }
            } else if (status) {
                status.textContent = `Đã đọc CCCD: ${data.cccd}. Vui lòng kiểm tra lại thông tin rồi bấm lưu.`;
            }
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
@endonce
