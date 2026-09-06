<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRoom extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'room_id',
        'adult_count',
        'child_count',
        'price_at_booking',
        'surcharge',
        'surcharge_reason',
        'created_at',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function serviceItems()
    {
        return $this->hasMany(BookingServiceItem::class);
    }

    public function promotions()
    {
        return $this->hasMany(BookingPromotion::class);
    }
}