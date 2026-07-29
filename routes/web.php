<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketingController;

Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('pricing');
Route::get('/privacy', [MarketingController::class, 'privacy'])->name('privacy');
Route::get('/terms', [MarketingController::class, 'terms'])->name('terms');

//generic login
//Route::get('/login', fn () => view('generic-login'))->name('generic.login');
//Route::post('/login', \App\Http\Controllers\GenericLoginController::class)->name('generic.login.attempt');
// routes/web.php
Route::get('/login', fn () => view('generic-login'))->name('generic.login');
Route::post('/login', \App\Http\Controllers\GenericLoginController::class)
     ->name('generic.login.attempt');

// Self-serve landlord signup
Route::get('/signup', [\App\Http\Controllers\LandlordSignupController::class, 'create'])->name('signup');
Route::post('/signup', [\App\Http\Controllers\LandlordSignupController::class, 'store'])
    ->name('signup.store')
    ->middleware('throttle:6,1');

// password reset (email or phone)
Route::get('/forgot-password', [\App\Http\Controllers\ForgotPasswordController::class, 'showForgotForm'])
    ->name('password.request');
Route::post('/forgot-password', [\App\Http\Controllers\ForgotPasswordController::class, 'sendReset'])
    ->name('password.email');
Route::get('/reset-password/{token}', [\App\Http\Controllers\ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\ResetPasswordController::class, 'reset'])
    ->name('password.update');

use App\Http\Controllers\PesapalController;
use App\Http\Controllers\MpesaController;

// Tenant payment initiation (Pesapal and M-Pesa)
Route::middleware(['auth'])->group(function () {
    // Pesapal routes
    Route::get('/tenant/payments/initiate/{invoice}', [PesapalController::class, 'initiate'])
        ->name('tenant.payments.initiate');
    Route::get('/payments/pesapal/callback', [PesapalController::class, 'callbackRedirect'])
        ->name('payments.pesapal.callback.redirect');
    
    // M-Pesa routes
    // Allow GET for Filament redirects and POST for direct form submissions
    Route::match(['get', 'post'], '/tenant/mpesa/initiate/{invoice}', [MpesaController::class, 'initiate'])
        ->name('tenant.mpesa.initiate');
    Route::get('/tenant/mpesa/status', [MpesaController::class, 'checkStatus'])
        ->name('tenant.mpesa.status');
    // Generic status route accessible to both tenants and admins
    Route::get('/mpesa/status', [MpesaController::class, 'checkStatus'])
        ->name('mpesa.status');
    Route::get('/mpesa/callback/redirect', [MpesaController::class, 'callbackRedirect'])
        ->name('mpesa.callback.redirect');

    // Chat page for tenant/admin messaging
    Route::get('/chat', fn () => view('chat'))->name('chat');

    // Local-only Pesapal simulation endpoint (marks a pending payment as paid without
    // actually calling Pesapal) - never registered outside local dev, so it can't be
    // used in production to bypass real payment collection.
    if (app()->environment('local')) {
        Route::get('/tenant/payments/pesapal-callback', [PesapalController::class, 'simulateCallback'])
            ->name('tenant.payments.pesapal.callback');
    }
});


