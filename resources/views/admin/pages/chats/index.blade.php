@extends('layouts.admin')

@section('title', 'Tin nhắn khách hàng')

@section('content')
    @php
        $currentFilter = $filter ?? 'messages';
        $counts = [
            'messages_unread' => $messagesUnreadCount ?? 0,
            'archived_unread' => $archivedUnreadCount ?? 0,
        ];

        $lastMessage = fn($conversation) => $conversation->messages?->first();
    @endphp

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
        data-conversation-id="{{ $selectedConversation?->id ?? 0 }}"
        data-read-url="{{ $selectedConversation ? route('admin.chats.read', $selectedConversation) : '' }}"
        data-older-messages-url="{{ $selectedConversation ? route('admin.chats.messages', $selectedConversation) : '' }}"
        data-has-older-messages="{{ !empty($hasOlderMessages) ? '1' : '0' }}"
        data-chat-supervisor="{{ in_array(auth()->user()->role, ['super_admin', 'manager', 'receptionist_lead'], true) ? '1' : '0' }}"
        data-chat-staff-user-id="{{ in_array(auth()->user()->role, ['receptionist', 'receptionist_lead'], true) ? auth()->id() : 0 }}"
        data-current-filter="{{ $currentFilter }}">
        <main class="admin-content">
            <div class="mb-3">
                <h2 class="mb-1">Tin nhắn khách hàng</h2>
                <p class="text-muted mb-0">Tin cần phản hồi được đánh dấu vàng; hội thoại đã xử lý giữ trạng thái bình thường.</p>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">
                        @if($myPresenceStatus)
                            <div class="col-xl-6">
                                <form method="POST" action="{{ route('admin.chats.presence.update') }}" class="row g-2 align-items-end">
                                    @csrf
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">Trạng thái trực chat</label>
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="online" @selected($myPresenceStatus === 'online')>Online · nhận chat mới</option>
                                            <option value="away" @selected($myPresenceStatus === 'away')>Away · không nhận chat mới</option>
                                            <option value="offline" @selected($myPresenceStatus === 'offline')>Offline · bàn giao toàn bộ</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">Khi Offline</label>
                                        <select name="handoff_mode" class="form-select form-select-sm">
                                            <option value="rebalance">Chia đều người đang online</option>
                                            <option value="target">Chuyển hết cho một người</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">Người nhận nếu chuyển hết</label>
                                        <select name="target_staff_id" class="form-select form-select-sm">
                                            <option value="">-- Chọn khi cần --</option>
                                            @foreach($onlineStaffs as $staff)
                                                @if($staff->id !== auth()->id())
                                                    <option value="{{ $staff->id }}">{{ $staff->name }} · {{ $staffLoads[$staff->id] ?? 0 }} chat</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-sm btn-primary">Cập nhật trạng thái</button>
                                        <span class="small text-muted ms-2">Away giữ khách hiện tại; Offline sẽ bàn giao toàn bộ chat đang mở.</span>
                                    </div>
                                </form>
                            </div>
                        @endif

                        <div class="col-xl-{{ $myPresenceStatus ? '6' : '12' }}">
                            <div class="small fw-semibold mb-2">Tải trực chat hiện tại</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($chatStaffs as $staff)
                                    @php
                                        $presenceStatus = app(\App\Services\ChatPresenceService::class)->statusFor($staff);
                                        $statusClass = $presenceStatus === 'online' ? 'success' : ($presenceStatus === 'away' ? 'warning' : 'secondary');
                                    @endphp
                                    <span class="badge text-bg-{{ $statusClass }} fw-normal">
                                        {{ $staff->name }} · {{ strtoupper($presenceStatus) }} · {{ $staffLoads[$staff->id] ?? 0 }} chat
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    @if(in_array(auth()->user()->role, ['super_admin', 'manager', 'receptionist_lead'], true))
                        <hr class="my-3">
                        <form method="POST" action="{{ route('admin.chats.handoff') }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Bàn giao toàn bộ chat của</label>
                                <select name="from_staff_id" class="form-select form-select-sm" required>
                                    <option value="">-- Chọn nhân viên --</option>
                                    @foreach($chatStaffs as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }} · {{ $staffLoads[$staff->id] ?? 0 }} chat</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Cách bàn giao</label>
                                <select name="handoff_mode" class="form-select form-select-sm">
                                    <option value="rebalance">Chia đều người online</option>
                                    <option value="target">Chuyển hết cho một người</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Người nhận</label>
                                <select name="target_staff_id" class="form-select form-select-sm">
                                    <option value="">-- Chỉ cần khi chuyển hết --</option>
                                    @foreach($onlineStaffs as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }} · {{ $staffLoads[$staff->id] ?? 0 }} chat</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" value="1" id="markOffline" name="mark_offline">
                                    <label class="form-check-label small" for="markOffline">Đánh dấu Offline</label>
                                </div>
                            </div>
                            <div class="col-md-1 d-grid">
                                <button class="btn btn-sm btn-outline-primary">Bàn giao</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
