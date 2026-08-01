<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\StaffController;

Route::resource('staffs', StaffController::class);