<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageRealtimeSent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ChatMessage $message,
        public string $action = 'sent'
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.realtime'),
            new PrivateChannel('chat.conversation.' . $this->message->conversation_id),
        ];

        $conversation = $this->message->relationLoaded('conversation') ? $this->message->conversation : null;
        $customerUserId = $conversation->customer_id ?? null;

        if ($customerUserId) {
            $channels[] = new PrivateChannel('chat.customer.' . $customerUserId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        $sender = $this->message->relationLoaded('sender') ? $this->message->sender : null;

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_type' => $this->message->sender_type,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $sender->name ?? ($this->message->sender_type === 'customer' ? 'Khách hàng' : 'Nhân viên'),
            'message' => $this->message->message,
            'action' => $this->action,
            'created_at' => $this->message->created_at
                ? $this->message->created_at->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')
                : now('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
        ];
    }
}
