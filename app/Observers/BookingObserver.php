<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\BookingStaffAssignment;
use App\Services\ChatAssignmentService;
use App\Services\OperationalNotificationService;
use App\Services\RoomReservationStatusService;
use App\Support\Realtime;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BookingObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private ChatAssignmentService $assignmentService,
        private RoomReservationStatusService $roomReservationStatusService
    ) {
    }

    public function created(Booking $booking): void
    {
        $this->assignmentService->ensureAvailableBookingAssignment($booking);
        $this->syncRoomReservationStatus($booking);
        Realtime::booking($booking, 'created');
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('status') && in_array($booking->status, ['checked_out', 'completed', 'cancelled', 'canceled'], true)) {
            BookingStaffAssignment::query()
                ->where('booking_id', $booking->id)
                ->where('status', 'active')
                ->update(['status' => 'done']);
        } elseif (in_array($booking->status, ['pending', 'confirmed', 'checked_in', 'inspection_requested'], true)) {
            $this->assignmentService->ensureAvailableBookingAssignment($booking);
        }

        if ($booking->wasChanged('status')
            || $booking->wasChanged('payment_expires_at')
            || $booking->wasChanged('payment_status')) {
            $this->syncRoomReservationStatus($booking);
        }

        $this->notifyCustomerForOperationalChange($booking);
        Realtime::booking($booking, $this->detectAction($booking));
    }

    public function deleted(Booking $booking): void
    {
        Realtime::booking($booking, 'deleted');
    }

    private function notifyCustomerForOperationalChange(Booking $booking): void
    {
        $actor = auth()->user();
        $staffAction = $actor && $actor->role !== 'customer';
        if (!$staffAction) {
            return;
        }

        $code = (string) $booking->booking_code;
        $service = app(OperationalNotificationService::class);

        if ($booking->wasChanged('status')) {
            $content = match ((string) $booking->status) {
                'confirmed' => [
                    'Booking ' . $code . ' đã được xác nhận',
                    'Khách sạn đã xác nhận booking ' . $code . '. Bạn có thể mở chi tiết booking để kiểm tra thời gian lưu trú, phòng và các khoản đã thanh toán.',
                    'success',
                    'booking_confirmed',
                ],
                'checked_in' => [
                    'Đã nhận phòng - booking ' . $code,
                    'Khách sạn đã ghi nhận bạn đã nhận phòng cho booking ' . $code . '. Nếu phát sinh vấn đề trong thời gian lưu trú, bạn có thể báo sự cố trên hệ thống hoặc liên hệ lễ tân.',
                    'success',
                    'booking_checked_in',
                ],
                'inspection_requested' => [
                    'Đang kiểm tra phòng trước khi trả - booking ' . $code,
                    'Khách sạn đã ghi nhận yêu cầu trả phòng của booking ' . $code . ' và đang kiểm tra phòng, dịch vụ và các khoản phát sinh trước khi hoàn tất.',
                    'info',
                    'booking_inspection_requested',
                ],
                'checked_out', 'completed' => [
                    'Đã trả phòng - booking ' . $code,
                    'Booking ' . $code . ' đã hoàn tất trả phòng. Cảm ơn bạn đã sử dụng dịch vụ tại MCuong Hotel.',
                    'success',
                    'booking_checked_out',
                ],
                'cancelled', 'canceled' => [
                    'Booking ' . $code . ' đã được hủy',
                    'Khách sạn đã ghi nhận booking ' . $code . ' ở trạng thái đã hủy. Nếu có khoản hoàn tiền, trạng thái xử lý sẽ tiếp tục được cập nhật trên booking.',
                    'warning',
                    'booking_cancelled_by_staff',
                ],
                default => null,
            };

            if ($content) {
                [$title, $message, $type, $event] = $content;
                $service->toBookingCustomer($booking, $title, $message, $type, null, [
                    'meta' => ['event' => $event],
                ]);
                return;
            }
        }

        if ($booking->wasChanged('check_out_at') || $booking->wasChanged('check_out_date')) {
            $checkOut = $booking->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
            $service->toBookingCustomer(
                $booking,
                'Đã cập nhật thời gian trả phòng - booking ' . $code,
                'Thời gian trả phòng của booking ' . $code . ' đã được cập nhật' . ($checkOut ? ' thành ' . $checkOut : '') . '. Vui lòng mở chi tiết booking để kiểm tra các khoản tiền liên quan nếu có.',
                'info',
                null,
                ['meta' => ['event' => 'booking_checkout_time_changed']]
            );
            return;
        }

        if ($booking->wasChanged('payment_status') || $booking->wasChanged('deposit_amount')) {
            $paid = (float) $booking->payments()->where('status', 'success')->sum('amount');
            $service->toBookingCustomer(
                $booking,
                'Đã cập nhật thanh toán - booking ' . $code,
                'Khách sạn đã cập nhật thanh toán của booking ' . $code . '. Tổng số tiền thanh toán thành công hiện được ghi nhận: ' . number_format($paid, 0, ',', '.') . 'đ.',
                'success',
                null,
                ['meta' => ['event' => 'booking_payment_updated_by_staff']]
            );
        }
    }

    private function syncRoomReservationStatus(Booking $booking): void
    {
        $roomIds = $booking->bookingRooms()
            ->pluck('room_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $this->roomReservationStatusService->syncRoomIds($roomIds);
    }

    private function detectAction(Booking $booking): string
    {
        if ($booking->wasChanged('payment_status')
            || $booking->wasChanged('deposit_amount')
            || $booking->wasChanged('overpayment_amount')) {
            return 'payment_updated';
        }

        if ($booking->wasChanged('status')) {
            return match ($booking->status) {
                'confirmed' => 'confirmed',
                'checked_in' => 'checked_in',
                'inspection_requested' => 'inspection_requested',
                'completed' => 'completed',
                'checked_out' => 'checked_out',
                'cancelled', 'canceled' => 'cancelled',
                default => 'status_updated',
            };
        }

        if ($booking->wasChanged('estimated_total')) {
            return 'total_updated';
        }

        if ($booking->wasChanged('check_out_at') || $booking->wasChanged('check_out_date')) {
            return 'extended';
        }

        if ($booking->wasChanged('note')) {
            return 'note_updated';
        }

        return 'updated';
    }
}
