<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingCancellationRequest extends Model
{
    protected $fillable = [
        'booking_id',
        'customer_id',
        'requested_by',
        'status',
        'reason',
        'policy_snapshot',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'policy_snapshot' => 'array',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
