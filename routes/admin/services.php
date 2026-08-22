<?php

use App\Http\Controllers\Admin\ServiceController;
use Illuminate\Support\Facades\Route;

Route::resource('services', ServiceController::class);
Route::resource('surcharges', \App\Http\Controllers\Admin\SurchargeController::class);
