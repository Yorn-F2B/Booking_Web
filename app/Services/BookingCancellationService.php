<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingPayment;
use App\Models\BookingServiceItem;
use Illuminate\Support\Facades\DB;

class BookingCancellationService
{
    public function __construct(private readonly BookingFinancialService $financials)
    {
    }

    public function cancel(Booking $booking, array $policy, ?int $actorUserId, string $action, string $actorLabel): float
    {
        return DB::transaction(function () use ($booking, $policy, $actorUserId, $action, $actorLabel) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (!in_array($locked->status, ['pending', 'confirmed'], true) || $locked->actual_check_in) {
                throw new \RuntimeException('Đơn không còn ở trạng thái có thể hủy.');
            }

            $creditAmount = 0; // Chính sách mới: cọc đã thanh toán bị giữ, không bảo lưu.

            BookingServiceItem::where('booking_id', $locked->id)
                ->where('billing_status', 'pending')
                ->update([
                    'billing_status' => 'cancelled',
                    'used_quantity' => 0,
                    'total' => 0,
                    'note' => DB::raw("CONCAT(COALESCE(note, ''), ' | Đã hủy cùng booking trước khi nhận phòng.')"),
                ]);

            $locked->load('bookingRooms.room');
            foreach ($locked->bookingRooms as $bookingRoom) {
                $room = $bookingRoom->room;
                if (!$room || !in_array($room->status, ['reserved', 'occupied'], true)) {
                    continue;
                }

                $hasOtherActiveBooking = DB::table('booking_rooms')
                    ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
                    ->where('booking_rooms.room_id', $room->id)
                    ->where('bookings.id', '!=', $locked->id)
                    ->whereNull('bookings.deleted_at')
                    ->where(function ($query) {
                        $query->whereIn('bookings.status', ['confirmed', 'checked_in', 'inspection_requested'])
                            ->orWhere(function ($pending) {
                                $pending->where('bookings.status', 'pending')
                                    ->whereNotNull('bookings.payment_expires_at')
                                    ->where('bookings.payment_expires_at', '>', now('Asia/Ho_Chi_Minh'));
                            });
                    })
                    ->exists();

                $room->update([
                    'status' => $hasOtherActiveBooking ? 'reserved' : 'available',
                    'status_from' => $hasOtherActiveBooking ? $room->status_from : null,
                    'status_until' => $hasOtherActiveBooking ? $room->status_until : null,
                ]);
            }

            BookingPayment::where('booking_id', $locked->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'response_code' => 'BOOKING_CANCELLED',
                    'transaction_status' => 'BOOKING_CANCELLED',
                ]);

            $locked->update([
                'status' => 'cancelled',
                'payment_expires_at' => null,
                'note' => trim(($locked->note ? $locked->note . "\n" : '')
                    . now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                    . ' - ' . $actorLabel . ' xác nhận hủy booking. ' . ($policy['label'] ?? 'Theo chính sách hủy') . '.'
                    . ' Tiền cọc đã thanh toán không hoàn lại và không bảo lưu.'),
            ]);

            $customerUser = $locked->customer?->user;
            if (config('account_restrictions.enabled', false) && $customerUser) {
                $cancel30 = Booking::query()
                    ->where('customer_id', $locked->customer_id)
                    ->where('status', 'cancelled')
                    ->where('updated_at', '>=', now()->subDays(30))
                    ->count();

                $noShow90 = BookingLog::query()
                    ->whereHas('booking', fn ($q) => $q->where('customer_id', $locked->customer_id))
                    ->whereIn('action', ['auto_cancel_no_show', 'cancel_no_show', 'late_arrival_cancelled'])
                    ->where('created_at', '>=', now()->subDays(90))
                    ->count();

                if ($noShow90 >= 3) {
                    $customerUser->update([
                        'status' => 'banned',
                        'booking_locked_until' => null,
                        'booking_lock_reason' => 'Tự động cấm do có từ 3 lần no-show trong 90 ngày.',
                    ]);
                } elseif ($cancel30 >= 3) {
                    $customerUser->update([
                        'booking_locked_until' => now()->addDays(7),
                        'booking_lock_reason' => 'Tạm khóa 7 ngày do hủy từ 3 booking trong 30 ngày.',
                    ]);
                }
            }

            BookingLog::create([
                'booking_id' => $locked->id,
                'user_id' => $actorUserId,
                'action' => $action,
                'description' => $actorLabel . ' xác nhận hủy booking. '
                    . ($policy['label'] ?? 'Theo chính sách hủy')
                    . '. Tiền giữ lại: ' . number_format((float) ($policy['forfeit_amount'] ?? 0), 0, ',', '.')
                    . 'đ; không hoàn lại, không bảo lưu.',
            ]);

            return $creditAmount;
        });
    }
}
