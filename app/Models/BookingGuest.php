<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingGuest extends Model
{
    protected $fillable = [
        'booking_id',
        'booking_room_id',
        'full_name',
        'cccd',
        'birthday',
        'gender',
        'nationality',
        'note',
    ];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingRoom()
    {
        return $this->belongsTo(BookingRoom::class);
    }
}
