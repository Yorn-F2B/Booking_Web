<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\StaffFloorAssignment;
use App\Models\StaffRoomAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\Realtime;

class HousekeepingController extends Controller
{
    public function index()
    {
        $rooms = Room::with('category')
            ->where('status', 'cleaning');

        $this->applyHousekeepingAssignmentScope($rooms);

        $rooms = $rooms
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->paginate(10);

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

    private function applyHousekeepingAssignmentScope($query): void
    {
        $user = Auth::user();

        if (!$user || in_array($user->role, ['super_admin', 'manager', 'housekeeping_supervisor'], true)) {
            return;
        }

        if ($user->role !== 'housekeeping') {
            abort(403, 'Bạn không có quyền xem danh sách dọn phòng.');
        }

        $today = now('Asia/Ho_Chi_Minh')->toDateString();

        $assignedFloorNumbers = StaffFloorAssignment::where('staff_id', $user->id)
            ->whereDate('work_date', $today)
            ->where('status', 'active')
            ->pluck('floor_number')
            ->toArray();

        $assignedRoomIds = StaffRoomAssignment::where('staff_id', $user->id)
            ->whereDate('work_date', $today)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->pluck('room_id')
            ->toArray();

        $query->where(function ($roomQuery) use ($assignedFloorNumbers, $assignedRoomIds) {
            $roomQuery->whereIn('id', $assignedRoomIds);

            if (!empty($assignedFloorNumbers)) {
                $roomQuery->orWhereIn('floor_number', $assignedFloorNumbers);
            }
        });
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
            ->whereDate('work_date', $today)
            ->where('status', 'active')
            ->exists();

        abort_unless($assignedByRoom || $assignedByFloor, 403, 'Bạn không được phân công xử lý phòng này.');
    }
}
