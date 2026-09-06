<?php

namespace App\Observers;

use App\Models\RoomInspection;
use App\Services\OperationalNotificationService;
use App\Support\Realtime;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class RoomInspectionObserver implements ShouldHandleEventsAfterCommit
{
    public function created(RoomInspection $roomInspection): void
    {
        Realtime::inspection($roomInspection, 'created');
        $this->notifyCustomer($roomInspection, 'created');
    }

    public function updated(RoomInspection $roomInspection): void
    {
        Realtime::inspection($roomInspection, $this->detectAction($roomInspection));
        $this->notifyCustomer($roomInspection, 'updated');
    }

    public function deleted(RoomInspection $roomInspection): void
    {
        Realtime::inspection($roomInspection, 'deleted');
    }

    private function notifyCustomer(RoomInspection $inspection, string $phase): void
    {
        $actor = auth()->user();
        if (!$actor || $actor->role === 'customer') {
            return;
        }

        if ($phase === 'updated' && !$inspection->wasChanged([
            'status', 'workflow_stage', 'approved_total', 'damage_total', 'minibar_total',
            'guest_consulted_at', 'confirmed_at', 'last_update_summary',
        ])) {
            return;
        }

        $inspection->loadMissing(['booking.customer', 'room']);
        $booking = $inspection->booking;
        if (!$booking) {
            return;
        }

        $room = $inspection->room?->room_number;
        $roomText = $room ? ' phòng ' . $room : '';
        $code = $booking->booking_code;

        if ($phase === 'created') {
            $title = 'Bắt đầu kiểm tra phòng - booking ' . $code;
            $message = 'Khách sạn đã bắt đầu kiểm tra' . $roomText . ' trước khi hoàn tất trả phòng cho booking ' . $code . '.';
            $type = 'info';
            $event = 'room_inspection_created';
        } else {
            [$title, $message, $type, $event] = match ((string) $inspection->status) {
                'reported' => [
                    'Đã có kết quả kiểm tra' . $roomText . ' - booking ' . $code,
                    'Bộ phận buồng phòng đã gửi kết quả kiểm tra' . $roomText . ' cho booking ' . $code
                        . '. Các khoản minibar/hư hỏng nếu có đang được lễ tân xác nhận với bạn.',
                    'warning', 'room_inspection_reported',
                ],
                'confirmed' => [
                    'Đã hoàn tất kiểm tra' . $roomText . ' - booking ' . $code,
                    'Khách sạn đã hoàn tất kiểm tra' . $roomText . ' cho booking ' . $code
                        . '. Khoản phát sinh được xác nhận: ' . number_format((float) $inspection->approved_total, 0, ',', '.') . 'đ.',
                    'success', 'room_inspection_confirmed',
                ],
                'rejected' => [
                    'Kết quả kiểm tra cần xử lý lại - booking ' . $code,
                    'Kết quả kiểm tra' . $roomText . ' của booking ' . $code . ' đang được yêu cầu kiểm tra/xử lý lại.',
                    'warning', 'room_inspection_rejected',
                ],
                default => [
                    'Cập nhật kiểm tra phòng - booking ' . $code,
                    'Khách sạn vừa cập nhật quá trình kiểm tra' . $roomText . ' của booking ' . $code . '.',
                    'info', 'room_inspection_updated',
                ],
            };
        }

        app(OperationalNotificationService::class)->toBookingCustomer(
            $booking,
            $title,
            $message,
            $type,
            null,
            [
                'room_id' => $inspection->room_id,
                'meta' => ['event' => $event, 'room_inspection_id' => $inspection->id],
            ]
        );
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
