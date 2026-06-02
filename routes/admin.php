<?php
use Illuminate\Support\Facades\Route;

Route::get('/admin', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');

require __DIR__ . '/admin/staffs.php';

require __DIR__ . '/admin/room-categories.php';
?>