<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'room_number',
        'room_category_id',
        'floor_number',
        'status',
        'note',
    ];

    public function category()
    {
        return $this->belongsTo(RoomCategory::class, 'room_category_id');
    }

    public function bookingRooms()
    {
        return $this->hasMany(BookingRoom::class);
    }

    public function inspections()
    {
        return $this->hasMany(RoomInspection::class);
    }


    public function roomAssignments()
    {
        return $this->hasMany(StaffRoomAssignment::class);
    }

    public function scopeAvailableForPeriod(
        Builder $query,
        $checkInAt,
        $checkOutAt,
        ?int $excludeBookingId = null,
        int $cleaningBufferMinutes = 60
    ): Builder {
        $checkInAt = Carbon::parse($checkInAt, 'Asia/Ho_Chi_Minh');
        $checkOutAt = Carbon::parse($checkOutAt, 'Asia/Ho_Chi_Minh');

        $checkInAtString = $checkInAt->toDateTimeString();
        $checkOutWithBufferAtString = $checkOutAt
            ->copy()
            ->addMinutes($cleaningBufferMinutes)
            ->toDateTimeString();

        return $query
            ->whereNotIn('status', ['maintenance', 'cleaning', 'inspection'])
            ->whereDoesntHave('bookingRooms.booking', function (Builder $bookingQuery) use (
                $checkInAtString,
                $checkOutWithBufferAtString,
                $excludeBookingId
            ) {
                if ($excludeBookingId) {
                    $bookingQuery->where('bookings.id', '!=', $excludeBookingId);
                }

                $bookingQuery
                    ->whereIn('status', [
                        'pending',
                        'confirmed',
                        'checked_in',
                        'inspection_requested',
                    ])
                    ->where('check_in_at', '<', $checkOutWithBufferAtString)
                    ->whereRaw(
                        'DATE_ADD(check_out_at, INTERVAL COALESCE(cleaning_buffer_minutes, 60) MINUTE) > ?',
                        [$checkInAtString]
                    );
            });
    }
}
