<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToLandlord;

class DeletedTenant extends Model
{
    use BelongsToLandlord;

    protected $fillable = [
        'landlord_id',
        'tenant_name',
        'phone_number',
        'email',
        'id_number',
        'total_invoiced',
        'total_paid',
        'outstanding_balance',
        'overpayment',
        'previous_house',
        'previous_house_id',
        'location_id',
        'location_name',
        'invoices_count',
        'paid_invoices_count',
        'unpaid_invoices_count',
        'partial_invoices_count',
        'invoices_data',
        'payments_data',
        'issues_data',
        'issues_count',
        'deleted_at',
        'auto_delete_at',
    ];

    protected $casts = [
        'invoices_data' => 'array',
        'payments_data' => 'array',
        'issues_data' => 'array',
        'deleted_at' => 'datetime',
        'auto_delete_at' => 'datetime',
        'total_invoiced' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'overpayment' => 'decimal:2',
    ];

    /**
     * Get the location this tenant belonged to
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the status of invoices
     */
    public function getOutstandingInvoicesAttribute()
    {
        return $this->unpaid_invoices_count + $this->partial_invoices_count;
    }

    /**
     * Check if tenant has overpayment
     */
    public function hasOverpayment()
    {
        return $this->overpayment > 0;
    }

    /**
     * Get days until auto-deletion
     */
    public function getDaysUntilDeletion()
    {
        return $this->auto_delete_at->diffInDays(now());
    }
}
