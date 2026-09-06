(function () {
    'use strict';

    const DEFAULT_DURATION = 6500;
    const TITLES = {
        success: 'Thành công',
        error: 'Có lỗi xảy ra',
        warning: 'Cảnh báo',
        info: 'Thông báo',
    };
    const ICONS = {
        success: '✓',
        error: '!',
        warning: '!',
        info: 'i',
    };
    const recentDedupeKeys = new Map();

    function normalizeText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function ensureStack() {
        let stack = document.querySelector('[data-app-toast-stack]');
        if (stack) return stack;

        stack = document.createElement('div');
        stack.className = 'app-toast-stack';
        stack.dataset.appToastStack = 'true';
        stack.setAttribute('aria-live', 'polite');
        stack.setAttribute('aria-atomic', 'false');
        document.body.appendChild(stack);
        return stack;
    }

    function removeMatchingInlineAlerts(message) {
        const target = normalizeText(message);
        if (!target) return;

        document.querySelectorAll('.alert, .rm-alert, [data-flash-inline]').forEach((element) => {
            const text = normalizeText(element.textContent);
            if (!text) return;
            if (text === target || text.includes(target) || target.includes(text)) {
                element.remove();
            }
        });
    }

    function show(message, type, options) {
        const displayText = String(message || '').trim();
        const text = normalizeText(displayText);
        if (!text || !document.body) return null;

        type = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
        options = options || {};
        const duration = Math.max(1800, Number(options.duration || DEFAULT_DURATION));
        const stack = ensureStack();
        const dedupeKey = normalizeText(options.dedupeKey || '');
        const now = Date.now();

        for (const [key, expiresAt] of recentDedupeKeys.entries()) {
            if (expiresAt <= now) recentDedupeKeys.delete(key);
        }
        if (dedupeKey && recentDedupeKeys.has(dedupeKey)) return null;

        const duplicate = Array.from(stack.querySelectorAll('.app-toast-message'))
            .find((node) => normalizeText(node.textContent) === text);
        if (duplicate) return duplicate.closest('.app-toast');
        if (dedupeKey) recentDedupeKeys.set(dedupeKey, now + Math.max(duration, 8000));

        const toast = document.createElement('section');
        toast.className = 'app-toast app-toast-' + type;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.style.setProperty('--toast-duration', duration + 'ms');

        const icon = document.createElement('span');
        icon.className = 'app-toast-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = ICONS[type];

        const copy = document.createElement('div');
        copy.className = 'app-toast-copy';

        const title = document.createElement('p');
        title.className = 'app-toast-title';
        title.textContent = options.title || TITLES[type];

        const messageNode = document.createElement('p');
        messageNode.className = 'app-toast-message';
        // Giữ xuống dòng của panel giao thông báo (email / web / khách / nội dung)
        // để nhân viên đọc nhanh; normalizeText chỉ dùng cho chống trùng.
        messageNode.textContent = displayText;

        let action = null;
        if (options.actionLabel && typeof options.onAction === 'function') {
            action = document.createElement('button');
            action.type = 'button';
            action.className = 'app-toast-action';
            action.textContent = String(options.actionLabel);
        }

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'app-toast-close';
        close.setAttribute('aria-label', 'Đóng thông báo');
        close.innerHTML = '&times;';

        const progress = document.createElement('span');
        progress.className = 'app-toast-progress';
        progress.setAttribute('aria-hidden', 'true');

        copy.append(title, messageNode);
        if (action) copy.appendChild(action);
        toast.append(icon, copy, close, progress);
        stack.appendChild(toast);

        let remaining = duration;
        let startedAt = Date.now();
        let timer = window.setTimeout(remove, remaining);

        function pause() {
            window.clearTimeout(timer);
            remaining = Math.max(0, remaining - (Date.now() - startedAt));
            progress.style.animationPlayState = 'paused';
        }

        function resume() {
            if (remaining <= 0) return remove();
            startedAt = Date.now();
            timer = window.setTimeout(remove, remaining);
            progress.style.animationPlayState = 'running';
        }

        function remove() {
            window.clearTimeout(timer);
            toast.classList.remove('is-visible');
            window.setTimeout(function () {
                toast.remove();
                if (!stack.children.length) stack.remove();
            }, 220);
        }

        close.addEventListener('click', remove);
        action?.addEventListener('click', function () {
            try { options.onAction(); } finally { remove(); }
        });
        toast.addEventListener('mouseenter', pause);
        toast.addEventListener('mouseleave', resume);
        toast.addEventListener('focusin', pause);
        toast.addEventListener('focusout', resume);

        requestAnimationFrame(function () {
            toast.classList.add('is-visible');
        });

        removeMatchingInlineAlerts(displayText);
        return toast;
    }

    function consumeServerFlashes() {
        document.querySelectorAll('[data-flash-inline]').forEach(function (element) {
            element.remove();
        });

        const items = Array.from(document.querySelectorAll('[data-app-flash]'));
        if (!items.length) return;

        items.forEach(function (item) {
            const options = {};
            if (item.dataset.title) options.title = item.dataset.title;
            if (item.dataset.duration) options.duration = Number(item.dataset.duration);
            if (item.dataset.dedupeKey) options.dedupeKey = item.dataset.dedupeKey;

            show(item.textContent, item.dataset.type || 'info', options);
            item.remove();
        });

        document.querySelectorAll('[data-app-flash-source]').forEach(function (source) {
            source.remove();
        });
    }

    window.AppToast = {
        show: show,
        success: function (message, options) { return show(message, 'success', options); },
        error: function (message, options) { return show(message, 'error', options); },
        warning: function (message, options) { return show(message, 'warning', options); },
        info: function (message, options) { return show(message, 'info', options); },
    };

    const queued = Array.isArray(window.__appToastQueue) ? window.__appToastQueue.splice(0) : [];
    queued.forEach(function (item) {
        show(item.message, item.type || 'info', item.options || {});
    });

    // Tương thích với JS build cũ còn cache: nếu một module cũ cố dựng
    // thông báo góc phải bên dưới, chuyển nội dung sang AppToast góc trên
    // rồi xóa container legacy. Nhờ vậy không cần đồng thời hiển thị 2 kiểu toast.
    function migrateLegacyNotifications(root) {
        if (!(root instanceof Element)) return;

        const refreshNodes = [];
        if (root.matches('[data-realtime-refresh]')) refreshNodes.push(root);
        root.querySelectorAll?.('[data-realtime-refresh]').forEach(node => refreshNodes.push(node));
        refreshNodes.forEach(function (notice) {
            if (!notice.isConnected) return;
            // Build cũ của realtime-global từng tạo thông báo "Có dữ liệu mới"
            // cho heartbeat/chat/assignment. Cơ chế mới đã cập nhật cục bộ, vì vậy
            // nếu trình duyệt còn cache module cũ thì chỉ loại bỏ notice legacy,
            // không biến nó thành toast và không cho phép nó kéo người dùng reload.
            notice.remove();
        });

        const containers = [];
        if (root.matches('[data-realtime-toast-wrapper], #toast-container')) containers.push(root);
        root.querySelectorAll?.('[data-realtime-toast-wrapper], #toast-container').forEach(node => containers.push(node));
        containers.forEach(function (container) {
            if (!container.isConnected) return;
            Array.from(container.children).forEach(function (legacyToast) {
                const text = normalizeText(legacyToast.textContent);
                if (text) show(text, 'info');
            });
            container.remove();
        });
    }

    function installLegacyNotificationGuard() {
        if (!document.body || window.__appLegacyToastGuardInstalled) return;
        window.__appLegacyToastGuardInstalled = true;
        migrateLegacyNotifications(document.body);

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) migrateLegacyNotifications(node);
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', installLegacyNotificationGuard, { once: true });
    } else {
        installLegacyNotificationGuard();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', consumeServerFlashes, { once: true });
    } else {
        consumeServerFlashes();
    }
})();
