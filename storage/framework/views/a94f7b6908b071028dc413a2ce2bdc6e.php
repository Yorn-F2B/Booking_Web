

<?php $__env->startSection('title', 'Tư vấn trực tuyến'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $statusLabels = [
            'waiting' => 'Đang chờ',
            'assigned' => 'Đã nhận',
            'active' => 'Đang chat',
            'closed' => 'Đã đóng',
        ];

        $statusClasses = [
            'waiting' => 'chat-status-waiting',
            'assigned' => 'chat-status-assigned',
            'active' => 'chat-status-active',
            'closed' => 'chat-status-closed',
        ];

        $totalWaiting = $waitingConversations->count();
        $totalMine = $myConversations->count();
        $totalOther = $otherConversations->count();
        $totalClosed = $closedConversations->count();

        $currentFilter = $filter ?? 'mine';

        $activeFilterClass = function ($name) use ($currentFilter) {
            return $currentFilter === $name ? 'active' : '';
        };

        $getLastMessage = function ($conversation) {
            return $conversation->messages?->last();
        };

        $needsReply = function ($conversation) use ($getLastMessage) {
            $lastMessage = $getLastMessage($conversation);

            return $conversation->status !== 'closed'
                && $lastMessage
                && $lastMessage->sender_type !== 'staff';
        };

        $formatContact = function ($conversation) {
            return $conversation->guest_phone
                ?? $conversation->guest_email
                ?? $conversation->customer?->email
                ?? 'Chưa có liên hệ';
        };

        $formatTime = function ($date) {
            return $date ? $date->format('H:i') : '--:--';
        };

        $selectedStatus = $selectedConversation?->status ?? null;
        $selectedStatusLabel = $selectedStatus ? ($statusLabels[$selectedStatus] ?? $selectedStatus) : '';
        $selectedStatusClass = $selectedStatus ? ($statusClasses[$selectedStatus] ?? 'chat-status-assigned') : '';
    ?>

    <style>
        .chat-page {
            --chat-border: #e5e7eb;
            --chat-soft: #f8fafc;
            --chat-muted: #64748b;
            --chat-ink: #111827;
            --chat-blue: #2563eb;
            --chat-blue-soft: #eff6ff;
            --chat-gold: #d4af37;
            --chat-danger: #dc2626;
            --chat-success: #16a34a;
        }

        .chat-page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .chat-page-head h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: var(--chat-ink);
        }

        .chat-page-head p {
            margin: 5px 0 0;
            color: var(--chat-muted);
            font-size: 13px;
        }

        .chat-refresh-btn {
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
        }

        .chat-app-shell {
            height: calc(100vh - 210px);
            min-height: 620px;
            background: #fff;
            border: 1px solid var(--chat-border);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 34px rgba(15, 23, 42, 0.045);
            display: grid;
            grid-template-columns: 360px minmax(0, 1fr) 300px;
        }

        .chat-sidebar {
            border-right: 1px solid var(--chat-border);
            background: #fff;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .chat-sidebar-head {
            padding: 16px;
            border-bottom: 1px solid var(--chat-border);
        }

        .chat-sidebar-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: var(--chat-ink);
        }

        .chat-sidebar-sub {
            margin-top: 4px;
            color: var(--chat-muted);
            font-size: 12px;
        }

        .chat-filter-tabs {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-bottom: 1px solid var(--chat-border);
        }

        .chat-filter-tab {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 11px 6px;
            color: #64748b;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            border-bottom: 2px solid transparent;
            background: #fff;
        }

        .chat-filter-tab:hover {
            color: var(--chat-blue);
            background: #f8fafc;
        }

        .chat-filter-tab.active {
            color: var(--chat-blue);
            border-bottom-color: var(--chat-blue);
            background: #eff6ff;
        }

        .chat-filter-count {
            min-width: 21px;
            height: 21px;
            padding: 0 6px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
        }

        .chat-filter-tab.active .chat-filter-count {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .chat-conversation-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            background: #fff;
        }

        .chat-list-item {
            display: block;
            text-decoration: none;
            color: inherit;
            border: 1px solid transparent;
            border-radius: 14px;
            padding: 12px;
            margin-bottom: 8px;
            transition: .16s ease;
            background: #fff;
        }

        .chat-list-item:hover {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .chat-list-item.active {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .chat-list-item.need-reply {
            background: #fff7ed;
            border-color: #fed7aa;
        }

        .chat-list-item.need-reply.active {
            background: #eff6ff;
            border-color: #60a5fa;
        }

        .chat-list-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }

        .chat-customer-name {
            color: var(--chat-ink);
            font-size: 14px;
            font-weight: 800;
            line-height: 1.3;
        }

        .chat-customer-contact {
            margin-top: 2px;
            color: var(--chat-muted);
            font-size: 12px;
            line-height: 1.35;
        }

        .chat-list-time {
            color: #64748b;
            font-size: 12px;
            white-space: nowrap;
        }

        .chat-last-message {
            margin-top: 8px;
            color: #475569;
            font-size: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .chat-list-bottom {
            margin-top: 9px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .chat-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .chat-status-waiting {
            color: #92400e;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .chat-status-assigned {
            color: #1d4ed8;
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .chat-status-active {
            color: #166534;
            background: #dcfce7;
            border-color: #bbf7d0;
        }

        .chat-status-closed {
            color: #475569;
            background: #f1f5f9;
            border-color: #e2e8f0;
        }

        .chat-reply-dot {
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: var(--chat-danger);
            box-shadow: 0 0 0 4px rgba(220, 38, 38, .1);
        }

        .chat-main {
            min-width: 0;
            background: #f6f7fb;
            display: flex;
            flex-direction: column;
        }

        .chat-main-head {
            min-height: 76px;
            padding: 14px 18px;
            background: #fff;
            border-bottom: 1px solid var(--chat-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .chat-main-user {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .chat-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #e0ecff;
            color: var(--chat-blue);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .chat-main-name {
            margin: 0;
            color: var(--chat-ink);
            font-size: 17px;
            font-weight: 800;
        }

        .chat-main-contact {
            margin-top: 2px;
            color: var(--chat-muted);
            font-size: 12px;
        }

        .chat-main-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .chat-main-actions .btn {
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 22px;
            background:
                linear-gradient(rgba(246, 247, 251, .94), rgba(246, 247, 251, .94)),
                radial-gradient(circle at 15% 0%, rgba(37, 99, 235, .08), transparent 28%);
        }

        .chat-message-row {
            display: flex;
            margin-bottom: 16px;
        }

        .chat-message-row.is-staff {
            justify-content: flex-end;
        }

        .chat-message-wrap {
            max-width: min(680px, 74%);
        }

        .chat-message-meta {
            margin-bottom: 5px;
            color: #94a3b8;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chat-message-row.is-staff .chat-message-meta {
            justify-content: flex-end;
            text-align: right;
        }

        .chat-message-bubble {
            padding: 11px 13px;
            border-radius: 16px;
            font-size: 13px;
            line-height: 1.5;
            word-break: break-word;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .045);
        }

        .chat-message-row.is-customer .chat-message-bubble {
            background: #fff;
            color: #111827;
            border: 1px solid #e5e7eb;
            border-top-left-radius: 5px;
        }

        .chat-message-row.is-staff .chat-message-bubble {
            background: #2563eb;
            color: #fff;
            border-top-right-radius: 5px;
        }

        .chat-message-row.is-system .chat-message-bubble {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .chat-footer {
            padding: 14px 18px;
            background: #fff;
            border-top: 1px solid var(--chat-border);
        }

        .chat-reply-form {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .chat-reply-form textarea {
            border-radius: 999px;
            border-color: #e5e7eb;
            min-height: 44px;
            max-height: 110px;
            resize: none;
            padding: 11px 16px;
            font-size: 13px;
        }

        .chat-reply-form textarea:focus {
            border-color: #bfdbfe;
            box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .1);
        }

        .chat-send-btn {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
        }

        .chat-info-panel {
            border-left: 1px solid var(--chat-border);
            background: #fff;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .chat-info-head {
            padding: 18px;
            border-bottom: 1px solid var(--chat-border);
            text-align: center;
        }

        .chat-info-avatar {
            width: 66px;
            height: 66px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: #eff6ff;
            color: var(--chat-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .chat-info-name {
            font-size: 15px;
            font-weight: 800;
            color: var(--chat-ink);
        }

        .chat-info-contact {
            margin-top: 3px;
            color: var(--chat-muted);
            font-size: 12px;
            word-break: break-word;
        }

        .chat-info-body {
            padding: 16px;
            overflow-y: auto;
        }

        .chat-info-section {
            margin-bottom: 18px;
        }

        .chat-info-title {
            margin-bottom: 9px;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .chat-info-line {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }

        .chat-info-label {
            color: var(--chat-muted);
        }

        .chat-info-value {
            color: var(--chat-ink);
            font-weight: 700;
            text-align: right;
        }

        .chat-action-stack {
            display: grid;
            gap: 8px;
        }

        .chat-action-stack .btn,
        .chat-action-stack .form-select {
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
        }

        .chat-empty-state {
            height: 100%;
            display: grid;
            place-items: center;
            text-align: center;
            color: var(--chat-muted);
            padding: 30px;
        }

        .chat-empty-icon {
            width: 62px;
            height: 62px;
            margin: 0 auto 12px;
            border-radius: 22px;
            background: #fff;
            color: var(--chat-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .06);
        }

        .chat-log-item {
            padding: 9px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .chat-log-reason {
            color: var(--chat-ink);
            font-size: 12px;
            font-weight: 700;
        }

        .chat-log-meta {
            margin-top: 3px;
            color: var(--chat-muted);
            font-size: 11px;
            line-height: 1.4;
        }

        @media (max-width: 1199px) {
            .chat-app-shell {
                grid-template-columns: 320px minmax(0, 1fr);
            }

            .chat-info-panel {
                display: none;
            }
        }

        @media (max-width: 767px) {
            .chat-app-shell {
                height: auto;
                min-height: 0;
                grid-template-columns: 1fr;
            }

            .chat-sidebar {
                min-height: 420px;
                border-right: 0;
                border-bottom: 1px solid var(--chat-border);
            }

            .chat-main {
                min-height: 560px;
            }

            .chat-message-wrap {
                max-width: 88%;
            }

            .chat-page-head {
                flex-direction: column;
            }
        }
    </style>

    <div class="admin-wrapper chat-page">
        <main class="admin-content">
            <p class="admin-breadcrumb mb-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>">Admin</a> / Tư vấn trực tuyến
            </p>

            <div class="chat-page-head">
                <div>
                    <h2>Tư vấn trực tuyến</h2>
                    <p>Danh sách khách cần trả lời nằm bên trái, nội dung chat nằm bên phải.</p>
                </div>

                <a href="<?php echo e(route('admin.chats.index', ['filter' => $currentFilter, 'conversation' => $selectedConversation?->id])); ?>"
                    class="btn btn-outline-primary chat-refresh-btn">
                    <i class="bx bx-refresh"></i>
                    Làm mới
                </a>
            </div>

            <?php if(session('success')): ?>
                <div class="alert alert-success">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <strong>Không thể xử lý:</strong>
                    <ul class="mb-0 mt-2">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="chat-app-shell">
                <aside class="chat-sidebar">
                    <div class="chat-sidebar-head">
                        <h3 class="chat-sidebar-title">Danh sách hội thoại</h3>
                        <div class="chat-sidebar-sub">
                            Hội thoại có chấm đỏ là khách đang chờ phản hồi.
                        </div>
                    </div>

                    <div class="chat-filter-tabs">
                        <a href="<?php echo e(route('admin.chats.index', ['filter' => 'mine'])); ?>"
                            class="chat-filter-tab <?php echo e($activeFilterClass('mine')); ?>">
                            Của tôi
                            <span class="chat-filter-count"><?php echo e($totalMine); ?></span>
                        </a>

                        <a href="<?php echo e(route('admin.chats.index', ['filter' => 'waiting'])); ?>"
                            class="chat-filter-tab <?php echo e($activeFilterClass('waiting')); ?>">
                            Chờ
                            <span class="chat-filter-count"><?php echo e($totalWaiting); ?></span>
                        </a>

                        <a href="<?php echo e(route('admin.chats.index', ['filter' => 'other'])); ?>"
                            class="chat-filter-tab <?php echo e($activeFilterClass('other')); ?>">
                            Khác
                            <span class="chat-filter-count"><?php echo e($totalOther); ?></span>
                        </a>

                        <a href="<?php echo e(route('admin.chats.index', ['filter' => 'closed'])); ?>"
                            class="chat-filter-tab <?php echo e($activeFilterClass('closed')); ?>">
                            Đóng
                            <span class="chat-filter-count"><?php echo e($totalClosed); ?></span>
                        </a>
                    </div>

                    <div class="chat-conversation-list">
                        <?php $__empty_1 = true; $__currentLoopData = $conversationList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $conversation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $lastMessage = $getLastMessage($conversation);
                                $itemNeedsReply = $needsReply($conversation);
                                $isActiveConversation = $selectedConversation && $selectedConversation->id === $conversation->id;
                                $status = $conversation->status ?? 'waiting';
                                $statusLabel = $statusLabels[$status] ?? $status;
                                $statusClass = $statusClasses[$status] ?? 'chat-status-assigned';
                            ?>

                            <a href="<?php echo e(route('admin.chats.index', ['filter' => $currentFilter, 'conversation' => $conversation->id])); ?>"
                                class="chat-list-item <?php echo e($isActiveConversation ? 'active' : ''); ?> <?php echo e($itemNeedsReply ? 'need-reply' : ''); ?>">
                                <div class="chat-list-top">
                                    <div>
                                        <div class="chat-customer-name">
                                            <?php echo e($conversation->customer_display_name); ?>

                                        </div>
                                        <div class="chat-customer-contact">
                                            <?php echo e($formatContact($conversation)); ?>

                                        </div>
                                    </div>

                                    <div class="chat-list-time">
                                        <?php echo e($formatTime($conversation->last_message_at ?? $conversation->created_at)); ?>

                                    </div>
                                </div>

                                <div class="chat-last-message">
                                    <?php echo e($lastMessage?->message ?? 'Chưa có tin nhắn.'); ?>

                                </div>

                                <div class="chat-list-bottom">
                                    <span class="chat-badge <?php echo e($statusClass); ?>">
                                        <?php echo e($statusLabel); ?>

                                    </span>

                                    <?php if($itemNeedsReply): ?>
                                        <span class="chat-reply-dot" title="Khách đang chờ phản hồi"></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="chat-empty-state">
                                <div>
                                    <div class="chat-empty-icon">
                                        <i class="bx bx-message-square-x"></i>
                                    </div>
                                    <div>Không có hội thoại phù hợp.</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </aside>

                <section class="chat-main">
                    <?php if($selectedConversation): ?>
                        <?php
                            $selectedContact = $formatContact($selectedConversation);
                        ?>

                        <div class="chat-main-head">
                            <div class="chat-main-user">
                                <div class="chat-avatar">
                                    <i class="bx bx-user"></i>
                                </div>

                                <div>
                                    <h3 class="chat-main-name">
                                        <?php echo e($selectedConversation->customer_display_name); ?>

                                    </h3>
                                    <div class="chat-main-contact">
                                        <?php echo e($selectedContact); ?>

                                    </div>
                                </div>
                            </div>

                            <div class="chat-main-actions">
                                <?php if($selectedStatus): ?>
                                    <span class="chat-badge <?php echo e($selectedStatusClass); ?>">
                                        <?php echo e($selectedStatusLabel); ?>

                                    </span>
                                <?php endif; ?>

                                <?php if($selectedConversation->assignedStaff): ?>
                                    <span class="chat-badge chat-status-assigned">
                                        CSKH: <?php echo e($selectedConversation->assignedStaff->name); ?>

                                    </span>
                                <?php endif; ?>

                                <?php if($selectedConversation->status !== 'closed'): ?>
                                    <form method="POST" action="<?php echo e(route('admin.chats.close', $selectedConversation)); ?>">
                                        <?php echo csrf_field(); ?>
                                        <button class="btn btn-outline-danger"
    onclick="return confirm('Đóng hội thoại này? Hội thoại vẫn có thể mở lại nếu khách hoặc nhân viên nhắn tiếp.')">
    <i class="bx bx-check-circle"></i>
    Kết thúc
</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="chat-body" id="adminChatRoomBody">
                            <?php $__empty_1 = true; $__currentLoopData = $selectedConversation->messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chatMessage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $isStaff = $chatMessage->sender_type === 'staff';
                                    $isSystem = $chatMessage->sender_type === 'system';
                                    $rowClass = $isSystem ? 'is-system' : ($isStaff ? 'is-staff' : 'is-customer');
                                    $senderName = $isSystem
                                        ? 'Hệ thống'
                                        : ($isStaff ? ($chatMessage->sender?->name ?? 'Nhân viên') : $selectedConversation->customer_display_name);
                                ?>

                                <div class="chat-message-row <?php echo e($rowClass); ?>">
                                    <div class="chat-message-wrap">
                                        <div class="chat-message-meta">
                                            <span><?php echo e($senderName); ?></span>
                                            <span>·</span>
                                            <span><?php echo e($chatMessage->created_at->format('H:i d/m/Y')); ?></span>
                                        </div>

                                        <div class="chat-message-bubble">
                                            <?php echo e($chatMessage->message); ?>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="chat-empty-state">
                                    <div>
                                        <div class="chat-empty-icon">
                                            <i class="bx bx-message-rounded-dots"></i>
                                        </div>
                                        <div>Chưa có tin nhắn nào trong hội thoại này.</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
<div class="chat-footer">
    <?php if($selectedConversation->status === 'closed'): ?>
        <div class="alert alert-warning py-2 px-3 mb-2 small">
            Hội thoại này đang được đánh dấu đã đóng. Nếu gửi tin nhắn mới, hệ thống sẽ tự mở lại hội thoại.
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('admin.chats.send', $selectedConversation)); ?>" class="chat-reply-form">
        <?php echo csrf_field(); ?>

        <textarea name="message"
            class="form-control"
            rows="1"
            placeholder="<?php echo e($selectedConversation->status === 'closed' ? 'Nhập tin nhắn để mở lại hội thoại...' : 'Nhập câu trả lời tư vấn cho khách...'); ?>"></textarea>

        <button class="btn btn-primary chat-send-btn" title="Gửi">
            <i class="bx bx-send"></i>
        </button>
    </form>
</div>
                    <?php else: ?>
                        <div class="chat-empty-state">
                            <div>
                                <div class="chat-empty-icon">
                                    <i class="bx bx-conversation"></i>
                                </div>
                                <div>Chọn một hội thoại bên trái để bắt đầu phản hồi.</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <aside class="chat-info-panel">
                    <?php if($selectedConversation): ?>
                        <div class="chat-info-head">
                            <div class="chat-info-avatar">
                                <i class="bx bx-user"></i>
                            </div>
                            <div class="chat-info-name">
                                <?php echo e($selectedConversation->customer_display_name); ?>

                            </div>
                            <div class="chat-info-contact">
                                <?php echo e($formatContact($selectedConversation)); ?>

                            </div>
                        </div>

                        <div class="chat-info-body">
                            <div class="chat-info-section">
                                <div class="chat-info-title">Thông tin</div>

                                <div class="chat-info-line">
                                    <span class="chat-info-label">Trạng thái</span>
                                    <span class="chat-info-value"><?php echo e($selectedStatusLabel); ?></span>
                                </div>

                                <div class="chat-info-line">
                                    <span class="chat-info-label">Nhân viên</span>
                                    <span class="chat-info-value">
                                        <?php echo e($selectedConversation->assignedStaff?->name ?? 'Chưa có'); ?>

                                    </span>
                                </div>

                                <div class="chat-info-line">
                                    <span class="chat-info-label">Booking</span>
                                    <span class="chat-info-value">
                                        <?php echo e($selectedConversation->booking_id ? '#' . $selectedConversation->booking_id : 'Không gắn'); ?>

                                    </span>
                                </div>

                                <div class="chat-info-line">
                                    <span class="chat-info-label">Ưu tiên</span>
                                    <span class="chat-info-value">
                                        <?php echo e($selectedConversation->priority_score); ?>

                                    </span>
                                </div>
                            </div>

                            <div class="chat-info-section">
                                <div class="chat-info-title">Thao tác</div>

                                <div class="chat-action-stack">
                                    <?php if($selectedConversation->status === 'waiting'): ?>
                                        <form method="POST" action="<?php echo e(route('admin.chats.take', $selectedConversation)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <button class="btn btn-success w-100">
                                                <i class="bx bx-user-check"></i>
                                                Tiếp nhận
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if($selectedConversation->status !== 'closed'): ?>
                                        <form method="POST" action="<?php echo e(route('admin.chats.transfer', $selectedConversation)); ?>">
                                            <?php echo csrf_field(); ?>

                                            <select name="staff_id" class="form-select mb-2">
                                                <?php $__currentLoopData = $staffs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $staff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($staff->id); ?>"
                                                        <?php echo e($selectedConversation->assigned_staff_id == $staff->id ? 'selected' : ''); ?>>
                                                        <?php echo e($staff->name); ?> - <?php echo e($staff->role); ?>

                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>

                                            <button class="btn btn-outline-primary w-100">
                                                <i class="bx bx-transfer"></i>
                                                Chuyển nhân viên
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="chat-info-section">
                                <div class="chat-info-title">Lịch sử phân công</div>

                                <?php $__empty_1 = true; $__currentLoopData = $selectedConversation->assignmentLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <div class="chat-log-item">
                                        <div class="chat-log-reason">
                                            <?php echo e($log->reason); ?>

                                        </div>
                                        <div class="chat-log-meta">
                                            <?php echo e($log->fromStaff?->name ?? 'Hệ thống'); ?>

                                            →
                                            <?php echo e($log->toStaff?->name ?? 'Không rõ'); ?>

                                            · <?php echo e($log->created_at->format('H:i d/m/Y')); ?>

                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <div class="text-muted small">
                                        Chưa có lịch sử phân công.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>
        </main>

        <footer class="admin-footer">
            <span>MCuong Hotel Admin</span>
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const chatBody = document.getElementById('adminChatRoomBody');

            if (chatBody) {
                chatBody.scrollTop = chatBody.scrollHeight;
            }

            document.querySelectorAll('.chat-reply-form textarea').forEach(function (textarea) {
                textarea.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();

                        if (textarea.value.trim()) {
                            textarea.closest('form').submit();
                        }
                    }
                });
            });
        });
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\booking-web\resources\views/admin/pages/chats/index.blade.php ENDPATH**/ ?>