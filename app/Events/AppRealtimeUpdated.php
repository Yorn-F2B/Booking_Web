<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppRealtimeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $resource,
        public string $action,
        public int|string|null $id = null,
        public bool $isPublic = false,
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.realtime')];

        if ($this->isPublic) {
            $channels[] = new Channel('site.realtime');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'app.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'resource' => $this->resource,
            'action' => $this->action,
            'id' => $this->id,
            'updated_at' => now('Asia/Ho_Chi_Minh')->toIso8601String(),
        ];
    }
}
