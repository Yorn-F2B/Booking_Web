<?php

namespace App\Support;

use App\Events\AppRealtimeUpdated;
use App\Events\BookingRealtimeUpdated;
use App\Events\ChatMessageRealtimeSent;
use App\Events\InspectionRealtimeUpdated;
use App\Events\RoomRealtimeUpdated;
use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\RoomInspection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class Realtime
{
    /** @var array<string, true> */
    private static array $broadcastedThisRequest = [];

    public static function booking(Booking|int|null $booking, string $action = 'updated', bool $broadcastRooms = true): void
    {
        if (!$booking) {
            return;
        }

        if (!$booking instanceof Booking) {
            $booking = Booking::find($booking);
        }

        if (!$booking) {
            return;
        }

        try {
            $booking->refresh();
        } catch (Throwable) {
            // Model có thể vừa bị xóa, vẫn broadcast dữ liệu đang có nếu được.
        }

        self::safeLoad($booking, [
            'customer',
            'roomCategory',
            'bookingRooms.room.category',
            'serviceItems.service',
            'roomInspections.items',
        ]);

        self::safeDispatch(new AppRealtimeUpdated('booking', $action, null), 'booking_signal', [
            'booking_id' => $booking->id,
            'action' => $action,
        ]);
        self::safeDispatch(new BookingRealtimeUpdated($booking, $action), 'booking', [
            'booking_id' => $booking->id,
            'action' => $action,
        ]);

        if (!$broadcastRooms) {
            return;
        }

        $bookingRooms = $booking->relationLoaded('bookingRooms') ? $booking->bookingRooms : collect();

        foreach ($bookingRooms as $bookingRoom) {
            if ($bookingRoom && isset($bookingRoom->room) && $bookingRoom->room) {
                self::room($bookingRoom->room, $action);
            }
        }
    }

    public static function room(Room|int|null $room, string $action = 'updated'): void
    {
        if (!$room) {
            return;
        }

        if (!$room instanceof Room) {
            $room = Room::find($room);
        }

        if (!$room) {
            return;
        }

        try {
            $room->refresh();
        } catch (Throwable) {
            // Bỏ qua nếu model vừa bị xóa.
        }

        self::safeLoad($room, ['category', 'roomCategory']);

        self::safeDispatch(new AppRealtimeUpdated('room', $action, null), 'room_signal', [
            'room_id' => $room->id,
            'action' => $action,
        ]);
        self::safeDispatch(new RoomRealtimeUpdated($room, $action), 'room', [
            'room_id' => $room->id,
            'action' => $action,
        ]);
    }

    public static function rooms(iterable|null $rooms, string $action = 'updated'): void
    {
        if (!$rooms) {
            return;
        }

        collect($rooms)
            ->filter()
            ->unique(fn ($room) => is_object($room) && isset($room->id) ? $room->id : $room)
            ->each(fn ($room) => self::room($room, $action));
    }

    public static function inspection(RoomInspection|int|null $inspection, string $action = 'updated'): void
    {
        if (!$inspection) {
            return;
        }

        if (!$inspection instanceof RoomInspection) {
            $inspection = RoomInspection::find($inspection);
        }

        if (!$inspection) {
            return;
        }

        if (!self::claimOnce('inspection', $inspection->id)) {
            return;
        }

        try {
            $inspection->refresh();
        } catch (Throwable) {
            // Bỏ qua nếu model vừa bị xóa.
        }

        self::safeLoad($inspection, [
            'booking.customer',
            'booking.roomCategory',
            'room.category',
            'inspector',
            'confirmer',
            'items',
        ]);

        self::safeDispatch(new AppRealtimeUpdated('room_inspection', $action, null), 'inspection_signal', [
            'inspection_id' => $inspection->id,
            'action' => $action,
        ]);
        self::safeDispatch(new InspectionRealtimeUpdated($inspection, $action), 'inspection', [
            'inspection_id' => $inspection->id,
            'action' => $action,
        ]);

        // Booking detail của lễ tân/khách cần biết phiếu kiểm phòng vừa đổi.
        // Room model không thay đổi trong các bước đối chiếu/recheck nên không broadcast
        // room lặp lại ở đây; RoomObserver sẽ tự phát khi trạng thái phòng thật sự đổi.
        if ($inspection->relationLoaded('booking') && $inspection->booking) {
            self::booking($inspection->booking, $action, false);
        }
    }

    public static function chat(ChatMessage|int|null $message, string $action = 'sent'): void
    {
        if (!$message) {
            return;
        }

        if (!$message instanceof ChatMessage) {
            $message = ChatMessage::find($message);
        }

        if (!$message) {
            return;
        }

        try {
            $message->refresh();
        } catch (Throwable) {
            // Bỏ qua nếu model vừa bị xóa.
        }

        self::safeLoad($message, [
            'conversation.customer',
            'conversation.assignedStaff',
            'sender',
        ]);

        self::safeDispatch(new ChatMessageRealtimeSent($message), 'chat', [
            'message_id' => $message->id,
            'action' => $action,
        ]);
    }

    private static function claimOnce(string $resource, int|string|null $id): bool
    {
        if ($id === null || $id === '') {
            return true;
        }

        $key = $resource . ':' . (string) $id;

        if (isset(self::$broadcastedThisRequest[$key])) {
            return false;
        }

        self::$broadcastedThisRequest[$key] = true;

        return true;
    }

    private static function safeDispatch(object $event, string $channel, array $context = []): void
    {
        try {
            event($event);
        } catch (Throwable $exception) {
            Log::warning('Không thể phát cập nhật realtime; nghiệp vụ chính vẫn được hoàn tất.', array_merge($context, [
                'realtime_channel' => $channel,
                'error' => $exception->getMessage(),
            ]));
        }
    }

    private static function safeLoad(Model $model, array $relations): void
    {
        foreach ($relations as $relation) {
            $rootRelation = explode('.', $relation)[0] ?? $relation;

            if (!method_exists($model, $rootRelation)) {
                continue;
            }

            try {
                $model->loadMissing($relation);
            } catch (Throwable) {
                // Quan hệ không tồn tại hoặc dữ liệu đang thiếu thì bỏ qua, không làm hỏng request chính.
            }
        }
    }
}
