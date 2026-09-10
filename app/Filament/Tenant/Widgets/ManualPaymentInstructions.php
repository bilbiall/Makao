<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Setting;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Filament-panel counterpart to resources/views/livewire/tenant/invoices.blade.php's
 * "How to pay" card - the app-shell (the primary UI tenants land on) already shows
 * this, but the Filament tenant panel at /tenant never did, leaving a tenant on
 * "manual" payment mode with a hidden Pay Now button and no explanation of how to
 * actually pay (see InvoiceResource::paymentModeIsAutomatic()).
 */
class ManualPaymentInstructions extends Widget
{
    protected static string $view = 'filament.tenant.widgets.manual-payment-instructions';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return filled(static::details());
    }

    public function getDetails(): array
    {
        return static::details();
    }

    protected static function details(): array
    {
        $landlordId = Auth::user()?->landlord_id;

        if (!$landlordId) {
            return [];
        }

        $payload = Setting::forLandlord($landlordId)->payload ?? [];

        if (($payload['payment_mode'] ?? 'manual') === 'automatic') {
            return [];
        }

        return array_filter($payload['manual_payment'] ?? []);
    }
}
