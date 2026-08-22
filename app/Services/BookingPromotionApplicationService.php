<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPromotionRoomUpgrade;
use App\Models\BookingRoom;
use App\Models\BookingRoomChange;
use App\Models\RoomIssueRequest;
use App\Models\BookingServiceItem;
use App\Models\Promotion;
use App\Models\PromotionRoomUpgradeOffer;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingPromotionApplicationService
{
    public function apply(
        Booking $booking,
        array $codes,
        ?string $note = null,
        ?int $appliedBy = null,
        bool $ignoreCombinationRules = false,
        ?BookingRoom $scopeRoom = null,
        ?RoomIssueRequest $roomIssue = null
    ): array {
        $codes = collect($codes)
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return $this->emptyResult();
        }

        $booking->loadMissing([
            'bookingPromotions',
            'serviceItems',
            'bookingRooms.room.category',
            'guests',
            'roomChanges.oldCategory',
            'roomChanges.newCategory',
        ]);

        $scopePromotions = $booking->bookingPromotions->filter(function ($usage) use ($scopeRoom) {
            if ($scopeRoom) {
                return ($usage->scope ?? 'booking') === 'room'
                    && (int) $usage->booking_room_id === (int) $scopeRoom->id;
            }

            return ($usage->scope ?? 'booking') === 'booking' && empty($usage->booking_room_id);
        });

        $existingCodes = $scopePromotions
            ->pluck('code_snapshot')
            ->map(fn ($code) => strtoupper(trim((string) $code)));

        $duplicates = $codes->intersect($existingCodes);
        if ($duplicates->isNotEmpty()) {
            throw new \RuntimeException('Booking đã áp dụng mã: ' . $duplicates->implode(', ') . '.');
        }

        $promotions = Promotion::with([
            'serviceOffers.service',
            'roomUpgradeOffers.fromCategory',
            'roomUpgradeOffers.toCategory',
        ])
            ->whereIn('code', $codes)
            ->where('status', 'active')
            ->where('admin_can_apply', true)
            ->get()
            ->keyBy('code');

        $missing = $codes->reject(fn ($code) => $promotions->has($code));
        if ($missing->isNotEmpty()) {
            throw new \RuntimeException('Mã không tồn tại, đã tắt hoặc không cho admin áp dụng: ' . $missing->implode(', ') . '.');
        }

        $existingPromotionModels = Promotion::query()
            ->whereIn('code', $existingCodes)
            ->get()
            ->keyBy('code');

        if (!$ignoreCombinationRules && $existingPromotionModels->where('is_stackable', false)->isNotEmpty()) {
            throw new \RuntimeException('Booking đã có một mã chỉ được dùng một mình nên không thể áp thêm mã khác.');
        }

        if (!$ignoreCombinationRules && $promotions->where('is_stackable', false)->isNotEmpty() && ($existingCodes->isNotEmpty() || $codes->count() > 1)) {
            $soloCode = $promotions->where('is_stackable', false)->first()?->code;
            throw new \RuntimeException('Mã ' . $soloCode . ' chỉ được dùng một mình.');
        }

        // Dùng chung đúng một nguồn giới hạn với PromotionService/UI. Tránh trường hợp
        // màn sửa booking cho chọn khác với màn tạo hoặc với lúc backend áp mã.
        foreach (app(PromotionService::class)->selectionRules() as $type => $rule) {
            $limit = $rule['limit'] ?? null;
            if ($ignoreCombinationRules || $limit === null) {
                continue;
            }

            $existingCount = $scopePromotions
                ->where('promotion_type_snapshot', $type)
                ->count();
            $newCount = $promotions->where('promotion_type', $type)->count();

            if ($existingCount + $newCount > $limit) {
                $label = mb_strtolower((string) ($rule['label'] ?? 'mã cùng loại'));
                throw new \RuntimeException('Mỗi booking chỉ được có tối đa ' . $limit . ' ' . $label . '.');
            }
        }

        $upgradeApplications = collect();
        $normalCodes = collect();
        $reservedChangeIds = [];

        foreach ($codes as $code) {
            $promotion = $promotions->get($code);
            $match = $this->findEligibleUpgradeChange($booking, $promotion, $reservedChangeIds, $scopeRoom);

            if ($match) {
                $upgradeApplications->push([
                    'promotion' => $promotion,
                    'change' => $match['change'],
                    'offer' => $match['offer'],
                ]);
                $reservedChangeIds[] = $match['change']->id;
            } else {
                $isUpgradeOnly = $promotion->roomUpgradeOffers->isNotEmpty()
                    && (float) $promotion->discount_value <= 0
                    && $promotion->serviceOffers->isEmpty();

                if ($isUpgradeOnly) {
                    throw new \RuntimeException(
                        'Mã ' . $promotion->code
                        . ' chỉ dùng cho một lần nâng hạng chưa được áp mã. Hãy đổi hạng trước hoặc chỉ chọn một mã nâng hạng cho mỗi lần đổi.'
                    );
                }

                $normalCodes->push($code);
            }
        }

        $summary = $this->emptyResult();

        if ($normalCodes->isNotEmpty()) {
            $normal = $this->applyNormalPromotions(
                $booking,
                $normalCodes,
                $note,
                $appliedBy,
                $ignoreCombinationRules,
                $scopeRoom,
                $roomIssue
            );
            foreach ($normal as $key => $value) {
                if (is_numeric($value) && array_key_exists($key, $summary)) {
                    $summary[$key] += $value;
                }
            }
            $summary['codes'] = array_merge($summary['codes'], $normal['codes']);
        }

        foreach ($upgradeApplications as $application) {
            $upgrade = $this->applyUpgradePromotion(
                $booking,
                $application['promotion'],
                $application['change'],
                $application['offer'],
                $note,
                $appliedBy,
                $scopeRoom,
                $roomIssue
            );

            foreach (['discount_total', 'money_discount_total', 'service_discount_total', 'room_upgrade_discount_total'] as $key) {
                $summary[$key] += $upgrade[$key] ?? 0;
            }
            $summary['codes'][] = $application['promotion']->code;
        }

        $summary['codes'] = array_values(array_unique($summary['codes']));

        return $summary;
    }

    private function applyNormalPromotions(
        Booking $booking,
        Collection $codes,
        ?string $note,
        ?int $appliedBy,
        bool $ignoreCombinationRules = false,
        ?BookingRoom $scopeRoom = null,
        ?RoomIssueRequest $roomIssue = null
    ): array {
        $booking->refresh()->loadMissing(['serviceItems', 'bookingPromotions', 'bookingRooms.room.category', 'guests']);

        $nightCount = max(
            1,
            Carbon::parse($booking->check_in_date, 'Asia/Ho_Chi_Minh')
                ->diffInDays(Carbon::parse($booking->check_out_date, 'Asia/Ho_Chi_Minh'))
        );

        $currentDiscount = (float) ($booking->discount_amount ?? 0);
        $bookingSubtotal = (float) ($booking->subtotal_amount ?? 0);
        if ($bookingSubtotal <= 0) {
            $bookingSubtotal = (float) $booking->estimated_total + $currentDiscount;
        }

        // Chỉ dịch vụ đã xác nhận mới là khoản tài chính và được phép
        // tham gia điều kiện/tính ưu đãi. Pending không được coi như đã mua.
        $allConfirmedServices = $booking->serviceItems
            ->where('billing_status', 'confirmed');
        $serviceCollection = $scopeRoom
            ? $allConfirmedServices->where('booking_room_id', $scopeRoom->id)
            : $allConfirmedServices;

        $roomBaseTotal = 0;
        if ($scopeRoom) {
            $roomBaseTotal = (float) $scopeRoom->price_at_booking * $nightCount + (float) $scopeRoom->surcharge;
        }
        $subtotalAmount = $scopeRoom
            ? $roomBaseTotal + (float) $serviceCollection->sum('total')
            : $bookingSubtotal;

        $serviceItems = $serviceCollection
            ->map(fn ($item) => [
                'service_id' => $item->service_id,
                'scope' => $item->scope ?? 'booking',
                'booking_room_id' => $item->booking_room_id,
                'name' => $item->name,
                'type' => $item->type,
                'billing_rule_snapshot' => $item->billing_rule_snapshot,
                'unit_price' => (float) $item->unit_price,
                'base_quantity' => (int) ($item->base_quantity ?: $item->quantity),
                'quantity' => (int) $item->quantity,
                'used_quantity' => (int) $item->used_quantity,
                'nights_snapshot' => (int) ($item->nights_snapshot ?: 1),
                'rooms_snapshot' => (int) ($item->rooms_snapshot ?: 1),
                'people_snapshot' => (int) ($item->people_snapshot ?: 1),
                'billing_status' => $item->billing_status,
                'total' => (float) $item->total,
                'note' => $item->note,
            ])->values()->all();

        $result = app(PromotionService::class)->validateCodes(
            $codes->all(),
            [
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'customer_email' => $booking->booked_customer_email,
                'customer_phone' => $booking->booked_customer_phone,
                'customer_cccd' => $booking->booked_customer_cccd,
                'subtotal_amount' => $subtotalAmount,
                'eligibility_subtotal_amount' => $bookingSubtotal,
                'service_items' => $serviceItems,
                'check_in_at' => $booking->check_in_at,
                'check_out_at' => $booking->check_out_at,
                'night_count' => $nightCount,
                'room_quantity' => $scopeRoom ? 1 : $booking->room_quantity,
                'eligibility_room_quantity' => $booking->room_quantity,
                'guest_count' => $scopeRoom
                    ? max(1, $booking->guests->where('booking_room_id', $scopeRoom->id)->count())
                    : max(1, (int) $booking->adult_count + (int) $booking->child_count + (int) ($booking->baby_count ?? 0)),
                'scope' => $scopeRoom ? 'room' : 'booking',
                'booking_room_id' => $scopeRoom?->id,
                'room_id_snapshot' => $scopeRoom?->room_id,
                'source_type' => $scopeRoom ? 'room_issue_promotion' : 'promotion',
                'source_id' => $roomIssue?->id,
            ],
            'admin',
            $note,
            $ignoreCombinationRules
        );

        if (!$result['ok']) {
            throw new \RuntimeException(implode(' ', $result['messages']));
        }

        foreach (($result['auto_service_items'] ?? []) as $item) {
            $this->upsertServiceItem($booking, $item, $scopeRoom, $roomIssue);
        }

        $addedDiscount = (float) $result['discount_total'];
        $newDiscount = $currentDiscount + $addedDiscount;
        $autoServiceAdded = max(0, (float) $result['subtotal_amount'] - $subtotalAmount);
        $newBookingSubtotal = $scopeRoom
            ? $bookingSubtotal + $autoServiceAdded
            : (float) $result['subtotal_amount'];
        $newTotal = max(0, $newBookingSubtotal - $newDiscount);

        $booking->update([
            'subtotal_amount' => $newBookingSubtotal,
            'discount_amount' => $newDiscount,
            'estimated_total' => $newTotal,
        ]);
        app(BookingFinancialService::class)->refreshPaymentStatus($booking);

        app(PromotionService::class)->storeUsages(
            $booking,
            $result['promotions'],
            'admin',
            $note,
            $appliedBy,
            [
                'scope' => $scopeRoom ? 'room' : 'booking',
                'booking_room_id' => $scopeRoom?->id,
                'room_issue_request_id' => $roomIssue?->id,
                'room_id_snapshot' => $scopeRoom?->room_id,
            ]
        );

        return [
            'codes' => $codes->all(),
            'discount_total' => $addedDiscount,
            'money_discount_total' => (float) ($result['money_discount_total'] ?? 0),
            'service_discount_total' => (float) ($result['service_discount_total'] ?? 0),
            'room_upgrade_discount_total' => 0,
        ];
    }

    private function applyUpgradePromotion(
        Booking $booking,
        Promotion $promotion,
        BookingRoomChange $change,
        PromotionRoomUpgradeOffer $offer,
        ?string $note,
        ?int $appliedBy,
        ?BookingRoom $scopeRoom = null,
        ?RoomIssueRequest $roomIssue = null
    ): array {
        $result = app(PromotionService::class)->findRoomUpgradeOffer(
            $promotion->code,
            (int) $change->old_room_category_id,
            (int) $change->new_room_category_id,
            $offer->upgrade_kind,
            [
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'customer_email' => $booking->booked_customer_email,
                'customer_phone' => $booking->booked_customer_phone,
                'customer_cccd' => $booking->booked_customer_cccd,
                'subtotal_amount' => (float) ($booking->subtotal_amount ?: ($booking->estimated_total + $booking->discount_amount)),
                'check_in_at' => $booking->check_in_at,
                'check_out_at' => $booking->check_out_at,
                'night_count' => $change->night_count,
                'room_quantity' => $booking->room_quantity,
            ],
            'admin',
            $note
        );

        if (!$result['ok']) {
            throw new \RuntimeException($result['message']);
        }

        $difference = max(0, (float) $change->price_difference_total);
        $covered = app(PromotionService::class)->calculateRoomUpgradeCoverAmount($result['offer'], $difference);
        $guestExtra = max(0, $difference - $covered);

        // Giá phòng mới luôn được ghi thật vào booking. Mã nâng hạng chỉ bù
        // phần chênh mà mã được phép bao phủ; phần còn lại khách phải trả.
        // Quy tắc này áp dụng giống nhau cho đổi thường và đổi do sự cố.
        $booking->refresh();
        $newDiscount = (float) $booking->discount_amount + $covered;
        $newTotal = max(0, (float) $booking->estimated_total - $covered);

        $booking->update([
            'discount_amount' => $newDiscount,
            'estimated_total' => $newTotal,
        ]);

        app(PromotionService::class)->storeRoomUpgradeUsage(
            $booking,
            $promotion,
            $result['offer'],
            [
                'booking_room_id' => $change->booking_room_id,
                'old_room_id' => $change->old_room_id,
                'new_room_id' => $change->new_room_id,
                'old_room_category_id' => $change->old_room_category_id,
                'old_room_category_name_snapshot' => $change->oldCategory?->name ?? 'Hạng cũ',
                'old_room_price_snapshot' => (float) $change->old_room_price,
                'new_room_category_id' => $change->new_room_category_id,
                'new_room_category_name_snapshot' => $change->newCategory?->name ?? 'Hạng mới',
                'new_room_price_snapshot' => (float) $change->new_room_price,
                'night_count' => $change->night_count,
                'room_quantity' => 1,
                'original_difference_amount' => $difference,
                'covered_amount' => $covered,
                'guest_extra_amount' => $guestExtra,
                'note' => $change->reason,
            ],
            'admin',
            $note,
            $appliedBy,
            [
                'scope' => $scopeRoom ? 'room' : 'booking',
                'booking_room_id' => $scopeRoom?->id ?? $change->booking_room_id,
                'room_issue_request_id' => $roomIssue?->id ?? $change->room_issue_request_id,
                'room_id_snapshot' => $change->new_room_id,
            ]
        );

        return [
            'discount_total' => $covered,
            'money_discount_total' => 0,
            'service_discount_total' => 0,
            'room_upgrade_discount_total' => $covered,
        ];
    }

    private function findEligibleUpgradeChange(Booking $booking, Promotion $promotion, array $reservedChangeIds, ?BookingRoom $scopeRoom = null): ?array
    {
        if ($promotion->roomUpgradeOffers->isEmpty()) {
            return null;
        }

        $changes = $booking->roomChanges
            ->where('price_difference_total', '>', 0)
            ->when($scopeRoom, fn ($items) => $items->where('booking_room_id', $scopeRoom->id))
            ->sortByDesc('id');

        foreach ($changes as $change) {
            if (in_array($change->id, $reservedChangeIds, true)) {
                continue;
            }

            $alreadyCovered = BookingPromotionRoomUpgrade::where('booking_id', $booking->id)
                ->where('booking_room_id', $change->booking_room_id)
                ->where('old_room_category_id', $change->old_room_category_id)
                ->where('new_room_category_id', $change->new_room_category_id)
                ->exists();

            if ($alreadyCovered) {
                continue;
            }

            $offer = $promotion->roomUpgradeOffers->first(function (PromotionRoomUpgradeOffer $offer) use ($change) {
                $fromMatches = !$offer->from_room_category_id
                    || (int) $offer->from_room_category_id === (int) $change->old_room_category_id;
                $toMatches = !$offer->to_room_category_id
                    || (int) $offer->to_room_category_id === (int) $change->new_room_category_id;

                return $fromMatches && $toMatches;
            });

            if ($offer) {
                return ['change' => $change, 'offer' => $offer];
            }
        }

        return null;
    }

    private function upsertServiceItem(Booking $booking, array $item, ?BookingRoom $scopeRoom = null, ?RoomIssueRequest $roomIssue = null): void
    {
        if (empty($item['service_id'])) {
            return;
        }

        $scope = $scopeRoom ? 'room' : ($item['scope'] ?? 'booking');
        $bookingRoomId = $scopeRoom?->id ?? ($item['booking_room_id'] ?? null);
        $existingQuery = BookingServiceItem::where('booking_id', $booking->id)
            ->where('service_id', $item['service_id'])
            ->where('scope', $scope)
            ->where('billing_status', 'confirmed');
        $bookingRoomId
            ? $existingQuery->where('booking_room_id', $bookingRoomId)
            : $existingQuery->whereNull('booking_room_id');
        $existing = $existingQuery->first();

        if ($existing) {
            $existing->base_quantity = max(1, (int) ($existing->base_quantity ?? $existing->quantity))
                + max(1, (int) ($item['base_quantity'] ?? $item['quantity']));
            $existing->quantity = $existing->base_quantity;
            $existing->used_quantity = (int) $existing->used_quantity + (int) $item['used_quantity'];
            $existing->total = (float) $existing->total + (float) $item['total'];
            $existing->billing_rule_snapshot = $item['billing_rule_snapshot'] ?? $existing->billing_rule_snapshot ?? \App\Models\Service::BILLING_ONCE;
            $existing->nights_snapshot = $item['nights_snapshot'] ?? $existing->nights_snapshot ?? 1;
            $existing->rooms_snapshot = $item['rooms_snapshot'] ?? $existing->rooms_snapshot ?? 1;
            $existing->people_snapshot = $item['people_snapshot'] ?? $existing->people_snapshot ?? 1;
            $existing->note = trim(implode('; ', array_filter([$existing->note, $item['note'] ?? null])));
            $existing->save();
            return;
        }

        BookingServiceItem::create([
            'booking_id' => $booking->id,
            'scope' => $scope,
            'booking_room_id' => $bookingRoomId,
            'room_id_snapshot' => $scopeRoom?->room_id ?? ($item['room_id_snapshot'] ?? null),
            'source_type' => $scopeRoom ? 'room_issue_promotion' : ($item['source_type'] ?? 'promotion'),
            'source_id' => $roomIssue?->id ?? ($item['source_id'] ?? null),
            'service_id' => $item['service_id'],
            'name' => $item['name'],
            'type' => $item['type'],
            'billing_rule_snapshot' => $item['billing_rule_snapshot'] ?? \App\Models\Service::BILLING_ONCE,
            'unit_price' => $item['unit_price'],
            'base_quantity' => $item['base_quantity'] ?? $item['quantity'],
            'quantity' => $item['quantity'],
            'used_quantity' => $item['used_quantity'],
            'nights_snapshot' => $item['nights_snapshot'] ?? 1,
            'rooms_snapshot' => $item['rooms_snapshot'] ?? 1,
            'people_snapshot' => $item['people_snapshot'] ?? 1,
            'billing_status' => $item['billing_status'],
            'total' => $item['total'],
            'note' => $item['note'] ?? null,
        ]);
    }

    private function emptyResult(): array
    {
        return [
            'codes' => [],
            'discount_total' => 0,
            'money_discount_total' => 0,
            'service_discount_total' => 0,
            'room_upgrade_discount_total' => 0,
        ];
    }
}
