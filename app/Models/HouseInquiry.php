<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToLandlord;

/**
 * A guest's "ask a question" lead on a short-stay House - see the migration's
 * docblock for why this is deliberately separate from Booking. Notifies the
 * house's assigned agent (if any) plus this landlord's admins on creation, the
 * same way ViewingRequest already does for long-term listings.
 */
class HouseInquiry extends Model
{
    use BelongsToLandlord;

    protected $fillable = [
        'house_id',
        'name',
        'phone',
        'email',
        'message',
        'status',
        'landlord_id',
    ];

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $inquiry) {
            if (!$inquiry->landlord_id && $inquiry->house_id) {
                $inquiry->landlord_id = House::withoutGlobalScopes()->find($inquiry->house_id)?->landlord_id;
            }
        });

        static::created(function (self $inquiry) {
            try {
                $house = $inquiry->house;

                $agentIds = StaffAssignment::withoutGlobalScopes()
                    ->where('house_id', $inquiry->house_id)
                    ->pluck('user_id');

                $recipients = User::where('landlord_id', $inquiry->landlord_id)
                    ->where(function ($q) use ($agentIds) {
                        $q->whereIn('role', ['admin', 'landlord'])
                            ->orWhereIn('id', $agentIds);
                    })
                    ->get();

                foreach ($recipients as $recipient) {
                    $recipient->notify(new \App\Notifications\DatabaseNotification(
                        'New question about ' . ($house?->publicName() ?? 'a listing'),
                        "{$inquiry->name} asked: " . \Illuminate\Support\Str::limit($inquiry->message ?? '', 100),
                        null
                    ));
                }
            } catch (\Throwable $e) {
                // ignore - matches the rest of this codebase's booted() convention
            }
        });
    }
}
