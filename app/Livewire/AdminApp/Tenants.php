<?php

namespace App\Livewire\AdminApp;

use App\Helpers\SmsHelper;
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

    public bool $showForm = false;
    public string $name = '';
    public string $email = '';
    public string $phone_number = '';
    public $house_id = '';
    public string $date_admitted = '';

    // Tenant history/payments popup - which tenant's card is open, if any.
    public ?int $selectedTenantId = null;

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
            'totalTenants' => $totalTenants,
            'totalOutstanding' => $totalOutstanding,
            'admittedThisMonth' => $admittedThisMonth,
            'occupancyRate' => $occupancyRate,
            'occupiedUnits' => $occupiedUnits,
            'vacantUnits' => $vacantUnits,
        ])->layout('components.layouts.app', ['title' => 'Tenants']);
    }
}
