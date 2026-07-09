<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionRoomUpgradeOffer extends Model
{
    public const KIND_INCIDENT_SUPPORT = 'incident_support';
    public const KIND_PAID_UPSELL = 'paid_upsell';

    public const COVER_FULL_DIFFERENCE = 'full_difference';
    public const COVER_PERCENT_DIFFERENCE = 'percent_difference';
    public const COVER_FIXED_AMOUNT = 'fixed_amount';

    protected $fillable = [
        'promotion_id',
        'upgrade_kind',
        'from_room_category_id',
        'to_room_category_id',
        'cover_type',
        'cover_value',
        'max_cover_amount',
        'requires_hotel_fault_reason',
        'guest_must_pay_extra',
        'auto_apply_on_upgrade',
        'note',
    ];

    protected $casts = [
        'cover_value' => 'decimal:2',
        'max_cover_amount' => 'decimal:2',
        'requires_hotel_fault_reason' => 'boolean',
        'guest_must_pay_extra' => 'boolean',
        'auto_apply_on_upgrade' => 'boolean',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function fromCategory()
    {
        return $this->belongsTo(RoomCategory::class, 'from_room_category_id');
    }

    public function toCategory()
    {
        return $this->belongsTo(RoomCategory::class, 'to_room_category_id');
    }

    public function bookingSnapshots()
    {
        return $this->hasMany(BookingPromotionRoomUpgrade::class, 'promotion_room_upgrade_offer_id');
    }

    public function getKindLabelAttribute(): string
    {
        return match ($this->upgrade_kind) {
            self::KIND_INCIDENT_SUPPORT => 'Hỗ trợ do sự cố',
            self::KIND_PAID_UPSELL => 'Upsell hạng cao',
            default => 'Nâng hạng phòng',
        };
    }

    public function getCoverLabelAttribute(): string
    {
        return match ($this->cover_type) {
            self::COVER_FULL_DIFFERENCE => 'Khách sạn chịu toàn bộ tiền chênh',
            self::COVER_PERCENT_DIFFERENCE => 'Giảm ' . rtrim(rtrim(number_format((float) $this->cover_value, 2, ',', '.'), '0'), ',') . '% tiền chênh',
            self::COVER_FIXED_AMOUNT => 'Giảm ' . number_format((float) $this->cover_value, 0, ',', '.') . 'đ tiền chênh',
            default => 'Ưu đãi nâng hạng',
        };
    }
}
