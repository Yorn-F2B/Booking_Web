<?php

namespace App\Services;

use App\Models\ChatAssignmentLog;
use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Collection;

class ChatAssignmentService
{
    /**
     * Cho phép ưu tiên nhân viên cũ nếu người đó không nhiều hơn
     * nhân viên ít khách nhất quá 2 cuộc chat.
     */
    private int $stickyTolerance = 2;

    public function assign(ChatConversation $conversation): ?User
    {
        $staffs = $this->availableStaffs();

        if ($staffs->isEmpty()) {
            $conversation->update([
                'status' => 'waiting',
                'assigned_staff_id' => null,
            ]);

            return null;
        }

        $loads = $this->staffLoads($staffs);
        $minLoad = $loads->min() ?? 0;

        $previousStaff = $this->findPreviousStaff($conversation);

        if ($previousStaff && $staffs->contains('id', $previousStaff->id)) {
            $previousLoad = $loads[$previousStaff->id] ?? 0;

            if ($previousLoad <= $minLoad + $this->stickyTolerance) {
                return $this->assignToStaff(
                    conversation: $conversation,
                    staff: $previousStaff,
                    reason: 'Gán lại cho nhân viên từng hỗ trợ khách'
                );
            }
        }

        $leastLoadedStaff = $staffs
            ->sortBy(fn ($staff) => $loads[$staff->id] ?? 0)
            ->first();

        return $this->assignToStaff(
            conversation: $conversation,
            staff: $leastLoadedStaff,
            reason: 'Gán cho nhân viên đang xử lý ít khách nhất'
        );
    }

    private function availableStaffs(): Collection
    {
        return User::query()
            ->whereIn('role', ['receptionist', 'manager'])
            ->where('status', 'active')
            ->orderBy('id')
            ->get();
    }

    private function staffLoads(Collection $staffs): Collection
    {
        return $staffs->mapWithKeys(function ($staff) {
            $count = ChatConversation::query()
                ->where('assigned_staff_id', $staff->id)
                ->whereIn('status', ['assigned', 'active'])
                ->count();

            return [$staff->id => $count];
        });
    }

    private function findPreviousStaff(ChatConversation $conversation): ?User
    {
        $query = ChatConversation::query()
            ->where('id', '!=', $conversation->id)
            ->whereNotNull('assigned_staff_id')
            ->where('updated_at', '>=', now()->subDays(7));

        if ($conversation->customer_id) {
            $query->where('customer_id', $conversation->customer_id);
        } else {
            if (!$conversation->guest_phone && !$conversation->guest_email) {
                return null;
            }

            $query->where(function ($q) use ($conversation) {
                if ($conversation->guest_phone) {
                    $q->orWhere('guest_phone', $conversation->guest_phone);
                }

                if ($conversation->guest_email) {
                    $q->orWhere('guest_email', $conversation->guest_email);
                }
            });
        }

        $previousConversation = $query
            ->latest('last_message_at')
            ->latest('updated_at')
            ->first();

        return $previousConversation?->assignedStaff;
    }

    private function assignToStaff(ChatConversation $conversation, User $staff, string $reason): User
    {
        $fromStaffId = $conversation->assigned_staff_id;

        $conversation->update([
            'assigned_staff_id' => $staff->id,
            'status' => $conversation->status === 'closed' ? 'closed' : 'assigned',
        ]);

        ChatAssignmentLog::create([
            'conversation_id' => $conversation->id,
            'from_staff_id' => $fromStaffId,
            'to_staff_id' => $staff->id,
            'reason' => $reason,
        ]);

        return $staff;
    }
}