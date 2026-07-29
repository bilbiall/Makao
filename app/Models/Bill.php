<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;
use App\Models\Concerns\BelongsToLandlord;

class Bill extends Model
{
    use BelongsToLandlord;

    //fillables
    protected $fillable = [
        'tenant_id',
        'water',
        'electricity',
        'internet',
        'trash',
        'bill_month',
        'note',
        'landlord_id',
    ];

    //relationsgip with tenant model
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    protected static function booted()
    {
        static::creating(function ($bill) {
            if (!$bill->landlord_id && $bill->tenant_id) {
                $bill->landlord_id = \App\Models\Tenant::withoutGlobalScopes()->find($bill->tenant_id)?->landlord_id;
            }
        });

        static::created(function ($bill) {
            try {
                $tenant = $bill->tenant;
                $actor = auth()->id() ?? null;
                $details = "Bill recorded for {$tenant->tenant_name} - Water: {$bill->water}, Electricity: {$bill->electricity}, Internet: {$bill->internet}, Trash: {$bill->trash}, Month: {$bill->bill_month}";
                \App\Helpers\ActivityLogger::log('record_bill', $actor, $details);

                // Notify this landlord's own admins about the new bill
                $admins = \App\Models\User::where('role', 'admin')->where('landlord_id', $bill->landlord_id)->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\DatabaseNotification(
                        'Bill Recorded',
                        $details,
                        null
                    ));
                }

                // Notify tenant user about new bill
                if ($tenantUser = $tenant->user ?? null) {
                    $total = $bill->water + $bill->electricity + $bill->internet + $bill->trash;
                    $tenantUser->notify(new \App\Notifications\DatabaseNotification(
                        'New Bill Added',
                        "A new bill for {$bill->bill_month} has been added. Total: KES " . number_format($total, 2),
                        null
                    ));
                }
            } catch (\Throwable $e) {
                // ignore logging errors
            }
        });
        
        static::deleted(function ($bill) {
            try {
                $actor = auth()->id() ?? null;
                $tenant = $bill->tenant;
                $total = $bill->water + $bill->electricity + $bill->internet + $bill->trash;
                $details = "Bill deleted for {$tenant->tenant_name} - Month: {$bill->bill_month}, Total: KES {$total}";
                \App\Helpers\ActivityLogger::log('delete_bill', $actor, $details);
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
}
