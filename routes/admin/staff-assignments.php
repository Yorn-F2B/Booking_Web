<?php

use App\Http\Controllers\Admin\StaffAssignmentController;
use Illuminate\Support\Facades\Route;

Route::get('staff-assignments', [StaffAssignmentController::class, 'index'])
    ->name('admin.staff-assignments.index');

Route::middleware('role:super_admin,manager,receptionist_lead')->group(function () {
    Route::get('staff-assignments/receptionists', [StaffAssignmentController::class, 'receptionists'])
        ->name('admin.staff-assignments.receptionists');

    Route::post('staff-assignments/receptionists', [StaffAssignmentController::class, 'storeReceptionist'])
        ->name('admin.staff-assignments.receptionists.store');

    Route::patch('staff-assignments/receptionists/{bookingStaffAssignment}/cancel', [StaffAssignmentController::class, 'cancelBookingAssignment'])
        ->name('admin.staff-assignments.receptionists.cancel');
});

Route::middleware('role:super_admin,manager,housekeeping_supervisor')->group(function () {
    Route::get('staff-assignments/housekeeping', [StaffAssignmentController::class, 'housekeeping'])
        ->name('admin.staff-assignments.housekeeping');

    Route::post('staff-assignments/housekeeping/floors', [StaffAssignmentController::class, 'storeFloorAssignment'])
        ->name('admin.staff-assignments.housekeeping.floors.store');

    Route::post('staff-assignments/housekeeping/rooms', [StaffAssignmentController::class, 'storeRoomAssignment'])
        ->name('admin.staff-assignments.housekeeping.rooms.store');

    Route::delete('staff-assignments/housekeeping/floors/{staffFloorAssignment}', [StaffAssignmentController::class, 'deleteFloorAssignment'])
        ->name('admin.staff-assignments.housekeeping.floors.destroy');

    Route::delete('staff-assignments/housekeeping/rooms/{staffRoomAssignment}', [StaffAssignmentController::class, 'deleteRoomAssignment'])
        ->name('admin.staff-assignments.housekeeping.rooms.destroy');
});
