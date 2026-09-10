<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single charge line on a monthly Bill - e.g. {bill_type: Trash, amount: 200}.
 * Replaces the old fixed water/electricity/internet/trash columns; see Bill::items()
 * and Bill::getTotalAttribute().
 */
class BillItem extends Model
{
    protected $fillable = [
        'bill_id',
        'bill_type_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function billType(): BelongsTo
    {
        return $this->belongsTo(BillType::class);
    }
}
