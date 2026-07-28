<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\ActivityLogger;


class House extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'house_name',
        //'number_of_rooms',
        'rent_amount',
        'location_id',
        'house_type',
        'house_status',
    ];

    //relationship with the location model
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    //relationship with the tenant model
    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    protected static function booted()
    {
        static::created(function ($house) {
            try {
                $actor = auth()->id() ?? null;
                $details = "House created: {$house->house_name} (Rent: {$house->rent_amount})";
                ActivityLogger::log('create_house', $actor, $details);

                // Notify admins about new house
                $admins = \App\Models\User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\DatabaseNotification(
                        'House Created',
                        $details,
                        null
                    ));
                }
            } catch (\Throwable $e) {
                // ignore logging errors
            }
        });
        
        static::updated(function ($house) {
            try {
                $actor = auth()->id() ?? null;
                $changes = [];
                
                if ($house->wasChanged('house_name')) {
                    $changes[] = "name from '{$house->getOriginal('house_name')}' to '{$house->house_name}'";
                }
                if ($house->wasChanged('rent_amount')) {
                    $changes[] = "rent from {$house->getOriginal('rent_amount')} to {$house->rent_amount}";
                }
                if ($house->wasChanged('house_status')) {
                    $changes[] = "status from '{$house->getOriginal('house_status')}' to '{$house->house_status}'";
                }
                if ($house->wasChanged('location_id')) {
                    $oldLocation = \App\Models\Location::find($house->getOriginal('location_id'));
                    $newLocation = $house->location;
                    $changes[] = "location from '{$oldLocation->location_name}' to '{$newLocation->location_name}'";
                }
                
                if (!empty($changes)) {
                    $details = "Updated house {$house->house_name}: " . implode(', ', $changes);
                    \App\Helpers\ActivityLogger::log('update_house', $actor, $details);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        });
        
        static::deleted(function ($house) {
            try {
                $actor = auth()->id() ?? null;
                $details = "House {$house->house_name} deleted (Rent: {$house->rent_amount})";
                \App\Helpers\ActivityLogger::log('delete_house', $actor, $details);
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
}
