<?php

namespace App\Services;

use App\Models\OperationalNotification;
use App\Models\User;

class OperationalNotificationService
{
    public function toUser(int $userId, string $title, string $message, ?string $url = null, string $type = 'info', array $extra = []): OperationalNotification
    {
        return OperationalNotification::create(array_merge($extra, [
            'user_id' => $userId,
            'role' => null,
            'title' => $title,
            'message' => $message,
            'target_url' => $url,
            'type' => $type,
        ]));
    }

    public function toRoles(array $roles, string $title, string $message, ?string $url = null, string $type = 'info', array $extra = []): void
    {
        // Tạo riêng từng thông báo cho từng tài khoản. Không dùng một row chung theo role
        // vì một nhân viên đọc sẽ làm mất chấm đỏ của các nhân viên khác cùng role.
        User::query()->whereIn('role', array_values(array_unique($roles)))
            ->pluck('id')
            ->each(fn ($userId) => $this->toUser((int) $userId, $title, $message, $url, $type, $extra));
    }
}
