<?php

namespace App\Http\Controllers\Admin;

use App\Support\Realtime;
use App\Http\Controllers\Controller;
use App\Models\ChatAssignmentLog;
use App\Models\ChatAttachment;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatAssignmentService;
use App\Services\ChatPresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function __construct(
        private ChatAssignmentService $assignmentService,
        private ChatPresenceService $presenceService,
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $this->presenceService->heartbeat($user);
        $filter = $request->query('filter', 'messages');

        if (!in_array($filter, ['messages', 'archived'], true)) {
            $filter = 'messages';
        }

        $visible = ChatConversation::query()
            ->with([
                'customer',
                'assignedStaff',
                'messages' => fn($query) => $query->latest('id')->limit(1),
            ])
            ->withExists([
                'messages as has_unread_customer' => fn($query) => $query
                    ->where('sender_type', 'customer')
                    ->where('is_read', false),
            ]);

        $this->applyVisibleScope($visible, $user);

        $messagesConversations = (clone $visible)
            ->whereIn('status', ['waiting', 'assigned', 'active'])
            ->latest('last_message_at')
            ->limit(100)
            ->get();

        $archivedConversations = (clone $visible)
            ->where('status', 'closed')
            ->latest('closed_at')
            ->limit(100)
            ->get();

        $messagesUnreadCount = $messagesConversations
            ->where('has_unread_customer', true)
            ->count();

        $archivedUnreadCount = $archivedConversations
            ->where('has_unread_customer', true)
            ->count();

        $conversationList = $filter === 'archived'
            ? $archivedConversations
            : $messagesConversations;

        $selectedConversation = null;

        if ($request->filled('conversation')) {
            $selectedQuery = ChatConversation::query()
                ->with([
                    'customer',
                    'assignedStaff',
                    'messages' => fn ($query) => $query
                        ->reorder()
                        ->latest('id')
                        ->limit(100)
                        ->with(['sender', 'attachments']),
                    'assignmentLogs.fromStaff',
                    'assignmentLogs.toStaff',
                ]);
            $this->applyVisibleScope($selectedQuery, $user);
            $selectedConversation = $selectedQuery->find($request->integer('conversation'));

            if ($selectedConversation) {
                $selectedConversation->setRelation(
                    'messages',
                    $selectedConversation->messages->sortBy('id')->values()
                );
            }
        }

        if (!$selectedConversation) {
            $selectedConversation = $conversationList->first();

            $selectedConversation?->load([
                'customer',
                'assignedStaff',
                'messages' => fn ($query) => $query
                    ->reorder()
                    ->latest('id')
                    ->limit(100)
                    ->with(['sender', 'attachments']),
                'assignmentLogs.fromStaff',
                'assignmentLogs.toStaff',
            ]);

            if ($selectedConversation) {
                $selectedConversation->setRelation(
                    'messages',
                    $selectedConversation->messages->sortBy('id')->values()
                );
            }
        }

        // Chỉ cần mở hội thoại là các tin khách chưa xem được đánh dấu đã đọc,
        // kể cả hội thoại đang nằm trong Lưu trữ.
        if ($selectedConversation) {
            $wasUnread = ChatMessage::query()
                ->where('conversation_id', $selectedConversation->id)
                ->where('sender_type', 'customer')
                ->where('is_read', false)
                ->exists();

            if ($wasUnread) {
                ChatMessage::query()
                    ->where('conversation_id', $selectedConversation->id)
                    ->where('sender_type', 'customer')
                    ->where('is_read', false)
                    ->update([
                        'is_read' => true,
                        'read_at' => now(),
                    ]);

                foreach ([$messagesConversations, $archivedConversations] as $collection) {
                    $item = $collection->firstWhere('id', $selectedConversation->id);
                    if ($item) {
                        $item->setAttribute('has_unread_customer', false);
                    }
                }

                if ($selectedConversation->status === 'closed') {
                    $archivedUnreadCount = max(0, $archivedUnreadCount - 1);
                } else {
                    $messagesUnreadCount = max(0, $messagesUnreadCount - 1);
                }
            }
        }

        $chatStaffs = User::query()
            ->whereIn('role', ['receptionist', 'receptionist_lead'])
            ->where('status', 'active')
            ->with('chatPresence')
            ->orderBy('name')
            ->get();

        $onlineStaffs = $this->presenceService->onlineStaffs();
        $staffLoads = $this->assignmentService->loadsFor($chatStaffs);
        $myPresenceStatus = $this->presenceService->isEligible($user)
            ? $this->presenceService->statusFor($user)
            : null;
        $hasOlderMessages = false;

        if ($selectedConversation && $selectedConversation->messages->isNotEmpty()) {
            $hasOlderMessages = ChatMessage::query()
                ->where('conversation_id', $selectedConversation->id)
                ->where('id', '<', $selectedConversation->messages->min('id'))
                ->exists();
        }

        return view('admin.pages.chats.index', compact(
            'filter',
            'conversationList',
            'selectedConversation',
            'messagesConversations',
            'archivedConversations',
            'messagesUnreadCount',
            'archivedUnreadCount',
            'chatStaffs',
            'onlineStaffs',
            'staffLoads',
            'myPresenceStatus',
            'hasOlderMessages'
        ));
    }

    public function unreadCount()
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $query = ChatConversation::query()
            ->whereIn('status', ['waiting', 'assigned', 'active'])
            ->whereHas('messages', fn ($message) => $message
                ->where('sender_type', 'customer')
                ->where('is_read', false));

        $this->applyVisibleScope($query, $user);

        return response()->json([
            'count' => $query->count(),
        ]);
    }

    public function take(ChatConversation $conversation)
    {
        $user = Auth::user();
        abort_unless($user && $this->presenceService->isEligible($user), 403, 'Chỉ lễ tân đang trực chat mới được tiếp nhận hội thoại.');
        abort_unless($this->presenceService->isOnline($user), 409, 'Hãy chuyển trạng thái trực chat sang Online trước khi tiếp nhận.');
        $this->guardCanAccessConversation($conversation, $user);

        DB::transaction(function () use ($conversation, $user) {
            $locked = ChatConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            $this->guardCanAccessConversation($locked, $user);

            if ($locked->assigned_staff_id && (int) $locked->assigned_staff_id !== (int) $user->id) {
                abort(409, 'Cuộc trò chuyện đã có nhân viên phụ trách.');
            }

            $this->assignmentService->transfer(
                $locked,
                $user,
                'Tiếp nhận cuộc trò chuyện',
                true,
                $user->id
            );
            $locked->update([
                // Tiếp nhận chưa đồng nghĩa đã trả lời.
                'status' => 'waiting',
                'closed_at' => null,
            ]);
        });

        return back()->with('success', 'Đã tiếp nhận cuộc trò chuyện.');
    }

    public function send(Request $request, ChatConversation $conversation)
    {
        $request->validate([
            'message' => ['nullable', 'string', 'max:2000', 'required_without:files'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => [
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,txt,zip',
            ],
        ]);

        $user = Auth::user();
        $storedPaths = [];

        try {
            $message = DB::transaction(function () use ($request, $conversation, $user, &$storedPaths) {
                $lockedConversation = ChatConversation::query()
                    ->lockForUpdate()
                    ->findOrFail($conversation->id);

                // Re-check sau khi lock để tránh gửi nhầm vào hội thoại vừa được chuyển cho người khác.
                $this->guardCanAccessConversation($lockedConversation, $user);

                if (
                    $lockedConversation->assigned_staff_id
                    && $lockedConversation->assigned_staff_id !== $user->id
                    && !in_array($user->role, ['super_admin', 'manager', 'receptionist_lead'], true)
                ) {
                    abort(403, 'Bạn không phải nhân viên phụ trách cuộc trò chuyện này.');
                }

                if (!$lockedConversation->assigned_staff_id) {
                    if ($this->presenceService->isEligible($user) && $this->presenceService->isOnline($user)) {
                        $this->assignmentService->transfer(
                            $lockedConversation,
                            $user,
                            'Tự tiếp nhận khi trả lời khách',
                            false,
                            null
                        );
                    } else {
                        $this->assignmentService->assign($lockedConversation);
                        $lockedConversation->refresh();
                    }
                }

                $message = ChatMessage::create([
                    'conversation_id' => $lockedConversation->id,
                    'sender_type' => 'staff',
                    'sender_id' => $user->id,
                    'message' => filled($request->message) ? trim($request->message) : null,
                    'is_read' => false,
                ]);

                foreach ($request->file('files', []) as $uploadedFile) {
                    $extension = strtolower($uploadedFile->getClientOriginalExtension());
                    $storedName = Str::uuid() . ($extension ? ".{$extension}" : '');
                    $directory = 'chat-attachments/' . now()->format('Y/m/d');
                    $path = $uploadedFile->storeAs($directory, $storedName, 'local');
                    $storedPaths[] = $path;

                    ChatAttachment::create([
                        'message_id' => $message->id,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'mime_type' => $uploadedFile->getMimeType(),
                        'extension' => $extension,
                        'size' => $uploadedFile->getSize(),
                        'type' => str_starts_with((string) $uploadedFile->getMimeType(), 'image/')
                            ? 'image'
                            : 'file',
                    ]);
                }

                $lockedConversation->update([
                    'status' => 'active',
                    'closed_at' => null,
                    'last_message_at' => now(),
                ]);

                return $message->load(['sender', 'attachments', 'conversation']);
            });

            Realtime::chat($message);

            $payload = [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_type' => $message->sender_type,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender?->name ?? 'Nhân viên',
                'message' => $message->message,
                'created_at' => $message->created_at
                    ->timezone('Asia/Ho_Chi_Minh')
                    ->format('H:i d/m/Y'),
                'attachments' => $message->attachments->map(fn ($file) => [
                    'id' => $file->id,
                    'name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size' => $file->size,
                    'type' => $file->type,
                    'download_url' => route('chat.attachments.download', $file),
                ])->values()->all(),
            ];

            if (!$request->expectsJson()) {
                return redirect()
                    ->route('admin.chats.index', [
                        'filter' => 'messages',
                        'conversation' => $message->conversation_id,
                    ])
                    ->with('success', 'Đã gửi tin nhắn.');
            }

            return response()->json([
                'success' => true,
                'message' => $payload,
            ]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            report($e);

            return response()->json([
                'message' => 'Không thể gửi tin nhắn.',
            ], 500);
        }
    }

    public function markRead(ChatConversation $conversation)
    {
        DB::transaction(function () use ($conversation) {
            $locked = ChatConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            $this->guardCanAccessConversation($locked);

            ChatMessage::query()
                ->where('conversation_id', $locked->id)
                ->where('sender_type', 'customer')
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        });

        return response()->json(['success' => true]);
    }

    public function close(ChatConversation $conversation)
    {
        DB::transaction(function () use ($conversation) {
            $locked = ChatConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            $this->guardCanAccessConversation($locked);
            $locked->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.chats.index', ['filter' => 'archived'])
            ->with('success', 'Đã chuyển cuộc trò chuyện vào lưu trữ.');
    }

    public function reopen(ChatConversation $conversation)
    {
        DB::transaction(function () use ($conversation) {
            $locked = ChatConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            $this->guardCanAccessConversation($locked);
            $locked->update([
                'status' => 'active',
                'closed_at' => null,
            ]);
        });

        return redirect()
            ->route('admin.chats.index', [
                'filter' => 'messages',
                'conversation' => $conversation->id,
            ])
            ->with('success', 'Đã khôi phục cuộc trò chuyện.');
    }

    public function transfer(Request $request, ChatConversation $conversation)
    {
        $data = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newStaff = User::query()
            ->whereKey((int) $data['staff_id'])
            ->whereIn('role', ['receptionist', 'receptionist_lead'])
            ->where('status', 'active')
            ->firstOrFail();

        DB::transaction(function () use ($conversation, $newStaff) {
            $locked = ChatConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            $this->guardCanAccessConversation($locked);
            $this->assignmentService->transfer(
                $locked,
                $newStaff,
                'Chuyển cuộc trò chuyện cho nhân viên khác',
                true,
                Auth::id()
            );
            $locked->update([
                'status' => 'active',
                'closed_at' => null,
            ]);
        });

        return back()->with('success', 'Đã chuyển cuộc trò chuyện.');
    }

    public function messages(Request $request, ChatConversation $conversation)
    {
        $this->guardCanAccessConversation($conversation);

        $data = $request->validate([
            'before_id' => ['required', 'integer', 'min:1'],
        ]);

        $messages = ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('id', '<', (int) $data['before_id'])
            ->with(['sender', 'attachments'])
            ->latest('id')
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        $oldestId = $messages->min('id');
        $hasMore = $oldestId
            ? ChatMessage::query()
                ->where('conversation_id', $conversation->id)
                ->where('id', '<', $oldestId)
                ->exists()
            : false;

        return response()->json([
            'messages' => $messages->map(fn (ChatMessage $message) => $this->messagePayload($message)),
            'has_more' => $hasMore,
        ]);
    }

    public function heartbeat()
    {
        $user = Auth::user();
        $wasOnline = $user ? $this->presenceService->isOnline($user) : false;
        $presence = $this->presenceService->heartbeat($user);
        $movedFromStale = $this->assignmentService->handoffStaleOnlineStaff();
        $assignedWaiting = $presence?->status === 'online'
            ? $this->assignmentService->assignWaitingConversations()
            : 0;
        $assignedBookings = $presence?->status === 'online'
            ? $this->assignmentService->assignUnassignedBookings()
            : 0;
        $rebalanced = (!$wasOnline && $presence?->status === 'online')
            ? $this->assignmentService->softRebalance()
            : 0;

        return response()->json([
            'success' => true,
            'status' => $presence?->status,
            'assigned_waiting' => $assignedWaiting,
            'assigned_bookings' => $assignedBookings,
            'rebalanced' => $rebalanced,
            'moved_from_stale' => $movedFromStale,
        ]);
    }

    public function updatePresence(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $this->presenceService->isEligible($user), 403);

        $data = $request->validate([
            'status' => ['required', 'in:online,away,offline'],
            'handoff_mode' => ['nullable', 'in:target,rebalance'],
            'target_staff_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $status = $data['status'];
        $moved = 0;

        if ($status === 'offline') {
            $target = null;
            $mode = $data['handoff_mode'] ?? 'rebalance';

            if ($mode === 'target') {
                $target = User::query()
                    ->whereKey((int) ($data['target_staff_id'] ?? 0))
                    ->whereIn('role', ['receptionist', 'receptionist_lead'])
                    ->where('status', 'active')
                    ->first();

                abort_unless($target, 422, 'Nhân viên nhận bàn giao không hợp lệ.');
            }

            $this->presenceService->setStatus($user, 'offline');
            $moved = $this->assignmentService->handoffAll($user, $mode, $target);
        } else {
            $this->presenceService->setStatus($user, $status);
            if ($status === 'online') {
                $this->assignmentService->handoffStaleOnlineStaff();
                $this->assignmentService->assignWaitingConversations();
                $this->assignmentService->assignUnassignedBookings();
                $this->assignmentService->softRebalance();
            }
        }

        return back()->with('success', $status === 'offline'
            ? "Đã chuyển Offline và bàn giao {$moved} gói booking/chat đang mở."
            : 'Đã cập nhật trạng thái trực chat.');
    }

    public function bulkHandoff(Request $request)
    {
        $actor = Auth::user();
        abort_unless($actor && in_array($actor->role, ['super_admin', 'manager', 'receptionist_lead'], true), 403);

        $data = $request->validate([
            'from_staff_id' => ['required', 'integer', 'exists:users,id'],
            'handoff_mode' => ['required', 'in:target,rebalance'],
            'target_staff_id' => ['nullable', 'integer', 'exists:users,id'],
            'mark_offline' => ['nullable', 'boolean'],
        ]);

        $fromStaff = User::query()
            ->whereKey((int) $data['from_staff_id'])
            ->whereIn('role', ['receptionist', 'receptionist_lead'])
            ->where('status', 'active')
            ->firstOrFail();

        $target = null;
        if ($data['handoff_mode'] === 'target') {
            $target = User::query()
                ->whereKey((int) ($data['target_staff_id'] ?? 0))
                ->whereIn('role', ['receptionist', 'receptionist_lead'])
                ->where('status', 'active')
                ->first();
            abort_unless($target, 422, 'Nhân viên nhận bàn giao không hợp lệ.');
        }

        if ($request->boolean('mark_offline')) {
            $this->presenceService->setStatus($fromStaff, 'offline');
        }

        $moved = $this->assignmentService->handoffAll(
            $fromStaff,
            $data['handoff_mode'],
            $target
        );

        return back()->with('success', "Đã bàn giao {$moved} gói booking/chat của {$fromStaff->name}.");
    }

    private function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender?->name
                ?? ($message->sender_type === 'staff' ? 'Nhân viên' : 'Khách hàng'),
            'message' => $message->message,
            'created_at' => $message->created_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
            'attachments' => $message->attachments->map(fn ($file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'type' => $file->type,
                'download_url' => route('chat.attachments.download', $file),
            ])->values(),
        ];
    }

    private function applyVisibleScope($query, User $user): void
    {
        if (in_array($user->role, ['super_admin', 'manager', 'receptionist_lead'], true)) {
            return;
        }

        abort_unless($user->role === 'receptionist', 403);

        $query->where(function ($scope) use ($user) {
            $scope->where('assigned_staff_id', $user->id)
                ->orWhere(function ($waiting) {
                    $waiting->whereNull('assigned_staff_id')
                        ->where('status', 'waiting');
                });
        });
    }

    private function guardCanAccessConversation(ChatConversation $conversation, ?User $user = null): void
    {
        $user ??= Auth::user();
        abort_unless($user, 403);

        if (in_array($user->role, ['super_admin', 'manager', 'receptionist_lead'], true)) {
            return;
        }

        abort_unless($user->role === 'receptionist', 403);

        $isOwnConversation = (int) $conversation->assigned_staff_id === (int) $user->id;
        $isUnassignedWaiting = $conversation->assigned_staff_id === null
            && $conversation->status === 'waiting';

        abort_unless($isOwnConversation || $isUnassignedWaiting, 403, 'Bạn không có quyền truy cập cuộc trò chuyện này.');
    }

}