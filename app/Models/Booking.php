<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToLandlord;

/**
 * A single short-stay reservation. Deliberately never becomes, and never touches,
 * a Tenant row - a guest checks in, checks out, and the unit resets, unlike a
 * long-term Tenant which occupies a House indefinitely. See BNB_MODE_DESIGN.md.
 */
class Booking extends Model
{
    use BelongsToLandlord;

    protected $fillable = [
        'house_id',
        'user_id',
        'price_package_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'check_in',
        'check_out',
        'nights',
        'package_name',
        'nightly_rate',
        'billing_unit',
        'total_amount',
        'status',
        'payment_status',
        'expires_at',
        'notes',
        'landlord_id',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'expires_at' => 'datetime',
            'nightly_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($booking) {
            if (!$booking->landlord_id && $booking->house_id) {
                $booking->landlord_id = House::withoutGlobalScopes()->find($booking->house_id)?->landlord_id;
            }
        });
    }

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pricePackage()
    {
        return $this->belongsTo(HousePricePackage::class, 'price_package_id');
    }

    public function payments()
    {
        return $this->hasMany(BookingPayment::class);
    }

    /**
     * Date-range overlap: two ranges [a_in, a_out) and [b_in, b_out) overlap iff
     * a_in < b_out AND a_out > b_in. Reused by both the public availability check
     * and the confirm-time race-condition guard, so the definition lives in one place.
     */
    public function scopeOverlapping($query, $checkIn, $checkOut)
    {
        return $query->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn);
    }

    /** Bookings that currently occupy the calendar - confirmed stays, or a still-live pending hold. */
    public function scopeBlocking($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('status', ['confirmed', 'checked_in'])
                ->orWhere(function ($q2) {
                    $q2->where('status', 'pending')->where('expires_at', '>', now());
                });
        });
    }
}
