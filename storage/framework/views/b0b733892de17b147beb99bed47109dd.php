<div id="hotel-chat-box" class="hotel-chat-box">
    <button type="button"
        class="hotel-chat-toggle"
        id="hotelChatToggle"
        onclick="toggleHotelChat()">
        <i class="bx bx-message-rounded-dots"></i>
        <span>Tư vấn</span>
    </button>

    <div class="hotel-chat-panel" id="hotelChatPanel">
        <div class="hotel-chat-header">
            <div>
                <strong>MCuong Hotel</strong>
                <small id="chatStaffName">Khách sạn sẽ phản hồi sớm</small>
            </div>

            <button type="button" onclick="toggleHotelChat()">
                <i class="bx bx-x"></i>
            </button>
        </div>

        <div class="hotel-chat-suggest">
            <button type="button" onclick="fillHotelChatMessage('Tôi muốn hỏi còn phòng hôm nay không?')">
                Còn phòng hôm nay?
            </button>
            <button type="button" onclick="fillHotelChatMessage('Tôi cần hỗ trợ thanh toán/cọc VNPay.')">
                Thanh toán
            </button>
            <button type="button" onclick="fillHotelChatMessage('Tôi muốn hỏi về nhận phòng sớm.')">
                Nhận phòng sớm
            </button>
        </div>

        <div class="hotel-chat-messages" id="hotelChatMessages">
            <div class="hotel-chat-empty">
                Anh/chị cần hỗ trợ gì ạ?
            </div>
        </div>

        <?php if(auth()->guard()->guest()): ?>
            <div class="hotel-chat-guest-info">
                <input type="text" id="chatGuestName" placeholder="Họ tên">
                <input type="text" id="chatGuestPhone" placeholder="Số điện thoại">
                <input type="email" id="chatGuestEmail" placeholder="Email nếu có">
            </div>
        <?php endif; ?>

        <div class="hotel-chat-input-row">
            <textarea id="hotelChatInput" placeholder="Nhập tin nhắn..." rows="2"></textarea>

            <button type="button" onclick="sendHotelChatMessage()">
                <i class="bx bx-send"></i>
            </button>
        </div>
    </div>
</div>

<style>
    .hotel-chat-box {
        --hotel-blue: #0f4f8a;
        --hotel-blue-2: #2563eb;
        --hotel-gold: #d4af37;
        --hotel-border: #e5e7eb;
        --hotel-muted: #64748b;
        --hotel-ink: #111827;
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 9999;
        font-family: inherit;
    }

    .hotel-chat-toggle {
        border: none;
        border-radius: 999px;
        padding: 12px 18px;
        background: #0f4f8a;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        box-shadow: 0 12px 28px rgba(15, 79, 138, .25);
        transition: .18s ease;
    }

    .hotel-chat-toggle:hover {
        transform: translateY(-2px);
        background: #0b477d;
    }

    .hotel-chat-toggle i {
        font-size: 20px;
    }

    .hotel-chat-panel {
        position: absolute;
        right: 0;
        bottom: 62px;
        width: 370px;
        height: 540px;
        display: none;
        flex-direction: column;
        background: #fff;
        border: 1px solid var(--hotel-border);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 20px 48px rgba(15, 23, 42, .22);
    }

    .hotel-chat-panel.open {
        display: flex;
        animation: hotelChatPop .18s ease both;
    }

    .hotel-chat-header {
        padding: 14px 16px;
        background: #0f4f8a;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .hotel-chat-header strong {
        display: block;
        font-size: 15px;
        font-weight: 700;
    }

    .hotel-chat-header small {
        display: block;
        margin-top: 2px;
        font-size: 12px;
        opacity: .88;
    }

    .hotel-chat-header button {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 50%;
        background: rgba(255, 255, 255, .15);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 20px;
    }

    .hotel-chat-suggest {
        display: flex;
        gap: 7px;
        padding: 10px;
        overflow-x: auto;
        border-bottom: 1px solid var(--hotel-border);
        background: #f8fafc;
    }

    .hotel-chat-suggest button {
        border: 1px solid #dbeafe;
        background: #fff;
        color: #0f4f8a;
        border-radius: 999px;
        padding: 7px 10px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        cursor: pointer;
    }

    .hotel-chat-suggest button:hover {
        background: #eff6ff;
    }

    .hotel-chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 14px;
        background: #f6f7fb;
    }

    .hotel-chat-empty {
        color: var(--hotel-muted);
        font-size: 13px;
        text-align: center;
        margin-top: 28px;
    }

    .hotel-chat-message {
        margin-bottom: 10px;
        display: flex;
    }

    .hotel-chat-message.customer {
        justify-content: flex-end;
    }

    .hotel-chat-message.staff,
    .hotel-chat-message.system {
        justify-content: flex-start;
    }

    .hotel-chat-bubble {
        max-width: 80%;
        padding: 9px 11px;
        border-radius: 15px;
        font-size: 13px;
        line-height: 1.45;
        background: #fff;
        color: var(--hotel-ink);
        border: 1px solid #e5e7eb;
        word-break: break-word;
    }

    .hotel-chat-message.customer .hotel-chat-bubble {
        background: #0f4f8a;
        color: #fff;
        border-color: #0f4f8a;
        border-top-right-radius: 5px;
    }

    .hotel-chat-message.staff .hotel-chat-bubble,
    .hotel-chat-message.system .hotel-chat-bubble {
        border-top-left-radius: 5px;
    }

    .hotel-chat-time {
        display: block;
        margin-top: 4px;
        font-size: 10px;
        opacity: .7;
    }

    .hotel-chat-guest-info {
        padding: 10px;
        display: grid;
        gap: 7px;
        background: #fff;
        border-top: 1px solid var(--hotel-border);
    }

    .hotel-chat-guest-info input {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 8px 10px;
        font-size: 13px;
        outline: none;
    }

    .hotel-chat-guest-info input:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 .15rem rgba(37, 99, 235, .1);
    }

    .hotel-chat-input-row {
        padding: 10px;
        display: flex;
        gap: 8px;
        background: #fff;
        border-top: 1px solid var(--hotel-border);
        align-items: flex-end;
    }

    .hotel-chat-input-row textarea {
        flex: 1;
        resize: none;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 10px 12px;
        font-size: 13px;
        outline: none;
        max-height: 90px;
    }

    .hotel-chat-input-row textarea:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 .15rem rgba(37, 99, 235, .1);
    }

    .hotel-chat-input-row button {
        width: 42px;
        height: 42px;
        border: none;
        border-radius: 50%;
        background: #0f4f8a;
        color: #fff;
        font-size: 19px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
    }

    .hotel-chat-input-row button:hover {
        background: #0b477d;
    }

    @keyframes hotelChatPop {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 576px) {
        .hotel-chat-box {
            right: 12px;
            left: 12px;
            bottom: 12px;
        }

        .hotel-chat-panel {
            width: 100%;
            height: min(560px, calc(100vh - 88px));
            right: 0;
        }
    }
