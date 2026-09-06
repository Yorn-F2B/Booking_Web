(function () {
    'use strict';

    const STYLE_ID = 'global-form-validation-hints-style';
    const HINT_CLASS = 'field-validation-hint';
    const INVALID_CLASS = 'field-validation-invalid';

    function installStyle() {
        if (document.getElementById(STYLE_ID)) return;
        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            .${HINT_CLASS}{display:block;margin-top:5px;font-size:12px;line-height:1.35;color:#dc3545;font-weight:500}
            .${INVALID_CLASS}{border-color:#dc3545!important;box-shadow:0 0 0 .12rem rgba(220,53,69,.08)!important}
            .form-check-input.${INVALID_CLASS}{outline:1px solid #dc3545;outline-offset:1px}
        `;
        document.head.appendChild(style);
    }

    function cleanLabel(text) {
        return String(text || '')
            .replace(/\*/g, '')
            .replace(/[:：]\s*$/, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function fieldLabel(field) {
        if (!field) return '';
        if (field.id) {
            const explicit = document.querySelector(`label[for="${CSS.escape(field.id)}"]`);
            if (explicit) return cleanLabel(explicit.textContent);
        }
        const wrapper = field.closest('.mb-3, .form-group, .field, .col, [class*="col-"]');
        const nearby = wrapper?.querySelector('label');
        if (nearby) return cleanLabel(nearby.textContent);
        return cleanLabel(field.getAttribute('aria-label') || field.getAttribute('placeholder') || 'trường này');
    }

    function missingMessage(field) {
        const label = fieldLabel(field) || 'trường này';
        const type = (field.type || '').toLowerCase();
        if (type === 'checkbox' || type === 'radio') return `Vui lòng chọn/xác nhận ${label}.`;
        if (field.tagName === 'SELECT') return `Vui lòng chọn ${label}.`;
        if (type === 'file') return `Vui lòng chọn ${label}.`;
        return `Vui lòng nhập ${label}.`;
    }

    function errorMessage(field) {
        if (field.validity?.valueMissing) return missingMessage(field);
        if (field.validity?.typeMismatch) return `Giá trị ${fieldLabel(field) || 'đã nhập'} chưa đúng định dạng.`;
        if (field.validity?.tooShort) return `Nội dung ${fieldLabel(field) || 'này'} còn quá ngắn.`;
        if (field.validity?.tooLong) return `Nội dung ${fieldLabel(field) || 'này'} vượt quá độ dài cho phép.`;
        if (field.validity?.rangeUnderflow || field.validity?.rangeOverflow || field.validity?.stepMismatch) {
            return `Giá trị ${fieldLabel(field) || 'này'} chưa nằm trong phạm vi cho phép.`;
        }
        if (field.validity?.patternMismatch) return `Giá trị ${fieldLabel(field) || 'này'} chưa đúng định dạng yêu cầu.`;
        return field.validationMessage || `Vui lòng kiểm tra ${fieldLabel(field) || 'trường này'}.`;
    }

    function groupFields(field) {
        if (!field?.name || !['radio', 'checkbox'].includes((field.type || '').toLowerCase())) return [field];
        try {
            return Array.from(field.form?.querySelectorAll(`[name="${CSS.escape(field.name)}"]`) || [field]);
        } catch (_) {
            return [field];
        }
    }

    function hintAnchor(field) {
        if (!field) return null;
        const type = (field.type || '').toLowerCase();
        if (type === 'radio' || type === 'checkbox') {
            return field.closest('.form-check, .option-row, label') || field;
        }
        return field;
    }

    function removeHint(field) {
        groupFields(field).forEach((item) => item?.classList.remove(INVALID_CLASS, 'is-invalid'));
        const name = field?.name || field?.id || '';
        if (!name) return;
        document.querySelectorAll(`.${HINT_CLASS}[data-validation-for]`).forEach((hint) => {
            if (hint.dataset.validationFor === name) hint.remove();
        });
    }

    function showHint(field, message) {
        if (!field || field.disabled || (field.type || '').toLowerCase() === 'hidden') return;
        installStyle();
        removeHint(field);
        groupFields(field).forEach((item) => item?.classList.add(INVALID_CLASS, 'is-invalid'));

        const wrapper = field.closest('.mb-3, .form-group, .field, .form-check, [class*="col-"]') || field.parentElement;
        const existingFeedback = wrapper?.querySelector('.invalid-feedback, [data-field-error]');
        if (!existingFeedback) {
            const hint = document.createElement('div');
            hint.className = HINT_CLASS;
            hint.dataset.validationFor = field.name || field.id || '';
            hint.textContent = message || errorMessage(field);

            const anchor = hintAnchor(field);
            if (anchor?.parentNode) anchor.insertAdjacentElement('afterend', hint);
        }

        let details = field.closest('details');
        while (details) {
            details.open = true;
            details = details.parentElement?.closest('details');
        }
    }

    function bracketName(dotName) {
        const parts = String(dotName || '').split('.');
        if (!parts.length) return dotName;
        return parts[0] + parts.slice(1).map((part) => `[${part}]`).join('');
    }

    function findFieldByErrorKey(key) {
        const names = [String(key), bracketName(key)];
        for (const name of names) {
            try {
                const field = document.querySelector(`[name="${CSS.escape(name)}"]`);
                if (field) return field;
            } catch (_) {}
        }
        return null;
    }

    function applyServerErrors() {
        const node = document.getElementById('globalValidationErrors');
        if (!node) return;
        let errors = {};
        try { errors = JSON.parse(node.textContent || '{}'); } catch (_) { return; }
        let first = null;
        Object.entries(errors).forEach(([key, messages]) => {
            const field = findFieldByErrorKey(key);
            if (!field) return;
            const message = Array.isArray(messages) ? messages[0] : messages;
            showHint(field, message || errorMessage(field));
            first ||= field;
        });
        if (first) {
            window.setTimeout(() => first.scrollIntoView({ behavior: 'smooth', block: 'center' }), 100);
        }
    }

    document.addEventListener('invalid', function (event) {
        const field = event.target;
        if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) return;
        showHint(field, errorMessage(field));
    }, true);

    document.addEventListener('input', function (event) {
        const field = event.target;
        if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) return;
        if (field.validity?.valid) removeHint(field);
    });

    document.addEventListener('change', function (event) {
        const field = event.target;
        if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) return;
        if (field.validity?.valid) removeHint(field);
    });

    document.addEventListener('DOMContentLoaded', function () {
        installStyle();
        applyServerErrors();
    });
})();
