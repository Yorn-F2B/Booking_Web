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

class PromotionService
{
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

    public function validateCodes(array $codes, array $context, string $channel = 'user', ?string $note = null): array
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

            $check = $this->checkPromotion($promotion, $context, $channel, true, $note);

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

        if ($validPromotions->count() > 1 && $nonStackable->isNotEmpty()) {
            return [
                'ok' => false,
                'promotions' => collect(),
                'discount_total' => 0,
                'money_discount_total' => 0,
                'service_discount_total' => 0,
                'room_upgrade_discount_total' => 0,
                'subtotal_amount' => (float) ($context['subtotal_amount'] ?? 0),
                'auto_service_items' => [],
                'messages' => ['Có mã không cho dùng chung với mã khác. Vui lòng chỉ chọn mã đó hoặc bỏ mã đó ra.'],
            ];
        }

        $baseSubtotal = max(0, (float) ($context['subtotal_amount'] ?? 0));
        $serviceItems = $this->normalizeServiceItems($context['service_items'] ?? []);
        $autoServiceItems = [];
        $serviceDiscountTotal = 0;

        foreach ($validPromotions as $promotion) {
            $serviceOfferResult = $this->calculateServiceOffers($promotion, $serviceItems);

            foreach ($serviceOfferResult['auto_service_items'] as $autoItem) {
                $autoServiceItems[] = $autoItem;
                $serviceItems = $this->mergeServiceItems($serviceItems, [$autoItem]);
                $baseSubtotal += (float) $autoItem['total'];
            }
        }

        $remainingForServiceDiscount = $baseSubtotal;

