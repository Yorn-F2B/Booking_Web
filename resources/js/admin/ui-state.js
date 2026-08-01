const STORAGE_PREFIX = 'admin-ui-scroll:';
const SIDEBAR_KEY = 'admin-ui-sidebar-scroll';
const MAX_AGE = 60_000;

document.addEventListener('DOMContentLoaded', () => {
    if (!document.body.classList.contains('admin-page')) return;

    restoreScrollPosition();
    bindScrollPersistence();
    showServerToasts();
});

function pageStorageKey() {
    return STORAGE_PREFIX + window.location.pathname + window.location.search;
}

function bindScrollPersistence() {
    const save = () => {
        sessionStorage.setItem(pageStorageKey(), JSON.stringify({
            y: window.scrollY,
            at: Date.now(),
        }));

        const sidebar = getScrollableSidebar();
        if (sidebar) {
            sessionStorage.setItem(SIDEBAR_KEY, String(sidebar.scrollTop));
        }
    };

    document.addEventListener('submit', save, true);
    document.addEventListener('change', (event) => {
        if (event.target.closest('form')) save();
    }, true);
    window.addEventListener('beforeunload', save);
}

function restoreScrollPosition() {
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';

    const raw = sessionStorage.getItem(pageStorageKey());
    const sidebarTop = Number.parseInt(sessionStorage.getItem(SIDEBAR_KEY), 10);

    const restore = () => {
        if (!window.location.hash && raw) {
            try {
                const position = JSON.parse(raw);
                if (Date.now() - position.at <= MAX_AGE) {
                    window.scrollTo(0, position.y || 0);
                }
            } catch {
                sessionStorage.removeItem(pageStorageKey());
            }
        }

        const sidebar = getScrollableSidebar();
        if (sidebar && Number.isFinite(sidebarTop)) sidebar.scrollTop = sidebarTop;
    };

    requestAnimationFrame(() => {
        restore();
        setTimeout(restore, 120);
    });
}

function showServerToasts() {
    const flashItems = [...document.querySelectorAll('[data-admin-flash]')];
    if (!flashItems.length) return;

    const messages = flashItems.map((item) => normalizeText(item.textContent)).filter(Boolean);
    removeMatchingInlineAlerts(messages);

    const wrapper = document.createElement('div');
    wrapper.className = 'admin-toast-stack';
    wrapper.setAttribute('aria-live', 'polite');
    document.body.appendChild(wrapper);

    flashItems.forEach((item) => {
        createToast(wrapper, item.dataset.type || 'info', item.textContent.trim());
        item.remove();
    });
}

function removeMatchingInlineAlerts(messages) {
    document.querySelectorAll('.alert, .rm-alert').forEach((alert) => {
        const text = normalizeText(alert.textContent);
        if (messages.some((message) => text === message || text.includes(message))) {
            alert.remove();
        }
    });
}

function createToast(wrapper, type, message) {
    const toast = document.createElement('section');
    toast.className = `admin-toast admin-toast-${type}`;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

    const icon = document.createElement('i');
    icon.className = `bx ${type === 'success' ? 'bx-check-circle' : type === 'error' ? 'bx-error-circle' : type === 'warning' ? 'bx-error' : 'bx-info-circle'}`;

    const text = document.createElement('div');
    text.className = 'admin-toast-message';
    text.textContent = message;

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'admin-toast-close';
    close.setAttribute('aria-label', 'Đóng thông báo');
    close.innerHTML = '&times;';

    let timer = setTimeout(remove, 15_000);
    const progress = document.createElement('span');
    progress.className = 'admin-toast-progress';

    close.addEventListener('click', remove);
    toast.addEventListener('mouseenter', () => {
        clearTimeout(timer);
        progress.style.animationPlayState = 'paused';
    });
    toast.addEventListener('mouseleave', () => {
        timer = setTimeout(remove, 5_000);
        progress.style.animationPlayState = 'running';
    });

    toast.append(icon, text, close, progress);
    wrapper.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('is-visible'));

    function remove() {
        clearTimeout(timer);
        toast.classList.remove('is-visible');
        setTimeout(() => {
            toast.remove();
            if (!wrapper.children.length) wrapper.remove();
        }, 220);
    }
}

function normalizeText(value) {
    return String(value || '').replace(/\s+/g, ' ').trim();
}

function getScrollableSidebar() {
    const candidates = [...document.querySelectorAll('.admin-nav, .admin-sidebar')];
    return candidates.find((element) => element.scrollHeight > element.clientHeight) || candidates[0] || null;
}
