<?php

use App\Http\Controllers\Admin\RoomController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:super_admin,manager,receptionist_lead,receptionist,housekeeping_supervisor,housekeeping')
    ->group(function () {
        Route::get('rooms', [RoomController::class, 'index'])->name('admin.rooms.index');
        Route::get('rooms/{room}', [RoomController::class, 'show'])->name('admin.rooms.show');
    });

Route::middleware('role:super_admin,manager')->group(function () {
    Route::post('rooms', [RoomController::class, 'store'])->name('admin.rooms.store');
    Route::put('rooms/{room}', [RoomController::class, 'update'])->name('admin.rooms.update');
    Route::patch('rooms/{room}/status', [RoomController::class, 'updateStatus'])->name('admin.rooms.update-status');
    Route::delete('rooms/{room}', [RoomController::class, 'destroy'])->name('admin.rooms.destroy');
});
