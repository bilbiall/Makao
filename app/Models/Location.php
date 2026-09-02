<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\ActivityLogger;
use App\Models\Concerns\BelongsToLandlord;
use App\Support\CurrentLandlord;


class Location extends Model
{
    // app/Models/Location.php
    use HasFactory, BelongsToLandlord;

    protected $fillable = [
        'location_name',
        'geo_id',
        'area_id',
        'landlord_id',
    ];

    public function houses()
    {
        return $this->hasMany(House::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    protected static function booted()
    {
        // geo_id (a plain string) is what every existing query filters/groups
        // on - keep it in sync from the picked Area's name so nothing else in
        // the app needs to know area_id exists.
        static::saving(function ($location) {
            if ($location->area_id && $location->isDirty('area_id')) {
                $location->geo_id = Area::find($location->area_id)?->name ?? $location->geo_id;
            }
        });

        static::creating(function ($location) {
            if (!$location->landlord_id) {
                $location->landlord_id = CurrentLandlord::id();
            }
        });

        static::creating(function ($location) {
            $landlord = $location->landlord_id ? \App\Models\Landlord::find($location->landlord_id) : null;
            $limitService = app(\App\Services\PackageLimitService::class);

            if ($landlord && !$limitService->canAdd('locations', $landlord)) {
                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title('Plan limit reached')
                    ->body($limitService->limitMessage('locations', $landlord))
                    ->send();

                return false;
            }
        });

        static::created(function ($location) {
            try {
                $actor = auth()->id() ?? null;
                $details = "Location created: {$location->location_name} (geo_id: {$location->geo_id})";
                ActivityLogger::log('create_location', $actor, $details);

                // Notify this landlord's own admins about the new location (not every landlord's)
                $admins = \App\Models\User::where('role', 'admin')->where('landlord_id', $location->landlord_id)->get();
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
