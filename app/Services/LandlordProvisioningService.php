<?php

namespace App\Services;

use App\Helpers\EmailHelper;
use App\Helpers\EmailTemplateHelper;
use App\Models\Landlord;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LandlordProvisioningService
{
    /**
     * Create a new Landlord account, its owner User (role=landlord), and a trialing
     * Subscription, then log the new owner in. No Location is auto-created - the
     * landlord's own admin dashboard shows an empty state prompting them to add their
     * first property, so their package usage counters start clean.
     */
    public function provision(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $package = Package::where('is_active', true)->findOrFail($data['package_id']);

            $landlord = Landlord::create([
                'name' => $data['business_name'],
                'contact_email' => $data['email'],
                'phone_number' => $data['phone_number'] ?? null,
            ]);

            $user = User::create([
                'name' => $data['contact_name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'landlord',
                'landlord_id' => $landlord->id,
            ]);

            Subscription::create([
                'landlord_id' => $landlord->id,
                'package_id' => $package->id,
                'status' => 'trialing',
                'starts_at' => now(),
                'trial_ends_at' => now()->addDays($package->trial_days),
                'expires_at' => now()->addDays($package->trial_days),
            ]);

            $this->sendWelcomeEmail($user, $landlord, $package);
            $this->sendVerificationEmail($user);

            Auth::login($user);

            return $user;
        });
    }

    protected function sendWelcomeEmail(User $user, Landlord $landlord, Package $package): void
    {
        try {
            $body = EmailTemplateHelper::render('landlord_welcome', [
                'contact_name' => $user->name,
                'business_name' => $landlord->name,
                'package_name' => $package->name,
                'trial_days' => $package->trial_days,
                'site_url' => url('/login'),
            ], $user->landlord_id);

            EmailHelper::send($user->email, 'Welcome to ' . config('app.name'), $body, $user->landlord_id);
        } catch (\Throwable $e) {
            // ignore email failures (e.g. SMTP not configured) - signup must still succeed
        }
    }

    protected function sendVerificationEmail(User $user): void
    {
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            // ignore email failures (e.g. SMTP not configured) - signup must still succeed
        }
    }
}
