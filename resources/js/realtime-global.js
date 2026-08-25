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

    const scheduleRefresh = (payload = null) => {
        // Chat có kênh realtime riêng. Không fetch lại toàn bộ HTML / reload / toast
        // chỉ vì tin nhắn, assignment hoặc conversation thay đổi. Chỉ lấy đúng số
        // unread nhỏ cho badge menu.
        const realtimeResource = payload && typeof payload === 'object' ? payload.resource : null;
        const chatOnlyResources = [
            'chat_message',
            'chat_conversation',
            'chat_assignment_log',
            'chat_attachment',
            'chat_staff_presence',
        ];

        // Chat/presence được cập nhật cục bộ. Không được reload cả trang chỉ vì
        // heartbeat, tin nhắn hoặc lịch sử phân chat thay đổi.
        if (chatOnlyResources.includes(realtimeResource)) {
            refreshChatUnreadBadge();
            return;
        }

        // Việc tự cân bằng booking + chat có thể thay owner nhưng không phải lý do
        // để reload form đang làm. Booking List có fragment refresh riêng.
        if (realtimeResource === 'booking_staff_assignment') {
            refreshChatUnreadBadge();
            refreshMenuBadges();
            if (isSpecializedBookingIndex()) {
                window.dispatchEvent(new CustomEvent('booking-index:refresh-requested'));
            }
            return;
        }

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
                // Không chen toast "Có dữ liệu mới" vào lúc người dùng đang nhập
                // và tuyệt đối không reload làm mất dữ liệu form. Lần thao tác kế
                // tiếp/backend vẫn revalidate dữ liệu trước khi ghi.
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

        // Echo/Reverb tự reconnect. Khi quay lại tab chỉ đồng bộ các badge/fragment
        // nhỏ; không reload cả trang chỉ vì tab từng bị ẩn, tránh mất vị trí/biểu mẫu.
        if (hiddenFor >= 30000 && isSpecializedBookingIndex()) {
            window.dispatchEvent(new CustomEvent('booking-index:refresh-requested'));
        }
    });

    window.addEventListener('online', () => {
        // Sau khi mạng trở lại, Reverb tự reconnect. Chỉ làm mới dữ liệu phụ trợ;
        // event nghiệp vụ thật sẽ tự quyết định fragment nào cần cập nhật.
        refreshMenuBadges();
        if (isSpecializedBookingIndex()) {
            window.dispatchEvent(new CustomEvent('booking-index:refresh-requested'));
        }
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

let chatBadgeRefreshing = false;

async function refreshChatUnreadBadge() {
    if (chatBadgeRefreshing || !document.body.classList.contains('admin-page')) return;

    const badge = document.querySelector('[data-realtime-menu-count="unread-chats"]');
    const url = badge?.dataset.chatUnreadUrl;
    if (!badge || !url) return;

    chatBadgeRefreshing = true;
    try {
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store',
            credentials: 'same-origin',
        });
        if (!response.ok) return;
        const payload = await response.json();
        const count = Math.max(0, Number.parseInt(payload.count, 10) || 0);
        const previous = Math.max(0, Number.parseInt(badge.textContent, 10) || 0);
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.hidden = count < 1;
        if (count > previous) {
            badge.animate(
                [{ transform: 'scale(.8)' }, { transform: 'scale(1.2)' }, { transform: 'scale(1)' }],
                { duration: 420, easing: 'ease-out' },
            );
        }
    } catch (error) {
        console.debug('Không thể cập nhật badge chat.', error);
    } finally {
        chatBadgeRefreshing = false;
    }
}

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