@if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="chat-shell">
                <aside class="chat-list">
                    <div class="chat-list-head">
                        <strong>Hội thoại</strong>
                        <div class="small text-muted">Bố cục tương tự Messenger</div>
                    </div>

                    <nav class="chat-tabs">
                        <a class="chat-tab {{ $currentFilter === 'messages' ? 'active' : '' }}"
                            href="{{ route('admin.chats.index', ['filter' => 'messages']) }}">
                            Tin nhắn
                            <span id="messagesUnreadBadge"
                                class="chat-tab-count {{ $counts['messages_unread'] > 0 ? '' : 'is-empty' }}">
                                {{ $counts['messages_unread'] }}
                            </span>
                        </a>
                        <a class="chat-tab {{ $currentFilter === 'archived' ? 'active' : '' }}"
                            href="{{ route('admin.chats.index', ['filter' => 'archived']) }}">
                            Lưu trữ
                            <span id="archivedUnreadBadge"
                                class="chat-tab-count {{ $counts['archived_unread'] > 0 ? '' : 'is-empty' }}">
                                {{ $counts['archived_unread'] }}
                            </span>
                        </a>
                    </nav>

                    <div class="chat-scroll">
                        @forelse($conversationList as $conversation)
                            @php $preview = $lastMessage($conversation); @endphp
                            <a class="chat-item {{ $selectedConversation?->id === $conversation->id ? 'active' : '' }} {{ $conversation->has_unread_customer ? 'pending' : '' }}"
                                data-conversation-id="{{ $conversation->id }}"
                                href="{{ route('admin.chats.index', ['filter' => $currentFilter, 'conversation' => $conversation->id]) }}">
                                @if($conversation->has_unread_customer)
                                    <span class="chat-item-pending-dot" aria-hidden="true"></span>
                                @endif
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="chat-name">{{ $conversation->customer_display_name }}</div>
                                    <small
                                        class="text-muted">{{ optional($conversation->last_message_at)->format('H:i') }}</small>
                                </div>
                                <div class="chat-preview">
                                    {{ $preview?->message ?: ($preview?->attachments?->count() ? 'Đã gửi tệp đính kèm' : 'Chưa có tin nhắn') }}
                                </div>
                            </a>
                        @empty
                            <div class="text-center text-muted small py-5">Không có hội thoại.</div>
                        @endforelse
                    </div>
                </aside>

                <section class="chat-main">
                    @if($selectedConversation)
                                <header class="chat-main-head">
                                    <div>
                                        <strong>{{ $selectedConversation->customer_display_name }}</strong>
                                        <div class="small text-muted">
                                            {{ $selectedConversation->guest_phone
                        ?? $selectedConversation->guest_email
                        ?? $selectedConversation->customer?->email
                        ?? 'Chưa có thông tin liên hệ' }}
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        @if($selectedConversation->status === 'waiting' && !$selectedConversation->assigned_staff_id && $myPresenceStatus === 'online')
                                            <form method="POST" action="{{ route('admin.chats.take', $selectedConversation) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-success">Tiếp nhận</button>
                                            </form>
                                        @endif

                                        @if($selectedConversation->status === 'closed')
                                            <form method="POST" action="{{ route('admin.chats.reopen', $selectedConversation) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-primary">Khôi phục</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.chats.close', $selectedConversation) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary"
                                                    onclick="return confirm('Chuyển cuộc trò chuyện vào lưu trữ?')">
                                                    Lưu trữ
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </header>

                                <div class="chat-body" id="adminChatBody">
                                    <div class="text-center mb-3" id="adminChatOlderWrap" @style(['display:none' => empty($hasOlderMessages)])>
                                        <button type="button" id="adminChatLoadOlder" class="btn btn-sm btn-light border">Tải tin nhắn cũ hơn</button>
                                    </div>
                                    @foreach($selectedConversation->messages as $message)
                                        <div class="chat-row {{ $message->sender_type === 'staff' ? 'staff' : '' }}"
                                            data-message-id="{{ $message->id }}">
                                            <div class="chat-bubble">
                                                @if($message->message)
                                                    <div>{{ $message->message }}</div>
                                                @endif

                                                @foreach($message->attachments as $file)
                                                    <a class="chat-attachment" href="{{ route('chat.attachments.download', $file) }}"
                                                        target="{{ $file->type === 'image' ? '_blank' : '_self' }}">
                                                        @if($file->type === 'image')
                                                            <img src="{{ route('chat.attachments.download', $file) }}"
                                                                alt="{{ $file->original_name }}">
                                                        @else
                                                            <i class="bx bx-file fs-4"></i>
                                                        @endif
                                                        <span>{{ $file->original_name }}<br>
                                                            <small>{{ number_format($file->size / 1024, 1) }} KB</small>
                                                        </span>
                                                    </a>
                                                @endforeach

                                                <div class="chat-meta">
                                                    {{ $message->sender?->name ?? ($message->sender_type === 'staff' ? 'Nhân viên' : 'Khách hàng') }}
                                                    · {{ $message->created_at->format('H:i d/m/Y') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <footer class="chat-footer">
                                    <div id="adminChatFilePreview" class="file-preview"></div>
                                    <form id="adminChatForm" method="POST"
                                        action="{{ route('admin.chats.send', $selectedConversation) }}" class="chat-form"
                                        enctype="multipart/form-data">
                                        @csrf
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
                    @else
                        <div class="m-auto text-center text-muted">Chọn một hội thoại để xem nội dung.</div>
                    @endif
                </section>

                <aside class="chat-info">
                    @if($selectedConversation)
                        <div class="text-center">
                            <div
                                class="rounded-circle bg-primary-subtle text-primary d-inline-grid place-items-center p-3 mb-2">
                                <i class="bx bx-user fs-2"></i>
                            </div>
                            <strong class="d-block">{{ $selectedConversation->customer_display_name }}</strong>
                        </div>

                        <div class="chat-info-title">Phụ trách</div>
                        <div class="small mb-2">{{ $selectedConversation->assignedStaff?->name ?? 'Chưa phân công' }}</div>

                        @if($selectedConversation->status !== 'closed')
                            <form method="POST" action="{{ route('admin.chats.transfer', $selectedConversation) }}">
                                @csrf
                                <select name="staff_id" class="form-select form-select-sm mb-2" @disabled($onlineStaffs->isEmpty())>
                                    @if($onlineStaffs->isEmpty())
                                        <option value="">Không có lễ tân online</option>
                                    @endif
                                    @foreach($onlineStaffs as $staff)
                                        <option value="{{ $staff->id }}"
                                            @selected($selectedConversation->assigned_staff_id == $staff->id)>
                                            {{ $staff->name }} · {{ $staffLoads[$staff->id] ?? 0 }} chat · ONLINE
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-outline-primary w-100" @disabled($onlineStaffs->isEmpty())>Chuyển nhân viên</button>
                            </form>
                        @endif
                    @endif
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
                            if (!found && message?.sender_type === 'customer') {
                                window.location.reload();
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
@endsection