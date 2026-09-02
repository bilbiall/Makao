<?php

namespace App\Livewire\AdminApp;

use App\Models\Area;
use App\Models\City;
use App\Models\House;
use App\Models\Landlord;
use App\Models\Location;
use App\Services\PackageLimitService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Properties extends Component
{
    public bool $showPropertyForm = false;
    public string $location_name = '';
    public string $geo_id = '';

    public ?int $addingHouseTo = null;
    public string $house_name = '';
    public string $display_name = '';
    public string $house_type = '';
    public $rent_amount = '';
    public string $listing_mode = 'long_term';
    public $bnb_nightly_price = '';
    public $bnb_weekly_price = '';
    public $bnb_monthly_price = '';

    protected function baseQuery()
    {
        $user = Auth::user();
        $query = Location::with('houses');

        // Manager/Caretaker are narrowed to their assigned locations via the
        // staff_assignments pivot - same rule enforced in HouseResource::getEloquentQuery().
        // Agent is scoped to specific houses for bookings only, not properties at all.
        if (in_array($user->role, ['caretaker', 'manager'])) {
            $query->whereIn('id', $user->staffAssignments()->pluck('location_id'));
        } elseif ($user->role === 'agent') {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function canManageProperties(): bool
    {
        // Matches LocationResource::canAccess() - only the account owner/staff with
        // full admin rights create new properties, not Manager/Caretaker/Agent.
        return in_array(Auth::user()->role, ['admin', 'landlord']);
    }

    public function createProperty(): void
    {
        abort_unless($this->canManageProperties(), 403);

        $this->validate([
            'location_name' => 'required|string|max:255',
            'geo_id' => 'nullable|string|max:255',
        ]);

        $landlord = Landlord::find(Auth::user()->landlord_id);
        if (!app(PackageLimitService::class)->canAdd('locations', $landlord)) {
            session()->flash('properties-error', app(PackageLimitService::class)->limitMessage('locations', $landlord));
            return;
        }

        // Only linked to a canonical Area when the typed value actually matches
        // one already seeded (case-insensitive) - anything else (a city/area
        // combo we haven't seeded yet) still just saves as a plain geo_id string.
        $area = $this->geo_id !== ''
            ? Area::whereRaw('LOWER(name) = ?', [strtolower($this->geo_id)])->first()
            : null;

        Location::create([
            'location_name' => $this->location_name,
            'geo_id' => $this->geo_id ?: null,
            'area_id' => $area?->id,
        ]);

        $this->reset(['location_name', 'geo_id', 'showPropertyForm']);
        session()->flash('properties-status', 'Property added.');
    }

    /**
     * Only when empty - deleting a property with units still in it would
     * cascade-delete those houses (and, for any occupied one, its tenant and
     * their entire bills/invoices/payments history along with it). Delete the
     * units first (see Units::deleteUnit()) and the property itself becomes a
     * safe, no-history-lost delete.
     */
    public function deleteLocation(int $locationId): void
    {
        abort_unless($this->canManageProperties(), 403);

        $location = $this->baseQuery()->withCount('houses')->whereKey($locationId)->firstOrFail();

        if ($location->houses_count > 0) {
            session()->flash('properties-error', 'Delete all units in this property first, then you can delete the property itself.');
            return;
        }

        $location->delete();
        session()->flash('properties-status', 'Property deleted.');
    }

    public function startAddingHouse(int $locationId): void
    {
        $this->addingHouseTo = $locationId;
        $this->reset(['house_name', 'display_name', 'house_type', 'rent_amount', 'listing_mode', 'bnb_nightly_price', 'bnb_weekly_price', 'bnb_monthly_price']);
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

        // Confirm this location is actually one the current user can see, not just
        // any id - the same defense-in-depth already used for staff scoping elsewhere.
        $location = $this->baseQuery()->whereKey($this->addingHouseTo)->firstOrFail();

        $landlord = Landlord::find($location->landlord_id);
        if (!app(PackageLimitService::class)->canAdd('houses', $landlord)) {
            session()->flash('properties-error', app(PackageLimitService::class)->limitMessage('houses', $landlord));
            return;
        }

        $house = House::create([
            'house_name' => $this->house_name,
            'display_name' => $this->display_name ?: null,
            'house_type' => $this->house_type,
            'rent_amount' => $this->listing_mode === 'long_term' ? $this->rent_amount : null,
            'location_id' => $location->id,
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

        $this->reset(['house_name', 'display_name', 'house_type', 'rent_amount', 'listing_mode', 'bnb_nightly_price', 'bnb_weekly_price', 'bnb_monthly_price', 'addingHouseTo']);
        session()->flash('properties-status', 'Unit added. Add photos, a description, amenities and nearby places via Advanced view to make it visible on the public site.');
    }

    public function render()
    {
        $locations = $this->baseQuery()->get();

        return view('livewire.admin-app.properties', [
            'locations' => $locations,
            'unitTypes' => House::UNIT_TYPES,
            'cities' => City::breakdown(),
        ])
            ->layout('components.layouts.app', ['title' => 'Properties']);
    }
}
