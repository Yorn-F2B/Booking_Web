<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingServiceItem;

class BookingOccupancyFeeService
{
    /**
     * Đồng bộ phụ thu vượt sức chứa với số khách thực tế đang lưu trên booking.
     * Phụ thu check-in được tính theo TỔNG sức chứa của toàn booking, không yêu
     * cầu lễ tân phân khách thủ công cho từng phòng.
     */
    public function reconcile(Booking $booking): array
    {
        $booking->loadMissing(['bookingRooms.room.category', 'serviceItems']);

        $adultCapacity = (int) $booking->bookingRooms->sum(
            fn ($bookingRoom) => max(0, (int) ($bookingRoom->room?->category?->adult_capacity ?? 0))
        );
        $minorCapacity = (int) $booking->bookingRooms->sum(
            fn ($bookingRoom) => max(0, (int) ($bookingRoom->room?->category?->child_capacity ?? 0))
        );

        $required = [
            'adult' => max(0, (int) $booking->adult_count - $adultCapacity),
            'minor' => max(0, (int) $booking->child_count - $minorCapacity),
        ];

        $removedTotal = 0.0;
        $updatedTotalDifference = 0.0;
        $messages = [];

        $items = BookingServiceItem::query()
            ->where('booking_id', $booking->id)
            ->where('source_type', 'checkin_capacity_fee')
            ->where('type', 'occupancy_fee')
            ->orderBy('id')
            ->get()
            ->groupBy(function (BookingServiceItem $item) {
                return str_contains((string) $item->note, '[capacity_type:minor]') ? 'minor' : 'adult';
            });

        foreach (['adult', 'minor'] as $guestType) {
            $groupItems = $items->get($guestType, collect());
            $remaining = (int) $required[$guestType];

            foreach ($groupItems as $item) {
                $oldTotal = (float) $item->total;

                if ($remaining <= 0) {
                    $removedTotal += $oldTotal;
                    $item->delete();
                    continue;
                }

                $newQuantity = min($remaining, max(1, (int) $item->quantity));
                $newTotal = round((float) $item->unit_price * $newQuantity, 2);
                $remaining -= $newQuantity;

                if ($newQuantity !== (int) $item->quantity || abs($newTotal - $oldTotal) > 0.01) {
                    $updatedTotalDifference += $newTotal - $oldTotal;
                    $item->update([
                        'base_quantity' => $newQuantity,
                        'people_snapshot' => $newQuantity,
                        'quantity' => $newQuantity,
                        'total' => $newTotal,
                    ]);
                }
            }

            $label = $guestType === 'adult' ? 'người lớn' : 'trẻ em';
            if ((int) $required[$guestType] === 0 && $groupItems->isNotEmpty()) {
                $messages[] = 'Đã gỡ phụ thu ' . $label . ' vì tổng booking không còn vượt sức chứa.';
            } elseif ($remaining > 0) {
                $messages[] = 'Booking vẫn vượt thêm ' . $remaining . ' ' . $label
                    . ' chưa có phụ thu; lễ tân cần xác nhận khoản mới.';
            }
        }

        return [
            'removed_total' => round($removedTotal, 2),
            'difference' => round($updatedTotalDifference - $removedTotal, 2),
            'messages' => $messages,
        ];
    }
}
