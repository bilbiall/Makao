<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaTransaction extends Model
{
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
    ];

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
