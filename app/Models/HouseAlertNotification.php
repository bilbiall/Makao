<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dedup log for HouseAlertMatchService - one row per (alert, house) pair
 * already notified, so a house cycling Vacant -> Occupied -> Vacant again
 * doesn't re-notify the same still-open alert for the same listing every time.
 */
class HouseAlertNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'house_alert_id',
        'house_id',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }
}
