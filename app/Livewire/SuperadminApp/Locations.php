<?php

namespace App\Livewire\SuperadminApp;

use App\Models\City;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The app-shell counterpart to Filament's CityResource (Superadmin > Locations
 * in the desktop/"Advanced view" panel) - same underlying City model and
 * is_open toggle, just in this app's own mobile-first UI so a superadmin
 * never has to leave it to manage which cities are open to landlords.
 */
class Locations extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public string $name = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleOpen(int $cityId): void
    {
        $city = City::findOrFail($cityId);
        $city->update(['is_open' => ! $city->is_open]);
    }

    public function createCity(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:cities,name',
        ]);

        City::create(['name' => $this->name, 'is_open' => false]);

        $this->reset(['name', 'showForm']);
        session()->flash('city-saved', 'City added - it starts closed, open it when you\'re ready.');
    }

    public function render()
    {
        $cities = City::withCount('areas')
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.superadmin-app.locations', [
            'cities' => $cities,
            'totalCities' => City::count(),
            'openCities' => City::where('is_open', true)->count(),
            'configuredCities' => City::has('areas')->count(),
        ])->layout('components.layouts.app', ['title' => 'Locations']);
    }
}
