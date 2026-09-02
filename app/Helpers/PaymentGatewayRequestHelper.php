<?php

namespace App\Helpers;

use App\Filament\Superadmin\Resources\LandlordResource\Pages\ManageLandlordSettings;
use App\Models\Landlord;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\DatabaseNotification;

/**
 * A property owner can't self-serve real M-Pesa/Pesapal credentials (see
 * HasLandlordSettingsSchema's Payments tab) - they request the gateway(s) they
 * want, and this notifies whoever can actually go fill those credentials in:
 * their own landlord's 'admin' staff, and every platform 'superadmin'.
 */
class PaymentGatewayRequestHelper
{
    public static function methodLabel(string $method): string
    {
        return match ($method) {
            'mpesa' => 'M-Pesa',
            'pesapal' => 'Pesapal',
            'both' => 'M-Pesa and Pesapal',
            default => ucfirst($method),
        };
    }

    public static function submit(int $landlordId, string $method, ?string $note, ?int $requestedByUserId): void
    {
        $settings = Setting::forLandlord($landlordId);
        $payload = $settings->payload ?? [];
        $payload['payment_gateway_request'] = [
            'status' => 'pending',
            'method' => $method,
            'note' => $note,
            'requested_at' => now()->toIso8601String(),
            'requested_by' => $requestedByUserId,
        ];
        $settings->payload = $payload;
        $settings->save();

        static::notify($landlordId, $method, $note);
    }

    protected static function notify(int $landlordId, string $method, ?string $note): void
    {
        $landlord = Landlord::find($landlordId);
        $landlordName = $landlord?->name ?? 'A property owner';
        $methodLabel = static::methodLabel($method);

        $title = 'Automatic payment setup requested';
        $message = "{$landlordName} requested {$methodLabel} for automatic tenant payments." . ($note ? " Note: {$note}" : '');

        // The landlord's own trusted staff can fulfill this from their own
        // Settings > Payments tab (same landlord_id, same Setting row).
        $admins = User::where('role', 'admin')->where('landlord_id', $landlordId)->get();
        foreach ($admins as $admin) {
            $admin->notify(new DatabaseNotification($title, $message, null));
        }

        // Every platform superadmin can fulfill it too, from this landlord's
        // settings page in the Superadmin panel.
        $url = null;
        try {
            // This runs from the app-shell (a plain Livewire request, no Filament
            // panel active), so the target panel must be explicit - getUrl() would
            // otherwise resolve against Filament::getCurrentPanel(), which is null/
            // wrong here, and build a URL on the wrong panel's route names entirely.
            $url = ManageLandlordSettings::getUrl(['record' => $landlordId], panel: 'superadmin');
        } catch (\Throwable $e) {
            // Filament may not be booted in some contexts (e.g. queue worker) - the
            // notification still lands, just without a deep link.
        }

        $superadmins = User::where('role', 'superadmin')->get();
        foreach ($superadmins as $superadmin) {
            $superadmin->notify(new DatabaseNotification($title, $message, $url));
        }
    }
}
