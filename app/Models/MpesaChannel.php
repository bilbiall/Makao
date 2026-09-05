<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToLandlord;

/**
 * One Daraja app/shortcode a landlord controls. location_id null means "this
 * landlord's default channel, used by any property with no more specific channel
 * of its own"; set means "only that property uses it" - see MpesaService/
 * BnbMpesaService's loadConfigForLocation(), which resolves the most specific
 * channel for a given property and falls back to the landlord-wide Setting payload
 * (the pre-existing single-channel-per-landlord behavior) if no channel exists at
 * all, so a landlord who never touches this feature sees no change.
 */
class MpesaChannel extends Model
{
    use BelongsToLandlord;

    protected $fillable = [
        'landlord_id',
        'location_id',
        'label',
        'business_shortcode',
        'consumer_key',
        'consumer_secret',
        'passkey',
        'sandbox',
        'stk_enabled',
        'c2b_enabled',
        'c2b_registered_at',
    ];

    protected function casts(): array
    {
        return [
            'consumer_secret' => 'encrypted',
            'passkey' => 'encrypted',
            'sandbox' => 'boolean',
            'stk_enabled' => 'boolean',
            'c2b_enabled' => 'boolean',
            'c2b_registered_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Most specific channel for a given property: that Location's own channel if it
     * has one, else this landlord's default (location_id null) channel, else null -
     * meaning the caller should fall back to the legacy landlord-wide Setting payload.
     */
    public static function resolveFor(?int $locationId, ?int $landlordId): ?self
    {
        if (!$landlordId) {
            return null;
        }

        if ($locationId) {
            $specific = static::where('landlord_id', $landlordId)
                ->where('location_id', $locationId)
                ->first();

            if ($specific) {
                return $specific;
            }
        }

        return static::where('landlord_id', $landlordId)
            ->whereNull('location_id')
            ->first();
    }

    public static function findByShortcode(string $shortcode): ?self
    {
        return static::withoutGlobalScopes()->where('business_shortcode', $shortcode)->first();
    }
}
