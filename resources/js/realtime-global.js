document.addEventListener('DOMContentLoaded', () => {
    if (!window.Echo) {
        return;
    }

    let dirty = false;
    let refreshTimer = null;
    let hiddenAt = document.hidden ? Date.now() : null;

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

        // Danh sách booking có refresh fragment riêng: không full reload làm mất filter/scroll.
        if (isSpecializedBookingIndex()) {
            window.dispatchEvent(new CustomEvent('booking-index:refresh-requested'));
            return;
        }

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

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            hiddenAt = Date.now();
            return;
        }

        const hiddenFor = hiddenAt ? Date.now() - hiddenAt : 0;
        hiddenAt = null;
        refreshMenuBadges();

        // Sau khi quay lại tab đủ lâu, chủ động đồng bộ state để bù event có thể bỏ lỡ lúc reconnect.
        if (hiddenFor >= 30000) {
            scheduleRefresh();
        }
    });

    window.addEventListener('online', () => {
        // Echo/Reverb tự reconnect; đồng bộ lại dữ liệu sau khi mạng trở lại.
        scheduleRefresh();
    });

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
    if (document.querySelector('[data-realtime-refresh-pending]')) return;

    const marker = document.createElement('span');
    marker.dataset.realtimeRefreshPending = 'true';
    marker.hidden = true;
    document.body.appendChild(marker);

    const options = {
        title: 'Có dữ liệu mới',
        duration: 9000,
        actionLabel: 'Cập nhật',
        onAction: () => window.location.reload(),
    };

    const done = () => window.setTimeout(() => marker.remove(), 9500);

    if (window.AppToast && typeof window.AppToast.info === 'function') {
        window.AppToast.info('Trang có dữ liệu mới. Bạn có thể cập nhật sau khi hoàn tất nội dung đang nhập.', options);
        done();
        return;
    }

    window.__appToastQueue = window.__appToastQueue || [];
    window.__appToastQueue.push({
        message: 'Trang có dữ liệu mới. Bạn có thể cập nhật sau khi hoàn tất nội dung đang nhập.',
        type: 'info',
        options,
    });
    done();
}

