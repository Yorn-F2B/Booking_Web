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
use App\Http\Controllers\Payment\VnpayController;

/*
|--------------------------------------------------------------------------
| Booking create
|--------------------------------------------------------------------------
*/

Route::get('room-availability', [RoomAvailabilityController::class, 'index'])
    ->name('admin.room-availability.index');

Route::get('rooms/{room}/action-logs', [\App\Http\Controllers\Admin\RoomActionLogController::class, 'getLogsByDate'])
    ->name('admin.rooms.action-logs.index');
Route::put('room-action-logs/{log}', [\App\Http\Controllers\Admin\RoomActionLogController::class, 'updateLog'])
    ->name('admin.room-action-logs.update');

Route::get('bookings/create', [BookingCreateController::class, 'create'])
    ->name('admin.bookings.create');

Route::post('bookings', [BookingCreateController::class, 'store'])
    ->name('admin.bookings.store');

Route::post('bookings/eligible-promotions', [BookingCreateController::class, 'eligiblePromotions'])
    ->name('admin.bookings.eligible-promotions');

Route::post('bookings/check-customer-account', [BookingCreateController::class, 'checkCustomerAccount'])
    ->name('admin.bookings.check-customer-account');

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
Route::post('bookings/{booking}/change-stay-dates/discard-preview', [BookingLifecycleController::class, 'discardStayDatePreview'])
    ->name('admin.bookings.change-stay-dates.discard-preview');

Route::patch('bookings/{booking}/cancel-late-arrival', [BookingLifecycleController::class, 'cancelLateArrival'])
    ->name('admin.bookings.cancel-late-arrival');

Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])
    ->name('admin.bookings.cancel');

Route::patch('bookings/{booking}/confirm-late-arrival', [BookingLifecycleController::class, 'confirmLateArrival'])
    ->name('admin.bookings.confirm-late-arrival');

Route::middleware('role:super_admin,manager,receptionist_lead')->group(function () {
Route::patch('bookings/{booking}/cancellation-request/approve', [BookingController::class, 'approveCancellationRequest'])
    ->name('admin.bookings.cancellation-request.approve');

Route::patch('bookings/{booking}/cancellation-request/reject', [BookingController::class, 'rejectCancellationRequest'])
    ->name('admin.bookings.cancellation-request.reject');
});

Route::patch('bookings/{booking}/request-inspection', [BookingLifecycleController::class, 'requestInspection'])
    ->name('admin.bookings.request-inspection');

Route::post(
    'bookings/{booking}/inspections/{roomInspection}/guest-consultation',
    [\App\Http\Controllers\Admin\InspectionGuestConsultationController::class, 'store']
)->name('admin.bookings.inspections.guest-consultation');

Route::post('bookings/{booking}/checkout-fees', [BookingLifecycleController::class, 'addCheckoutFee'])
    ->name('admin.bookings.checkout-fees.store');

Route::patch('bookings/{booking}/check-out', [BookingLifecycleController::class, 'checkOut'])
    ->name('admin.bookings.check-out');

Route::post('bookings/{booking}/extend-stay/preview', [BookingLifecycleController::class, 'previewExtendStay'])
    ->name('admin.bookings.extend-stay.preview');

Route::patch('bookings/{booking}/extend-stay', [BookingLifecycleController::class, 'extendStay'])
    ->name('admin.bookings.extend-stay');

Route::post('bookings/room-category-availability', [BookingCreateController::class, 'checkRoomCategoryAvailability'])
    ->name('admin.bookings.room-category-availability');

Route::post('bookings/hourly-inventory-check', [BookingCreateController::class, 'checkHourlyInventory'])
    ->name('admin.bookings.hourly-inventory-check');

Route::middleware('role:super_admin,manager,receptionist_lead')->group(function () {
Route::post('bookings/{booking}/promotions', [BookingController::class, 'applyPromotions'])
    ->name('admin.bookings.promotions.store');
});

Route::post('bookings/{booking}/service-items', [BookingController::class, 'storeServiceItem'])
    ->name('admin.bookings.service-items.store');

Route::patch('bookings/{booking}/service-items/{bookingServiceItem}', [BookingController::class, 'updateServiceItem'])
    ->name('admin.bookings.service-items.update');

Route::delete('bookings/{booking}/service-items/{bookingServiceItem}', [BookingController::class, 'destroyServiceItem'])
    ->name('admin.bookings.service-items.destroy');

Route::post('bookings/{booking}/payments', [BookingController::class, 'recordPayment'])
    ->name('admin.bookings.payments.store');

Route::post('bookings/{booking}/vnpay', [VnpayController::class, 'adminCreate'])
    ->name('admin.bookings.vnpay.create');

/*
|--------------------------------------------------------------------------
| Booking resource
|--------------------------------------------------------------------------
*/

Route::middleware('role:super_admin,manager,receptionist_lead')->group(function () {
Route::patch('bookings/{booking}/payment-status', [BookingController::class, 'updatePaymentStatus'])
    ->name('admin.bookings.update-payment-status');
});

Route::patch('bookings/{booking}/note', [BookingController::class, 'updateNote'])
    ->name('admin.bookings.update-note');

Route::post('bookings/{booking}/send-room-issue-form', [BookingController::class, 'sendRoomIssueForm'])
    ->name('admin.bookings.send-room-issue-form');

Route::post('bookings/{booking}/guests', [BookingController::class, 'addGuest'])
    ->name('admin.bookings.guests.store');

Route::patch('bookings/{booking}/guests/{guest}', [BookingController::class, 'updateGuest'])
    ->name('admin.bookings.guests.update');

Route::delete('bookings/{booking}/guests/{guest}', [BookingController::class, 'removeGuest'])
    ->name('admin.bookings.guests.destroy');

Route::get('bookings', [BookingController::class, 'index'])
    ->name('admin.bookings.index');


Route::get('bookings/{booking}', [BookingController::class, 'show'])
    ->name('admin.bookings.show');

Route::middleware('role:super_admin,manager,receptionist_lead')->group(function () {
    Route::get('bookings/{booking}/edit', [BookingController::class, 'edit'])
        ->name('admin.bookings.edit');

    Route::match(['put', 'patch'], 'bookings/{booking}', [BookingController::class, 'update'])
        ->name('admin.bookings.update');
});
Route::get('bookings/{booking}/room-issue-proposal', [\App\Http\Controllers\Admin\RoomIssueGroupController::class, 'receptionistShow'])
    ->name('admin.bookings.room-issue-proposal');
Route::patch('bookings/{booking}/room-issue-proposal/respond', [\App\Http\Controllers\Admin\RoomIssueGroupController::class, 'receptionistRespond'])
    ->name('admin.bookings.room-issue-proposal.respond');
