<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomInspection extends Model
{
    protected $fillable = [
        'booking_id',
        'room_id',
        'inspected_by',
        'confirmed_by',
        'status',
        'has_damage',
        'damage_items',
        'damage_total',
        'inspection_note',
        'admin_note',
        'inspected_at',
        'confirmed_at',
    ];

    protected $casts = [
        'has_damage' => 'boolean',
        'damage_items' => 'array',
        'damage_total' => 'decimal:2',
        'inspected_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function items()
    {
        return $this->hasMany(RoomInspectionItem::class);
    }
}