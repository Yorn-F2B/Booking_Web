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
        // Broaden matching for dates to handle spaces and common OCR errors like l or 1 instead of /
        const match = text.match(/\b(\d{1,2})\s*[\/\-.\\]\s*(\d{1,2})\s*[\/\-.\\]\s*((?:19|20)\d{2})\b/);
        if (!match) return '';
        return `${match[3]}-${String(match[2]).padStart(2, '0')}-${String(match[1]).padStart(2, '0')}`;
    }

    function parseCccd(text) {
        const clean = normalizeText(text);
        const lines = clean.split('\n').map(normalizeText).filter(Boolean);
        const cccd = (clean.match(/\b\d{12}\b/) || [''])[0];
        const birthday = parseDate(clean);

        // Find DOB line to help locate name
        let dobLineIdx = -1;
        for (let i = 0; i < lines.length; i++) {
            if (/\b\d{1,2}\s*[\/\-.\\]\s*\d{1,2}\s*[\/\-.\\]\s*\d{4}\b/.test(lines[i]) || /ng[aà]y.*sinh/i.test(lines[i]) || /date.*birth/i.test(lines[i])) {
                dobLineIdx = i;
                break;
            }
        }

        // Parse Full Name
        let fullName = '';
        for (let i = 0; i < lines.length; i++) {
            let lower = lines[i].toLowerCase();
            if (lower.includes('họ và tên') || lower.includes('ho va ten') || lower.includes('full name') || lower.includes('ho va tan')) {
                // Try to get name from the same line
                let text = lines[i].replace(/^.*?(họ và tên|ho va ten|full name|ho va tan)/i, '');
                text = text.replace(/full\s*name/i, '').replace(/^[\\\/\|\-\:\s]+/, '').trim();
                
                if (text && text.length > 3) {
                    fullName = text;
                } else {
                    // Try next 2 lines
                    for (let j = 1; j <= 2; j++) {
                        if (i + j < lines.length) {
                            let nextLine = lines[i + j].replace(/full\s*name/i, '').replace(/^[\\\/\|\-\:\s]+/, '').trim();
                            if (nextLine && !/\d/.test(nextLine) && nextLine.length > 3) {
                                fullName = nextLine;
                                break;
                            }
                        }
                    }
                }
                break;
            }
        }
        
        // Fallback for name if keywords fail but we found DOB
        if (!fullName && dobLineIdx > 0) {
            let candidate = lines[dobLineIdx - 1];
            if (!/h[oọ] v[aà] t[eê]n|ho va ten/i.test(candidate)) {
                fullName = candidate;
            } else if (dobLineIdx > 1) {
                fullName = lines[dobLineIdx - 2];
            }
        }

        // Clean up name by removing numbers and punctuation instead of strict whitelist
        if (fullName) {
            fullName = fullName.replace(/h[oọ]\s*v[aà]\s*t[eê]n|ho\s*va\s*ten|full\s*name/ig, '');
            fullName = fullName.replace(/[0-9!@#\$%\^\&*\)\(+=._\-\:\"\'\?\<\>\[\]\{\}\\\/|~]/g, '');
            fullName = fullName.replace(/\s+/g, ' ').trim();
        }
        
        const parts = fullName.split(' ').filter(Boolean);
        const firstName = parts.length ? parts.pop() : '';
        const lastName = parts.join(' ');

        // Parse Gender
        let gender = '';
        if (/giới\s*tính\s*[:\-]?\s*nam|sex\s*[:\-]?\s*male|\bnam\b/i.test(clean)) gender = 'male';
        if (/giới\s*tính\s*[:\-]?\s*nữ|sex\s*[:\-]?\s*female|\bn[ữu]\b/i.test(clean)) gender = 'female';

        // Parse Address: Only extract Quê quán (Place of Origin) per user preference
        let address = '';
        let startIdx = -1;
        
        // 1. Find the starting line of Quê quán
        for (let i = 0; i < lines.length; i++) {
            let lower = lines[i].toLowerCase();
            // More forgiving regex for "Quê quán" or "Place of origin"
            if (/qu[eê]\s*qu[aá]n|place.*?origin/i.test(lower)) {
                startIdx = i;
                break;
            }
        }
        
        // 2. Collect lines until "Nơi thường trú" or "Có giá trị đến"
        if (startIdx !== -1) {
            for (let i = startIdx; i < Math.min(startIdx + 4, lines.length); i++) {
                let nextLine = lines[i];
                // Forgiving regex for "Thường trú" or "Place ... residence"
                if (i !== startIdx && /(th[uư][oờ]ng\s*tr[uú]|place.*?residence|c[oó]\s*gi[aá]\s*tr[iị]|date.*?expiry|c[uụ]c\s*tr[uư][oở]ng)/i.test(nextLine)) {
                    break;
                }
                address += (address ? ', ' : '') + nextLine;
            }
        }
        
        if (address) {
            // Cut off anything from "Nơi thường trú" onwards if it got caught on the same line
            let cutMatch = address.match(/(th[uư][oờ]ng\s*tr[uú]|place.*?residence|c[oó]\s*gi[aá]\s*tr[iị]|date.*?expiry)/i);
            if (cutMatch) {
                address = address.substring(0, cutMatch.index);
            }
            
            // Aggressively strip out Quê quán labels
            address = address.replace(/(qu[eê]\s*qu[aá]n|place.*?origin)/ig, '');
            
            // Strip out weird symbols including colons but keep letters, numbers, spaces, and basic punctuation
            address = address.replace(/[!@#\$%\^\&*\)\(+=\"\'\?\<\>\[\]\{\}\|~_:\-\/—–]/g, '');
            
            // Collapse whitespace
            address = address.replace(/\s+/g, ' ').trim();
            
            // Clean up stray commas left behind
            address = address.replace(/\s*,\s*,\s*/g, ', ');
            address = address.replace(/^[,.\s]+/, '');
            
            // Remove OCR noise at the beginning like "SS 2, EN " 
            // It strips short uppercase words/digits but preserves valid location prefixes (TP, TT, TX, Q, H, X)
            let prevAddress = '';
            while (address !== prevAddress) {
                prevAddress = address;
                address = address.replace(/^(?!(?:TP|TT|TX|Q|H|X)\b)[A-Z0-9]{1,4}[\s,.\-_]+/, '');
            }
            
            // Final trim and trailing comma removal
            address = address.replace(/[,.\s]+$/, '').trim();
        }

        return { cccd, fullName, firstName, lastName, birthday, gender, address };
    }

    function preprocessImage(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = function(event) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    // Resize to a maximum dimension to speed up OCR and normalize scale
                    const MAX_DIMENSION = 1600;
                    let width = img.width;
                    let height = img.height;

                    if (width > height) {
                        if (width > MAX_DIMENSION) {
                            height *= MAX_DIMENSION / width;
                            width = MAX_DIMENSION;
                        }
                    } else {
                        if (height > MAX_DIMENSION) {
                            width *= MAX_DIMENSION / height;
                            height = MAX_DIMENSION;
                        }
                    }
                    canvas.width = width;
                    canvas.height = height;
                    ctx.drawImage(img, 0, 0, width, height);

                    // Grayscale and Contrast Enhancement
                    const imageData = ctx.getImageData(0, 0, width, height);
                    const data = imageData.data;
                    const contrast = 1.3; // Increase contrast
                    const intercept = 128 * (1 - contrast);
                    
                    for (let i = 0; i < data.length; i += 4) {
                        // Grayscale formula
                        const grayscale = data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114;
                        
                        // Apply contrast to grayscale
                        let color = grayscale * contrast + intercept;
                        color = Math.min(255, Math.max(0, color)); // Clamp
                        
                        data[i] = color;
                        data[i + 1] = color;
                        data[i + 2] = color;
                        // Alpha channel (data[i + 3]) left unchanged
                    }
                    ctx.putImageData(imageData, 0, 0);

                    // Use slightly lower quality JPEG to speed up Tesseract loading, though it shouldn't affect OCR much
                    resolve(canvas.toDataURL('image/jpeg', 0.95));
                };
                img.onerror = reject;
                img.src = event.target.result;
            };
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
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
            if (status) status.textContent = 'Đang xử lý độ sắc nét của ảnh...';

            const processedImage = await preprocessImage(input.files[0]);

            if (status) status.textContent = 'Đang nhận diện ảnh CCCD, vui lòng chờ...';

            const result = await Tesseract.recognize(processedImage, 'vie+eng', {
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