        foreach ($validPromotions as $promotion) {
            $serviceOfferResult = $this->calculateServiceOffers($promotion, $serviceItems, false);
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
        ?int $appliedBy = null
    ): BookingPromotion {
        $coveredAmount = round((float) ($snapshot['covered_amount'] ?? 0), 0);

        $bookingPromotion = BookingPromotion::create([
            'booking_id' => $booking->id,
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

                $serviceItem['quantity'] = (int) $serviceItem['quantity'] + (int) $extraItem['quantity'];
                $serviceItem['used_quantity'] = (int) $serviceItem['used_quantity'] + (int) $extraItem['used_quantity'];
                $serviceItem['total'] = (float) $serviceItem['total'] + (float) $extraItem['total'];

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
        ?int $appliedBy = null
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
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $usedQuantity = max(0, (int) ($item['used_quantity'] ?? $quantity));
            $total = isset($item['total'])
                ? max(0, (float) $item['total'])
                : $unitPrice * $usedQuantity;

            $normalized[$serviceId] = [
                'service_id' => $serviceId,
                'name' => $item['name'] ?? null,
                'type' => $item['type'] ?? 'service',
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'used_quantity' => $usedQuantity,
                'billing_status' => $item['billing_status'] ?? 'confirmed',
                'total' => $total,
                'note' => $item['note'] ?? null,
            ];
        }

        return array_values($normalized);
    }

    private function calculateServiceOffers(Promotion $promotion, array $serviceItems, bool $allowAutoAdd = true): array
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
                ->sum('quantity');

            $applicableQuantity = min($offerQuantity, max(0, (int) $currentQuantity));
            $missingQuantity = max(0, $offerQuantity - $applicableQuantity);

            if ($missingQuantity > 0 && $allowAutoAdd && $offer->auto_add_service) {
                $autoServiceItems[] = $this->makeAutoServiceItem($offer, $missingQuantity);
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

    private function makeAutoServiceItem(PromotionServiceOffer $offer, int $quantity): array
    {
        $service = $offer->service;
        $unitPrice = (float) $service->price;
        $originalTotal = $unitPrice * $quantity;
        $discountAmount = $this->calculateServiceDiscountAmount($offer, $unitPrice, $quantity);
        $discountAmount = min($discountAmount, $originalTotal);
        $total = max(0, $originalTotal - $discountAmount);

        return [
            'service_id' => $service->id,
            'name' => $service->name,
            'type' => $service->type,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'used_quantity' => $quantity,
            'billing_status' => 'confirmed',
            'total' => $total,
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

    private function checkPromotion(
        Promotion $promotion,
        array $context,
        string $channel,
        bool $strict = true,
        ?string $note = null
    ): array {
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $customerId = $context['customer_id'] ?? null;
        $subtotal = (float) ($context['subtotal_amount'] ?? 0);
        $nightCount = (int) ($context['night_count'] ?? 1);
        $roomQuantity = (int) ($context['room_quantity'] ?? 1);
        $checkInAt = !empty($context['check_in_at'])
            ? Carbon::parse($context['check_in_at'], 'Asia/Ho_Chi_Minh')
            : null;
        $checkOutAt = !empty($context['check_out_at'])
            ? Carbon::parse($context['check_out_at'], 'Asia/Ho_Chi_Minh')
            : null;

        if ($channel === 'user') {
            if (!$promotion->user_can_apply || !$promotion->is_public || $promotion->promotion_type === Promotion::TYPE_SUPPORT) {
                return $this->fail('Mã ' . $promotion->code . ' chỉ dành cho nhân viên khách sạn áp dụng.');
            }
        } elseif (!$promotion->admin_can_apply) {
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

        if ((float) $promotion->min_booking_amount > 0 && $subtotal < (float) $promotion->min_booking_amount) {
            return $this->fail('Mã ' . $promotion->code . ' chỉ áp dụng cho đơn từ ' . number_format((float) $promotion->min_booking_amount, 0, ',', '.') . 'đ.');
        }

        if ((int) $promotion->min_nights > 0 && $nightCount < (int) $promotion->min_nights) {
            return $this->fail('Mã ' . $promotion->code . ' yêu cầu tối thiểu ' . (int) $promotion->min_nights . ' đêm.');
        }

        if ((int) $promotion->min_rooms > 0 && $roomQuantity < (int) $promotion->min_rooms) {
            return $this->fail('Mã ' . $promotion->code . ' yêu cầu tối thiểu ' . (int) $promotion->min_rooms . ' phòng.');
        }

        if ((int) $promotion->usage_limit > 0 && (int) $promotion->used_count >= (int) $promotion->usage_limit) {
            return $this->fail('Mã ' . $promotion->code . ' đã hết lượt sử dụng.');
        }

        if ($strict && $promotion->requires_note && trim((string) $note) === '') {
            return $this->fail('Mã ' . $promotion->code . ' cần nhập lý do hỗ trợ.');
        }

        if ($customerId) {
            if ((int) $promotion->per_customer_limit > 0) {
                $usedByCustomer = BookingPromotion::where('promotion_id', $promotion->id)
                    ->whereHas('booking', function ($query) use ($customerId) {
                        $query->where('customer_id', $customerId);
                    })
                    ->count();

                if ($usedByCustomer >= (int) $promotion->per_customer_limit) {
                    return $this->fail('Khách này đã dùng hết lượt cho mã ' . $promotion->code . '.');
                }
            }

            if ((int) $promotion->min_completed_bookings > 0) {
                $completedCount = Booking::where('customer_id', $customerId)
                    ->whereIn('status', ['checked_out', 'completed'])
                    ->count();

                if ($completedCount < (int) $promotion->min_completed_bookings) {
                    return $this->fail('Mã ' . $promotion->code . ' yêu cầu khách đã hoàn thành tối thiểu ' . (int) $promotion->min_completed_bookings . ' đơn.');
                }
            }

            if ((float) $promotion->min_total_spent > 0) {
                $totalSpent = Booking::where('customer_id', $customerId)
                    ->whereIn('status', ['checked_out', 'completed'])
                    ->sum('estimated_total');

                if ((float) $totalSpent < (float) $promotion->min_total_spent) {
                    return $this->fail('Mã ' . $promotion->code . ' yêu cầu khách đã chi tiêu tối thiểu ' . number_format((float) $promotion->min_total_spent, 0, ',', '.') . 'đ.');
                }
            }
        } elseif (
            $strict
            && (
                (int) $promotion->per_customer_limit > 0
                || (int) $promotion->min_completed_bookings > 0
                || (float) $promotion->min_total_spent > 0
            )
        ) {
            return $this->fail('Mã ' . $promotion->code . ' cần có thông tin khách hàng để kiểm tra điều kiện.');
        }

        return [
            'ok' => true,
            'message' => null,
        ];
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
