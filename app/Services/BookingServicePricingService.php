<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingServiceItem;
use App\Models\Service;
use Illuminate\Support\Collection;

class BookingServicePricingService
{
    public function calculateLine(
        string $billingRule,
        int $baseQuantity,
        float $unitPrice,
        int $nightCount,
        int $roomQuantity,
        int $guestCount
    ): array {
        $billingRule = Service::normalizeBillingRule($billingRule);
        $baseQuantity = max(1, $baseQuantity);
        $nightCount = max(1, $nightCount);
        $roomQuantity = max(1, $roomQuantity);
        $guestCount = max(1, $guestCount);

        $multiplier = match ($billingRule) {
            Service::BILLING_PER_NIGHT => $nightCount,
            Service::BILLING_PER_ROOM => $roomQuantity,
            Service::BILLING_PER_ROOM_PER_NIGHT => $roomQuantity * $nightCount,
            Service::BILLING_PER_GUEST => $guestCount,
            Service::BILLING_PER_GUEST_PER_NIGHT => $guestCount * $nightCount,
            default => 1,
        };

        $billedQuantity = max(1, $baseQuantity * $multiplier);
        $total = round(max(0, $unitPrice) * $billedQuantity, 0);

        return [
            'billing_rule' => $billingRule,
            'base_quantity' => $baseQuantity,
            'multiplier' => $multiplier,
            'billed_quantity' => $billedQuantity,
            'night_count' => $nightCount,
            'room_quantity' => $roomQuantity,
            'guest_count' => $guestCount,
            'total' => $total,
            'formula' => $this->formulaText(
                $billingRule,
                $baseQuantity,
                $unitPrice,
                $nightCount,
                $roomQuantity,
                $guestCount,
                $total
            ),
        ];
    }

    public function nightCountForNewService(Booking $booking): int
    {
        if ($booking->booking_type === 'hourly') {
            return 1;
        }

        $checkInAt = $booking->check_in_at?->copy()
            ?? \Carbon\Carbon::parse($booking->getRawOriginal('check_in_at'), 'Asia/Ho_Chi_Minh');
        $checkOutAt = $booking->check_out_at?->copy()
            ?? \Carbon\Carbon::parse($booking->getRawOriginal('check_out_at'), 'Asia/Ho_Chi_Minh');

        $checkInDay = $checkInAt->timezone('Asia/Ho_Chi_Minh')->startOfDay();
        $checkOutDay = $checkOutAt->timezone('Asia/Ho_Chi_Minh')->startOfDay();

        if (!$booking->actual_check_in && $booking->status !== 'checked_in') {
            return max(1, (int) $checkInDay->diffInDays($checkOutDay));
        }

        $today = now('Asia/Ho_Chi_Minh')->startOfDay();
        $effectiveStart = $today->greaterThan($checkInDay) ? $today : $checkInDay;

        return max(1, (int) $effectiveStart->diffInDays($checkOutDay, false));
    }

