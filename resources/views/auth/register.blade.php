@extends('layouts.user')

@section('title', 'Register')

@section('content')

    <section class="page-header">
        <div class="container">

            <h1 class="display-6 fw-bold mb-1">
                Đăng ký tài khoản khách hàng
            </h1>

            <p class="text-muted mb-0">
                Nhập đầy đủ thông tin để đặt phòng
                và quản lý hồ sơ nhanh hơn.
            </p>

        </div>
    </section>

    <main class="py-5">

        <div class="container">

            <div class="row justify-content-center">

                <div class="col-lg-8">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body p-4">

                            <form method="POST" action="{{ route('register') }}" id="registerForm">

                                @csrf

                                @if ($errors->any())
                                    <div class="alert alert-danger mb-3">
                                        <strong>Có lỗi xảy ra:</strong>
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="mb-4 text-center">
                                    <input type="file" id="cccdInput" accept="image/*" class="d-none">
                                    <button type="button" id="btnScanCccd" class="btn btn-outline-primary" onclick="document.getElementById('cccdInput').click()">
                                        <i class="bi bi-person-badge"></i> Quét thẻ CCCD (Tự động điền)
                                    </button>
                                    <div class="small text-muted mt-2">Hệ thống tự động nén ảnh để quét siêu tốc, không cần chờ lâu.</div>
                                </div>

                                <div class="row g-3">

                                    {{-- HỌ --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Họ
                                        </label>

                                        <input name="first_name" type="text" class="form-control" required />

                                    </div>

                                    {{-- TÊN --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Tên
                                        </label>

                                        <input name="last_name" type="text" class="form-control" required />

                                    </div>

                                    {{-- CCCD --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Số CCCD
                                        </label>

                                        <input name="cccd" type="text" class="form-control" maxlength="12"
                                            pattern="[0-9]{12}" required />

                                    </div>

                                    {{-- PHONE --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Số điện thoại
                                        </label>

                                        <input name="phone" type="tel" class="form-control" pattern="0[0-9]{9}" required />

                                    </div>

                                    {{-- EMAIL --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Email
                                        </label>

                                        <input name="email" type="email" class="form-control" required />

                                    </div>

                                    {{-- NGÀY SINH --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Ngày sinh
                                        </label>

                                        <input name="birthday" type="text" class="form-control js-birthday-picker"
                                            value="{{ old('birthday') }}" placeholder="dd/mm/yyyy" autocomplete="off" />
                                    </div>

                                    {{-- GIỚI TÍNH --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Giới tính
                                        </label>

                                        <select name="gender" class="form-select">

                                            <option value="male">
                                                Nam
                                            </option>

                                            <option value="female">
                                                Nữ
                                            </option>

                                            <option value="other">
                                                Khác
                                            </option>

                                        </select>

                                    </div>

                                    {{-- PASSWORD --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Mật khẩu
                                        </label>

                                        <input name="password" type="password" class="form-control" required />

                                    </div>

                                    {{-- CONFIRM PASSWORD --}}
                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Xác nhận mật khẩu
                                        </label>

                                        <input name="password_confirmation" type="password" class="form-control" required />

                                    </div>

                                    {{-- ADDRESS --}}
                                    <div class="col-12">

                                        <label class="form-label">
                                            Địa chỉ liên hệ
                                        </label>

                                        <textarea name="address" class="form-control" rows="2"></textarea>

                                    </div>

                                </div>

                                {{-- POLICY --}}
                                <div class="form-check mt-3">

                                    <input class="form-check-input" type="checkbox" id="policyCheck" required />

                                    <label class="form-check-label small" for="policyCheck">

                                        Tôi đồng ý với điều khoản sử dụng
                                        và chính sách bảo mật.

                                    </label>

                                </div>

                                {{-- BUTTON --}}
                                <div class="d-flex gap-2 mt-3">

                                    <button type="submit" class="btn btn-primary">

                                        Tạo tài khoản

                                    </button>

                                    <a href="{{ route('login') }}" class="btn btn-outline-primary">

                                        Đã có tài khoản

                                    </a>

                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        .flatpickr-calendar {
            font-family: inherit;
        }

        .flatpickr-current-month {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            left: 0;
            width: 100%;
            height: 34px;
            padding: 0;
        }

        .flatpickr-current-month .flatpickr-monthDropdown-months {
            height: 32px;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            padding: 2px 8px;
            background: #fff;
            font-size: 15px;
            font-weight: 600;
        }

        .flatpickr-current-month .numInputWrapper {
            display: none !important;
        }

        .birthday-year-select {
            height: 32px;
            min-width: 88px;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            background: #fff;
            padding: 2px 8px;
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            outline: none;
        }

        .birthday-year-select:focus,
        .flatpickr-current-month .flatpickr-monthDropdown-months:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof flatpickr === 'undefined') {
                return;
            }

            const currentYear = new Date().getFullYear();
            const minYear = currentYear - 120;
            const defaultYear = currentYear - 18;

            function addYearSelect(instance) {
                const currentMonth = instance.calendarContainer.querySelector('.flatpickr-current-month');

                if (!currentMonth) {
                    return;
                }

                let select = currentMonth.querySelector('.birthday-year-select');

                if (!select) {
                    select = document.createElement('select');
                    select.className = 'birthday-year-select';

                    for (let year = currentYear; year >= minYear; year--) {
                        const option = document.createElement('option');
                        option.value = year;
                        option.textContent = year;
                        select.appendChild(option);
                    }

                    select.addEventListener('change', function () {
                        instance.changeYear(Number(this.value));
                    });

                    currentMonth.appendChild(select);
                }

                select.value = instance.currentYear;
            }

            flatpickr('.js-birthday-picker', {
                locale: flatpickr.l10ns.vn,
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: true,
                disableMobile: true,
                monthSelectorType: 'dropdown',
                maxDate: 'today',
                minDate: `${minYear}-01-01`,
                defaultDate: null,

                onReady: function (selectedDates, dateStr, instance) {
                    if (!selectedDates.length) {
                        instance.jumpToDate(new Date(defaultYear, 0, 1));
                    }

                    addYearSelect(instance);
                },

                onOpen: function (selectedDates, dateStr, instance) {
                    if (!selectedDates.length) {
                        instance.jumpToDate(new Date(defaultYear, 0, 1));
                    }

                    addYearSelect(instance);
                },

                onMonthChange: function (selectedDates, dateStr, instance) {
                    addYearSelect(instance);
                },

                onYearChange: function (selectedDates, dateStr, instance) {
                    addYearSelect(instance);
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cccdInput = document.getElementById('cccdInput');
            if (cccdInput) {
                cccdInput.addEventListener('change', async function (e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const btn = document.getElementById('btnScanCccd');
                    const originalText = btn ? btn.innerHTML : '';
                    if (btn) {
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang quét và phân tích...';
                        btn.disabled = true;
                    }

                    try {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        const img = new Image();
                        img.src = URL.createObjectURL(file);
                        
                        await new Promise(resolve => img.onload = resolve);
                        
                        const MAX_WIDTH = 1200;
                        let width = img.width;
                        let height = img.height;
                        if (width > MAX_WIDTH) {
                            height = Math.round(height * MAX_WIDTH / width);
                            width = MAX_WIDTH;
                        }
                        
                        canvas.width = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);
                        
                        const imageData = ctx.getImageData(0, 0, width, height);
                        const data = imageData.data;
                        
                        for (let i = 0; i < data.length; i += 4) {
                            const avg = (data[i] + data[i + 1] + data[i + 2]) / 3;
                            const color = avg > 115 ? 255 : 0; 
                            data[i] = data[i + 1] = data[i + 2] = color;
                        }
                        ctx.putImageData(imageData, 0, 0);

                        const result = await Tesseract.recognize(
                            canvas.toDataURL('image/jpeg'),
                            'vie',
                            { logger: m => console.log(m) }
                        );
                        
                        const text = (result.data.text || '').normalize('NFC');
                        console.log("OCR Result:", text);
                        
                        let cccd = '';
                        let fullName = '';
                        let dob = '';
                        let address = '';
                        let gender = '';

                        const cccdMatch = text.match(/\b\d{12}\b/);
                        if (cccdMatch) cccd = cccdMatch[0];

                        const dobMatch = text.match(/\b(\d{2})[\/\-\.](\d{2})[\/\-\.](\d{4})\b/);
                        if (dobMatch) dob = `${dobMatch[3]}-${dobMatch[2]}-${dobMatch[1]}`; 

                        if (/Nam/i.test(text)) gender = 'male';
                        else if (/Nữ/i.test(text)) gender = 'female';

                        const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 0);
                        
                        let nameLineIndex = -1;
                        for (let i = 0; i < lines.length; i++) {
                            let lowerLine = lines[i].toLowerCase();
                            if (lowerLine.includes('họ và tên') || lowerLine.includes('full name') || lowerLine.includes('ho va ten') || lowerLine.includes('tên:')) {
                                nameLineIndex = i;
                                break;
                            }
                        }

                        if (nameLineIndex !== -1) {
                            let currentLineText = lines[nameLineIndex].replace(/.*(họ và tên|full name|ho va ten|tên)[:\-\s]*/i, '').trim();
                            if (currentLineText.length > 5 && !currentLineText.toLowerCase().includes('ngày sinh')) {
                                fullName = currentLineText;
                            } else if (nameLineIndex + 1 < lines.length) {
                                fullName = lines[nameLineIndex + 1];
                            }
                        } 
                        
                        if (!fullName) {
                            for (let i = 0; i < lines.length; i++) {
                                let upperCount = (lines[i].match(/[A-ZĂÂÊÔƠƯĐÁÀẢÃẠẮẰẲẴẶẤẦẨẪẬÉÈẺẼẸẾỀỂỄỆÍÌỈĨỊÓÒỎÕỌỐỒỔỖỘỚỜỞỠỢÚÙỦŨỤỨỪỬỮỰÝỲỶỸỴ]/g) || []).length;
                                let totalCount = lines[i].replace(/\s/g, '').length;
                                if (totalCount > 0 && upperCount / totalCount > 0.5 && lines[i].length > 6 && !lines[i].includes('CỘNG HÒA') && !lines[i].includes('ĐỘC LẬP') && !lines[i].includes('CĂN CƯỚC') && !lines[i].includes('SOCIALIST')) {
                                    fullName = lines[i];
                                    break;
                                }
                            }
                        }
                        
                        if (fullName) {
                            fullName = fullName.replace(/[^a-zA-ZĂÂÊÔƠƯĐÁÀẢÃẠẮẰẲẴẶẤẦẨẪẬÉÈẺẼẸẾỀỂỄỆÍÌỈĨỊÓÒỎÕỌỐỒỔỖỘỚỜỞỠỢÚÙỦŨỤỨỪỬỮỰÝỲỶỸỴ\s]/gi, '').trim().toUpperCase();
                        }

                        let addressLines = [];
                        let foundAddress = false;
                        for (let i = 0; i < lines.length; i++) {
                            let lowerLine = lines[i].toLowerCase();
                            if (foundAddress) {
                                if (lowerLine.includes('có giá trị đến') || lowerLine.includes('ngày') || lowerLine.includes('date') || lowerLine.includes('giám đốc') || lowerLine.includes('đặc điểm')) break;
                                addressLines.push(lines[i]);
                            } else if (lowerLine.includes('thường trú') || lowerLine.includes('residence') || lowerLine.includes('thuong tru') || lowerLine.includes('nơi trú') || (lowerLine.includes('nơi') && lowerLine.includes('trú'))) {
                                foundAddress = true;
                                let curr = lines[i].replace(/.*(thường trú|residence|thuong tru|nơi trú|trú)[:\-\s]*/i, '').trim();
                                if (curr.length > 3) addressLines.push(curr);
                            }
                        }
                        
                        if (addressLines.length === 0) {
                            for (let i = 0; i < lines.length; i++) {
                                let lowerLine = lines[i].toLowerCase();
                                if (lowerLine.includes('xã,') || lowerLine.includes('phường,') || lowerLine.includes('quận,') || lowerLine.includes('huyện,') || lowerLine.includes('thành phố') || lowerLine.includes('tỉnh,')) {
                                    addressLines.push(lines[i]);
                                    if (i + 1 < lines.length && !lines[i+1].toLowerCase().includes('có giá trị')) {
                                        addressLines.push(lines[i+1]);
                                    }
                                }
                            }
                        }

                        if (addressLines.length === 0) {
                            let commaLines = [];
                            for (let i = Math.floor(lines.length / 2); i < lines.length; i++) {
                                let lowerLine = lines[i].toLowerCase();
                                if (lowerLine.includes('ngày') || lowerLine.includes('giá trị') || lowerLine.includes('date') || lowerLine.includes('cộng hòa')) continue;
                                if (lines[i].includes(',')) commaLines.push(lines[i]);
                            }
                            if (commaLines.length > 0) {
                                addressLines.push(commaLines[commaLines.length - 1]);
                                if (commaLines.length > 1 && lines.indexOf(commaLines[commaLines.length - 1]) - lines.indexOf(commaLines[commaLines.length - 2]) === 1) {
                                    addressLines.unshift(commaLines[commaLines.length - 2]);
                                }
                            }
                        }

                        if (addressLines.length === 0) {
                            let genderLineIndex = -1;
                            for (let i = 0; i < lines.length; i++) {
                                if (lines[i].toLowerCase().includes('nam') || lines[i].toLowerCase().includes('nữ')) {
                                    genderLineIndex = i;
                                    break;
                                }
                            }
                            if (genderLineIndex !== -1) {
                                let remainingLines = lines.slice(genderLineIndex + 1).filter(l => {
                                    let low = l.toLowerCase();
                                    return !low.includes('có giá trị') && !low.includes('date') && !low.includes('đặc điểm') && !low.includes('giám đốc') && !low.includes('quốc tịch') && !low.includes('nationality') && !low.includes('quê') && !low.includes('origin');
                                });
                                if (remainingLines.length > 0) {
                                    addressLines.push(remainingLines[remainingLines.length - 1]);
                                    if (remainingLines.length > 1) {
                                        addressLines.unshift(remainingLines[remainingLines.length - 2]);
                                    }
                                }
                            }
                        }

                        if (addressLines.length > 0) {
                            address = addressLines.join(' ');
                            address = address.replace(/,/g, ' , ');
                            address = address.replace(/[^a-zA-Z0-9ĂÂÊÔƠƯĐÁÀẢÃẠẮẰẲẴẶẤẦẨẪẬÉÈẺẼẸẾỀỂỄỆÍÌỈĨỊÓÒỎÕỌỐỒỔỖỘỚỜỞỠỢÚÙỦŨỤỨỪỬỮỰÝỲỶỸỴ\,\s]/gi, ' ');
                            
                            let words = address.split(/\s+/);
                            let cleanWords = [];
                            for (let w of words) {
                                if (w === '') continue;
                                if (w === ',') {
                                    if (cleanWords.length > 0 && cleanWords[cleanWords.length - 1] !== ',') {
                                        cleanWords.push(',');
                                    }
                                    continue;
                                }
                                
                                if (/^[a-zăâêôơưđáàảãạắằẳẵặấầẩẫậéèẻẽẹếềểễệíìỉĩịóòỏõọốồổỗộớờởỡợúùủũụứừửữựýỳỷỹỵ]/.test(w)) continue;
                                if (w.length === 1 && !/^\d$/.test(w)) continue;
                                
                                let low = w.toLowerCase();
                                if (['place', 'of', 'residence', 'residenoe', 'origin', 'date', 'nationality'].includes(low)) continue;
                                
                                cleanWords.push(w);
                            }
                            
                            address = cleanWords.join(' ').replace(/\s+,\s+/g, ', ').replace(/^[\s,]+|[\s,]+$/g, '');
                        }

                        if (cccd) document.querySelector('input[name="cccd"]').value = cccd;
                        
                        if (fullName) {
                            let parts = fullName.split(' ');
                            if (parts.length > 1) {
                                document.querySelector('input[name="first_name"]').value = parts[0]; 
                                document.querySelector('input[name="last_name"]').value = parts.slice(1).join(' '); 
                            } else {
                                document.querySelector('input[name="first_name"]').value = fullName;
                            }
                        }
                        
                        if (dob) {
                            const bInput = document.querySelector('input[name="birthday"]');
                            if (bInput && bInput._flatpickr) {
                                bInput._flatpickr.setDate(dob);
                            } else if (bInput) {
                                bInput.value = dob;
                            }
                        }
                        
                        if (gender) document.querySelector('select[name="gender"]').value = gender;
                        if (address) document.querySelector('textarea[name="address"]').value = address;

                        alert('Quét hoàn tất! Vui lòng kiểm tra kỹ và chỉnh sửa lại nếu có lỗi nhận diện chữ.');
                        
                    } catch (err) {
                        console.error(err);
                        alert('Có lỗi xảy ra khi quét ảnh. Vui lòng thử lại.');
                    } finally {
                        if (btn) {
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }
                        e.target.value = ''; 
                    }
                });
            }
        });
    </script>
@endsection