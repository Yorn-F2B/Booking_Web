<?php

use Illuminate\Support\Facades\Route;

Route::middleware('role:super_admin,manager,receptionist_lead,receptionist,housekeeping_supervisor,housekeeping')
    ->get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
    ->name('admin.dashboard');

Route::middleware('role:super_admin')
    ->get('dashboard/detail/{metric}', [\App\Http\Controllers\Admin\DashboardController::class, 'detail'])
    ->name('admin.dashboard.detail');

require __DIR__ . '/admin/rooms.php';

Route::middleware('role:super_admin,manager,receptionist_lead,receptionist,housekeeping_supervisor,housekeeping')->group(function () {
    Route::get('operation-center', [\App\Http\Controllers\Admin\OperationCenterController::class, 'index'])->name('admin.operation-center.index');
    Route::get('notifications/{notification}/open', [\App\Http\Controllers\Admin\OperationCenterController::class, 'open'])->name('admin.notifications.open');
    Route::patch('notifications/read-all', [\App\Http\Controllers\Admin\OperationCenterController::class, 'markAllRead'])->name('admin.notifications.read-all');
});
Route::middleware('role:super_admin,manager')->group(function () {
    Route::get('email-logs', [\App\Http\Controllers\Admin\EmailDeliveryLogController::class, 'index'])->name('admin.email-logs.index');
});


Route::middleware('role:super_admin')->group(function () {
    require __DIR__ . '/admin/staffs.php';
});

Route::middleware('role:super_admin,manager,receptionist_lead,housekeeping_supervisor')->group(function () {
    require __DIR__ . '/admin/staff-assignments.php';
});

Route::middleware('role:super_admin,manager')->group(function () {
    Route::get('policies', [\App\Http\Controllers\Admin\HotelPolicyController::class, 'index'])->name('admin.policies.index');
    Route::patch('policies', [\App\Http\Controllers\Admin\HotelPolicyController::class, 'update'])->name('admin.policies.update');

    require __DIR__ . '/admin/room-issues.php';
    require __DIR__ . '/admin/room-categories.php';
    require __DIR__ . '/admin/services.php';
    require __DIR__ . '/admin/promotions.php';
    require __DIR__ . '/admin/amenities.php';
    require __DIR__ . '/admin/reviews.php';

    Route::get('banned-words', [\App\Http\Controllers\Admin\BannedWordController::class, 'index'])->name('admin.banned-words.index');
    Route::get('banned-words/create', [\App\Http\Controllers\Admin\BannedWordController::class, 'create'])->name('admin.banned-words.create');
    Route::post('banned-words', [\App\Http\Controllers\Admin\BannedWordController::class, 'store'])->name('admin.banned-words.store');
    Route::delete('banned-words/{bannedWord}', [\App\Http\Controllers\Admin\BannedWordController::class, 'destroy'])->name('admin.banned-words.destroy');

    Route::patch('customer-requests/{customerRequest}/approve', [\App\Http\Controllers\Admin\CustomerRequestController::class, 'approve'])->name('admin.customer-requests.approve');
    Route::patch('customer-requests/{customerRequest}/reject', [\App\Http\Controllers\Admin\CustomerRequestController::class, 'reject'])->name('admin.customer-requests.reject');



    Route::resource('customers', \App\Http\Controllers\Admin\CustomerController::class)
        ->except(['create', 'store'])
        ->names('admin.customers');
});

Route::middleware('role:super_admin,manager,receptionist_lead,receptionist')->group(function () {
    require __DIR__ . '/admin/bookings.php';
    require __DIR__ . '/admin/staying-guests.php';
    require __DIR__ . '/admin/chats.php';

    Route::get('customer-requests', [\App\Http\Controllers\Admin\CustomerRequestController::class, 'index'])->name('admin.customer-requests.index');
    Route::get('customer-requests/{customerRequest}', [\App\Http\Controllers\Admin\CustomerRequestController::class, 'show'])->name('admin.customer-requests.show');
    Route::patch('customer-requests/{customerRequest}/receptionist-note', [\App\Http\Controllers\Admin\CustomerRequestController::class, 'receptionistNote'])->name('admin.customer-requests.receptionist-note');
    Route::post('customer-requests/{customerRequest}/acknowledge', [\App\Http\Controllers\Admin\CustomerRequestController::class, 'acknowledge'])->name('admin.customer-requests.acknowledge');
    Route::get('customer-requests/{customerRequest}/updates', [\App\Http\Controllers\Admin\CustomerRequestController::class, 'updates'])->name('admin.customer-requests.updates');
    Route::get('customer-requests/{customerRequest}/attachments/{attachment}', [\App\Http\Controllers\Admin\CustomerRequestController::class, 'attachment'])->name('admin.customer-requests.attachment');
    Route::post('bookings/{booking}/send-customer-request-form', [\App\Http\Controllers\Admin\CustomerRequestController::class, 'sendGuestForm'])->name('admin.bookings.send-customer-request-form');


    Route::get('bookings/{booking}/invoice', [\App\Http\Controllers\Admin\InvoiceController::class, 'generate'])
        ->name('admin.bookings.invoice');
});

Route::middleware('role:super_admin,manager,housekeeping_supervisor,housekeeping')->group(function () {
    require __DIR__ . '/admin/room-repairs.php';

    Route::get('housekeeping', [\App\Http\Controllers\Admin\HousekeepingController::class, 'index'])
        ->name('admin.housekeeping.index');

    Route::patch('housekeeping/{room}/mark-available', [\App\Http\Controllers\Admin\HousekeepingController::class, 'markAvailable'])
        ->name('admin.housekeeping.mark-available');
});

Route::middleware('role:super_admin,manager,receptionist_lead,receptionist,housekeeping_supervisor,housekeeping')->group(function () {
    Route::get('room-issue-attachments/{attachment}', [\App\Http\Controllers\Admin\RoomIssueRequestController::class, 'attachment'])
        ->name('admin.room-issue-attachments.show');
});

Route::middleware('role:super_admin,manager,housekeeping_supervisor,housekeeping')->group(function () {
    Route::get('floor-inspections', [\App\Http\Controllers\Admin\FloorInspectionController::class, 'index'])
        ->name('admin.floor-inspections.index');

    Route::get('floor-inspections/{roomInspection}', [\App\Http\Controllers\Admin\FloorInspectionController::class, 'show'])
        ->name('admin.floor-inspections.show');

    Route::post('floor-inspections/{roomInspection}/report', [\App\Http\Controllers\Admin\FloorInspectionController::class, 'report'])
        ->name('admin.floor-inspections.report');

    Route::post('floor-inspections/{roomInspection}/supplemental-report', [\App\Http\Controllers\Admin\FloorInspectionController::class, 'supplementalReport'])
        ->name('admin.floor-inspections.supplemental-report');

    Route::post('floor-inspections/{roomInspection}/recheck', [\App\Http\Controllers\Admin\FloorInspectionController::class, 'recheck'])
        ->name('admin.floor-inspections.recheck');
});

