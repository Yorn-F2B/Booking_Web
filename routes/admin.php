<?php

use Illuminate\Support\Facades\Route;

Route::middleware('role:super_admin')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
        ->name('admin.dashboard');
});

require __DIR__ . '/admin/rooms.php';

Route::middleware('role:super_admin')->group(function () {
    require __DIR__ . '/admin/staffs.php';
});

Route::middleware('role:super_admin,manager,receptionist_lead,housekeeping_supervisor')->group(function () {
    require __DIR__ . '/admin/staff-assignments.php';
});

Route::middleware('role:super_admin,manager')->group(function () {
    require __DIR__ . '/admin/room-categories.php';
    require __DIR__ . '/admin/services.php';
    require __DIR__ . '/admin/promotions.php';
    require __DIR__ . '/admin/amenities.php';
    require __DIR__ . '/admin/reviews.php';


    Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class)
        ->names('admin.customers');
});

Route::middleware('role:super_admin,manager,receptionist_lead,receptionist')->group(function () {
    require __DIR__ . '/admin/bookings.php';
    require __DIR__ . '/admin/staying-guests.php';
    require __DIR__ . '/admin/chats.php';

    Route::get('bookings/{booking}/invoice', [\App\Http\Controllers\Admin\InvoiceController::class, 'generate'])
        ->name('admin.bookings.invoice');
});

Route::middleware('role:super_admin,manager,housekeeping_supervisor,housekeeping')->group(function () {
    Route::get('housekeeping', [\App\Http\Controllers\Admin\HousekeepingController::class, 'index'])
        ->name('admin.housekeeping.index');

    Route::patch('housekeeping/{room}/mark-available', [\App\Http\Controllers\Admin\HousekeepingController::class, 'markAvailable'])
        ->name('admin.housekeeping.mark-available');
});

Route::middleware('role:super_admin,manager,housekeeping_supervisor,housekeeping')->group(function () {
    Route::get('floor-inspections', [\App\Http\Controllers\Admin\FloorInspectionController::class, 'index'])
        ->name('admin.floor-inspections.index');

    Route::get('floor-inspections/{roomInspection}', [\App\Http\Controllers\Admin\FloorInspectionController::class, 'show'])
        ->name('admin.floor-inspections.show');

    Route::post('floor-inspections/{roomInspection}/report', [\App\Http\Controllers\Admin\FloorInspectionController::class, 'report'])
        ->name('admin.floor-inspections.report');
});

Route::middleware('role:super_admin,manager')->group(function () {
    Route::get('inspection-approvals', [\App\Http\Controllers\Admin\InspectionApprovalController::class, 'index'])
        ->name('admin.inspection-approvals.index');

    Route::get('inspection-approvals/{roomInspection}', [\App\Http\Controllers\Admin\InspectionApprovalController::class, 'show'])
        ->name('admin.inspection-approvals.show');

    Route::post('inspection-approvals/{roomInspection}/approve', [\App\Http\Controllers\Admin\InspectionApprovalController::class, 'approve'])
        ->name('admin.inspection-approvals.approve');
});
