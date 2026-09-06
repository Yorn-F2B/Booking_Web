<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomIssueRequest;
use App\Models\RoomIssueRoomHold;
use Illuminate\Support\Collection;

class RoomIssueProposalService
{
    public const HOLD_MINUTES = 30;

    public function prepareGroup(
        Booking $booking,
        Collection $issues,
        ?int $actorId,
        string $workflowStatus,
        bool $resetGuestResponse = true,
        array $preferences = []
    ): array {
        $booking->loadMissing(['bookingRooms.room.category']);

        $now = now('Asia/Ho_Chi_Minh');
        $holdMinutes = max(5, (int) app(HotelPolicyService::class)
            ->forBooking($booking, 'room_issue.proposal_hold_minutes', self::HOLD_MINUTES));
        $expiresAt = $now->copy()->addMinutes($holdMinutes);
        $usedTargetRoomIds = [];
        $items = [];

        foreach ($issues as $sourceIssue) {
            $issue = RoomIssueRequest::whereKey($sourceIssue->id)
                ->lockForUpdate()
                ->firstOrFail();
            $issue->loadMissing(['currentRoom.category', 'currentCategory', 'proposedRoom.category']);

            $available = $this->resolveAvailableProposals($issue, $booking, $usedTargetRoomIds);
            $requested = (string) ($preferences[$issue->id] ?? 'auto');

            if ($requested === 'auto') {
                $proposal = $available['same_category']
                    ?? $available['upgrade_category']
                    ?? $available['repair_only']
                    ?? null;
            } else {
                $proposal = $available[$requested] ?? null;
            }

            if (!$proposal) {
                throw new \RuntimeException(
                    'Phương án đã chọn cho phòng ' . ($issue->currentRoom?->room_number ?? $issue->current_room_id)
                    . ' không còn khả dụng. Vui lòng tải lại trang và chọn phương án khác.'
                );
            }

            $this->releaseIssueHolds($issue, $now, 'Quản lý tạo/cập nhật phương án mới');

            $targetRoom = $proposal['room'];
            if ($targetRoom) {
                $usedTargetRoomIds[] = (int) $targetRoom->id;
                RoomIssueRoomHold::create([
                    'group_uuid' => $issue->group_uuid,
                    'room_issue_request_id' => $issue->id,
                    'booking_id' => $booking->id,
                    'room_id' => $targetRoom->id,
                    'held_by' => $actorId,
                    'held_at' => $now,
                    'expires_at' => $expiresAt,
                ]);
            }

            $updates = [
                'workflow_status' => $workflowStatus,
                'proposed_resolution_type' => $proposal['type'],
                'proposed_room_id' => $targetRoom?->id,
                'proposed_room_category_id' => $targetRoom?->room_category_id,
                'proposal_note' => $proposal['description'] ?? null,
                'proposal_created_by' => $actorId,
                'proposal_created_at' => $now,
                'proposal_expires_at' => $targetRoom ? $expiresAt : null,
            ];

            if ($resetGuestResponse) {
                $updates += [
                    'guest_response' => null,
                    'guest_selected_resolution_type' => null,
                    'guest_responded_by' => null,
                    'guest_responded_at' => null,
                ];
            }

            $issue->update($updates);

            $items[] = [
                'issue_id' => (int) $issue->id,
                'current_room_number' => $issue->currentRoom?->room_number,
                'type' => $proposal['type'],
                'label' => $proposal['label'],
                'target_room_id' => $targetRoom?->id,
                'target_room_number' => $targetRoom?->room_number,
            ];
        }

        return [
            'expires_at' => $expiresAt,
            'items' => $items,
        ];
    }

