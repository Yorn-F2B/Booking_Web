<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatAssignmentLog;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\Realtime;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $filter = $request->query('filter', 'mine');

        $baseWith = [
            'customer',
            'assignedStaff',
            'messages',
        ];

        $waitingConversations = ChatConversation::query()
            ->with($baseWith)
            ->where('status', 'waiting')
            ->orderByDesc('priority_score')
            ->orderBy('last_message_at')
            ->get();

        $myConversations = ChatConversation::query()
            ->with($baseWith)
            ->where('assigned_staff_id', $user->id)
            ->whereIn('status', ['assigned', 'active'])
            ->orderByDesc('last_message_at')
            ->get();

        $otherConversations = collect();

        if (in_array($user->role, ['super_admin', 'manager'])) {
            $otherConversations = ChatConversation::query()
                ->with($baseWith)
                ->whereNotNull('assigned_staff_id')
                ->where('assigned_staff_id', '!=', $user->id)
                ->whereIn('status', ['assigned', 'active'])
                ->orderByDesc('last_message_at')
                ->get();
        }

        $closedConversations = ChatConversation::query()
            ->with($baseWith)
            ->where('status', 'closed')
            ->latest('closed_at')
            ->limit(30)
            ->get();

        $conversationList = match ($filter) {
            'waiting' => $waitingConversations,
            'other' => $otherConversations,
            'closed' => $closedConversations,
            default => $myConversations,
        };

        $selectedConversation = null;

        if ($request->filled('conversation')) {
            $selectedConversation = ChatConversation::query()
                ->with([
                    'customer',
                    'assignedStaff',
                    'messages.sender',
                    'assignmentLogs.fromStaff',
                    'assignmentLogs.toStaff',
                ])
                ->find($request->conversation);
        }

        if (!$selectedConversation) {
            $selectedConversation = $conversationList->first()
                ?? $myConversations->first()
                ?? $waitingConversations->first()
                ?? $otherConversations->first()
                ?? $closedConversations->first();

            if ($selectedConversation) {
                $selectedConversation->load([
                    'customer',
                    'assignedStaff',
                    'messages.sender',
                    'assignmentLogs.fromStaff',
                    'assignmentLogs.toStaff',
                ]);
            }
        }

        $staffs = User::query()
            ->whereIn('role', ['receptionist', 'manager'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.pages.chats.index', compact(
            'filter',
            'conversationList',
            'selectedConversation',
            'waitingConversations',
            'myConversations',
            'otherConversations',
            'closedConversations',
            'staffs'
        ));
    }

    public function show(ChatConversation $conversation)
    {
        return redirect()->route('admin.chats.index', [
            'conversation' => $conversation->id,
        ]);
    }

    public function take(ChatConversation $conversation)
    {
        try {
            DB::transaction(function () use ($conversation) {
                $lockedConversation = ChatConversation::query()
                    ->where('id', $conversation->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedConversation->status !== 'waiting' || $lockedConversation->assigned_staff_id) {
                    throw new \Exception('Cuộc trò chuyện này đã có nhân viên tiếp nhận.');
                }

                $lockedConversation->update([
                    'assigned_staff_id' => Auth::id(),
                    'status' => 'assigned',
                ]);

                ChatAssignmentLog::create([
                    'conversation_id' => $lockedConversation->id,
                    'from_staff_id' => null,
                    'to_staff_id' => Auth::id(),
                    'reason' => 'Nhân viên chủ động tiếp nhận cuộc trò chuyện',
                ]);
            });

            return back()->with('success', 'Đã tiếp nhận cuộc trò chuyện.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function send(Request $request, ChatConversation $conversation)
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'message.required' => 'Vui lòng nhập nội dung tin nhắn.',
            'message.max' => 'Tin nhắn không được vượt quá 2000 ký tự.',
        ]);

        $user = Auth::user();

        if (!$conversation->assigned_staff_id) {
            $conversation->update([
                'status' => 'active',
                'closed_at' => null,
                'last_message_at' => now(),
            ]);

            ChatAssignmentLog::create([
                'conversation_id' => $conversation->id,
                'from_staff_id' => null,
                'to_staff_id' => $user->id,
                'reason' => 'Tự nhận khi phản hồi khách',
            ]);
        }

        if (
            $conversation->assigned_staff_id !== $user->id
            && !in_array($user->role, ['super_admin', 'manager'])
        ) {
            return back()->with('error', 'Bạn không phải nhân viên phụ trách cuộc trò chuyện này.');
        }

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'staff',
            'sender_id' => $user->id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        $conversation->update([
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        return back()->with('success', 'Đã gửi tin nhắn.');
    }

    public function close(ChatConversation $conversation)
    {
        $conversation->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return redirect()
            ->route('admin.chats.index', [
                'filter' => 'closed',
                'conversation' => $conversation->id,
            ])
            ->with('success', 'Đã đóng hội thoại. Nếu khách hoặc nhân viên nhắn tiếp, hội thoại sẽ tự mở lại.');
    }

    public function transfer(Request $request, ChatConversation $conversation)
    {
        $request->validate([
            'staff_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newStaff = User::query()
            ->where('id', $request->staff_id)
            ->whereIn('role', ['receptionist', 'manager'])
            ->where('status', 'active')
            ->firstOrFail();

        $fromStaffId = $conversation->assigned_staff_id;

        $conversation->update([
            'assigned_staff_id' => $newStaff->id,
            'status' => 'assigned',
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