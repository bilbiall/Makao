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
}
