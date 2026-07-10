<?php

namespace App\Http\Controllers\Admin;

use App\Events\ChatMessageRealtimeSent;
use App\Http\Controllers\Controller;
use App\Models\ChatAssignmentLog;
use App\Models\ChatAttachment;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
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

        if (!in_array($user->role, ['super_admin', 'manager'], true)) {
            $visible->where(function ($query) use ($user) {
                $query->where('assigned_staff_id', $user->id)
                    ->orWhere(function ($query) {
                        $query->whereNull('assigned_staff_id')
                            ->where('status', 'waiting');
                    });
            });
        }

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
            $selectedConversation = ChatConversation::query()
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
                ])
                ->find($request->integer('conversation'));

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

        $staffs = User::query()
            ->whereIn('role', ['receptionist', 'receptionist_lead', 'manager'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.pages.chats.index', compact(
            'filter',
            'conversationList',
            'selectedConversation',
            'messagesConversations',
            'archivedConversations',
            'messagesUnreadCount',
            'archivedUnreadCount',
            'staffs'
        ));
    }

    public function take(ChatConversation $conversation)
    {
        DB::transaction(function () use ($conversation) {
            $conversation = ChatConversation::query()
                ->lockForUpdate()
                ->findOrFail($conversation->id);

            if ($conversation->assigned_staff_id && $conversation->assigned_staff_id !== Auth::id()) {
                abort(409, 'Cuộc trò chuyện đã có nhân viên phụ trách.');
            }

            $conversation->update([
                'assigned_staff_id' => Auth::id(),
                // Tiếp nhận chưa đồng nghĩa đã trả lời.
                'status' => 'waiting',
                'closed_at' => null,
            ]);

            ChatAssignmentLog::create([
                'conversation_id' => $conversation->id,
                'from_staff_id' => null,
                'to_staff_id' => Auth::id(),
                'reason' => 'Tiếp nhận cuộc trò chuyện',
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

        if (
            $conversation->assigned_staff_id
            && $conversation->assigned_staff_id !== $user->id
            && !in_array($user->role, ['super_admin', 'manager'], true)
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Bạn không phải nhân viên phụ trách cuộc trò chuyện này.',
                ], 403);
            }

            return back()->with('error', 'Bạn không phải nhân viên phụ trách cuộc trò chuyện này.');
        }

        $storedPaths = [];

        try {
            $message = DB::transaction(function () use ($request, $conversation, $user, &$storedPaths) {
                if (!$conversation->assigned_staff_id) {
                    $conversation->update(['assigned_staff_id' => $user->id]);

                    ChatAssignmentLog::create([
                        'conversation_id' => $conversation->id,
                        'from_staff_id' => null,
                        'to_staff_id' => $user->id,
                        'reason' => 'Tự tiếp nhận khi trả lời khách',
                    ]);
                }

                $message = ChatMessage::create([
                    'conversation_id' => $conversation->id,
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

                // Nhân viên vừa trả lời => chuyển về Tin nhắn.
                $conversation->update([
                    'status' => 'active',
                    'closed_at' => null,
                    'last_message_at' => now(),
                ]);

                return $message->load(['sender', 'attachments', 'conversation']);
            });

            event(new ChatMessageRealtimeSent($message));

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
                        'conversation' => $conversation->id,
                    ])
                    ->with('success', 'Đã gửi tin nhắn.');
            }

            return response()->json([
                'success' => true,
                'message' => $payload,
            ]);
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
        ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    public function close(ChatConversation $conversation)
    {
        $conversation->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return redirect()
            ->route('admin.chats.index', ['filter' => 'archived'])
            ->with('success', 'Đã chuyển cuộc trò chuyện vào lưu trữ.');
    }

    public function reopen(ChatConversation $conversation)
    {
        $conversation->update([
            'status' => 'active',
            'closed_at' => null,
        ]);

        return redirect()
            ->route('admin.chats.index', [
                'filter' => 'messages',
                'conversation' => $conversation->id,
            ])
            ->with('success', 'Đã khôi phục cuộc trò chuyện.');
    }

    public function transfer(Request $request, ChatConversation $conversation)
    {
        $request->validate([
            'staff_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newStaff = User::query()
            ->whereKey($request->integer('staff_id'))
            ->whereIn('role', ['receptionist', 'receptionist_lead', 'manager'])
            ->where('status', 'active')
            ->firstOrFail();

        $fromStaffId = $conversation->assigned_staff_id;

        $conversation->update([
            'assigned_staff_id' => $newStaff->id,
            'status' => 'active',
            'closed_at' => null,
        ]);

        ChatAssignmentLog::create([
            'conversation_id' => $conversation->id,
            'from_staff_id' => $fromStaffId,
            'to_staff_id' => $newStaff->id,
            'reason' => 'Chuyển cuộc trò chuyện cho nhân viên khác',
        ]);

        return back()->with('success', 'Đã chuyển cuộc trò chuyện.');
    }
}