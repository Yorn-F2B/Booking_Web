<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\ChatAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\Realtime;

class ChatController extends Controller
{
    public function __construct(
        private ChatAssignmentService $assignmentService
    ) {
    }

    public function messages(Request $request)
    {
        $conversation = $this->getCurrentConversation($request);

        if (!$conversation) {
            return response()->json([
                'conversation' => null,
                'messages' => [],
            ]);
        }

        $conversation->load(['assignedStaff', 'messages.sender']);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'assigned_staff_name' => $conversation->assignedStaff?->name,
            ],
            'messages' => $conversation->messages
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'sender_type' => $message->sender_type,
                        'sender_name' => $message->sender?->name,
                        'message' => $message->message,
                        'created_at' => $message->created_at->format('H:i d/m/Y'),
                    ];
                })
                ->values(),
        ]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'guest_name' => ['nullable', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
        ], [
            'message.required' => 'Vui lòng nhập nội dung tin nhắn.',
            'message.max' => 'Tin nhắn không được vượt quá 2000 ký tự.',
            'guest_email.email' => 'Email không hợp lệ.',
        ]);
        $conversation = $this->getOrCreateConversation($request);

        if ($conversation->status === 'closed') {
            $conversation->update([
                'status' => $conversation->assigned_staff_id ? 'active' : 'waiting',
                'closed_at' => null,
            ]);
        }

        if (!$conversation->assigned_staff_id) {
            $this->assignmentService->assign($conversation);
            $conversation->refresh();
        }

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'customer',
            'sender_id' => Auth::check() && Auth::user()->role === 'customer' ? Auth::id() : null,
            'message' => $request->message,
            'is_read' => false,
        ]);
        $conversation->update([
            'status' => $conversation->assigned_staff_id ? 'active' : 'waiting',
            'closed_at' => null,
            'last_message_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi tin nhắn.',
        ]);
    }

    public function close(Request $request)
    {
        $conversation = $this->getCurrentConversation($request);

        if ($conversation) {
            $conversation->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            session()->forget('chat_conversation_id');
        }

        return response()->json([
            'success' => true,
        ]);
    }

    private function getCurrentConversation(Request $request): ?ChatConversation
    {
        if (Auth::check() && Auth::user()->role === 'customer') {
            return ChatConversation::query()
                ->where('customer_id', Auth::id())
                ->whereIn('status', ['waiting', 'assigned', 'active', 'closed'])
                ->latest('last_message_at')
                ->latest()
                ->first();
        }

        $conversationId = session('chat_conversation_id');

        if (!$conversationId) {
            return null;
        }

        return ChatConversation::query()
            ->where('id', $conversationId)
            ->whereIn('status', ['waiting', 'assigned', 'active', 'closed'])
            ->first();
    }

    private function getOrCreateConversation(Request $request): ChatConversation
    {
        $existing = $this->getCurrentConversation($request);

        if ($existing) {
            return $existing;
        }

        $conversation = ChatConversation::create([
            'customer_id' => Auth::check() && Auth::user()->role === 'customer' ? Auth::id() : null,
            'booking_id' => $request->booking_id,
            'guest_name' => $request->guest_name,
            'guest_email' => $request->guest_email,
            'guest_phone' => $request->guest_phone,
            'status' => 'waiting',
            'priority_score' => $this->calculatePriorityScore($request),
            'last_message_at' => now(),
        ]);

        session([
            'chat_conversation_id' => $conversation->id,
        ]);

        $this->assignmentService->assign($conversation);

        return $conversation->fresh();
    }

    private function calculatePriorityScore(Request $request): int
    {
        $message = mb_strtolower($request->message ?? '');

        if (
            str_contains($message, 'thanh toán')
            || str_contains($message, 'cọc')
            || str_contains($message, 'vnpay')
            || str_contains($message, 'lỗi tiền')
        ) {
            return 100;
        }

        if (
            str_contains($message, 'check in')
            || str_contains($message, 'nhận phòng')
            || str_contains($message, 'hôm nay')
        ) {
            return 90;
        }

        if ($request->booking_id) {
            return 80;
        }

        if (
            str_contains($message, 'hủy')
            || str_contains($message, 'đổi ngày')
            || str_contains($message, 'hoàn tiền')
        ) {
            return 70;
        }

        if (
            str_contains($message, 'còn phòng')
            || str_contains($message, 'giá phòng')
            || str_contains($message, 'đặt phòng')
        ) {
            return 50;
        }

        return 20;
    }
}