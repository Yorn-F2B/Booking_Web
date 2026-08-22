<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingPayment;
use App\Models\Room;
use App\Services\BookingFinancialService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid';
    protected $description = 'Xử lý booking đang chờ cọc khi hết thời hạn giữ thanh toán';

    public function handle(BookingFinancialService $financials): int
    {
        $ids = Booking::query()
            ->where('status', 'pending')
            ->whereNotNull('payment_expires_at')
            ->where('payment_expires_at', '<=', now('Asia/Ho_Chi_Minh'))
            ->pluck('id');

        $cancelledCount = 0;
        $confirmedCount = 0;
        $manualCount = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $financials, &$cancelledCount, &$confirmedCount, &$manualCount) {
                $booking = Booking::query()
                    ->with('bookingRooms.room')
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if (
                    !$booking
                    || $booking->status !== 'pending'
                    || !$booking->payment_expires_at
                    || now('Asia/Ho_Chi_Minh')->lt($booking->payment_expires_at)
                ) {
                    return;
                }

                BookingPayment::query()
                    ->where('booking_id', $booking->id)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'failed',
                        'response_code' => 'EXPIRED',
                        'transaction_status' => 'EXPIRED',
                    ]);

                // Có thể khách đã trả một phần bằng nhiều giao dịch. Không được
                // chỉ nhìn payment_status=unpaid rồi hủy nhầm booking đã đủ cọc.
                $financials->refreshPaymentStatus($booking);
                $booking->refresh();

                if ($financials->hasMinimumDeposit($booking)) {
                    $roomIds = $booking->bookingRooms->pluck('room_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
                    $allAssignedRoomsStillUsable = $roomIds->count() === max(1, (int) $booking->room_quantity);

                    if ($allAssignedRoomsStillUsable) {
                        $usableCount = Room::query()
                            ->whereIn('id', $roomIds->all())
                            ->availableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id)
                            ->count();
                        $allAssignedRoomsStillUsable = $usableCount === $roomIds->count();
                    }

                    if ($allAssignedRoomsStillUsable) {
                        Room::query()
                            ->whereIn('id', $roomIds->all())
                            ->whereNotIn('status', ['maintenance'])
                            ->update([
                                'status' => 'reserved',
                                'status_from' => now('Asia/Ho_Chi_Minh'),
                                'status_until' => null,
                            ]);

                        $booking->update([
                            'status' => 'confirmed',
                            'payment_expires_at' => null,
                        ]);

                        BookingLog::create([
                            'booking_id' => $booking->id,
                            'user_id' => null,
                            'action' => 'payment_hold_recovered',
                            'description' => 'Hết hạn link thanh toán nhưng tổng giao dịch thành công đã đủ mức cọc. Hệ thống giữ booking và xác nhận lại phòng.',
                        ]);
                        $confirmedCount++;
                        return;
                    }

                    // Tiền đã đủ cọc nhưng phòng không còn an toàn để tự xác nhận:
                    // tuyệt đối không hủy/mất tiền của khách. Chuyển sang lễ tân xử lý.
                    $booking->update([
                        'payment_expires_at' => null,
                        'note' => trim(($booking->note ? $booking->note . "\n" : '')
                            . now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i')
                            . ' - Đã đủ cọc nhưng phòng gán trước đó không còn khả dụng. Cần lễ tân gán lại phòng.'),
                    ]);

                    BookingLog::create([
                        'booking_id' => $booking->id,
                        'user_id' => null,
                        'action' => 'payment_hold_paid_needs_room',
                        'description' => 'Hết hạn link thanh toán; booking đã đủ cọc nên không bị hủy nhưng cần lễ tân kiểm tra/gán lại phòng.',
                    ]);
                    $manualCount++;
                    return;
                }

                $booking->update([
                    'status' => 'cancelled',
                    'payment_expires_at' => null,
                ]);

                // Booking tạo tại quầy có thể đã đặt room.status = reserved trong
                // thời gian chờ VNPay. Khi link hết hạn phải mở phòng lại, nhưng
                // không được mở nếu phòng còn một booking active khác.
                foreach ($booking->bookingRooms as $bookingRoom) {
                    $room = $bookingRoom->room;
                    if (!$room || !in_array($room->status, ['reserved', 'occupied'], true)) {
                        continue;
                    }

                    $hasOtherActiveBooking = DB::table('booking_rooms')
                        ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
                        ->where('booking_rooms.room_id', $room->id)
                        ->where('bookings.id', '!=', $booking->id)
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
                        'status_from' => $hasOtherActiveBooking ? now('Asia/Ho_Chi_Minh') : null,
                        'status_until' => null,
                    ]);
                }

                $paid = $financials->paidTotal($booking);
                $requiredDeposit = $financials->requiredDeposit($booking);
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'user_id' => null,
                    'action' => 'payment_hold_expired',
                    'description' => 'Booking tự hủy vì hết thời hạn giữ cọc và tổng đã thanh toán '
                        . number_format($paid, 0, ',', '.') . 'đ chưa đạt mức cọc '
                        . number_format($requiredDeposit, 0, ',', '.') . 'đ. Phòng đã được đồng bộ lại trạng thái.',
                ]);
                $cancelledCount++;
            });
        }

        $this->info(
            'Đã xử lý ' . $ids->count() . ' booking hết hạn: '
            . $cancelledCount . ' hủy, '
            . $confirmedCount . ' giữ/xác nhận vì đã đủ cọc, '
            . $manualCount . ' cần lễ tân gán lại phòng.'
        );

        return self::SUCCESS;
    }
}
