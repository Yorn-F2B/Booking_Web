<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingServiceItem;

class BookingOccupancyFeeService
{
    /**
     * Đồng bộ phụ thu vượt sức chứa đã tạo lúc check-in với phân bổ khách hiện tại.
     * - Phòng hết vượt: gỡ phụ thu của loại khách tương ứng.
     * - Phòng vẫn vượt nhưng số lượng giảm/tăng: cập nhật quantity và total.
     * - Không tự tạo khoản mới vì lễ tân phải chọn loại phụ thu và xác nhận giá.
     */
    public function reconcile(Booking $booking): array
    {
        $booking->loadMissing(['bookingRooms.room.category', 'guests', 'serviceItems']);

        $capacityMap = [];
        foreach ($booking->bookingRooms as $bookingRoom) {
            $roomGuests = $booking->guests->where('booking_room_id', $bookingRoom->id);
            $adultCount = $roomGuests->where('guest_type', 'adult')->count();
            $minorCount = $roomGuests->whereIn('guest_type', ['child', 'infant'])->count();

            $capacityMap[(int) $bookingRoom->id] = [
                'adult' => max(0, $adultCount - (int) ($bookingRoom->room?->category?->adult_capacity ?? 0)),
                'minor' => max(0, $minorCount - (int) ($bookingRoom->room?->category?->child_capacity ?? 0)),
            ];
        }

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
                $type = str_contains((string) $item->note, '[capacity_type:minor]') ? 'minor' : 'adult';
                return (int) $item->booking_room_id . '|' . $type;
            });

        foreach ($items as $groupKey => $groupItems) {
            [$bookingRoomId, $guestType] = explode('|', $groupKey, 2);
            $bookingRoomId = (int) $bookingRoomId;
            $requiredQuantity = (int) ($capacityMap[$bookingRoomId][$guestType] ?? 0);
            $remaining = $requiredQuantity;

            foreach ($groupItems as $item) {
                $oldTotal = (float) $item->total;

                if ($remaining <= 0 || !isset($capacityMap[$bookingRoomId])) {
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

            if ($requiredQuantity === 0) {
                $label = $guestType === 'adult' ? 'người lớn' : 'trẻ em/em bé';
                $messages[] = 'Đã gỡ phụ thu ' . $label . ' vì phòng không còn vượt sức chứa.';
            } elseif ($remaining > 0) {
                $label = $guestType === 'adult' ? 'người lớn' : 'trẻ em/em bé';
                $messages[] = 'Phòng vẫn vượt thêm ' . $remaining . ' ' . $label
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
