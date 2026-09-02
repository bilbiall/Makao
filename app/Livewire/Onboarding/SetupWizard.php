<?php

namespace App\Livewire\Onboarding;

use App\Models\House;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * A short guided setup shown once, immediately after landlord signup, so a
 * brand-new account isn't dropped into an empty dashboard. Just sequences the
 * existing Location::create()/House::create() calls (with their existing plan-limit
 * checks) behind two simple steps - no new creation logic of its own.
 */
class SetupWizard extends Component
{
    public int $step = 1;

    // Step 1: first property
    public string $location_name = '';
    public string $geo_id = '';

    // Step 2: first unit
    public string $house_name = '';
    public string $house_type = '';
    public $rent_amount = '';

    public ?Location $createdLocation = null;

    public function mount(): void
    {
        $landlord = Auth::user()->landlord;

        // Already onboarded (or has properties from before this wizard existed) -
        // nothing to do here, go straight to the real dashboard.
        if ($landlord && $landlord->isOnboarded()) {
            redirect()->route('app.admin.dashboard');
        }
    }

    public function createLocation(): void
    {
        $this->validate([
            'location_name' => 'required|string|max:255',
            'geo_id' => 'nullable|string|max:255',
        ]);

        $this->createdLocation = Location::create([
            'location_name' => $this->location_name,
            'geo_id' => $this->geo_id ?: null,
        ]);

        $this->step = 2;
    }

    public function createHouse(): void
    {
        $this->validate([
            'house_name' => 'required|string|max:255',
            'house_type' => 'required|string|in:' . implode(',', House::UNIT_TYPES),
            'rent_amount' => 'required|numeric|min:0',
        ]);

        House::create([
            'house_name' => $this->house_name,
            'house_type' => $this->house_type,
            'rent_amount' => $this->rent_amount,
            'location_id' => $this->createdLocation->id,
            'house_status' => 'Vacant',
        ]);

        $this->finish();
    }

    public function skip(): void
    {
        $this->finish();
    }

    protected function finish(): void
    {
        Auth::user()->landlord?->update(['onboarded_at' => now()]);
        $this->redirect(route('app.admin.dashboard'));
    }

    public function render()
    {
        return view('livewire.onboarding.setup-wizard', [
            'unitTypes' => House::UNIT_TYPES,
        ])
            ->layout('components.layouts.app', ['title' => 'Set up your account']);
    }
}
