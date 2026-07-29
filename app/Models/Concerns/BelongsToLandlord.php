<?php

namespace App\Models\Concerns;

use App\Models\Landlord;
use App\Models\Scopes\LandlordScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToLandlord
{
    public static function bootBelongsToLandlord(): void
    {
        static::addGlobalScope(new LandlordScope());
    }

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class);
    }
}
