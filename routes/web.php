<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSettingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\RoomController;
use App\Models\RoomCategory;
use App\Http\Controllers\BookingController;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| HOME PAGE
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $featuredRoomCategories = RoomCategory::with(['images', 'amenities'])
        ->where('status', 'active')
        ->latest()
        ->take(6)
        ->get();

    $now = Carbon::now('Asia/Ho_Chi_Minh');
    $todayCheckInDeadline = $now->copy()->setTime(14, 0, 0);

    $onlineBookingClosedToday = $now->greaterThanOrEqualTo($todayCheckInDeadline);

    $minOnlineCheckInDate = $onlineBookingClosedToday
        ? $now->copy()->addDay()->toDateString()
        : $now->toDateString();

    $minOnlineCheckOutDate = Carbon::parse($minOnlineCheckInDate, 'Asia/Ho_Chi_Minh')
        ->addDay()
        ->toDateString();

    $maxAdultCapacity = max(
        1,
        (int) RoomCategory::where('status', 'active')->max('adult_capacity')
    );

    $maxChildCapacity = max(
        0,
        (int) RoomCategory::where('status', 'active')->max('child_capacity')
    );

    return view('user.pages.home', compact(
        'featuredRoomCategories',
        'minOnlineCheckInDate',
        'minOnlineCheckOutDate',
        'onlineBookingClosedToday',
        'maxAdultCapacity',
        'maxChildCapacity'
    ));
})->name('home');
/*
|--------------------------------------------------------------------------
| USER PAGES
|--------------------------------------------------------------------------
*/

Route::get('/about', function () {
    return view('user.pages.about');
});

Route::get('/rooms', [RoomController::class, 'index'])->name('rooms');
Route::get('/rooms/{roomCategory}', [RoomController::class, 'show'])->name('rooms.show');

Route::get('/booking-history', function () {
    return view('user.pages.booking-history');
});

Route::get('/contact', function () {
    return view('user.pages.contact');
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

    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->name('bookings.cancel');

    Route::post('/booking-history/{booking}/services', [BookingController::class, 'storeCustomerService'])
        ->name('bookings.services.store');

    Route::get(
        '/booking-history/{booking}',
        [BookingController::class, 'show']
    )->name('bookings.show');
});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';

Route::get('/bookings/confirm', [BookingController::class, 'confirm'])
    ->name('bookings.confirm');

Route::middleware('auth')->group(function () {
    Route::post('/bookings', [BookingController::class, 'store'])
        ->name('bookings.store');
});