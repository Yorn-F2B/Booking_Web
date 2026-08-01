<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionServiceOffer extends Model
{
    protected $fillable = [
        'promotion_id',
        'service_id',
        'discount_type',
        'discount_value',
        'quantity',
        'auto_add_service',
        'note',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'quantity' => 'integer',
        'auto_add_service' => 'boolean',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function bookingSnapshots()
    {
        return $this->hasMany(BookingPromotionServiceOffer::class);
    }

    public function getOfferLabelAttribute(): string
    {
        $serviceName = $this->service->name ?? 'Dịch vụ';
        $quantity = max(1, (int) $this->quantity);

        if ($this->discount_type === Promotion::DISCOUNT_PERCENT) {
            $value = rtrim(rtrim(number_format((float) $this->discount_value, 2, ',', '.'), '0'), ',') . '%';
        } else {
            $value = number_format((float) $this->discount_value, 0, ',', '.') . 'đ';
        }

        return $serviceName . ' x' . $quantity . ' giảm ' . $value;
    }
}
