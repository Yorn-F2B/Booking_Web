<?php

namespace App\Observers;

use App\Models\RoomIssueRequest;
use App\Services\OperationalNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class RoomIssueRequestObserver implements ShouldHandleEventsAfterCommit
{
    public function created(RoomIssueRequest $issue): void
    {
        $this->notify($issue, 'created');
    }

    public function updated(RoomIssueRequest $issue): void
    {
        if (!$issue->wasChanged('status') && !$issue->wasChanged('workflow_status') && !$issue->wasChanged('repair_status')) {
            return;
        }

        $this->notify($issue, 'updated');
    }

    private function notify(RoomIssueRequest $issue, string $phase): void
    {
        $issue->loadMissing(['booking.customer', 'currentRoom', 'approvedRoom', 'proposedRoom']);
        $booking = $issue->booking;
        if (!$booking) {
            return;
        }

        $code = (string) $booking->booking_code;
        $room = (string) ($issue->currentRoom?->room_number ?: ('#' . $issue->current_room_id));
        $workflow = (string) $issue->workflow_status;
        $status = (string) $issue->status;

        if ($phase === 'created') {
            $content = [
                'Đã ghi nhận sự cố phòng ' . $room,
                'Khách sạn đã ghi nhận yêu cầu báo sự cố tại phòng ' . $room . ' của booking ' . $code . '. Bộ phận buồng phòng sẽ kiểm tra và hệ thống sẽ tiếp tục cập nhật kết quả xử lý.',
                'warning',
                'room_issue_created',
            ];
        } else {
            $content = match (true) {
                $status === 'rejected' || $workflow === 'rejected' => [
                    'Cập nhật sự cố phòng ' . $room,
                    'Yêu cầu sự cố tại phòng ' . $room . ' của booking ' . $code . ' chưa được chấp thuận.' . ($issue->admin_note ? ' Lý do: ' . trim((string) $issue->admin_note) : ''),
                    'warning', 'room_issue_rejected',
                ],
                $workflow === 'housekeeping_verified' => [
                    'Đã xác minh sự cố phòng ' . $room,
                    'Bộ phận buồng phòng đã xác minh sự cố tại phòng ' . $room . ' của booking ' . $code . '. Yêu cầu đang được chuyển sang bước xử lý tiếp theo.',
                    'info', 'room_issue_verified',
                ],
                $workflow === 'housekeeping_not_found' => [
                    'Kết quả kiểm tra sự cố phòng ' . $room,
                    'Bộ phận buồng phòng chưa ghi nhận được sự cố như mô tả tại phòng ' . $room . ' của booking ' . $code . '. Khách sạn sẽ tiếp tục trao đổi nếu cần thêm thông tin.',
                    'warning', 'room_issue_not_found',
                ],
                $workflow === 'waiting_guest_confirmation' => [
                    'Có phương án xử lý sự cố phòng ' . $room,
                    'Khách sạn đã đề xuất phương án xử lý sự cố tại phòng ' . $room . ' của booking ' . $code . '. Vui lòng mở chi tiết booking để xem và xác nhận phương án.',
                    'info', 'room_issue_waiting_guest',
                ],
                $status === 'approved' || $workflow === 'approved' => [
                    'Đã duyệt phương án xử lý sự cố phòng ' . $room,
                    'Phương án xử lý sự cố tại phòng ' . $room . ' của booking ' . $code . ' đã được duyệt.' . ($issue->approvedRoom ? ' Phòng thay thế: ' . $issue->approvedRoom->room_number . '.' : ' Khách sạn sẽ xử lý tại phòng hiện tại.'),
                    'success', 'room_issue_approved',
                ],
                $status === 'repair_only' => [
                    'Sự cố phòng ' . $room . ' sẽ được sửa tại phòng',
                    'Khách sạn đã xác nhận xử lý sự cố tại phòng ' . $room . ' của booking ' . $code . ' mà không cần đổi phòng.',
                    'info', 'room_issue_repair_only',
                ],
                $issue->repair_status === 'completed' => [
                    'Đã hoàn tất xử lý sự cố phòng ' . $room,
                    'Khách sạn đã ghi nhận việc sửa chữa/xử lý sự cố tại phòng ' . $room . ' của booking ' . $code . ' đã hoàn tất.',
                    'success', 'room_issue_repair_completed',
                ],
                default => null,
            };
        }

        if (!$content) {
            return;
        }

        [$title, $message, $type, $event] = $content;
        app(OperationalNotificationService::class)->toBookingCustomer(
            $booking,
            $title,
            $message,
            $type,
            null,
            [
                'room_id' => $issue->current_room_id,
                'meta' => ['event' => $event, 'room_issue_request_id' => $issue->id],
            ]
        );
    }
}
