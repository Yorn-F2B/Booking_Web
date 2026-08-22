<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public const TYPE_SERVICE = 'service';
    public const TYPE_MINIBAR = 'minibar';
    public const TYPE_MINIBAR_ORDER = 'minibar_order';
    public const TYPE_DAMAGE_FEE = 'damage_fee';
    public const TYPE_OCCUPANCY_FEE = 'occupancy_fee';
    public const TYPE_POLICY_VIOLATION_FEE = 'policy_violation_fee';
    public const TYPE_EARLY_CHECKIN_FEE = 'early_checkin_fee';
    public const TYPE_LATE_CHECKOUT_FEE = 'late_checkout_fee';
    public const TYPE_EXTENSION_FEE = 'extension_fee';
    public const TYPE_EXTRA_GUEST_FEE = 'extra_guest_fee';
    public const TYPE_MANUAL_FEE = 'manual_fee';

    public const GROUP_GENERAL = 'general';
    public const GROUP_FOOD_DRINK = 'food_drink';
    public const GROUP_VEHICLE = 'vehicle';
    public const GROUP_LAUNDRY = 'laundry';
    public const GROUP_TRANSPORT = 'transport';
    public const GROUP_WELLNESS = 'wellness';
    public const GROUP_ROOM_SUPPORT = 'room_support';
    public const GROUP_OTHER = 'other';

    public const BILLING_ONCE = 'once';
    public const BILLING_PER_NIGHT = 'per_night';
    public const BILLING_PER_ROOM = 'per_room';
    public const BILLING_PER_ROOM_PER_NIGHT = 'per_room_per_night';
    public const BILLING_PER_GUEST = 'per_guest';
    public const BILLING_PER_GUEST_PER_NIGHT = 'per_guest_per_night';

    protected $fillable = [
        'name',
        'type',
        'service_group',
        'price',
        'unit',
        'billing_rule',
        'description',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public static function typeLabels(): array
    {
        return [
            self::TYPE_SERVICE => 'Dịch vụ',
            self::TYPE_MINIBAR => 'Minibar có sẵn trong phòng',
            self::TYPE_MINIBAR_ORDER => 'Minibar gọi thêm',
            self::TYPE_DAMAGE_FEE => 'Phí hư hại',
            self::TYPE_OCCUPANCY_FEE => 'Phụ thu số người',
            self::TYPE_POLICY_VIOLATION_FEE => 'Phí vi phạm/chính sách',
            self::TYPE_EARLY_CHECKIN_FEE => 'Phụ thu check-in sớm',
            self::TYPE_LATE_CHECKOUT_FEE => 'Phụ thu check-out muộn',
            self::TYPE_EXTENSION_FEE => 'Phụ thu gia hạn',
            self::TYPE_EXTRA_GUEST_FEE => 'Phụ thu vượt sức chứa',
            self::TYPE_MANUAL_FEE => 'Phí phát sinh thủ công',
        ];
    }


    public static function serviceCatalogTypes(): array
    {
        return [
            self::TYPE_SERVICE,
            self::TYPE_MINIBAR,
            self::TYPE_MINIBAR_ORDER,
        ];
    }

    public static function surchargeCatalogTypes(): array
    {
        return [
            self::TYPE_DAMAGE_FEE,
            self::TYPE_OCCUPANCY_FEE,
            self::TYPE_POLICY_VIOLATION_FEE,
            self::TYPE_EARLY_CHECKIN_FEE,
            self::TYPE_LATE_CHECKOUT_FEE,
            self::TYPE_EXTENSION_FEE,
            self::TYPE_EXTRA_GUEST_FEE,
            self::TYPE_MANUAL_FEE,
        ];
    }

    public static function serviceTypeLabels(): array
    {
        return array_intersect_key(self::typeLabels(), array_flip(self::serviceCatalogTypes()));
    }

    public static function surchargeTypeLabels(): array
    {
        return array_intersect_key(self::typeLabels(), array_flip(self::surchargeCatalogTypes()));
    }

    public function isServiceCatalog(): bool
    {
        return in_array($this->type, self::serviceCatalogTypes(), true);
    }

    public function isSurchargeCatalog(): bool
    {
        return in_array($this->type, self::surchargeCatalogTypes(), true);
    }

    public static function billingRuleLabels(): array
    {
        return [
            self::BILLING_ONCE => 'Một lần / theo số lượng nhập',
            self::BILLING_PER_NIGHT => 'Theo mỗi đêm',
            self::BILLING_PER_ROOM => 'Theo mỗi phòng',
            self::BILLING_PER_ROOM_PER_NIGHT => 'Theo mỗi phòng mỗi đêm',
            self::BILLING_PER_GUEST => 'Theo mỗi khách',
            self::BILLING_PER_GUEST_PER_NIGHT => 'Theo mỗi khách mỗi đêm',
        ];
    }

    public static function normalizeBillingRule(?string $rule): string
    {
        $rule = trim((string) $rule);

        return array_key_exists($rule, self::billingRuleLabels())
            ? $rule
            : self::BILLING_ONCE;
    }

    public static function groupLabels(): array
    {
        return [
            self::GROUP_GENERAL => 'Dịch vụ chung',
            self::GROUP_FOOD_DRINK => 'Ăn uống',
            self::GROUP_VEHICLE => 'Xe cộ / gửi xe',
            self::GROUP_LAUNDRY => 'Giặt là',
            self::GROUP_TRANSPORT => 'Đưa đón / di chuyển',
            self::GROUP_WELLNESS => 'Spa / chăm sóc',
            self::GROUP_ROOM_SUPPORT => 'Hỗ trợ phòng',
            self::GROUP_OTHER => 'Khác',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type ?? '---';
    }

    public function getBillingRuleLabelAttribute(): string
    {
        return self::billingRuleLabels()[self::normalizeBillingRule($this->billing_rule)]
            ?? self::billingRuleLabels()[self::BILLING_ONCE];
    }

    public function getGroupLabelAttribute(): string
    {
        return self::groupLabels()[$this->service_group] ?? 'Chưa phân nhóm';
    }

    public function getIsVehicleServiceAttribute(): bool
    {
        return $this->service_group === self::GROUP_VEHICLE;
    }
}
