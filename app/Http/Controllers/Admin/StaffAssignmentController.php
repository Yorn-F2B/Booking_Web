<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStaffAssignment;
use App\Models\Room;
use App\Models\StaffFloorAssignment;
use App\Models\StaffRoomAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffAssignmentController extends Controller
{
    public function index()
    {
        $this->ensureCanManageAnyAssignment();

        $today = Carbon::today('Asia/Ho_Chi_Minh')->toDateString();
        $canManageReceptionists = $this->canManageReceptionistAssignments();
        $canManageHousekeeping = $this->canManageHousekeepingAssignments();

        $receptionistAssignmentCount = $canManageReceptionists
            ? BookingStaffAssignment::where('status', 'active')->count()
            : 0;

        $floorAssignmentCount = $canManageHousekeeping
            ? StaffFloorAssignment::whereDate('work_date', $today)
                ->where('status', 'active')
                ->count()
            : 0;

        $roomAssignmentCount = $canManageHousekeeping
            ? StaffRoomAssignment::whereDate('work_date', $today)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count()
            : 0;

        return view('admin.pages.staff-assignments.index', compact(
            'today',
            'canManageReceptionists',
            'canManageHousekeeping',
            'receptionistAssignmentCount',
            'floorAssignmentCount',
            'roomAssignmentCount'
        ));
    }

    public function receptionists(Request $request)
    {
        $this->ensureCanManageReceptionistAssignments();

        $receptionists = User::with('staff')
            ->where('role', 'receptionist')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $bookings = Booking::with([
            'customer',
            'roomCategory',
            'creator',
            'activeStaffAssignments.staff.staff',
        ])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = trim($request->keyword);

                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('booking_code', 'like', '%' . $keyword . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($keyword) {
                            $customerQuery->where('first_name', 'like', '%' . $keyword . '%')
                                ->orWhere('last_name', 'like', '%' . $keyword . '%')
                                ->orWhere('phone', 'like', '%' . $keyword . '%')
                                ->orWhere('email', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('assigned_staff_id'), function ($query) use ($request) {
                if ($request->assigned_staff_id === 'none') {
                    $query->whereDoesntHave('activeStaffAssignments');
                    return;
                }

                $query->whereHas('activeStaffAssignments', function ($assignmentQuery) use ($request) {
                    $assignmentQuery->where('staff_id', $request->assigned_staff_id);
                });
            })
            ->whereNotIn('status', ['completed', 'checked_out', 'canceled', 'cancelled'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pages.staff-assignments.receptionists', compact('bookings', 'receptionists'));
    }

    public function storeReceptionist(Request $request)
    {
        $this->ensureCanManageReceptionistAssignments();

        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'staff_id' => 'required|exists:users,id',
            'role_in_booking' => 'required|in:owner,check_in,check_out,payment,support',
            'note' => 'nullable|string|max:1000',
        ], [
            'booking_id.required' => 'Vui lòng chọn booking cần gán.',
            'staff_id.required' => 'Vui lòng chọn lễ tân phụ trách.',
            'role_in_booking.required' => 'Vui lòng chọn nhiệm vụ của lễ tân.',
        ]);

        $staff = User::where('id', $data['staff_id'])
            ->where('role', 'receptionist')
            ->where('status', 'active')
            ->first();

        if (!$staff) {
            return back()->withInput()->with('error', 'Nhân viên được chọn không phải lễ tân đang hoạt động.');
        }

        DB::transaction(function () use ($data) {
            if ($data['role_in_booking'] === 'owner') {
                BookingStaffAssignment::where('booking_id', $data['booking_id'])
                    ->where('role_in_booking', 'owner')
                    ->where('status', 'active')
                    ->update(['status' => 'canceled']);
            }

            BookingStaffAssignment::updateOrCreate(
                [
                    'booking_id' => $data['booking_id'],
                    'staff_id' => $data['staff_id'],
                    'role_in_booking' => $data['role_in_booking'],
                ],
                [
                    'assigned_by' => Auth::id(),
                    'status' => 'active',
                    'note' => $data['note'] ?? null,
                ]
            );
        });

        return back()->with('success', 'Đã gán lễ tân phụ trách booking.');
    }

    public function cancelBookingAssignment(BookingStaffAssignment $bookingStaffAssignment)
    {
        $this->ensureCanManageReceptionistAssignments();

        $bookingStaffAssignment->update([
            'status' => 'canceled',
        ]);

        return back()->with('success', 'Đã hủy phân công lễ tân.');
    }

    public function housekeeping(Request $request)
    {
        $this->ensureCanManageHousekeepingAssignments();

        $workDate = $request->input('work_date', Carbon::today('Asia/Ho_Chi_Minh')->toDateString());

        $housekeepers = User::with('staff')
            ->where('role', 'housekeeping')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $floors = Room::query()
            ->select('floor_number')
            ->whereNotNull('floor_number')
            ->distinct()
            ->orderBy('floor_number')
            ->pluck('floor_number');

        $rooms = Room::with('category')
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->get();

        $floorAssignments = StaffFloorAssignment::with(['staff.staff', 'assigner'])
            ->whereDate('work_date', $workDate)
            ->latest()
            ->get();

        $roomAssignments = StaffRoomAssignment::with(['staff.staff', 'room.category', 'assigner'])
            ->whereDate('work_date', $workDate)
            ->latest()
            ->get();

        return view('admin.pages.staff-assignments.housekeeping', compact(
            'workDate',
            'housekeepers',
            'floors',
            'rooms',
            'floorAssignments',
            'roomAssignments'
        ));
    }

    public function storeFloorAssignment(Request $request)
    {
        $this->ensureCanManageHousekeepingAssignments();

        $data = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'floor_numbers' => 'required|array|min:1',
            'floor_numbers.*' => 'required|integer',
            'work_date' => 'required|date',
            'shift' => 'required|in:morning,afternoon,evening,full_day',
            'note' => 'nullable|string|max:1000',
        ], [
            'staff_id.required' => 'Vui lòng chọn nhân viên buồng phòng.',
            'floor_numbers.required' => 'Vui lòng chọn ít nhất một tầng.',
            'work_date.required' => 'Vui lòng chọn ngày làm việc.',
        ]);

        $this->guardHousekeeper($data['staff_id']);

        foreach (array_unique($data['floor_numbers']) as $floorNumber) {
            StaffFloorAssignment::updateOrCreate(
                [
                    'staff_id' => $data['staff_id'],
                    'floor_number' => $floorNumber,
                    'work_date' => $data['work_date'],
                    'shift' => $data['shift'],
                ],
                [
                    'status' => 'active',
                    'assigned_by' => Auth::id(),
                    'note' => $data['note'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Đã gán tầng cho nhân viên buồng phòng.');
    }

    public function storeRoomAssignment(Request $request)
    {
        $this->ensureCanManageHousekeepingAssignments();

        $data = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'room_ids' => 'required|array|min:1',
            'room_ids.*' => 'required|exists:rooms,id',
            'work_date' => 'required|date',
            'shift' => 'required|in:morning,afternoon,evening,full_day',
            'task_type' => 'required|in:cleaning,inspection,maintenance_support',
            'note' => 'nullable|string|max:1000',
        ], [
            'staff_id.required' => 'Vui lòng chọn nhân viên buồng phòng.',
            'room_ids.required' => 'Vui lòng chọn ít nhất một phòng.',
            'work_date.required' => 'Vui lòng chọn ngày làm việc.',
        ]);

        $this->guardHousekeeper($data['staff_id']);

        foreach (array_unique($data['room_ids']) as $roomId) {
            StaffRoomAssignment::updateOrCreate(
                [
                    'staff_id' => $data['staff_id'],
                    'room_id' => $roomId,
                    'work_date' => $data['work_date'],
                    'shift' => $data['shift'],
                    'task_type' => $data['task_type'],
                ],
                [
                    'status' => 'assigned',
                    'assigned_by' => Auth::id(),
                    'note' => $data['note'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Đã gán phòng cho nhân viên buồng phòng.');
    }

    public function deleteFloorAssignment(StaffFloorAssignment $staffFloorAssignment)
    {
        $this->ensureCanManageHousekeepingAssignments();

        $staffFloorAssignment->delete();

        return back()->with('success', 'Đã xóa phân công tầng.');
    }

    public function deleteRoomAssignment(StaffRoomAssignment $staffRoomAssignment)
    {
        $this->ensureCanManageHousekeepingAssignments();

        $staffRoomAssignment->delete();

        return back()->with('success', 'Đã xóa phân công phòng.');
    }

    private function guardHousekeeper(int $staffId): void
    {
        $staff = User::where('id', $staffId)
            ->where('role', 'housekeeping')
            ->where('status', 'active')
            ->first();

        if (!$staff) {
            throw ValidationException::withMessages([
                'staff_id' => 'Nhân viên được chọn không phải buồng phòng đang hoạt động.',
            ]);
        }
    }

    private function ensureCanManageAnyAssignment(): void
    {
        abort_unless(
            $this->canManageReceptionistAssignments() || $this->canManageHousekeepingAssignments(),
            403,
            'Bạn không có quyền phân công nhân sự.'
        );
    }

    private function ensureCanManageReceptionistAssignments(): void
    {
        abort_unless(
            $this->canManageReceptionistAssignments(),
            403,
            'Bạn không có quyền phân công lễ tân.'
        );
    }

    private function ensureCanManageHousekeepingAssignments(): void
    {
        abort_unless(
            $this->canManageHousekeepingAssignments(),
            403,
            'Bạn không có quyền phân công buồng phòng.'
        );
    }

    private function canManageReceptionistAssignments(): bool
    {
        $role = Auth::user()->role ?? null;

        return in_array($role, ['super_admin', 'manager', 'receptionist_lead'], true);
    }

    private function canManageHousekeepingAssignments(): bool
    {
        $role = Auth::user()->role ?? null;

        return in_array($role, ['super_admin', 'manager', 'housekeeping_supervisor'], true);
    }
}
