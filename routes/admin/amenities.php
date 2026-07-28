<?php

use App\Http\Controllers\Admin\AmenityController;
use Illuminate\Support\Facades\Route;

Route::resource('amenities', AmenityController::class);