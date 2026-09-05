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
    ];

    protected function casts(): array
    {
        return [
            'onboarded_at' => 'datetime',
            'c2b_enabled' => 'boolean',
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
}