    public function previewBookingItems(
        Booking $booking,
        int $newNightCount,
        int $newRoomQuantity,
        int $newGuestCount,
        array $excludedItemIds = []
    ): array {
        $booking->loadMissing(['serviceItems.service', 'serviceItems.bookingRoom', 'bookingRooms', 'guests']);
        $excludedItemIds = array_map('intval', $excludedItemIds);
        $lines = [];
        $oldTotal = 0;
        $newTotal = 0;

        /** @var BookingServiceItem $item */
        foreach ($booking->serviceItems as $item) {
            $isConfirmed = $item->billing_status === 'confirmed';
            $oldItemTotal = $isConfirmed ? (float) $item->total : 0.0;

            if (in_array((int) $item->id, $excludedItemIds, true)) {
                $lines[] = [
                    'item_id' => (int) $item->id,
                    'service_id' => (int) $item->service_id,
                    'scope' => $item->scope ?? 'booking',
                    'booking_room_id' => $item->booking_room_id ? (int) $item->booking_room_id : null,
                    'name' => $item->name,
                    'type' => $item->type,
                    'unit_price' => (float) $item->unit_price,
                    'base_quantity' => $this->resolveBaseQuantity($item),
                    'billed_quantity' => 0,
                    'billing_status' => $item->billing_status,
                    'billing_rule' => $this->resolveRule($item),
                    'billing_rule_label' => Service::billingRuleLabels()[$this->resolveRule($item)] ?? 'Một lần',
                    'old_total' => $oldItemTotal,
                    'new_total' => 0,
                    'difference' => 0 - $oldItemTotal,
                    'old_formula' => $isConfirmed
                        ? $this->oldFormulaText($item)
                        : 'Khoản này chưa được xác nhận nên chưa tính tiền.',
                    'new_formula' => 'Gỡ khỏi booking cùng mã ưu đãi.',
                    'will_remove' => true,
                    'will_reprice' => false,
                ];
                $oldTotal += $oldItemTotal;
                continue;
            }

            // Pending/cancelled/unused vẫn tồn tại để theo dõi nghiệp vụ nhưng
            // không được chen vào subtotal khi booking được tính lại.
            if (!$isConfirmed) {
                $lines[] = [
                    'item_id' => (int) $item->id,
                    'service_id' => (int) $item->service_id,
                    'scope' => $item->scope ?? 'booking',
                    'booking_room_id' => $item->booking_room_id ? (int) $item->booking_room_id : null,
                    'room_id_snapshot' => $item->room_id_snapshot ? (int) $item->room_id_snapshot : null,
                    'name' => $item->name,
                    'type' => $item->type,
                    'unit_price' => (float) $item->unit_price,
                    'base_quantity' => $this->resolveBaseQuantity($item),
                    'billed_quantity' => 0,
                    'billing_status' => $item->billing_status,
                    'billing_rule' => $this->resolveRule($item),
                    'billing_rule_label' => Service::billingRuleLabels()[$this->resolveRule($item)] ?? 'Một lần',
                    'old_total' => 0,
                    'new_total' => 0,
                    'difference' => 0,
                    'old_formula' => 'Chưa xác nhận/đã hủy nên không tính tiền.',
                    'new_formula' => 'Chưa xác nhận/đã hủy nên không tính tiền.',
                    'will_remove' => false,
                    'will_reprice' => false,
                ];
                continue;
            }

            $oldTotal += $oldItemTotal;

            [$itemRoomQuantity, $itemGuestCount] = $this->dimensionsForItem(
                $booking,
                $item,
                $newRoomQuantity,
                $newGuestCount
            );
            $itemNightCount = $this->nightCountForItem($booking, $item, $newNightCount);

            if (!$this->canAutoReprice($item)) {
                $newItemTotal = $oldItemTotal;
                $newLine = null;
            } else {
                $newLine = $this->calculateLine(
                    $this->resolveRule($item),
                    $this->resolveBaseQuantity($item),
                    (float) $item->unit_price,
                    $itemNightCount,
                    $itemRoomQuantity,
                    $itemGuestCount
                );
                $newItemTotal = (float) $newLine['total'];
            }

            $newTotal += $newItemTotal;
            $lines[] = [
                'item_id' => (int) $item->id,
                'service_id' => (int) $item->service_id,
                'scope' => $item->scope ?? 'booking',
                'booking_room_id' => $item->booking_room_id ? (int) $item->booking_room_id : null,
                'room_id_snapshot' => $item->room_id_snapshot ? (int) $item->room_id_snapshot : null,
                'name' => $item->name,
                'type' => $item->type,
                'unit_price' => (float) $item->unit_price,
                'base_quantity' => $this->resolveBaseQuantity($item),
                'billed_quantity' => $newLine['billed_quantity'] ?? (int) $item->used_quantity,
                'billing_status' => $item->billing_status,
                'billing_rule' => $this->resolveRule($item),
                'billing_rule_label' => Service::billingRuleLabels()[$this->resolveRule($item)] ?? 'Một lần',
                'old_total' => $oldItemTotal,
                'new_total' => $newItemTotal,
                'difference' => $newItemTotal - $oldItemTotal,
                'old_formula' => $this->oldFormulaText($item),
                'new_formula' => $newLine['formula'] ?? 'Giữ nguyên vì đây là phụ thu/chi phí đã ghi nhận một lần.',
                'will_remove' => false,
                'will_reprice' => $newLine !== null && abs($newItemTotal - $oldItemTotal) > 0.01,
            ];
        }

        return [
            'lines' => $lines,
            'old_total' => round($oldTotal, 0),
            'new_total' => round($newTotal, 0),
            'difference' => round($newTotal - $oldTotal, 0),
        ];
    }

