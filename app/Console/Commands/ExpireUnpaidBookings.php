<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingPayment;
use App\Models\Room;
use App\Services\BookingFinancialService;
use App\Services\BookingCancellationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid';
    protected $description = 'Xử lý booking đang chờ cọc khi hết thời hạn giữ thanh toán';

    public function handle(BookingFinancialService $financials, BookingCancellationService $cancellations): int
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
            DB::transaction(function () use ($id, $financials, $cancellations, &$cancelledCount, &$confirmedCount, &$manualCount) {
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
                            ->bookableForPeriod($booking->check_in_at, $booking->check_out_at, $booking->id, $booking->cleaning_buffer_minutes)
                            ->count();
                        $allAssignedRoomsStillUsable = $usableCount === $roomIds->count();
                    }

                    if ($allAssignedRoomsStillUsable) {
                        // Đủ cọc chỉ xác nhận booking/lịch giữ phòng. Không đổi room.status
                        // trước check-in vì phòng có thể đang phục vụ nghiệp vụ hiện tại.

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

                // Chưa đủ cọc: đây là booking hết hạn thanh toán, không phải khách
                // chủ động hủy/no-show. Nếu đã thu một phần thì phải tạo refund toàn bộ,
                // đồng thời trả lượt promotion và hủy dịch vụ pending.
                $cancellations->cancelForExpiredPaymentHold($booking);
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
