<?php

namespace App\Support;

use App\Models\StaffFloorAssignment;
use App\Models\StaffRoomAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Một nguồn phạm vi duy nhất cho công việc buồng phòng.
 *
 * Supervisor/Manager/Super Admin nhìn toàn bộ. Nhân viên buồng phòng thường
 * chỉ nhìn các phòng được giao riêng trong ngày hoặc các tầng đang được phân
 * công lâu dài. Sidebar badge và các trang danh sách phải dùng cùng scope này
 * để tránh tình trạng menu báo có việc nhưng mở trang lại không có gì.
 */
class HousekeepingWorkScope
{
    /** @return array{0: array<int>, 1: array<int>} */
    public static function assignmentIds(?User $user, ?string $date = null): array
    {
        if (!$user || $user->role !== 'housekeeping') {
            return [[], []];
        }

        $date ??= now('Asia/Ho_Chi_Minh')->toDateString();

        $floorNumbers = StaffFloorAssignment::query()
            ->where('staff_id', $user->id)
            ->effectiveOn($date)
            ->pluck('floor_number')
            ->filter(fn ($floor) => $floor !== null)
            ->map(fn ($floor) => (int) $floor)
            ->unique()
            ->values()
            ->all();

        $roomIds = StaffRoomAssignment::query()
            ->where('staff_id', $user->id)
            ->whereDate('work_date', $date)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->pluck('room_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return [$floorNumbers, $roomIds];
    }

    public static function applyToRooms(Builder $query, ?User $user, ?string $date = null): Builder
    {
        if (!$user || in_array($user->role, ['super_admin', 'manager', 'housekeeping_supervisor'], true)) {
            return $query;
        }

        if ($user->role !== 'housekeeping') {
            return $query->whereRaw('1 = 0');
        }

        [$floorNumbers, $roomIds] = self::assignmentIds($user, $date);

        return $query->where(function (Builder $roomQuery) use ($floorNumbers, $roomIds) {
            $roomQuery->whereIn('id', $roomIds);
            if ($floorNumbers !== []) {
                $roomQuery->orWhereIn('floor_number', $floorNumbers);
            }
        });
    }

    public static function applyToIssues(Builder $query, ?User $user, ?string $date = null): Builder
    {
        if (!$user || in_array($user->role, ['super_admin', 'manager', 'housekeeping_supervisor'], true)) {
            return $query;
        }

        if ($user->role !== 'housekeeping') {
            return $query->whereRaw('1 = 0');
        }

        [$floorNumbers, $roomIds] = self::assignmentIds($user, $date);

        return $query->whereHas('currentRoom', function (Builder $roomQuery) use ($floorNumbers, $roomIds) {
            $roomQuery->whereIn('id', $roomIds);
            if ($floorNumbers !== []) {
                $roomQuery->orWhereIn('floor_number', $floorNumbers);
            }
        });
    }

    public static function applyToInspections(Builder $query, ?User $user, ?string $date = null): Builder
    {
        if (!$user || in_array($user->role, ['super_admin', 'manager', 'housekeeping_supervisor'], true)) {
            return $query;
        }

        if ($user->role !== 'housekeeping') {
            return $query->whereRaw('1 = 0');
        }

        [$floorNumbers, $roomIds] = self::assignmentIds($user, $date);

        return $query->whereHas('room', function (Builder $roomQuery) use ($floorNumbers, $roomIds) {
            $roomQuery->whereIn('id', $roomIds);
            if ($floorNumbers !== []) {
                $roomQuery->orWhereIn('floor_number', $floorNumbers);
            }
        });
    }
}
