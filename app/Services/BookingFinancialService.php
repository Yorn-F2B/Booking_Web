<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPayment;
use Illuminate\Support\Facades\DB;

class BookingFinancialService
{
    public function paidTotal(Booking $booking): float
    {
        return (float) $booking->payments()->where('status', 'success')->sum('amount');
    }

    public function currentTotal(Booking $booking): float
    {
        $booking->loadMissing(['bookingRooms', 'serviceItems', 'roomInspections.items']);

        $roomSurchargeTotal = (float) $booking->bookingRooms->sum('surcharge');
        if ($booking->booking_type === 'hourly' && $booking->check_in_at && $booking->check_out_at) {
            $oneNightRoomTotal = (float) $booking->bookingRooms->sum('price_at_booking');
            $durationMinutes = max(1, $booking->check_in_at->diffInMinutes($booking->check_out_at));
            $hourly = app(StayPricingPolicyService::class)->shortStay(
                $oneNightRoomTotal,
                1,
                $durationMinutes,
                $booking
            );
            $roomTotal = (float) $hourly['amount'] + $roomSurchargeTotal;
        } else {
            $nights = max(1, $booking->check_in_at->copy()->startOfDay()->diffInDays($booking->check_out_at->copy()->startOfDay()));
            $roomTotal = (float) $booking->bookingRooms->sum(fn ($item) => (float) $item->price_at_booking * $nights)
                + $roomSurchargeTotal;
        }

        if ($roomTotal <= 0) {
            $roomTotal = max(0, (float) $booking->subtotal_amount - (float) $booking->serviceItems->sum('total'));
        }
        $services = (float) $booking->serviceItems->where('billing_status', 'confirmed')->sum('total');
        $inspection = (float) $booking->roomInspections->flatMap->items->where('status', 'approved')->sum('total');
        $manualRoomSelectionFee = max(0, (float) ($booking->room_selection_fee ?? 0));

        return max(0, round(
            $roomTotal + $services + $inspection + $manualRoomSelectionFee - (float) $booking->discount_amount,
            0
        ));
    }


    /**
     * Phí chọn phòng thủ công phát sinh thêm khi khách ĐANG Ở và chủ động
     * yêu cầu lễ tân chọn chính xác phòng thay thế. Chế độ hệ thống tự chọn
     * và mọi đổi phòng do sự cố khách sạn không thu khoản này.
     */
    public function manualRoomChangeSelectionFee(Booking $booking, string $assignmentMode, int $roomCount = 1): float
    {
        if ($booking->status !== 'checked_in' || $assignmentMode !== 'manual') {
            return 0.0;
        }

        $unitFee = max(0, (float) app(HotelPolicyService::class)
            ->forBooking($booking, 'booking.manual_room_selection_fee', 50000));

        return round($unitFee * max(1, $roomCount), 0);
    }

    public function addManualRoomChangeSelectionFee(Booking $booking, string $assignmentMode, int $roomCount = 1): float
    {
        $fee = $this->manualRoomChangeSelectionFee($booking, $assignmentMode, $roomCount);
        if ($fee <= 0) {
            return 0.0;
        }

        $booking->forceFill([
            'room_selection_fee' => round(max(0, (float) ($booking->room_selection_fee ?? 0)) + $fee, 0),
        ])->save();

        return $fee;
    }

    public function requiredDeposit(Booking $booking): float
    {
        // required_deposit_amount là mức cọc hiện hành sau khi đổi lịch/hạng.
        // deposit_amount được giữ làm mức cọc đã chốt ban đầu để đối chiếu lịch sử.
        if (array_key_exists('required_deposit_amount', $booking->getAttributes())) {
            return round(max(0, (float) $booking->required_deposit_amount), 0);
        }

        if ((float) $booking->deposit_amount > 0) {
            return round((float) $booking->deposit_amount, 0);
        }

        return round(max(0, (float) $booking->estimated_total) * app(HotelPolicyService::class)->depositRate($booking), 0);
    }

    public function hasMinimumDeposit(Booking $booking): bool
    {
        return $this->paidTotal($booking) + 0.01 >= $this->requiredDeposit($booking);
    }

    public function refreshPaymentStatus(Booking $booking): void
    {
        $total = $this->currentTotal($booking);
        $paid = $this->paidTotal($booking);
        $booking->payment_status = $paid <= 0 ? 'unpaid' : ($paid + 0.01 >= $total ? 'paid' : 'partial');
        $booking->overpayment_amount = max(0, round($paid - $total, 0));
        $booking->save();
    }

    public function remainingTotal(Booking $booking): float
    {
        return max(0, round($this->currentTotal($booking) - $this->paidTotal($booking), 0));
    }

    public function overpaymentTotal(Booking $booking): float
    {
        return max(0, round($this->paidTotal($booking) - $this->currentTotal($booking), 0));
    }

    /**
     * Phân bổ tiền khách đã thanh toán theo booking hiện tại.
     *
     * Lịch sử giao dịch không bị sửa khi booking đổi ngày/hạng. Hệ thống chỉ
     * phân bổ lại số đã thu: ưu tiên đủ mức cọc hiện hành, phần vượt mức
     * cọc trở thành tiền trả trước và tiếp tục bù trừ dịch vụ/phụ thu sau đó.
     */
    public function paymentAllocation(Booking $booking, ?float $currentTotal = null): array
    {
        $total = round(max(0, $currentTotal ?? $this->currentTotal($booking)), 0);
        $paid = round(max(0, $this->paidTotal($booking)), 0);
        $requiredDeposit = round(max(0, min($this->requiredDeposit($booking), $total)), 0);

        $allocatedDeposit = min($paid, $requiredDeposit);
        $prepaidAmount = max(0, $paid - $allocatedDeposit);
        $depositShortfall = max(0, $requiredDeposit - $paid);
        $remaining = max(0, $total - $paid);
        $overpayment = max(0, $paid - $total);

        return [
            'total' => $total,
            'paid_total' => $paid,
            'required_deposit' => $requiredDeposit,
            'allocated_deposit' => $allocatedDeposit,
            'prepaid_amount' => $prepaidAmount,
            'deposit_shortfall' => min($depositShortfall, $remaining),
            'remaining' => $remaining,
            'overpayment' => $overpayment,
        ];
    }

    public function cancellationPolicy(Booking $booking): array
    {
        $paid = $this->paidTotal($booking);
        $nights = max(1, $booking->check_in_at->copy()->startOfDay()->diffInDays($booking->check_out_at->copy()->startOfDay()));

        return [
            'nights' => $nights,
            'days_before' => max(0, now('Asia/Ho_Chi_Minh')->startOfDay()->diffInDays($booking->check_in_at->copy()->startOfDay(), false)),
            'paid_amount' => round($paid, 0),
            'forfeit_amount' => round($paid, 0),
            'credit_amount' => 0,
            'label' => $paid > 0
                ? 'Hủy đơn: mất toàn bộ số tiền đã thanh toán, không hoàn lại và không bảo lưu.'
                : 'Hủy đơn chưa thanh toán: không phát sinh tiền hoàn hoặc bảo lưu.',
        ];
    }


}
