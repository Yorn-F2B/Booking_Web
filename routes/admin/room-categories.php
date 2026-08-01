<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RoomCategoryController;

Route::resource('room-categories', RoomCategoryController::class);