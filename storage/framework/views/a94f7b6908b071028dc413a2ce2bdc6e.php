<?php $__env->startSection('title', 'Tin nhắn khách hàng'); ?>

<?php $__env->startSection('content'); ?>
    <script>document.body?.setAttribute('data-realtime-local-only', 'true');</script>
    <?php
        $currentFilter = $filter ?? 'messages';
        $counts = [
            'messages_unread' => $messagesUnreadCount ?? 0,
            'archived_unread' => $archivedUnreadCount ?? 0,
        ];

        $lastMessage = fn($conversation) => $conversation->messages?->first();
    ?>

    <style>
        .chat-page {
            --line: #e5e7eb;
            --muted: #64748b;
            --blue: #2563eb;
            --soft: #f8fafc
        }

        .chat-shell {
            height: calc(100vh - 170px);
            min-height: 620px;
            display: grid;
            grid-template-columns: 340px minmax(0, 1fr) 280px;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden
        }

        .chat-list {
            border-right: 1px solid var(--line);
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            overflow: hidden
        }

        .chat-list-head {
            padding: 16px;
            border-bottom: 1px solid var(--line)
        }

        .chat-tabs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            border-bottom: 1px solid var(--line)
        }

        .chat-tab {
            padding: 11px 6px;
            text-align: center;
            text-decoration: none;
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            border-bottom: 2px solid transparent
        }

        .chat-tab.active {
            color: var(--blue);
            background: #eff6ff;
            border-bottom-color: var(--blue)
        }

        .chat-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 9px
        }

        .chat-item {
            display: block;
            padding: 12px;
            border-radius: 13px;
            text-decoration: none;
            color: inherit;
            margin-bottom: 7px;
            border: 1px solid transparent
        }

        .chat-item:hover {
            background: #f8fafc
        }

        .chat-item.active {
            background: #eff6ff;
            border-color: #bfdbfe
        }

        .chat-item.pending {
            position: relative;
            background: #fff7d6;
            border-color: #f6d365;
        }

        .chat-item.pending:hover,
        .chat-item.pending.active {
            background: #fff1b8;
            border-color: #eab308;
        }

        .chat-item-pending-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, .12);
        }

        .chat-tab-count {
            display: inline-flex;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            margin-left: 4px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            font-size: 11px;
            line-height: 1;
        }

        .chat-tab-count.is-empty {
            display: none;
        }

        .chat-name {
            font-weight: 800;
            font-size: 14px
        }

        .chat-preview {
            font-size: 12px;
            color: var(--muted);
            margin-top: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .chat-main {
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            overflow: hidden;
            background: #f5f7fb
        }

        .chat-main-head {
            flex: 0 0 auto;
            padding: 13px 17px;
            background: #fff;
            border-bottom: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .chat-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 20px;
            overscroll-behavior: contain
        }

        .chat-row {
            display: flex;
            margin-bottom: 14px
        }

        .chat-row.staff {
            justify-content: flex-end
        }

        .chat-bubble {
            max-width: 74%;
            padding: 10px 12px;
            border-radius: 15px;
            background: #fff;
            border: 1px solid var(--line);
            font-size: 13px
        }

        .chat-row.staff .chat-bubble {
            background: var(--blue);
            color: #fff;
            border-color: var(--blue)
        }

        .chat-meta {
            font-size: 10px;
            opacity: .7;
            margin-top: 5px
        }

        .chat-attachment {
            display: flex;
            gap: 7px;
            align-items: center;
            margin-top: 7px;
            padding: 7px;
            border-radius: 9px;
            background: rgba(255, 255, 255, .18);
            color: inherit;
            text-decoration: none
        }

        .chat-row:not(.staff) .chat-attachment {
            background: #f1f5f9;
            color: #334155
        }

        .chat-attachment img {
            width: 84px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px
        }

        .chat-footer {
            flex: 0 0 auto;
            padding: 12px;
            background: #fff;
            border-top: 1px solid var(--line)
        }

        .chat-form {
            display: flex;
            align-items: flex-end;
            gap: 8px
        }

        .chat-form textarea {
            flex: 1;
            min-height: 43px;
            max-height: 110px;
            border-radius: 16px;
            resize: none
        }

        .chat-icon-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: grid;
            place-items: center
        }

        .chat-info {
            border-left: 1px solid var(--line);
            padding: 17px;
            overflow: auto
        }

        .chat-info-title {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 800;
            margin: 15px 0 8px
        }

        .file-preview {
            display: flex;
            gap: 6px;
            overflow: auto;
            margin-bottom: 8px
        }

        .file-chip {
            background: #f1f5f9;
            padding: 5px 8px;
            border-radius: 8px;
            font-size: 11px;
            white-space: nowrap
        }

        .file-chip {
            position: relative;
            padding-right: 28px;
        }

        .file-chip-remove {
            position: absolute;
            top: 50%;
            right: 5px;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: #dc2626;
            color: #fff;
            font-size: 13px;
            line-height: 18px;
            cursor: pointer;
        }

        @media(max-width:1199px) {
            .chat-shell {
                grid-template-columns: 310px minmax(0, 1fr)
            }

            .chat-info {
                display: none
            }
        }

        @media(max-width:767px) {
            .chat-shell {
                height: calc(100vh - 130px);
                min-height: 560px;
                grid-template-columns: 1fr
            }

            .chat-list {
                min-height: 380px
            }

            .chat-main {
                min-height: 560px
            }
        }
    </style>

    <div id="adminChatRoot" class="admin-wrapper chat-page"
        data-conversation-id="<?php echo e($selectedConversation?->id ?? 0); ?>"
        data-read-url="<?php echo e($selectedConversation ? route('admin.chats.read', $selectedConversation) : ''); ?>"
        data-older-messages-url="<?php echo e($selectedConversation ? route('admin.chats.messages', $selectedConversation) : ''); ?>"
        data-has-older-messages="<?php echo e(!empty($hasOlderMessages) ? '1' : '0'); ?>"
        data-chat-supervisor="<?php echo e(in_array(auth()->user()->role, ['super_admin', 'manager', 'receptionist_lead'], true) ? '1' : '0'); ?>"
        data-chat-staff-user-id="<?php echo e(in_array(auth()->user()->role, ['receptionist', 'receptionist_lead'], true) ? auth()->id() : 0); ?>"
        data-current-filter="<?php echo e($currentFilter); ?>">
        <main class="admin-content">
            <div class="mb-3">
                <h2 class="mb-1">Tin nhắn khách hàng</h2>
                <p class="text-muted mb-0">Tin cần phản hồi được đánh dấu vàng; hội thoại đã xử lý giữ trạng thái bình thường.</p>
            </div>

