<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingServiceItem extends Model
{
    protected $fillable = [
        'booking_id',
        'scope',
        'booking_room_id',
        'room_id_snapshot',
        'source_type',
        'source_id',
        'service_id',
        'name',
        'type',
        'billing_rule_snapshot',
        'unit_price',
        'base_quantity',
        'nights_snapshot',
        'rooms_snapshot',
        'people_snapshot',
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
        'base_quantity' => 'integer',
        'nights_snapshot' => 'integer',
        'rooms_snapshot' => 'integer',
        'people_snapshot' => 'integer',
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

    public function bookingRoom()
    {
        return $this->belongsTo(BookingRoom::class);
    }

    public function roomSnapshot()
    {
        return $this->belongsTo(Room::class, 'room_id_snapshot');
    }
}