</style>

<script>
    let hotelChatInterval = null;

    function toggleHotelChat() {
        const panel = document.getElementById('hotelChatPanel');
        panel.classList.toggle('open');

        if (panel.classList.contains('open')) {
            loadHotelChatMessages();

            if (!hotelChatInterval) {
                hotelChatInterval = setInterval(loadHotelChatMessages, 4000);
            }

            setTimeout(function () {
                document.getElementById('hotelChatInput')?.focus();
            }, 120);
        }
    }

    function fillHotelChatMessage(text) {
        const input = document.getElementById('hotelChatInput');

        if (!input) {
            return;
        }

        input.value = text;
        input.focus();
        autoResizeHotelChatInput(input);
    }

    async function loadHotelChatMessages() {
        try {
            const response = await fetch("<?php echo e(route('chat.messages')); ?>", {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            renderHotelChatMessages(data);
        } catch (error) {
            console.error(error);
        }
    }

    async function sendHotelChatMessage() {
        const input = document.getElementById('hotelChatInput');
        const message = input.value.trim();

        if (!message) {
            return;
        }

        const payload = {
            message: message,
            guest_name: document.getElementById('chatGuestName')?.value || null,
            guest_phone: document.getElementById('chatGuestPhone')?.value || null,
            guest_email: document.getElementById('chatGuestEmail')?.value || null,
        };

        try {
            const response = await fetch("<?php echo e(route('chat.send')); ?>", {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "<?php echo e(csrf_token()); ?>",
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                let errorMessage = 'Không gửi được tin nhắn. Vui lòng kiểm tra thông tin và thử lại.';

                try {
                    const errorData = await response.json();

                    if (errorData.errors) {
                        const firstKey = Object.keys(errorData.errors)[0];
                        errorMessage = errorData.errors[firstKey][0] || errorMessage;
                    } else if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {}

                alert(errorMessage);
                return;
            }

            input.value = '';
            autoResizeHotelChatInput(input);
            await loadHotelChatMessages();
        } catch (error) {
            console.error(error);
            alert('Có lỗi khi gửi tin nhắn.');
        }
    }

    function renderHotelChatMessages(data) {
        const wrapper = document.getElementById('hotelChatMessages');
        const staffLabel = document.getElementById('chatStaffName');

        if (data.conversation?.assigned_staff_name) {
            staffLabel.innerText = 'Đang hỗ trợ bởi ' + data.conversation.assigned_staff_name;
        } else if (data.conversation?.status === 'waiting') {
            staffLabel.innerText = 'Đang chờ nhân viên tiếp nhận';
        } else {
            staffLabel.innerText = 'Khách sạn sẽ phản hồi sớm';
        }

        if (!data.messages || data.messages.length === 0) {
            wrapper.innerHTML = '<div class="hotel-chat-empty">Anh/chị cần hỗ trợ gì ạ?</div>';
            return;
        }

        wrapper.innerHTML = data.messages.map(item => {
            const isCustomer = item.sender_type === 'customer';

            return `
                <div class="hotel-chat-message ${isCustomer ? 'customer' : 'staff'}">
                    <div class="hotel-chat-bubble">
                        ${escapeHtml(item.message)}
                        <span class="hotel-chat-time">${item.created_at}</span>
                    </div>
                </div>
            `;
        }).join('');

        wrapper.scrollTop = wrapper.scrollHeight;
    }

    function autoResizeHotelChatInput(input) {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 90) + 'px';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('hotelChatInput');

        if (!input) {
            return;
        }

        input.addEventListener('input', function () {
            autoResizeHotelChatInput(input);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendHotelChatMessage();
            }
        });
    });
</script><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/components/chat-box.blade.php ENDPATH**/ ?>