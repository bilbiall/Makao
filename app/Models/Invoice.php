<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Helpers\SmsHelper; // if your function is inside a helper class
use App\Helpers\SmsTemplateHelper;
use Illuminate\Support\Facades\Config;
use App\Helpers\ActivityLogger;



class Invoice extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'amount',
        'status',
        'balance',
        'comment',
    ];

    // An invoice belongs to a tenant relationship
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    //payment invoice relationship
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    //to send sms
    protected static function booted()
    {
        //autopopulate inv no
        static::creating(function ($invoice) {
            // Only generate if not manually set
            if (!$invoice->invoice_number) {
                $lastId = self::max('id') + 1; // or use a UUID if needed
                $invoice->invoice_number = 'INV-' . $lastId;
            }
        });

        //push amount to the database
        static::creating(function ($invoice) {
            $tenant = $invoice->tenant;
            $rent = $tenant->house->rent_amount ?? 0;
            $bills = $tenant->bills()
                ->whereMonth('bill_month', now()->month)
                ->whereYear('bill_month', now()->year)
                ->get();

            $billTotal = $bills->sum(function ($bill) {
                return $bill->water + $bill->trash + $bill->internet;
            });

            $invoice->amount = $rent + $billTotal; // <<== Important
            
            // Initialize balance to full amount (no payments yet)
            if (!isset($invoice->balance)) {
                $invoice->balance = $invoice->amount;
            }
            
            // Set initial status to unpaid
            if (!isset($invoice->status)) {
                $invoice->status = 'unpaid';
            }
        });

        static::created(function ($invoice) {
            $tenant = $invoice->tenant;

            $message = SmsTemplateHelper::render('template_invoice', [
                'tenant_name' => $tenant->tenant_name,
                'invoice_number' => $invoice->invoice_number,
                'amount' => number_format($invoice->amount),
                'due_date' => \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y'),
            ]);

            try {
                SmsHelper::sendSms($tenant->phone_number, $message);
            } catch (\Throwable $e) {
                // ignore SMS failures (e.g. gateway not configured)
            }

            // Send database notification to admins
            $admins = \App\Models\User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\DatabaseNotification(
                    'New Invoice Created',
                    "Invoice {$invoice->invoice_number} created for {$tenant->tenant_name}",
                    null
                ));
            }

            // Send database notification to tenant user (if linked)
            if ($tenantUser = $tenant->user ?? null) {
                $tenantUser->notify(new \App\Notifications\DatabaseNotification(
                    'New Invoice',
                    "New invoice {$invoice->invoice_number} of KES " . number_format($invoice->amount, 2) . " is due by " . \Carbon\Carbon::parse($invoice->due_date)->format('d M Y'),
                    null
                ));
            }

            // Record activity log (who performed the action if available)
            try {
                $actor = auth()->id() ?? null;
                ActivityLogger::log('send_invoice', $actor, "Invoice {$invoice->invoice_number} created for {$tenant->tenant_name}");
            } catch (\Throwable $e) {
                // don't break invoice creation on logging failure
            }
        });
        
        static::updated(function ($invoice) {
            try {
                $actor = auth()->id() ?? null;
                $tenant = $invoice->tenant;
                $changes = [];
                
                if ($invoice->wasChanged('status')) {
                    $changes[] = "status from '{$invoice->getOriginal('status')}' to '{$invoice->status}'";
                }
                if ($invoice->wasChanged('amount')) {
                    $changes[] = "amount from {$invoice->getOriginal('amount')} to {$invoice->amount}";
                }
                if ($invoice->wasChanged('balance')) {
                    $changes[] = "balance from {$invoice->getOriginal('balance')} to {$invoice->balance}";
                }
                
                if (!empty($changes)) {
                    $details = "Updated invoice {$invoice->invoice_number} for {$tenant->tenant_name}: " . implode(', ', $changes);
                    \App\Helpers\ActivityLogger::log('update_invoice', $actor, $details);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        });
        
        static::deleted(function ($invoice) {
            try {
                $actor = auth()->id() ?? null;
                $tenant = $invoice->tenant;
                \App\Helpers\ActivityLogger::log('delete_invoice', $actor, "Invoice {$invoice->invoice_number} deleted (Tenant: {$tenant->tenant_name}, Amount: {$invoice->amount})");
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
}
