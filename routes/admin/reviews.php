<?php

use App\Http\Controllers\Admin\HotelReviewController;
use Illuminate\Support\Facades\Route;

Route::get('reviews', [HotelReviewController::class, 'index'])->name('admin.reviews.index');
Route::get('reviews/{hotelReview}', [HotelReviewController::class, 'show'])->name('admin.reviews.show');
Route::patch('reviews/{hotelReview}/reply', [HotelReviewController::class, 'reply'])->name('admin.reviews.reply');
