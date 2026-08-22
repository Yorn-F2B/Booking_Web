<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomIssueRequest;
use App\Models\RoomIssueRoomHold;
use Illuminate\Support\Collection;

class RoomIssueProposalService
{
    public const HOLD_MINUTES = 30; // fallback

    /**
     * Tạo hoặc làm mới phương án cho toàn bộ nhóm sự cố.
     *
     * Phương án đổi phòng đã được giữ còn hiệu lực sẽ được giữ nguyên và gia hạn
     * thêm 30 phút. Chỉ khi phòng giữ cũ hết hạn/không tồn tại hệ thống mới dò lại
     * theo thứ tự: cùng hạng -> nâng hạng -> giữ nguyên sửa gấp.
     */
    public function prepareGroup(
        Booking $booking,
        Collection $issues,
        ?int $actorId,
        string $workflowStatus,
        bool $resetGuestResponse = true
    ): array {
        $booking->loadMissing(['bookingRooms.room.category']);

        $now = now('Asia/Ho_Chi_Minh');
        $holdMinutes = max(5, (int) app(HotelPolicyService::class)->forBooking($booking, 'room_issue.proposal_hold_minutes', self::HOLD_MINUTES));
        $expiresAt = $now->copy()->addMinutes($holdMinutes);
        $usedTargetRoomIds = [];
        $items = [];

        foreach ($issues as $sourceIssue) {
            $issue = RoomIssueRequest::whereKey($sourceIssue->id)
                ->lockForUpdate()
                ->firstOrFail();
            $issue->loadMissing(['currentRoom.category', 'currentCategory', 'proposedRoom.category']);

            $proposal = $this->existingHeldProposal($issue, $now, $usedTargetRoomIds);

            if (!$proposal) {
                $this->releaseIssueHolds($issue, $now, 'Phương án cũ hết hiệu lực, hệ thống tự chọn lại');
                $proposal = $this->resolveAutomaticProposal($issue, $booking, $usedTargetRoomIds);
            }

            $targetRoom = $proposal['room'];
            if ($targetRoom) {
                $usedTargetRoomIds[] = (int) $targetRoom->id;

                $hold = RoomIssueRoomHold::where('group_uuid', $issue->group_uuid)
                    ->where('room_issue_request_id', $issue->id)
                    ->where('room_id', $targetRoom->id)
                    ->whereNull('released_at')
                    ->lockForUpdate()
                    ->first();

                if ($hold) {
                    $hold->update([
                        'expires_at' => $expiresAt,
                        'release_reason' => null,
                    ]);
                } else {
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
            } else {
                $this->releaseIssueHolds($issue, $now, 'Không có phòng thay thế để tiếp tục giữ');
            }

            $updates = [
                'workflow_status' => $workflowStatus,
                'proposed_resolution_type' => $proposal['type'],
                'proposed_room_id' => $targetRoom?->id,
                'proposed_room_category_id' => $targetRoom?->room_category_id,
                'proposal_note' => null,
                'proposal_created_by' => $actorId,
                'proposal_created_at' => $now,
                'proposal_expires_at' => $targetRoom ? $expiresAt : null,
            ];

            if ($resetGuestResponse) {
                $preservedGuestChoice = $issue->guest_selected_resolution_type;
                $allowedGuestChoices = ['repair_only'];
                if ($targetRoom && in_array($proposal['type'], ['same_category', 'upgrade_category'], true)) {
                    $allowedGuestChoices[] = $proposal['type'];
                }

                if (!in_array($preservedGuestChoice, $allowedGuestChoices, true)) {
                    $preservedGuestChoice = null;
                }

                $updates += [
                    'guest_response' => null,
                    // Giữ lựa chọn trước đó làm mặc định khi gửi lại, nhưng vẫn giữ
                    // toàn bộ phương án khả dụng để khách có thể đổi ý.
                    'guest_selected_resolution_type' => $preservedGuestChoice,
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

    public function resolveAutomaticProposal(
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

        if (!$to) {
            return $this->repairOnlyProposal(
                'Booking không có thời gian trả phòng hợp lệ để giữ phòng thay thế.'
            );
        }

        $sameCategoryRoom = Room::with('category')
            ->where('room_category_id', $issue->current_room_category_id)
            ->whereNotIn('id', $excludedRoomIds)
            ->availableForPeriod($from, $to, $booking->id)
            ->orderBy('floor_number')
            ->orderBy('room_number')
            ->first();

        if ($sameCategoryRoom) {
            return [
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
            ->availableForPeriod($from, $to, $booking->id)
            ->get()
            ->sortBy(fn ($room) => [
                (float) ($room->category?->price ?? PHP_INT_MAX),
                (int) $room->floor_number,
                (string) $room->room_number,
            ])
            ->first();

        if ($upgradeRoom) {
            return [
                'type' => 'upgrade_category',
                'room' => $upgradeRoom,
                'label' => 'Nâng hạng miễn phí',
                'description' => 'Hết phòng cùng hạng; còn phòng ' . $upgradeRoom->room_number
                    . ' thuộc hạng ' . ($upgradeRoom->category?->name ?? '---') . '.',
            ];
        }

        return $this->repairOnlyProposal(
            'Không còn phòng cùng hạng hoặc hạng cao hơn phù hợp đến hết thời gian lưu trú.'
        );
    }

    private function existingHeldProposal(
        RoomIssueRequest $issue,
        $now,
        array $usedTargetRoomIds
    ): ?array {
        if (!in_array($issue->proposed_resolution_type, ['same_category', 'upgrade_category'], true)
            || !$issue->proposed_room_id
            || in_array((int) $issue->proposed_room_id, array_map('intval', $usedTargetRoomIds), true)) {
            return null;
        }

        $hold = RoomIssueRoomHold::where('group_uuid', $issue->group_uuid)
            ->where('room_issue_request_id', $issue->id)
            ->where('room_id', $issue->proposed_room_id)
            ->whereNull('released_at')
            ->where('expires_at', '>', $now)
            ->lockForUpdate()
            ->first();

        if (!$hold) {
            return null;
        }

        $room = Room::with('category')->find($issue->proposed_room_id);
        if (!$room) {
            return null;
        }

        return [
            'type' => $issue->proposed_resolution_type,
            'room' => $room,
            'label' => $issue->proposed_resolution_type === 'same_category'
                ? 'Đổi phòng cùng hạng'
                : 'Nâng hạng miễn phí',
            'description' => 'Giữ nguyên phương án phòng đã được hệ thống khóa trước đó.',
        ];
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
