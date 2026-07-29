<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\ActivityLogger;
use App\Models\Concerns\BelongsToLandlord;


class House extends Model
{
    //
    use HasFactory, BelongsToLandlord;

    protected $fillable = [
        'house_name',
        //'number_of_rooms',
        'rent_amount',
        'location_id',
        'house_type',
        'house_status',
        'landlord_id',
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
        static::creating(function ($house) {
            if (!$house->landlord_id && $house->location_id) {
                $house->landlord_id = \App\Models\Location::withoutGlobalScopes()->find($house->location_id)?->landlord_id;
            }
        });

        static::creating(function ($house) {
            $landlord = $house->landlord_id ? \App\Models\Landlord::find($house->landlord_id) : null;
            $limitService = app(\App\Services\PackageLimitService::class);

            if ($landlord && !$limitService->canAdd('houses', $landlord)) {
                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title('Plan limit reached')
                    ->body($limitService->limitMessage('houses', $landlord))
                    ->send();

                return false;
            }
        });

        static::created(function ($house) {
            try {
                $actor = auth()->id() ?? null;
                $details = "House created: {$house->house_name} (Rent: {$house->rent_amount})";
                ActivityLogger::log('create_house', $actor, $details);

                // Notify this landlord's own admins about the new house
                $admins = \App\Models\User::where('role', 'admin')->where('landlord_id', $house->landlord_id)->get();
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
