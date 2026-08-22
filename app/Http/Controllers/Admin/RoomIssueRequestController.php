<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\Room;
use App\Models\RoomActionLog;
use App\Models\RoomIssueAttachment;
use App\Models\RoomIssueRequest;
use App\Models\StaffFloorAssignment;
use App\Models\StaffRoomAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\HousekeepingWorkScope;

class RoomIssueRequestController extends Controller
{
    public function index(Request $request)
    {
        $this->guardManager();

        $status = (string) $request->query('status', 'pending');
        if (!in_array($status, ['pending', 'waiting_guest', 'approved', 'repair_only', 'rejected', 'all'], true)) {
            $status = 'pending';
        }
        $search = trim((string) $request->query('search', ''));

        $representativeIds = RoomIssueRequest::query()
            ->selectRaw('MIN(id)')
            ->groupBy('group_uuid');

        $query = RoomIssueRequest::query()
            ->whereIn('id', $representativeIds)
            ->with(['booking.customer', 'currentRoom.category', 'approvedRoom.category', 'reviewer']);

        if ($status !== 'all') {
            if ($status === 'pending') {
                $query->needsManagerAction();
            } elseif ($status === 'waiting_guest') {
                $query->waitingGuestConfirmation();
            } else {
                $query->where('status', $status);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('booking', fn ($b) => $b->where('booking_code', 'like', "%{$search}%"))
                    ->orWhereHas('currentRoom', fn ($r) => $r->where('room_number', 'like', "%{$search}%"))
                    ->orWhere('issue_description', 'like', "%{$search}%");
            });
        }

        $issues = $query->latest()->paginate(15)->withQueryString();
        $pendingCount = RoomIssueRequest::query()
            ->whereIn('id', $representativeIds)
            ->needsManagerAction()
            ->count();

