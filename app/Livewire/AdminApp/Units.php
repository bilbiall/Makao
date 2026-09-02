<?php

namespace App\Livewire\AdminApp;

use App\Models\House;
use App\Models\Landlord;
use App\Models\Location;
use App\Services\PackageLimitService;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Units extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $propertyFilter = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $modeFilter = '';

    public bool $showImport = false;
    public $importFile = null;
    public ?array $importSummary = null;
    public array $importErrors = [];

    public bool $showAddUnit = false;
    public string $unit_property_id = '';
    public string $unit_name = '';
    public string $unit_type = '';
    public string $unit_listing_mode = 'long_term'; // 'long_term' | 'short_term'
    public string $unit_rent_amount = '';
    public string $unit_bnb_nightly = '';
    public string $unit_bnb_weekly = '';
    public string $unit_bnb_monthly = '';

    protected const IMPORT_COLUMNS = [
        'property_name', 'area', 'unit_name', 'unit_type', 'listing_type',
        'rent_amount', 'bnb_nightly_price', 'bnb_weekly_price', 'bnb_monthly_price',
    ];

    public function canManageProperties(): bool
    {
        // Matches Properties::canManageProperties() - only the account owner/staff with
        // full admin rights create new properties, not Manager/Caretaker/Agent.
        return in_array(Auth::user()->role, ['admin', 'landlord']);
    }

    protected function unitsQuery()
    {
        $query = House::with(['location', 'pricePackages'])->orderBy('house_name');
        StaffScope::onHouse($query);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('house_name', 'like', "%{$this->search}%")
                    ->orWhere('house_type', 'like', "%{$this->search}%")
                    ->orWhereHas('location', fn ($lq) => $lq->where('location_name', 'like', "%{$this->search}%"));
            });
        }

        if ($this->propertyFilter !== '') {
            $query->where('location_id', $this->propertyFilter);
        }

        if ($this->typeFilter !== '') {
            $query->where('house_type', $this->typeFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('house_status', $this->statusFilter);
        }

        if ($this->modeFilter !== '') {
            $query->where('listing_mode', $this->modeFilter);
        }

        return $query;
    }

    protected function propertiesForFilter()
    {
        $query = Location::orderBy('location_name');
        if (StaffScope::isScopedStaff()) {
            $query->whereIn('id', StaffScope::locationIds());
        } elseif (StaffScope::isAgent()) {
            $query->whereRaw('1 = 0');
        }

        return $query->get();
    }

    /**
     * Owner-facing publish switch, independent of house_status - toggled straight
     * from the unit list, no separate edit screen needed. Scoped through the same
     * StaffScope-filtered query as the list itself, so a caretaker/manager can only
     * flip units within their own assigned location(s).
     */
    public function togglePublish(int $houseId): void
    {
        $house = $this->unitsQuery()->whereKey($houseId)->firstOrFail();
        $house->update(['is_published' => !$house->is_published]);
    }

    public function downloadTemplate()
    {
        $header = implode(',', self::IMPORT_COLUMNS);
        $rows = [
            'Kilimani Breeze Apartments,Kilimani,A1,1 Bedroom,rental,45000,,,',
            'Kilimani Breeze Apartments,Kilimani,Studio 2,Studio,bnb,,3500,20000,65000',
        ];
        $csv = $header . "\n" . implode("\n", $rows) . "\n";

        return response()->streamDownload(fn () => print($csv), 'units-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(): void
    {
        $this->validate(['importFile' => 'required|file|mimes:csv,txt|max:2048']);

        $rows = array_map('str_getcsv', file($this->importFile->getRealPath()));
        $header = array_map(fn ($h) => trim((string) $h), array_shift($rows) ?? []);

        $landlordId = Auth::user()->landlord_id;
        $landlord = $landlordId ? Landlord::find($landlordId) : null;
        $limitService = app(PackageLimitService::class);

        $created = ['properties' => 0, 'units' => 0];
        $errors = [];
        $propertyCache = [];

        foreach ($rows as $index => $row) {
            $lineNo = $index + 2; // account for the header row

            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // skip blank rows
            }

            $data = array_combine($header, array_pad($row, count($header), null));
            if ($data === false) {
                $errors[] = "Row {$lineNo}: column count doesn't match the header row.";
                continue;
            }

            try {
                $propertyName = trim((string) ($data['property_name'] ?? ''));
                $unitName = trim((string) ($data['unit_name'] ?? ''));
                $unitType = trim((string) ($data['unit_type'] ?? ''));
                $listingTypeRaw = strtolower(trim((string) ($data['listing_type'] ?? '')));

                if ($propertyName === '') {
                    throw new \RuntimeException('property_name is required.');
                }
                if ($unitName === '') {
                    throw new \RuntimeException('unit_name is required.');
                }
                if ($unitType === '') {
                    throw new \RuntimeException('unit_type is required.');
                }
                if (!in_array($listingTypeRaw, ['rental', 'bnb'], true)) {
                    throw new \RuntimeException('listing_type must be "rental" or "bnb".');
                }

                $listingMode = $listingTypeRaw === 'bnb' ? 'short_term' : 'long_term';
                $cacheKey = strtolower($propertyName);

                if (!isset($propertyCache[$cacheKey])) {
                    $location = Location::whereRaw('LOWER(location_name) = ?', [$cacheKey])->first();

                    if (!$location) {
                        if (!$this->canManageProperties()) {
                            throw new \RuntimeException("property \"{$propertyName}\" doesn't exist yet, and you don't have permission to create new properties.");
                        }
                        if (!$limitService->canAdd('locations', $landlord)) {
                            throw new \RuntimeException($limitService->limitMessage('locations', $landlord));
                        }

                        $location = Location::create([
                            'location_name' => $propertyName,
                            'geo_id' => trim((string) ($data['area'] ?? '')) ?: null,
                        ]);
                        $created['properties']++;
                    } elseif (StaffScope::isScopedStaff() && !in_array($location->id, StaffScope::locationIds(), true)) {
                        throw new \RuntimeException("you don't manage the property \"{$propertyName}\".");
                    }

                    $propertyCache[$cacheKey] = $location;
                }

                $location = $propertyCache[$cacheKey];

                if (!$limitService->canAdd('houses', $landlord)) {
                    throw new \RuntimeException($limitService->limitMessage('houses', $landlord));
                }

                $rentAmount = null;
                $bnbPrices = [];

                if ($listingMode === 'long_term') {
                    $rent = trim((string) ($data['rent_amount'] ?? ''));
                    if ($rent === '' || !is_numeric($rent)) {
                        throw new \RuntimeException('rent_amount is required and must be a number for rental units.');
                    }
                    $rentAmount = (float) $rent;
                } else {
                    $priceColumns = [
                        ['bnb_nightly_price', 'Nightly', 'night'],
                        ['bnb_weekly_price', 'Weekly', 'week'],
                        ['bnb_monthly_price', 'Monthly', 'month'],
                    ];
                    foreach ($priceColumns as [$column, $label, $unit]) {
                        $value = trim((string) ($data[$column] ?? ''));
                        if ($value !== '') {
                            if (!is_numeric($value)) {
                                throw new \RuntimeException("{$column} must be a number.");
                            }
                            $bnbPrices[] = ['name' => $label, 'price' => (float) $value, 'billing_unit' => $unit];
                        }
                    }
                    if (empty($bnbPrices)) {
                        throw new \RuntimeException('at least one of bnb_nightly_price, bnb_weekly_price, bnb_monthly_price is required for bnb units.');
                    }
                }

                $house = House::create([
                    'house_name' => $unitName,
                    'house_type' => $unitType,
                    'rent_amount' => $rentAmount,
                    'location_id' => $location->id,
                    'house_status' => 'Vacant',
                    'listing_mode' => $listingMode,
                ]);

                foreach ($bnbPrices as $sortOrder => $price) {
                    $house->pricePackages()->create($price + ['sort_order' => $sortOrder]);
                }

                $created['units']++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$lineNo}: " . $e->getMessage();
            }
        }

        $this->importFile = null;
        $this->importSummary = $created;
        $this->importErrors = $errors;
        $this->resetPage();
    }

    protected function addUnitRules(): array
    {
        return [
            'unit_property_id' => 'required|exists:locations,id',
            'unit_name' => 'required|string|max:255',
            'unit_type' => 'required|string|in:' . implode(',', House::UNIT_TYPES),
            'unit_listing_mode' => 'required|in:long_term,short_term',
            'unit_rent_amount' => 'nullable|numeric',
            'unit_bnb_nightly' => 'nullable|numeric',
            'unit_bnb_weekly' => 'nullable|numeric',
            'unit_bnb_monthly' => 'nullable|numeric',
        ];
    }

    public function addUnit(): void
    {
        $this->validate($this->addUnitRules());

        $location = Location::find($this->unit_property_id);
        if (StaffScope::isScopedStaff() && !in_array($location->id, StaffScope::locationIds(), true)) {
            session()->flash('unit-error', "You don't manage that property.");
            return;
        }

        if ($this->unit_listing_mode === 'long_term' && $this->unit_rent_amount === '') {
            $this->addError('unit_rent_amount', 'Rent amount is required for a rental unit.');
            return;
        }

        $bnbPrices = [
            ['name' => 'Nightly', 'price' => $this->unit_bnb_nightly, 'billing_unit' => 'night'],
            ['name' => 'Weekly', 'price' => $this->unit_bnb_weekly, 'billing_unit' => 'week'],
            ['name' => 'Monthly', 'price' => $this->unit_bnb_monthly, 'billing_unit' => 'month'],
        ];
        $bnbPrices = array_values(array_filter($bnbPrices, fn ($p) => $p['price'] !== ''));

        if ($this->unit_listing_mode === 'short_term' && empty($bnbPrices)) {
            $this->addError('unit_bnb_nightly', 'Set at least one BnB price (nightly, weekly, or monthly).');
            return;
        }

        $landlordId = Auth::user()->landlord_id;
        $landlord = $landlordId ? Landlord::find($landlordId) : null;
        $limitService = app(PackageLimitService::class);
        if (!$limitService->canAdd('houses', $landlord)) {
            session()->flash('unit-error', $limitService->limitMessage('houses', $landlord));
            return;
        }

        $house = House::create([
            'house_name' => $this->unit_name,
            'house_type' => $this->unit_type,
            'rent_amount' => $this->unit_listing_mode === 'long_term' ? $this->unit_rent_amount : null,
            'location_id' => $location->id,
            'house_status' => 'Vacant',
            'listing_mode' => $this->unit_listing_mode,
        ]);

        foreach ($bnbPrices as $sortOrder => $price) {
            $house->pricePackages()->create($price + ['sort_order' => $sortOrder]);
        }

        $this->reset([
            'unit_property_id', 'unit_name', 'unit_type', 'unit_rent_amount',
            'unit_bnb_nightly', 'unit_bnb_weekly', 'unit_bnb_monthly', 'showAddUnit',
        ]);
        $this->unit_listing_mode = 'long_term';
        $this->resetPage();
        session()->flash('unit-added', 'Unit added successfully.');
    }

    public function render()
    {
        return view('livewire.admin-app.units', [
            'units' => $this->unitsQuery()->paginate(15),
            'properties' => $this->propertiesForFilter(),
            'unitTypes' => House::UNIT_TYPES,
        ])->layout('components.layouts.app', ['title' => 'Units', 'hideHeading' => true]);
    }
}
