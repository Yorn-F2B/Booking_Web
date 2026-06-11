<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'room_number',
        'room_category_id',
        'floor_number',
        'status',
        'note',
    ];

    public function category()
    {
        return $this->belongsTo(RoomCategory::class, 'room_category_id');
    }

    public function bookingRooms()
    {
        return $this->hasMany(BookingRoom::class);
    }

    public function inspections()
    {
        return $this->hasMany(RoomInspection::class);
    }
}