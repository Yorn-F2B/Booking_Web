<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\StaffFloorAssignment;
use App\Models\StaffRoomAssignment;
use Illuminate\Support\Facades\Auth;
use App\Support\Realtime;
use App\Support\HousekeepingWorkScope;

class HousekeepingController extends Controller
{
    public function index()
    {
        $rooms = Room::with('category')
            ->where('status', 'cleaning');

        $this->applyHousekeepingAssignmentScope($rooms);

        $rooms = $rooms
            ->orderByRaw("CASE WHEN note LIKE '%[PRIORITY_BOOKING:%' THEN 0 ELSE 1 END")
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->paginate(10);

        $this->attachHousekeepingAssignees($rooms);

        return view('admin.pages.housekeeping.index', compact('rooms'));
    }

    public function markAvailable(Room $room)
    {
        $this->guardCanHandleRoom($room);

        if ($room->status !== 'cleaning') {
            return back()->with('error', 'Chỉ có thể hoàn tất dọn phòng với phòng đang dọn dẹp.');
        }

        $room->update([
            'status' => 'available',
            'status_from' => null,
            'status_until' => null,
            'note' => null,
        ]);

        \App\Models\RoomActionLog::create([
            'room_id' => $room->id,
            'user_id' => Auth::id(),
            'action_type' => 'cleaning',
            'action_time' => now(),
            'note' => 'Nhân viên hoàn tất dọn phòng. Chuyển phòng sang trạng thái trống.',
        ]);

        $user = Auth::user();

        if ($user && $user->role === 'housekeeping') {
            StaffRoomAssignment::where('staff_id', $user->id)
                ->where('room_id', $room->id)
                ->whereDate('work_date', now('Asia/Ho_Chi_Minh')->toDateString())
                ->whereIn('status', ['assigned', 'in_progress'])
                ->update(['status' => 'completed']);
        }

        return back()->with('success', 'Đã xác nhận dọn xong. Phòng đã chuyển về trạng thái còn trống.');
    }



    private function attachHousekeepingAssignees($rooms): void
    {
        $collection = $rooms->getCollection();
        if ($collection->isEmpty()) {
            return;
        }

        $today = now('Asia/Ho_Chi_Minh')->toDateString();
        $roomIds = $collection->pluck('id')->filter()->values();
        $floorNumbers = $collection->pluck('floor_number')->filter(fn ($floor) => $floor !== null)->unique()->values();

        $roomAssignments = StaffRoomAssignment::query()
            ->with('staff.staff')
            ->whereIn('room_id', $roomIds)
            ->whereDate('work_date', $today)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->get()
            ->groupBy('room_id');

        $floorAssignments = StaffFloorAssignment::query()
            ->with('staff.staff')
            ->whereIn('floor_number', $floorNumbers)
            ->effectiveOn($today)
            ->get()
            ->groupBy('floor_number');

        foreach ($collection as $room) {
            $names = collect($roomAssignments->get($room->id, []))
                ->merge($floorAssignments->get($room->floor_number, []))
                ->map(function ($assignment) {
                    return $assignment->staff?->staff?->full_name
                        ?: $assignment->staff?->name;
                })
                ->filter()
                ->unique()
                ->values();

            $room->setAttribute('housekeeping_assignees', $names);
        }
    }

    private function applyHousekeepingAssignmentScope($query): void
    {
        $user = Auth::user();

        if ($user && !in_array($user->role, ['super_admin', 'manager', 'housekeeping_supervisor', 'housekeeping'], true)) {
            abort(403, 'Bạn không có quyền xem danh sách dọn phòng.');
        }

        HousekeepingWorkScope::applyToRooms($query, $user);
    }

    private function guardCanHandleRoom(Room $room): void
    {
        $user = Auth::user();

        if (!$user || in_array($user->role, ['super_admin', 'manager', 'housekeeping_supervisor'], true)) {
            return;
        }

        if ($user->role !== 'housekeeping') {
            abort(403, 'Bạn không có quyền xử lý phòng này.');
        }

        $today = now('Asia/Ho_Chi_Minh')->toDateString();

        $assignedByRoom = StaffRoomAssignment::where('staff_id', $user->id)
            ->where('room_id', $room->id)
            ->whereDate('work_date', $today)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->exists();

        $assignedByFloor = StaffFloorAssignment::where('staff_id', $user->id)
            ->where('floor_number', $room->floor_number)
            ->effectiveOn($today)
            ->exists();

        abort_unless($assignedByRoom || $assignedByFloor, 403, 'Bạn không được phân công xử lý phòng này.');
    }


}
