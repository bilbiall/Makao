<?php

namespace App\Livewire\AdminApp;

use App\Helpers\SmsHelper;
use App\Livewire\Concerns\ExportsCsv;
use App\Models\DeletedTenant;
use App\Models\House;
use App\Models\Tenant;
use App\Models\User;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Tenants extends Component
{
    use WithPagination;
    use ExportsCsv;

    public bool $showForm = false;
    public string $name = '';
    public string $email = '';
    public string $phone_number = '';
    public $house_id = '';
    public string $date_admitted = '';

    // Tenant history/payments popup - which tenant's card is open, if any.
    public ?int $selectedTenantId = null;

    // Edit form state - reuses the same fields as admit(), pre-filled.
    public ?int $editingTenantId = null;
    public string $edit_name = '';
    public string $edit_email = '';
    public string $edit_phone_number = '';
    public $edit_house_id = '';
    public string $edit_payment_account_code = '';
    public string $edit_date_admitted = '';

    public bool $showDeleted = false;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->date_admitted = now()->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|max:20',
            'house_id' => 'required|exists:houses,id',
            'date_admitted' => 'required|date',
        ];
    }

    protected function editRules(): array
    {
        return [
            'edit_name' => 'required|string|max:255',
            'edit_email' => 'required|email',
            'edit_phone_number' => 'required|string|max:20',
            'edit_house_id' => 'required|exists:houses,id',
            'edit_payment_account_code' => 'nullable|string|max:50',
            'edit_date_admitted' => 'required|date',
        ];
    }

    public function admit(): void
    {
        $this->validate();

        $password = Str::random(8);
        $landlordId = Auth::user()->landlord_id;

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'password' => bcrypt($password),
            'role' => 'tenant',
            'landlord_id' => $landlordId,
        ]);

        try {
            SmsHelper::sendSms(
                $this->phone_number,
                "Hi {$this->name}, your tenant account has been created. Login with Email: {$this->email}, Password: {$password} - " . \App\Helpers\AppHelper::getAppName($landlordId),
                $landlordId
            );
        } catch (\Throwable $e) {
            // ignore SMS failures (e.g. gateway not configured)
        }

        Tenant::create([
            'user_id' => $user->id,
            'house_id' => $this->house_id,
            'tenant_name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'date_admitted' => $this->date_admitted,
        ]);

        $this->reset(['name', 'email', 'phone_number', 'house_id', 'showForm']);
        $this->date_admitted = now()->format('Y-m-d');
        session()->flash('tenant-admitted', 'Tenant admitted successfully.');
    }

    public function viewTenant(int $tenantId): void
    {
        $this->selectedTenantId = $tenantId;
    }

    public function closeTenantModal(): void
    {
        $this->selectedTenantId = null;
    }

    public function getSelectedTenantProperty(): ?Tenant
    {
        if (!$this->selectedTenantId) {
            return null;
        }

        // Re-scoped the same way as the list below - a crafted selectedTenantId
        // must not leak a tenant outside this staff member's assigned properties.
        return StaffScope::onTenant(Tenant::query())
            ->with([
                'house',
                'invoices' => fn ($q) => $q->latest('invoice_date')->limit(15),
                'payments' => fn ($q) => $q->latest('payment_date')->limit(15),
            ])
            ->find($this->selectedTenantId);
    }

    public function whatsappUrl(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if (!Str::startsWith($digits, '254')) {
            $digits = '254' . ltrim($digits, '0');
        }

        return "https://wa.me/{$digits}";
    }

    public function editTenant(int $tenantId): void
    {
        $tenant = StaffScope::onTenant(Tenant::query())->findOrFail($tenantId);

        $this->editingTenantId = $tenant->id;
        $this->edit_name = $tenant->tenant_name;
        $this->edit_email = $tenant->email ?? '';
        $this->edit_phone_number = $tenant->phone_number ?? '';
        $this->edit_house_id = $tenant->house_id;
        $this->edit_payment_account_code = $tenant->payment_account_code ?? '';
        $this->edit_date_admitted = $tenant->date_admitted ? \Carbon\Carbon::parse($tenant->date_admitted)->format('Y-m-d') : now()->format('Y-m-d');
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingTenantId', 'edit_name', 'edit_email', 'edit_phone_number', 'edit_house_id', 'edit_payment_account_code', 'edit_date_admitted']);
    }

    public function saveTenant(): void
    {
        $this->validate($this->editRules());

        $tenant = StaffScope::onTenant(Tenant::query())->findOrFail($this->editingTenantId);

        $tenant->update([
            'tenant_name' => $this->edit_name,
            'email' => $this->edit_email,
            'phone_number' => $this->edit_phone_number,
            'house_id' => $this->edit_house_id,
            'payment_account_code' => $this->edit_payment_account_code ?: null,
            'date_admitted' => $this->edit_date_admitted,
        ]);

        $this->cancelEdit();
        session()->flash('tenant-updated', 'Tenant details updated.');
    }

    public function vacate(int $tenantId): void
    {
        $tenant = StaffScope::onTenant(Tenant::query())->findOrFail($tenantId);

        // TenantObserver::deleting() (registered app-wide) archives this tenancy
        // to DeletedTenant and Tenant::booted()'s `deleted` hook frees the house -
        // nothing else to do here.
        $tenant->delete();

        if ($this->selectedTenantId === $tenantId) {
            $this->closeTenantModal();
        }

        session()->flash('tenant-updated', 'Tenant vacated and archived.');
    }

    public function export()
    {
        $tenants = StaffScope::onTenant(Tenant::query())->with('house')->latest()->get();

        return $this->streamCsv(
            'tenants.csv',
            ['Name', 'Phone', 'Email', 'House', 'Date Admitted', 'Balance'],
            $tenants->map(fn (Tenant $tenant) => [
                $tenant->tenant_name,
                $tenant->phone_number,
                $tenant->email,
                $tenant->house?->house_name,
                optional($tenant->date_admitted ? \Carbon\Carbon::parse($tenant->date_admitted) : null)->format('Y-m-d'),
                $tenant->balance,
            ])
        );
    }

    public function render()
    {
        $query = StaffScope::onTenant(Tenant::query())->with('house')->latest();

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('tenant_name', 'like', $term)
                    ->orWhere('phone_number', 'like', $term)
                    ->orWhereHas('house', fn ($h) => $h->where('house_name', 'like', $term));
            });
        }

        $tenants = $query->paginate(10);

        $vacantHouses = StaffScope::onHouse(House::where('house_status', 'Vacant'))->get();

        // A tenant being edited keeps their own (Occupied) house selectable too,
        // not just currently-vacant units.
        $editHouses = $vacantHouses;
        if ($this->editingTenantId && $this->edit_house_id) {
            $currentHouse = House::find($this->edit_house_id);
            if ($currentHouse && !$editHouses->contains('id', $currentHouse->id)) {
                $editHouses = $editHouses->push($currentHouse);
            }
        }

        $deletedTenants = $this->showDeleted
            ? StaffScope::onHouse(DeletedTenant::query())->latest('deleted_at')->paginate(10, ['*'], 'deletedPage')
            : null;

        $houses = StaffScope::onHouse(House::query());
        $totalUnits = (clone $houses)->count();
        $occupiedUnits = (clone $houses)->where('house_status', 'Occupied')->count();
        $vacantUnits = $totalUnits - $occupiedUnits;
        $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100) : 0;

        $totalTenants = (clone StaffScope::onTenant(Tenant::query()))->count();
        $totalOutstanding = (float) (clone StaffScope::onTenant(Tenant::query()))->sum('balance');
        $admittedThisMonth = (clone StaffScope::onTenant(Tenant::query()))
            ->whereMonth('date_admitted', now()->month)
            ->whereYear('date_admitted', now()->year)
            ->count();

        return view('livewire.admin-app.tenants', [
            'tenants' => $tenants,
            'vacantHouses' => $vacantHouses,
            'editHouses' => $editHouses,
            'deletedTenants' => $deletedTenants,
            'totalTenants' => $totalTenants,
            'totalOutstanding' => $totalOutstanding,
            'admittedThisMonth' => $admittedThisMonth,
            'occupancyRate' => $occupancyRate,
            'occupiedUnits' => $occupiedUnits,
            'vacantUnits' => $vacantUnits,
        ])->layout('components.layouts.app', ['title' => 'Tenants']);
    }
}
