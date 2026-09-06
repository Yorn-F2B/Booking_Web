<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\CustomerRequest;
use App\Models\Room;
use App\Models\RoomInspection;
use App\Models\RoomIssueRequest;
use App\Models\User;
use App\Support\HousekeepingWorkScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Số việc đang mở dùng chung cho badge/sidebar và Trung tâm công việc.
 *
 * Việc đã đọc notification không ảnh hưởng số này; task chỉ biến mất khi trạng
 * thái nghiệp vụ thực sự đã được xử lý.
 */
class OperationCenterService
{
    public function taskCount(User $user): int
    {
        $role = (string) $user->role;
        $now = now('Asia/Ho_Chi_Minh');
        $count = 0;

        $frontDesk = in_array($role, ['super_admin', 'manager', 'receptionist_lead', 'receptionist'], true);
        $management = in_array($role, ['super_admin', 'manager'], true);
        $housekeeping = in_array($role, ['super_admin', 'manager', 'housekeeping_supervisor', 'housekeeping'], true);

        if ($frontDesk) {
            $lateCheckout = Booking::query()
                ->whereIn('status', ['checked_in', 'inspection_requested'])
                ->whereNotNull('check_out_at')
                ->where('check_out_at', '<', $now);
            $this->scopeFrontDeskBookings($lateCheckout, $user, $role);
            $count += $lateCheckout->count();

            $unassigned = Booking::query()
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereBetween('check_in_at', [$now->copy()->subHour(), $now->copy()->addHours(12)])
                ->whereDoesntHave('bookingRooms');
            $this->scopeFrontDeskBookings($unassigned, $user, $role);
            $count += $unassigned->count();

            $upcoming = Booking::query()
                ->where('status', 'confirmed')
                ->whereBetween('check_in_at', [$now, $now->copy()->addHours(4)])
                ->whereHas('bookingRooms');
            $this->scopeFrontDeskBookings($upcoming, $user, $role);
            $count += $upcoming->count();

            $approvedLateArrival = CustomerRequest::query()
                ->where('type', 'late_arrival')
                ->where('status', 'approved')
                ->whereNotNull('expected_arrival_at')
                ->whereBetween('expected_arrival_at', [$now->copy()->subHours(2), $now->copy()->addHours(12)])
                ->whereHas('booking', function (Builder $booking) use ($user, $role) {
                    $booking->whereNotIn('status', ['checked_in', 'checked_out', 'completed', 'cancelled']);
                    $this->scopeFrontDeskBookings($booking, $user, $role);
                });
            $count += $approvedLateArrival->count();

            $pending = Booking::query()
                ->where('status', 'pending')
                ->where(function ($query) {
                    // Booking online chưa thanh toán là luồng tự phục vụ của khách;
                    // không biến toàn bộ chúng thành việc phải click của lễ tân.
                    $query->where('booking_source', '!=', 'user_online')
                        ->orWhere('payment_status', '!=', 'unpaid');
                })
                ->where(function ($query) use ($now) {
                    // Booking chưa gán phòng và sắp đến đã có task ưu tiên riêng.
                    // Không đếm lại cùng booking ở nhóm pending chung.
                    $query->whereHas('bookingRooms')
                        ->orWhere('check_in_at', '<', $now->copy()->subHour())
                        ->orWhere('check_in_at', '>', $now->copy()->addHours(12));
                });
            $this->scopeFrontDeskBookings($pending, $user, $role);
            $count += $pending->count();

            // Badge phải đếm đúng cùng logic với Trung tâm công việc:
            // chỉ còn lỗi thanh toán nếu booking vẫn đang hoạt động và lần thanh toán mới nhất
            // vẫn là pending/failed. Một lần failed cũ không được giữ badge nếu khách đã trả lại thành công.
            $paymentExceptionFilter = function ($q) use ($now) {
                $q->where(function ($exception) use ($now) {
                    $exception->where(fn ($x) => $x->where('status', 'pending')->where('created_at', '<=', $now->copy()->subMinutes(15)))
                        ->orWhere(fn ($x) => $x->where('status', 'failed')->where('updated_at', '>=', $now->copy()->subDay()));
                });
            };
            $paymentExceptionBookings = Booking::query()
                ->whereNotIn('status', ['cancelled', 'canceled', 'checked_out', 'completed'])
                ->where(fn ($q) => $q->whereNull('payment_status')->orWhere('payment_status', '!=', 'paid'))
                ->whereHas('payments', $paymentExceptionFilter)
                ->with(['payments' => fn ($q) => $q->latest('updated_at')]);
            $this->scopeFrontDeskBookings($paymentExceptionBookings, $user, $role);
            $count += $paymentExceptionBookings->get()->filter(function ($booking) use ($now) {
                $payment = $booking->payments->sortByDesc('updated_at')->first();
                if (!$payment || $payment->status === 'success') {
                    return false;
                }
                if ($payment->status === 'pending' && $payment->created_at?->gt($now->copy()->subMinutes(15))) {
                    return false;
                }
                if ($payment->status === 'failed' && $payment->updated_at?->lt($now->copy()->subDay())) {
                    return false;
                }
                return in_array($payment->status, ['pending', 'failed'], true);
            })->count();

        }

        if ($management) {
            $count += Booking::query()
                ->whereIn('status', ['cancelled', 'canceled'])
                ->where('refund_status', 'pending')
                ->where('refund_due_amount', '>', 0)
                ->count();

            $count += CustomerRequest::query()
                ->where('type', 'late_arrival')
                ->where('status', 'pending')
                ->count();

            // Một group_uuid là một sự cố nghiệp vụ. Trang Trung tâm công việc cũng
            // gom theo group_uuid, vì vậy badge không được đếm từng row trong cùng một nhóm.
            $count += RoomIssueRequest::query()
                ->forActiveStay()
                ->needsManagerAction()
                ->select('group_uuid')
                ->distinct()
                ->count('group_uuid');
            $count += Room::query()
                ->where('status', 'maintenance')
                ->whereNotNull('status_until')
                ->where('status_until', '<=', $now)
                ->count();
        }

        if ($housekeeping) {
            $cleaningRooms = Room::query()->where('status', 'cleaning');
            HousekeepingWorkScope::applyToRooms($cleaningRooms, $user);
            $count += $cleaningRooms->count();

            $verification = RoomIssueRequest::query()
                ->forActiveStay()
                ->where('status', 'pending')
                ->where('workflow_status', 'awaiting_housekeeping');
            HousekeepingWorkScope::applyToIssues($verification, $user);
            $count += $verification->count();

            $inspections = RoomInspection::query()->where(function ($query) {
                $query->where(function ($initial) {
                    $initial->where('workflow_stage', RoomInspection::STAGE_HOUSEKEEPING_REPORT)
                        ->whereIn('status', ['pending', 'rejected']);
                })->orWhere(function ($recheck) {
                    $recheck->where('workflow_stage', RoomInspection::STAGE_HOUSEKEEPING_RECHECK)
                        ->where('status', 'reported');
                });
            });
            HousekeepingWorkScope::applyToInspections($inspections, $user);
            $count += $inspections->distinct()->count('booking_id');

            $repairs = RoomIssueRequest::query()
                ->whereIn('status', ['approved', 'repair_only'])
                ->where('repair_status', 'waiting');
            HousekeepingWorkScope::applyToIssues($repairs, $user);
            $count += $repairs->count();
        }

        return $count;
    }

    private function scopeFrontDeskBookings(Builder $query, User $user, string $role): void
    {
        if ($role === 'receptionist') {
            $query->visibleToOperationsUser($user);
        }
    }
}
