<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'landlord_id',
        'package_id',
        'status',
        'starts_at',
        'trial_ends_at',
        'expires_at',
        'payment_reference',
        'payment_notes',
        'amount_paid',
        'last_payment_at',
        'recorded_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isInGoodStanding(): bool
    {
        return in_array($this->status, ['trialing', 'active'])
            && (!$this->expires_at || $this->expires_at->isFuture());
    }
}
