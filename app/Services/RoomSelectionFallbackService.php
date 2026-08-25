<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingLog;
use Illuminate\Support\Facades\DB;

class RoomSelectionFallbackService
{
    public function __construct(
        private readonly BookingCancellationService $cancellations
    ) {
    }

    /**
     * Khách phản hồi sau khi khách sạn xác nhận không thể đáp ứng yêu cầu phòng.
     *
     * - accept: giữ nguyên phòng dự phòng và tiếp tục booking, không thu phí yêu cầu phòng.
     * - decline: hủy booking do khách sạn không đáp ứng yêu cầu và hoàn lại toàn bộ số đã thanh toán.
     */
    public function respond(
        Booking $booking,
        string $decision,
        ?int $actorUserId,
        string $actorLabel
    ): array {
        if (!in_array($decision, ['accept', 'decline'], true)) {
            throw new \InvalidArgumentException('Phản hồi phòng dự phòng không hợp lệ.');
        }

        if ($decision === 'decline') {
            $refundDue = $this->cancellations->cancelForRoomRequestFailure(
                $booking,
                $actorUserId,
                'manual_room_fallback_declined',
                $actorLabel,
                'Khách từ chối phòng dự phòng vì khách sạn không thể đáp ứng yêu cầu chọn phòng.'
            );

            return [
                'accepted' => false,
                'refund_due' => $refundDue,
            ];
        }

        return DB::transaction(function () use ($booking, $actorUserId, $actorLabel) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->room_selection_mode !== 'manual'
                || $locked->room_selection_status !== 'awaiting_guest') {
                throw new \RuntimeException('Yêu cầu phòng không còn chờ khách xác nhận.');
            }

            if (!in_array($locked->status, ['pending', 'confirmed'], true) || $locked->actual_check_in) {
                throw new \RuntimeException('Booking không còn ở trạng thái có thể xác nhận phòng dự phòng.');
            }

            $locked->forceFill([
                'room_selection_status' => 'fallback_accepted',
                'room_selection_fee' => 0,
                'room_selection_guest_decided_at' => now('Asia/Ho_Chi_Minh'),
            ])->save();

            BookingLog::create([
                'booking_id' => $locked->id,
                'user_id' => $actorUserId,
                'action' => 'manual_room_fallback_accepted',
                'description' => $actorLabel . ' đồng ý sử dụng phòng dự phòng sau khi khách sạn không thể đáp ứng yêu cầu chọn phòng. Không thu phí đảm bảo yêu cầu phòng.',
            ]);

            return [
                'accepted' => true,
                'refund_due' => 0,
            ];
        });
    }
}
