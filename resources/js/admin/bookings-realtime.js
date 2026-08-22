document.addEventListener('DOMContentLoaded', () => {
    if (!document.body.classList.contains('admin-page')) {
        return;
    }

    if (!window.Echo) {
        console.warn('Laravel Echo chưa sẵn sàng.');
        return;
    }

    const role = document.body.dataset.authUserRole || '';
    const userId = Number(document.body.dataset.authUserId || 0);
    const bookingChannels = [];

    const listenBookingChannel = (channelName) => {
        if (!channelName || bookingChannels.includes(channelName)) return;
        bookingChannels.push(channelName);
        window.Echo.private(channelName)
            .listen('.booking.updated', (event) => {
                window.dispatchEvent(new CustomEvent('booking:updated', { detail: event }));
                handleBookingUpdated(event);
            })
            .error((error) => console.error(`Lỗi realtime ${channelName}:`, error));
    };

    if (['super_admin', 'manager', 'receptionist_lead'].includes(role)) {
        listenBookingChannel('admin.bookings.supervisors');
    } else if (role === 'receptionist' && userId > 0) {
        listenBookingChannel(`admin.bookings.staff.${userId}`);
        listenBookingChannel('admin.bookings.unassigned');
    }

    if (['super_admin', 'manager', 'receptionist_lead', 'receptionist', 'housekeeping_supervisor'].includes(role)) {
        window.Echo.private('admin.rooms.operations')
            .listen('.room.updated', (event) => {
                window.dispatchEvent(new CustomEvent('room:updated', { detail: event }));
                handleRoomUpdated(event);
            })
            .error((error) => console.error('Lỗi realtime phòng:', error));
    }

    if (['super_admin', 'manager', 'receptionist_lead', 'housekeeping_supervisor'].includes(role)) {
        window.Echo.private('admin.inspections.supervisors')
            .listen('.inspection.updated', (event) => {
                window.dispatchEvent(new CustomEvent('inspection:updated', { detail: event }));
                handleInspectionUpdated(event);
            })
            .error((error) => console.error('Lỗi realtime kiểm phòng:', error));
    }
});

let bookingIndexRefreshTimer = null;
let bookingIndexRefreshing = false;

window.addEventListener('booking-index:refresh-requested', () => {
    if (isAdminBookingsIndexPage()) {
        scheduleBookingIndexRefresh();
    }
});

function handleBookingUpdated(event) {
    updateBookingBadge(event);
    updateBookingRow(event);

    const currentUserId = Number(document.body.dataset.authUserId || 0);
    const actorUserId = Number(event.actor_user_id || 0);
    if (!currentUserId || !actorUserId || currentUserId !== actorUserId) {
        showRealtimeToast(getBookingMessage(event), 'Booking', `booking:${event.id}:${event.action}`);
    }

    if (isAdminBookingsIndexPage()) {
        scheduleBookingIndexRefresh();
    }
}

function handleRoomUpdated(event) {
    updateRoomCard(event);

    if (event.room_number) {
        showRealtimeToast(`Phòng ${event.room_number} vừa cập nhật: ${event.status_label}`, 'Phòng');
    }
}

function handleInspectionUpdated(event) {
    showRealtimeToast(
        `Phiếu kiểm tra phòng ${event.room_number || ''} vừa cập nhật: ${event.status_label || 'Đã cập nhật'}`,
        'Kiểm tra phòng'
    );

    if (isAdminBookingsIndexPage()) {
        scheduleBookingIndexRefresh();
    }
}

function handleChatMessageSent(event) {
    if (event.sender_type === 'staff') {
        return;
    }

    showRealtimeToast(`Khách vừa nhắn: ${truncate(event.message, 70)}`, 'Chat');
}

function isAdminBookingsIndexPage() {
    const path = window.location.pathname.replace(/\/+$/, '');
    return path === '/admin/bookings';
}

function scheduleBookingIndexRefresh() {
    clearTimeout(bookingIndexRefreshTimer);

    bookingIndexRefreshTimer = setTimeout(() => {
        refreshBookingIndexFragment();
    }, 500);
}

async function refreshBookingIndexFragment() {
    if (bookingIndexRefreshing) {
        return;
    }

    bookingIndexRefreshing = true;

    try {
        const response = await fetch(window.location.href, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html, application/xhtml+xml',
            },
            cache: 'no-store',
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`Không tải lại được danh sách booking. HTTP ${response.status}`);
        }

        const html = await response.text();
        const nextDocument = new DOMParser().parseFromString(html, 'text/html');

        if (!replaceBookingIndexContent(nextDocument)) {
            showRealtimeToast('Có thay đổi mới. Không tìm thấy vùng danh sách để tự cập nhật, bấm F5 nếu cần.', 'Realtime');
            return;
        }

        markBookingIndexRefreshed();
    } catch (error) {
        console.error('Lỗi tự cập nhật danh sách booking:', error);
        showRealtimeToast('Có thay đổi mới nhưng chưa tự tải lại được danh sách.', 'Realtime');
    } finally {
        bookingIndexRefreshing = false;
    }
}

function replaceBookingIndexContent(nextDocument) {
    const selectors = [
        '[data-bookings-index-fragment]',
        '#bookings-index-realtime-fragment',
        '[data-booking-index-fragment]',
        '.bookings-index-page',
        '.booking-index-page',
        '.booking-list-page',
        '.booking-table-wrapper',
        '.table-responsive',
        'main .container-fluid',
        'main .container',
        'main',
    ];

    for (const selector of selectors) {
        const current = document.querySelector(selector);
        const next = nextDocument.querySelector(selector);

        if (!current || !next) {
            continue;
        }

        current.innerHTML = next.innerHTML;
        return true;
    }

    const currentTable = document.querySelector('table');
    const nextTable = nextDocument.querySelector('table');

    if (currentTable && nextTable) {
        currentTable.innerHTML = nextTable.innerHTML;
        refreshPagination(nextDocument);
        return true;
    }

    return false;
}

