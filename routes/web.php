<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarketingController;

Route::get('/', [MarketingController::class, 'home'])->name('home');
Route::get('/for-landlords', [MarketingController::class, 'forLandlords'])->name('for-landlords');
Route::get('/pricing', [MarketingController::class, 'pricing'])->name('pricing');
Route::get('/privacy', [MarketingController::class, 'privacy'])->name('privacy');
Route::get('/terms', [MarketingController::class, 'terms'])->name('terms');

//generic login
//Route::get('/login', fn () => view('generic-login'))->name('generic.login');
//Route::post('/login', \App\Http\Controllers\GenericLoginController::class)->name('generic.login.attempt');
// routes/web.php
Route::get('/login', fn () => view('generic-login'))->name('generic.login');
Route::post('/login', \App\Http\Controllers\GenericLoginController::class)
     ->middleware('throttle:5,1')
     ->name('generic.login.attempt');

// Homepage "try the demo" buttons - {role} is one of owner/admin/manager/
// caretaker/agent/tenant, resolved server-side against one fixed demo
// landlord (config('demo.landlord_email')), never a client-supplied account.
Route::post('/demo-login/{role}', \App\Http\Controllers\DemoLoginController::class)
    ->name('demo-login')
    ->middleware('throttle:20,1');

// Registration split - "list and manage properties" (landlord) vs "looking for a
// house" (user). Browsing listings itself needs no account at all; this is only
// the fork for the two self-service account types.
Route::get('/get-started', fn () => view('get-started'))->name('get-started');

// Self-serve landlord signup
Route::get('/signup', [\App\Http\Controllers\LandlordSignupController::class, 'create'])->name('signup');
Route::post('/signup', [\App\Http\Controllers\LandlordSignupController::class, 'store'])
    ->name('signup.store')
    ->middleware('throttle:6,1');

// Self-serve "looking for a house" signup
Route::get('/join', [\App\Http\Controllers\UserSignupController::class, 'create'])->name('user-signup');
Route::post('/join', [\App\Http\Controllers\UserSignupController::class, 'store'])
    ->name('user-signup.store')
    ->middleware('throttle:6,1');

// Public house discovery - no auth required to browse; watchlist/request-viewing
// redirect to login (with intended() bouncing back here) if not signed in.
Route::get('/houses', [\App\Http\Controllers\PropertyListingController::class, 'index'])->name('listings.index');
Route::get('/houses/{house}', [\App\Http\Controllers\PropertyListingController::class, 'show'])->name('listings.show');
Route::middleware(['auth'])->group(function () {
    Route::post('/houses/{house}/watchlist', [\App\Http\Controllers\PropertyListingController::class, 'toggleWatchlist'])
        ->name('listings.watchlist');
    // Verified only - unlike watchlisting (harmless) or a guest booking a stay (no
    // account to gate at all), this is the one unauthenticated-feeling, no-other-
    // friction action a bot account could spam landlords with.
    Route::post('/houses/{house}/request-viewing', [\App\Http\Controllers\PropertyListingController::class, 'requestViewing'])
        ->middleware('verified')
        ->name('listings.request-viewing');
});

// Public short-stay (BnB) discovery + booking - no auth required to browse or book as
// a guest; an authenticated 'user' account just gets its details autofilled and the
// booking linked back to it (see BookingController).
Route::get('/stays', [\App\Http\Controllers\StayListingController::class, 'index'])->name('stays.index');
Route::get('/stays/{house}', [\App\Http\Controllers\StayListingController::class, 'show'])->name('stays.show');
Route::post('/stays/{house}/book', [\App\Http\Controllers\BookingController::class, 'store'])->name('bookings.store');

// Signed, not auth-gated - most guests book without an account, so there's no session
// to check ownership against. A booking's id is sequential and its details (guest name/
// phone/dates/amount) are sensitive, so the link handed back at booking creation (and
// only that link) is what proves the visitor is allowed to see/pay it - see
// BookingController::store()'s redirect and bookings/show.blade.php's payment form.
Route::get('/bookings/{booking}', [\App\Http\Controllers\BookingController::class, 'show'])
    ->middleware('signed')
    ->name('bookings.show');
