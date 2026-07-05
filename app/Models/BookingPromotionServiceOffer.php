<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPromotionServiceOffer extends Model
{
    protected $fillable = [
        'booking_id',
        'booking_promotion_id',
        'promotion_id',
        'promotion_service_offer_id',
        'service_id',
        'code_snapshot',
        'service_name_snapshot',
        'service_unit_snapshot',
        'service_price_snapshot',
        'discount_type_snapshot',
        'discount_value_snapshot',
        'quantity',
        'original_amount',
        'discount_amount',
        'final_amount',
        'note',
    ];

    protected $casts = [
        'service_price_snapshot' => 'decimal:2',
        'discount_value_snapshot' => 'decimal:2',
        'quantity' => 'integer',
        'original_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
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

    public function promotionServiceOffer()
    {
        return $this->belongsTo(PromotionServiceOffer::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
