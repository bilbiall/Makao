<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Create this month's recurring bill charges before auto-invoicing reads them (see
// Invoice::booted() / SendAutoInvoices - a bill must exist before that tenant's
// invoice is generated, or its cost is silently never billed)
Schedule::command('app:generate-recurring-bills')
    ->dailyAt('08:00')
    ->withoutOverlapping();

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

// Flip trial/active subscriptions past their expiry date to expired
Schedule::command('app:expire-trials')
    ->daily()
    ->withoutOverlapping();