Route::post('/bookings/{booking}/mpesa/initiate', [\App\Http\Controllers\BookingPaymentController::class, 'initiate'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('bookings.mpesa.initiate');

// password reset (email or phone)
Route::get('/forgot-password', [\App\Http\Controllers\ForgotPasswordController::class, 'showForgotForm'])
    ->name('password.request');
Route::post('/forgot-password', [\App\Http\Controllers\ForgotPasswordController::class, 'sendReset'])
    ->middleware('throttle:6,1')
    ->name('password.email');
Route::get('/reset-password/{token}', [\App\Http\Controllers\ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\ResetPasswordController::class, 'reset'])
    ->middleware('throttle:6,1')
    ->name('password.update');

// Email verification - a soft gate (every role still lands in their own dashboard
// at signup regardless of verified_at) except for the couple of specific,
// bot-abusable actions that require it - see EmailVerificationController.
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [\App\Http\Controllers\EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [\App\Http\Controllers\EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

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
    Route::get('/viewing-requests', \App\Livewire\AdminApp\ViewingRequests::class)->name('app.admin.viewing-requests');
    Route::get('/properties', \App\Livewire\AdminApp\Properties::class)->name('app.admin.properties');
    Route::get('/units', \App\Livewire\AdminApp\Units::class)->name('app.admin.units');
    Route::get('/invoices', \App\Livewire\AdminApp\Invoices::class)->name('app.admin.invoices');
    Route::get('/invoices/print', [\App\Http\Controllers\AdminPrintController::class, 'invoices'])->name('app.admin.invoices.print');
    Route::get('/payments', \App\Livewire\AdminApp\Payments::class)->name('app.admin.payments');
    Route::get('/payments/print', [\App\Http\Controllers\AdminPrintController::class, 'payments'])->name('app.admin.payments.print');
    Route::get('/tenants/print', [\App\Http\Controllers\AdminPrintController::class, 'tenants'])->name('app.admin.tenants.print');
    Route::get('/bills', \App\Livewire\AdminApp\Bills::class)->name('app.admin.bills');
    Route::get('/issues', \App\Livewire\AdminApp\Issues::class)->name('app.admin.issues');
    Route::get('/notices', \App\Livewire\AdminApp\Notices::class)->name('app.admin.notices');
    Route::get('/reports', \App\Livewire\AdminApp\Reports::class)->name('app.admin.reports');
    Route::get('/bookings', \App\Livewire\AdminApp\Bookings::class)->name('app.admin.bookings');
    Route::get('/users', \App\Livewire\AdminApp\Users::class)->name('app.admin.users');
    Route::get('/chat', fn () => view('admin-app.chat'))->name('app.admin.chat');
    Route::get('/settings', \App\Livewire\AdminApp\Settings::class)->name('app.admin.settings');
    Route::get('/profile', \App\Livewire\Profile::class)->name('app.admin.profile');
});

Route::middleware(['auth', \App\Http\Middleware\EnsureUserRole::class])->prefix('app/user')->group(function () {
    Route::get('/dashboard', \App\Livewire\UserApp\Dashboard::class)->name('app.user.dashboard');
    Route::get('/watchlist', \App\Livewire\UserApp\Watchlist::class)->name('app.user.watchlist');
    Route::get('/applications', \App\Livewire\UserApp\Applications::class)->name('app.user.applications');
    Route::get('/profile', \App\Livewire\Profile::class)->name('app.user.profile');
});

// One-time guided setup for a brand-new landlord account - creates their first
// Property (Location) and Unit (House) through a short wizard instead of dropping
// them straight into an empty dashboard. Skips itself once a landlord has ≥1 Location.
Route::middleware(['auth', \App\Http\Middleware\EnsureAdminRole::class])->group(function () {
    Route::get('/app/admin/setup', \App\Livewire\Onboarding\SetupWizard::class)->name('app.admin.setup');
});

Route::middleware(['auth', \App\Http\Middleware\EnsureSuperadminRole::class])->prefix('app/superadmin')->group(function () {
    Route::get('/dashboard', \App\Livewire\SuperadminApp\Dashboard::class)->name('app.superadmin.dashboard');
    Route::get('/landlords', \App\Livewire\SuperadminApp\Landlords::class)->name('app.superadmin.landlords');
    Route::get('/packages', \App\Livewire\SuperadminApp\Packages::class)->name('app.superadmin.packages');
    Route::get('/subscriptions', \App\Livewire\SuperadminApp\Subscriptions::class)->name('app.superadmin.subscriptions');
    Route::get('/settings', \App\Livewire\SuperadminApp\PlatformSettings::class)->name('app.superadmin.settings');
    Route::get('/profile', \App\Livewire\Profile::class)->name('app.superadmin.profile');
});


