<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\BookingRoomController;
use App\Http\Controllers\Admin\BookingLifecycleController;
use App\Http\Controllers\Admin\FloorInspectionController;
use App\Http\Controllers\Admin\InspectionApprovalController;
use App\Http\Controllers\Admin\HousekeepingController;
use App\Http\Controllers\Admin\BookingCreateController;
use App\Http\Controllers\Admin\RoomAvailabilityController;

/*
|--------------------------------------------------------------------------
| Booking create
|--------------------------------------------------------------------------
*/

Route::get('room-availability', [RoomAvailabilityController::class, 'index'])
    ->name('admin.room-availability.index');

Route::get('bookings/create', [BookingCreateController::class, 'create'])
    ->name('admin.bookings.create');

Route::post('bookings', [BookingCreateController::class, 'store'])
    ->name('admin.bookings.store');

Route::post('bookings/suggestions/store', [BookingCreateController::class, 'storeSuggestion'])
    ->name('admin.bookings.suggestions.store');

/*
|--------------------------------------------------------------------------
| Booking room assignment / room change
|--------------------------------------------------------------------------
*/

Route::post('bookings/{booking}/assign-rooms', [BookingRoomController::class, 'assignRooms'])
    ->name('admin.bookings.assign-rooms');

Route::post('bookings/{booking}/change-room', [BookingRoomController::class, 'changeRoom'])
    ->name('admin.bookings.change-room');

Route::patch('bookings/{booking}/add-room-to-booking', [BookingLifecycleController::class, 'addRoomToBooking'])
    ->name('admin.bookings.add-room-to-booking');

Route::patch('bookings/{booking}/change-one-room-category', [BookingLifecycleController::class, 'changeOneRoomCategory'])
    ->name('admin.bookings.change-one-room-category');

Route::patch('bookings/{booking}/change-all-room-category', [BookingLifecycleController::class, 'changeAllRoomCategory'])
    ->name('admin.bookings.change-all-room-category');

/*
|--------------------------------------------------------------------------
| Booking lifecycle
|--------------------------------------------------------------------------
*/

Route::patch('bookings/{booking}/check-in', [BookingLifecycleController::class, 'checkIn'])
    ->name('admin.bookings.check-in');

Route::patch('bookings/{booking}/priority-cleaning', [BookingLifecycleController::class, 'requestPriorityCleaning'])
    ->name('admin.bookings.priority-cleaning');

Route::patch('bookings/{booking}/change-stay-dates', [BookingLifecycleController::class, 'changeStayDates'])
    ->name('admin.bookings.change-stay-dates');

Route::patch('bookings/{booking}/cancel-late-arrival', [BookingLifecycleController::class, 'cancelLateArrival'])
    ->name('admin.bookings.cancel-late-arrival');

Route::patch('bookings/{booking}/request-inspection', [BookingLifecycleController::class, 'requestInspection'])
    ->name('admin.bookings.request-inspection');

Route::patch('bookings/{booking}/check-out', [BookingLifecycleController::class, 'checkOut'])
    ->name('admin.bookings.check-out');

Route::post('bookings/{booking}/extend-stay/preview', [BookingLifecycleController::class, 'previewExtendStay'])
    ->name('admin.bookings.extend-stay.preview');

Route::patch('bookings/{booking}/extend-stay', [BookingLifecycleController::class, 'extendStay'])
    ->name('admin.bookings.extend-stay');

Route::post('bookings/hourly-inventory-check', [BookingCreateController::class, 'checkHourlyInventory'])
    ->name('admin.bookings.hourly-inventory-check');

Route::post('bookings/{booking}/promotions', [BookingController::class, 'applyPromotions'])
    ->name('admin.bookings.promotions.store');

Route::post('bookings/{booking}/service-items', [BookingController::class, 'storeServiceItem'])
    ->name('admin.bookings.service-items.store');

Route::patch('bookings/{booking}/service-items/{bookingServiceItem}', [BookingController::class, 'updateServiceItem'])
    ->name('admin.bookings.service-items.update');

Route::delete('bookings/{booking}/service-items/{bookingServiceItem}', [BookingController::class, 'destroyServiceItem'])
    ->name('admin.bookings.service-items.destroy');

/*
|--------------------------------------------------------------------------
| Housekeeping
|--------------------------------------------------------------------------
*/

Route::get('housekeeping', [HousekeepingController::class, 'index'])
    ->name('admin.housekeeping.index');

Route::patch('housekeeping/{room}/mark-available', [HousekeepingController::class, 'markAvailable'])
    ->name('admin.housekeeping.mark-available');

/*
|--------------------------------------------------------------------------
| Booking resource
|--------------------------------------------------------------------------
*/

Route::patch('bookings/{booking}/payment-status', [BookingController::class, 'updatePaymentStatus'])
    ->name('admin.bookings.update-payment-status');

Route::patch('bookings/{booking}/note', [BookingController::class, 'updateNote'])
    ->name('admin.bookings.update-note');


Route::resource('bookings', BookingController::class)
    ->except(['create', 'store', 'destroy'])
    ->names('admin.bookings');