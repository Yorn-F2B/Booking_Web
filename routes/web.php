<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HOME PAGE
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('user.pages.home');
})->name('home');
/*
|--------------------------------------------------------------------------
| USER PAGES
|--------------------------------------------------------------------------
*/

Route::get('/about', function () {
    return view('user.pages.about');
});

Route::get('/rooms', function () {
    return view('user.pages.rooms');
})->name('rooms');

Route::get('/booking-history', function () {
    return view('user.pages.booking-history');
});

Route::get('/contact', function () {
    return view('user.pages.contact');
});


Route::get('/room-deluxe-sea', function () {
    return view('user.pages.room-deluxe-sea');
});

Route::get('/room-family-suite', function () {
    return view('user.pages.room-family-suite');
});

Route::get('/room-premier-city', function () {
    return view('user.pages.room-premier-city');
});

Route::get('/room-presidential', function () {
    return view('user.pages.room-presidential');
});

/*
|--------------------------------------------------------------------------
| USER SETTINGS
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/user-settings', [UserSettingController::class, 'index'])
        ->name('user.settings');

    Route::post('/user-settings', [UserSettingController::class, 'update'])
        ->name('user.settings.update');

    Route::post('/user-password', [UserSettingController::class, 'updatePassword'])
        ->name('user.password.update');
});

/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
