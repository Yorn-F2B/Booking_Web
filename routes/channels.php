<?php

use App\Models\ChatConversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.realtime', function ($user) {
    return in_array($user->role, [
        'super_admin',
        'manager',
        'receptionist_lead',
        'receptionist',
        'housekeeping_supervisor',
        'housekeeping',
    ], true);
});

Broadcast::channel('customer.{customerId}', function ($user, $customerId) {
    return $user->role === 'customer'
        && $user->customer
        && (int) $user->customer->id === (int) $customerId;
});

Broadcast::channel('chat.customer.{userId}', function ($user, $userId) {
    return $user->role === 'customer' && (int) $user->id === (int) $userId;
});

Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = ChatConversation::find($conversationId);

    if (!$conversation) {
        return false;
    }

    if (in_array($user->role, ['super_admin', 'manager'], true)) {
        return true;
    }

    if (
        in_array($user->role, ['receptionist_lead', 'receptionist'], true)
        && (
            !$conversation->assigned_staff_id
            || (int) $conversation->assigned_staff_id === (int) $user->id
        )
    ) {
        return true;
    }

    return $user->role === 'customer'
        && (int) $conversation->customer_id === (int) $user->id;
});
