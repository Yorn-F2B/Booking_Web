<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRoom extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'room_id',
        'adult_count',
        'child_count',
        'price_at_booking',
        'surcharge',
        'surcharge_reason',
        'support_amount',
        'support_reason',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::created(function (self $room) {
            // All new room rows receive an immutable initial price period. Legacy rows are
            // backfilled by the migration, and the calculator retains a fallback for rollout safety.
            $booking = $room->booking;
            if (!$booking || !$booking->check_in_at || !$booking->check_out_at) return;
            if ($booking->check_out_at->toDateString() <= $booking->check_in_at->toDateString()) return;
            $room->periods()->create([
                'room_id' => $room->room_id,
                'start_date' => $booking->check_in_at->toDateString(),
                'end_date' => $booking->check_out_at->toDateString(),
                'price_per_night' => $room->price_at_booking,
            ]);
        });
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function serviceItems()
    {
        return $this->hasMany(BookingServiceItem::class);
    }

    public function promotions()
    {
        return $this->hasMany(BookingPromotion::class);
    }

    public function periods()
    {
        return $this->hasMany(BookingRoomPeriod::class)->orderBy('start_date');
    }
}
