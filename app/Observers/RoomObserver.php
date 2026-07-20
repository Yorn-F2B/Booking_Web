<?php

namespace App\Observers;

use App\Models\Room;
use App\Support\Realtime;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class RoomObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Room $room): void
    {
        Realtime::room($room, 'created');
    }

    public function updated(Room $room): void
    {
        Realtime::room($room, $room->wasChanged('status') ? 'status_updated' : 'updated');
    }

    public function deleted(Room $room): void
    {
        Realtime::room($room, 'deleted');
    }
}
