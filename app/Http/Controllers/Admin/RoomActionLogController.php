<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomActionLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RoomActionLogController extends Controller
{
    public function getLogsByDate(Request $request, Room $room)
    {
        $date = $request->input('date', now('Asia/Ho_Chi_Minh')->toDateString());

        // ── Action Logs ──────────────────────────────────────────────
        $logs = $room->actionLogs()
            ->whereDate('action_time', $date)
            ->with('user.staff')
            ->orderBy('action_time', 'desc')
            ->get();

        $currentUser = Auth::user();

        $logsData = $logs->map(function ($log) use ($currentUser) {
            $roleLabel = 'Không rõ';
            if ($log->user) {
                $roleLabel = match ($log->user->role) {
                    'super_admin'             => 'Admin',
                    'manager'                 => 'Quản lý',
                    'receptionist_lead'       => 'Trưởng Lễ tân',
                    'receptionist'            => 'Lễ tân',
                    'housekeeping_supervisor' => 'Trưởng Buồng phòng',
                    'housekeeping'            => 'Nhân viên Buồng phòng',
                    default                   => $log->user->role,
                };
            }

            $canEdit = false;
            if ($currentUser && in_array($currentUser->role, ['super_admin', 'manager'])) {
                $canEdit = true;
            } elseif ($currentUser && $currentUser->role === 'receptionist_lead' && in_array($log->action_type, ['check_in', 'check_out', 'status_change'])) {
                $canEdit = true;
            } elseif ($currentUser && $currentUser->role === 'housekeeping_supervisor' && in_array($log->action_type, ['cleaning', 'status_change'])) {
                $canEdit = true;
            }

            return [
                'id'          => $log->id,
                'action_type' => $log->action_type,
                'action_time' => Carbon::parse($log->action_time)->setTimezone('Asia/Ho_Chi_Minh')->format('H:i'),
                'note'        => $log->note,
                'user_name'   => $log->user?->staff?->full_name ?? $log->user?->name ?? 'Hệ thống',
                'user_role'   => $roleLabel,
                'can_edit'    => $canEdit,
            ];
        });

        // ── Bookings overlapping this date ────────────────────────────
        $dayStart = Carbon::parse($date . ' 00:00:00', 'Asia/Ho_Chi_Minh');
        $dayEnd   = Carbon::parse($date . ' 23:59:59', 'Asia/Ho_Chi_Minh');

        $bookingStatusLabels = [
            'pending'              => 'Chờ xác nhận',
            'confirmed'            => 'Đã xác nhận',
            'checked_in'           => 'Đã check-in',
            'inspection_requested' => 'Yêu cầu kiểm tra',
            'checked_out'          => 'Đã trả phòng',
            'completed'            => 'Hoàn thành',
            'cancelled'            => 'Đã hủy',
        ];

        $bookings = $room->bookingRooms()
            ->with(['booking.customer'])
            ->whereHas('booking', function ($q) use ($dayStart, $dayEnd) {
                $q->where('check_in_at', '<', $dayEnd)
                  ->where('check_out_at', '>', $dayStart)
                  ->whereNotIn('status', ['cancelled']);
            })
            ->get()
            ->map(function ($br) use ($bookingStatusLabels) {
                $b = $br->booking;
                if (!$b) return null;

                $customer = $b->customer;
                $guestName = $customer
                    ? trim(($customer->last_name ?? '') . ' ' . ($customer->first_name ?? ''))
                    : 'Khách vãng lai';

                return [
                    'id'           => $b->id,
                    'booking_code' => $b->booking_code,
                    'status'       => $b->status,
                    'status_label' => $bookingStatusLabels[$b->status] ?? $b->status,
                    'guest_name'   => $guestName,
                    'check_in_at'  => Carbon::parse($b->check_in_at)->setTimezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
                    'check_out_at' => Carbon::parse($b->check_out_at)->setTimezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
                    'adult_count'  => $b->adult_count ?? 0,
                    'child_count'  => $b->child_count ?? 0,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'success'  => true,
            'logs'     => $logsData,
            'bookings' => $bookings,
        ]);
    }

    public function updateLog(Request $request, RoomActionLog $log)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $canEdit = false;
        if (in_array($user->role, ['super_admin', 'manager'])) {
            $canEdit = true;
        } elseif ($user->role === 'receptionist_lead' && in_array($log->action_type, ['check_in', 'check_out', 'status_change'])) {
            $canEdit = true;
        } elseif ($user->role === 'housekeeping_supervisor' && in_array($log->action_type, ['cleaning', 'status_change'])) {
            $canEdit = true;
        }

        if (!$canEdit) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền sửa nhật ký này.'], 403);
        }

        $request->validate([
            'note' => 'required|string|max:1000'
        ]);

        $log->update([
            'note' => $request->input('note')
        ]);

        return response()->json(['success' => true, 'message' => 'Cập nhật nhật ký thành công.']);
    }
}
