<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSettingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\RoomController;
use App\Models\RoomCategory;
use App\Http\Controllers\BookingController;
use Carbon\Carbon;
use App\Http\Controllers\Payment\VnpayController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\HotelReviewController;
use App\Models\HotelReview;

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

    $approvedHotelReviews = HotelReview::approved()
        ->with(['customer', 'booking.roomCategory', 'replier'])
        ->latest('approved_at')
        ->take(6)
        ->get();

    $hotelReviewStats = [
        'count' => HotelReview::approved()->count(),
        'average' => round((float) HotelReview::approved()->avg('rating'), 1),
    ];

    return view('user.pages.home', compact(
        'featuredRoomCategories',
        'minOnlineCheckInDate',
        'minOnlineCheckOutDate',
        'onlineBookingClosedToday',
        'maxAdultCapacity',
        'maxChildCapacity',
        'approvedHotelReviews',
        'hotelReviewStats'
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

Route::middleware('auth')->get('/booking-history', [BookingController::class, 'history'])
    ->name('bookings.history');

Route::get('/contact', function () {
    return view('user.pages.contact');
});

/*
|--------------------------------------------------------------------------
| CHAT ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/chat/messages', [ChatController::class, 'messages'])
    ->name('chat.messages');

Route::post('/chat/send', [ChatController::class, 'send'])
    ->name('chat.send');

Route::post('/chat/close', [ChatController::class, 'close'])
    ->name('chat.close');

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

    Route::get('/bookings/current', [BookingController::class, 'current'])
        ->name('bookings.current');

    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->name('bookings.cancel');

    Route::post('/booking-history/{booking}/services', [BookingController::class, 'storeCustomerService'])
        ->name('bookings.services.store');

    Route::get('/booking-history/{booking}/review', [HotelReviewController::class, 'create'])
        ->name('bookings.reviews.create');

    Route::post('/booking-history/{booking}/review', [HotelReviewController::class, 'store'])
        ->name('bookings.reviews.store');

    Route::get('/reviews/{hotelReview}/edit', [HotelReviewController::class, 'edit'])
        ->name('reviews.edit');

    Route::put('/reviews/{hotelReview}', [HotelReviewController::class, 'update'])
        ->name('reviews.update');

    Route::delete('/reviews/{hotelReview}', [HotelReviewController::class, 'destroy'])
        ->name('reviews.destroy');

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

/*
|--------------------------------------------------------------------------
| PAYMENT ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/payment/vnpay/return', [VnpayController::class, 'return'])
    ->name('payment.vnpay.return');

Route::get('/payment/vnpay/ipn', [VnpayController::class, 'ipn'])
    ->name('payment.vnpay.ipn');

Route::get('/payment/vnpay/admin-request/{payment}', [VnpayController::class, 'payRequest'])
    ->name('payment.vnpay.admin-request');

Route::middleware('auth')->group(function () {
    Route::post('/payment/vnpay/{booking}', [VnpayController::class, 'create'])
        ->name('payment.vnpay.create');
});

Route::get('/bookings/confirm', [BookingController::class, 'confirm'])
    ->name('bookings.confirm');

Route::middleware('auth')->group(function () {
    Route::post('/bookings', [BookingController::class, 'store'])
        ->name('bookings.store');
});
