<?php

use App\Models\ChatConversation;
use App\Models\Customer;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.realtime', function ($user) {
    return in_array($user->role, [
        'super_admin',
        'manager',
        'receptionist',
        'housekeeping',
    ], true);
});

Broadcast::channel('admin.bookings', function ($user) {
    return in_array($user->role, [
        'super_admin',
        'manager',
        'receptionist',
    ], true);
});

Broadcast::channel('admin.rooms', function ($user) {
    return in_array($user->role, [
        'super_admin',
        'manager',
        'receptionist',
        'housekeeping',
    ], true);
});

Broadcast::channel('customer.{customerId}', function ($user, $customerId) {
    $customer = Customer::find($customerId);

    if (!$customer) {
        return false;
    }

    if (in_array($user->role, ['super_admin', 'manager', 'receptionist'], true)) {
        return true;
    }

    if (isset($customer->user_id) && (int) $customer->user_id === (int) $user->id) {
        return true;
    }

    if (!empty($customer->email) && !empty($user->email) && $customer->email === $user->email) {
        return true;
    }

    return false;
});

Broadcast::channel('chat.customer.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
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
        in_array($user->role, ['receptionist', 'housekeeping'], true)
        && (int) ($conversation->assigned_staff_id ?? 0) === (int) $user->id
    ) {
        return true;
    }

    if (
        $user->role === 'customer'
        && (int) ($conversation->customer_id ?? 0) === (int) $user->id
    ) {
        return true;
    }

    return false;
});