    public function persistBookingItems(
        Booking $booking,
        int $newNightCount,
        int $newRoomQuantity,
        int $newGuestCount,
        array $excludedItemIds = []
    ): array {
        $preview = $this->previewBookingItems(
            $booking,
            $newNightCount,
            $newRoomQuantity,
            $newGuestCount,
            $excludedItemIds
        );

        $excludedLookup = array_fill_keys(array_map('intval', $excludedItemIds), true);

        foreach ($booking->serviceItems as $item) {
            if (isset($excludedLookup[(int) $item->id])) {
                $item->delete();
                continue;
            }

            if (!$this->canAutoReprice($item)) {
                continue;
            }

            [$itemRoomQuantity, $itemGuestCount] = $this->dimensionsForItem(
                $booking,
                $item,
                $newRoomQuantity,
                $newGuestCount
            );

            $line = $this->calculateLine(
                $this->resolveRule($item),
                $this->resolveBaseQuantity($item),
                (float) $item->unit_price,
                $this->nightCountForItem($booking, $item, $newNightCount),
                $itemRoomQuantity,
                $itemGuestCount
            );

            $item->forceFill([
                'billing_rule_snapshot' => $line['billing_rule'],
                'base_quantity' => $line['base_quantity'],
                'quantity' => $line['base_quantity'],
                'used_quantity' => $line['billed_quantity'],
                'nights_snapshot' => $line['night_count'],
                'rooms_snapshot' => $line['room_quantity'],
                'people_snapshot' => $line['guest_count'],
                'total' => $line['total'],
            ])->save();
        }

        return $preview;
    }

    public function snapshotForService(
        Service $service,
        int $baseQuantity,
        float $unitPrice,
        int $nightCount,
        int $roomQuantity,
        int $guestCount
    ): array {
        $line = $this->calculateLine(
            $service->billing_rule ?: Service::BILLING_ONCE,
            $baseQuantity,
            $unitPrice,
            $nightCount,
            $roomQuantity,
            $guestCount
        );

        return [
            'billing_rule_snapshot' => $line['billing_rule'],
            'base_quantity' => $line['base_quantity'],
            'quantity' => $line['base_quantity'],
            'used_quantity' => $line['billed_quantity'],
            'nights_snapshot' => $line['night_count'],
            'rooms_snapshot' => $line['room_quantity'],
            'people_snapshot' => $line['guest_count'],
            'total' => $line['total'],
            'formula' => $line['formula'],
        ];
    }

