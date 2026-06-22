<?php

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
    return Customer::where('id', $customerId)
        ->where('user_id', $user->id)
        ->exists();
});