        return view('admin.pages.room-issues.index', compact('issues', 'pendingCount', 'status', 'search'));
    }

    public function attachment(RoomIssueAttachment $attachment)
    {
        $attachment->loadMissing(['request.booking', 'request.currentRoom']);
        $this->guardIssueViewer($attachment->request);

        if (!Storage::disk('public')->exists($attachment->path)) {
            abort(404, 'Ảnh sự cố không còn tồn tại trong storage/app/public.');
        }

        return Storage::disk('public')->response(
            $attachment->path,
            $attachment->original_name ?: basename($attachment->path),
            ['Content-Type' => $attachment->mime_type ?: 'image/jpeg']
        );
    }

    public function repairs(Request $request)
    {
        $this->guardHousekeeping();

        $status = (string) $request->query('status', 'waiting');
        if (!in_array($status, ['waiting', 'completed', 'all'], true)) {
            $status = 'waiting';
        }

        $query = RoomIssueRequest::with([
            'booking', 'currentRoom.category', 'approvedRoom.category', 'attachments', 'reviewer', 'repairCompleter',
        ])->whereIn('status', ['approved', 'repair_only']);

        if ($status !== 'all') {
            $query->where('repair_status', $status);
        }

        $this->applyHousekeepingIssueScope($query);

        $issues = $query->orderByRaw("CASE WHEN repair_status = 'waiting' THEN 0 ELSE 1 END")
            ->latest('reviewed_at')->paginate(15)->withQueryString();

        return view('admin.pages.room-repairs.index', compact('issues', 'status'));
    }

    public function repairShow(RoomIssueRequest $roomIssueRequest)
    {
        $this->guardHousekeeping();
        abort_unless(in_array($roomIssueRequest->status, ['approved', 'repair_only'], true), 404);

        $roomIssueRequest->load([
            'booking', 'currentRoom.category', 'approvedRoom.category', 'attachments',
            'reviewer', 'repairCompleter',
        ]);
        $this->guardCanHandleIssue($roomIssueRequest);

        return view('admin.pages.room-repairs.show', compact('roomIssueRequest'));
    }

    public function completeRepair(Request $request, RoomIssueRequest $roomIssueRequest)
    {
        $this->guardHousekeeping();
        $roomIssueRequest->loadMissing('currentRoom');
        $this->guardCanHandleIssue($roomIssueRequest);

        $data = $request->validate([
            'repair_note' => ['required', 'string', 'max:2000'],
        ], ['repair_note.required' => 'Vui lòng ghi nội dung đã sửa.']);

        if (!in_array($roomIssueRequest->status, ['approved', 'repair_only'], true)) {
            return back()->with('error', 'Yêu cầu chưa được quản lý duyệt.');
        }
        if ($roomIssueRequest->repair_status === 'completed') {
            return back()->with('error', 'Phòng này đã được xác nhận sửa xong.');
        }

        DB::transaction(function () use ($roomIssueRequest, $data) {
            $issue = RoomIssueRequest::whereKey($roomIssueRequest->id)->lockForUpdate()->firstOrFail();
            $issue->load(['booking.bookingRooms', 'currentRoom']);
            $this->guardCanHandleIssue($issue);

            if (!in_array($issue->status, ['approved', 'repair_only'], true) || $issue->repair_status !== 'waiting') {
                throw new \RuntimeException('Yêu cầu sửa phòng đã thay đổi trạng thái hoặc đã được xử lý.');
            }

            $oldRoom = Room::lockForUpdate()->findOrFail($issue->current_room_id);

            $guestStillUsesOldRoom = $issue->booking
                && in_array($issue->booking->status, ['checked_in', 'inspection_requested'], true)
                && $issue->booking->bookingRooms->contains('room_id', $oldRoom->id);

            $nextStatus = $guestStillUsesOldRoom ? 'occupied' : 'available';
            $oldRoom->update([
                'status' => $nextStatus,
                'status_from' => null,
                'status_until' => null,
                'note' => $guestStillUsesOldRoom
                    ? 'Đã khắc phục sự cố; khách vẫn đang sử dụng phòng.'
                    : null,
            ]);

            $issue->update([
                'repair_status' => 'completed',
                'repair_completed_by' => Auth::id(),
                'repair_completed_at' => now(),
                'repair_note' => $data['repair_note'],
            ]);

            RoomActionLog::create([
                'room_id' => $oldRoom->id,
                'user_id' => Auth::id(),
                'action_type' => 'maintenance_support',
                'action_time' => now(),
                'note' => 'Đã khắc phục xong sự cố. ' . $data['repair_note']
                    . '. Trạng thái phòng sau xử lý: ' . ($nextStatus === 'available' ? 'trống' : 'đang ở') . '.',
            ]);

            if ($issue->booking) {
                BookingLog::create([
                    'booking_id' => $issue->booking_id,
                    'user_id' => Auth::id(),
                    'action' => 'room_issue_repair_completed',
                    'description' => 'Buồng phòng xác nhận đã sửa xong phòng ' . $oldRoom->room_number
                        . '. ' . $data['repair_note'],
                ]);
            }
        });

        return redirect()->route('admin.room-repairs.show', $roomIssueRequest)
            ->with('success', 'Đã xác nhận sửa xong và cập nhật trạng thái phòng.');
    }

    private function guardManager(): void
    {
        abort_unless(in_array(Auth::user()?->role, ['super_admin', 'manager'], true), 403, 'Chỉ quản lý được duyệt sự cố phòng.');
    }

    private function guardHousekeeping(): void
    {
        abort_unless(in_array(Auth::user()?->role, ['super_admin', 'manager', 'housekeeping_supervisor', 'housekeeping'], true), 403);
    }

    private function applyHousekeepingIssueScope($query): void
    {
        $user = Auth::user();

        if ($user && !in_array($user->role, ['super_admin', 'manager', 'housekeeping_supervisor', 'housekeeping'], true)) {
            abort(403, 'Bạn không có quyền xem danh sách phòng cần sửa.');
        }

        HousekeepingWorkScope::applyToIssues($query, $user);
    }

    private function guardCanHandleIssue(RoomIssueRequest $issue): void
    {
        $user = Auth::user();

        if (!$user || in_array($user->role, ['super_admin', 'manager', 'housekeeping_supervisor'], true)) {
            return;
        }

        if ($user->role !== 'housekeeping') {
            abort(403, 'Bạn không có quyền xử lý phòng này.');
        }

        $issue->loadMissing('currentRoom');
        $room = $issue->currentRoom;
        abort_unless($room, 404, 'Không tìm thấy phòng của yêu cầu sự cố.');

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

    private function guardIssueViewer(?RoomIssueRequest $issue): void
    {
        $role = Auth::user()?->role;
        abort_unless(in_array($role, [
            'super_admin', 'manager', 'receptionist_lead', 'receptionist',
            'housekeeping_supervisor', 'housekeeping',
        ], true), 403);

        if ($role === 'housekeeping') {
            abort_unless($issue, 404);
            $this->guardCanHandleIssue($issue);
            return;
        }

        if ($role === 'receptionist') {
            abort_unless($issue?->booking && $issue->booking->canBeHandledBy(Auth::user()), 403,
                'Bạn không được phân công xử lý booking này.');
        }
    }
}
