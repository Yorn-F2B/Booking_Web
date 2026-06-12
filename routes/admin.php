<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');

Route::middleware('role:super_admin')->group(function () {
    require __DIR__ . '/admin/staffs.php';
});

Route::middleware('role:super_admin,manager')->group(function () {
    require __DIR__ . '/admin/room-categories.php';
    require __DIR__ . '/admin/rooms.php';
    require __DIR__ . '/admin/services.php';
    require __DIR__ . '/admin/amenities.php';
});

Route::middleware('role:super_admin,manager,receptionist')->group(function () {
    require __DIR__ . '/admin/bookings.php';
});

Route::middleware('role:super_admin,manager,housekeeping')->group(function () {
    Route::get('housekeeping', [\App\Http\Controllers\Admin\HousekeepingController::class, 'index'])
        ->name('admin.housekeeping.index');

    Route::patch('housekeeping/{room}/mark-available', [\App\Http\Controllers\Admin\HousekeepingController::class, 'markAvailable'])
        ->name('admin.housekeeping.mark-available');
});

Route::middleware('role:super_admin,manager')->group(function () {
    Route::get('floor-inspections', [\App\Http\Controllers\Admin\FloorInspectionController::class, 'index'])
        ->name('admin.floor-inspections.index');

    Route::get('floor-inspections/{roomInspection}', [\App\Http\Controllers\Admin\FloorInspectionController::class, 'show'])
        ->name('admin.floor-inspections.show');

    Route::post('floor-inspections/{roomInspection}/report', [\App\Http\Controllers\Admin\FloorInspectionController::class, 'report'])
        ->name('admin.floor-inspections.report');

    Route::get('inspection-approvals', [\App\Http\Controllers\Admin\InspectionApprovalController::class, 'index'])
        ->name('admin.inspection-approvals.index');

    Route::get('inspection-approvals/{roomInspection}', [\App\Http\Controllers\Admin\InspectionApprovalController::class, 'show'])
        ->name('admin.inspection-approvals.show');

    Route::post('inspection-approvals/{roomInspection}/approve', [\App\Http\Controllers\Admin\InspectionApprovalController::class, 'approve'])
        ->name('admin.inspection-approvals.approve');
});