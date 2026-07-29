<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'billing_interval',
        'max_locations',
        'max_houses',
        'max_tenants',
        'features',
        'trial_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function limitFor(string $resource): ?int
    {
        return match ($resource) {
            'locations' => $this->max_locations,
            'houses' => $this->max_houses,
            'tenants' => $this->max_tenants,
            default => null,
        };
    }
}
