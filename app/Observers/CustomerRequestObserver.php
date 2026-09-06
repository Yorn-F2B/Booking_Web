<?php

namespace App\Observers;

use App\Models\CustomerRequest;
use App\Services\OperationalNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CustomerRequestObserver implements ShouldHandleEventsAfterCommit
{
    public function created(CustomerRequest $request): void
    {
        if ($request->type === 'late_arrival') {
            $this->send($request, 'Đã gửi yêu cầu đến muộn', 'Khách sạn đã nhận yêu cầu đến muộn của booking %s. Yêu cầu đang chờ bộ phận phụ trách xem xét.', 'warning', 'late_arrival_created');
        }
    }

    public function updated(CustomerRequest $request): void
    {
        if ($request->type !== 'late_arrival' || !$request->wasChanged('status')) {
            return;
        }

        if ($request->status === 'approved') {
            $arrival = $request->expected_arrival_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y');
            $message = 'Yêu cầu đến muộn của booking %s đã được khách sạn chấp thuận.' . ($arrival ? ' Giờ dự kiến đến: ' . $arrival . '.' : '');
            $this->send($request, 'Yêu cầu đến muộn đã được chấp thuận', $message, 'success', 'late_arrival_approved');
        } elseif ($request->status === 'rejected') {
            $message = 'Yêu cầu đến muộn của booking %s chưa được khách sạn chấp thuận.' . ($request->admin_note ? ' Lý do: ' . trim((string) $request->admin_note) : '');
            $this->send($request, 'Yêu cầu đến muộn chưa được chấp thuận', $message, 'warning', 'late_arrival_rejected');
        }
    }

    private function send(CustomerRequest $request, string $title, string $messageTemplate, string $type, string $event): void
    {
        $request->loadMissing('booking.customer');
        $booking = $request->booking;
        if (!$booking) {
            return;
        }

        app(OperationalNotificationService::class)->toBookingCustomer(
            $booking,
            $title . ' - ' . $booking->booking_code,
            sprintf($messageTemplate, $booking->booking_code),
            $type,
            null,
            ['meta' => ['event' => $event, 'customer_request_id' => $request->id]]
        );
    }
}
