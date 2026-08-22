<?php

namespace App\Observers;

use App\Models\RoomInspection;
use App\Support\Realtime;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class RoomInspectionObserver implements ShouldHandleEventsAfterCommit
{
    public function created(RoomInspection $roomInspection): void
    {
        Realtime::inspection($roomInspection, 'created');
    }

    public function updated(RoomInspection $roomInspection): void
    {
        Realtime::inspection($roomInspection, $this->detectAction($roomInspection));
    }

    public function deleted(RoomInspection $roomInspection): void
    {
        Realtime::inspection($roomInspection, 'deleted');
    }

    private function detectAction(RoomInspection $roomInspection): string
    {
        if ($roomInspection->wasChanged('status')) {
            return match ($roomInspection->status) {
                'pending' => 'inspection_requested',
                'reported' => 'inspection_reported',
                'confirmed' => 'inspection_completed',
                'rejected' => 'inspection_rejected',
                default => 'inspection_updated',
            };
        }

        return 'inspection_updated';
    }
}
