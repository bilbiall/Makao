<?php

namespace App\Livewire\Onboarding;

use App\Models\Area;
use App\Models\City;
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
    public string $display_name = '';
    public string $house_type = '';
    public string $listing_mode = 'long_term';
    public $rent_amount = '';
    public $bnb_nightly_price = '';
    public $bnb_weekly_price = '';
    public $bnb_monthly_price = '';

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

        // Same "typed value happens to match a seeded area" linking as
        // AdminApp\Properties::createProperty() - matches across every city
        // since this single field isn't scoped to one (unlike Properties' city
        // + area picker), same as the public search widgets.
        $area = $this->geo_id !== ''
            ? Area::whereRaw('LOWER(name) = ?', [strtolower($this->geo_id)])->first()
            : null;

        $this->createdLocation = Location::create([
            'location_name' => $this->location_name,
            'geo_id' => $this->geo_id ?: null,
            'area_id' => $area?->id,
        ]);

        $this->step = 2;
    }

    public function createHouse(): void
    {
        $this->validate([
            'house_name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'house_type' => 'required|string|in:' . implode(',', House::UNIT_TYPES),
            'listing_mode' => 'required|in:long_term,short_term',
            'rent_amount' => $this->listing_mode === 'long_term' ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'bnb_nightly_price' => 'nullable|numeric|min:0',
            'bnb_weekly_price' => 'nullable|numeric|min:0',
            'bnb_monthly_price' => 'nullable|numeric|min:0',
        ]);

        if ($this->listing_mode === 'short_term'
            && $this->bnb_nightly_price === '' && $this->bnb_weekly_price === '' && $this->bnb_monthly_price === '') {
            $this->addError('bnb_nightly_price', 'Add at least one price (nightly, weekly, or monthly).');
            return;
        }

        $house = House::create([
            'house_name' => $this->house_name,
            'display_name' => $this->display_name ?: null,
            'house_type' => $this->house_type,
            'rent_amount' => $this->listing_mode === 'long_term' ? $this->rent_amount : null,
            'location_id' => $this->createdLocation->id,
            'house_status' => 'Vacant',
            'listing_mode' => $this->listing_mode,
        ]);

        if ($this->listing_mode === 'short_term') {
            $prices = [
                ['field' => $this->bnb_nightly_price, 'name' => 'Nightly', 'billing_unit' => 'night'],
                ['field' => $this->bnb_weekly_price, 'name' => 'Weekly', 'billing_unit' => 'week'],
                ['field' => $this->bnb_monthly_price, 'name' => 'Monthly', 'billing_unit' => 'month'],
            ];
            foreach ($prices as $sortOrder => $price) {
                if ($price['field'] !== '' && $price['field'] !== null) {
                    $house->pricePackages()->create([
                        'name' => $price['name'],
                        'price' => $price['field'],
                        'billing_unit' => $price['billing_unit'],
                        'sort_order' => $sortOrder,
                    ]);
                }
            }
        }

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
            'cities' => City::breakdown(),
        ])
            ->layout('components.layouts.app', ['title' => 'Set up your account']);
    }
}
