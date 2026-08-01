document.addEventListener('DOMContentLoaded', () => {
    if (!window.Echo) {
        return;
    }

    let dirty = false;
    let refreshTimer = null;

    document.addEventListener('input', (event) => {
        if (event.isTrusted && event.target.closest('form')) dirty = true;
    });
    document.addEventListener('change', (event) => {
        if (event.isTrusted && event.target.closest('form')) dirty = true;
    });
    document.addEventListener('submit', () => {
        dirty = false;
    });

    const scheduleRefresh = () => {
        refreshMenuBadges();

        // Trang có cơ chế cập nhật riêng tự xử lý realtime, không reload và không hiện thông báo chung.
        if (document.body.matches('[data-realtime-local-only]')) {
            return;
        }
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => {
            if (mustRefreshManually(dirty)) {
                showRefreshButton();
                return;
            }

            window.location.reload();
        }, 900);
    };

    if (document.body.classList.contains('admin-page')) {
        window.Echo.private('admin.realtime')
            .listen('.app.updated', scheduleRefresh);

        ['booking:updated', 'room:updated', 'inspection:updated'].forEach((name) => {
            window.addEventListener(name, () => {
                if (!isSpecializedBookingIndex()) scheduleRefresh();
            });
        });
    } else {
        window.Echo.channel('site.realtime')
            .listen('.app.updated', scheduleRefresh);

        window.addEventListener('customer-booking:updated', scheduleRefresh);
    }
});

let menuBadgeRefreshing = false;

async function refreshMenuBadges() {
    if (menuBadgeRefreshing || !document.body.classList.contains('admin-page')) return;

    const currentBadges = document.querySelectorAll('[data-realtime-menu-count]');
    if (!currentBadges.length) return;

    menuBadgeRefreshing = true;

    try {
        const response = await fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            cache: 'no-store',
            credentials: 'same-origin',
        });

        if (!response.ok) return;

        const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');

        currentBadges.forEach((badge) => {
            const key = badge.dataset.realtimeMenuCount;
            const nextBadge = nextDocument.querySelector(`[data-realtime-menu-count="${key}"]`);
            if (!nextBadge) return;

            const count = Math.max(0, Number.parseInt(nextBadge.textContent, 10) || 0);
            const previous = Math.max(0, Number.parseInt(badge.textContent, 10) || 0);

            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = count < 1;

            if (count > previous) {
                badge.animate(
                    [
                        { transform: 'scale(.8)' },
                        { transform: 'scale(1.2)' },
                        { transform: 'scale(1)' },
                    ],
                    { duration: 420, easing: 'ease-out' },
                );
            }
        });
    } catch (error) {
        console.warn('Không thể cập nhật số thông báo trên menu.', error);
    } finally {
        menuBadgeRefreshing = false;
    }
}

function mustRefreshManually(dirty) {
    if (dirty || document.body.matches('[data-realtime-manual]')) return true;
    if (document.querySelector('.modal.show, .offcanvas.show, [role="dialog"][open]')) return true;

    const path = window.location.pathname;
    if (/(\/create|\/edit)(\/|$)/.test(path)) return true;

    const active = document.activeElement;
    return Boolean(active && active.closest('form') && /INPUT|TEXTAREA|SELECT/.test(active.tagName));
}

function isSpecializedBookingIndex() {
    return window.location.pathname.replace(/\/+$/, '') === '/admin/bookings';
}

function showRefreshButton() {
    if (document.querySelector('[data-realtime-refresh]')) return;

    const notice = document.createElement('div');
    notice.dataset.realtimeRefresh = 'true';
    notice.setAttribute('role', 'status');
    notice.style.cssText = [
        'position:fixed', 'right:20px', 'bottom:20px', 'z-index:100000',
        'display:flex', 'align-items:center', 'gap:12px', 'padding:10px 12px',
        'border:1px solid #d8dee9', 'border-radius:12px', 'background:#fff',
        'box-shadow:0 14px 36px rgba(15,23,42,.18)', 'color:#0f172a',
        'font:600 14px/1.4 system-ui,sans-serif',
    ].join(';');

    const label = document.createElement('span');
    label.textContent = 'Có dữ liệu mới';

    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = 'Cập nhật';
    button.style.cssText = [
        'border:0', 'border-radius:8px', 'padding:8px 12px',
        'background:#0b1d38', 'color:#fff', 'font:700 13px system-ui,sans-serif',
    ].join(';');
    button.addEventListener('click', () => window.location.reload());

    notice.append(label, button);
    document.body.appendChild(notice);
}
