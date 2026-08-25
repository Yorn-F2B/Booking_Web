<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStaffAssignment;
use App\Models\ChatAssignmentLog;
use App\Models\ChatConversation;
use App\Models\ChatStaffPresence;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatAssignmentService
{
    private const ACTIVE_BOOKING_STATUSES = [
        'pending',
        'confirmed',
        'checked_in',
        'inspection_requested',
    ];

    private const PRE_ARRIVAL_BOOKING_STATUSES = [
        'pending',
        'confirmed',
    ];

    private const OPEN_CHAT_STATUSES = [
        'waiting',
        'assigned',
        'active',
    ];

    /**
     * Ưu tiên người đã phụ trách cùng khách nếu tải không lệch quá nhiều.
     */
    private int $stickyTolerance = 2;

    public function __construct(private ChatPresenceService $presenceService)
    {
    }

    /**
     * Gán một hội thoại. Booking + chat của cùng một khách được coi là một gói
     * công việc và cố gắng giữ chung một lễ tân.
     */
    public function assign(ChatConversation $conversation, ?int $excludeStaffId = null): ?User
    {
        $staffs = $this->presenceService->onlineStaffs($excludeStaffId);

        if ($staffs->isEmpty()) {
            $this->moveToWaiting($conversation, 'Không còn lễ tân online');
            return null;
        }

        $bundleOwner = $this->findBundleOwnerForConversation($conversation, $staffs);
        if ($bundleOwner) {
            return $this->assignToStaff(
                conversation: $conversation,
                staff: $bundleOwner,
                reason: 'Đồng bộ người phụ trách booking và chat của cùng khách'
            );
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
                reason: 'Gán lại cho lễ tân từng hỗ trợ khách và vẫn đang online'
            );
        }

        $leastLoadedStaff = $this->leastLoadedStaff($staffs, $loads);

        return $this->assignToStaff(
            conversation: $conversation,
            staff: $leastLoadedStaff,
            reason: 'Tự động gán gói khách cho lễ tân online có tải thấp nhất'
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
            // Dù người cũ còn online, vẫn đồng bộ booking của khách về cùng owner.
            $staff = $conversation->assignedStaff;
            if ($staff) {
                $this->syncBookingsForConversation($conversation, $staff, false, null);
            }

            return $staff;
        }

        return $this->assign($conversation, $conversation->assigned_staff_id);
    }

    /**
     * Gán booking cho lễ tân. $manual=true là trường hợp đặc biệt do quản lý
     * chủ động ghim booking; auto-rebalance sẽ không giật các gói này khi owner
     * vẫn còn online.
     */
    public function assignBooking(
        Booking $booking,
        ?User $targetStaff = null,
        bool $manual = false,
        ?int $assignedBy = null,
        ?string $note = null,
        string $reason = 'Tự động phân phối booking'
    ): ?User {
        if (!in_array($booking->status, self::ACTIVE_BOOKING_STATUSES, true)) {
            return null;
        }

        $staffs = $this->presenceService->onlineStaffs();

        if ($targetStaff) {
            abort_unless(
                $this->presenceService->isEligible($targetStaff) && $this->presenceService->isOnline($targetStaff),
                422,
                'Lễ tân được gán phải đang Online.'
            );
            $staff = $targetStaff;
        } else {
            if ($staffs->isEmpty()) {
                $this->clearAutomaticBookingAssignments($booking, true);
                return null;
            }

            $staff = $this->findBundleOwnerForBooking($booking, $staffs)
                ?? $this->leastLoadedStaff($staffs, $this->staffLoads($staffs));
        }

        $this->assignBookingBundleToStaff(
            booking: $booking,
            staff: $staff,
            manual: $manual,
            assignedBy: $manual ? $assignedBy : null,
            note: $note,
            reason: $reason
        );

        return $staff;
    }

    public function ensureAvailableBookingAssignment(Booking $booking): ?User
    {
        if (!in_array($booking->status, self::ACTIVE_BOOKING_STATUSES, true)) {
            return null;
        }

        $assignment = $booking->activeStaffAssignments()
            ->with('staff')
            ->latest('id')
            ->first();

        if (
            $assignment?->staff
            && $this->presenceService->isOnline($assignment->staff)
        ) {
            // Owner còn online: giữ sticky và chỉ đồng bộ chat/sibling booking.
            $this->assignBookingBundleToStaff(
                $booking,
                $assignment->staff,
                $assignment->assigned_by !== null,
                $assignment->assigned_by,
                $assignment->note,
                'Đồng bộ gói khách theo owner hiện tại'
            );

            return $assignment->staff;
        }

        return $this->assignBooking($booking);
    }

    /**
     * Nhân viên mở Show Booking => giữ lock ngắn để soft rebalance không chuyển
     * việc giữa lúc họ đang đọc/nhập dữ liệu.
     */
    public function lockBookingForWork(Booking $booking, User $staff, int $seconds = 600): void
    {
        if ($staff->role !== 'receptionist' || !$booking->isAssignedTo($staff->id)) {
            return;
        }

        Cache::put($this->bookingWorkLockKey($booking->id), $staff->id, now()->addSeconds($seconds));
    }

    /**
     * Bàn giao toàn bộ gói khách của một người. Không chỉ chat: booking active và
     * chat đi cùng nhau để tránh A xử lý đơn nhưng B trả lời chat của cùng khách.
     */
    public function handoffAll(User $fromStaff, string $mode = 'rebalance', ?User $targetStaff = null): int
    {
        abort_unless(in_array($mode, ['target', 'rebalance'], true), 422, 'Kiểu bàn giao không hợp lệ.');

        if ($mode === 'target') {
            abort_unless($targetStaff, 422, 'Vui lòng chọn nhân viên nhận bàn giao.');
            abort_if($targetStaff->id === $fromStaff->id, 422, 'Không thể bàn giao cho chính nhân viên hiện tại.');
            abort_unless($this->presenceService->isOnline($targetStaff), 422, 'Nhân viên nhận bàn giao phải đang online.');
        }

        $bundleKeys = collect();

        BookingStaffAssignment::query()
            ->with('booking.customer')
            ->where('staff_id', $fromStaff->id)
            ->where('status', 'active')
            ->whereHas('booking', fn (Builder $query) => $query->whereIn('status', self::ACTIVE_BOOKING_STATUSES))
            ->get()
            ->each(function (BookingStaffAssignment $assignment) use ($bundleKeys) {
                if ($assignment->booking) {
                    $bundleKeys->push($this->bundleKeyForBooking($assignment->booking));
                }
            });

        ChatConversation::query()
            ->with(['booking.customer'])
            ->where('assigned_staff_id', $fromStaff->id)
            ->whereIn('status', self::OPEN_CHAT_STATUSES)
            ->get()
            ->each(fn (ChatConversation $conversation) => $bundleKeys->push($this->bundleKeyForConversation($conversation)));

        $moved = 0;

        foreach ($bundleKeys->filter()->unique()->values() as $bundleKey) {
            $booking = $this->bookingForBundleKey($bundleKey, $fromStaff->id);
            $conversation = $this->conversationForBundleKey($bundleKey, $fromStaff->id);

            if ($booking) {
                $newOwner = $mode === 'target'
                    ? $targetStaff
                    : $this->chooseStaffExcluding($fromStaff->id);

                if ($newOwner) {
                    $this->assignBookingBundleToStaff(
                        $booking,
                        $newOwner,
                        false,
                        null,
                        null,
                        'Bàn giao tự động vì lễ tân cũ Offline'
                    );
                } else {
                    $this->clearAutomaticBookingAssignments($booking, true);
                    if ($conversation) {
                        $this->moveBundleConversationsToWaiting($conversation, 'Chưa có lễ tân online sau bàn giao');
                    }
                }

                $moved++;
                continue;
            }

            if ($conversation) {
                if ($mode === 'target' && $targetStaff) {
                    $this->assignToStaff($conversation, $targetStaff, 'Bàn giao ca từ ' . $fromStaff->name);
                } else {
                    $this->assign($conversation, $fromStaff->id);
                }
                $moved++;
            }
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

    public function assignUnassignedBookings(int $limit = 100): int
    {
        $ids = Booking::query()
            ->whereIn('status', self::ACTIVE_BOOKING_STATUSES)
            ->whereDoesntHave('activeStaffAssignments')
            ->orderByRaw("CASE WHEN status IN ('checked_in','inspection_requested') THEN 0 ELSE 1 END")
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $assigned = 0;

        foreach ($ids as $bookingId) {
            $booking = Booking::find($bookingId);
            if ($booking && $this->assignBooking($booking)) {
                $assigned++;
            }
        }

        return $assigned;
    }

    /**
     * Khi có lễ tân mới Online, chỉ cân lại các gói an toàn: booking chưa check-in,
     * không ghim thủ công, không đang mở Show Booking và không có chat nóng gần đây.
     */
    public function softRebalance(int $maxMoves = 30): int
    {
        $staffs = $this->presenceService->onlineStaffs();
        if ($staffs->count() < 2) {
            return 0;
        }

        $moves = 0;

        while ($moves < $maxMoves) {
            $loads = $this->staffLoads($staffs);
            $targetStaffId = (int) $loads->sort()->keys()->first();
            $targetLoad = (int) ($loads[$targetStaffId] ?? 0);
            $target = $staffs->firstWhere('id', $targetStaffId);

            if (!$target) {
                break;
            }

            $movedThisRound = false;

            // Không chỉ nhìn người có tải cao nhất. Người cao nhất có thể toàn là
            // booking đã check-in/đang xử lý/được ghim nên không được phép chuyển,
            // trong khi người tải cao thứ hai vẫn có nhiều booking trước check-in
            // có thể chia cho nhân viên vừa vào ca.
            foreach ($loads->sortDesc() as $sourceStaffId => $sourceLoad) {
                $sourceStaffId = (int) $sourceStaffId;
                $sourceLoad = (int) $sourceLoad;
                $loadGap = $sourceLoad - $targetLoad;

                if ($sourceStaffId === $targetStaffId || $loadGap <= 1) {
                    continue;
                }

                $candidate = Booking::query()
                    ->with(['customer', 'activeStaffAssignments'])
                    ->whereIn('status', self::PRE_ARRIVAL_BOOKING_STATUSES)
                    ->whereHas('activeStaffAssignments', fn (Builder $query) => $query
                        ->where('staff_id', $sourceStaffId)
                        ->whereNull('assigned_by'))
                    ->orderByDesc('check_in_at')
                    ->limit(40)
                    ->get()
                    ->first(function (Booking $booking) use ($loadGap) {
                        if (!$this->canSoftMoveBooking($booking)) {
                            return false;
                        }

                        // Chỉ chuyển khi phép chuyển thực sự làm chênh tải nhỏ đi.
                        // Ví dụ nguồn=2, đích=0 và bundle nặng 2 thì chỉ đổi chỗ
                        // 2↔0, không hề cân bằng hơn và dễ gây ping-pong.
                        return $this->bundleWeightForBooking($booking) < $loadGap;
                    });

                if ($candidate) {
                    $this->assignBookingBundleToStaff(
                        $candidate,
                        $target,
                        false,
                        null,
                        null,
                        'Cân bằng tải khi có thêm lễ tân Online'
                    );
                    $moves++;
                    $movedThisRound = true;
                    break;
                }

                // Không còn booking an toàn ở nguồn này: cân các chat thuần
                // (không gắn booking active) nếu hội thoại đã yên ít nhất 5 phút.
                $conversation = ChatConversation::query()
                    ->with('booking.customer')
                    ->withExists([
                        'messages as has_unread_customer' => fn ($query) => $query
                            ->where('sender_type', 'customer')
                            ->where('is_read', false),
                    ])
                    ->where('assigned_staff_id', $sourceStaffId)
                    ->whereIn('status', self::OPEN_CHAT_STATUSES)
                    ->where(function ($query) {
                        $query->whereNull('last_message_at')
                            ->orWhere('last_message_at', '<', now()->subMinutes(5));
                    })
                    ->oldest('last_message_at')
                    ->limit(40)
                    ->get()
                    ->first(function (ChatConversation $conversation) use ($loadGap) {
                        if ($this->matchingActiveBookingsForConversation($conversation)->isNotEmpty()) {
                            return false;
                        }

                        $weight = $conversation->has_unread_customer ? 2 : 1;
                        return $weight < $loadGap;
                    });

                if ($conversation) {
                    $this->assignToStaff(
                        $conversation,
                        $target,
                        'Cân bằng chat khi có thêm lễ tân Online'
                    );
                    $moves++;
                    $movedThisRound = true;
                    break;
                }
            }

            if (!$movedThisRound) {
                break;
            }
        }

        return $moves;
    }

    /**
     * Trình duyệt có thể đóng mà không logout. Heartbeat của người còn online sẽ
     * phát hiện presence quá TTL, chuyển Offline rồi bàn giao cả booking + chat.
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

    public function transfer(
        ChatConversation $conversation,
        User $newStaff,
        string $reason,
        bool $manual = true,
        ?int $assignedBy = null
    ): User {
        abort_unless($this->presenceService->isOnline($newStaff), 422, 'Nhân viên nhận chat phải đang online.');

        return $this->assignToStaff(
            $conversation,
            $newStaff,
            $reason,
            true,
            $manual,
            $manual ? $assignedBy : null
        );
    }

    public function loadFor(int $staffId): int
    {
        $staff = User::find($staffId);
        return $staff ? (int) ($this->staffLoads(collect([$staff]))[$staffId] ?? 0) : 0;
    }

    public function loadsFor(Collection $staffs): Collection
    {
        return $this->staffLoads($staffs);
    }

    private function leastLoadedStaff(Collection $staffs, Collection $loads): User
    {
        return $staffs
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
    }

    private function chooseStaffExcluding(?int $excludeStaffId = null): ?User
    {
        $staffs = $this->presenceService->onlineStaffs($excludeStaffId);
        if ($staffs->isEmpty()) {
            return null;
        }

        return $this->leastLoadedStaff($staffs, $this->staffLoads($staffs));
    }

    /**
     * Tải theo bundle khách, không cộng booking và chat của cùng khách hai lần.
     * Bundle đang lưu trú / có tin chưa đọc được tính nặng hơn booking tương lai.
     */
    private function staffLoads(Collection $staffs): Collection
    {
        $ids = $staffs->pluck('id')->map(fn ($id) => (int) $id)->values();
        $weights = [];

        foreach ($ids as $id) {
            $weights[$id] = [];
        }

        BookingStaffAssignment::query()
            ->with('booking.customer')
            ->whereIn('staff_id', $ids)
            ->where('status', 'active')
            ->whereHas('booking', fn (Builder $query) => $query->whereIn('status', self::ACTIVE_BOOKING_STATUSES))
            ->get()
            ->each(function (BookingStaffAssignment $assignment) use (&$weights) {
                $booking = $assignment->booking;
                if (!$booking) {
                    return;
                }

                $key = $this->bundleKeyForBooking($booking);
                $weight = $this->bookingLoadWeight($booking);

                $staffId = (int) $assignment->staff_id;
                $weights[$staffId][$key] = max($weights[$staffId][$key] ?? 0, $weight);
            });

        ChatConversation::query()
            ->with(['booking.customer'])
            ->withExists([
                'messages as has_unread_customer' => fn ($query) => $query
                    ->where('sender_type', 'customer')
                    ->where('is_read', false),
            ])
            ->whereIn('assigned_staff_id', $ids)
            ->whereIn('status', self::OPEN_CHAT_STATUSES)
            ->get()
            ->each(function (ChatConversation $conversation) use (&$weights) {
                $staffId = (int) $conversation->assigned_staff_id;
                $key = $this->bundleKeyForConversation($conversation);
                $weight = $conversation->has_unread_customer ? 2 : 1;
                $weights[$staffId][$key] = max($weights[$staffId][$key] ?? 0, $weight);
            });

        return $staffs->mapWithKeys(fn ($staff) => [
            $staff->id => array_sum($weights[(int) $staff->id] ?? []),
        ]);
    }

    private function bookingLoadWeight(Booking $booking): int
    {
        return match ($booking->status) {
            'checked_in', 'inspection_requested' => 3,
            'confirmed' => $booking->check_in_at && $booking->check_in_at->lte(now()->addHours(6)) ? 2 : 1,
            'pending' => 2,
            default => 1,
        };
    }

    private function bundleWeightForBooking(Booking $booking): int
    {
        $weight = 1;

        foreach ($this->matchingActiveBookingsForBooking($booking) as $bundleBooking) {
            $weight = max($weight, $this->bookingLoadWeight($bundleBooking));
        }

        $this->matchingOpenConversationsForBooking($booking)
            ->withExists([
                'messages as has_unread_customer' => fn ($query) => $query
                    ->where('sender_type', 'customer')
                    ->where('is_read', false),
            ])
            ->get()
            ->each(function (ChatConversation $conversation) use (&$weight) {
                $weight = max($weight, $conversation->has_unread_customer ? 2 : 1);
            });

        return $weight;
    }

    private function findBundleOwnerForConversation(ChatConversation $conversation, Collection $onlineStaffs): ?User
    {
        $assignments = BookingStaffAssignment::query()
            ->with(['staff', 'booking.customer'])
            ->where('status', 'active')
            ->whereIn('booking_id', $this->matchingActiveBookingsForConversation($conversation)->pluck('id'))
            ->get()
            ->filter(fn (BookingStaffAssignment $assignment) => $assignment->staff && $onlineStaffs->contains('id', $assignment->staff_id))
            ->sortByDesc(function (BookingStaffAssignment $assignment) {
                $manual = $assignment->assigned_by !== null ? 100 : 0;
                $critical = in_array($assignment->booking?->status, ['checked_in', 'inspection_requested'], true) ? 50 : 0;
                return $manual + $critical + (int) $assignment->id / 100000;
            });

        return $assignments->first()?->staff;
    }

    private function findBundleOwnerForBooking(Booking $booking, Collection $onlineStaffs): ?User
    {
        $bundleBookings = $this->matchingActiveBookingsForBooking($booking);

        $assignments = BookingStaffAssignment::query()
            ->with(['staff', 'booking'])
            ->where('status', 'active')
            ->whereIn('booking_id', $bundleBookings->pluck('id'))
            ->get()
            ->filter(fn (BookingStaffAssignment $assignment) => $assignment->staff && $onlineStaffs->contains('id', $assignment->staff_id))
            ->sortByDesc(function (BookingStaffAssignment $assignment) {
                $manual = $assignment->assigned_by !== null ? 100 : 0;
                $critical = in_array($assignment->booking?->status, ['checked_in', 'inspection_requested'], true) ? 50 : 0;
                return $manual + $critical + (int) $assignment->id / 100000;
            });

        if ($assignments->isNotEmpty()) {
            return $assignments->first()->staff;
        }

        $onlineIds = $onlineStaffs->pluck('id');
        $conversation = $this->matchingOpenConversationsForBooking($booking)
            ->whereIn('assigned_staff_id', $onlineIds)
            ->latest('last_message_at')
            ->first();

        return $conversation?->assignedStaff;
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
                    $q->orWhereRaw('LOWER(guest_email) = ?', [Str::lower(trim($conversation->guest_email))]);
                }
            });
        }

        return $query
            ->latest('last_message_at')
            ->latest('updated_at')
            ->first()?->assignedStaff;
    }

    private function assignToStaff(
        ChatConversation $conversation,
        User $staff,
        string $reason,
        bool $syncBundle = true,
        bool $manual = false,
        ?int $assignedBy = null
    ): User {
        $fromStaffId = $conversation->assigned_staff_id;

        if ((int) $fromStaffId !== (int) $staff->id) {
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
        } elseif ($conversation->status !== 'waiting' && $conversation->status !== 'closed') {
            $conversation->update(['status' => 'assigned']);
        }

        ChatStaffPresence::query()
            ->where('user_id', $staff->id)
            ->update(['last_assigned_at' => now()]);

        if ($syncBundle) {
            $this->syncBookingsForConversation($conversation, $staff, $manual, $assignedBy);
        }

        return $staff;
    }

    private function syncBookingsForConversation(
        ChatConversation $conversation,
        User $staff,
        bool $manual,
        ?int $assignedBy
    ): void {
        $booking = $this->matchingActiveBookingsForConversation($conversation)->first();
        if (!$booking) {
            return;
        }

        $this->assignBookingBundleToStaff(
            $booking,
            $staff,
            $manual,
            $manual ? $assignedBy : null,
            null,
            $manual
                ? 'Đồng bộ booking theo thao tác chuyển chat thủ công'
                : 'Đồng bộ booking và chat cùng khách'
        );
    }

    private function assignBookingBundleToStaff(
        Booking $booking,
        User $staff,
        bool $manual,
        ?int $assignedBy,
        ?string $note,
        string $reason
    ): void {
        $bundleBookings = $this->matchingActiveBookingsForBooking($booking);

        DB::transaction(function () use ($bundleBookings, $staff, $manual, $assignedBy, $note) {
            foreach ($bundleBookings as $bundleBooking) {
                $active = BookingStaffAssignment::query()
                    ->where('booking_id', $bundleBooking->id)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->get();

                $same = $active->first(fn ($assignment) => (int) $assignment->staff_id === (int) $staff->id);

                if ($same && $active->count() === 1) {
                    if ($manual) {
                        $same->update([
                            'assigned_by' => $assignedBy,
                            'note' => $note ?? $same->note,
                            'role_in_booking' => 'owner',
                        ]);
                    }
                    continue;
                }

                BookingStaffAssignment::query()
                    ->where('booking_id', $bundleBooking->id)
                    ->where('status', 'active')
                    ->update(['status' => 'canceled']);

                BookingStaffAssignment::create([
                    'booking_id' => $bundleBooking->id,
                    'staff_id' => $staff->id,
                    'role_in_booking' => 'owner',
                    'assigned_by' => $manual ? $assignedBy : null,
                    'status' => 'active',
                    'note' => $manual ? $note : null,
                ]);
            }
        });

        $this->matchingOpenConversationsForBooking($booking)
            ->get()
            ->each(fn (ChatConversation $conversation) => $this->assignToStaff(
                $conversation,
                $staff,
                $reason,
                false,
                $manual,
                $assignedBy
            ));
    }

    private function clearAutomaticBookingAssignments(Booking $booking, bool $includeManual = false): void
    {
        $bookingIds = $this->matchingActiveBookingsForBooking($booking)->pluck('id');
        BookingStaffAssignment::query()
            ->whereIn('booking_id', $bookingIds)
            ->where('status', 'active')
            ->when(!$includeManual, fn ($query) => $query->whereNull('assigned_by'))
            ->update(['status' => 'canceled']);
    }

    private function moveBundleConversationsToWaiting(ChatConversation $conversation, string $reason): void
    {
        $booking = $this->matchingActiveBookingsForConversation($conversation)->first();
        if ($booking) {
            $this->matchingOpenConversationsForBooking($booking)
                ->get()
                ->each(fn (ChatConversation $item) => $this->moveToWaiting($item, $reason));
            return;
        }

        $this->moveToWaiting($conversation, $reason);
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

    private function matchingActiveBookingsForConversation(ChatConversation $conversation): Collection
    {
        $query = Booking::query()->with('customer')->whereIn('status', self::ACTIVE_BOOKING_STATUSES);
        $hasIdentity = false;

        $query->where(function (Builder $scope) use ($conversation, &$hasIdentity) {
            if ($conversation->booking_id) {
                $scope->orWhere('id', $conversation->booking_id);
                $hasIdentity = true;
            }

            if ($conversation->customer_id) {
                $scope->orWhereHas('customer', fn (Builder $customer) => $customer->where('user_id', $conversation->customer_id));
                $hasIdentity = true;
            }

            $email = Str::lower(trim((string) $conversation->guest_email));
            if ($email !== '') {
                $scope->orWhereRaw('LOWER(customer_email_snapshot) = ?', [$email]);
                $hasIdentity = true;
            }
        });

        return $hasIdentity ? $query->orderByRaw("CASE WHEN status IN ('checked_in','inspection_requested') THEN 0 ELSE 1 END")->get() : collect();
    }

    private function matchingActiveBookingsForBooking(Booking $booking): Collection
    {
        $booking->loadMissing('customer');
        $email = Str::lower(trim((string) ($booking->customer_email_snapshot ?: $booking->customer?->email)));
        $userId = $booking->customer?->user_id;
        $customerId = $booking->customer_id;

        return Booking::query()
            ->with('customer')
            ->whereIn('status', self::ACTIVE_BOOKING_STATUSES)
            ->where(function (Builder $query) use ($booking, $email, $userId, $customerId) {
                $query->whereKey($booking->id);
                if ($userId) {
                    $query->orWhereHas('customer', fn (Builder $customer) => $customer->where('user_id', $userId));
                } elseif ($customerId) {
                    $query->orWhere('customer_id', $customerId);
                }
                if ($email !== '') {
                    $query->orWhereRaw('LOWER(customer_email_snapshot) = ?', [$email]);
                }
            })
            ->get();
    }

    private function matchingOpenConversationsForBooking(Booking $booking): Builder
    {
        $booking->loadMissing('customer');
        $email = Str::lower(trim((string) ($booking->customer_email_snapshot ?: $booking->customer?->email)));
        $userId = $booking->customer?->user_id;
        $bundleBookingIds = $this->matchingActiveBookingsForBooking($booking)->pluck('id');

        return ChatConversation::query()
            ->with(['assignedStaff', 'booking.customer'])
            ->whereIn('status', self::OPEN_CHAT_STATUSES)
            ->where(function (Builder $query) use ($bundleBookingIds, $userId, $email) {
                if ($bundleBookingIds->isNotEmpty()) {
                    $query->orWhereIn('booking_id', $bundleBookingIds);
                }
                if ($userId) {
                    $query->orWhere('customer_id', $userId);
                }
                if ($email !== '') {
                    $query->orWhereRaw('LOWER(guest_email) = ?', [$email]);
                }
            });
    }

    private function canSoftMoveBooking(Booking $booking): bool
    {
        if (!in_array($booking->status, self::PRE_ARRIVAL_BOOKING_STATUSES, true)) {
            return false;
        }

        if (Cache::has($this->bookingWorkLockKey($booking->id))) {
            return false;
        }

        $bundleBookings = $this->matchingActiveBookingsForBooking($booking);
        if ($bundleBookings->contains(fn (Booking $item) => in_array($item->status, ['checked_in', 'inspection_requested'], true))) {
            return false;
        }

        $hasPinned = BookingStaffAssignment::query()
            ->whereIn('booking_id', $bundleBookings->pluck('id'))
            ->where('status', 'active')
            ->whereNotNull('assigned_by')
            ->exists();
        if ($hasPinned) {
            return false;
        }

        return !$this->matchingOpenConversationsForBooking($booking)
            ->where('last_message_at', '>=', now()->subMinutes(5))
            ->exists();
    }

    private function bundleKeyForBooking(Booking $booking): string
    {
        $booking->loadMissing('customer');
        if ($booking->customer?->user_id) {
            return 'u:' . (int) $booking->customer->user_id;
        }

        $email = Str::lower(trim((string) ($booking->customer_email_snapshot ?: $booking->customer?->email)));
        if ($email !== '') {
            return 'e:' . $email;
        }

        if ($booking->customer_id) {
            return 'customer:' . (int) $booking->customer_id;
        }

        return 'booking:' . (int) $booking->id;
    }

    private function bundleKeyForConversation(ChatConversation $conversation): string
    {
        if ($conversation->customer_id) {
            return 'u:' . (int) $conversation->customer_id;
        }

        if ($conversation->booking) {
            return $this->bundleKeyForBooking($conversation->booking);
        }

        $email = Str::lower(trim((string) $conversation->guest_email));
        if ($email !== '') {
            return 'e:' . $email;
        }

        return 'conversation:' . (int) $conversation->id;
    }

    private function bookingForBundleKey(string $key, ?int $assignedStaffId = null): ?Booking
    {
        $query = Booking::query()
            ->with('customer')
            ->whereIn('status', self::ACTIVE_BOOKING_STATUSES);

        if ($assignedStaffId) {
            $query->whereHas('activeStaffAssignments', fn (Builder $assignment) => $assignment->where('staff_id', $assignedStaffId));
        }

        if (str_starts_with($key, 'u:')) {
            $userId = (int) substr($key, 2);
            $query->whereHas('customer', fn (Builder $customer) => $customer->where('user_id', $userId));
        } elseif (str_starts_with($key, 'e:')) {
            $query->whereRaw('LOWER(customer_email_snapshot) = ?', [substr($key, 2)]);
        } elseif (str_starts_with($key, 'customer:')) {
            $query->where('customer_id', (int) substr($key, 9));
        } elseif (str_starts_with($key, 'booking:')) {
            $query->whereKey((int) substr($key, 8));
        } else {
            return null;
        }

        return $query
            ->orderByRaw("CASE WHEN status IN ('checked_in','inspection_requested') THEN 0 ELSE 1 END")
            ->orderBy('check_in_at')
            ->first();
    }

    private function conversationForBundleKey(string $key, ?int $assignedStaffId = null): ?ChatConversation
    {
        $query = ChatConversation::query()
            ->with('booking.customer')
            ->whereIn('status', self::OPEN_CHAT_STATUSES);

        if ($assignedStaffId) {
            $query->where('assigned_staff_id', $assignedStaffId);
        }

        if (str_starts_with($key, 'u:')) {
            $query->where('customer_id', (int) substr($key, 2));
        } elseif (str_starts_with($key, 'e:')) {
            $query->whereRaw('LOWER(guest_email) = ?', [substr($key, 2)]);
        } elseif (str_starts_with($key, 'conversation:')) {
            $query->whereKey((int) substr($key, 13));
        } else {
            // Booking/customer legacy key: tìm theo booking matching nếu có.
            $booking = $this->bookingForBundleKey($key, $assignedStaffId);
            if (!$booking) {
                return null;
            }
            return $this->matchingOpenConversationsForBooking($booking)
                ->when($assignedStaffId, fn ($scope) => $scope->where('assigned_staff_id', $assignedStaffId))
                ->latest('last_message_at')
                ->first();
        }

        return $query->latest('last_message_at')->first();
    }

    private function bookingWorkLockKey(int $bookingId): string
    {
        return 'receptionist-working-booking:' . $bookingId;
    }
}
