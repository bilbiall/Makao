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

// Unified "app-like" logout for the new mobile-first app shell - the Filament panels
// each ship their own logout, but the app shell sits outside any panel, so it needs
// its own route hitting the same underlying "web" guard session every panel shares.
Route::post('/app/logout', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('generic.login');
})->middleware('auth')->name('app.logout');

// The mobile-first "app" experience - a custom Blade/Livewire shell (bottom tab bar on
// mobile, sidebar on desktop) that sits in front of the Filament panels. Filament itself
// is untouched and still fully reachable at /admin, /tenant, /superadmin; this is just
// the primary place login sends people now, since Filament's own chrome always reads as
// an admin dashboard rather than a product tenants/landlords use day to day.
Route::middleware(['auth', \App\Http\Middleware\EnsureTenantRole::class])->prefix('app/tenant')->group(function () {
    Route::get('/dashboard', \App\Livewire\Tenant\Dashboard::class)->name('app.tenant.dashboard');
    Route::get('/invoices', \App\Livewire\Tenant\Invoices::class)->name('app.tenant.invoices');
    Route::get('/bills', \App\Livewire\Tenant\Bills::class)->name('app.tenant.bills');
    Route::get('/payments', \App\Livewire\Tenant\Payments::class)->name('app.tenant.payments');
    Route::get('/issues', \App\Livewire\Tenant\Issues::class)->name('app.tenant.issues');
    Route::get('/notices', \App\Livewire\Tenant\Notices::class)->name('app.tenant.notices');
    Route::get('/chat', fn () => view('tenant-app.chat'))->name('app.tenant.chat');
    Route::get('/profile', \App\Livewire\Profile::class)->name('app.tenant.profile');
});

Route::middleware(['auth', \App\Http\Middleware\EnsureAdminRole::class])->prefix('app/admin')->group(function () {
    Route::get('/dashboard', \App\Livewire\AdminApp\Dashboard::class)->name('app.admin.dashboard');
    Route::get('/tenants', \App\Livewire\AdminApp\Tenants::class)->name('app.admin.tenants');
    Route::get('/properties', \App\Livewire\AdminApp\Properties::class)->name('app.admin.properties');
    Route::get('/invoices', \App\Livewire\AdminApp\Invoices::class)->name('app.admin.invoices');
    Route::get('/payments', \App\Livewire\AdminApp\Payments::class)->name('app.admin.payments');
    Route::get('/bills', \App\Livewire\AdminApp\Bills::class)->name('app.admin.bills');
    Route::get('/issues', \App\Livewire\AdminApp\Issues::class)->name('app.admin.issues');
    Route::get('/notices', \App\Livewire\AdminApp\Notices::class)->name('app.admin.notices');
    Route::get('/reports', \App\Livewire\AdminApp\Reports::class)->name('app.admin.reports');
    Route::get('/users', \App\Livewire\AdminApp\Users::class)->name('app.admin.users');
    Route::get('/chat', fn () => view('admin-app.chat'))->name('app.admin.chat');
    Route::get('/settings', \App\Livewire\AdminApp\Settings::class)->name('app.admin.settings');
    Route::get('/profile', \App\Livewire\Profile::class)->name('app.admin.profile');
});

Route::middleware(['auth', \App\Http\Middleware\EnsureSuperadminRole::class])->prefix('app/superadmin')->group(function () {
    Route::get('/dashboard', \App\Livewire\SuperadminApp\Dashboard::class)->name('app.superadmin.dashboard');
    Route::get('/landlords', \App\Livewire\SuperadminApp\Landlords::class)->name('app.superadmin.landlords');
    Route::get('/packages', \App\Livewire\SuperadminApp\Packages::class)->name('app.superadmin.packages');
    Route::get('/subscriptions', \App\Livewire\SuperadminApp\Subscriptions::class)->name('app.superadmin.subscriptions');
    Route::get('/settings', \App\Livewire\SuperadminApp\PlatformSettings::class)->name('app.superadmin.settings');
    Route::get('/profile', \App\Livewire\Profile::class)->name('app.superadmin.profile');
});


