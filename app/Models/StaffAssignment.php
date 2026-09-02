<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToLandlord;

/**
 * Grants a Manager or Caretaker User access to a specific Location (property).
 * Replaces the old single-column User.location_id caretaker scoping - a User can
 * now hold assignments across multiple locations, and the same mechanism covers
 * both the 'manager' and 'caretaker' roles instead of each having its own logic.
 */
class StaffAssignment extends Model
{
    use BelongsToLandlord;

    protected $fillable = [
        'user_id',
        'location_id',
        'house_id',
        'role',
        'assigned_by',
        'landlord_id',
    ];

    protected static function booted()
    {
        static::creating(function ($assignment) {
            if ($assignment->landlord_id) {
                return;
            }

            if ($assignment->location_id) {
                $assignment->landlord_id = Location::withoutGlobalScopes()->find($assignment->location_id)?->landlord_id;
            } elseif ($assignment->house_id) {
                $assignment->landlord_id = House::withoutGlobalScopes()->find($assignment->house_id)?->landlord_id;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // Only set for 'agent' rows - a specific short_term House grant, as opposed to
    // 'manager'/'caretaker' rows which grant an entire Location (property).
    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