    /**
     * Trả về toàn bộ phương án thực sự khả thi ở thời điểm hiện tại.
     * Một sự cố có thể đồng thời có: đổi cùng hạng, nâng hạng, và ở lại sửa gấp.
     */
    public function resolveAvailableProposals(
        RoomIssueRequest $issue,
        Booking $booking,
        array $usedTargetRoomIds = []
    ): array {
        $issue->loadMissing(['currentRoom.category', 'currentCategory']);
        $booking->loadMissing(['bookingRooms']);

        $excludedRoomIds = array_values(array_unique(array_merge(
            $booking->bookingRooms->pluck('room_id')->map(fn ($id) => (int) $id)->all(),
            array_map('intval', $usedTargetRoomIds)
        )));

        $from = now('Asia/Ho_Chi_Minh');
        $to = $booking->check_out_at;
        $options = [];

        if ($to) {
            $sameCategoryRoom = Room::with('category')
                ->where('room_category_id', $issue->current_room_category_id)
                ->whereNotIn('id', $excludedRoomIds)
                ->bookableForPeriod($from, $to, $booking->id)
                ->orderBy('floor_number')
                ->orderBy('room_number')
                ->first();

            if ($sameCategoryRoom) {
                $options['same_category'] = [
                    'type' => 'same_category',
                    'room' => $sameCategoryRoom,
                    'label' => 'Đổi phòng cùng hạng',
                    'description' => 'Còn phòng ' . $sameCategoryRoom->room_number . ' cùng hạng '
                        . ($sameCategoryRoom->category?->name ?? '---') . '.',
                ];
            }

            $currentPrice = (float) (
                $issue->currentCategory?->price
                ?? $issue->currentRoom?->category?->price
                ?? 0
            );

            $upgradeRoom = Room::with('category')
                ->whereNotIn('id', $excludedRoomIds)
                ->whereHas('category', fn ($query) => $query
                    ->where('status', 'active')
                    ->where('price', '>', $currentPrice))
                ->bookableForPeriod($from, $to, $booking->id)
                ->get()
                ->sortBy(fn ($room) => [
                    (float) ($room->category?->price ?? PHP_INT_MAX),
                    (int) $room->floor_number,
                    (string) $room->room_number,
                ])
                ->first();

            if ($upgradeRoom) {
                $options['upgrade_category'] = [
                    'type' => 'upgrade_category',
                    'room' => $upgradeRoom,
                    'label' => 'Nâng hạng miễn phí',
                    'description' => 'Còn phòng ' . $upgradeRoom->room_number . ' thuộc hạng '
                        . ($upgradeRoom->category?->name ?? '---') . '. Khách sạn chịu phần chênh lệch do sự cố.',
                ];
            }
        }

        if ($issue->housekeeping_can_repair_in_room || empty($options)) {
            $options['repair_only'] = $this->repairOnlyProposal(
                $issue->housekeeping_can_repair_in_room
                    ? 'Buồng phòng xác nhận có thể giữ khách ở phòng hiện tại và sửa gấp.'
                    : 'Không còn phòng cùng hạng hoặc hạng cao hơn phù hợp; tạm giữ khách ở phòng hiện tại và xử lý khẩn.'
            );
        }

        return $options;
    }

    public function resolveAutomaticProposal(
        RoomIssueRequest $issue,
        Booking $booking,
        array $usedTargetRoomIds = []
    ): array {
        $available = $this->resolveAvailableProposals($issue, $booking, $usedTargetRoomIds);

        return $available['same_category']
            ?? $available['upgrade_category']
            ?? $available['repair_only'];
    }

    private function releaseIssueHolds(RoomIssueRequest $issue, $now, string $reason): void
    {
        RoomIssueRoomHold::where('group_uuid', $issue->group_uuid)
            ->where('room_issue_request_id', $issue->id)
            ->whereNull('released_at')
            ->update([
                'released_at' => $now,
                'release_reason' => $reason,
            ]);
    }

    private function repairOnlyProposal(string $description): array
    {
        return [
            'type' => 'repair_only',
            'room' => null,
            'label' => 'Giữ nguyên phòng, sửa gấp',
            'description' => $description,
        ];
    }
}
