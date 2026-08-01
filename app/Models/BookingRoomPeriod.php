<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRoomPeriod extends Model
{
    protected $fillable = ['booking_room_id', 'room_id', 'start_date', 'end_date', 'price_per_night'];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'price_per_night' => 'decimal:2'];

    public function bookingRoom() { return $this->belongsTo(BookingRoom::class); }
    public function room() { return $this->belongsTo(Room::class); }
}
