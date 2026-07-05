<?php

use App\Http\Controllers\Admin\PromotionController;
use Illuminate\Support\Facades\Route;

Route::patch('promotions/{promotion}/toggle-status', [PromotionController::class, 'toggleStatus'])
    ->name('admin.promotions.toggle-status');

Route::resource('promotions', PromotionController::class)
    ->names('admin.promotions');
