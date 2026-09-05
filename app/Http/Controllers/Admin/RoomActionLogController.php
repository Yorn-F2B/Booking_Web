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
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ], [
            'date.date_format' => 'Ngày xem lịch sử phải đúng định dạng Y-m-d.',
        ]);
        $date = $validated['date'] ?? now('Asia/Ho_Chi_Minh')->toDateString();

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
            'canceled'             => 'Đã hủy',
        ];

        $bookings = $room->bookingRooms()
            ->with(['booking.customer', 'booking.staffAssignments'])
            ->whereHas('booking', function ($q) use ($dayStart, $dayEnd) {
                $q->where('check_in_at', '<', $dayEnd)
                  ->where('check_out_at', '>', $dayStart)
                  ->whereNotIn('status', ['cancelled', 'canceled']);
            })
            ->get()
            ->map(function ($br) use ($bookingStatusLabels, $currentUser) {
                $b = $br->booking;
                if (!$b) return null;

                // Lễ tân vẫn cần biết phòng đang bị chiếm trong khung giờ để vận hành,
                // nhưng không được xem PII/mã đơn của booking đã phân cho lễ tân khác.
                $canViewDetails = $currentUser
                    && ($currentUser->role !== 'receptionist'
                        || $b->staffAssignments->contains(function ($assignment) use ($currentUser) {
                            return (int) $assignment->staff_id === (int) $currentUser->id
                                && in_array($assignment->status, ['active', 'done'], true);
                        }));

                $customer = $canViewDetails ? $b->customer : null;
                $guestName = !$canViewDetails
                    ? 'Booking ngoài phạm vi'
                    : ($customer
                        ? trim(($customer->last_name ?? '') . ' ' . ($customer->first_name ?? ''))
                        : 'Khách vãng lai');

                return [
                    'id'           => $canViewDetails ? $b->id : null,
                    'booking_code' => $canViewDetails ? $b->booking_code : null,
                    'status'       => $b->status,
                    'status_label' => $bookingStatusLabels[$b->status] ?? $b->status,
                    'guest_name'   => $guestName,
                    'check_in_at'  => Carbon::parse($b->check_in_at)->setTimezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
                    'check_out_at' => Carbon::parse($b->check_out_at)->setTimezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y'),
                    'adult_count'  => $canViewDetails ? ($b->adult_count ?? 0) : null,
                    'child_count'  => $canViewDetails ? ($b->child_count ?? 0) : null,
                    'baby_count'   => $canViewDetails ? ($b->baby_count ?? 0) : null,
                    'can_view_details' => (bool) $canViewDetails,
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
