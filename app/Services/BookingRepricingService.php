<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPromotion;
use App\Models\BookingPromotionServiceOffer;
use App\Models\Promotion;
use App\Models\PromotionRoomUpgradeOffer;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingRepricingService
{
    public function __construct(
        private readonly BookingServicePricingService $servicePricing,
        private readonly PromotionService $promotionService,
        private readonly BookingFinancialService $financialService
    ) {
    }

    public function preview(
        Booking $booking,
        Carbon $newCheckInAt,
        Carbon $newCheckOutAt,
        float $newOneNightRoomTotal,
        ?float $newCategoryPrice = null
    ): array {
        $booking->loadMissing([
            'serviceItems.service',
            'bookingPromotions.bookingRoom.room.category',
            'bookingPromotions.promotion.serviceOffers.service',
            'bookingPromotions.promotion.roomUpgradeOffers',
            'bookingPromotions.serviceOffers.bookingRoom.room',
            'bookingPromotions.roomUpgradeOffers.offer',
            'payments',
            'customer',
            'bookingRooms.room.category',
            'guests',
            'roomInspections.items',
        ]);

        $newNightCount = max(1, $newCheckInAt->copy()->startOfDay()->diffInDays($newCheckOutAt->copy()->startOfDay()));
        $newRoomQuantity = max(1, $booking->bookingRooms->count());
        $newGuestCount = max(1, (int) $booking->adult_count + (int) $booking->child_count + (int) ($booking->baby_count ?? 0));
        $newRoomTotal = round(max(0, $newOneNightRoomTotal) * $newNightCount, 0);

        $excludedItemIds = [];
        $promotionPreview = [];
        $servicePreview = [];

        // Mã bị gỡ có thể kéo theo dịch vụ được tự thêm từ mã. Lặp vài vòng để
        // tổng dịch vụ và điều kiện mã ổn định trước khi hiển thị bản xem trước.
        for ($i = 0; $i < 5; $i++) {
            $servicePreview = $this->servicePricing->previewBookingItems(
                $booking,
                $newNightCount,
                $newRoomQuantity,
                $newGuestCount,
                $excludedItemIds
            );

            $serviceItemsForPromotion = collect($servicePreview['lines'])
                ->reject(fn (array $line) => !empty($line['will_remove']))
                ->map(fn (array $line) => [
                    'service_id' => $line['service_id'],
                    'scope' => $line['scope'] ?? 'booking',
                    'booking_room_id' => $line['booking_room_id'] ?? null,
                    'room_id_snapshot' => $line['room_id_snapshot'] ?? null,
                    'name' => $line['name'],
                    'type' => $line['type'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => max(1, (int) $line['billed_quantity']),
                    'used_quantity' => max(1, (int) $line['billed_quantity']),
                    'billing_status' => $line['billing_status'],
                    'total' => $line['new_total'],
                ])
                ->values()
                ->all();

            $context = [
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
                'customer_email' => $booking->booked_customer_email,
                'customer_phone' => $booking->booked_customer_phone,
                'customer_cccd' => $booking->booked_customer_cccd,
                'subtotal_amount' => $newRoomTotal + (float) $servicePreview['new_total'],
                'service_items' => $serviceItemsForPromotion,
                'check_in_at' => $newCheckInAt,
                'check_out_at' => $newCheckOutAt,
                'night_count' => $newNightCount,
                'room_quantity' => $newRoomQuantity,
                'guest_count' => $newGuestCount,
            ];

            $promotionPreview = $this->previewPromotions(
                $booking,
                $context,
                $newNightCount,
                $newCategoryPrice
            );

            $nextExcludedItemIds = $this->findAutoServiceItemIdsForRemovedUsages(
                $booking,
                $promotionPreview['removed'] ?? []
            );

            sort($nextExcludedItemIds);
            $current = $excludedItemIds;
            sort($current);

            if ($nextExcludedItemIds === $current) {
                break;
            }

            $excludedItemIds = $nextExcludedItemIds;
        }

        $oldRoomTotal = $this->currentRoomTotal($booking);
        $oldServiceTotal = (float) $booking->serviceItems
            ->where('billing_status', 'confirmed')
            ->sum('total');
        $approvedInspectionTotal = (float) $booking->roomInspections
            ->flatMap->items
            ->where('status', 'approved')
            ->sum('total');
        $oldDiscountTotal = (float) $booking->discount_amount;
        $oldTotal = max(0, round(
            $oldRoomTotal + $oldServiceTotal + $approvedInspectionTotal - $oldDiscountTotal,
            0
        ));

        $newSubtotal = round(
            $newRoomTotal + (float) $servicePreview['new_total'] + $approvedInspectionTotal,
            0
        );
        $newDiscountTotal = round((float) $promotionPreview['discount_total'], 0);
        $newTotal = max(0, round($newSubtotal - $newDiscountTotal, 0));
        $paidTotal = round($this->financialService->paidTotal($booking), 0);
        $remaining = max(0, round($newTotal - $paidTotal, 0));
        $overpayment = max(0, round($paidTotal - $newTotal, 0));

        // Cọc chỉ dựa trên tiền phòng sau phần giảm tiền/nâng hạng có thể quy về phòng.
        // Dịch vụ và phụ thu phát sinh không làm tăng ngưỡng cọc trước check-in.
        $roomDiscountForDeposit = min(
            $newRoomTotal,
            (float) $promotionPreview['money_discount_total']
                + (float) $promotionPreview['room_upgrade_discount_total']
        );
        $requiredDeposit = round(max(0, $newRoomTotal - $roomDiscountForDeposit) * 0.30, 0);
        $depositShortfall = max(0, round($requiredDeposit - $paidTotal, 0));

        return [
            'old' => [
                'night_count' => $this->nightCount($booking->check_in_at, $booking->check_out_at),
                'room_total' => $oldRoomTotal,
                'service_total' => $oldServiceTotal,
                'inspection_total' => $approvedInspectionTotal,
                'discount_total' => $oldDiscountTotal,
                'total' => $oldTotal,
                'required_deposit' => $this->financialService->requiredDeposit($booking),
            ],
            'new' => [
                'night_count' => $newNightCount,
                'room_quantity' => $newRoomQuantity,
                'room_total' => $newRoomTotal,
                'service_total' => (float) $servicePreview['new_total'],
                'inspection_total' => $approvedInspectionTotal,
                'subtotal' => $newSubtotal,
                'discount_total' => $newDiscountTotal,
                'total' => $newTotal,
                'required_deposit' => $requiredDeposit,
                'deposit_shortfall' => $depositShortfall,
                'remaining' => $remaining,
                'overpayment' => $overpayment,
            ],
            'paid_total' => $paidTotal,
            'service_preview' => $servicePreview,
            'promotion_preview' => $promotionPreview,
            'excluded_service_item_ids' => $excludedItemIds,
            'period' => [
                'check_in_at' => $newCheckInAt->format('Y-m-d H:i:s'),
                'check_out_at' => $newCheckOutAt->format('Y-m-d H:i:s'),
                'text' => $newCheckInAt->format('d/m/Y H:i') . ' → ' . $newCheckOutAt->format('d/m/Y H:i'),
            ],
        ];
    }

    public function apply(Booking $booking, array $preview): array
    {
        $new = $preview['new'];
        $promotionPreview = $preview['promotion_preview'];

        $this->servicePricing->persistBookingItems(
            $booking,
            (int) $new['night_count'],
            (int) $new['room_quantity'],
            max(1, (int) $booking->adult_count + (int) $booking->child_count + (int) ($booking->baby_count ?? 0)),
            $preview['excluded_service_item_ids'] ?? []
        );

        $this->persistPromotions($booking, $promotionPreview);

        $calculation = app(BookingCalculatorService::class)->calculateTotal($booking->fresh());
        $booking->forceFill([
            // Do not persist a total calculated from a one-price-per-room approximation.
            'subtotal_amount' => (float) $calculation['room_total'] + (float) $calculation['services_total'] + (float) $calculation['inspection_total'],
            'discount_amount' => (float) $calculation['booking_discount'],
            'estimated_total' => (float) $calculation['total'],
            'required_deposit_amount' => (float) $new['required_deposit'],
            'overpayment_amount' => (float) $new['overpayment'],
            'payment_status' => $preview['paid_total'] <= 0
                ? 'unpaid'
                : ((float) $new['remaining'] <= 0.01 ? 'paid' : 'partial'),
        ])->save();

        return $preview;
    }

    private function previewPromotions(
        Booking $booking,
        array $context,
        int $newNightCount,
        ?float $newCategoryPrice
    ): array {
        $removed = [];
        $kept = [];
        $standardResults = [];
        $moneyDiscountTotal = 0;
        $serviceDiscountTotal = 0;

        $standardUsages = $booking->bookingPromotions
            ->filter(fn (BookingPromotion $usage) =>
                (float) $usage->money_discount_amount > 0
                || (float) $usage->service_discount_amount > 0
            )
            ->groupBy(fn (BookingPromotion $usage) =>
                ($usage->scope ?? 'booking') === 'room' && $usage->booking_room_id
                    ? 'room:' . (int) $usage->booking_room_id
                    : 'booking'
            );

        foreach ($standardUsages as $scopeKey => $usages) {
            $scopeRoomId = str_starts_with((string) $scopeKey, 'room:')
                ? (int) substr((string) $scopeKey, 5)
                : null;
            $scopeContext = $this->promotionContextForScope(
                $booking,
                $context,
                $scopeRoomId,
                $newNightCount,
                $newCategoryPrice
            );

            if ($scopeRoomId && !$scopeContext) {
                foreach ($usages as $usage) {
                    $removed[] = $this->removedUsage($usage, 'Phòng áp dụng mã không còn thuộc booking.');
                }
                continue;
            }

            $acceptedCodes = [];
            $acceptedUsages = collect();
            $groupResult = [
                'ok' => true,
                'promotions' => collect(),
                'discount_total' => 0,
                'money_discount_total' => 0,
                'service_discount_total' => 0,
                'room_upgrade_discount_total' => 0,
                'messages' => [],
            ];

            foreach ($usages as $usage) {
                $promotion = $usage->promotion;
                if (!$promotion) {
                    $removed[] = $this->removedUsage($usage, 'Mã không còn tồn tại trong danh mục.');
                    continue;
                }

                $check = $this->promotionService->checkExistingPromotionEligibility(
                    $promotion,
                    $scopeContext,
                    'reprice',
                    $usage->note
                );

                if (!$check['ok']) {
                    $removed[] = $this->removedUsage($usage, $check['message']);
                    continue;
                }

                $candidateCodes = array_merge($acceptedCodes, [$promotion->code]);
                $candidateResult = $this->promotionService->validateCodes(
                    $candidateCodes,
                    $scopeContext,
                    'reprice',
                    null,
                    true,
                    false
                );

                if (!$candidateResult['ok']) {
                    $removed[] = $this->removedUsage(
                        $usage,
                        implode(' ', $candidateResult['messages'] ?? ['Mã không còn tạo được ưu đãi hợp lệ.'])
                    );
                    continue;
                }

                $acceptedCodes = $candidateCodes;
                $acceptedUsages->push($usage);
                $groupResult = $candidateResult;
            }

            if ($acceptedUsages->isEmpty()) {
                continue;
            }

            foreach ($groupResult['promotions'] ?? collect() as $promotion) {
                $usage = $acceptedUsages->first(
                    fn (BookingPromotion $item) => (int) $item->promotion_id === (int) $promotion->id
                );
                if (!$usage) {
                    continue;
                }

                $kept[] = [
                    'usage_id' => (int) $usage->id,
                    'code' => $promotion->code,
                    'discount_amount' => (float) ($promotion->calculated_discount_amount ?? 0),
                    'kind' => 'standard',
                    'scope' => $scopeRoomId ? 'room' : 'booking',
                    'booking_room_id' => $scopeRoomId,
                ];
            }

            $moneyDiscountTotal += (float) ($groupResult['money_discount_total'] ?? 0);
            $serviceDiscountTotal += (float) ($groupResult['service_discount_total'] ?? 0);
            $standardResults[] = [
                'scope' => $scopeRoomId ? 'room' : 'booking',
                'booking_room_id' => $scopeRoomId,
                'room_id_snapshot' => $scopeRoomId
                    ? optional($booking->bookingRooms->firstWhere('id', $scopeRoomId))->room_id
                    : null,
                'result' => $groupResult,
            ];
        }

        $roomUpgradeUsages = $booking->bookingPromotions
            ->filter(fn (BookingPromotion $usage) => (float) $usage->room_upgrade_discount_amount > 0)
            ->values();

        $upgradeResults = [];
        $roomUpgradeDiscountTotal = 0;

        foreach ($roomUpgradeUsages as $usage) {
            $promotion = $usage->promotion;
            if (!$promotion) {
                $removed[] = $this->removedUsage($usage, 'Mã hỗ trợ nâng hạng không còn tồn tại.');
                continue;
            }

            $scopeRoomId = ($usage->scope ?? 'booking') === 'room' && $usage->booking_room_id
                ? (int) $usage->booking_room_id
                : null;
            $scopeContext = $this->promotionContextForScope(
                $booking,
                $context,
                $scopeRoomId,
                $newNightCount,
                $newCategoryPrice
            );

            if ($scopeRoomId && !$scopeContext) {
                $removed[] = $this->removedUsage($usage, 'Phòng áp dụng mã nâng hạng không còn thuộc booking.');
                continue;
            }

            $check = $this->promotionService->checkExistingPromotionEligibility(
                $promotion,
                $scopeContext,
                'reprice',
                $usage->note
            );

            if (!$check['ok']) {
                $removed[] = $this->removedUsage($usage, $check['message']);
                continue;
            }

            $usageCovered = 0;
            $snapshotResults = [];

            foreach ($usage->roomUpgradeOffers as $snapshot) {
                $oldBasePrice = (float) $snapshot->old_room_price_snapshot;
                $newPrice = $newCategoryPrice !== null
                    ? $newCategoryPrice
                    : (float) $snapshot->new_room_price_snapshot;
                $quantity = max(1, (int) $snapshot->room_quantity);
                $difference = max(0, round(($newPrice - $oldBasePrice) * $newNightCount * $quantity, 0));
                $offer = $snapshot->offer;

                if ($offer) {
                    $covered = $this->promotionService->calculateRoomUpgradeCoverAmount($offer, $difference);
                } else {
                    $oldDifference = max(0.01, (float) $snapshot->original_difference_amount);
                    $ratio = min(1, max(0, (float) $snapshot->covered_amount / $oldDifference));
                    $covered = round($difference * $ratio, 0);
                }

                $snapshotResults[] = [
                    'snapshot_id' => (int) $snapshot->id,
                    'new_price' => $newPrice,
                    'night_count' => $newNightCount,
                    'room_quantity' => $quantity,
                    'difference' => $difference,
                    'covered_amount' => $covered,
                    'guest_extra_amount' => max(0, $difference - $covered),
                ];
                $usageCovered += $covered;
            }

            if ($usageCovered <= 0 && $usage->roomUpgradeOffers->isNotEmpty()) {
                $removed[] = $this->removedUsage(
                    $usage,
                    'Lịch/hạng mới không còn phát sinh phần chênh lệch nâng hạng để mã hỗ trợ này chi trả.'
                );
                continue;
            }

            // Giá phòng mới luôn được ghi nhận theo giá thật. Mã nâng hạng/sự cố
            // phải trừ đúng phần được khách sạn hỗ trợ; phần còn lại khách thanh toán.
            $effectiveDiscount = $usageCovered;
            $upgradeResults[(int) $usage->id] = [
                'stored_discount_amount' => round($usageCovered, 0),
                'effective_discount_amount' => round($effectiveDiscount, 0),
                'snapshots' => $snapshotResults,
            ];
            $roomUpgradeDiscountTotal += $effectiveDiscount;
            $kept[] = [
                'usage_id' => (int) $usage->id,
                'code' => $usage->code_snapshot,
                'discount_amount' => round($effectiveDiscount, 0),
                'support_value' => round($usageCovered, 0),
                'kind' => 'room_upgrade',
                'scope' => $scopeRoomId ? 'room' : 'booking',
                'booking_room_id' => $scopeRoomId,
            ];
        }

        $removed = collect($removed)
            ->unique(fn (array $item) => $item['usage_id'] ?? ($item['code'] . ':' . ($item['booking_room_id'] ?? 'booking')))
            ->values()
            ->all();

        return [
            'standard_results' => $standardResults,
            'upgrade_results' => $upgradeResults,
            'kept' => $kept,
            'removed' => $removed,
            'money_discount_total' => round($moneyDiscountTotal, 0),
            'service_discount_total' => round($serviceDiscountTotal, 0),
            'room_upgrade_discount_total' => round($roomUpgradeDiscountTotal, 0),
            'discount_total' => round(
                $moneyDiscountTotal + $serviceDiscountTotal + $roomUpgradeDiscountTotal,
                0
            ),
        ];
    }

    private function promotionContextForScope(
        Booking $booking,
        array $baseContext,
        ?int $bookingRoomId,
        int $newNightCount,
        ?float $newCategoryPrice
    ): ?array {
        $baseContext['eligibility_subtotal_amount'] = $baseContext['subtotal_amount'] ?? 0;
        $baseContext['eligibility_room_quantity'] = $baseContext['room_quantity'] ?? 1;

        if (!$bookingRoomId) {
            $baseContext['scope'] = 'booking';
            $baseContext['booking_room_id'] = null;
            return $baseContext;
        }

        $bookingRoom = $booking->bookingRooms->firstWhere('id', $bookingRoomId);
        if (!$bookingRoom) {
            return null;
        }

        $roomServices = collect($baseContext['service_items'] ?? [])
            ->filter(fn (array $item) => (int) ($item['booking_room_id'] ?? 0) === $bookingRoomId)
            ->values();
        $roomServiceTotal = (float) $roomServices->sum('total');
        $roomPrice = $newCategoryPrice !== null
            ? $newCategoryPrice
            : (float) $bookingRoom->price_at_booking;
        $roomTotal = max(0, round($roomPrice * $newNightCount + (float) $bookingRoom->surcharge, 0));
        $roomGuestCount = $booking->guests->where('booking_room_id', $bookingRoomId)->count();
        if ($roomGuestCount <= 0) {
            $roomGuestCount = max(1, (int) $bookingRoom->adult_count + (int) $bookingRoom->child_count);
        }

        return array_merge($baseContext, [
            'subtotal_amount' => $roomTotal + $roomServiceTotal,
            'service_items' => $roomServices->all(),
            'room_quantity' => 1,
            'guest_count' => max(1, $roomGuestCount),
            'scope' => 'room',
            'booking_room_id' => $bookingRoomId,
            'room_id_snapshot' => $bookingRoom->room_id,
        ]);
    }

    private function removedUsage(BookingPromotion $usage, string $reason): array
    {
        return [
            'usage_id' => (int) $usage->id,
            'code' => $usage->code_snapshot,
            'scope' => $usage->scope ?? 'booking',
            'booking_room_id' => $usage->booking_room_id ? (int) $usage->booking_room_id : null,
            'reason' => $reason,
        ];
    }

    private function persistPromotions(Booking $booking, array $promotionPreview): void
    {
        $removedUsageIds = collect($promotionPreview['removed'] ?? [])
            ->pluck('usage_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($removedUsageIds->isNotEmpty()) {
            $booking->bookingPromotions
                ->whereIn('id', $removedUsageIds->all())
                ->each(function (BookingPromotion $usage) {
                    $usage->serviceOffers()->delete();
                    $usage->roomUpgradeOffers()->delete();
                    if ($usage->promotion && (int) $usage->promotion->used_count > 0) {
                        $usage->promotion->decrement('used_count');
                    }
                    $usage->delete();
                });
        }

        foreach (($promotionPreview['standard_results'] ?? []) as $group) {
            $result = $group['result'] ?? null;
            if (!$result || ($result['promotions'] ?? collect())->isEmpty()) {
                continue;
            }

            $scope = $group['scope'] ?? 'booking';
            $bookingRoomId = $group['booking_room_id'] ?? null;
            $roomIdSnapshot = $group['room_id_snapshot'] ?? null;

            foreach ($result['promotions'] as $promotion) {
                $usage = $booking->bookingPromotions
                    ->first(function (BookingPromotion $item) use ($promotion, $scope, $bookingRoomId) {
                        if ((int) $item->promotion_id !== (int) $promotion->id) {
                            return false;
                        }

                        if ($scope === 'room') {
                            return ($item->scope ?? 'booking') === 'room'
                                && (int) $item->booking_room_id === (int) $bookingRoomId;
                        }

                        return ($item->scope ?? 'booking') === 'booking'
                            && empty($item->booking_room_id);
                    });

                if (!$usage) {
                    continue;
                }

                $money = round((float) ($promotion->calculated_money_discount_amount ?? 0), 0);
                $service = round((float) ($promotion->calculated_service_discount_amount ?? 0), 0);
                $usage->forceFill([
                    'money_discount_amount' => $money,
                    'service_discount_amount' => $service,
                    'discount_amount' => $money + $service + (float) $usage->room_upgrade_discount_amount,
                ])->save();

                $usage->serviceOffers()->delete();
                foreach (($promotion->calculated_service_offer_snapshots ?? collect()) as $snapshot) {
                    BookingPromotionServiceOffer::create([
                        'booking_id' => $booking->id,
                        'booking_room_id' => $scope === 'room' ? $bookingRoomId : null,
                        'room_id_snapshot' => $scope === 'room' ? $roomIdSnapshot : null,
                        'booking_promotion_id' => $usage->id,
                        'promotion_id' => $promotion->id,
                        'promotion_service_offer_id' => $snapshot['promotion_service_offer_id'] ?? null,
                        'service_id' => $snapshot['service_id'] ?? null,
                        'code_snapshot' => $promotion->code,
                        'service_name_snapshot' => $snapshot['service_name_snapshot'],
                        'service_unit_snapshot' => $snapshot['service_unit_snapshot'],
                        'service_price_snapshot' => $snapshot['service_price_snapshot'],
                        'discount_type_snapshot' => $snapshot['discount_type_snapshot'],
                        'discount_value_snapshot' => $snapshot['discount_value_snapshot'],
                        'quantity' => $snapshot['quantity'],
                        'original_amount' => $snapshot['original_amount'],
                        'discount_amount' => $snapshot['discount_amount'],
                        'final_amount' => $snapshot['final_amount'],
                        'note' => $snapshot['note'] ?? null,
                    ]);
                }
            }
        }

        foreach (($promotionPreview['upgrade_results'] ?? []) as $usageId => $result) {
            $usage = BookingPromotion::find($usageId);
            if (!$usage || (int) $usage->booking_id !== (int) $booking->id) {
                continue;
            }

            foreach ($result['snapshots'] as $snapshotResult) {
                $snapshot = $usage->roomUpgradeOffers()->whereKey($snapshotResult['snapshot_id'])->first();
                if (!$snapshot) {
                    continue;
                }

                $snapshot->forceFill([
                    'new_room_price_snapshot' => $snapshotResult['new_price'],
                    'night_count' => $snapshotResult['night_count'],
                    'room_quantity' => $snapshotResult['room_quantity'],
                    'original_difference_amount' => $snapshotResult['difference'],
                    'covered_amount' => $snapshotResult['covered_amount'],
                    'guest_extra_amount' => $snapshotResult['guest_extra_amount'],
                ])->save();
            }

            $storedRoomUpgradeDiscount = round((float) ($result['stored_discount_amount'] ?? 0), 0);
            $usage->forceFill([
                'room_upgrade_discount_amount' => $storedRoomUpgradeDiscount,
                'discount_amount' => (float) $usage->money_discount_amount
                    + (float) $usage->service_discount_amount
                    + $storedRoomUpgradeDiscount,
            ])->save();
        }
    }

    private function findAutoServiceItemIdsForRemovedUsages(Booking $booking, array $removedUsages): array
    {
        $removed = collect($removedUsages)
            ->filter(fn (array $item) => !empty($item['code']))
            ->values();

        if ($removed->isEmpty()) {
            return [];
        }

        return $booking->serviceItems
            ->filter(function ($item) use ($removed) {
                $note = strtoupper((string) $item->note);

                return $removed->contains(function (array $usage) use ($item, $note) {
                    $code = strtoupper((string) $usage['code']);
                    $matchesCode = str_contains($note, 'MÃ ƯU ĐÃI ' . $code)
                        || str_contains($note, 'MA UU DAI ' . $code)
                        || str_contains($note, $code);
                    if (!$matchesCode) {
                        return false;
                    }

                    if (($usage['scope'] ?? 'booking') === 'room') {
                        return ($item->scope ?? 'booking') === 'room'
                            && (int) $item->booking_room_id === (int) ($usage['booking_room_id'] ?? 0);
                    }

                    return ($item->scope ?? 'booking') === 'booking'
                        && empty($item->booking_room_id);
                });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function currentRoomTotal(Booking $booking): float
    {
        return (float) app(BookingCalculatorService::class)->calculateTotal($booking)['room_total'];
    }

    private function nightCount($checkInAt, $checkOutAt): int
    {
        return max(
            1,
            Carbon::parse($checkInAt, 'Asia/Ho_Chi_Minh')->startOfDay()
                ->diffInDays(Carbon::parse($checkOutAt, 'Asia/Ho_Chi_Minh')->startOfDay())
        );
    }
}
