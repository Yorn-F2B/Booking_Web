<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_code',
        'customer_id',
        'created_by',
        'room_category_id',
        'check_in_date',
        'check_out_date',
        'actual_check_in',
        'actual_check_out',
        'adult_count',
        'child_count',
        'room_quantity',
        'prefer_adjacent_rooms',
        'estimated_total',
        'deposit_amount',
        'payment_status',
        'status',
        'note',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function roomCategory()
    {
        return $this->belongsTo(RoomCategory::class);
    }

    public function bookingRooms()
    {
        return $this->hasMany(BookingRoom::class);
    }

    public function roomInspections()
    {
        return $this->hasMany(RoomInspection::class);
    }
}