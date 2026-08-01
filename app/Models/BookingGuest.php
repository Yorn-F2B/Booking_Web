<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingGuest extends Model
{
    public const TYPE_ADULT = 'adult';
    public const TYPE_CHILD = 'child';
    public const TYPE_INFANT = 'infant';

    protected $fillable = [
        'booking_id',
        'booking_room_id',
        'full_name',
        'guest_type',
        'document_type',
        'document_number',
        'cccd',
        'birthday',
        'gender',
        'nationality',
        'address',
        'is_booking_representative',
        'guardian_guest_id',
        'guardian_relationship',
        'planned_check_in_at',
        'planned_check_out_at',
        'actual_check_in_at',
        'actual_check_out_at',
        'status',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'birthday' => 'date',
        'planned_check_in_at' => 'datetime',
        'planned_check_out_at' => 'datetime',
        'actual_check_in_at' => 'datetime',
        'actual_check_out_at' => 'datetime',
        'is_booking_representative' => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingRoom()
    {
        return $this->belongsTo(BookingRoom::class);
    }

    public function guardian()
    {
        return $this->belongsTo(self::class, 'guardian_guest_id');
    }

    public function dependants()
    {
        return $this->hasMany(self::class, 'guardian_guest_id');
    }

    public function roomHistories()
    {
        return $this->hasMany(BookingGuestRoomHistory::class);
    }

    public function getDisplayDocumentAttribute(): ?string
    {
        return $this->document_number ?: $this->cccd;
    }
}
