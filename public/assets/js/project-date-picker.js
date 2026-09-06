(function () {
    'use strict';

    const DATE_SELECTOR = [
        'input[type="date"]:not([data-native-date-picker])',
        'input[data-project-date-picker]:not([data-project-time-picker]):not([data-project-datetime-picker])',
        'input[data-birth-date]',
    ].join(',');

    const TIME_SELECTOR = [
        'input[type="time"]:not([data-native-time-picker])',
        'input[data-project-time-picker]',
    ].join(',');

    const DATETIME_SELECTOR = [
        'input[type="datetime-local"]:not([data-native-datetime-picker])',
        'input[data-project-datetime-picker]',
    ].join(',');

    const parseYear = (value) => {
        if (!value) return null;
        const match = String(value).match(/^(\d{4})/);
        return match ? Number(match[1]) : null;
    };

    const todayValue = () => {
        const now = new Date();
        const local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 10);
    };

    const inputKey = (input) => [
        input?.name,
        input?.id,
        input?.className,
    ].filter(Boolean).join(' ').toLowerCase();

    const isBirthInput = (input) => {
        if (!input) return false;
        return input.hasAttribute('data-birth-date')
            || /(birth|birthday|date_of_birth|ngay_sinh|scanned_birthday)/.test(inputKey(input));
    };

    const isBookingDateInput = (input) => {
        if (!input || input.hasAttribute('data-no-min')) return false;
        if (input.hasAttribute('data-future-date')) return true;

        return /(check.?in|check.?out|arrival|departure|booking.?date|ngay.?nhan|ngay.?tra)/.test(inputKey(input));
    };

    const needsYearSelect = (input) => {
        if (!input || input.hasAttribute('data-hide-year-select')) return false;
        return true;
    };

    const yearRangeFor = (input, instance) => {
        const currentYear = new Date().getFullYear();
        const selectedYear = instance?.selectedDates?.[0]?.getFullYear()
            || instance?.currentYear
            || currentYear;

        let minYear = parseYear(input.getAttribute('min'));
        let maxYear = parseYear(input.getAttribute('max'));

        if (isBirthInput(input)) {
            minYear = minYear || 1900;
            maxYear = Math.min(maxYear || currentYear, currentYear);
        } else if (isBookingDateInput(input)) {
            minYear = minYear || currentYear;
            maxYear = maxYear || Math.max(currentYear + 10, selectedYear + 5);
        } else {
            minYear = minYear || Math.min(currentYear - 10, selectedYear - 5);
            maxYear = maxYear || Math.max(currentYear + 10, selectedYear + 5);
        }

        if (!isBookingDateInput(input)) {
            minYear = Math.min(minYear, selectedYear);
        }
        maxYear = Math.max(maxYear, selectedYear);

        return { minYear, maxYear };
    };

    const attachYearSelect = (instance, input) => {
        if (!instance?.calendarContainer || !input || instance.config?.noCalendar || !needsYearSelect(input)) return;

        const currentMonth = instance.calendarContainer.querySelector('.flatpickr-current-month');
        if (!currentMonth) return;

        const { minYear, maxYear } = yearRangeFor(input, instance);
        const activeYear = instance.currentYear
            || instance.selectedDates?.[0]?.getFullYear()
            || new Date().getFullYear();

        let select = currentMonth.querySelector('.project-year-select');
        if (!select) {
            select = document.createElement('select');
            select.className = 'project-year-select';
            select.setAttribute('aria-label', 'Chọn năm');

            // Không để Flatpickr hiểu thao tác với dropdown năm là click ra ngoài lịch.
            ['pointerdown', 'mousedown', 'click'].forEach((eventName) => {
                select.addEventListener(eventName, (event) => event.stopPropagation());
            });

            select.addEventListener('change', function (event) {
                event.stopPropagation();
                const year = Number(this.value);
                if (!Number.isInteger(year)) return;
                instance.changeYear(year);
            });

            const nativeYear = currentMonth.querySelector('.numInputWrapper');
            nativeYear?.classList.add('project-native-year-hidden');

            const monthDropdown = currentMonth.querySelector('.flatpickr-monthDropdown-months');
            if (monthDropdown) monthDropdown.insertAdjacentElement('afterend', select);
            else currentMonth.appendChild(select);
        }

        // Chỉ dựng lại option khi khoảng năm thay đổi; không làm mỗi lần DOM biến động.
        const rangeKey = `${minYear}:${maxYear}`;
        if (select.dataset.range !== rangeKey) {
            const fragment = document.createDocumentFragment();
            for (let year = maxYear; year >= minYear; year -= 1) {
                const option = document.createElement('option');
                option.value = String(year);
                option.textContent = String(year);
                fragment.appendChild(option);
            }
            select.replaceChildren(fragment);
            select.dataset.range = rangeKey;
        }
        select.value = String(activeYear);
    };

    window.initializeProjectDatePicker = function (input) {
        if (!input || !input.matches?.(DATE_SELECTOR)) return;
        if (typeof window.flatpickr === 'undefined') return;

        // Một số trang hủy rồi tạo lại Flatpickr trên chính input cũ khi đổi
        // ngày nhận/trả. Khi đó chỉ gắn lại phần chọn năm, không khởi tạo thêm
        // instance và không can thiệp handler nghiệp vụ của trang.
        if (input.dataset.projectDateInitialized === '1') {
            if (input._flatpickr) attachYearSelect(input._flatpickr, input);
            return;
        }

        input.dataset.projectDateInitialized = '1';

        const birthInput = isBirthInput(input);
        const bookingInput = isBookingDateInput(input);
        const defaultMinDate = birthInput ? '1900-01-01' : (bookingInput ? todayValue() : null);
        const defaultMaxDate = birthInput ? todayValue() : null;

        if (defaultMinDate && !input.getAttribute('min')) input.setAttribute('min', defaultMinDate);
        if (defaultMaxDate && !input.getAttribute('max')) input.setAttribute('max', defaultMaxDate);

        const currentMinDate = () => input.getAttribute('min') || null;
        const currentMaxDate = () => input.getAttribute('max') || null;

        const validateDate = () => {
            const value = input.value;
            const minDate = currentMinDate();
            const maxDate = currentMaxDate();
            let message = '';
            if (value && minDate && value < minDate) {
                message = birthInput
                    ? 'Ngày sinh không hợp lệ.'
                    : 'Ngày đã chọn không được nhỏ hơn mốc tối thiểu cho phép.';
            } else if (value && maxDate && value > maxDate) {
                message = birthInput ? 'Ngày sinh không được lớn hơn ngày hiện tại.' : 'Ngày đã chọn vượt quá mốc tối đa cho phép.';
            }
            input.setCustomValidity(message);
        };

        const watchDynamicLimits = (instance) => {
            const sync = () => {
                instance?.set('minDate', currentMinDate());
                instance?.set('maxDate', currentMaxDate());
                validateDate();
            };
            const observer = new MutationObserver(sync);
            observer.observe(input, { attributes: true, attributeFilter: ['min', 'max'] });
            sync();
        };

        // Một số trang có cấu hình nghiệp vụ riêng (cặp ngày, ngày giờ). Giữ nguyên
        // instance đó, đồng bộ giới hạn và bổ sung dropdown năm.
        if (input._flatpickr) {
            const instance = input._flatpickr;
            watchDynamicLimits(instance);
            if (needsYearSelect(input)) {
                const enhance = () => attachYearSelect(instance, input);
                instance.config.onReady.push(enhance);
                instance.config.onOpen.push(enhance);
                instance.config.onMonthChange.push(enhance);
                instance.config.onYearChange.push(enhance);
                enhance();
            }
            input.addEventListener('change', validateDate);
            input.addEventListener('blur', validateDate);
            validateDate();
            return;
        }

        window.flatpickr(input, {
            locale: window.flatpickr.l10ns?.vn || 'default',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            minDate: currentMinDate(),
            maxDate: currentMaxDate(),
            allowInput: true,
            disableMobile: true,
            monthSelectorType: 'dropdown',
            onReady: (_dates, _value, instance) => attachYearSelect(instance, input),
            onOpen: (_dates, _value, instance) => attachYearSelect(instance, input),
            onMonthChange: (_dates, _value, instance) => attachYearSelect(instance, input),
            onYearChange: (_dates, _value, instance) => attachYearSelect(instance, input),
            onChange: () => {
                validateDate();
                input.dispatchEvent(new CustomEvent('project-date-change', { bubbles: true }));
            },
        });

        watchDynamicLimits(input._flatpickr);
        input.addEventListener('change', validateDate);
        input.addEventListener('blur', validateDate);
        validateDate();
    };

    window.initializeProjectTimePicker = function (input) {
        if (!input || !input.matches?.(TIME_SELECTOR)) return;
        if (typeof window.flatpickr === 'undefined') return;
        if (input.dataset.projectTimeInitialized === '1') return;
        input.dataset.projectTimeInitialized = '1';

        // Giữ nguyên picker nghiệp vụ đã được trang khởi tạo riêng.
        if (input._flatpickr) return;

        window.flatpickr(input, {
            locale: window.flatpickr.l10ns?.vn || 'default',
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            allowInput: true,
            disableMobile: true,
            minuteIncrement: Number(input.getAttribute('step')) >= 60
                ? Math.max(1, Math.round(Number(input.getAttribute('step')) / 60))
                : 1,
        });
    };

    window.initializeProjectDateTimePicker = function (input) {
        if (!input || !input.matches?.(DATETIME_SELECTOR)) return;
        if (typeof window.flatpickr === 'undefined') return;
        if (input.dataset.projectDatetimeInitialized === '1') return;
        input.dataset.projectDatetimeInitialized = '1';

        if (input._flatpickr) return;

        window.flatpickr(input, {
            locale: window.flatpickr.l10ns?.vn || 'default',
            enableTime: true,
            dateFormat: 'Y-m-d\TH:i',
            altInput: true,
            altFormat: 'd/m/Y H:i',
            time_24hr: true,
            allowInput: true,
            disableMobile: true,
            monthSelectorType: 'dropdown',
            onReady: (_dates, _value, instance) => attachYearSelect(instance, input),
            onOpen: (_dates, _value, instance) => attachYearSelect(instance, input),
            onMonthChange: (_dates, _value, instance) => attachYearSelect(instance, input),
            onYearChange: (_dates, _value, instance) => attachYearSelect(instance, input),
        });
    };

    window.initializeProjectDatePickers = function (root) {
        const scope = root instanceof Element || root instanceof Document ? root : document;
        if (scope.matches?.(DATE_SELECTOR)) window.initializeProjectDatePicker(scope);
        scope.querySelectorAll?.(DATE_SELECTOR).forEach(window.initializeProjectDatePicker);

        if (scope.matches?.(TIME_SELECTOR)) window.initializeProjectTimePicker(scope);
        scope.querySelectorAll?.(TIME_SELECTOR).forEach(window.initializeProjectTimePicker);

        if (scope.matches?.(DATETIME_SELECTOR)) window.initializeProjectDateTimePicker(scope);
        scope.querySelectorAll?.(DATETIME_SELECTOR).forEach(window.initializeProjectDateTimePicker);
    };

    let enhanceFrame = null;
    const enhanceAllYearSelects = () => {
        enhanceFrame = null;
        const enhancedInstances = new Set();
        document.querySelectorAll('input').forEach((candidate) => {
            const instance = candidate._flatpickr;
            if (!instance || instance.config?.noCalendar || enhancedInstances.has(instance)) return;

            enhancedInstances.add(instance);
            const sourceInput = instance.input || candidate;
            attachYearSelect(instance, sourceInput);
        });
    };
    const scheduleYearSelectEnhancement = () => {
        if (enhanceFrame !== null) return;
        enhanceFrame = window.requestAnimationFrame(enhanceAllYearSelects);
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initializeProjectDatePickers(document);
        scheduleYearSelectEnhancement();
    }, { once: true });

    // Form động phải chủ động phát sự kiện này sau khi thêm HTML.
    document.addEventListener('project-date-picker:init', function (event) {
        window.initializeProjectDatePickers(event.detail?.root || document);
    });

    // Bảo đảm dropdown năm được gắn lại nếu một trang chủ động destroy/recreate
    // Flatpickr sau lần khởi tạo chung.
    document.addEventListener('focusin', function (event) {
        if (event.target?.matches?.(DATE_SELECTOR)) window.initializeProjectDatePicker(event.target);
        if (event.target?.matches?.(TIME_SELECTOR)) window.initializeProjectTimePicker(event.target);
        if (event.target?.matches?.(DATETIME_SELECTOR)) window.initializeProjectDateTimePicker(event.target);
        if (event.target?.classList?.contains('flatpickr-input')
            || event.target?._flatpickr
            || event.target?.classList?.contains('form-control')) {
            scheduleYearSelectEnhancement();
        }
    });

    document.addEventListener('pointerdown', function (event) {
        if (event.target?.matches?.(DATE_SELECTOR)
            || event.target?.matches?.(TIME_SELECTOR)
            || event.target?.matches?.(DATETIME_SELECTOR)
            || event.target?.classList?.contains('flatpickr-input')
            || event.target?._flatpickr
            || event.target?.classList?.contains('form-control')) {
            scheduleYearSelectEnhancement();
        }
    }, true);

    // Các dòng khách lưu trú được thêm động vẫn nhận đúng date picker.
    document.addEventListener('DOMContentLoaded', function () {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node instanceof Element) window.initializeProjectDatePickers(node);
                });
            });
            scheduleYearSelectEnhancement();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }, { once: true });
})();
