<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PesapalController;
use App\Http\Controllers\MpesaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 * Pesapal payment callbacks and IPN listeners
 * These routes are stateless and should NOT require authentication
 * Pesapal verifies requests via HMAC-SHA256 signature in X-Pesapal-Signature header
 * 
 * Environment-based URLs:
 * - Local:      http://localhost:8000/payments/pesapal/callback
 * - Production: https://yourdomain.com/payments/pesapal/callback
 * 
 * The route path is the same across all environments.
 * The full URL is built from config('app.url') . '/payments/pesapal/callback'
 */
Route::post('/payments/pesapal/callback', [PesapalController::class, 'callback'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->name('api.pesapal.callback');

Route::post('/payments/pesapal/ipn', [PesapalController::class, 'ipn'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->name('api.pesapal.ipn');

/**
 * M-Pesa Daraja API callbacks
 * These routes receive STK push confirmation from Safaricom
 * No authentication or CSRF verification needed
 */
Route::post('/mpesa/callback', [MpesaController::class, 'callback'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->name('api.mpesa.callback');

/**
 * BnB booking M-Pesa callback - entirely separate from the tenant rent-collection
 * callback above (App\Services\BnbMpesaService, not MpesaService).
 */
Route::post('/bookings/mpesa/callback', [\App\Http\Controllers\BookingPaymentController::class, 'callback'])
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
    ->name('api.bookings.mpesa.callback');
