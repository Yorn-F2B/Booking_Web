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

    public function __construct(public ChatMessage $message)
    {
        $this->message->loadMissing(['sender', 'attachments', 'conversation']);
    }

    public function broadcastOn(): array
    {
        $channels = [
            // Trưởng lễ tân/Manager/Super Admin cần thấy hàng đợi để điều phối.
            new PrivateChannel('chat.supervisors'),
            // Kênh conversation chỉ dành cho chính khách của hội thoại.
            // Nhân viên không dùng kênh này để tránh subscription cũ tiếp tục
            // nhận nội dung sau khi hội thoại đã được transfer.
            new PrivateChannel('chat.conversation.' . $this->message->conversation_id),
        ];

        if ($this->message->conversation?->assigned_staff_id) {
            $channels[] = new PrivateChannel(
                'chat.staff.' . $this->message->conversation->assigned_staff_id
            );
        }

        if ($this->message->conversation?->customer_id) {
            $channels[] = new PrivateChannel(
                'chat.customer.' . $this->message->conversation->customer_id
            );
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_type' => $this->message->sender_type,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender?->name
                ?? ($this->message->sender_type === 'staff' ? 'Nhân viên' : 'Khách hàng'),
            'message' => $this->message->message,
            'conversation_status' => $this->message->conversation?->status,
            'is_unread_customer' => $this->message->sender_type === 'customer'
                && !$this->message->is_read,
            'created_at' => $this->message->created_at
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('H:i d/m/Y'),
            'attachments' => $this->message->attachments->map(fn ($file) => [
                'id' => $file->id,
                'name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'type' => $file->type,
                'download_url' => route('chat.attachments.download', $file),
            ])->values()->all(),
        ];
    }
}