<?php if($errors->any()): ?>
                <div class="alert alert-danger"><?php echo e($errors->first()); ?></div>
            <?php endif; ?>

            <div class="chat-shell">
                <aside class="chat-list">
                    <div class="chat-list-head">
                        <strong>Hội thoại</strong>
                        <div class="small text-muted">Bố cục tương tự Messenger</div>
                    </div>

                    <nav class="chat-tabs">
                        <a class="chat-tab <?php echo e($currentFilter === 'messages' ? 'active' : ''); ?>"
                            href="<?php echo e(route('admin.chats.index', ['filter' => 'messages'])); ?>">
                            Tin nhắn
                            <span id="messagesUnreadBadge"
                                class="chat-tab-count <?php echo e($counts['messages_unread'] > 0 ? '' : 'is-empty'); ?>">
                                <?php echo e($counts['messages_unread']); ?>

                            </span>
                        </a>
                        <a class="chat-tab <?php echo e($currentFilter === 'archived' ? 'active' : ''); ?>"
                            href="<?php echo e(route('admin.chats.index', ['filter' => 'archived'])); ?>">
                            Lưu trữ
                            <span id="archivedUnreadBadge"
                                class="chat-tab-count <?php echo e($counts['archived_unread'] > 0 ? '' : 'is-empty'); ?>">
                                <?php echo e($counts['archived_unread']); ?>

                            </span>
                        </a>
                    </nav>

                    <div class="chat-scroll">
                        <?php $__empty_1 = true; $__currentLoopData = $conversationList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php $preview = $lastMessage($conversation); ?>
                            <a class="chat-item <?php echo e($selectedConversation?->id === $conversation->id ? 'active' : ''); ?> <?php echo e($conversation->has_unread_customer ? 'pending' : ''); ?>"
                                data-conversation-id="<?php echo e($conversation->id); ?>"
                                href="<?php echo e(route('admin.chats.index', ['filter' => $currentFilter, 'conversation' => $conversation->id])); ?>">
                                <?php if($conversation->has_unread_customer): ?>
                                    <span class="chat-item-pending-dot" aria-hidden="true"></span>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="chat-name"><?php echo e($conversation->customer_display_name); ?></div>
                                    <small
                                        class="text-muted"><?php echo e(optional($conversation->last_message_at)->format('H:i')); ?></small>
                                </div>
                                <div class="chat-preview">
                                    <?php echo e($preview?->message ?: ($preview?->attachments?->count() ? 'Đã gửi tệp đính kèm' : 'Chưa có tin nhắn')); ?>

                                </div>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="text-center text-muted small py-5">Không có hội thoại.</div>
                        <?php endif; ?>
                    </div>
                </aside>

                <section class="chat-main">
                    <?php if($selectedConversation): ?>
                                <header class="chat-main-head">
                                    <div>
                                        <strong><?php echo e($selectedConversation->customer_display_name); ?></strong>
                                        <div class="small text-muted">
                                            <?php echo e($selectedConversation->guest_phone
                        ?? $selectedConversation->guest_email
                        ?? $selectedConversation->customer?->email
                        ?? 'Chưa có thông tin liên hệ'); ?>

                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <?php if($selectedConversation->status === 'waiting' && !$selectedConversation->assigned_staff_id && $myPresenceStatus === 'online'): ?>
                                            <form method="POST" action="<?php echo e(route('admin.chats.take', $selectedConversation)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <button class="btn btn-sm btn-success">Tiếp nhận</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if($selectedConversation->status === 'closed'): ?>
                                            <form method="POST" action="<?php echo e(route('admin.chats.reopen', $selectedConversation)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <button class="btn btn-sm btn-outline-primary">Khôi phục</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="<?php echo e(route('admin.chats.close', $selectedConversation)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <button class="btn btn-sm btn-outline-secondary"
                                                    onclick="return confirm('Chuyển cuộc trò chuyện vào lưu trữ?')">
                                                    Lưu trữ
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </header>

                                <div class="chat-body" id="adminChatBody">
                                    <div class="text-center mb-3" id="adminChatOlderWrap" style="<?php echo \Illuminate\Support\Arr::toCssStyles(['display:none' => empty($hasOlderMessages)]) ?>">
                                        <button type="button" id="adminChatLoadOlder" class="btn btn-sm btn-light border">Tải tin nhắn cũ hơn</button>
                                    </div>
                                    <?php $__currentLoopData = $selectedConversation->messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="chat-row <?php echo e($message->sender_type === 'staff' ? 'staff' : ''); ?>"
                                            data-message-id="<?php echo e($message->id); ?>">
                                            <div class="chat-bubble">
                                                <?php if($message->message): ?>
                                                    <div><?php echo e($message->message); ?></div>
                                                <?php endif; ?>

                                                <?php $__currentLoopData = $message->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <a class="chat-attachment" href="<?php echo e(route('chat.attachments.download', $file)); ?>"
                                                        target="<?php echo e($file->type === 'image' ? '_blank' : '_self'); ?>">
                                                        <?php if($file->type === 'image'): ?>
                                                            <img src="<?php echo e(route('chat.attachments.download', $file)); ?>"
                                                                alt="<?php echo e($file->original_name); ?>">
                                                        <?php else: ?>
                                                            <i class="bx bx-file fs-4"></i>
                                                        <?php endif; ?>
                                                        <span><?php echo e($file->original_name); ?><br>
                                                            <small><?php echo e(number_format($file->size / 1024, 1)); ?> KB</small>
                                                        </span>
                                                    </a>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                                <div class="chat-meta">
                                                    <?php echo e($message->sender?->name ?? ($message->sender_type === 'staff' ? 'Nhân viên' : 'Khách hàng')); ?>

                                                    · <?php echo e($message->created_at->format('H:i d/m/Y')); ?>

                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>

                                <footer class="chat-footer">
                                    <div id="adminChatFilePreview" class="file-preview"></div>
                                    <form id="adminChatForm" method="POST"
                                        action="<?php echo e(route('admin.chats.send', $selectedConversation)); ?>" class="chat-form"
                                        enctype="multipart/form-data">
                                        <?php echo csrf_field(); ?>
                                        <label for="adminChatFiles" class="btn btn-light chat-icon-btn" title="Gửi ảnh hoặc file">
                                            <i class="bx bx-paperclip"></i>
                                        </label>
                                        <input id="adminChatFiles" name="files[]" type="file" class="d-none" multiple
                                            accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                                        <textarea name="message" class="form-control" rows="1"
                                            placeholder="Nhập tin nhắn..."></textarea>
                                        <button type="submit" class="btn btn-primary chat-icon-btn" aria-label="Gửi tin nhắn"><i class="bx bx-send"></i></button>
                                    </form>
                                </footer>
                    <?php else: ?>
                        <div class="m-auto text-center text-muted">Chọn một hội thoại để xem nội dung.</div>
                    <?php endif; ?>
                </section>

                <aside class="chat-info">
                    <?php if($selectedConversation): ?>
                        <div class="text-center">
                            <div
                                class="rounded-circle bg-primary-subtle text-primary d-inline-grid place-items-center p-3 mb-2">
                                <i class="bx bx-user fs-2"></i>
                            </div>
                            <strong class="d-block"><?php echo e($selectedConversation->customer_display_name); ?></strong>
                        </div>

                        <div class="chat-info-title">Phụ trách</div>
                        <div class="small mb-2"><?php echo e($selectedConversation->assignedStaff?->name ?? 'Chưa phân công'); ?></div>

                        <?php if($selectedConversation->status !== 'closed'): ?>
                            <form method="POST" action="<?php echo e(route('admin.chats.transfer', $selectedConversation)); ?>">
                                <?php echo csrf_field(); ?>
                                <select name="staff_id" class="form-select form-select-sm mb-2" <?php if($onlineStaffs->isEmpty()): echo 'disabled'; endif; ?>>
                                    <?php if($onlineStaffs->isEmpty()): ?>
                                        <option value="">Không có lễ tân online</option>
                                    <?php endif; ?>
                                    <?php $__currentLoopData = $onlineStaffs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($staff->id); ?>"
                                            <?php if($selectedConversation->assigned_staff_id == $staff->id): echo 'selected'; endif; ?>>
                                            <?php echo e($staff->name); ?> · <?php echo e($staffLoads[$staff->id] ?? 0); ?> chat · ONLINE
                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary w-100" <?php if($onlineStaffs->isEmpty()): echo 'disabled'; endif; ?>>Chuyển nhân viên</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </aside>
            </div>
        </main>
    </div>

    <script>
        (function () {
            const initAdminChat = function () {
            const root = document.getElementById('adminChatRoot');
            const body = document.getElementById('adminChatBody');
            const form = document.getElementById('adminChatForm');
            const filesInput = document.getElementById('adminChatFiles');
            const filesPreview = document.getElementById('adminChatFilePreview');
            const messageInput = form?.querySelector('textarea[name="message"]');
            const messagesUnreadBadge = document.getElementById('messagesUnreadBadge');
            const archivedUnreadBadge = document.getElementById('archivedUnreadBadge');
            const currentFilter = root?.dataset.currentFilter || 'messages';
            const readUrl = root?.dataset.readUrl || '';
            const olderMessagesUrl = root?.dataset.olderMessagesUrl || '';
            const loadOlderButton = document.getElementById('adminChatLoadOlder');
            const olderWrap = document.getElementById('adminChatOlderWrap');
            let hasOlderMessages = root?.dataset.hasOlderMessages === '1';
            let selectedFiles = [];
            let conversationListRefreshing = false;
            let sidebarUnreadTimer = null;

            const refreshSidebarUnreadBadge = async function () {
                const badge = document.querySelector('[data-realtime-menu-count="unread-chats"]');
                const url = badge?.dataset.chatUnreadUrl;
                if (!badge || !url) return;

                try {
                    const response = await fetch(url, {
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) return;
                    const payload = await response.json();
                    const count = Math.max(0, Number.parseInt(payload.count, 10) || 0);
                    badge.textContent = count > 99 ? '99+' : String(count);
                    badge.hidden = count < 1;
                } catch (error) {
                    console.debug('Không thể cập nhật badge chat.', error);
                }
            };

            const scheduleSidebarUnreadRefresh = function () {
                window.clearTimeout(sidebarUnreadTimer);
                sidebarUnreadTimer = window.setTimeout(refreshSidebarUnreadBadge, 120);
            };

            const refreshConversationList = async function () {
                if (conversationListRefreshing) return;
                conversationListRefreshing = true;

                try {
                    const response = await fetch(window.location.href, {
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) return;
                    const next = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const currentScroll = document.querySelector('.chat-scroll');
                    const nextScroll = next.querySelector('.chat-scroll');
                    if (currentScroll && nextScroll) currentScroll.innerHTML = nextScroll.innerHTML;

                    ['messagesUnreadBadge', 'archivedUnreadBadge'].forEach(function (id) {
                        const current = document.getElementById(id);
                        const replacement = next.getElementById(id);
                        if (!current || !replacement) return;
                        current.textContent = replacement.textContent;
                        current.classList.toggle('is-empty', replacement.classList.contains('is-empty'));
                    });
                    scheduleSidebarUnreadRefresh();
                } catch (error) {
                    console.debug('Không thể đồng bộ danh sách hội thoại.', error);
                } finally {
                    conversationListRefreshing = false;
                }
            };

            const conversationId = Number(
                root?.dataset.conversationId || 0
            );

            const scrollToLatestMessage = function () {
                if (!body) {
                    return;
                }

                const applyScroll = function () {
                    body.scrollTop = body.scrollHeight;
                };

                requestAnimationFrame(function () {
                    applyScroll();

                    requestAnimationFrame(applyScroll);
                });

                setTimeout(applyScroll, 80);
                setTimeout(applyScroll, 250);

                body.querySelectorAll('img').forEach(function (image) {
                    if (!image.complete) {
                        image.addEventListener('load', applyScroll, { once: true });
                        image.addEventListener('error', applyScroll, { once: true });
                    }
                });
            };

            scrollToLatestMessage();

            const getUnreadBadge = function (filterName) {
                return filterName === 'archived'
                    ? archivedUnreadBadge
                    : messagesUnreadBadge;
            };

            const getUnreadCount = function (filterName) {
                return Number(getUnreadBadge(filterName)?.textContent || 0);
            };

            const setUnreadCount = function (filterName, count) {
                const badge = getUnreadBadge(filterName);

                if (!badge) {
                    return;
                }

                const safeCount = Math.max(0, Number(count) || 0);
                badge.textContent = String(safeCount);
                badge.classList.toggle('is-empty', safeCount === 0);
            };

            const setConversationUnread = function (targetConversationId, isUnread, filterName = currentFilter) {
                const item = document.querySelector(
                    `.chat-item[data-conversation-id="${targetConversationId}"]`
                );

                if (!item) {
                    return false;
                }

                const wasUnread = item.classList.contains('pending');
                item.classList.toggle('pending', isUnread);

                let dot = item.querySelector('.chat-item-pending-dot');

                if (isUnread && !dot) {
                    dot = document.createElement('span');
                    dot.className = 'chat-item-pending-dot';
                    dot.setAttribute('aria-hidden', 'true');
                    item.prepend(dot);
                }

                if (!isUnread && dot) {
                    dot.remove();
                }

                if (!wasUnread && isUnread) {
                    setUnreadCount(filterName, getUnreadCount(filterName) + 1);
                }

                if (wasUnread && !isUnread) {
                    setUnreadCount(filterName, getUnreadCount(filterName) - 1);
                }

                scheduleSidebarUnreadRefresh();
                return true;
            };

            const markCurrentConversationRead = async function () {
                if (!readUrl || !conversationId) {
                    return;
                }

                setConversationUnread(conversationId, false, currentFilter);

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                try {
                    await fetch(readUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                    });
                } catch (error) {
                    console.error('Không thể đánh dấu tin nhắn đã đọc.', error);
                }
            };

            const escapeHtml = function (value = '') {
                const element = document.createElement('div');
                element.textContent = value;

                return element.innerHTML;
            };

            const formatSize = function (bytes = 0) {
                if (bytes < 1024) {
                    return `${bytes} B`;
                }

                if (bytes < 1024 * 1024) {
                    return `${(bytes / 1024).toFixed(1)} KB`;
                }

                return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
            };

            const renderAttachment = function (file) {
                if (file.type === 'image') {
                    return `
                    <a
                        class="chat-attachment"
                        href="${file.download_url}"
                        target="_blank"
                    >
                        <img
                            src="${file.download_url}"
                            alt="${escapeHtml(file.name)}"
                        >

                        <span>
                            ${escapeHtml(file.name)}
                            <br>
                            <small>${formatSize(file.size)}</small>
                        </span>
                    </a>
                `;
                }

                return `
                <a
                    class="chat-attachment"
                    href="${file.download_url}"
                >
                    <i class="bx bx-file fs-4"></i>

                    <span>
                        ${escapeHtml(file.name)}
                        <br>
                        <small>${formatSize(file.size)}</small>
                    </span>
                </a>
            `;
            };

            const buildAdminMessageRow = function (message) {
                const row = document.createElement('div');
                row.className = `chat-row ${message.sender_type === 'staff' ? 'staff' : ''}`;
                row.dataset.messageId = message.id;
                const attachments = (message.attachments || []).map(renderAttachment).join('');
                row.innerHTML = `
                    <div class="chat-bubble">
                        ${message.message ? `<div>${escapeHtml(message.message)}</div>` : ''}
                        ${attachments}
                        <div class="chat-meta">
                            ${escapeHtml(message.sender_name || 'Người gửi')} · ${escapeHtml(message.created_at || '')}
                        </div>
                    </div>
                `;
                return row;
            };

            const appendAdminMessage = function (message) {
                if (!body || !message?.id) {
                    return;
                }

                if (
                    body.querySelector(
                        `[data-message-id="${message.id}"]`
                    )
                ) {
                    return;
                }

                const row = buildAdminMessageRow(message);

                body.appendChild(row);
                scrollToLatestMessage();
            };

            const fileKey = function (file) {
                return `${file.name}-${file.size}-${file.lastModified}`;
            };

            const renderSelectedFiles = function () {
                if (!filesPreview) {
                    return;
                }

                filesPreview.replaceChildren();

                selectedFiles.forEach(function (file, index) {
                    const chip = document.createElement('span');
                    chip.className = 'file-chip';
                    chip.textContent = file.name;

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'file-chip-remove';
                    removeButton.innerHTML = '&times;';
                    removeButton.setAttribute('aria-label', `Bỏ ${file.name}`);
                    removeButton.addEventListener('click', function () {
                        selectedFiles.splice(index, 1);
                        renderSelectedFiles();
                    });

                    chip.appendChild(removeButton);
                    filesPreview.appendChild(chip);
                });
            };

            const loadOlderMessages = async function () {
                if (!body || !loadOlderButton || !olderMessagesUrl || !hasOlderMessages) {
                    return;
                }

                const firstMessage = body.querySelector('[data-message-id]');
                const beforeId = Number(firstMessage?.dataset.messageId || 0);
                if (!beforeId) return;

                loadOlderButton.disabled = true;
                const oldHeight = body.scrollHeight;
                const oldTop = body.scrollTop;

                try {
                    const response = await fetch(`${olderMessagesUrl}?before_id=${beforeId}`, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) throw new Error('Không thể tải lịch sử chat.');
                    const payload = await response.json();
                    const messages = payload.messages || [];
                    const firstExisting = body.querySelector('[data-message-id]');

                    [...messages].reverse().forEach(function (message) {
                        if (body.querySelector(`[data-message-id="${message.id}"]`)) return;
                        body.insertBefore(buildAdminMessageRow(message), firstExisting);
                    });

                    hasOlderMessages = Boolean(payload.has_more);
                    if (olderWrap) olderWrap.style.display = hasOlderMessages ? '' : 'none';
                    body.scrollTop = oldTop + (body.scrollHeight - oldHeight);
                } catch (error) {
                    console.error(error);
                } finally {
                    loadOlderButton.disabled = false;
                }
            };

            loadOlderButton?.addEventListener('click', loadOlderMessages);

            filesInput?.addEventListener('change', function () {
                const existing = new Set(selectedFiles.map(fileKey));

                Array.from(filesInput.files || []).forEach(function (file) {
                    if (selectedFiles.length >= 5) {
                        return;
                    }

                    const key = fileKey(file);

                    if (!existing.has(key)) {
                        selectedFiles.push(file);
                        existing.add(key);
                    }
                });

                filesInput.value = '';
                renderSelectedFiles();
            });

            messageInput?.addEventListener(
                'keydown',
                function (event) {
                    if (
                        event.key === 'Enter'
                        && !event.shiftKey
                    ) {
                        event.preventDefault();
                        form?.requestSubmit();
                    }
                }
            );

            form?.addEventListener(
                'submit',
                async function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (form.dataset.submitting === '1') {
                        return;
                    }

                    const hasMessage =
                        messageInput?.value.trim().length > 0;

                    const hasFiles = selectedFiles.length > 0;

                    if (!hasMessage && !hasFiles) {
                        return;
                    }

                    const submitButton = form.querySelector(
                        'button[type="submit"], button:not([type])'
                    );

                    const formData = new FormData(form);
                    formData.delete('files[]');

                    selectedFiles.forEach(function (file) {
                        formData.append('files[]', file, file.name);
                    });

                    try {
                        form.dataset.submitting = '1';

                        if (submitButton) {
                            submitButton.disabled = true;
                        }

                        const csrfToken = form.querySelector('input[name="_token"]')?.value
                            || document.querySelector('meta[name="csrf-token"]')?.content
                            || '';

                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                        });

                        const payload = await response.json().catch(function () {
                            return {};
                        });

                        if (!response.ok) {
                            const validationMessage = payload.errors
                                ? Object.values(payload.errors).flat()[0]
                                : null;

                            throw new Error(
                                validationMessage
                                || payload.message
                                || 'Không thể gửi tin nhắn.'
                            );
                        }

                        appendAdminMessage(payload.message);
                        setConversationUnread(conversationId, false, currentFilter);

                        const activeItem = document.querySelector(
                            `.chat-item[data-conversation-id="${conversationId}"]`
                        );

                        if (activeItem) {
                            const preview = activeItem.querySelector('.chat-preview');
                            const time = activeItem.querySelector('small.text-muted');

                            if (preview) {
                                preview.textContent = payload.message?.message
                                    || ((payload.message?.attachments || []).length
                                        ? 'Đã gửi tệp đính kèm'
                                        : 'Tin nhắn mới');
                            }

                            if (time) {
                                const createdAt = String(payload.message?.created_at || '');
                                time.textContent = createdAt.slice(0, 5);
                            }
                        }

                        messageInput.value = '';
                        filesInput.value = '';
                        selectedFiles = [];
                        renderSelectedFiles();
                    } catch (error) {
                        alert(
                            error.message
                            || 'Không thể gửi tin nhắn.'
                        );
                    } finally {
                        delete form.dataset.submitting;

                        if (submitButton) {
                            submitButton.disabled = false;
                        }

                        messageInput?.focus();
                    }
                }
            );

            if (window.Echo) {
                const isChatSupervisor = root?.dataset.chatSupervisor === '1';
                const chatStaffUserId = Number(root?.dataset.chatStaffUserId || 0);
                const channelName = isChatSupervisor
                    ? 'chat.supervisors'
                    : (chatStaffUserId > 0 ? `chat.staff.${chatStaffUserId}` : null);

                if (channelName) {
                    window.Echo
                        .private(channelName)
                        .listen('.chat.message.sent', function (message) {
                            const incomingConversationId = Number(message?.conversation_id || 0);
                            if (!incomingConversationId) return;

                            if (incomingConversationId === conversationId) {
                                appendAdminMessage(message);
                                if (message?.sender_type === 'customer') {
                                    markCurrentConversationRead();
                                }
                                return;
                            }

                            const found = message?.sender_type === 'customer'
                                ? setConversationUnread(incomingConversationId, true, currentFilter)
                                : true;

                            // Chat mới vừa được phân cho mình / hàng đợi supervisor chưa có trong DOM.
                            // Chỉ refresh cột hội thoại, không reload cả trang và không làm mất tin đang nhập.
                            if (!found && message?.sender_type === 'customer') {
                                refreshConversationList();
                                scheduleSidebarUnreadRefresh();
                            }
                        });
                }
            }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAdminChat, { once: true });
            } else {
                initAdminChat();
            }
        })();
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/chats/index.blade.php ENDPATH**/ ?>