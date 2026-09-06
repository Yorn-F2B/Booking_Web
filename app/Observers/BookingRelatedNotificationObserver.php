<?php

namespace App\Observers;

use App\Models\BookingCancellationRequest;
use App\Models\BookingGuest;
use App\Models\BookingPayment;
use App\Models\BookingRoom;
use App\Models\BookingServiceItem;
use App\Services\OperationalNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BookingRelatedNotificationObserver implements ShouldHandleEventsAfterCommit
{
    public function created(object $model): void
    {
        $this->dispatch($model, 'created');
    }

    public function updated(object $model): void
    {
        $this->dispatch($model, 'updated');
    }

    public function deleted(object $model): void
    {
        $this->dispatch($model, 'deleted');
    }

    private function dispatch(object $model, string $phase): void
    {
        if ($model instanceof BookingPayment) {
            $this->payment($model, $phase);
            return;
        }

        if ($model instanceof BookingRoom) {
            $this->bookingRoom($model, $phase);
            return;
        }

        if ($model instanceof BookingCancellationRequest) {
            $this->cancellation($model, $phase);
            return;
        }

        if ($model instanceof BookingServiceItem) {
            $this->serviceItem($model, $phase);
            return;
        }

        if ($model instanceof BookingGuest) {
            $this->guestRepresentative($model, $phase);
        }
    }

    private function payment(BookingPayment $payment, string $phase): void
    {
        // Thanh toán online đã có EmailDeliveryService gửi mail + web notification riêng.
        // Observer này chỉ bù cho các giao dịch do nhân viên ghi nhận/chỉnh tại quầy.
        if (!$this->isStaffAction()) {
            return;
        }

        $payment->loadMissing('booking.customer');
        $booking = $payment->booking;
        if (!$booking) {
            return;
        }

        if ($phase === 'updated' && !$payment->wasChanged(['status', 'amount', 'payment_type', 'paid_at'])) {
            return;
        }

        $amount = number_format((float) $payment->amount, 0, ',', '.') . 'đ';
        $status = match ((string) $payment->status) {
            'success', 'paid', 'completed' => 'thành công',
            'failed' => 'thất bại',
            'refunded' => 'đã hoàn',
            'pending' => 'đang chờ xử lý',
            default => (string) $payment->status,
        };

        $title = 'Cập nhật thanh toán - booking ' . $booking->booking_code;
        $message = 'Khách sạn đã cập nhật giao dịch ' . $amount . ' của booking ' . $booking->booking_code
            . ' với trạng thái ' . $status . '.';

        $this->send($booking, $title, $message, in_array($payment->status, ['failed'], true) ? 'warning' : 'success', [
            'event' => 'booking_payment_' . $phase,
            'booking_payment_id' => $payment->id,
        ]);
    }

    private function bookingRoom(BookingRoom $bookingRoom, string $phase): void
    {
        if (!$this->isStaffAction()) {
            return;
        }

        $bookingRoom->loadMissing(['booking.customer', 'room.category']);
        $booking = $bookingRoom->booking;
        if (!$booking) {
            return;
        }

        $room = $bookingRoom->room;
        $roomText = $room
            ? 'phòng ' . $room->room_number . ($room->category?->name ? ' (' . $room->category->name . ')' : '')
            : 'một phòng trong booking';

        if ($phase === 'created') {
            $title = 'Đã xếp thêm phòng - booking ' . $booking->booking_code;
            $message = 'Khách sạn đã xếp ' . $roomText . ' cho booking ' . $booking->booking_code . '.';
            $type = 'success';
        } elseif ($phase === 'deleted') {
            $title = 'Đã thay đổi danh sách phòng - booking ' . $booking->booking_code;
            $message = 'Khách sạn đã bỏ ' . $roomText . ' khỏi booking ' . $booking->booking_code . '. Danh sách phòng còn lại đã được cập nhật.';
            $type = 'warning';
        } else {
            if (!$bookingRoom->wasChanged(['room_id', 'surcharge', 'surcharge_reason'])) {
                return;
            }
            $title = 'Đã cập nhật phòng - booking ' . $booking->booking_code;
            $message = 'Khách sạn đã cập nhật ' . $roomText . ' trong booking ' . $booking->booking_code . '.';
            $type = 'info';
        }

        $this->send($booking, $title, $message, $type, [
            'event' => 'booking_room_' . $phase,
            'booking_room_id' => $bookingRoom->id,
        ], $room?->id);
    }

    private function cancellation(BookingCancellationRequest $request, string $phase): void
    {
        $request->loadMissing('booking.customer');
        $booking = $request->booking;
        if (!$booking) {
            return;
        }

        if ($phase === 'created') {
            $title = 'Đã nhận yêu cầu hủy - booking ' . $booking->booking_code;
            $message = 'Khách sạn đã ghi nhận yêu cầu hủy booking ' . $booking->booking_code . '. Yêu cầu đang chờ xem xét.';
            $type = 'warning';
            $event = 'booking_cancellation_requested';
        } elseif ($phase === 'updated' && $request->wasChanged('status')) {
            [$title, $message, $type, $event] = match ((string) $request->status) {
                'approved' => [
                    'Yêu cầu hủy đã được duyệt - booking ' . $booking->booking_code,
                    'Khách sạn đã chấp thuận yêu cầu hủy booking ' . $booking->booking_code . '.' . ($request->review_note ? ' Ghi chú: ' . trim((string) $request->review_note) : ''),
                    'warning', 'booking_cancellation_approved',
                ],
                'rejected' => [
                    'Yêu cầu hủy chưa được chấp thuận - booking ' . $booking->booking_code,
                    'Khách sạn chưa chấp thuận yêu cầu hủy booking ' . $booking->booking_code . '.' . ($request->review_note ? ' Lý do: ' . trim((string) $request->review_note) : ''),
                    'warning', 'booking_cancellation_rejected',
                ],
                default => [null, null, null, null],
            };
            if (!$title) {
                return;
            }
        } else {
            return;
        }

        $this->send($booking, $title, $message, $type, [
            'event' => $event,
            'booking_cancellation_request_id' => $request->id,
        ]);
    }

    private function serviceItem(BookingServiceItem $item, string $phase): void
    {
        if (!$this->isStaffAction()) {
            return;
        }

        $item->loadMissing('booking.customer');
        $booking = $item->booking;
        if (!$booking) {
            return;
        }

        if ($phase === 'updated' && !$item->wasChanged(['billing_status', 'quantity', 'used_quantity', 'unit_price', 'total', 'name'])) {
            return;
        }

        $name = trim((string) $item->name) ?: 'dịch vụ/phụ thu';
        $amount = number_format((float) $item->total, 0, ',', '.') . 'đ';

        $action = match ($phase) {
            'created' => 'đã thêm',
            'deleted' => 'đã bỏ',
            default => 'đã cập nhật',
        };

        $title = 'Cập nhật dịch vụ/phụ thu - booking ' . $booking->booking_code;
        $message = 'Khách sạn ' . $action . ' khoản “' . $name . '” của booking ' . $booking->booking_code
            . ($phase !== 'deleted' ? ' với thành tiền ' . $amount : '') . '.';

        $this->send($booking, $title, $message, $phase === 'deleted' ? 'info' : 'warning', [
            'event' => 'booking_service_item_' . $phase,
            'booking_service_item_id' => $item->id,
        ]);
    }

    private function guestRepresentative(BookingGuest $guest, string $phase): void
    {
        if (!$this->isStaffAction()) {
            return;
        }

        // Luồng lưu trú mới chỉ cần người đại diện phòng; chỉ thông báo các hồ sơ đại diện,
        // tránh spam vì dữ liệu kỹ thuật/legacy của khách khác.
        $isRepresentativeNow = (bool) $guest->is_booking_representative;
        $wasRepresentative = (bool) $guest->getOriginal('is_booking_representative');
        if (!$isRepresentativeNow && !$wasRepresentative) {
            return;
        }

        if ($phase === 'updated' && !$guest->wasChanged(['full_name', 'booking_room_id', 'is_booking_representative'])) {
            return;
        }

        $guest->loadMissing(['booking.customer', 'bookingRoom.room']);
        $booking = $guest->booking;
        if (!$booking) {
            return;
        }

        $room = $guest->bookingRoom?->room?->room_number;
        $title = 'Cập nhật người đại diện - booking ' . $booking->booking_code;
        $message = match ($phase) {
            'deleted' => 'Khách sạn đã bỏ thông tin người đại diện ' . $guest->full_name . ($room ? ' của phòng ' . $room : '') . ' trong booking ' . $booking->booking_code . '.',
            'created' => 'Khách sạn đã ghi nhận ' . $guest->full_name . ' là người đại diện' . ($room ? ' của phòng ' . $room : '') . ' trong booking ' . $booking->booking_code . '.',
            default => 'Khách sạn đã cập nhật người đại diện ' . $guest->full_name . ($room ? ' của phòng ' . $room : '') . ' trong booking ' . $booking->booking_code . '.',
        };

        $this->send($booking, $title, $message, 'info', [
            'event' => 'booking_representative_' . $phase,
            'booking_guest_id' => $guest->id,
        ]);
    }

    private function send($booking, string $title, string $message, string $type, array $meta, ?int $roomId = null): void
    {
        app(OperationalNotificationService::class)->toBookingCustomer(
            $booking,
            $title,
            $message,
            $type,
            null,
            [
                'room_id' => $roomId,
                'meta' => $meta,
            ]
        );
    }

    private function isStaffAction(): bool
    {
        $actor = auth()->user();
        return $actor && $actor->role !== 'customer';
    }
}
