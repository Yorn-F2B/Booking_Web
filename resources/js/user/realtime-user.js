document.addEventListener('DOMContentLoaded', () => {
    if (!window.Echo) {
        return;
    }

    const customerId = document.querySelector('meta[name="customer-id"]')?.content;
    const userId = document.querySelector('meta[name="auth-user-id"]')?.content;

    if (customerId) {
        window.Echo.private(`customer.${customerId}`)
            .listen('.booking.updated', (event) => {
                window.dispatchEvent(new CustomEvent('customer-booking:updated', { detail: event }));
                const actorUserId = Number(event.actor_user_id || 0);
                if (!userId || !actorUserId || Number(userId) !== actorUserId) {
                    showUserToast(getCustomerBookingMessage(event), `booking:${event.id}:${event.action}`);
                }
                updateCustomerBookingView(event);
            })
            .error((error) => console.error('Lỗi realtime khách:', error));
    }

    if (userId) {
        window.Echo.private(`chat.customer.${userId}`)
            .listen('.chat.message.sent', (event) => {
                if (event.sender_type === 'customer') return;

                window.dispatchEvent(new CustomEvent('customer-chat:message-sent', { detail: event }));
                showUserToast(`Nhân viên vừa nhắn: ${truncate(event.message, 70)}`);
            })
            .error((error) => console.error('Lỗi realtime chat khách:', error));
    }
});

function updateCustomerBookingView(event) {
    const wrapper = document.querySelector(`[data-customer-booking-id="${event.id}"]`);

    if (!wrapper) return;

    const statusEl = wrapper.querySelector('[data-booking-status]');
    const paymentEl = wrapper.querySelector('[data-booking-payment-status]');
    const totalEl = wrapper.querySelector('[data-booking-total]');

    if (statusEl) statusEl.textContent = event.status_label;
    if (paymentEl) paymentEl.textContent = event.payment_status_label;
    if (totalEl) totalEl.textContent = event.estimated_total_text;

    wrapper.classList.add('realtime-updated');
    setTimeout(() => wrapper.classList.remove('realtime-updated'), 2500);
}

function getCustomerBookingMessage(event) {
    const code = event.booking_code || `#${event.id}`;

    switch (event.action) {
        case 'payment_updated':
            return `Booking ${code} đã cập nhật thanh toán`;
        case 'checked_in':
            return `Booking ${code} đã được check-in`;
        case 'inspection_requested':
            return `Booking ${code} đang chờ kiểm tra phòng`;
        case 'completed':
        case 'checked_out':
            return `Booking ${code} đã hoàn tất`;
        case 'cancelled':
        case 'canceled':
            return `Booking ${code} đã bị hủy`;
        case 'service_updated':
            return `Booking ${code} đã cập nhật dịch vụ`;
        case 'total_updated':
            return `Booking ${code} đã cập nhật tổng tiền`;
        default:
            return `Booking ${code} vừa được cập nhật`;
    }
}

function showUserToast(message, dedupeKey = '') {
    if (window.AppToast && typeof window.AppToast.info === 'function') {
        window.AppToast.info(message, { title: 'Cập nhật booking', duration: 4500, dedupeKey });
        return;
    }

    window.__appToastQueue = window.__appToastQueue || [];
    window.__appToastQueue.push({ message, type: 'info', options: { title: 'Cập nhật booking', duration: 4500, dedupeKey } });
}

function truncate(value, limit = 80) {
    value = String(value ?? '');
    return value.length > limit ? value.slice(0, limit) + '...' : value;
}
