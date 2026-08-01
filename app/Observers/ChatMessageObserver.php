<?php

namespace App\Observers;

use App\Models\ChatMessage;
use App\Support\Realtime;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ChatMessageObserver implements ShouldHandleEventsAfterCommit
{
    public function created(ChatMessage $message): void
    {
        Realtime::chat($message, 'sent');
    }
}
