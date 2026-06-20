<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
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