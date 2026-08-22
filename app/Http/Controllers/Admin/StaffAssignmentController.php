<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingStaffAssignment;
use App\Models\Room;
use App\Models\StaffFloorAssignment;
use App\Models\StaffRoomAssignment;
use App\Models\User;
use App\Support\StaffShiftSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffAssignmentController extends Controller
{
    private const ACTIVE_BOOKING_STATUSES = [
        'pending',
        'confirmed',
        'checked_in',
        'inspection_requested',
    ];

    public function index()
    {
        $this->ensureCanManageAnyAssignment();

        $today = Carbon::today('Asia/Ho_Chi_Minh')->toDateString();
        $canManageReceptionists = $this->canManageReceptionistAssignments();
        $canManageHousekeeping = $this->canManageHousekeepingAssignments();

        $receptionistAssignmentCount = $canManageReceptionists
            ? Booking::query()
                ->whereIn('status', self::ACTIVE_BOOKING_STATUSES)
                ->whereHas('activeStaffAssignments')
                ->count()
            : 0;

        $floorAssignmentCount = $canManageHousekeeping
            ? StaffFloorAssignment::effectiveOn($today)->count()
            : 0;

        $roomAssignmentCount = $canManageHousekeeping
            ? StaffRoomAssignment::whereDate('work_date', $today)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count()
            : 0;

        $shiftDefinitions = StaffShiftSchedule::definitions();

        return view('admin.pages.staff-assignments.index', compact(
            'today',
            'canManageReceptionists',
            'canManageHousekeeping',
            'receptionistAssignmentCount',
            'floorAssignmentCount',
            'roomAssignmentCount',
            'shiftDefinitions'
        ));
    }

    public function status(Request $request)
    {
        $this->ensureCanManageAnyAssignment();

        $today = Carbon::today('Asia/Ho_Chi_Minh')->toDateString();
        $canManageReceptionists = $this->canManageReceptionistAssignments();
        $canManageHousekeeping = $this->canManageHousekeepingAssignments();
        $type = in_array($request->input('type'), ['all', 'receptionist', 'floor', 'room'], true)
            ? $request->input('type')
            : 'all';
        $keyword = trim((string) $request->input('keyword', ''));

        $bookingAssignments = null;
        if ($canManageReceptionists && in_array($type, ['all', 'receptionist'], true)) {
            $bookingAssignments = Booking::query()
                ->whereIn('status', self::ACTIVE_BOOKING_STATUSES)
                ->whereHas('activeStaffAssignments')
                ->with([
                    'customer',
                    'activeStaffAssignments.staff.staff',
                    'activeStaffAssignments.assigner',
                ])
                ->when($keyword !== '', function ($query) use ($keyword) {
                    $query->where(function ($subQuery) use ($keyword) {
                        $subQuery->where('booking_code', 'like', '%' . $keyword . '%')
                            ->orWhereHas('customer', function ($customerQuery) use ($keyword) {
                                $customerQuery->where('first_name', 'like', '%' . $keyword . '%')
                                    ->orWhere('last_name', 'like', '%' . $keyword . '%')
                                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                                    ->orWhere('email', 'like', '%' . $keyword . '%');
                            })
                            ->orWhereHas('activeStaffAssignments.staff', function ($staffQuery) use ($keyword) {
                                $staffQuery->where('name', 'like', '%' . $keyword . '%')
                                    ->orWhereHas('staff', fn ($profileQuery) => $profileQuery->where('full_name', 'like', '%' . $keyword . '%'));
                            });
                    });
                })
                ->latest('updated_at')
                ->paginate(15, ['*'], 'booking_page')
                ->withQueryString();
        }

        $floorAssignmentGroups = collect();
        if ($canManageHousekeeping && in_array($type, ['all', 'floor'], true)) {
            $floorAssignments = StaffFloorAssignment::query()
                ->with(['staff.staff', 'assigner'])
                ->effectiveOn($today)
                ->when($keyword !== '', function ($query) use ($keyword) {
                    $query->where(function ($subQuery) use ($keyword) {
                        $subQuery->where('floor_number', 'like', '%' . $keyword . '%')
                            ->orWhereHas('staff', function ($staffQuery) use ($keyword) {
                                $staffQuery->where('name', 'like', '%' . $keyword . '%')
                                    ->orWhereHas('staff', fn ($profileQuery) => $profileQuery->where('full_name', 'like', '%' . $keyword . '%'));
                            });
                    });
                })
                ->orderBy('staff_id')
                ->orderBy('shift')
                ->orderBy('floor_number')
                ->get();

            $floorAssignmentGroups = $floorAssignments
                ->groupBy(fn ($assignment) => $assignment->staff_id . '|' . $assignment->shift . '|' . optional($assignment->work_date)->toDateString())
                ->values();
        }

        $roomAssignments = null;
        if ($canManageHousekeeping && in_array($type, ['all', 'room'], true)) {
            $roomAssignments = StaffRoomAssignment::query()
                ->with(['staff.staff', 'room.category', 'assigner'])
                ->whereDate('work_date', $today)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->when($keyword !== '', function ($query) use ($keyword) {
                    $query->where(function ($subQuery) use ($keyword) {
                        $subQuery->whereHas('room', fn ($roomQuery) => $roomQuery->where('room_number', 'like', '%' . $keyword . '%'))
                            ->orWhereHas('staff', function ($staffQuery) use ($keyword) {
                                $staffQuery->where('name', 'like', '%' . $keyword . '%')
                                    ->orWhereHas('staff', fn ($profileQuery) => $profileQuery->where('full_name', 'like', '%' . $keyword . '%'));
                            });
                    });
                })
                ->latest('updated_at')
                ->paginate(15, ['*'], 'room_page')
                ->withQueryString();
        }

        $summary = [
            'bookings' => $canManageReceptionists
                ? Booking::query()->whereIn('status', self::ACTIVE_BOOKING_STATUSES)->whereHas('activeStaffAssignments')->count()
                : 0,
            'receptionists' => $canManageReceptionists
                ? BookingStaffAssignment::query()
                    ->where('status', 'active')
                    ->whereHas('booking', fn ($query) => $query->whereIn('status', self::ACTIVE_BOOKING_STATUSES))
                    ->distinct()
                    ->count('staff_id')
                : 0,
            'floor_assignments' => $canManageHousekeeping ? StaffFloorAssignment::effectiveOn($today)->count() : 0,
            'room_tasks' => $canManageHousekeeping
                ? StaffRoomAssignment::whereDate('work_date', $today)->whereIn('status', ['assigned', 'in_progress'])->count()
                : 0,
        ];

        $shiftLabels = StaffShiftSchedule::labels();

        return view('admin.pages.staff-assignments.status', compact(
            'today',
            'type',
            'keyword',
            'canManageReceptionists',
            'canManageHousekeeping',
            'bookingAssignments',
            'floorAssignmentGroups',
            'roomAssignments',
            'summary',
            'shiftLabels'
        ));
    }

    public function receptionists(Request $request)
    {
        $this->ensureCanManageReceptionistAssignments();

        $receptionists = User::with('staff')
            ->whereIn('role', ['receptionist', 'receptionist_lead'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $bookings = Booking::with([
            'customer',
            'roomCategory',
            'creator',
            'activeStaffAssignments.staff.staff',
            'activeStaffAssignments.assigner',
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
            ->whereIn('status', self::ACTIVE_BOOKING_STATUSES)
            ->latest('updated_at')
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
            'note' => 'nullable|string|max:1000',
        ], [
            'booking_id.required' => 'Vui lòng chọn booking cần gán.',
            'staff_id.required' => 'Vui lòng chọn lễ tân phụ trách.',
        ]);

        $staff = User::where('id', $data['staff_id'])
            ->whereIn('role', ['receptionist', 'receptionist_lead'])
            ->where('status', 'active')
            ->first();

        if (!$staff) {
            return back()->withInput()->with('error', 'Nhân viên được chọn không phải lễ tân/trưởng lễ tân đang hoạt động.');
        }

        DB::transaction(function () use ($data) {
            $booking = Booking::whereKey($data['booking_id'])->lockForUpdate()->firstOrFail();
            if (!in_array($booking->status, self::ACTIVE_BOOKING_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'booking_id' => 'Booking đã kết thúc/hủy nên không thể gán lễ tân phụ trách.',
                ]);
            }

            $activeAssignments = BookingStaffAssignment::query()
                ->where('booking_id', $booking->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            if (
                $activeAssignments->count() === 1
                && (int) $activeAssignments->first()->staff_id === (int) $data['staff_id']
                && $activeAssignments->first()->role_in_booking === 'owner'
            ) {
                $activeAssignments->first()->update([
                    'assigned_by' => Auth::id(),
                    'note' => $data['note'] ?? $activeAssignments->first()->note,
                ]);
                return;
            }

            BookingStaffAssignment::query()
                ->where('booking_id', $booking->id)
                ->where('status', 'active')
                ->update(['status' => 'canceled']);

            BookingStaffAssignment::create([
                'booking_id' => $booking->id,
                'staff_id' => $data['staff_id'],
                'role_in_booking' => 'owner',
                'assigned_by' => Auth::id(),
                'status' => 'active',
                'note' => $data['note'] ?? null,
            ]);
        });

        return back()->with('success', 'Đã giao toàn bộ quy trình booking cho lễ tân được chọn.');
    }

    public function cancelBookingAssignment(BookingStaffAssignment $bookingStaffAssignment)
    {
        $this->ensureCanManageReceptionistAssignments();

        BookingStaffAssignment::query()
            ->where('booking_id', $bookingStaffAssignment->booking_id)
            ->where('status', 'active')
            ->update(['status' => 'canceled']);

        return back()->with('success', 'Đã dừng phân công toàn bộ booking. Booking trở về trạng thái chưa có lễ tân phụ trách.');
    }

    public function housekeeping(Request $request)
    {
        $this->ensureCanManageHousekeepingAssignments();

        $workDate = $request->input('work_date', Carbon::today('Asia/Ho_Chi_Minh')->toDateString());

        $housekeepers = User::with('staff')
            ->whereIn('role', ['housekeeping', 'housekeeping_supervisor'])
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
            ->effectiveOn($workDate)
            ->orderBy('floor_number')
            ->orderBy('shift')
            ->get();

        $roomAssignments = StaffRoomAssignment::with(['staff.staff', 'room.category', 'assigner'])
            ->whereDate('work_date', $workDate)
            ->latest()
            ->get();

        $activeFloorAssignmentsByStaff = StaffFloorAssignment::query()
            ->with(['staff.staff'])
            ->where('status', 'active')
            ->orderBy('work_date')
            ->orderBy('floor_number')
            ->get()
            ->groupBy('staff_id');

        $shiftDefinitions = StaffShiftSchedule::definitions();
        $shiftLabels = StaffShiftSchedule::labels();

        return view('admin.pages.staff-assignments.housekeeping', compact(
            'workDate',
            'housekeepers',
            'floors',
            'rooms',
            'floorAssignments',
            'roomAssignments',
            'activeFloorAssignmentsByStaff',
            'shiftDefinitions',
            'shiftLabels'
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
            'shift' => ['required', Rule::in(StaffShiftSchedule::keys())],
            'note' => 'nullable|string|max:1000',
        ], [
            'staff_id.required' => 'Vui lòng chọn nhân viên buồng phòng.',
            'floor_numbers.required' => 'Vui lòng chọn ít nhất một tầng.',
            'work_date.required' => 'Vui lòng chọn ngày làm việc.',
        ]);

        $this->guardHousekeeper($data['staff_id']);

        DB::transaction(function () use ($data) {
            $existingAssignments = StaffFloorAssignment::query()
                ->where('staff_id', $data['staff_id'])
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            $conflicts = $existingAssignments
                ->filter(fn ($assignment) => StaffShiftSchedule::overlaps($assignment->shift, $data['shift']));

            if ($conflicts->isNotEmpty()) {
                $details = $conflicts
                    ->groupBy('shift')
                    ->map(function ($assignments, $shift) {
                        $floors = $assignments->pluck('floor_number')->unique()->sort()->implode(', ');
                        return StaffShiftSchedule::label($shift) . ' · tầng ' . $floors;
                    })
                    ->implode('; ');

                throw ValidationException::withMessages([
                    'staff_id' => 'Nhân viên đang có phân công còn hiệu lực (' . $details . '). Hãy bấm Dừng phân công cũ trước khi gán tiếp vào ca bị trùng.',
                ]);
            }

            foreach (array_unique($data['floor_numbers']) as $floorNumber) {
                StaffFloorAssignment::create([
                    'staff_id' => $data['staff_id'],
                    'floor_number' => $floorNumber,
                    'work_date' => $data['work_date'],
                    'shift' => $data['shift'],
                    'status' => 'active',
                    'assigned_by' => Auth::id(),
                    'note' => $data['note'] ?? null,
                ]);
            }
        });

        return back()->with('success', 'Đã lưu phân công tầng lâu dài. Phân công có hiệu lực từ ngày đã chọn đến khi được dừng.');
    }

    public function storeRoomAssignment(Request $request)
    {
        $this->ensureCanManageHousekeepingAssignments();

        $data = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'room_ids' => 'required|array|min:1',
            'room_ids.*' => 'required|exists:rooms,id',
            'work_date' => 'required|date',
            'shift' => ['required', Rule::in(StaffShiftSchedule::keys())],
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

        return back()->with('success', 'Đã gán nhiệm vụ phòng tạm thời cho nhân viên buồng phòng.');
    }

    public function stopFloorAssignmentGroup(Request $request)
    {
        $this->ensureCanManageHousekeepingAssignments();

        $data = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'shift' => ['required', Rule::in(StaffShiftSchedule::keys())],
        ]);

        $stopped = StaffFloorAssignment::query()
            ->where('staff_id', $data['staff_id'])
            ->where('shift', $data['shift'])
            ->where('status', 'active')
            ->update(['status' => 'canceled']);

        if ($stopped === 0) {
            return back()->with('warning', 'Phân công này đã được dừng hoặc không còn hiệu lực.');
        }

        return back()->with('success', 'Đã dừng toàn bộ phân công ' . StaffShiftSchedule::label($data['shift']) . ' của nhân viên. Có thể gán lại ngay.');
    }

    public function deleteFloorAssignment(StaffFloorAssignment $staffFloorAssignment)
    {
        $this->ensureCanManageHousekeepingAssignments();

        $staffFloorAssignment->update(['status' => 'canceled']);

        return back()->with('success', 'Đã dừng phân công tầng. Nhân viên có thể được gán lại vào ca tương ứng.');
    }

    public function deleteRoomAssignment(StaffRoomAssignment $staffRoomAssignment)
    {
        $this->ensureCanManageHousekeepingAssignments();

        $staffRoomAssignment->update(['status' => 'canceled']);

        return back()->with('success', 'Đã hủy nhiệm vụ phòng.');
    }

    private function guardHousekeeper(int $staffId): void
    {
        $staff = User::where('id', $staffId)
            ->whereIn('role', ['housekeeping', 'housekeeping_supervisor'])
            ->where('status', 'active')
            ->first();

        if (!$staff) {
            throw ValidationException::withMessages([
                'staff_id' => 'Nhân viên được chọn không phải buồng phòng/trưởng buồng phòng đang hoạt động.',
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
