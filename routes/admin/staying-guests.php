<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StayingGuestController;

Route::get('staying-guests', [StayingGuestController::class, 'index'])->name('admin.staying-guests.index');
