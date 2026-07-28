const root = document.getElementById('hotelChat');

if (root) {
    const panel = document.getElementById('hotelChatPanel');
    const toggle = document.getElementById('hotelChatToggle');
    const hide = document.getElementById('hotelChatHide');
    const form = document.getElementById('hotelChatForm');
    const input = document.getElementById('hotelChatInput');
    const filesInput = document.getElementById('hotelChatFiles');
    const cameraInput = document.getElementById('hotelChatCamera');
    const preview = document.getElementById('hotelChatFilesPreview');
    const messagesBox = document.getElementById('hotelChatMessages');
    const badge = document.getElementById('hotelChatBadge');

    let conversationId = null;
    const renderedIds = new Set();
    let echoChannel = null;
    let selectedFiles = [];

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    const escapeHtml = (value = '') => {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    };

    const formatSize = (bytes = 0) => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
    };

    const fileKey = (file) => `${file.name}-${file.size}-${file.lastModified}`;

    const renderAttachment = (file) => {
        if (file.type === 'image') {
            return `<a class="hotel-chat-attachment" href="${file.download_url}" target="_blank">
                <img src="${file.download_url}" alt="${escapeHtml(file.name)}">
                <span>${escapeHtml(file.name)}</span>
            </a>`;
        }

        return `<a class="hotel-chat-attachment" href="${file.download_url}">
            <i class="bx bx-file"></i>
            <span>${escapeHtml(file.name)}<br><small>${formatSize(file.size)}</small></span>
        </a>`;
    };

    const appendMessage = (message) => {
        if (!message || renderedIds.has(Number(message.id))) return;

        renderedIds.add(Number(message.id));
        messagesBox?.querySelector('.hotel-chat-empty')?.remove();

        const row = document.createElement('div');
        row.className = `hotel-chat-row ${message.sender_type === 'customer' ? 'customer' : 'staff'}`;
        row.dataset.messageId = message.id;

        const attachments = (message.attachments || []).map(renderAttachment).join('');

        row.innerHTML = `<div class="hotel-chat-bubble">
            ${message.message ? `<div>${escapeHtml(message.message)}</div>` : ''}
            ${attachments}
            <span class="hotel-chat-time">${escapeHtml(message.created_at || '')}</span>
        </div>`;

        messagesBox?.appendChild(row);
        if (messagesBox) messagesBox.scrollTop = messagesBox.scrollHeight;

        if (!panel?.classList.contains('is-open') && message.sender_type === 'staff') {
            badge?.classList.remove('d-none');
        }
    };

    const subscribeRealtime = () => {
        if (!window.Echo || !conversationId || echoChannel) return;

        echoChannel = window.Echo
            .private(`chat.conversation.${conversationId}`)
            .listen('.chat.message.sent', appendMessage);
    };

    let isLoadingMessages = false;

    const loadMessages = async () => {
        if (isLoadingMessages) return;

        isLoadingMessages = true;

        try {
            const response = await window.axios.get('/chat/messages');
            conversationId = response.data.conversation?.id || null;
            (response.data.messages || []).forEach(appendMessage);
            subscribeRealtime();
        } catch (error) {
            console.error('Không thể tải chat', error);
        } finally {
            isLoadingMessages = false;
        }
    };

    const renderSelectedFiles = () => {
        if (!preview) return;

        preview.replaceChildren();

        selectedFiles.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'hotel-chat-preview-item';

            if (file.type.startsWith('image/')) {
                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                image.alt = file.name;
                image.onload = () => URL.revokeObjectURL(image.src);
                item.appendChild(image);
            } else {
                const icon = document.createElement('i');
                icon.className = 'bx bx-file hotel-chat-preview-icon';
                item.appendChild(icon);
            }

            const info = document.createElement('span');
            info.className = 'hotel-chat-preview-name';
            info.textContent = file.name;
            item.appendChild(info);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'hotel-chat-preview-remove';
            remove.setAttribute('aria-label', `Bỏ ${file.name}`);
            remove.innerHTML = '&times;';
            remove.addEventListener('click', () => {
                selectedFiles.splice(index, 1);
                renderSelectedFiles();
            });
            item.appendChild(remove);

            preview.appendChild(item);
        });
    };

    const addFiles = (files) => {
        const existing = new Set(selectedFiles.map(fileKey));

        for (const file of Array.from(files || [])) {
            if (selectedFiles.length >= 5) break;

            const key = fileKey(file);
            if (!existing.has(key)) {
                selectedFiles.push(file);
                existing.add(key);
            }
        }

        renderSelectedFiles();
    };

    const openPanel = () => {
        if (!panel || !toggle) return;

        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        toggle.classList.add('is-hidden');
        toggle.setAttribute('aria-expanded', 'true');
        badge?.classList.add('d-none');

        loadMessages().finally(() => {
            requestAnimationFrame(() => {
                if (messagesBox) {
                    messagesBox.scrollTop = messagesBox.scrollHeight;
                }
                input?.focus();
            });
        });
    };

    const closePanel = () => {
        if (!panel || !toggle) return;

        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        toggle.classList.remove('is-hidden');
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle?.setAttribute('aria-expanded', 'false');
    toggle?.addEventListener('click', openPanel);
    hide?.addEventListener('click', closePanel);

    filesInput?.addEventListener('change', () => {
        addFiles(filesInput.files);
        filesInput.value = '';
    });

    cameraInput?.addEventListener('change', () => {
        addFiles(cameraInput.files);
        cameraInput.value = '';
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!input?.value.trim() && selectedFiles.length === 0) return;

        const data = new FormData();
        data.append('_token', csrf || '');
        data.append('message', input?.value.trim() || '');
        selectedFiles.forEach((file) => data.append('files[]', file, file.name));

        const submitButton = form.querySelector('button[type="submit"]');

        try {
            if (submitButton) submitButton.disabled = true;

            const response = await window.axios.post('/chat/send', data, {
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            conversationId = response.data.conversation?.id || conversationId;
            appendMessage(response.data.message);

            if (input) input.value = '';
            selectedFiles = [];
            renderSelectedFiles();
            subscribeRealtime();
        } catch (error) {
            alert(error.response?.data?.message || 'Không thể gửi tin nhắn.');
        } finally {
            if (submitButton) submitButton.disabled = false;
            input?.focus();
        }
    });

    input?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form?.requestSubmit();
        }
    });

    setInterval(() => {
        if (panel?.classList.contains('is-open')) loadMessages();
    }, 8000);
}
