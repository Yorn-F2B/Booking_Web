<?php

use App\Http\Controllers\Admin\RoomIssueGroupController;
use App\Http\Controllers\Admin\RoomIssueRequestController;
use Illuminate\Support\Facades\Route;

Route::get('room-issues', [RoomIssueRequestController::class, 'index'])
    ->name('admin.room-issues.index');
Route::get('room-issues/{roomIssueRequest}', [RoomIssueGroupController::class, 'show'])
    ->name('admin.room-issues.show');
Route::patch('room-issues/{roomIssueRequest}/proposal', [RoomIssueGroupController::class, 'saveProposal'])
    ->name('admin.room-issues.proposal');
Route::patch('room-issues/{roomIssueRequest}/finalize', [RoomIssueGroupController::class, 'finalize'])
    ->name('admin.room-issues.finalize');
