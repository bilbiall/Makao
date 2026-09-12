<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A named price tier for a short_term (BnB) House - e.g. "Nightly" KES 3,500,
 * "Weekly" KES 20,000, "Monthly" KES 65,000. A house can have more than one.
 * `price` is the standing rate; discount_* fields (see AdminApp\Promotions) let
 * an agent/landlord run a time-boxed promotion on top of it without touching
 * the standing rate itself.
 */
class HousePricePackage extends Model
{
    protected $fillable = [
        'house_id',
        'name',
        'price',
        'discount_percent',
        'discount_label',
        'discount_starts_at',
        'discount_ends_at',
        'billing_unit',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_starts_at' => 'datetime',
            'discount_ends_at' => 'datetime',
        ];
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    /** A discount only "counts" while it's both set and inside its own window. */
    public function hasActiveDiscount(): bool
    {
        if (!$this->discount_percent) {
            return false;
        }

        $now = now();

        if ($this->discount_starts_at && $now->lt($this->discount_starts_at)) {
            return false;
        }

        if ($this->discount_ends_at && $now->gt($this->discount_ends_at)) {
            return false;
        }

        return true;
    }

    /** The standing price, discounted - regardless of whether the discount is currently active. */
    public function discountedPrice(): float
    {
        return round((float) $this->price * (1 - ($this->discount_percent ?? 0) / 100), 2);
    }

    /** What a guest actually pays right now - the discounted price only while the discount is active, else the standing price. */
    public function effectivePrice(): float
    {
        return $this->hasActiveDiscount() ? $this->discountedPrice() : (float) $this->price;
    }
}
