<?php

namespace App\Services;

use App\Models\BookingPayment;
use Illuminate\Support\Facades\Auth;

class PendingPaymentRequestService
{
    /**
     * Vô hiệu ngay các yêu cầu/link VNPay còn chờ của booking.
     *
     * @return int Số giao dịch đã bị đóng.
     */
    public function expire(
        int $bookingId,
        string $reason,
        ?string $paymentType = null,
        ?int $exceptPaymentId = null
    ): int {
        $query = BookingPayment::query()
            ->where('booking_id', $bookingId)
            ->whereIn('provider', ['vnpay', 'admin_vnpay'])
            ->where('status', 'pending')
            ->lockForUpdate();

        if ($paymentType !== null) {
            $query->where('payment_type', $paymentType);
        }

        if ($exceptPaymentId !== null) {
            $query->where('id', '<>', $exceptPaymentId);
        }

        $payments = $query->get();
        $now = now('Asia/Ho_Chi_Minh')->toDateTimeString();

        foreach ($payments as $payment) {
            $raw = $payment->raw_response ?? [];
            $raw['closed_reason'] = $reason;
            $raw['closed_at'] = $now;
            $raw['closed_by'] = Auth::id();

            $payment->forceFill([
                'status' => 'failed',
                'response_code' => 'REPLACED',
                'transaction_status' => 'REPLACED',
                'raw_response' => $raw,
            ])->save();
        }

        return $payments->count();
    }
}
