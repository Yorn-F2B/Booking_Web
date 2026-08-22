<?php

namespace App\Services;

use App\Models\ChatAssignmentLog;
use App\Models\ChatConversation;
use App\Models\ChatStaffPresence;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatAssignmentService
{
    /**
     * Ưu tiên nhân viên từng hỗ trợ khách nếu tải không vượt quá
     * nhân viên ít khách nhất quá 2 hội thoại.
     */
    private int $stickyTolerance = 2;

    public function __construct(private ChatPresenceService $presenceService)
    {
    }

    public function assign(ChatConversation $conversation, ?int $excludeStaffId = null): ?User
    {
        $staffs = $this->presenceService->onlineStaffs($excludeStaffId);

        if ($staffs->isEmpty()) {
            $this->moveToWaiting($conversation, 'Không còn nhân viên trực chat online');
            return null;
        }

        $loads = $this->staffLoads($staffs);
        $minLoad = $loads->min() ?? 0;
        $previousStaff = $this->findPreviousStaff($conversation);

        if (
            $previousStaff
            && $staffs->contains('id', $previousStaff->id)
            && (($loads[$previousStaff->id] ?? 0) <= $minLoad + $this->stickyTolerance)
        ) {
            return $this->assignToStaff(
                conversation: $conversation,
                staff: $previousStaff,
                reason: 'Gán lại cho nhân viên từng hỗ trợ khách và đang online'
            );
        }

        $leastLoadedStaff = $staffs
            ->sort(function ($a, $b) use ($loads) {
                $loadCompare = ($loads[$a->id] ?? 0) <=> ($loads[$b->id] ?? 0);

                if ($loadCompare !== 0) {
                    return $loadCompare;
                }

                $aAssigned = $a->chatPresence?->last_assigned_at?->getTimestamp() ?? 0;
                $bAssigned = $b->chatPresence?->last_assigned_at?->getTimestamp() ?? 0;

                if ($aAssigned !== $bAssigned) {
                    return $aAssigned <=> $bAssigned;
                }

                return $a->id <=> $b->id;
            })
            ->first();

        return $this->assignToStaff(
            conversation: $conversation,
            staff: $leastLoadedStaff,
            reason: 'Gán cho nhân viên online đang xử lý ít khách nhất'
        );
    }

    public function ensureAvailableAssignment(ChatConversation $conversation): ?User
    {
        if ($conversation->status === 'closed') {
            return $conversation->assignedStaff;
        }

        if (
            $conversation->assigned_staff_id
            && $this->presenceService->isOnline((int) $conversation->assigned_staff_id)
        ) {
            return $conversation->assignedStaff;
        }

        return $this->assign($conversation, $conversation->assigned_staff_id);
    }

    /**
     * Bàn giao toàn bộ hội thoại đang mở của một nhân viên.
     * mode = target: dồn cho một người online.
     * mode = rebalance: chia lại theo tải hiện tại.
     */
    public function handoffAll(User $fromStaff, string $mode = 'rebalance', ?User $targetStaff = null): int
    {
        abort_unless(in_array($mode, ['target', 'rebalance'], true), 422, 'Kiểu bàn giao không hợp lệ.');

        if ($mode === 'target') {
            abort_unless($targetStaff, 422, 'Vui lòng chọn nhân viên nhận bàn giao.');
            abort_if($targetStaff->id === $fromStaff->id, 422, 'Không thể bàn giao cho chính nhân viên hiện tại.');
            abort_unless($this->presenceService->isOnline($targetStaff), 422, 'Nhân viên nhận bàn giao phải đang online.');
        }

        $conversationIds = ChatConversation::query()
            ->where('assigned_staff_id', $fromStaff->id)
            ->whereIn('status', ['waiting', 'assigned', 'active'])
            ->orderBy('id')
            ->pluck('id');

        $moved = 0;

        foreach ($conversationIds as $conversationId) {
            DB::transaction(function () use ($conversationId, $fromStaff, $mode, $targetStaff, &$moved) {
                $conversation = ChatConversation::query()
                    ->lockForUpdate()
                    ->find($conversationId);

                if (!$conversation || (int) $conversation->assigned_staff_id !== (int) $fromStaff->id) {
                    return;
                }

                if ($mode === 'target' && $targetStaff) {
                    $this->assignToStaff(
                        $conversation,
                        $targetStaff,
                        'Bàn giao ca từ ' . $fromStaff->name
                    );
                } else {
                    $this->assign($conversation, $fromStaff->id);
                }

                $moved++;
            });
        }

        return $moved;
    }

    public function assignWaitingConversations(int $limit = 100): int
    {
        $ids = ChatConversation::query()
            ->whereNull('assigned_staff_id')
            ->where('status', 'waiting')
            ->orderByDesc('priority_score')
            ->orderBy('last_message_at')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $assigned = 0;

        foreach ($ids as $conversationId) {
            DB::transaction(function () use ($conversationId, &$assigned) {
                $conversation = ChatConversation::query()->lockForUpdate()->find($conversationId);

                if (!$conversation || $conversation->assigned_staff_id !== null || $conversation->status !== 'waiting') {
                    return;
                }

                if ($this->assign($conversation)) {
                    $assigned++;
                }
            });
        }

        return $assigned;
    }

    /**
     * Trình duyệt có thể bị đóng mà không gửi logout. Khi một nhân viên khác
     * còn online phát heartbeat, các presence Online đã quá TTL được chuyển
     * Offline và toàn bộ chat của họ được chia lại.
     */
    public function handoffStaleOnlineStaff(): int
    {
        $stalePresences = ChatStaffPresence::query()
            ->with('user')
            ->whereIn('status', ['online', 'away'])
            ->where(function ($query) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', now()->subSeconds(ChatPresenceService::ONLINE_TTL_SECONDS));
            })
            ->get();

        $moved = 0;

        foreach ($stalePresences as $presence) {
            $staff = $presence->user;
            if (!$staff || !$this->presenceService->isEligible($staff)) {
                $presence->update(['status' => 'offline']);
                continue;
            }

            $presence->update([
                'status' => 'offline',
                'last_seen_at' => $presence->last_seen_at,
            ]);
            $moved += $this->handoffAll($staff, 'rebalance');
        }

        return $moved;
    }

    public function transfer(ChatConversation $conversation, User $newStaff, string $reason): User
    {
        abort_unless($this->presenceService->isOnline($newStaff), 422, 'Nhân viên nhận chat phải đang online.');

        return $this->assignToStaff($conversation, $newStaff, $reason);
    }

    public function loadFor(int $staffId): int
    {
        return ChatConversation::query()
            ->where('assigned_staff_id', $staffId)
            ->whereIn('status', ['waiting', 'assigned', 'active'])
            ->count();
    }

    public function loadsFor(Collection $staffs): Collection
    {
        return $this->staffLoads($staffs);
    }

    private function staffLoads(Collection $staffs): Collection
    {
        $ids = $staffs->pluck('id');

        $counts = ChatConversation::query()
            ->selectRaw('assigned_staff_id, COUNT(*) as aggregate')
            ->whereIn('assigned_staff_id', $ids)
            ->whereIn('status', ['waiting', 'assigned', 'active'])
            ->groupBy('assigned_staff_id')
            ->pluck('aggregate', 'assigned_staff_id');

        return $staffs->mapWithKeys(fn ($staff) => [
            $staff->id => (int) ($counts[$staff->id] ?? 0),
        ]);
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

        return $query
            ->latest('last_message_at')
            ->latest('updated_at')
            ->first()?->assignedStaff;
    }

    private function assignToStaff(ChatConversation $conversation, User $staff, string $reason): User
    {
        $fromStaffId = $conversation->assigned_staff_id;

        if ((int) $fromStaffId === (int) $staff->id) {
            if ($conversation->status === 'waiting') {
                return $staff;
            }

            $conversation->update([
                'status' => $conversation->status === 'closed' ? 'closed' : 'assigned',
            ]);

            return $staff;
        }

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

        ChatStaffPresence::query()
            ->where('user_id', $staff->id)
            ->update(['last_assigned_at' => now()]);

        return $staff;
    }

    private function moveToWaiting(ChatConversation $conversation, string $reason): void
    {
        $fromStaffId = $conversation->assigned_staff_id;

        if ($fromStaffId === null && $conversation->status === 'waiting') {
            return;
        }

        $conversation->update([
            'status' => 'waiting',
            'assigned_staff_id' => null,
        ]);

        if ($fromStaffId !== null) {
            ChatAssignmentLog::create([
                'conversation_id' => $conversation->id,
                'from_staff_id' => $fromStaffId,
                'to_staff_id' => null,
                'reason' => $reason,
            ]);
        }
    }
}