function refreshPagination(nextDocument) {
    const currentPagination = document.querySelector('.pagination')?.closest('nav') || document.querySelector('.pagination');
    const nextPagination = nextDocument.querySelector('.pagination')?.closest('nav') || nextDocument.querySelector('.pagination');

    if (currentPagination && nextPagination) {
        currentPagination.innerHTML = nextPagination.innerHTML;
    }
}

function markBookingIndexRefreshed() {
    const target = document.querySelector('[data-bookings-index-fragment]')
        || document.querySelector('#bookings-index-realtime-fragment')
        || document.querySelector('table')
        || document.querySelector('main');

    if (!target) {
        return;
    }

    markUpdated(target);
}

function updateBookingBadge(event) {
    if (!['created', 'staff_created', 'walk_in_created', 'created_by_staff'].includes(event.action)) {
        return;
    }

    const badge = document.querySelector('[data-new-booking-badge]');
    const reloadButton = document.querySelector('[data-new-booking-reload]');

    if (!badge) return;

    const current = Number(badge.dataset.count || 0);
    const next = current + 1;

    badge.dataset.count = String(next);
    badge.textContent = `${next} đơn mới`;
    badge.classList.remove('d-none');

    if (reloadButton) {
        reloadButton.classList.remove('d-none');
    }
}

function updateBookingRow(event) {
    const row = document.querySelector(`[data-booking-id="${event.id}"]`);

    if (!row) return;

    const statusEl = row.querySelector('[data-booking-status]');
    const paymentEl = row.querySelector('[data-booking-payment-status]');
    const totalEl = row.querySelector('[data-booking-total]');
    const roomsEl = row.querySelector('[data-booking-rooms]');

    if (statusEl) statusEl.textContent = event.status_label;
    if (paymentEl) paymentEl.textContent = event.payment_status_label;
    if (totalEl) totalEl.textContent = event.estimated_total_text;
    if (roomsEl) roomsEl.textContent = event.room_numbers || 'Chưa gán';

    markUpdated(row);
}

function updateRoomCard(event) {
    const card = document.querySelector(`[data-room-id="${event.id}"]`);

    if (!card) return;

    card.dataset.status = event.status;

    const statusEl = card.querySelector('[data-room-status]');
    const statusLabelEl = card.querySelector('[data-room-status-label]');
    const categoryEl = card.querySelector('[data-room-category]');

    if (statusEl) {
        statusEl.className = `status-pill s-${event.status}`;
        statusEl.dataset.roomStatus = '';
    }
    if (statusLabelEl) statusLabelEl.textContent = event.status_label;
    else if (statusEl) statusEl.textContent = event.status_label;
    if (categoryEl) categoryEl.textContent = event.room_category;

    markUpdated(card);
}

function getBookingMessage(event) {
    const code = event.booking_code || `#${event.id}`;

    switch (event.action) {
        case 'created':
            return `Có đơn đặt phòng mới ${code}`;
        case 'staff_created':
        case 'created_by_staff':
            return `Lễ tân vừa tạo booking ${code}`;
        case 'walk_in_created':
            return `Lễ tân vừa tạo booking ở ngay ${code}`;
        case 'cancelled':
        case 'canceled':
            return `Booking ${code} vừa bị hủy`;
        case 'checked_in':
            return `Booking ${code} đã check-in`;
        case 'inspection_requested':
            return `Booking ${code} đã yêu cầu kiểm tra phòng`;
        case 'inspection_reported':
            return `Booking ${code} đã có báo cáo kiểm tra phòng`;
        case 'inspection_completed':
        case 'inspection_approved':
            return `Booking ${code} đã hoàn tất kiểm tra phòng`;
        case 'completed':
        case 'checked_out':
            return `Booking ${code} đã hoàn tất/trả phòng`;
        case 'payment_updated':
            return `Booking ${code} vừa cập nhật thanh toán`;
        case 'payment_failed':
            return `Thanh toán của booking ${code} thất bại`;
        case 'service_updated':
            return `Booking ${code} vừa cập nhật dịch vụ`;
        case 'promotion_updated':
            return `Booking ${code} vừa cập nhật ưu đãi`;
        case 'room_changed':
        case 'rooms_assigned':
            return `Booking ${code} vừa cập nhật phòng`;
        case 'extended':
            return `Đơn ${code} vừa được gia hạn lưu trú`;
        case 'room_issue_proposal_sent':
            return `Quản lý vừa gửi phương án sự cố mới cho đơn ${code}`;
        case 'room_issue_guest_updated':
            return `Khách/lễ tân vừa cập nhật lựa chọn sự cố của đơn ${code}`;
        case 'room_issue_finalized':
            return `Phương án sự cố của đơn ${code} đã được xác nhận`;
        default:
            return `Booking ${code} vừa được cập nhật`;
    }
}

function showRealtimeToast(message, title = 'Realtime', dedupeKey = '') {
    if (window.AppToast && typeof window.AppToast.info === 'function') {
        window.AppToast.info(message, { title, duration: 4500, dedupeKey });
        return;
    }

    window.__appToastQueue = window.__appToastQueue || [];
    window.__appToastQueue.push({ message, type: 'info', options: { title, duration: 4500, dedupeKey } });
}

function markUpdated(element) {
    element.classList.add('realtime-updated');
    setTimeout(() => element.classList.remove('realtime-updated'), 2500);
}

function truncate(value, limit = 80) {
    value = String(value ?? '');
    return value.length > limit ? value.slice(0, limit) + '...' : value;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
