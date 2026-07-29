<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToLandlord;

class MpesaTransaction extends Model
{
    use BelongsToLandlord;

    protected $table = 'mpesa_transactions';
    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'house_id',
        'amount',
        'phone_number',
        'reference',
        'checkout_request_id',
        'status',
        'response_code',
        'response_message',
        'receipt_number',
        'result_code',
        'result_desc',
        'meta',
        'landlord_id',
    ];

    protected static function booted(): void
    {
        static::creating(function ($transaction) {
            if (!$transaction->landlord_id && $transaction->tenant_id) {
                $transaction->landlord_id = \App\Models\Tenant::withoutGlobalScopes()->find($transaction->tenant_id)?->landlord_id;
            }
        });
    }

    protected $casts = [
        'meta' => 'array',
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class, 'payment_reference', 'reference');
    }
}