    private function dimensionsForItem(Booking $booking, BookingServiceItem $item, int $bookingRoomQuantity, int $bookingGuestCount): array
    {
        $snapshotLocked = $this->snapshotLocked($booking);

        if (($item->scope ?? 'booking') !== 'room' || !$item->booking_room_id) {
            $roomQuantity = max(1, $bookingRoomQuantity);
            $guestCount = max(1, $bookingGuestCount);

            if ($snapshotLocked) {
                if ((int) ($item->rooms_snapshot ?? 0) > 0) {
                    $roomQuantity = max(1, (int) $item->rooms_snapshot);
                }

                if ((int) ($item->people_snapshot ?? 0) > 0) {
                    $guestCount = max(1, (int) $item->people_snapshot);
                }
            }

            return [$roomQuantity, $guestCount];
        }

        if ($snapshotLocked && (int) ($item->people_snapshot ?? 0) > 0) {
            return [1, max(1, (int) $item->people_snapshot)];
        }

        $bookingRoom = $booking->bookingRooms->firstWhere('id', (int) $item->booking_room_id);
        $guestCount = $bookingRoom
            ? max(1, (int) $bookingRoom->adult_count + (int) $bookingRoom->child_count)
            : 1;

        return [1, $guestCount];
    }

    private function nightCountForItem(Booking $booking, BookingServiceItem $item, int $newNightCount): int
    {
        if ($this->snapshotLocked($booking) && (int) ($item->nights_snapshot ?? 0) > 0) {
            return max(1, (int) $item->nights_snapshot);
        }

        return max(1, $newNightCount);
    }

    private function snapshotLocked(Booking $booking): bool
    {
        return (bool) $booking->actual_check_in || $booking->status === 'checked_in';
    }

    private function canAutoReprice(BookingServiceItem $item): bool
    {
        return $item->billing_status === 'confirmed'
            && in_array($item->type, [Service::TYPE_SERVICE, Service::TYPE_MINIBAR_ORDER], true);
    }

    private function resolveRule(BookingServiceItem $item): string
    {
        $snapshot = trim((string) ($item->billing_rule_snapshot ?? ''));

        if ($snapshot !== '') {
            return Service::normalizeBillingRule($snapshot);
        }

        if ($item->service) {
            return Service::normalizeBillingRule((string) $item->service->billing_rule);
        }

        return Service::BILLING_ONCE;
    }

    private function resolveBaseQuantity(BookingServiceItem $item): int
    {
        if ((int) ($item->base_quantity ?? 0) > 0) {
            return (int) $item->base_quantity;
        }

        return max(1, (int) $item->quantity);
    }

    private function oldFormulaText(BookingServiceItem $item): string
    {
        $baseQuantity = $this->resolveBaseQuantity($item);
        $unitPrice = (float) $item->unit_price;
        $total = (float) $item->total;
        $rule = $this->resolveRule($item);
        $nightCount = max(1, (int) ($item->nights_snapshot ?? 1));
        $roomQuantity = max(1, (int) ($item->rooms_snapshot ?? 1));
        $guestCount = max(1, (int) ($item->people_snapshot ?? 1));

        return $this->formulaText(
            $rule,
            $baseQuantity,
            $unitPrice,
            $nightCount,
            $roomQuantity,
            $guestCount,
            $total
        );
    }

    private function formulaText(
        string $rule,
        int $baseQuantity,
        float $unitPrice,
        int $nightCount,
        int $roomQuantity,
        int $guestCount,
        float $total
    ): string {
        $parts = [number_format($unitPrice, 0, ',', '.') . 'đ', '× ' . $baseQuantity];

        if (in_array($rule, [Service::BILLING_PER_NIGHT, Service::BILLING_PER_ROOM_PER_NIGHT, Service::BILLING_PER_GUEST_PER_NIGHT], true)) {
            $parts[] = '× ' . $nightCount . ' đêm';
        }

        if (in_array($rule, [Service::BILLING_PER_ROOM, Service::BILLING_PER_ROOM_PER_NIGHT], true)) {
            $parts[] = '× ' . $roomQuantity . ' phòng';
        }

        if (in_array($rule, [Service::BILLING_PER_GUEST, Service::BILLING_PER_GUEST_PER_NIGHT], true)) {
            $parts[] = '× ' . $guestCount . ' khách';
        }

        return implode(' ', $parts) . ' = ' . number_format($total, 0, ',', '.') . 'đ';
    }
}
