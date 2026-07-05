<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPromotionRoomUpgrade extends Model
{
    protected $fillable = [
        'booking_id',
        'booking_promotion_id',
        'promotion_id',
        'promotion_room_upgrade_offer_id',
        'booking_room_id',
        'old_room_id',
        'new_room_id',
        'old_room_category_id',
        'old_room_category_name_snapshot',
        'old_room_price_snapshot',
        'new_room_category_id',
        'new_room_category_name_snapshot',
        'new_room_price_snapshot',
        'night_count',
        'room_quantity',
        'original_difference_amount',
        'covered_amount',
        'guest_extra_amount',
        'upgrade_kind_snapshot',
        'cover_type_snapshot',
        'cover_value_snapshot',
        'reason',
        'note',
    ];

    protected $casts = [
        'old_room_price_snapshot' => 'decimal:2',
        'new_room_price_snapshot' => 'decimal:2',
        'night_count' => 'integer',
        'room_quantity' => 'integer',
        'original_difference_amount' => 'decimal:2',
        'covered_amount' => 'decimal:2',
        'guest_extra_amount' => 'decimal:2',
        'cover_value_snapshot' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingPromotion()
    {
        return $this->belongsTo(BookingPromotion::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function offer()
    {
        return $this->belongsTo(PromotionRoomUpgradeOffer::class, 'promotion_room_upgrade_offer_id');
    }
}
