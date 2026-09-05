<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToLandlord;

/**
 * One row per inbound Safaricom C2B confirmation, matched or not - this is the
 * source of truth for the C2B Payments dashboard. match_reason is the plain-English
 * explanation of what MpesaC2bMatchService tried and why it landed where it did.
 */
class MpesaC2bTransaction extends Model
{
    use BelongsToLandlord;

    protected $table = 'mpesa_c2b_transactions';

    protected $fillable = [
        'mpesa_channel_id',
        'landlord_id',
        'location_id',
        'tenant_id',
        'invoice_id',
        'house_id',
        'payment_id',
        'trans_id',
        'trans_time',
        'trans_amount',
        'business_shortcode',
        'bill_ref_number',
        'msisdn',
        'payer_name',
        'match_status',
        'match_reason',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'trans_time' => 'datetime',
            'trans_amount' => 'decimal:2',
            'raw_payload' => 'array',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(MpesaChannel::class, 'mpesa_channel_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
