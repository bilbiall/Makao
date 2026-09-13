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
        'preferred_visit_date',
        'status',
        'requested_at',
        'admin_notes',
        'handled_by',
        'landlord_id',
        'contacted_at',
        'contacted_by',
    ];

    protected function casts(): array
    {
        return [
            'preferred_visit_date' => 'date',
            'requested_at' => 'datetime',
            'contacted_at' => 'datetime',
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
                $title = 'New Viewing Request';
                $message = ($requester->name ?? 'A user') . " requested a viewing for {$house?->house_name}"
                    . ($request->preferred_visit_date ? ', available ' . $request->preferred_visit_date->format('d M Y') : '') . '.';

                foreach (static::notificationRecipients($request, $house) as $recipient) {
                    $recipient->notify(new \App\Notifications\DatabaseNotification(
                        $title,
                        $message,
                        route('app.admin.viewing-requests')
                    ));

                    if (filled($recipient->email)) {
                        try {
                            \App\Helpers\EmailHelper::send($recipient->email, $title, $message, $request->landlord_id);
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('Viewing request email failed', [
                                'viewing_request_id' => $request->id,
                                'landlord_id' => $request->landlord_id,
                                'email' => $recipient->email,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
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

    /**
     * Who gets notified of a new request: whichever individual staff users and/or
     * whole staff roles the landlord chose in Settings > Notifications (see
     * App\Livewire\AdminApp\Settings), or - if they've never configured this - the
     * same hardcoded default as before (admin/landlord + any manager/caretaker
     * assigned to this house's location), so an unconfigured landlord's behavior
     * doesn't change.
     */
    protected static function notificationRecipients(self $request, ?House $house)
    {
        $config = \App\Models\Setting::forLandlord($request->landlord_id)->payload['notifications']['viewing_requests'] ?? [];
        $userIds = $config['user_ids'] ?? [];
        $staffRoleIds = $config['staff_role_ids'] ?? [];

        if (empty($userIds) && empty($staffRoleIds)) {
            $staffUserIds = \App\Models\StaffAssignment::withoutGlobalScopes()
                ->where('location_id', $house?->location_id)
                ->pluck('user_id');

            return \App\Models\User::where('landlord_id', $request->landlord_id)
                ->where(function ($q) use ($staffUserIds) {
                    $q->whereIn('role', ['admin', 'landlord'])
                        ->orWhereIn('id', $staffUserIds);
                })
                ->get();
        }

        return \App\Models\User::where('landlord_id', $request->landlord_id)
            ->where(function ($q) use ($userIds, $staffRoleIds) {
                $q->whereIn('id', $userIds)
                    ->orWhereIn('staff_role_id', $staffRoleIds);
            })
            ->get();
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

    public function contactedBy()
    {
        return $this->belongsTo(User::class, 'contacted_by');
    }
}
