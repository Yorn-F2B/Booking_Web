<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Room;
use App\Models\RoomActionLog;
use Illuminate\Support\Facades\Auth;

class RoomPreparationService
{
    public function flagPriorityIfNeeded(Booking $booking, Room $room, string $source = 'booking'): void
    {
        if (!in_array($room->status, ['cleaning', 'inspection'], true)) {
            return;
        }

        $marker = '[PRIORITY_BOOKING:' . $booking->id . ']';
        if (str_contains((string) $room->note, $marker)) {
            return;
        }

        $now = now('Asia/Ho_Chi_Minh');
        $statusLabel = $room->status === 'cleaning' ? 'đang dọn' : 'đang chờ kiểm tra';
        $message = $marker . ' ' . $now->format('d/m/Y H:i')
            . ' - ƯU TIÊN DỌN NHANH cho booking ' . $booking->booking_code
            . '. Phòng ' . $statusLabel . '; khách đã chọn hạng/phòng này, vui lòng hoàn tất sớm.';

        $room->update([
            'note' => trim(($room->note ? $room->note . "\n" : '') . $message),
        ]);

        RoomActionLog::create([
            'room_id' => $room->id,
            'user_id' => Auth::id(),
            'action_type' => 'cleaning',
            'action_time' => $now,
            'note' => 'Yêu cầu dọn ưu tiên cho booking ' . $booking->booking_code
                . ' (' . $source . '). Phòng hiện ' . $statusLabel . '.',
        ]);

        BookingLog::create([
            'booking_id' => $booking->id,
            'user_id' => Auth::id(),
            'action' => 'priority_cleaning_auto',
            'description' => 'Tự động gửi yêu cầu buồng phòng ưu tiên chuẩn bị phòng '
                . $room->room_number . ' vì phòng hiện ' . $statusLabel . '.',
        ]);
    }
}
