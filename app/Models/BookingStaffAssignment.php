<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingStaffAssignment extends Model
{
    protected $fillable = [
        'booking_id',
        'staff_id',
        'role_in_booking',
        'assigned_by',
        'status',
        'note',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
