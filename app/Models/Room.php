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

    /**
     * Kiểm tra phòng có đang được booking khác (ngoài $excludeBookingId) đang hoạt động giữ không.
     * Dùng để tránh set status = available khi cancel/hủy booking tương lai
     * mà phòng đang bị giữ/occupied bởi booking khác.
     */
    public function hasActiveBookingOtherThan(int $excludeBookingId): bool
    {
        return $this->bookingRooms()
            ->whereHas('booking', function ($q) use ($excludeBookingId) {
                $q->where('id', '!=', $excludeBookingId)
                  ->whereIn('status', ['confirmed', 'checked_in', 'inspection_requested', 'pending']);
            })
            ->exists();
    }

    /**
     * Giải phóng phòng khỏi một booking.
     * Chỉ đổi status sang available nếu:
     * 1. Không có booking nào khác đang active trên phòng này.
     * 2. Tình trạng vật lý của phòng không nằm trong các trạng thái đang có người hoặc đang dọn/sửa.
     */
    public function releaseRoomFromBooking(int $bookingId): void
    {
        if ($this->hasActiveBookingOtherThan($bookingId)) {
            return;
        }

        if (in_array($this->status, ['occupied', 'cleaning', 'inspection', 'maintenance'])) {
            return;
        }

        $this->update(['status' => 'available']);
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
