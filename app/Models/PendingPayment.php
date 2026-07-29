<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToLandlord;

class PendingPayment extends Model
{
    use HasFactory, BelongsToLandlord;

    protected $fillable = [
        'tenant_id', 'invoice_id', 'amount', 'reference', 'status', 'meta', 'expires_at', 'landlord_id',
    ];

    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($pending) {
            if (!$pending->landlord_id && $pending->tenant_id) {
                $pending->landlord_id = \App\Models\Tenant::withoutGlobalScopes()->find($pending->tenant_id)?->landlord_id;
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Invoice::class);
    }
}
