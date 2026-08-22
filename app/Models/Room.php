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
        'status_from',
        'status_until',
        'note',
    ];

    protected $casts = [
        'status_from'  => 'datetime',
        'status_until' => 'datetime',
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

    public function actionLogs()
    {
        return $this->hasMany(RoomActionLog::class);
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
        int $cleaningBufferMinutes = 0
    ): Builder {
        $checkInAt = Carbon::parse($checkInAt, 'Asia/Ho_Chi_Minh');
        $checkOutAt = Carbon::parse($checkOutAt, 'Asia/Ho_Chi_Minh');

        $checkInAtString = $checkInAt->toDateTimeString();
        $checkOutWithBufferAtString = $checkOutAt
            ->copy()
            ->addMinutes($cleaningBufferMinutes)
            ->toDateTimeString();

        return $query
            ->where(function (Builder $statusQuery) use ($checkInAtString) {
                $statusQuery->whereIn('status', ['available', 'reserved', 'occupied'])
                    ->orWhere(function (Builder $temporaryStatus) use ($checkInAtString) {
                        $temporaryStatus->whereIn('status', ['maintenance', 'cleaning', 'inspection'])
                            ->whereNotNull('status_until')
                            ->where('status_until', '<=', $checkInAtString);
                    });
            })
            ->whereNotExists(function ($holdQuery) use ($excludeBookingId) {
                $holdQuery->selectRaw('1')
                    ->from('room_issue_room_holds')
                    ->whereColumn('room_issue_room_holds.room_id', 'rooms.id')
                    ->whereNull('room_issue_room_holds.released_at')
                    ->where('room_issue_room_holds.expires_at', '>', now('Asia/Ho_Chi_Minh'));
            })
            ->whereDoesntHave('bookingRooms.booking', function (Builder $bookingQuery) use (
                $checkInAtString,
                $checkOutWithBufferAtString,
                $excludeBookingId
            ) {
                if ($excludeBookingId) {
                    $bookingQuery->where('bookings.id', '!=', $excludeBookingId);
                }

                $bookingQuery
                    ->activeForOperations()
                    ->where('check_in_at', '<', $checkOutWithBufferAtString)
                    ->whereRaw(
                        'DATE_ADD(check_out_at, INTERVAL COALESCE(cleaning_buffer_minutes, 0) MINUTE) > ?',
                        [$checkInAtString]
                    );
            });
    }
    public function scopeBookableForPeriod(
        Builder $query,
        $checkInAt,
        $checkOutAt,
        ?int $excludeBookingId = null
    ): Builder {
        $checkInAt = Carbon::parse($checkInAt, 'Asia/Ho_Chi_Minh');
        $checkOutAt = Carbon::parse($checkOutAt, 'Asia/Ho_Chi_Minh');

        $checkInAtString = $checkInAt->toDateTimeString();
        $checkOutAtString = $checkOutAt->toDateTimeString();

        return $query
            ->whereIn('status', [
                'available',
                'reserved',
                'occupied',
                'cleaning',
                'inspection',
            ])
            ->whereNotExists(function ($holdQuery) use ($excludeBookingId) {
                $holdQuery->selectRaw('1')
                    ->from('room_issue_room_holds')
                    ->whereColumn('room_issue_room_holds.room_id', 'rooms.id')
                    ->whereNull('room_issue_room_holds.released_at')
                    ->where('room_issue_room_holds.expires_at', '>', now('Asia/Ho_Chi_Minh'));
            })
            ->whereDoesntHave('bookingRooms.booking', function (Builder $bookingQuery) use (
                $checkInAtString,
                $checkOutAtString,
                $excludeBookingId
            ) {
                if ($excludeBookingId) {
                    $bookingQuery->where('bookings.id', '!=', $excludeBookingId);
                }

                $bookingQuery
                    ->activeForOperations()
                    ->where('check_in_at', '<', $checkOutAtString)
                    ->where('check_out_at', '>', $checkInAtString);
            })
            ->orderByRaw("CASE rooms.status WHEN 'available' THEN 0 WHEN 'reserved' THEN 1 WHEN 'cleaning' THEN 2 WHEN 'occupied' THEN 3 ELSE 4 END")
            // Random trong từng nhóm trạng thái: ưu tiên phòng sẵn sàng,
            // chỉ lấy phòng đang dọn khi nhóm sẵn sàng không đủ.
            ->inRandomOrder();
    }

}
