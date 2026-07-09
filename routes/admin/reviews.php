<?php

use App\Http\Controllers\Admin\HotelReviewController;
use Illuminate\Support\Facades\Route;

Route::get('reviews', [HotelReviewController::class, 'index'])
    ->name('admin.reviews.index');

Route::get('reviews/{hotelReview}', [HotelReviewController::class, 'show'])
    ->name('admin.reviews.show');

Route::patch('reviews/{hotelReview}/approve', [HotelReviewController::class, 'approve'])
    ->name('admin.reviews.approve');

Route::patch('reviews/{hotelReview}/hide', [HotelReviewController::class, 'hide'])
    ->name('admin.reviews.hide');

Route::patch('reviews/{hotelReview}/reply', [HotelReviewController::class, 'reply'])
    ->name('admin.reviews.reply');

Route::delete('reviews/{hotelReview}', [HotelReviewController::class, 'destroy'])
    ->name('admin.reviews.destroy');
