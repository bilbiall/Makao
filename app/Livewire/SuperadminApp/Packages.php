<?php

namespace App\Livewire\SuperadminApp;

use App\Models\Package;
use Illuminate\Support\Str;
use Livewire\Component;

class Packages extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $slug = '';
    public $price = '';
    public string $billing_interval = 'monthly';
    public $trial_days = 14;
    public $max_locations = '';
    public $max_houses = '';
    public $max_tenants = '';
    public $max_users = '';
    public bool $feature_sms_notifications = false;
    public bool $feature_mpesa_payments = false;
    public bool $feature_reports = false;
    public $sort_order = 0;
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:packages,slug,' . ($this->editingId ?? 'NULL') . ',id',
            'price' => 'required|numeric|min:0',
            'billing_interval' => 'required|in:monthly,yearly',
            'trial_days' => 'required|integer|min:0',
            'max_locations' => 'nullable|integer|min:0',
            'max_houses' => 'nullable|integer|min:0',
            'max_tenants' => 'nullable|integer|min:0',
            'max_users' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer',
        ];
    }

    public function updatedName(string $value): void
    {
        // Only auto-follow the name while creating - once editing an existing
        // package, the slug is a stable identifier other records may reference,
        // so renaming the package shouldn't silently change it out from under them.
        if (!$this->editingId) {
            $this->slug = Str::slug($value);
        }
    }

    public function startCreate(): void
    {
        $this->reset([
            'editingId', 'name', 'slug', 'price', 'billing_interval', 'trial_days',
            'max_locations', 'max_houses', 'max_tenants', 'max_users',
            'feature_sms_notifications', 'feature_mpesa_payments', 'feature_reports',
            'sort_order', 'is_active',
        ]);
        $this->billing_interval = 'monthly';
        $this->trial_days = 14;
        $this->sort_order = Package::max('sort_order') + 1;
        $this->is_active = true;
        $this->showForm = true;
    }

    public function startEdit(int $packageId): void
    {
        $package = Package::findOrFail($packageId);

        $this->editingId = $package->id;
        $this->name = $package->name;
        $this->slug = $package->slug;
        $this->price = $package->price;
        $this->billing_interval = $package->billing_interval;
        $this->trial_days = $package->trial_days;
        $this->max_locations = $package->max_locations;
        $this->max_houses = $package->max_houses;
        $this->max_tenants = $package->max_tenants;
        $this->max_users = $package->max_users;
        $this->feature_sms_notifications = (bool) ($package->features['sms_notifications'] ?? false);
        $this->feature_mpesa_payments = (bool) ($package->features['mpesa_payments'] ?? false);
        $this->feature_reports = (bool) ($package->features['reports'] ?? false);
        $this->sort_order = $package->sort_order;
        $this->is_active = $package->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'billing_interval' => $this->billing_interval,
            'trial_days' => $this->trial_days,
            'max_locations' => $this->max_locations !== '' ? $this->max_locations : null,
            'max_houses' => $this->max_houses !== '' ? $this->max_houses : null,
            'max_tenants' => $this->max_tenants !== '' ? $this->max_tenants : null,
            'max_users' => $this->max_users !== '' ? $this->max_users : null,
            'features' => [
                'sms_notifications' => $this->feature_sms_notifications,
                'mpesa_payments' => $this->feature_mpesa_payments,
                'reports' => $this->feature_reports,
            ],
            'sort_order' => $this->sort_order !== '' ? $this->sort_order : 0,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Package::findOrFail($this->editingId)->update($data);
            session()->flash('package-saved', 'Package updated.');
        } else {
            Package::create($data);
            session()->flash('package-saved', 'Package created.');
        }

        $this->showForm = false;
    }

    public function delete(int $packageId): void
    {
        $package = Package::findOrFail($packageId);

        if ($package->subscriptions()->exists()) {
            session()->flash('package-error', 'Cannot delete a package with landlords subscribed to it - deactivate it instead.');
            return;
        }

        $package->delete();
        session()->flash('package-saved', 'Package deleted.');
    }

    public function render()
    {
        $packages = Package::orderBy('sort_order')->get();

        return view('livewire.superadmin-app.packages', ['packages' => $packages])
            ->layout('components.layouts.app', ['title' => 'Packages']);
    }
}
