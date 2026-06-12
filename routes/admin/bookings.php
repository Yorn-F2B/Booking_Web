<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\BookingRoomController;
use App\Http\Controllers\Admin\BookingLifecycleController;
use App\Http\Controllers\Admin\FloorInspectionController;
use App\Http\Controllers\Admin\InspectionApprovalController;
use App\Http\Controllers\Admin\HousekeepingController;
use App\Http\Controllers\Admin\BookingCreateController;

/*
|--------------------------------------------------------------------------
| Booking create
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Booking lifecycle
|--------------------------------------------------------------------------
*/

Route::patch('bookings/{booking}/check-in', [BookingLifecycleController::class, 'checkIn'])
    ->name('admin.bookings.check-in');

Route::patch('bookings/{booking}/request-inspection', [BookingLifecycleController::class, 'requestInspection'])
    ->name('admin.bookings.request-inspection');

Route::patch('bookings/{booking}/check-out', [BookingLifecycleController::class, 'checkOut'])
    ->name('admin.bookings.check-out');

/*
|--------------------------------------------------------------------------
| Floor inspections
|--------------------------------------------------------------------------
*/

Route::get('floor-inspections', [FloorInspectionController::class, 'index'])
    ->name('admin.floor-inspections.index');

Route::get('floor-inspections/{roomInspection}', [FloorInspectionController::class, 'show'])
    ->name('admin.floor-inspections.show');

Route::post('floor-inspections/{roomInspection}/report', [FloorInspectionController::class, 'report'])
    ->name('admin.floor-inspections.report');

/*
|--------------------------------------------------------------------------
| Inspection approvals
|--------------------------------------------------------------------------
*/

Route::get('inspection-approvals', [InspectionApprovalController::class, 'index'])
    ->name('admin.inspection-approvals.index');

Route::get('inspection-approvals/{roomInspection}', [InspectionApprovalController::class, 'show'])
    ->name('admin.inspection-approvals.show');

Route::post('inspection-approvals/{roomInspection}/approve', [InspectionApprovalController::class, 'approve'])
    ->name('admin.inspection-approvals.approve');

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

Route::resource('bookings', BookingController::class)
    ->except(['create', 'store'])
    ->names('admin.bookings');