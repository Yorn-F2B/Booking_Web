<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('user.pages.home');
});

Route::get('/about', function () {
    return view('user.pages.about');
});

Route::get('/booking-history', function () {
    return view('user.pages.booking-history');
});

Route::get('/contact', function () {
    return view('user.pages.contact');
});

Route::get('/login', function () {
    return view('user.pages.login');
});

Route::get('/register', function () {
    return view('user.pages.register');
});

Route::get('/room-deluxe-sea', function () {
    return view('user.pages.room-deluxe-sea');
});

Route::get('/room-family-suite', function () {
    return view('user.pages.room-family-suite');
});

Route::get('/room-premier-city', function () {
    return view('user.pages.room-premier-city');
});

Route::get('/room-presidential', function () {
    return view('user.pages.room-presidential');
});

Route::get('/rooms', function () {
    return view('user.pages.rooms');
});

Route::get('/user-settings', function () {
    return view('user.pages.user-settings');
});