<?php

namespace App\Support;

use App\Models\Booking;

class BookingBilling
{
    /**
     * Tính chi tiết các khoản tiền của một booking để hiển thị hoá đơn.
     *
     * Các dòng luôn khớp: base_after_discount + extra_charges = total.
     *
     * @return array<string, mixed>
     */
    public static function summary(Booking $booking): array
    {
        $subtotal = (float) ($booking->subtotal_amount ?? 0);
        $discount = (float) ($booking->discount_amount ?? 0);
        $total = (float) ($booking->estimated_total ?? 0);
        $paid = (float) ($booking->deposit_amount ?? 0);
        $lateFee = (float) ($booking->late_arrival_fee ?? 0);

        // Booking cũ có thể chưa lưu subtotal, suy ngược từ tổng + giảm giá.
        if ($subtotal <= 0) {
            $subtotal = $total + $discount;
        }

        $baseAfterDiscount = max(0, round($subtotal - $discount, 2));
        $extraCharges = max(0, round($total - $baseAfterDiscount, 2));
        $remaining = max(0, round($total - $paid, 2));

        $vatRate = (float) config('booking.vat_rate', 0);
        $vatAmount = 0.0;
        $netAmount = $total;

        // Giá đã bao gồm VAT: tách phần thuế ra khỏi tổng (không làm đổi tổng).
        if ($vatRate > 0) {
            $netAmount = round($total / (1 + $vatRate / 100), 2);
            $vatAmount = round($total - $netAmount, 2);
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'base_after_discount' => $baseAfterDiscount,
            'extra_charges' => $extraCharges,
            'late_fee' => $lateFee,
            'total' => $total,
            'paid' => $paid,
            'remaining' => $remaining,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'net_amount' => $netAmount,
        ];
    }
}
