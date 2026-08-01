<?php

namespace App\Observers;

use App\Events\AppRealtimeUpdated;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppRealtimeObserver implements ShouldHandleEventsAfterCommit
{
    private const PUBLIC_RESOURCES = [
        'amenity',
        'hotel_review',
        'promotion',
        'promotion_room_upgrade_offer',
        'promotion_service_offer',
        'room_category',
        'room_category_image',
        'service',
    ];

    public function created(Model $model): void
    {
        $this->broadcast($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->broadcast($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->broadcast($model, 'deleted');
    }

    public function restored(Model $model): void
    {
        $this->broadcast($model, 'restored');
    }

    private function broadcast(Model $model, string $action): void
    {
        $resource = str(class_basename($model))->snake()->toString();

        try {
            event(new AppRealtimeUpdated(
                $resource,
                $action,
                $model->getKey(),
                in_array($resource, self::PUBLIC_RESOURCES, true),
            ));
        } catch (Throwable $exception) {
            Log::warning('Không thể phát cập nhật realtime tổng quát.', [
                'resource' => $resource,
                'action' => $action,
                'id' => $model->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
