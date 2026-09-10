<?php

namespace App\Models;

use App\Models\Concerns\BelongsToLandlord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A landlord's own charge type (Water, Trash, ...) - replaces the old hardcoded
 * water/electricity/internet/trash columns on Bill. location_id null means "all my
 * properties", set means "only that property" - same nullable-scope idea as
 * MpesaChannel.location_id. is_recurring + default_amount drive
 * GenerateRecurringBills, which auto-creates a bill_item of this type/amount for every
 * tenant in the scoped propert(y/ies) each month.
 */
class BillType extends Model
{
    use BelongsToLandlord;

    protected $fillable = [
        'landlord_id',
        'location_id',
        'name',
        'default_amount',
        'is_recurring',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function appliesToTenant(Tenant $tenant): bool
    {
        if ($this->location_id === null) {
            return true;
        }

        return $tenant->house?->location_id === $this->location_id;
    }
}
