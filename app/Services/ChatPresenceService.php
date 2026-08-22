<?php

namespace App\Services;

use App\Models\ChatStaffPresence;
use App\Models\User;
use Illuminate\Support\Collection;

class ChatPresenceService
{
    public const ONLINE_TTL_SECONDS = 120;

    public function isEligible(User $user): bool
    {
        return ($user->status ?? null) === 'active'
            && in_array($user->role, ['receptionist', 'receptionist_lead'], true);
    }

    public function markOnline(User $user): ?ChatStaffPresence
    {
        if (!$this->isEligible($user)) {
            return null;
        }

        return ChatStaffPresence::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'online',
                'last_seen_at' => now(),
            ]
        );
    }

    public function heartbeat(User $user): ?ChatStaffPresence
    {
        if (!$this->isEligible($user)) {
            return null;
        }

        $presence = ChatStaffPresence::query()->firstOrNew(['user_id' => $user->id]);

        if (!$presence->exists) {
            $presence->status = 'online';
        }

        $presence->last_seen_at = now();
        $presence->save();

        return $presence;
    }

    public function setStatus(User $user, string $status): ChatStaffPresence
    {
        abort_unless($this->isEligible($user), 422, 'Nhân viên này không thuộc nhóm trực chat.');
        abort_unless(in_array($status, ['online', 'away', 'offline'], true), 422, 'Trạng thái trực chat không hợp lệ.');

        return ChatStaffPresence::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => $status,
                'last_seen_at' => now(),
            ]
        );
    }

    public function markOffline(User $user): ?ChatStaffPresence
    {
        if (!$this->isEligible($user)) {
            return null;
        }

        return $this->setStatus($user, 'offline');
    }

    public function isOnline(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        $presence = ChatStaffPresence::query()->where('user_id', $userId)->first();

        if (!$presence || $presence->status !== 'online' || !$presence->last_seen_at) {
            return false;
        }

        return $presence->last_seen_at->gte(now()->subSeconds(self::ONLINE_TTL_SECONDS));
    }

    public function onlineStaffs(?int $excludeUserId = null): Collection
    {
        $freshSince = now()->subSeconds(self::ONLINE_TTL_SECONDS);

        return User::query()
            ->whereIn('role', ['receptionist', 'receptionist_lead'])
            ->where('status', 'active')
            ->when($excludeUserId, fn ($query) => $query->where('id', '!=', $excludeUserId))
            ->whereHas('chatPresence', function ($query) use ($freshSince) {
                $query->where('status', 'online')
                    ->where('last_seen_at', '>=', $freshSince);
            })
            ->with('chatPresence')
            ->orderBy('id')
            ->get();
    }

    public function statusFor(User $user): string
    {
        $presence = $user->relationLoaded('chatPresence')
            ? $user->chatPresence
            : $user->chatPresence()->first();

        if (!$presence) {
            return 'offline';
        }

        if (
            in_array($presence->status, ['online', 'away'], true)
            && (!$presence->last_seen_at || $presence->last_seen_at->lt(now()->subSeconds(self::ONLINE_TTL_SECONDS)))
        ) {
            return 'offline';
        }

        return $presence->status;
    }
}
