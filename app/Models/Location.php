<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\ActivityLogger;


class Location extends Model
{
    // app/Models/Location.php
    use HasFactory;

    protected $fillable = [
        'location_name',
        'geo_id',
    ];

    public function houses()
    {
        return $this->hasMany(House::class);
    }

    protected static function booted()
    {
        static::created(function ($location) {
            try {
                $actor = auth()->id() ?? null;
                $details = "Location created: {$location->location_name} (geo_id: {$location->geo_id})";
                ActivityLogger::log('create_location', $actor, $details);

                // Notify admins about new location
                $admins = \App\Models\User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\DatabaseNotification(
                        'Location Created',
                        $details,
                        null
                    ));
                }
            } catch (\Throwable $e) {
                // ignore logging errors
            }
        });
        
        static::updated(function ($location) {
            try {
                $actor = auth()->id() ?? null;
                $changes = [];
                
                if ($location->wasChanged('location_name')) {
                    $changes[] = "name from '{$location->getOriginal('location_name')}' to '{$location->location_name}'";
                }
                if ($location->wasChanged('geo_id')) {
                    $changes[] = "geo_id from '{$location->getOriginal('geo_id')}' to '{$location->geo_id}'";
                }
                
                if (!empty($changes)) {
                    $details = "Updated location {$location->location_name}: " . implode(', ', $changes);
                    \App\Helpers\ActivityLogger::log('update_location', $actor, $details);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        });
        
        static::deleted(function ($location) {
            try {
                $actor = auth()->id() ?? null;
                $housesCount = $location->houses()->count();
                $details = "Location {$location->location_name} deleted ({$housesCount} houses)";
                \App\Helpers\ActivityLogger::log('delete_location', $actor, $details);
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
}
