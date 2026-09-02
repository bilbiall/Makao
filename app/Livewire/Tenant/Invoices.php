<?php

namespace App\Livewire\Tenant;

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Invoices extends Component
{
    use WithPagination;

    public $paymentModeIsAutomatic = false;
    public array $manualPaymentDetails = [];

    public function mount(): void
    {
        $landlordId = Auth::user()->landlord_id;
        $payload = Setting::forLandlord($landlordId)->payload ?? [];
        $this->paymentModeIsAutomatic = ($payload['payment_mode'] ?? 'manual') === 'automatic';
        $this->manualPaymentDetails = array_filter($payload['manual_payment'] ?? []);
    }

    public function render()
    {
        $tenant = Auth::user()->tenant;

        $invoices = $tenant
            ? Invoice::where('tenant_id', $tenant->id)->latest()->paginate(10)
            : Invoice::whereRaw('1 = 0')->paginate(10);

        return view('livewire.tenant.invoices', ['invoices' => $invoices])
            ->layout('components.layouts.app', ['title' => 'Invoices']);
    }
}
