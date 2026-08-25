<?php

use App\Http\Controllers\Admin\RoomIssueRequestController;
use Illuminate\Support\Facades\Route;

Route::get('room-issue-verifications', [RoomIssueRequestController::class, 'verifications'])
    ->name('admin.room-issue-verifications.index');
Route::get('room-issue-verifications/{roomIssueRequest}', [RoomIssueRequestController::class, 'verificationShow'])
    ->name('admin.room-issue-verifications.show');
Route::patch('room-issue-verifications/{roomIssueRequest}', [RoomIssueRequestController::class, 'verify'])
    ->name('admin.room-issue-verifications.verify');

Route::get('room-repairs', [RoomIssueRequestController::class, 'repairs'])
    ->name('admin.room-repairs.index');
Route::get('room-repairs/{roomIssueRequest}', [RoomIssueRequestController::class, 'repairShow'])
    ->name('admin.room-repairs.show');
Route::patch('room-repairs/{roomIssueRequest}/complete', [RoomIssueRequestController::class, 'completeRepair'])
    ->name('admin.room-repairs.complete');
