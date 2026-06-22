<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPromotion extends Model
{
    protected $fillable = [
        'booking_id',
        'promotion_id',
        'code_snapshot',
        'promotion_type_snapshot',
        'discount_type_snapshot',
        'discount_value_snapshot',
        'money_discount_amount',
        'service_discount_amount',
        'discount_amount',
        'applied_by',
        'applied_channel',
        'note',
    ];

    protected $casts = [
        'discount_value_snapshot' => 'decimal:2',
        'money_discount_amount' => 'decimal:2',
        'service_discount_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function serviceOffers()
    {
        return $this->hasMany(BookingPromotionServiceOffer::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->promotion_type_snapshot) {
            Promotion::TYPE_NORMAL => 'Mã thường',
            Promotion::TYPE_EVENT => 'Mã sự kiện',
            Promotion::TYPE_SUPPORT => 'Mã hỗ trợ',
            Promotion::TYPE_CONDITIONAL => 'Mã điều kiện',
            default => 'Mã ưu đãi',
        };
    }
}
