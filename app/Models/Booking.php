<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    public const STANDARD_CHECK_OUT_TIME = '12:00:00';
    public const PRIORITY_CLEANING_START_TIME = '12:00:00';
    public const EARLY_CHECK_IN_TIME = '13:00:00';
    public const STANDARD_CHECK_IN_TIME = '14:00:00';
    public const DEFAULT_CLEANING_BUFFER_MINUTES = 60;

    protected $fillable = [
        'booking_code',
        'customer_id',
        'created_by',
        'room_category_id',
        'booking_type',
        'booking_mode',
        'booking_source',
        'check_in_date',
        'check_out_date',
        'check_in_at',
        'check_out_at',
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
        'late_arrival_fee',
        'late_arrival_hours',
        'late_arrival_policy',
        'cleaning_buffer_minutes',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
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

    public function serviceItems()
    {
        return $this->hasMany(BookingServiceItem::class);
    }

    public function logs()
    {
        return $this->hasMany(BookingLog::class)->latest();
    }
}