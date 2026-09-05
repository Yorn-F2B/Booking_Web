<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPromotion;
use App\Models\BookingPromotionServiceOffer;
use App\Models\Customer;
use App\Models\Promotion;
use App\Models\PromotionRoomUpgradeOffer;
use App\Models\PromotionServiceOffer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class PromotionService
{
    public function selectionRules(): array
    {
        return [
            Promotion::TYPE_NORMAL => [
                'label' => 'Mã thường',
                'limit' => 1,
            ],
            Promotion::TYPE_EVENT => [
                'label' => 'Mã sự kiện',
                'limit' => 1,
            ],
            Promotion::TYPE_CONDITIONAL => [
                'label' => 'Mã điều kiện',
                'limit' => 1,
            ],
            Promotion::TYPE_SUPPORT => [
                'label' => 'Mã hỗ trợ',
                'limit' => null,
            ],
        ];
    }

    public function availablePromotions(array $context, string $channel = 'user'): Collection
    {
        $query = Promotion::query()
            ->with(['serviceOffers.service', 'roomUpgradeOffers.fromCategory', 'roomUpgradeOffers.toCategory'])
            ->where('status', 'active')
            ->orderByRaw("FIELD(promotion_type, 'normal_discount', 'event_discount', 'conditional_discount', 'support_discount')")
            ->orderBy('code');

        if ($channel === 'user') {
            $query->where('user_can_apply', true)
                ->where('is_public', true)
                ->where('promotion_type', '!=', Promotion::TYPE_SUPPORT);
        } else {
            $query->where('admin_can_apply', true);
        }

        return $query->get()
            ->filter(function (Promotion $promotion) use ($context, $channel) {
                return $this->checkPromotion($promotion, $context, $channel, false)['ok'];
            })
            ->values();
    }

    public function validateCodes(
        array $codes,
        array $context,
        string $channel = 'user',
        ?string $note = null,
        bool $ignoreCombinationRules = false,
        bool $strictConditions = true
    ): array
    {
        $codes = collect($codes)
            ->filter()
            ->map(fn ($code) => strtoupper(trim((string) $code)))
            ->filter()
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return $this->emptyResult((float) ($context['subtotal_amount'] ?? 0));
        }

        $promotions = Promotion::with(['serviceOffers.service', 'roomUpgradeOffers.fromCategory', 'roomUpgradeOffers.toCategory'])
            ->whereIn('code', $codes)
            ->where('status', 'active')
            ->get()
            ->keyBy('code');

        $messages = [];
        $validPromotions = collect();

        foreach ($codes as $code) {
            $promotion = $promotions->get($code);

            if (!$promotion) {
                $messages[] = 'Mã ' . $code . ' không tồn tại hoặc đã bị tắt.';
                continue;
            }

            $check = $this->checkPromotion($promotion, $context, $channel, $strictConditions, $note);

            if (!$check['ok']) {
                $messages[] = $check['message'];
                continue;
            }

            $validPromotions->push($promotion);
        }

        if (!empty($messages)) {
            return [
                'ok' => false,
                'promotions' => collect(),
                'discount_total' => 0,
                'money_discount_total' => 0,
                'service_discount_total' => 0,
                'room_upgrade_discount_total' => 0,
                'subtotal_amount' => (float) ($context['subtotal_amount'] ?? 0),
                'auto_service_items' => [],
                'messages' => $messages,
            ];
        }

        $nonStackable = $validPromotions->where('is_stackable', false);

        if (!$ignoreCombinationRules && $validPromotions->count() > 1 && $nonStackable->isNotEmpty()) {
            return [
                'ok' => false,
                'promotions' => collect(),
                'discount_total' => 0,
                'money_discount_total' => 0,
                'service_discount_total' => 0,
                'room_upgrade_discount_total' => 0,
                'subtotal_amount' => (float) ($context['subtotal_amount'] ?? 0),
                'auto_service_items' => [],
                'messages' => ['Có mã được cấu hình chỉ dùng một mình. Vui lòng bỏ các mã khác hoặc bỏ mã đó.'],
            ];
        }

        // Một nguồn quy tắc duy nhất cho backend lẫn UI. Không để màn tạo/sửa
        // booking tự đặt giới hạn khác với validate server.
        foreach ($this->selectionRules() as $type => $rule) {
            $limit = $rule['limit'];
            if (!$ignoreCombinationRules && $limit !== null && $validPromotions->where('promotion_type', $type)->count() > $limit) {
                return [
                    'ok' => false,
                    'promotions' => collect(),
                    'discount_total' => 0,
                    'money_discount_total' => 0,
                    'service_discount_total' => 0,
                    'room_upgrade_discount_total' => 0,
                    'subtotal_amount' => (float) ($context['subtotal_amount'] ?? 0),
                    'auto_service_items' => [],
                    'messages' => ['Mỗi booking chỉ được chọn tối đa ' . $limit . ' ' . mb_strtolower($rule['label']) . '.'],
                ];
            }
        }

        $baseSubtotal = max(0, (float) ($context['subtotal_amount'] ?? 0));
        $serviceItems = $this->normalizeServiceItems($context['service_items'] ?? []);
        $autoServiceItems = [];
        $serviceDiscountTotal = 0;

        foreach ($validPromotions as $promotion) {
            $serviceOfferResult = $this->calculateServiceOffers($promotion, $serviceItems, true, $context);

            foreach ($serviceOfferResult['auto_service_items'] as $autoItem) {
                $autoServiceItems[] = $autoItem;
                $serviceItems = $this->mergeServiceItems($serviceItems, [$autoItem]);
                $baseSubtotal += (float) $autoItem['total'];
            }
        }

        $remainingForServiceDiscount = $baseSubtotal;

        foreach ($validPromotions as $promotion) {
            $serviceOfferResult = $this->calculateServiceOffers($promotion, $serviceItems, false, $context);
            $snapshots = collect($serviceOfferResult['snapshots'])
                ->map(function (array $snapshot) use (&$remainingForServiceDiscount) {
                    $snapshot['discount_amount'] = min((float) $snapshot['discount_amount'], $remainingForServiceDiscount);
                    $snapshot['final_amount'] = max(0, (float) $snapshot['original_amount'] - (float) $snapshot['discount_amount']);
                    $remainingForServiceDiscount -= (float) $snapshot['discount_amount'];

                    return $snapshot;
                })
                ->filter(fn (array $snapshot) => (float) $snapshot['discount_amount'] > 0)
                ->values();

            $promotionServiceDiscount = round($snapshots->sum('discount_amount'), 0);
            $promotion->calculated_service_discount_amount = $promotionServiceDiscount;
            $promotion->calculated_service_offer_snapshots = $snapshots;

            $serviceDiscountTotal += $promotionServiceDiscount;
        }

        $remaining = max(0, $baseSubtotal - $serviceDiscountTotal);
        $moneyDiscountTotal = 0;
        $calculatedPromotions = collect();

        foreach ($validPromotions as $promotion) {
            $moneyDiscountAmount = $this->calculateDiscountAmount($promotion, $baseSubtotal);
            $moneyDiscountAmount = min($moneyDiscountAmount, $remaining);
            $moneyDiscountAmount = round($moneyDiscountAmount, 0);

            $promotionServiceDiscount = round((float) ($promotion->calculated_service_discount_amount ?? 0), 0);
            $totalPromotionDiscount = $moneyDiscountAmount + $promotionServiceDiscount;

            if ($totalPromotionDiscount <= 0) {
                return [
                    'ok' => false,
                    'promotions' => collect(),
                    'discount_total' => 0,
                    'money_discount_total' => 0,
                    'service_discount_total' => 0,
                    'subtotal_amount' => $baseSubtotal,
                    'auto_service_items' => [],
                    'messages' => ['Mã ' . $promotion->code . ' không tạo ra ưu đãi hợp lệ.'],
                ];
            }

            $promotion->calculated_money_discount_amount = $moneyDiscountAmount;
            $promotion->calculated_discount_amount = $totalPromotionDiscount;

            $moneyDiscountTotal += $moneyDiscountAmount;
            $remaining -= $moneyDiscountAmount;
            $calculatedPromotions->push($promotion);

            if ($remaining <= 0) {
                $remaining = 0;
            }
        }

        return [
            'ok' => true,
            'promotions' => $calculatedPromotions,
            'discount_total' => round($moneyDiscountTotal + $serviceDiscountTotal, 0),
            'money_discount_total' => round($moneyDiscountTotal, 0),
            'service_discount_total' => round($serviceDiscountTotal, 0),
            'subtotal_amount' => round($baseSubtotal, 0),
            'auto_service_items' => $autoServiceItems,
            'messages' => [],
        ];
    }



    public function findRoomUpgradeOffer(
        string $code,
        int $fromCategoryId,
        int $toCategoryId,
        string $upgradeKind,
        array $context = [],
        string $channel = 'admin',
        ?string $note = null
    ): array {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return $this->fail('Vui lòng nhập mã nâng hạng phòng.');
        }

        if (!in_array($upgradeKind, [
            PromotionRoomUpgradeOffer::KIND_INCIDENT_SUPPORT,
            PromotionRoomUpgradeOffer::KIND_PAID_UPSELL,
        ], true)) {
            return $this->fail('Loại nâng hạng phòng không hợp lệ.');
        }

        $promotion = Promotion::with([
            'roomUpgradeOffers.fromCategory',
            'roomUpgradeOffers.toCategory',
            'serviceOffers.service',
        ])
            ->where('code', $code)
            ->where('status', 'active')
            ->first();

        if (!$promotion) {
            return $this->fail('Mã ' . $code . ' không tồn tại hoặc đã bị tắt.');
        }

        if (
            $upgradeKind === PromotionRoomUpgradeOffer::KIND_INCIDENT_SUPPORT
            && $promotion->promotion_type !== Promotion::TYPE_SUPPORT
        ) {
            return $this->fail('Mã ' . $code . ' không phải mã hỗ trợ sự cố.');
        }

        if (
            $upgradeKind === PromotionRoomUpgradeOffer::KIND_PAID_UPSELL
            && $promotion->promotion_type !== Promotion::TYPE_CONDITIONAL
        ) {
            return $this->fail('Mã ' . $code . ' không phải mã điều kiện nâng hạng.');
        }

        $check = $this->checkPromotion($promotion, $context, $channel, true, $note);

        if (!$check['ok']) {
            return $check;
        }

        $offer = $promotion->roomUpgradeOffers
            ->where('upgrade_kind', $upgradeKind)
            ->first(function (PromotionRoomUpgradeOffer $offer) use ($fromCategoryId, $toCategoryId) {
                $fromOk = empty($offer->from_room_category_id)
                    || (int) $offer->from_room_category_id === (int) $fromCategoryId;
                $toOk = empty($offer->to_room_category_id)
                    || (int) $offer->to_room_category_id === (int) $toCategoryId;

                return $fromOk && $toOk;
            });

        if (!$offer) {
            return $this->fail('Mã ' . $code . ' không áp dụng cho cặp hạng phòng đang đổi.');
        }

        if ($offer->requires_hotel_fault_reason && trim((string) $note) === '') {
            return $this->fail('Mã ' . $code . ' cần nhập lý do sự cố khi nâng hạng.');
        }

        return [
            'ok' => true,
            'message' => null,
            'promotion' => $promotion,
            'offer' => $offer,
        ];
    }

    public function calculateRoomUpgradeCoverAmount(PromotionRoomUpgradeOffer $offer, float $differenceAmount): float
    {
        $differenceAmount = max(0, $differenceAmount);

        if ($differenceAmount <= 0) {
            return 0;
        }

        $coveredAmount = match ($offer->cover_type) {
            PromotionRoomUpgradeOffer::COVER_FULL_DIFFERENCE => $differenceAmount,
            PromotionRoomUpgradeOffer::COVER_PERCENT_DIFFERENCE => $differenceAmount * ((float) $offer->cover_value / 100),
            PromotionRoomUpgradeOffer::COVER_FIXED_AMOUNT => (float) $offer->cover_value,
            default => 0,
        };

        if ((float) $offer->max_cover_amount > 0) {
            $coveredAmount = min($coveredAmount, (float) $offer->max_cover_amount);
        }

        return round(min(max(0, $coveredAmount), $differenceAmount), 0);
    }

    public function storeRoomUpgradeUsage(
        Booking $booking,
        Promotion $promotion,
        PromotionRoomUpgradeOffer $offer,
        array $snapshot,
        string $channel = 'admin',
        ?string $note = null,
        ?int $appliedBy = null,
        array $scopeContext = []
    ): BookingPromotion {
        $coveredAmount = round((float) ($snapshot['covered_amount'] ?? 0), 0);

        $bookingPromotion = BookingPromotion::create([
            'booking_id' => $booking->id,
            'scope' => $scopeContext['scope'] ?? 'booking',
            'booking_room_id' => $scopeContext['booking_room_id'] ?? null,
            'room_issue_request_id' => $scopeContext['room_issue_request_id'] ?? null,
            'promotion_id' => $promotion->id,
            'code_snapshot' => $promotion->code,
            'promotion_type_snapshot' => $promotion->promotion_type,
            'discount_type_snapshot' => $promotion->discount_type,
            'discount_value_snapshot' => $promotion->discount_value,
            'money_discount_amount' => 0,
            'service_discount_amount' => 0,
            'room_upgrade_discount_amount' => $coveredAmount,
            'discount_amount' => $coveredAmount,
            'applied_by' => $appliedBy,
            'applied_channel' => $channel,
            'note' => $note,
        ]);

        $bookingPromotion->roomUpgradeOffers()->create(array_merge($snapshot, [
            'booking_id' => $booking->id,
            'booking_promotion_id' => $bookingPromotion->id,
            'promotion_id' => $promotion->id,
            'promotion_room_upgrade_offer_id' => $offer->id,
            'upgrade_kind_snapshot' => $offer->upgrade_kind,
            'cover_type_snapshot' => $offer->cover_type,
            'cover_value_snapshot' => $offer->cover_value,
            'reason' => $note,
        ]));

        $promotion->increment('used_count');

        return $bookingPromotion;
    }

    public function mergeServiceItems(array $serviceItems, array $extraItems): array
    {
        foreach ($extraItems as $extraItem) {
            if (empty($extraItem['service_id'])) {
                continue;
            }

            $merged = false;

            foreach ($serviceItems as &$serviceItem) {
                if ((int) $serviceItem['service_id'] !== (int) $extraItem['service_id']) {
                    continue;
                }

                $serviceItem['base_quantity'] = max(1, (int) ($serviceItem['base_quantity'] ?? $serviceItem['quantity'] ?? 1))
                    + max(1, (int) ($extraItem['base_quantity'] ?? $extraItem['quantity'] ?? 1));
                $serviceItem['quantity'] = $serviceItem['base_quantity'];
                $serviceItem['used_quantity'] = (int) ($serviceItem['used_quantity'] ?? 0)
                    + (int) ($extraItem['used_quantity'] ?? $extraItem['quantity'] ?? 0);
                $serviceItem['total'] = (float) $serviceItem['total'] + (float) $extraItem['total'];
                foreach (['billing_rule_snapshot', 'nights_snapshot', 'rooms_snapshot', 'people_snapshot'] as $snapshotField) {
                    if (!array_key_exists($snapshotField, $serviceItem) && array_key_exists($snapshotField, $extraItem)) {
                        $serviceItem[$snapshotField] = $extraItem[$snapshotField];
                    }
                }

                $extraNote = trim((string) ($extraItem['note'] ?? ''));
                if ($extraNote !== '') {
                    $serviceItem['note'] = trim((string) ($serviceItem['note'] ?? '')) !== ''
                        ? $serviceItem['note'] . '; ' . $extraNote
                        : $extraNote;
                }

                $merged = true;
                break;
            }
            unset($serviceItem);

            if (!$merged) {
                $serviceItems[] = $extraItem;
            }
        }

        return $serviceItems;
    }

    public function storeUsages(
        Booking $booking,
        Collection $promotions,
        string $channel,
        ?string $note = null,
        ?int $appliedBy = null,
        array $scopeContext = []
    ): float {
        $totalDiscount = 0;

        foreach ($promotions as $promotion) {
            $moneyDiscountAmount = round((float) ($promotion->calculated_money_discount_amount ?? 0), 0);
            $serviceDiscountAmount = round((float) ($promotion->calculated_service_discount_amount ?? 0), 0);
            $roomUpgradeDiscountAmount = round((float) ($promotion->calculated_room_upgrade_discount_amount ?? 0), 0);
            $discountAmount = round($moneyDiscountAmount + $serviceDiscountAmount + $roomUpgradeDiscountAmount, 0);

            if ($discountAmount <= 0) {
                continue;
            }

            $bookingPromotion = BookingPromotion::create([
                'booking_id' => $booking->id,
                'scope' => $scopeContext['scope'] ?? 'booking',
                'booking_room_id' => $scopeContext['booking_room_id'] ?? null,
                'room_issue_request_id' => $scopeContext['room_issue_request_id'] ?? null,
                'promotion_id' => $promotion->id,
                'code_snapshot' => $promotion->code,
                'promotion_type_snapshot' => $promotion->promotion_type,
                'discount_type_snapshot' => $promotion->discount_type,
                'discount_value_snapshot' => $promotion->discount_value,
                'money_discount_amount' => $moneyDiscountAmount,
                'service_discount_amount' => $serviceDiscountAmount,
                'room_upgrade_discount_amount' => $roomUpgradeDiscountAmount,
                'discount_amount' => $discountAmount,
                'applied_by' => $appliedBy,
                'applied_channel' => $channel,
                'note' => $note,
            ]);

            foreach (($promotion->calculated_service_offer_snapshots ?? collect()) as $snapshot) {
                BookingPromotionServiceOffer::create([
                    'booking_id' => $booking->id,
                    'booking_room_id' => $scopeContext['booking_room_id'] ?? null,
                    'room_id_snapshot' => $scopeContext['room_id_snapshot'] ?? null,
                    'booking_promotion_id' => $bookingPromotion->id,
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

            $promotion->increment('used_count');
            $totalDiscount += $discountAmount;
        }

        return $totalDiscount;
    }

    private function emptyResult(float $subtotal): array
    {
        return [
            'ok' => true,
            'promotions' => collect(),
            'discount_total' => 0,
            'money_discount_total' => 0,
            'service_discount_total' => 0,
            'room_upgrade_discount_total' => 0,
            'subtotal_amount' => round(max(0, $subtotal), 0),
            'auto_service_items' => [],
            'messages' => [],
        ];
    }

    private function normalizeServiceItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            if (empty($item['service_id'])) {
                continue;
            }

            $serviceId = (int) $item['service_id'];
            $baseQuantity = max(1, (int) ($item['base_quantity'] ?? $item['quantity'] ?? 1));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $usedQuantity = max(0, (int) ($item['used_quantity'] ?? $item['quantity'] ?? $baseQuantity));
            $total = isset($item['total'])
                ? max(0, (float) $item['total'])
                : $unitPrice * $usedQuantity;

            if (isset($normalized[$serviceId])) {
                $normalized[$serviceId]['base_quantity'] += $baseQuantity;
                $normalized[$serviceId]['quantity'] = $normalized[$serviceId]['base_quantity'];
                $normalized[$serviceId]['used_quantity'] += $usedQuantity;
                $normalized[$serviceId]['total'] += $total;
                continue;
            }

            $normalized[$serviceId] = [
                'service_id' => $serviceId,
                'name' => $item['name'] ?? null,
                'type' => $item['type'] ?? 'service',
                'billing_rule_snapshot' => $item['billing_rule_snapshot'] ?? \App\Models\Service::BILLING_ONCE,
                'unit_price' => $unitPrice,
                'base_quantity' => $baseQuantity,
                'quantity' => $baseQuantity,
                'used_quantity' => $usedQuantity,
                'nights_snapshot' => max(1, (int) ($item['nights_snapshot'] ?? 1)),
                'rooms_snapshot' => max(1, (int) ($item['rooms_snapshot'] ?? 1)),
                'people_snapshot' => max(1, (int) ($item['people_snapshot'] ?? 1)),
                'billing_status' => $item['billing_status'] ?? 'confirmed',
                'total' => $total,
                'note' => $item['note'] ?? null,
            ];
        }

        return array_values($normalized);
    }

    private function calculateServiceOffers(
        Promotion $promotion,
        array $serviceItems,
        bool $allowAutoAdd = true,
        array $context = []
    ): array
    {
        $snapshots = [];
        $autoServiceItems = [];

        foreach ($promotion->serviceOffers as $offer) {
            if (!$offer->service || $offer->service->status !== 'active' || (float) $offer->service->price <= 0) {
                continue;
            }

            $service = $offer->service;
            $offerQuantity = max(1, (int) $offer->quantity);
            $currentQuantity = collect($serviceItems)
                ->where('service_id', $service->id)
                ->sum('used_quantity');

            $applicableQuantity = min($offerQuantity, max(0, (int) $currentQuantity));
            $missingQuantity = max(0, $offerQuantity - $applicableQuantity);

            if ($missingQuantity > 0 && $allowAutoAdd && $offer->auto_add_service) {
                $autoServiceItems[] = $this->makeAutoServiceItem($offer, $missingQuantity, $context);
                $applicableQuantity += $missingQuantity;
            }

            if ($applicableQuantity <= 0) {
                continue;
            }

            $unitPrice = (float) $service->price;
            $originalAmount = round($unitPrice * $applicableQuantity, 0);
            $discountAmount = $this->calculateServiceDiscountAmount($offer, $unitPrice, $applicableQuantity);
            $discountAmount = min($discountAmount, $originalAmount);

            if ($discountAmount <= 0) {
                continue;
            }

            $snapshots[] = [
                'promotion_service_offer_id' => $offer->id,
                'service_id' => $service->id,
                'service_name_snapshot' => $service->name,
                'service_unit_snapshot' => $service->unit,
                'service_price_snapshot' => $unitPrice,
                'discount_type_snapshot' => $offer->discount_type,
                'discount_value_snapshot' => (float) $offer->discount_value,
                'quantity' => $applicableQuantity,
                'original_amount' => $originalAmount,
                'discount_amount' => round($discountAmount, 0),
                'final_amount' => max(0, $originalAmount - round($discountAmount, 0)),
                'note' => $offer->note,
            ];
        }

        return [
            'snapshots' => $snapshots,
            'auto_service_items' => $autoServiceItems,
        ];
    }

    private function makeAutoServiceItem(PromotionServiceOffer $offer, int $quantity, array $context = []): array
    {
        $service = $offer->service;
        $unitPrice = (float) $service->price;
        $snapshot = app(BookingServicePricingService::class)->snapshotForService(
            $service,
            max(1, $quantity),
            $unitPrice,
            max(1, (int) ($context['night_count'] ?? 1)),
            max(1, (int) ($context['room_quantity'] ?? 1)),
            max(1, (int) ($context['guest_count'] ?? 1))
        );

        return [
            'scope' => $context['scope'] ?? 'booking',
            'booking_room_id' => $context['booking_room_id'] ?? null,
            'room_id_snapshot' => $context['room_id_snapshot'] ?? null,
            'source_type' => $context['source_type'] ?? 'promotion',
            'source_id' => $context['source_id'] ?? null,
            'service_id' => $service->id,
            'name' => $service->name,
            'type' => $service->type,
            'billing_rule_snapshot' => $snapshot['billing_rule_snapshot'],
            'unit_price' => $unitPrice,
            'base_quantity' => $snapshot['base_quantity'],
            'quantity' => $snapshot['quantity'],
            'used_quantity' => $snapshot['used_quantity'],
            'nights_snapshot' => $snapshot['nights_snapshot'],
            'rooms_snapshot' => $snapshot['rooms_snapshot'],
            'people_snapshot' => $snapshot['people_snapshot'],
            'billing_status' => 'confirmed',
            'total' => $snapshot['total'],
            'note' => 'Tự thêm từ mã ưu đãi ' . $offer->promotion->code . ($offer->note ? ': ' . $offer->note : ''),
        ];
    }

    private function calculateServiceDiscountAmount(PromotionServiceOffer $offer, float $unitPrice, int $quantity): float
    {
        $discountValue = (float) $offer->discount_value;

        if ($offer->discount_type === Promotion::DISCOUNT_PERCENT) {
            return round(($unitPrice * $quantity) * $discountValue / 100, 0);
        }

        return round($discountValue * $quantity, 0);
    }


    public function checkExistingPromotionEligibility(
        Promotion $promotion,
        array $context,
        string $channel = 'reprice',
        ?string $note = null
    ): array {
        return $this->checkPromotion($promotion, $context, $channel, false, $note);
    }

    private function checkPromotion(
        Promotion $promotion,
        array $context,
        string $channel,
        bool $strict = true,
        ?string $note = null
    ): array {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $identity = $this->resolveCustomerIdentity($context);
        $subtotal = (float) ($context['subtotal_amount'] ?? 0);
        $eligibilitySubtotal = (float) ($context['eligibility_subtotal_amount'] ?? $subtotal);
        $nightCount = (int) ($context['night_count'] ?? 1);
        $roomQuantity = (int) ($context['room_quantity'] ?? 1);
        $eligibilityRoomQuantity = (int) ($context['eligibility_room_quantity'] ?? $roomQuantity);
        $checkInAt = !empty($context['check_in_at'])
            ? Carbon::parse($context['check_in_at'], 'Asia/Ho_Chi_Minh')
            : null;
        $checkOutAt = !empty($context['check_out_at'])
            ? Carbon::parse($context['check_out_at'], 'Asia/Ho_Chi_Minh')
            : null;
        $currentBookingId = !empty($context['booking_id']) ? (int) $context['booking_id'] : null;

        if ($channel === 'user') {
            if (!$promotion->user_can_apply || !$promotion->is_public || $promotion->promotion_type === Promotion::TYPE_SUPPORT) {
                return $this->fail('Mã ' . $promotion->code . ' chỉ dành cho nhân viên khách sạn áp dụng.');
            }
        } elseif ($channel !== 'reprice' && !$promotion->admin_can_apply) {
            return $this->fail('Mã ' . $promotion->code . ' không cho phép admin áp dụng.');
        }

        if ($promotion->valid_from && $now->lessThan($promotion->valid_from)) {
            return $this->fail('Mã ' . $promotion->code . ' chưa đến thời gian sử dụng.');
        }

        if ($promotion->valid_to && $now->greaterThan($promotion->valid_to)) {
            return $this->fail('Mã ' . $promotion->code . ' đã hết hạn.');
        }

        if ($promotion->stay_from && $checkOutAt && $checkOutAt->toDateString() < $promotion->stay_from->toDateString()) {
            return $this->fail('Mã ' . $promotion->code . ' không áp dụng cho thời gian lưu trú đã chọn.');
        }

        if ($promotion->stay_to && $checkInAt && $checkInAt->toDateString() > $promotion->stay_to->toDateString()) {
            return $this->fail('Mã ' . $promotion->code . ' không áp dụng cho thời gian lưu trú đã chọn.');
        }

        if ((float) $promotion->min_booking_amount > 0 && $eligibilitySubtotal < (float) $promotion->min_booking_amount) {
            return $this->fail('Mã ' . $promotion->code . ' chỉ áp dụng cho đơn từ ' . number_format((float) $promotion->min_booking_amount, 0, ',', '.') . 'đ.');
        }

        if ((int) $promotion->min_nights > 0 && $nightCount < (int) $promotion->min_nights) {
            return $this->fail('Mã ' . $promotion->code . ' yêu cầu tối thiểu ' . (int) $promotion->min_nights . ' đêm.');
        }

        if ((int) $promotion->min_rooms > 0 && $eligibilityRoomQuantity < (int) $promotion->min_rooms) {
            return $this->fail('Mã ' . $promotion->code . ' yêu cầu tối thiểu ' . (int) $promotion->min_rooms . ' phòng.');
        }

        if ((int) $promotion->usage_limit > 0) {
            // used_count là số đếm legacy và có thể vẫn chứa booking đã hủy.
            // Giới hạn thực tế phải dựa vào usage đang gắn với booking còn hiệu lực;
            // booking hủy không được giữ mất lượt mã của hệ thống/khách.
            $effectiveUsageQuery = BookingPromotion::query()
                ->where('promotion_id', $promotion->id)
                ->whereHas('booking', fn (Builder $booking) => $booking->where('status', '!=', 'cancelled'));

            if ($currentBookingId) {
                $effectiveUsageQuery->where('booking_id', '!=', $currentBookingId);
            }

            if ($effectiveUsageQuery->count() >= (int) $promotion->usage_limit) {
                return $this->fail('Mã ' . $promotion->code . ' đã hết lượt sử dụng.');
            }
        }

        if ($strict && $promotion->requires_note && trim((string) $note) === '') {
            return $this->fail('Mã ' . $promotion->code . ' cần nhập lý do hỗ trợ.');
        }

        $hasCustomerCondition = (int) $promotion->per_customer_limit > 0
            || (int) $promotion->min_completed_bookings > 0
            || (float) $promotion->min_total_spent > 0;

        if ($hasCustomerCondition && !$identity['has_identity']) {
            return $this->fail('Mã ' . $promotion->code . ' cần CCCD người đứng tên để kiểm tra điều kiện khách hàng.');
        }

        if ($identity['has_identity']) {
            if ((int) $promotion->per_customer_limit > 0) {
                $usedByCustomerQuery = BookingPromotion::where('promotion_id', $promotion->id)
                    ->whereHas('booking', function (Builder $query) use ($identity) {
                        $query->where('status', '!=', 'cancelled');
                        $this->applyBookingIdentityScope($query, $identity);
                    });

                if ($currentBookingId) {
                    $usedByCustomerQuery->where('booking_id', '!=', $currentBookingId);
                }

                $usedByCustomer = $usedByCustomerQuery->count();

                if ($usedByCustomer >= (int) $promotion->per_customer_limit) {
                    return $this->fail('CCCD/khách này đã dùng hết lượt cho mã ' . $promotion->code . '.');
                }
            }

            if ((int) $promotion->min_completed_bookings > 0) {
                $completedCount = Booking::query()
                    ->whereIn('status', ['checked_out', 'completed'])
                    ->where(function (Builder $query) use ($identity) {
                        $this->applyBookingIdentityScope($query, $identity);
                    })
                    ->count();

                if ($completedCount < (int) $promotion->min_completed_bookings) {
                    return $this->fail('Mã ' . $promotion->code . ' yêu cầu khách đã hoàn thành tối thiểu ' . (int) $promotion->min_completed_bookings . ' đơn.');
                }
            }

            if ((float) $promotion->min_total_spent > 0) {
                $totalSpent = Booking::query()
                    ->whereIn('status', ['checked_out', 'completed'])
                    ->where(function (Builder $query) use ($identity) {
                        $this->applyBookingIdentityScope($query, $identity);
                    })
                    ->selectRaw('COALESCE(SUM(COALESCE(final_total, estimated_total, 0)), 0) as total_spent')
                    ->value('total_spent');

                if ((float) $totalSpent < (float) $promotion->min_total_spent) {
                    return $this->fail('Mã ' . $promotion->code . ' yêu cầu khách đã chi tiêu tối thiểu ' . number_format((float) $promotion->min_total_spent, 0, ',', '.') . 'đ.');
                }
            }
        }

        return [
            'ok' => true,
            'message' => null,
        ];
    }


    private function resolveCustomerIdentity(array $context): array
    {
        $customerId = isset($context['customer_id']) ? (int) $context['customer_id'] : null;
        $email = strtolower(trim((string) ($context['customer_email'] ?? '')));
        $phone = preg_replace('/\s+/', '', trim((string) ($context['customer_phone'] ?? '')));
        $cccd = preg_replace('/\D+/', '', trim((string) ($context['customer_cccd'] ?? '')));

        // Nếu caller chỉ truyền customer_id thì lấy CCCD từ hồ sơ. Quyền lợi mã
        // giảm giá luôn gắn với CCCD, không fallback sang email/SĐT.
        if ($cccd === '' && $customerId) {
            $cccd = preg_replace('/\D+/', '', (string) Customer::query()->whereKey($customerId)->value('cccd'));
        }

        if ($cccd !== '') {
            return [
                'mode' => 'cccd',
                'customer_ids' => Customer::query()->where('cccd', $cccd)->pluck('id')->map(fn ($id)=>(int)$id)->unique()->values()->all(),
                'email' => $email,
                'phone' => $phone,
                'cccd' => $cccd,
                'has_identity' => true,
            ];
        }

        return [
            'mode' => 'none', 'customer_ids' => [],
            'email' => $email, 'phone' => $phone, 'cccd' => '',
            'has_identity' => false,
        ];
    }

    private function applyBookingIdentityScope(Builder $query, array $identity): void
    {
        $query->where(function (Builder $identityQuery) use ($identity) {
            if (($identity['mode'] ?? null) !== 'cccd' || empty($identity['cccd'])) {
                $identityQuery->whereRaw('1 = 0');
                return;
            }

            $identityQuery->where('customer_cccd_snapshot', $identity['cccd']);
            if (!empty($identity['customer_ids'])) {
                $identityQuery->orWhereIn('customer_id', $identity['customer_ids']);
            }
        });
    }

    private function calculateDiscountAmount(Promotion $promotion, float $subtotal): float
    {
        $discountValue = (float) $promotion->discount_value;

        if ($discountValue <= 0) {
            return 0;
        }

        if ($promotion->discount_type === Promotion::DISCOUNT_PERCENT) {
            $amount = round($subtotal * $discountValue / 100, 0);

            if ((float) $promotion->max_discount_amount > 0) {
                $amount = min($amount, (float) $promotion->max_discount_amount);
            }

            return max(0, $amount);
        }

        return max(0, round($discountValue, 0));
    }

    private function fail(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
        ];
    }
}
