<?php

namespace App\Services;

use App\Models\Landlord;
use App\Models\Package;

class PackageLimitService
{
    public function currentPackage(?Landlord $landlord): ?Package
    {
        if (!$landlord) {
            return null;
        }

        return $landlord->currentSubscription?->package;
    }

    /**
     * Null means unlimited. Returns null (unlimited) if the landlord has no active
     * subscription at all, rather than blocking everything for a mis-provisioned account -
     * ExpireTrials/superadmin suspension is the intended way to actually cut someone off.
     */
    public function remaining(string $resource, ?Landlord $landlord): ?int
    {
        $package = $this->currentPackage($landlord);

        if (!$package) {
            return null;
        }

        $limit = $package->limitFor($resource);

        if ($limit === null) {
            return null;
        }

        $used = match ($resource) {
            'locations' => $landlord->locations()->count(),
            'houses' => $landlord->houses()->count(),
            'tenants' => $landlord->tenants()->count(),
            default => 0,
        };

        return max(0, $limit - $used);
    }

    public function canAdd(string $resource, ?Landlord $landlord, int $count = 1): bool
    {
        $remaining = $this->remaining($resource, $landlord);

        return $remaining === null || $remaining >= $count;
    }

    public function limitMessage(string $resource, ?Landlord $landlord): string
    {
        $package = $this->currentPackage($landlord);
        $limit = $package?->limitFor($resource);
        $label = ucfirst($resource);

        if (!$package) {
            return "You don't have an active subscription. Contact support to add {$label}.";
        }

        return "Your {$package->name} plan allows up to {$limit} {$resource}. Upgrade your plan to add more {$label}.";
    }
}
