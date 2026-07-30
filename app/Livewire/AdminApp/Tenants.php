<?php

namespace App\Livewire\AdminApp;

use App\Helpers\SmsHelper;
use App\Models\House;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CaretakerScope;
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

    public function render()
    {
        $tenants = CaretakerScope::onTenant(Tenant::query())
            ->with('house')
            ->latest()
            ->paginate(10);

        $vacantHouses = CaretakerScope::onHouse(House::where('house_status', 'Vacant'))->get();

        return view('livewire.admin-app.tenants', [
            'tenants' => $tenants,
            'vacantHouses' => $vacantHouses,
        ])->layout('components.layouts.app', ['title' => 'Tenants']);
    }
}
