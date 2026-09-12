<?php

namespace App\Livewire\AdminApp;

use App\Models\House;
use App\Models\HousePricePackage;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Time-boxed discount toggles on a house's price packages - the pricing/promo
 * lever from the BnB marketing brainstorm. Deliberately its own narrow page
 * rather than folding into Units.php: an agent has zero access to editing a
 * property (StaffScope::denyIfAgent() fails closed on almost everything), but
 * should still be able to run a promotion on the specific houses they're
 * assigned to market - this page is scoped to exactly that, nothing else.
 */
class Promotions extends Component
{
    public ?int $editingPackageId = null;
    public string $discount_percent = '';
    public string $discount_label = '';
    public string $discount_starts_at = '';
    public string $discount_ends_at = '';

    public function mount(): void
    {
        abort_unless(StaffScope::isAgent() || in_array(Auth::user()->role, ['admin', 'landlord']), 403);
    }

    protected function housesQuery()
    {
        $query = House::where('listing_mode', 'short_term')->with('pricePackages');

        if (StaffScope::isAgent()) {
            $query->whereIn('id', StaffScope::houseIds());
        }

        return $query;
    }

    protected function rules(): array
    {
        return [
            'discount_percent' => 'required|integer|min:1|max:90',
            'discount_label' => 'nullable|string|max:255',
            'discount_starts_at' => 'nullable|date',
            'discount_ends_at' => 'nullable|date|after_or_equal:discount_starts_at',
        ];
    }

    public function startEdit(int $packageId): void
    {
        $package = $this->packageOrFail($packageId);

        $this->editingPackageId = $package->id;
        $this->discount_percent = (string) ($package->discount_percent ?? '');
        $this->discount_label = $package->discount_label ?? '';
        $this->discount_starts_at = optional($package->discount_starts_at)->format('Y-m-d\TH:i') ?? '';
        $this->discount_ends_at = optional($package->discount_ends_at)->format('Y-m-d\TH:i') ?? '';
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingPackageId', 'discount_percent', 'discount_label', 'discount_starts_at', 'discount_ends_at']);
    }

    /** Only a package belonging to a house this user is actually allowed to manage - never trust the id alone. */
    protected function packageOrFail(int $packageId): HousePricePackage
    {
        return HousePricePackage::whereIn('house_id', $this->housesQuery()->pluck('id'))->findOrFail($packageId);
    }

    public function save(): void
    {
        $this->validate();

        $package = $this->packageOrFail($this->editingPackageId);

        $package->update([
            'discount_percent' => (int) $this->discount_percent,
            'discount_label' => $this->discount_label ?: null,
            'discount_starts_at' => $this->discount_starts_at ?: null,
            'discount_ends_at' => $this->discount_ends_at ?: null,
        ]);

        $this->cancelEdit();
        session()->flash('promo-saved', 'Discount saved.');
    }

    public function clear(int $packageId): void
    {
        $this->packageOrFail($packageId)->update([
            'discount_percent' => null,
            'discount_label' => null,
            'discount_starts_at' => null,
            'discount_ends_at' => null,
        ]);

        session()->flash('promo-saved', 'Discount removed.');
    }

    public function render()
    {
        $houses = $this->housesQuery()->with('location')->get();

        return view('livewire.admin-app.promotions', compact('houses'))
            ->layout('components.layouts.app', ['title' => 'Promotions']);
    }
}
