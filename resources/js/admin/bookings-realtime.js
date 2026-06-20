import '../bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const badge = document.querySelector('[data-new-booking-badge]');
    const list = document.querySelector('[data-new-booking-list]');
    const reloadButton = document.querySelector('[data-new-booking-reload]');

    if (!badge || !list) {
        console.warn('Không tìm thấy vùng hiển thị booking realtime.');
        return;
    }

    if (!window.Echo) {
        console.warn('Laravel Echo chưa sẵn sàng.');
        return;
    }

    console.log('Đã kết nối Echo, đang nghe kênh admin.bookings...');

    window.Echo.private('admin.bookings')
        .listen('.booking.created', (event) => {
            console.log('Nhận booking realtime:', event);

            const currentCount = Number(badge.dataset.count || 0);
            const nextCount = currentCount + 1;

            badge.dataset.count = String(nextCount);
            badge.textContent = `${nextCount} đơn mới`;
            badge.classList.remove('d-none');

            if (reloadButton) {
                reloadButton.classList.remove('d-none');
            }

            const item = document.createElement('div');
            item.className = 'booking-realtime-item';
            item.innerHTML = `
                <div>
                    <div class="booking-realtime-title">
                        ${escapeHtml(event.booking_code)}
                    </div>
                    <div class="booking-realtime-text">
                        ${escapeHtml(event.customer_name)} · ${escapeHtml(event.customer_phone)}
                    </div>
                    <div class="booking-realtime-text">
                        ${escapeHtml(event.room_category)}
                        ${event.room_numbers ? ' · Phòng ' + escapeHtml(event.room_numbers) : ''}
                    </div>
                    <div class="booking-realtime-time">
                        ${escapeHtml(event.created_at)}
                    </div>
                </div>

                <a href="${event.url}" class="btn btn-sm btn-primary">
                    Xem
                </a>
            `;

            list.prepend(item);

            if (document.title && !document.title.startsWith('(')) {
                document.title = `(${nextCount}) ${document.title}`;
            }
        })
        .error((error) => {
            console.error('Lỗi khi nghe private channel admin.bookings:', error);
        });

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
});