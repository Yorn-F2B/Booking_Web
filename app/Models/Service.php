<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public const TYPE_SERVICE = 'service';
    public const TYPE_MINIBAR = 'minibar';
    public const TYPE_DAMAGE_FEE = 'damage_fee';
    public const TYPE_OCCUPANCY_FEE = 'occupancy_fee';
    public const TYPE_POLICY_VIOLATION_FEE = 'policy_violation_fee';

    public const GROUP_GENERAL = 'general';
    public const GROUP_FOOD_DRINK = 'food_drink';
    public const GROUP_VEHICLE = 'vehicle';
    public const GROUP_LAUNDRY = 'laundry';
    public const GROUP_TRANSPORT = 'transport';
    public const GROUP_WELLNESS = 'wellness';
    public const GROUP_ROOM_SUPPORT = 'room_support';
    public const GROUP_OTHER = 'other';

    protected $fillable = [
        'name',
        'type',
        'service_group',
        'price',
        'unit',
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
            self::TYPE_MINIBAR => 'Minibar',
            self::TYPE_DAMAGE_FEE => 'Phí hư hại',
            self::TYPE_OCCUPANCY_FEE => 'Phụ thu số người',
            self::TYPE_POLICY_VIOLATION_FEE => 'Phí vi phạm/chính sách',
        ];
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

    public function getGroupLabelAttribute(): string
    {
        return self::groupLabels()[$this->service_group] ?? 'Chưa phân nhóm';
    }

    public function getIsVehicleServiceAttribute(): bool
    {
        return $this->service_group === self::GROUP_VEHICLE;
    }
}
