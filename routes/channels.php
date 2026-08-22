<?php

use App\Models\ChatConversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.realtime', function ($user) {
    return ($user->status ?? null) === 'active' && in_array($user->role, [
        'super_admin',
        'manager',
        'receptionist_lead',
        'receptionist',
        'housekeeping_supervisor',
        'housekeeping',
    ], true);
});

Broadcast::channel('customer.{customerId}', function ($user, $customerId) {
    return ($user->status ?? null) === 'active'
        && $user->role === 'customer'
        && $user->customer
        && (int) $user->customer->id === (int) $customerId;
});

Broadcast::channel('chat.customer.{userId}', function ($user, $userId) {
    return ($user->status ?? null) === 'active'
        && $user->role === 'customer'
        && (int) $user->id === (int) $userId;
});

Broadcast::channel('chat.staff.{userId}', function ($user, $userId) {
    return ($user->status ?? null) === 'active'
        && in_array($user->role, ['receptionist', 'receptionist_lead'], true)
        && (int) $user->id === (int) $userId;
});

Broadcast::channel('chat.supervisors', function ($user) {
    return ($user->status ?? null) === 'active'
        && in_array($user->role, ['super_admin', 'manager', 'receptionist_lead'], true);
});

Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = ChatConversation::find($conversationId);

    if (($user->status ?? null) !== 'active' || !$conversation) {
        return false;
    }

    // Kênh này chỉ dành cho khách. Nhân viên nhận realtime qua kênh staff/supervisor
    // để transfer có hiệu lực ngay với các message phát sau đó.
    return $user->role === 'customer'
        && (int) $conversation->customer_id === (int) $user->id;
});

Broadcast::channel('admin.bookings.supervisors', function ($user) {
    return ($user->status ?? null) === 'active'
        && in_array($user->role, ['super_admin', 'manager', 'receptionist_lead'], true);
});

Broadcast::channel('admin.bookings.unassigned', function ($user) {
    return ($user->status ?? null) === 'active'
        && $user->role === 'receptionist';
});

Broadcast::channel('admin.bookings.staff.{userId}', function ($user, $userId) {
    return ($user->status ?? null) === 'active'
        && in_array($user->role, ['receptionist', 'receptionist_lead'], true)
        && (int) $user->id === (int) $userId;
});

Broadcast::channel('admin.rooms.operations', function ($user) {
    return ($user->status ?? null) === 'active'
        && in_array($user->role, [
            'super_admin',
            'manager',
            'receptionist_lead',
            'receptionist',
            'housekeeping_supervisor',
        ], true);
});

Broadcast::channel('admin.inspections.supervisors', function ($user) {
    return ($user->status ?? null) === 'active'
        && in_array($user->role, [
            'super_admin',
            'manager',
            'receptionist_lead',
            'housekeeping_supervisor',
        ], true);
});
