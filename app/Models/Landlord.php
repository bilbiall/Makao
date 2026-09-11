<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Landlord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'contact_email',
        'phone_number',
        'status',
        'onboarded_at',
        'c2b_enabled',
        'verification_status',
        'verification_requested_at',
        'verified_at',
        'verified_by',
        'verification_notes',
    ];

    protected function casts(): array
    {
        return [
            'onboarded_at' => 'datetime',
            'c2b_enabled' => 'boolean',
            'verification_requested_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function mpesaChannels(): HasMany
    {
        return $this->hasMany(MpesaChannel::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function houses(): HasMany
    {
        return $this->hasMany(House::class);
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // The actual login account for this business - the "landlord" role user
    // among the (possibly many) User rows this Landlord hasMany() of, which
    // also include their admin/manager/caretaker/agent staff and tenants.
    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'landlord');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // The most recent subscription is treated as "the" current one - a landlord
    // only ever has one active plan at a time, but history is kept for renewals/upgrades.
    public function currentSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany('starts_at');
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isOnboarded(): bool
    {
        return $this->onboarded_at !== null || $this->locations()->exists();
    }

    public function verifiedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function requestVerification(): void
    {
        $this->update([
            'verification_status' => 'pending',
            'verification_requested_at' => now(),
            'verification_notes' => null,
        ]);
    }

    public function approveVerification(int $adminUserId): void
    {
        $this->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $adminUserId,
        ]);
    }

    public function rejectVerification(int $adminUserId, ?string $notes = null): void
    {
        $this->update([
            'verification_status' => 'rejected',
            'verified_at' => null,
            'verified_by' => $adminUserId,
            'verification_notes' => $notes,
        ]);
    }

    /**
     * Business-name-based URLs (/superadmin/landlords/acme-properties-x7k2p9)
     * instead of a bare id, so one landlord's admin links don't look
     * interchangeable with another's - generated once on creation and never
     * changed afterward, same convention as House::getRouteKeyName().
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Accepts either the real slug or a bare numeric id (an old link from
     * before slugs existed) - same fallback convention as House::resolveRouteBinding().
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('slug', $value)->first()
            ?? (ctype_digit((string) $value) ? $this->where('id', (int) $value)->first() : null);
    }

    /** Public so the one-off backfill migration for pre-existing rows can reuse it. */
    public static function generateUniqueSlug(self $landlord): string
    {
        $base = \Illuminate\Support\Str::slug($landlord->name) ?: 'landlord';

        do {
            $slug = $base . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
        } while (static::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }

    protected static function booted()
    {
        static::creating(function (Landlord $landlord) {
            if (!$landlord->slug) {
                $landlord->slug = static::generateUniqueSlug($landlord);
            }
        });
    }
}
