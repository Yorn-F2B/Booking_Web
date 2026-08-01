<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingLog;
use App\Models\BookingRoom;
use App\Models\Room;
use App\Models\RoomActionLog;
use App\Models\RoomIssueRequest;
use App\Models\StaffFloorAssignment;
use App\Models\StaffRoomAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\Realtime;

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


    public function repairs(Request $request)
    {
        $status = (string) $request->query('status', 'waiting');
        if (!in_array($status, ['waiting', 'completed', 'all'], true)) {
            $status = 'waiting';
        }

        $query = RoomIssueRequest::query()
            ->with([
                'booking.customer',
                'currentRoom.category',
                'approvedRoom.category',
                'reviewer',
                'repairCompleter',
                'attachments',
            ])
            ->whereIn('status', ['approved', 'repair_only'])
            ->whereNotNull('resolution_type');

        if ($status !== 'all') {
            $query->where('repair_status', $status);
        }

        $this->applyHousekeepingIssueScope($query);

        $issues = $query
            ->orderByRaw("CASE WHEN repair_status = 'waiting' THEN 0 ELSE 1 END")
            ->latest('reviewed_at')
            ->paginate(15)
            ->withQueryString();

        $pendingCountQuery = RoomIssueRequest::query()
            ->whereIn('status', ['approved', 'repair_only'])
            ->where('repair_status', 'waiting');
        $this->applyHousekeepingIssueScope($pendingCountQuery);
        $pendingCount = $pendingCountQuery->count();

        return view('admin.pages.housekeeping.repairs', compact('issues', 'status', 'pendingCount'));
    }

    public function completeRepair(RoomIssueRequest $roomIssueRequest)
    {
        $roomIssueRequest->loadMissing(['currentRoom', 'booking']);
        $this->guardCanHandleIssue($roomIssueRequest);

        if (!in_array($roomIssueRequest->status, ['approved', 'repair_only'], true)
            || $roomIssueRequest->repair_status !== 'waiting') {
            return back()->with('error', 'Yêu cầu sửa phòng này đã hoàn tất hoặc chưa được quản lý duyệt.');
        }

        DB::transaction(function () use ($roomIssueRequest) {
            $issue = RoomIssueRequest::query()
                ->whereKey($roomIssueRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($issue->repair_status !== 'waiting') {
                return;
            }

            $room = Room::query()->whereKey($issue->current_room_id)->lockForUpdate()->firstOrFail();

            $stillOccupied = BookingRoom::query()
                ->where('room_id', $room->id)
                ->whereHas('booking', function ($bookingQuery) {
                    $bookingQuery->whereIn('status', ['checked_in', 'inspection_requested'])
                        ->whereNotNull('actual_check_in');
                })
                ->exists();

            $nextStatus = $stillOccupied ? 'occupied' : 'available';

            $room->update([
                'status' => $nextStatus,
                'status_from' => null,
                'status_until' => null,
                'note' => $stillOccupied
                    ? 'Sự cố đã được khắc phục; khách vẫn đang lưu trú trong phòng.'
                    : null,
            ]);

            $issue->update([
                'repair_status' => 'completed',
                'repair_completed_by' => Auth::id(),
                'repair_completed_at' => now('Asia/Ho_Chi_Minh'),
            ]);

            RoomActionLog::create([
                'room_id' => $room->id,
                'user_id' => Auth::id(),
                'action_type' => 'maintenance',
                'action_time' => now('Asia/Ho_Chi_Minh'),
                'note' => 'Buồng phòng xác nhận đã khắc phục sự cố. Chuyển phòng sang trạng thái '
                    . ($nextStatus === 'available' ? 'trống' : 'đang ở') . '.',
            ]);

            BookingLog::create([
                'booking_id' => $issue->booking_id,
                'user_id' => Auth::id(),
                'action' => 'room_issue_repair_completed',
                'description' => 'Buồng phòng đã xác nhận khắc phục xong sự cố tại phòng '
                    . $room->room_number . '. Trạng thái phòng sau xử lý: '
                    . ($nextStatus === 'available' ? 'trống' : 'đang ở vì khách vẫn lưu trú') . '.',
            ]);
        });

        return back()->with('success', 'Đã xác nhận sửa xong phòng và cập nhật trạng thái phòng tự động.');
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

    private function applyHousekeepingIssueScope($query): void
    {
        $user = Auth::user();

        if (!$user || in_array($user->role, ['super_admin', 'manager', 'housekeeping_supervisor'], true)) {
            return;
        }

        if ($user->role !== 'housekeeping') {
            abort(403, 'Bạn không có quyền xem danh sách phòng cần sửa.');
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

        $query->whereHas('currentRoom', function ($roomQuery) use ($assignedFloorNumbers, $assignedRoomIds) {
            $roomQuery->whereIn('id', $assignedRoomIds);
            if (!empty($assignedFloorNumbers)) {
                $roomQuery->orWhereIn('floor_number', $assignedFloorNumbers);
            }
        });
    }

    private function guardCanHandleIssue(RoomIssueRequest $issue): void
    {
        abort_unless($issue->currentRoom, 404, 'Không tìm thấy phòng của yêu cầu sự cố.');
        $this->guardCanHandleRoom($issue->currentRoom);
    }

}
