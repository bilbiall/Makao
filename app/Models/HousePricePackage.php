<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A named price tier for a short_term (BnB) House - e.g. "Nightly" KES 3,500,
 * "Weekly" KES 20,000, "Monthly" KES 65,000. A house can have more than one.
 * Booking itself (Phase 2) is not implemented yet - this is just the pricing
 * data entry a landlord fills in when listing a unit as short-stay.
 */
class HousePricePackage extends Model
{
    protected $fillable = [
        'house_id',
        'name',
        'price',
        'billing_unit',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }
}
