<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('bookings:expire-unpaid')->everyMinute()->withoutOverlapping();

Schedule::command('bookings:cancel-no-show')->everyMinute()->withoutOverlapping();
