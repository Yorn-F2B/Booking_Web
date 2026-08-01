<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'cccd',
        'email',
        'birthday',
        'gender',
        'address',
        'status',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }


    public function hotelReviews()
    {
        return $this->hasMany(HotelReview::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->last_name ?? '') . ' ' . ($this->first_name ?? ''));
    }
}
