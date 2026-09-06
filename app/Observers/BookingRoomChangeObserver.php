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
            'booking.customer',
            'oldRoom.category',
            'newRoom.category',
        ]);

        $booking = $change->booking;
        if (!$booking || !in_array($booking->status, ['checked_in', 'inspection_requested'], true)) {
            // Phân lại phòng trước check-in đã có luồng email/thông báo riêng.
            // Observer này chỉ dùng cho khách đang lưu trú để tránh gửi trùng.
            return;
        }

        $customerUserId = (int) ($booking->customer?->user_id ?? 0);
        $oldRoomNumber = trim((string) ($change->oldRoom?->room_number ?? ''));
        $newRoomNumber = trim((string) ($change->newRoom?->room_number ?? ''));

        if ($customerUserId <= 0 || $oldRoomNumber === '' || $newRoomNumber === '' || $oldRoomNumber === $newRoomNumber) {
            return;
        }

        $oldCategory = trim((string) ($change->oldRoom?->category?->name ?? $change->oldCategory?->name ?? ''));
        $newCategory = trim((string) ($change->newRoom?->category?->name ?? $change->newCategory?->name ?? ''));
        $reason = trim((string) $change->reason);
        $difference = (float) ($change->price_difference_total ?? 0);

        $message = 'Bạn đã được chuyển từ phòng ' . $oldRoomNumber
            . ($oldCategory !== '' ? ' (' . $oldCategory . ')' : '')
            . ' sang phòng ' . $newRoomNumber
            . ($newCategory !== '' ? ' (' . $newCategory . ')' : '')
            . ' cho booking ' . $booking->booking_code . '.';

        if ($reason !== '') {
            $message .= ' Lý do: ' . rtrim($reason, ". \t\n\r\0\x0B") . '.';
        }

        if ($difference > 0) {
            $message .= ' Phần chênh lệch tiền phòng phát sinh: ' . number_format($difference, 0, ',', '.') . 'đ.';
        } elseif ($difference < 0) {
            $message .= ' Số tiền phòng được giảm do thay đổi này: ' . number_format(abs($difference), 0, ',', '.') . 'đ.';
        } else {
            $message .= ' Thay đổi này không làm phát sinh chênh lệch tiền phòng.';
        }

        app(OperationalNotificationService::class)->toUser(
            $customerUserId,
            'Đã chuyển phòng ' . $oldRoomNumber . ' → ' . $newRoomNumber,
            $message,
            url('/booking-history/' . $booking->id),
            'info',
            [
                'booking_id' => $booking->id,
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
