<?php

namespace App\Livewire\UserApp;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/**
 * The claim step of the tenant self-service invite flow: a landlord admits a
 * tenant with just name/phone/house (see AdminApp\Tenants::admit() /
 * TenantResource) which creates a Tenant row with no user_id and texts a join
 * code (Tenant::sendInviteSms()). The recipient signs up (or logs in) as a
 * plain 'user'-role account, lands here, and proves they're the right person
 * for the right unit with three things - the code, their phone number, and
 * the unit name - before their account is promoted to 'tenant'.
 */
class ConnectApartment extends Component
{
    public string $code = '';
    public string $phone_number = '';
    public string $house_name = '';

    protected function rules(): array
    {
        return [
            'code' => 'required|string|max:12',
            'phone_number' => 'required|string|max:20',
            'house_name' => 'required|string|max:255',
        ];
    }

    public function claim()
    {
        $this->validate();

        $rateLimitKey = 'connect-apartment:' . Auth::id();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            session()->flash('connect-error', "Too many attempts - try again in {$seconds} seconds.");
            return;
        }

        $tenant = Tenant::whereNull('user_id')
            ->where('join_code', strtoupper(trim($this->code)))
            ->where('join_code_expires_at', '>', now())
            ->with('house')
            ->first();

        if (!$tenant) {
            RateLimiter::hit($rateLimitKey, 60);
            session()->flash('connect-error', 'Invalid or expired code. Ask your property manager to resend it.');
            return;
        }

        if ($this->normalizePhone($this->phone_number) !== $this->normalizePhone((string) $tenant->phone_number)) {
            RateLimiter::hit($rateLimitKey, 60);
            session()->flash('connect-error', "That phone number doesn't match our records.");
            return;
        }

        $typedHouseName = trim(strtolower($this->house_name));
        $actualHouseName = trim(strtolower($tenant->house?->publicName() ?? ''));

        if ($typedHouseName === '' || $typedHouseName !== $actualHouseName) {
            RateLimiter::hit($rateLimitKey, 60);
            session()->flash('connect-error', "That unit name doesn't match our records.");
            return;
        }

        RateLimiter::clear($rateLimitKey);

        $user = Auth::user();

        DB::transaction(function () use ($user, $tenant) {
            $user->update([
                'role' => 'tenant',
                'landlord_id' => $tenant->landlord_id,
                'phone_number' => $tenant->phone_number,
            ]);

            // update(), not create() - the Tenant row already exists from
            // admit-time, this only links it to the account that just proved it
            // belongs to it. Single-use: the code is cleared once claimed.
            $tenant->update([
                'user_id' => $user->id,
                'join_code' => null,
                'join_code_expires_at' => null,
            ]);
        });

        session()->flash('status', 'Your account is connected - welcome!');

        return redirect()->route('app.tenant.dashboard');
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone) ?? '';

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '254')) {
            $phone = '254' . $phone;
        }

        return $phone;
    }

    public function render()
    {
        return view('livewire.user-app.connect-apartment')
            ->layout('components.layouts.app', ['title' => 'Connect your apartment']);
    }
}
