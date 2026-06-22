import '../bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    if (!window.Echo) {
        console.warn('Laravel Echo chưa sẵn sàng.');
        return;
    }

    console.log('Đã kết nối Echo, đang nghe admin.realtime...');

    window.Echo.private('admin.realtime')
        .listen('.booking.updated', (event) => {
            console.log('Booking realtime:', event);

            window.dispatchEvent(new CustomEvent('booking:updated', {
                detail: event,
            }));

            handleBookingUpdated(event);
        })
        .listen('.room.updated', (event) => {
            console.log('Room realtime:', event);

            window.dispatchEvent(new CustomEvent('room:updated', {
                detail: event,
            }));

            handleRoomUpdated(event);
        })
        .error((error) => {
            console.error('Lỗi khi nghe kênh admin.realtime:', error);
        });
});

function handleBookingUpdated(event) {
    updateNewBookingBadge(event);
    updateBookingRow(event);
    showRealtimeToast(getBookingMessage(event), 'booking');
}

function handleRoomUpdated(event) {
    updateRoomCard(event);
    showRealtimeToast(
        `Phòng ${event.room_number} vừa chuyển sang: ${event.status_label}`,
        'room'
    );
}

function updateNewBookingBadge(event) {
    if (event.action !== 'created') return;

    const badge = document.querySelector('[data-new-booking-badge]');
    const reloadButton = document.querySelector('[data-new-booking-reload]');

    if (!badge) return;

    const currentCount = Number(badge.dataset.count || 0);
    const nextCount = currentCount + 1;

    badge.dataset.count = String(nextCount);
    badge.textContent = `${nextCount} đơn mới`;
    badge.classList.remove('d-none');

    if (reloadButton) {
        reloadButton.classList.remove('d-none');
    }

    if (document.title && !document.title.startsWith('(')) {
        document.title = `(${nextCount}) ${document.title}`;
    }
}

function updateBookingRow(event) {
    const row = document.querySelector(`[data-booking-id="${event.id}"]`);

    if (!row) return;

    const statusEl = row.querySelector('[data-booking-status]');
    const paymentEl = row.querySelector('[data-booking-payment-status]');
    const totalEl = row.querySelector('[data-booking-total]');

    if (statusEl) statusEl.textContent = event.status_label;
    if (paymentEl) paymentEl.textContent = event.payment_status_label;
    if (totalEl) totalEl.textContent = event.estimated_total_text;

    row.classList.add('realtime-updated');

    setTimeout(() => {
        row.classList.remove('realtime-updated');
    }, 2500);
}

function updateRoomCard(event) {
    const card = document.querySelector(`[data-room-id="${event.id}"]`);

    if (!card) return;

    card.dataset.status = event.status;

    const statusEl = card.querySelector('[data-room-status]');
    if (statusEl) {
        statusEl.textContent = event.status_label;
    }

    card.classList.add('realtime-updated');

    setTimeout(() => {
        card.classList.remove('realtime-updated');
    }, 2500);
}

function getBookingMessage(event) {
    const code = event.booking_code || `#${event.id}`;

    switch (event.action) {
        case 'created':
            return `Có đơn đặt phòng mới ${code}`;
        case 'cancelled':
        case 'canceled':
            return `Booking ${code} vừa bị hủy`;
        case 'checked_in':
            return `Booking ${code} đã check-in`;
        case 'checked_out':
        case 'completed':
            return `Booking ${code} đã hoàn tất/trả phòng`;
        case 'payment_updated':
            return `Booking ${code} vừa cập nhật thanh toán`;
        case 'service_updated':
            return `Booking ${code} vừa cập nhật dịch vụ`;
        case 'room_changed':
            return `Booking ${code} vừa đổi/gán phòng`;
        default:
            return `Booking ${code} vừa được cập nhật`;
    }
}

function showRealtimeToast(message, type = 'info') {
    let wrapper = document.querySelector('[data-realtime-toast-wrapper]');

    if (!wrapper) {
        wrapper = document.createElement('div');
        wrapper.setAttribute('data-realtime-toast-wrapper', 'true');
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
    toast.style.borderRadius = '12px';
    toast.style.background = '#111827';
    toast.style.color = '#fff';
    toast.style.boxShadow = '0 12px 30px rgba(0, 0, 0, 0.2)';
    toast.style.fontSize = '14px';
    toast.style.lineHeight = '1.4';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(8px)';
    toast.style.transition = 'all .2s ease';

    toast.innerHTML = `
        <div style="font-weight: 700; margin-bottom: 3px;">
            ${type === 'room' ? 'Cập nhật phòng' : 'Cập nhật booking'}
        </div>
        <div>${escapeHtml(message)}</div>
    `;

    wrapper.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';

        setTimeout(() => {
            toast.remove();
        }, 250);
    }, 4500);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}