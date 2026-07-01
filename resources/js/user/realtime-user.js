import '../bootstrap';

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
                showUserToast(getCustomerBookingMessage(event));
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

function showUserToast(message) {
    let wrapper = document.querySelector('[data-user-realtime-toast-wrapper]');

    if (!wrapper) {
        wrapper = document.createElement('div');
        wrapper.setAttribute('data-user-realtime-toast-wrapper', 'true');
        wrapper.style.position = 'fixed';
        wrapper.style.right = '18px';
        wrapper.style.bottom = '18px';
        wrapper.style.zIndex = '99999';
        wrapper.style.display = 'flex';
        wrapper.style.flexDirection = 'column';
        wrapper.style.gap = '10px';
        document.body.appendChild(wrapper);
    }

    const toast = document.createElement('div');
    toast.style.minWidth = '260px';
    toast.style.maxWidth = '360px';
    toast.style.padding = '12px 14px';
    toast.style.borderRadius = '14px';
    toast.style.background = '#111827';
    toast.style.color = '#fff';
    toast.style.boxShadow = '0 12px 32px rgba(0,0,0,.22)';
    toast.style.fontSize = '14px';
    toast.style.lineHeight = '1.45';
    toast.textContent = message;

    wrapper.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 4500);
}

function truncate(value, limit = 80) {
    value = String(value ?? '');
    return value.length > limit ? value.slice(0, limit) + '...' : value;
}
