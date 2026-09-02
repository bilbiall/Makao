<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    // Named "app_settings" (not "settings") to avoid colliding with the
    // tomatophp/filament-settings-hub package's own "settings" table.
    protected $table = 'app_settings';

    protected $fillable = [
        'landlord_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Each landlord has their own settings row (SMS/email templates, M-Pesa/Pesapal
     * credentials, app name) - these are business configuration, not shared platform
     * config, so no two landlords may see or affect each other's here.
     *
     * Pass null only for the system-level fallback row (used for superadmin's own
     * notifications, since a superadmin belongs to no landlord).
     */
    public static function forLandlord(?int $landlordId): self
    {
        return cache()->rememberForever("settings_for_landlord_{$landlordId}", function () use ($landlordId) {
            return self::firstOrCreate(['landlord_id' => $landlordId]);
        });
    }

    /**
     * A landlord's own payload value for a dot-notation key (e.g. 'mpesa.consumer_key',
     * 'sms_url', 'app_name'), falling back to the platform-wide "system" row
     * (landlord_id null, configured via Platform Settings) when the landlord hasn't
     * set it themselves, then to $default. Lets business settings - app name, SMS
     * gateway, payment gateway credentials - default to whatever the platform
     * operator has configured instead of every landlord needing their own.
     */
    public static function effective(?int $landlordId, string $key, $default = null)
    {
        $own = data_get(self::forLandlord($landlordId)->payload, $key);
        if (filled($own)) {
            return $own;
        }

        if ($landlordId === null) {
            return $default;
        }

        return data_get(self::forLandlord(null)->payload, $key, $default);
    }

    /**
     * True once an admin/superadmin has actually entered an M-Pesa or Pesapal
     * consumer key for this landlord - the signal that "automatic" is real, not
     * just requested (see payment_gateway_request).
     *
     * Deliberately landlord-only, no platform-wide fallback: each landlord's tenants
     * pay into that landlord's own till/paybill, so credentials can't be shared
     * across landlords the way app name/SMS/SMTP can. The platform's own mpesa/pesapal
     * settings (Platform Settings > Payments) are a separate concern entirely - the
     * gateway used to charge landlords for their own Makao subscription, not something
     * a landlord's tenant payments could ever fall back to.
     */
    public function hasPaymentGatewayCredentials(): bool
    {
        $payload = $this->payload ?? [];

        return filled($payload['mpesa']['consumer_key'] ?? null)
            || filled($payload['pesapal']['consumer_key'] ?? null);
    }

    /** The landlord's own pending request to enable automatic payments, if any -
     *  see App\Helpers\PaymentGatewayRequestHelper. */
    public function pendingPaymentGatewayRequest(): ?array
    {
        $request = $this->payload['payment_gateway_request'] ?? null;

        return ($request && ($request['status'] ?? null) === 'pending') ? $request : null;
    }

    protected static function booted(): void
    {
        static::saved(function (self $setting) {
            cache()->forget("settings_for_landlord_{$setting->landlord_id}");
        });
    }
}
