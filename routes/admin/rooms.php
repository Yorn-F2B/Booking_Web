<?php

use App\Http\Controllers\Admin\RoomController;
use Illuminate\Support\Facades\Route;

Route::patch('rooms/{room}/status', [RoomController::class, 'updateStatus'])
    ->name('admin.rooms.update-status');

Route::resource('rooms', RoomController::class)
    ->names('admin.rooms');