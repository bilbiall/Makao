<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToLandlord;

/**
 * A guest's rating/review of a short-stay House - always tied to the specific
 * Booking that proves they actually stayed there (see Booking::canBeReviewed()
 * and the booking's own signed confirmation page, which is where this gets
 * submitted from). Never created any other way, so "only from users who have
 * been a guest there" is enforced by the unique booking_id constraint, not
 * just app-level checks.
 */
class HouseReview extends Model
{
    use BelongsToLandlord;

    protected $fillable = [
        'house_id',
        'booking_id',
        'user_id',
        'rating',
        'comment',
        'landlord_id',
    ];

    protected static function booted()
    {
        static::creating(function (HouseReview $review) {
            if (!$review->landlord_id && $review->house_id) {
                $review->landlord_id = House::withoutGlobalScopes()->find($review->house_id)?->landlord_id;
            }
        });
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Whoever's name should show on the review - the account name if linked, else the booking's own guest name. */
    public function reviewerName(): string
    {
        return $this->user?->name ?? $this->booking?->guest_name ?? 'Guest';
    }
}
