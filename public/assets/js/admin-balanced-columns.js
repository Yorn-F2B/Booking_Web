(function () {
    'use strict';

    const BREAKPOINT = 1200;
    const STICKY_TOP = 82;
    const MIN_HEIGHT_GAP = 120;
    const ACTIVE_CLASS = 'admin-balanced-short-column';

    function clearColumn(el) {
        el.classList.remove(ACTIVE_CLASS);
        el.style.removeProperty('--admin-balanced-top');
    }

    function clearShell(shell) {
        shell.classList.remove('booking-shell--stacked-balance');
        const main = shell.querySelector(':scope > .main-stack');
        const side = shell.querySelector(':scope > .side-stack');
        if (main) clearColumn(main);
        if (side) clearColumn(side);
    }

    function balanceBookingShell(shell) {
        clearShell(shell);
        if (window.innerWidth < BREAKPOINT) return;

        const main = shell.querySelector(':scope > .main-stack');
        const side = shell.querySelector(':scope > .side-stack');
        if (!main || !side) return;

        const mainHeight = Math.ceil(main.scrollHeight);
        const sideHeight = Math.ceil(side.scrollHeight);
        const available = Math.max(320, window.innerHeight - STICKY_TOP - 18);

        // Giống booking-confirm: chỉ dùng một scrollbar của trang. Tuyệt đối
        // không tạo overflow-y riêng cho một cột vì sẽ sinh hai vùng cuộn và
        // lộ khoảng nền lớn khi hai cột không cùng chiều cao.
        if (mainHeight >= sideHeight + MIN_HEIGHT_GAP && sideHeight <= available) {
            side.classList.add(ACTIVE_CLASS);
            side.style.setProperty('--admin-balanced-top', `${STICKY_TOP}px`);
            return;
        }

        // Nếu rail bên phải lại dài hơn main, ép layout xuống một cột. Cách này
        // tránh trường hợp main hết nội dung từ sớm còn rail tiếp tục kéo dài,
        // tạo một mảng trống lớn ở nửa trang.
        if (sideHeight > mainHeight + MIN_HEIGHT_GAP) {
            shell.classList.add('booking-shell--stacked-balance');
        }
    }


    function directColumns(row) {
        const children = Array.from(row.children).filter(el => el instanceof HTMLElement);
        const left = children.find(el => el.classList.contains('col-lg-8'));
        const right = children.find(el => el.classList.contains('col-lg-4'));
        return left && right ? [left, right] : null;
    }

    function balanceBootstrapRow(row) {
        row.classList.remove('admin-balanced-row--stacked');
        const cols = directColumns(row);
        if (!cols || window.innerWidth < BREAKPOINT) return;
        if (row.closest('.card, .card-clean, .settings-section, .booking-form-card, .promotion-form-card, .modal, .offcanvas')) return;

        const [left, right] = cols;
        clearColumn(left);
        clearColumn(right);
        const leftHeight = Math.ceil(left.scrollHeight);
        const rightHeight = Math.ceil(right.scrollHeight);
        const available = Math.max(320, window.innerHeight - STICKY_TOP - 18);

        if (leftHeight >= rightHeight + MIN_HEIGHT_GAP && rightHeight <= available) {
            right.classList.add(ACTIVE_CLASS);
            right.style.setProperty('--admin-balanced-top', `${STICKY_TOP}px`);
        } else if (rightHeight > leftHeight + MIN_HEIGHT_GAP) {
            row.classList.add('admin-balanced-row--stacked');
        }
    }

    function run() {
        document.querySelectorAll('.booking-shell').forEach(balanceBookingShell);

        document.querySelectorAll('.admin-content .row').forEach(balanceBootstrapRow);
    }

    let raf = null;
    function schedule() {
        if (raf) cancelAnimationFrame(raf);
        raf = requestAnimationFrame(function () {
            raf = null;
            run();
        });
    }

    document.addEventListener('DOMContentLoaded', schedule, { once: true });
    window.addEventListener('load', schedule, { once: true });
    window.addEventListener('resize', schedule, { passive: true });

    const observer = new MutationObserver(schedule);
    observer.observe(document.documentElement, { subtree: true, childList: true });
})();
