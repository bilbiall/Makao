<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Tenant;
use App\Models\Concerns\BelongsToLandlord;

class Bill extends Model
{
    use BelongsToLandlord;

    //fillables
    protected $fillable = [
        'tenant_id',
        'bill_month',
        'note',
        'landlord_id',
    ];

    //relationsgip with tenant model
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    /**
     * Sum of this bill's line items - replaces the old water+electricity+internet+
     * trash arithmetic (and the DB's now-unused virtual `total` column) everywhere a
     * bill's total is needed, so there's a single place that math lives.
     */
    protected function total(): Attribute
    {
        return Attribute::get(fn () => $this->items->sum(fn (BillItem $item) => (float) $item->amount));
    }

    protected static function booted()
    {
        static::creating(function ($bill) {
            if (!$bill->landlord_id && $bill->tenant_id) {
                $bill->landlord_id = \App\Models\Tenant::withoutGlobalScopes()->find($bill->tenant_id)?->landlord_id;
            }
        });

        // Deleting bill_items cascade at the DB level once this Bill row is removed, so
        // the total must be captured beforehand (deleting, not deleted) to still be
        // accurate for the log entry.
        static::deleting(function ($bill) {
            try {
                $actor = auth()->id() ?? null;
                $tenant = $bill->tenant;
                $details = "Bill deleted for {$tenant->tenant_name} - Month: {$bill->bill_month}, Total: KES {$bill->total}";
                \App\Helpers\ActivityLogger::log('delete_bill', $actor, $details);
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }

    /**
     * Called explicitly by callers once this bill's line items are attached (not a
     * `created` model event - the total isn't known yet at that instant, since items
     * are created in a separate step right after Bill::create()).
     */
    public function logAndNotifyRecorded(): void
    {
        try {
            $tenant = $this->tenant;
            $actor = auth()->id() ?? null;
            $details = "Bill recorded for {$tenant->tenant_name} - Total: KES " . number_format($this->total, 2) . ", Month: {$this->bill_month}";
            \App\Helpers\ActivityLogger::log('record_bill', $actor, $details);

            // Notify this landlord's own admins about the new bill
            $admins = \App\Models\User::where('role', 'admin')->where('landlord_id', $this->landlord_id)->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\DatabaseNotification(
                    'Bill Recorded',
                    $details,
                    null
                ));
            }

            // Notify tenant user about new bill
            if ($tenantUser = $tenant->user ?? null) {
                $tenantUser->notify(new \App\Notifications\DatabaseNotification(
                    'New Bill Added',
                    "A new bill for {$this->bill_month} has been added. Total: KES " . number_format($this->total, 2),
                    null
                ));
            }
        } catch (\Throwable $e) {
            // ignore logging errors
        }
    }
}
