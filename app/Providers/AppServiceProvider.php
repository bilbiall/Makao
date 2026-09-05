<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\Rules\Password;
use App\Helpers\ActivityLogger;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\NoticeToVacate;
use App\Models\Payment;
use App\Models\Tenant;
use App\Observers\TenantObserver;
use Livewire\Livewire;
use App\Http\Livewire\ChatPanel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Applies to every signup/password-reset (landlord accounts hold M-Pesa/Pesapal
        // credentials, so the bare 8-char default isn't enough). uncompromised() checks
        // against the Have I Been Pwned breach corpus via k-anonymity (no full password
        // ever leaves the server) and fails open (doesn't block signup) if that lookup
        // itself is unreachable, so this can't turn a network hiccup into a signup outage.
        Password::defaults(fn () => Password::min(8)->uncompromised());

        // Register Tenant observer
        Tenant::observe(TenantObserver::class);

        // Short, stable keys for Message::attachment_type - decouples the stored value
        // from the model's namespace/class name (see the messages.attachment_* columns).
        Relation::morphMap([
            'invoice' => Invoice::class,
            'bill' => Bill::class,
            'payment' => Payment::class,
            'notice' => NoticeToVacate::class,
        ]);

        // Register Livewire chat-panel component alias (ensures discovery on some setups)
        try {
            Livewire::component('chat-panel', ChatPanel::class);
        } catch (\Throwable $e) {
            // ignore if Livewire isn't available at boot time
        }

        // Record user login events
        Event::listen(Login::class, function (Login $event) {
            try {
                ActivityLogger::log('login', $event->user->id ?? null, 'User logged in');
            } catch (\Throwable $e) {
                // avoid breaking boot if logging fails
            }
        });
    }
}
