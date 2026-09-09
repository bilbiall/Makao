<?php

namespace App\Models;

use App\Models\Concerns\BelongsToLandlord;
use App\Support\CurrentLandlord;
use Illuminate\Database\Eloquent\Model;

/**
 * Landlord-wide operating costs (electricity, water, internet, maintenance,
 * other) for a given month - not billed to any tenant, unlike Bill. One row
 * per month is the expected shape (matches how every source system that's
 * fed into this one has tracked it), but nothing enforces that here.
 */
class Expense extends Model
{
    use BelongsToLandlord;

    protected $fillable = [
        'landlord_id',
        'expense_month',
        'electricity',
        'water',
        'internet',
        'maintenance',
        'other',
        'notes',
    ];

    protected $casts = [
        'expense_month' => 'date',
        'electricity' => 'float',
        'water' => 'float',
        'internet' => 'float',
        'maintenance' => 'float',
        'other' => 'float',
    ];

    public function total(): float
    {
        return $this->electricity + $this->water + $this->internet + $this->maintenance + $this->other;
    }

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            if (! $expense->landlord_id) {
                $expense->landlord_id = CurrentLandlord::id();
            }
        });
    }
}
