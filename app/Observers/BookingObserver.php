<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\BookingStaffAssignment;
use App\Services\ChatAssignmentService;
use App\Services\OperationalNotificationService;
use App\Services\RoomReservationStatusService;
use App\Support\Realtime;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class BookingObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private ChatAssignmentService $assignmentService,
        private RoomReservationStatusService $roomReservationStatusService
    ) {
    }

    public function created(Booking $booking): void
    {
        $this->assignmentService->ensureAvailableBookingAssignment($booking);
        $this->syncRoomReservationStatus($booking);
        Realtime::booking($booking, 'created');
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('status') && in_array($booking->status, ['checked_out', 'completed', 'cancelled', 'canceled'], true)) {
            BookingStaffAssignment::query()
                ->where('booking_id', $booking->id)
                ->where('status', 'active')
                ->update(['status' => 'done']);
        } elseif (in_array($booking->status, ['pending', 'confirmed', 'checked_in', 'inspection_requested'], true)) {
            $this->assignmentService->ensureAvailableBookingAssignment($booking);
        }

        if ($booking->wasChanged('status')
            || $booking->wasChanged('payment_expires_at')
            || $booking->wasChanged('payment_status')) {
            $this->syncRoomReservationStatus($booking);
        }

        $this->notifyCustomerForOperationalChange($booking);
        Realtime::booking($booking, $this->detectAction($booking));
    }

    public function deleted(Booking $booking): void
    {
        Realtime::booking($booking, 'deleted');
    }

    private function notifyCustomerForOperationalChange(Booking $booking): void
    {
        $actor = auth()->user();
        if (!$actor) {
            return;
        }

        $code = (string) $booking->booking_code;
        $service = app(OperationalNotificationService::class);

        // Khách tự sửa thông tin booking (trước thanh toán/qua các biểu mẫu tự phục vụ)
        // cũng phải nhận đủ 3 phản hồi: toast của request hiện tại, email và thông báo web.
        // Các thay đổi trạng thái/thanh toán được luồng mail/payment/cancellation chuyên biệt
        // xử lý để tránh gửi trùng hai email cho cùng một thao tác.
        if ($actor->role === 'customer') {
            $changes = [];

            if ($booking->wasChanged('check_in_at') || $booking->wasChanged('check_in_date')) {
                $checkIn = $booking->check_in_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')
                    ?: ($booking->check_in_date ? \Carbon\Carbon::parse($booking->check_in_date)->format('d/m/Y') : null);
                $changes[] = 'thời gian nhận phòng' . ($checkIn ? ' thành ' . $checkIn : '');
            }

            if ($booking->wasChanged('check_out_at') || $booking->wasChanged('check_out_date')) {
                $checkOut = $booking->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')
                    ?: ($booking->check_out_date ? \Carbon\Carbon::parse($booking->check_out_date)->format('d/m/Y') : null);
                $changes[] = 'thời gian trả phòng' . ($checkOut ? ' thành ' . $checkOut : '');
            }

            if ($booking->wasChanged('room_quantity')) {
                $changes[] = 'số phòng thành ' . (int) $booking->room_quantity . ' phòng';
            }

            if ($booking->wasChanged('adult_count') || $booking->wasChanged('child_count')) {
                $changes[] = 'số khách thành ' . (int) $booking->adult_count . ' người lớn, '
                    . (int) $booking->child_count . ' trẻ em';
            }

            if ($booking->wasChanged('room_category_id')) {
                $booking->loadMissing('roomCategory');
                $changes[] = 'hạng phòng thành ' . ($booking->roomCategory?->name ?: 'hạng phòng mới');
            }

            if ($booking->wasChanged('room_selection_request')) {
                $changes[] = 'yêu cầu chọn phòng';
            }

            if ($booking->wasChanged('note')) {
                $changes[] = 'ghi chú booking';
            }

            if ($changes !== []) {
                $service->toBookingCustomer(
                    $booking,
                    'Đã ghi nhận cập nhật booking ' . $code,
                    'Hệ thống đã ghi nhận thao tác của bạn: ' . implode('; ', $changes) . '.',
                    'success',
                    null,
                    ['meta' => ['event' => 'booking_details_updated_by_customer']]
                );
            }

            return;
        }

        // Trạng thái là thay đổi quan trọng nhất, dùng thông báo chuyên biệt.
        if ($booking->wasChanged('status')) {
            $content = match ((string) $booking->status) {
                'confirmed' => [
                    'Booking ' . $code . ' đã được xác nhận',
                    'Khách sạn đã xác nhận booking ' . $code . '. Bạn có thể mở chi tiết booking để kiểm tra thời gian lưu trú, phòng và các khoản đã thanh toán.',
                    'success',
                    'booking_confirmed',
                ],
                'checked_in' => [
                    'Đã nhận phòng - booking ' . $code,
                    'Khách sạn đã ghi nhận bạn đã nhận phòng cho booking ' . $code . '.',
                    'success',
                    'booking_checked_in',
                ],
                'inspection_requested' => [
                    'Đang kiểm tra phòng trước khi trả - booking ' . $code,
                    'Khách sạn đã ghi nhận yêu cầu trả phòng của booking ' . $code . ' và đang kiểm tra phòng, dịch vụ và các khoản phát sinh.',
                    'info',
                    'booking_inspection_requested',
                ],
                'checked_out', 'completed' => [
                    'Đã trả phòng - booking ' . $code,
                    'Booking ' . $code . ' đã hoàn tất trả phòng. Cảm ơn bạn đã sử dụng dịch vụ tại MCuong Hotel.',
                    'success',
                    'booking_checked_out',
                ],
                'cancelled', 'canceled' => [
                    'Booking ' . $code . ' đã được hủy',
                    'Khách sạn đã ghi nhận booking ' . $code . ' ở trạng thái đã hủy. Nếu có khoản hoàn tiền, trạng thái xử lý sẽ tiếp tục được cập nhật trên booking.',
                    'warning',
                    'booking_cancelled_by_staff',
                ],
                default => null,
            };

            if ($content) {
                [$title, $message, $type, $event] = $content;
                $service->toBookingCustomer($booking, $title, $message, $type, null, [
                    'meta' => ['event' => $event],
                ]);
                return;
            }
        }

        // Thanh toán/cọc phải có thông báo riêng, vì đây là số tiền khách cần biết.
        if ($booking->wasChanged('payment_status')
            || $booking->wasChanged('deposit_amount')
            || $booking->wasChanged('required_deposit_amount')
            || $booking->wasChanged('overpayment_amount')) {
            $paid = (float) $booking->payments()->where('status', 'success')->sum('amount');
            $required = (float) ($booking->required_deposit_amount ?? 0);
            $service->toBookingCustomer(
                $booking,
                'Đã cập nhật thanh toán - booking ' . $code,
                'Tổng tiền đã thanh toán thành công: ' . number_format($paid, 0, ',', '.') . 'đ.'
                    . ($required > 0 ? ' Mức cọc cần đạt hiện tại: ' . number_format($required, 0, ',', '.') . 'đ.' : ''),
                'success',
                null,
                ['meta' => ['event' => 'booking_payment_updated_by_staff']]
            );
            return;
        }

        // Gom các thay đổi thông tin booking trong cùng một lần save thành một thông báo,
        // tránh spam nhiều email nhưng vẫn nói rõ khách đã bị thay đổi phần nào.
        $changes = [];

        if ($booking->wasChanged('check_in_at') || $booking->wasChanged('check_in_date')) {
            $checkIn = $booking->check_in_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')
                ?: ($booking->check_in_date ? \Carbon\Carbon::parse($booking->check_in_date)->format('d/m/Y') : null);
            $changes[] = 'thời gian nhận phòng' . ($checkIn ? ' thành ' . $checkIn : '');
        }

        if ($booking->wasChanged('check_out_at') || $booking->wasChanged('check_out_date')) {
            $checkOut = $booking->check_out_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y')
                ?: ($booking->check_out_date ? \Carbon\Carbon::parse($booking->check_out_date)->format('d/m/Y') : null);
            $changes[] = 'thời gian trả phòng' . ($checkOut ? ' thành ' . $checkOut : '');
        }

        if ($booking->wasChanged('room_quantity')) {
            $changes[] = 'số phòng thành ' . (int) $booking->room_quantity . ' phòng';
        }

        if ($booking->wasChanged('adult_count') || $booking->wasChanged('child_count')) {
            $changes[] = 'số khách thành ' . (int) $booking->adult_count . ' người lớn, '
                . (int) $booking->child_count . ' trẻ em';
        }

        if ($booking->wasChanged('room_category_id')) {
            $booking->loadMissing('roomCategory');
            $changes[] = 'hạng phòng thành ' . ($booking->roomCategory?->name ?: 'hạng phòng mới');
        }

        if ($booking->wasChanged('room_selection_status')) {
            $selectionLabel = match ((string) $booking->room_selection_status) {
                'fulfilled', 'approved' => 'yêu cầu phòng đã được đáp ứng',
                'rejected', 'unavailable', 'fallback_declined' => 'yêu cầu phòng chưa thể đáp ứng',
                'awaiting_guest' => 'khách sạn đã gửi phương án phòng dự phòng và đang chờ bạn xác nhận',
                'fallback_accepted' => 'phương án phòng dự phòng đã được xác nhận',
                'pending' => 'yêu cầu phòng đang chờ xử lý',
                default => 'trạng thái yêu cầu phòng đã thay đổi',
            };
            $changes[] = $selectionLabel;
        }

        if ($booking->wasChanged('room_selection_request')) {
            $changes[] = 'yêu cầu chọn phòng đã được cập nhật';
        }

        if ($booking->wasChanged('refund_status') || $booking->wasChanged('refund_due_amount')) {
            $refund = number_format((float) ($booking->refund_due_amount ?? 0), 0, ',', '.') . 'đ';
            $refundStatus = match ((string) $booking->refund_status) {
                'pending' => 'đang chờ hoàn',
                'completed' => 'đã hoàn',
                'not_required', 'none', '' => 'không cần hoàn',
                default => (string) $booking->refund_status,
            };
            $changes[] = 'hoàn tiền ' . $refund . ' - ' . $refundStatus;
        }

        if ($booking->wasChanged('estimated_total') || $booking->wasChanged('final_total')
            || $booking->wasChanged('discount_amount') || $booking->wasChanged('room_selection_fee')) {
            $total = (float) ($booking->final_total ?: $booking->estimated_total ?: 0);
            $changes[] = 'tổng tiền booking hiện là ' . number_format($total, 0, ',', '.') . 'đ';
        }

        if ($changes !== []) {
            $service->toBookingCustomer(
                $booking,
                'Booking ' . $code . ' có cập nhật',
                'Khách sạn đã cập nhật: ' . implode('; ', $changes) . '.',
                'info',
                null,
                ['meta' => ['event' => 'booking_details_updated_by_staff']]
            );
        }
    }

    private function syncRoomReservationStatus(Booking $booking): void
    {
        $roomIds = $booking->bookingRooms()
            ->pluck('room_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $this->roomReservationStatusService->syncRoomIds($roomIds);
    }

    private function detectAction(Booking $booking): string
    {
        if ($booking->wasChanged('payment_status')
            || $booking->wasChanged('deposit_amount')
            || $booking->wasChanged('overpayment_amount')) {
            return 'payment_updated';
        }

        if ($booking->wasChanged('status')) {
            return match ($booking->status) {
                'confirmed' => 'confirmed',
                'checked_in' => 'checked_in',
                'inspection_requested' => 'inspection_requested',
                'completed' => 'completed',
                'checked_out' => 'checked_out',
                'cancelled', 'canceled' => 'cancelled',
                default => 'status_updated',
            };
        }

        if ($booking->wasChanged('estimated_total')) {
            return 'total_updated';
        }

        if ($booking->wasChanged('check_out_at') || $booking->wasChanged('check_out_date')) {
            return 'extended';
        }

        if ($booking->wasChanged('note')) {
            return 'note_updated';
        }

        return 'updated';
    }
}
