<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule auto-invoices to run daily at 9 AM
Schedule::command('app:send-auto-invoices')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onSuccess(function () {
        // Log successful execution
    })
    ->onFailure(function () {
        // Log failed execution
    });

