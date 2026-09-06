<?php

namespace App\Models;

use App\Services\HotelPolicyService;
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
        int $cleaningBufferMinutes = 0,
        ?array $allowedCurrentStatuses = null,
        bool $allowCleaningWithoutReadyTime = false
    ): Builder {
        $checkInAt = Carbon::parse($checkInAt, 'Asia/Ho_Chi_Minh');
        $checkOutAt = Carbon::parse($checkOutAt, 'Asia/Ho_Chi_Minh');

        $checkInAtString = $checkInAt->toDateTimeString();
        $checkOutAtString = $checkOutAt->toDateTimeString();
        $checkOutWithBufferAtString = $checkOutAt
            ->copy()
            ->addMinutes(max(0, $cleaningBufferMinutes))
            ->toDateTimeString();

        return $query
            // Maintenance là trạng thái khóa bán cho tới khi nghiệp vụ sửa phòng
            // xác nhận hoàn tất và chuyển trạng thái. Không được dựa vào status_until
            // (chỉ là thời gian dự kiến) để tự coi phòng đã sửa xong. Cleaning/inspection
            // có thể được bán cho kỳ lưu trú tương lai nếu thời gian dự kiến hoàn tất
            // đã nằm trước giờ nhận phòng; backend vẫn kiểm tra lại khi xác nhận booking.
            ->where(function (Builder $statusQuery) use ($checkInAtString, $allowedCurrentStatuses, $allowCleaningWithoutReadyTime) {
                if ($allowedCurrentStatuses !== null) {
                    $statusQuery->whereIn('status', $allowedCurrentStatuses);

                    if (!$allowCleaningWithoutReadyTime && in_array('cleaning', $allowedCurrentStatuses, true)) {
                        $statusQuery->where(function (Builder $readyQuery) use ($checkInAtString) {
                            $readyQuery->where('status', '!=', 'cleaning')
                                ->orWhere(function (Builder $cleaningReady) use ($checkInAtString) {
                                    $cleaningReady->whereNotNull('status_until')
                                        ->where('status_until', '<=', $checkInAtString);
                                });
                        });
                    }

                    return;
                }

                // Trạng thái vật lý chỉ khóa bán khi phòng đang bảo trì.
                // Phòng đang dọn/kiểm tra vẫn được phép giữ cho booking; các booking
                // giao nhau và room-hold vẫn được chặn ở các điều kiện phía dưới.
                $statusQuery->where('status', '!=', 'maintenance');
            })
            // Hold sự cố chỉ chặn nếu hold còn hiệu lực *và* booking được giữ phòng
            // thực sự giao với khoảng đang tìm. Tránh giữ 30 phút hôm nay làm mất
            // phòng của một booking tuần sau.
            ->whereNotExists(function ($holdQuery) use (
                $excludeBookingId,
                $checkInAtString,
                $checkOutWithBufferAtString
            ) {
                $holdQuery->selectRaw('1')
                    ->from('room_issue_room_holds')
                    ->join('bookings as held_bookings', 'held_bookings.id', '=', 'room_issue_room_holds.booking_id')
                    ->whereColumn('room_issue_room_holds.room_id', 'rooms.id')
                    ->whereNull('room_issue_room_holds.released_at')
                    ->where('room_issue_room_holds.expires_at', '>', now('Asia/Ho_Chi_Minh'))
                    ->whereNull('held_bookings.deleted_at')
                    ->whereNotIn('held_bookings.status', ['cancelled', 'checked_out', 'completed'])
                    ->where('held_bookings.check_in_at', '<', $checkOutWithBufferAtString)
                    ->whereRaw(
                        'DATE_ADD(held_bookings.check_out_at, INTERVAL COALESCE(held_bookings.cleaning_buffer_minutes, 0) MINUTE) > ?',
                        [$checkInAtString]
                    );

                if ($excludeBookingId) {
                    $holdQuery->where('room_issue_room_holds.booking_id', '!=', $excludeBookingId);
                }
            })
            // Booking trước phải trả xong + hết buffer dọn; đồng thời buffer của
            // booking đang tìm cũng phải chừa chỗ cho booking kế tiếp.
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
        ?int $excludeBookingId = null,
        ?int $cleaningBufferMinutes = null
    ): Builder {
        $cleaningBufferMinutes ??= max(0, (int) app(HotelPolicyService::class)->get('booking.cleaning_buffer_minutes', 0));

        // Một nguồn kiểm tra tồn phòng duy nhất. Không duy trì hai bộ điều kiện
        // bookable/available khác nhau vì rất dễ sinh kết quả user và admin lệch nhau.
        return $query
            ->availableForPeriod(
                $checkInAt,
                $checkOutAt,
                $excludeBookingId,
                $cleaningBufferMinutes
            )
            ->orderByRaw("CASE rooms.status WHEN 'available' THEN 0 WHEN 'reserved' THEN 1 WHEN 'cleaning' THEN 2 WHEN 'occupied' THEN 3 ELSE 4 END")
            ->orderBy('rooms.floor_number')
            ->orderBy('rooms.room_number');
    }

}
