<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToLandlord;

/**
 * Records a prospective renter's ("user") request to view a vacant House, and
 * tracks the landlord/manager/caretaker decision that follows the visit. This is
 * the record the admission workflow hangs off of - admitting a request promotes
 * the requesting User to a Tenant (see App\Filament\Resources\ViewingRequestResource).
 */
class ViewingRequest extends Model
{
    use BelongsToLandlord;

    protected $fillable = [
        'user_id',
        'house_id',
        'status',
        'requested_at',
        'admin_notes',
        'handled_by',
        'landlord_id',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($request) {
            $request->requested_at ??= now();
            $request->status ??= 'pending';

            if (!$request->landlord_id && $request->house_id) {
                $request->landlord_id = House::withoutGlobalScopes()->find($request->house_id)?->landlord_id;
            }
        });

        static::created(function ($request) {
            try {
                $house = $request->house;
                $requester = $request->user;

                // Notify the landlord/admins plus any manager/caretaker assigned to this house's location.
                $staffUserIds = \App\Models\StaffAssignment::withoutGlobalScopes()
                    ->where('location_id', $house?->location_id)
                    ->pluck('user_id');

                $recipients = \App\Models\User::where('landlord_id', $request->landlord_id)
                    ->where(function ($q) use ($staffUserIds) {
                        $q->whereIn('role', ['admin', 'landlord'])
                            ->orWhereIn('id', $staffUserIds);
                    })
                    ->get();

                foreach ($recipients as $recipient) {
                    $recipient->notify(new \App\Notifications\DatabaseNotification(
                        'New Viewing Request',
                        ($requester->name ?? 'A user') . " requested a viewing for {$house?->house_name}.",
                        null
                    ));
                }

                \App\Helpers\ActivityLogger::log(
                    'create_viewing_request',
                    $requester?->id,
                    "Viewing requested for {$house?->house_name} by {$requester?->name}"
                );
            } catch (\Throwable $e) {
                // ignore notification/logging errors
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function handledBy()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
