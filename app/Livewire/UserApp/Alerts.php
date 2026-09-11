<?php

namespace App\Livewire\UserApp;

use App\Models\Area;
use App\Models\House;
use App\Models\HouseAlert;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * "Notify me of new openings" signup - general criteria alerts (house_id null).
 * The per-listing "notify me when this one's available again" alert is created
 * instead from listings/unavailable.blade.php - see
 * PropertyListingController::notifyWhenAvailable().
 */
class Alerts extends Component
{
    public array $house_types = [];
    public array $areas = [];
    public ?int $max_rent = null;
    public string $listing_mode = '';

    public function store(): void
    {
        $this->validate([
            'house_types' => 'array',
            'house_types.*' => 'in:' . implode(',', House::UNIT_TYPES),
            'areas' => 'array',
            'areas.*' => 'string|max:255',
            'max_rent' => 'nullable|integer|min:0',
            'listing_mode' => 'nullable|in:long_term,short_term',
        ]);

        HouseAlert::create([
            'user_id' => Auth::id(),
            'house_types' => $this->house_types ?: null,
            'areas' => $this->areas ?: null,
            'max_rent' => $this->max_rent ?: null,
            'listing_mode' => $this->listing_mode ?: null,
        ]);

        $this->reset(['house_types', 'areas', 'max_rent', 'listing_mode']);

        session()->flash('alert-saved', 'Alert created. We\'ll email you when something matches.');
    }

    public function delete(int $alertId): void
    {
        Auth::user()->houseAlerts()->whereKey($alertId)->delete();
    }

    public function render()
    {
        $alerts = Auth::user()->houseAlerts()->with('house')->latest()->get();
        $areaOptions = Area::suggestionNames();

        return view('livewire.user-app.alerts', [
            'alerts' => $alerts,
            'areaOptions' => $areaOptions,
        ])->layout('components.layouts.app', ['title' => 'Alerts']);
    }
}
