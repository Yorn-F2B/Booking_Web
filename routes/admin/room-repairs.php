<?php

use App\Http\Controllers\Admin\RoomIssueRequestController;
use Illuminate\Support\Facades\Route;

Route::get('room-repairs', [RoomIssueRequestController::class, 'repairs'])
    ->name('admin.room-repairs.index');
Route::get('room-repairs/{roomIssueRequest}', [RoomIssueRequestController::class, 'repairShow'])
    ->name('admin.room-repairs.show');
Route::patch('room-repairs/{roomIssueRequest}/complete', [RoomIssueRequestController::class, 'completeRepair'])
    ->name('admin.room-repairs.complete');
