<?php

use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\RoomDailyController;
use Illuminate\Support\Facades\Route;

Route::get('rooms/daily', [RoomDailyController::class, 'index'])
    ->name('admin.rooms.daily');

Route::patch('rooms/{room}/status', [RoomController::class, 'updateStatus'])
    ->name('admin.rooms.update-status');

Route::resource('rooms', RoomController::class)
    ->names('admin.rooms');