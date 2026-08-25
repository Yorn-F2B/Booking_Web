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

            // Booking chưa check-in: hủy lịch giữ phòng bằng trạng thái booking.
            // Không cập nhật room.status vì đó là trạng thái vận hành tại thời điểm hiện tại.

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
                    ->whereIn('action', ['auto_cancel_no_show', 'cancel_no_show', 'late_arrival_cancelled', 'system_no_show_cancelled'])
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

    /**
     * Hủy booking do khách sạn không thể đáp ứng yêu cầu phòng đã cam kết xử lý.
     * Đây không phải lỗi/hủy tự nguyện của khách: toàn bộ số đã thanh toán phải hoàn lại,
     * không ghi nhận vi phạm hủy/no-show và không áp khóa tài khoản.
     */
    public function cancelForRoomRequestFailure(
        Booking $booking,
        ?int $actorUserId,
        string $action,
        string $actorLabel,
        string $reason
    ): float {
        return DB::transaction(function () use ($booking, $actorUserId, $action, $actorLabel, $reason) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->room_selection_mode !== 'manual'
                || $locked->room_selection_status !== 'awaiting_guest') {
                throw new \RuntimeException('Yêu cầu phòng không còn chờ khách phản hồi.');
            }

            if (!in_array($locked->status, ['pending', 'confirmed'], true) || $locked->actual_check_in) {
                throw new \RuntimeException('Đơn không còn ở trạng thái có thể hủy và hoàn cọc.');
            }

            $refundDue = round((float) BookingPayment::where('booking_id', $locked->id)
                ->where('status', 'success')
                ->sum('amount'), 0);

            BookingServiceItem::where('booking_id', $locked->id)
                ->where('billing_status', 'pending')
                ->update([
                    'billing_status' => 'cancelled',
                    'used_quantity' => 0,
                    'total' => 0,
                    'note' => DB::raw("CONCAT(COALESCE(note, ''), ' | Đã hủy do khách sạn không đáp ứng yêu cầu phòng.')"),
                ]);

            // Booking chưa check-in: hủy lịch giữ phòng bằng trạng thái booking.
            // Không cập nhật room.status vì đó là trạng thái vận hành tại thời điểm hiện tại.

            BookingPayment::where('booking_id', $locked->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'response_code' => 'BOOKING_CANCELLED_HOTEL_REQUEST_FAILURE',
                    'transaction_status' => 'BOOKING_CANCELLED_HOTEL_REQUEST_FAILURE',
                ]);

            $now = now('Asia/Ho_Chi_Minh');
            $locked->forceFill([
                'status' => 'cancelled',
                'payment_expires_at' => null,
                'room_selection_status' => 'fallback_declined',
                'room_selection_fee' => 0,
                'room_selection_guest_decided_at' => $now,
                'refund_due_amount' => $refundDue,
                'refund_status' => $refundDue > 0 ? 'pending' : 'completed',
                'refund_reason' => trim($reason),
                'refund_processed_at' => $refundDue > 0 ? null : $now,
                'refund_processed_by' => null,
                'note' => trim(($locked->note ? $locked->note . "\n" : '')
                    . $now->format('d/m/Y H:i') . ' - ' . $actorLabel
                    . ' từ chối phòng dự phòng do khách sạn không đáp ứng yêu cầu. Booking được hủy không phạt khách.'
                    . ($refundDue > 0
                        ? ' Cần hoàn lại toàn bộ ' . number_format($refundDue, 0, ',', '.') . 'đ đã thanh toán.'
                        : ' Booking chưa phát sinh khoản đã thanh toán nên không có tiền cần hoàn.')),
            ])->save();

            BookingLog::create([
                'booking_id' => $locked->id,
                'user_id' => $actorUserId,
                'action' => $action,
                'description' => $actorLabel . ' từ chối phòng dự phòng vì khách sạn không đáp ứng yêu cầu chọn phòng. '
                    . 'Booking hủy không phạt khách; số tiền cần hoàn: '
                    . number_format($refundDue, 0, ',', '.') . 'đ.',
            ]);

            return $refundDue;
        });
    }

}
