<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InvoiceController;

/*
|--------------------------------------------------------------------------
| Invoice Routes
|--------------------------------------------------------------------------
*/

Route::get('invoices', [InvoiceController::class, 'index'])
    ->name('admin.invoices.index');

Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])
    ->name('admin.invoices.show');

Route::get('bookings/{booking}/invoice/create', [InvoiceController::class, 'createFromBooking'])
    ->name('admin.invoices.create-from-booking');

Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])
    ->name('admin.invoices.print');