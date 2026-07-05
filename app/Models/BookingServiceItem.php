<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingServiceItem extends Model
{
    protected $fillable = [
        'booking_id',
        'service_id',
        'name',
        'type',
        'unit_price',
        'quantity',
        'used_quantity',
        'billing_status',
        'confirmed_by',
        'confirmed_at',
        'confirm_note',
        'total',
        'note',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity' => 'integer',
        'used_quantity' => 'integer',
        'confirmed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}