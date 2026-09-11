<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One alert "rule" a signed-up user has set up - either a general criteria
 * match (house_id null, house_types/areas/max_rent/listing_mode describe what
 * they want), or a "notify me when this specific house is available again"
 * watch (house_id set, everything else null) created from the unavailable-
 * listing page. See App\Services\HouseAlertMatchService for the matching and
 * notification logic, run from House::booted() whenever a house becomes
 * newly available.
 */
class HouseAlert extends Model
{
    protected $fillable = [
        'user_id',
        'house_id',
        'house_types',
        'areas',
        'max_rent',
        'listing_mode',
    ];

    protected function casts(): array
    {
        return [
            'house_types' => 'array',
            'areas' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function isSpecificHouse(): bool
    {
        return filled($this->house_id);
    }

    /** Short, human-readable summary for the Alerts list page. */
    public function describe(): string
    {
        if ($this->isSpecificHouse()) {
            return $this->house
                ? 'When "' . $this->house->publicName() . '" is available again'
                : 'A specific listing (no longer exists)';
        }

        $parts = [];

        if (filled($this->house_types)) {
            $parts[] = implode(' or ', $this->house_types);
        }

        if (filled($this->areas)) {
            $parts[] = 'in ' . implode(' or ', $this->areas);
        }

        if ($this->max_rent) {
            $parts[] = 'under KES ' . number_format($this->max_rent);
        }

        if ($this->listing_mode) {
            $parts[] = $this->listing_mode === 'short_term' ? '(short stays)' : '(long-term)';
        }

        return $parts ? implode(' ', $parts) : 'Any new listing';
    }
}
