<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingGuestRoomHistory extends Model
{
    protected $fillable = [
        'booking_guest_id',
        'from_booking_room_id',
        'to_booking_room_id',
        'started_at',
        'ended_at',
        'reason',
        'changed_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function guest()
    {
        return $this->belongsTo(BookingGuest::class, 'booking_guest_id');
    }

    public function fromBookingRoom()
    {
        return $this->belongsTo(BookingRoom::class, 'from_booking_room_id');
    }

    public function toBookingRoom()
    {
        return $this->belongsTo(BookingRoom::class, 'to_booking_room_id');
    }
}
