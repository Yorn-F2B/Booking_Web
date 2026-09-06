<?php

namespace App\Observers;

use App\Models\BookingRoomChange;
use App\Services\OperationalNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BookingRoomChangeObserver implements ShouldHandleEventsAfterCommit
{
    public function created(BookingRoomChange $change): void
    {
        $change->loadMissing([
            'booking.customer.user',
            'oldRoom.category',
            'newRoom.category',
            'oldCategory',
            'newCategory',
        ]);

        $booking = $change->booking;
        if (!$booking) {
            return;
        }

        $oldRoomNumber = trim((string) ($change->oldRoom?->room_number ?? ''));
        $newRoomNumber = trim((string) ($change->newRoom?->room_number ?? ''));

        // Chỉ phát thông báo khi đây thực sự là một lần chuyển sang phòng vật lý khác.
        if ($oldRoomNumber === '' || $newRoomNumber === '' || $oldRoomNumber === $newRoomNumber) {
            return;
        }

        $oldCategory = trim((string) ($change->oldRoom?->category?->name ?? $change->oldCategory?->name ?? ''));
        $newCategory = trim((string) ($change->newRoom?->category?->name ?? $change->newCategory?->name ?? ''));
        $reason = trim((string) $change->reason);
        $difference = (float) ($change->price_difference_total ?? 0);

        $message = 'Booking ' . $booking->booking_code . ': bạn đã được chuyển từ phòng ' . $oldRoomNumber
            . ($oldCategory !== '' ? ' (' . $oldCategory . ')' : '')
            . ' sang phòng ' . $newRoomNumber
            . ($newCategory !== '' ? ' (' . $newCategory . ')' : '') . '.';

        if ($reason !== '') {
            $message .= ' Lý do: ' . rtrim($reason, ". \t\n\r\0\x0B") . '.';
        }

        if ($difference > 0) {
            $message .= ' Chênh lệch tiền phòng: +' . number_format($difference, 0, ',', '.') . 'đ.';
        } elseif ($difference < 0) {
            $message .= ' Chênh lệch tiền phòng: -' . number_format(abs($difference), 0, ',', '.') . 'đ.';
        } else {
            $message .= ' Không phát sinh chênh lệch tiền phòng.';
        }

        // Dùng toBookingCustomer thay vì toUser để cả booking online và booking tại quầy
        // (có email nhưng không có tài khoản) đều nhận được cập nhật. Không giới hạn trạng
        // thái booking: đổi phòng trước check-in hay trong lúc lưu trú đều phải báo khách.
        app(OperationalNotificationService::class)->toBookingCustomer(
            $booking,
            'Đổi phòng ' . $oldRoomNumber . ' → ' . $newRoomNumber,
            $message,
            'info',
            null,
            [
                'room_id' => $change->new_room_id,
                'meta' => [
                    'event' => 'room_changed',
                    'room_change_id' => $change->id,
                    'old_room' => $oldRoomNumber,
                    'new_room' => $newRoomNumber,
                ],
            ]
        );
    }
}
