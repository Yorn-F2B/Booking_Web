<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    public const TYPE_NORMAL = 'normal_discount';
    public const TYPE_EVENT = 'event_discount';
    public const TYPE_SUPPORT = 'support_discount';
    public const TYPE_CONDITIONAL = 'conditional_discount';

    public const DISCOUNT_PERCENT = 'percent';
    public const DISCOUNT_FIXED = 'fixed_amount';

    protected $fillable = [
        'code',
        'name',
        'description',
        'promotion_type',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'valid_from',
        'valid_to',
        'stay_from',
        'stay_to',
        'min_booking_amount',
        'min_nights',
        'min_rooms',
        'min_completed_bookings',
        'min_total_spent',
        'usage_limit',
        'used_count',
        'per_customer_limit',
        'is_public',
        'user_can_apply',
        'admin_can_apply',
        'requires_note',
        'is_stackable',
        'status',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'stay_from' => 'date',
        'stay_to' => 'date',
        'min_booking_amount' => 'decimal:2',
        'min_total_spent' => 'decimal:2',
        'is_public' => 'boolean',
        'user_can_apply' => 'boolean',
        'admin_can_apply' => 'boolean',
        'requires_note' => 'boolean',
        'is_stackable' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(BookingPromotion::class);
    }

    public function serviceOffers()
    {
        return $this->hasMany(PromotionServiceOffer::class);
    }

    public function roomUpgradeOffers()
    {
        return $this->hasMany(PromotionRoomUpgradeOffer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->promotion_type) {
            self::TYPE_NORMAL => 'Mã thường',
            self::TYPE_EVENT => 'Mã sự kiện',
            self::TYPE_SUPPORT => 'Mã hỗ trợ',
            self::TYPE_CONDITIONAL => 'Mã điều kiện',
            default => 'Mã ưu đãi',
        };
    }

    public function getDiscountLabelAttribute(): string
    {
        $value = (float) $this->discount_value;

        if ($this->discount_type === self::DISCOUNT_PERCENT) {
            $label = rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',') . '%';

            if ((float) $this->max_discount_amount > 0) {
                $label .= ', tối đa ' . number_format((float) $this->max_discount_amount, 0, ',', '.') . 'đ';
            }

            return $label;
        }

        return number_format($value, 0, ',', '.') . 'đ';
    }
}
