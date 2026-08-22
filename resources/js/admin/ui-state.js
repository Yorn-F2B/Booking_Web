const STORAGE_PREFIX = 'admin-ui-scroll:';
const SIDEBAR_KEY = 'admin-ui-sidebar-scroll';
const MAX_AGE = 60_000;

document.addEventListener('DOMContentLoaded', () => {
    if (!document.body.classList.contains('admin-page')) return;

    restoreScrollPosition();
    bindScrollPersistence();
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

function getScrollableSidebar() {
    const candidates = [...document.querySelectorAll('.admin-nav, .admin-sidebar')];
    return candidates.find((element) => element.scrollHeight > element.clientHeight) || candidates[0] || null;
}
