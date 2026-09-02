<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


use App\Models\Tenant;
use App\Models\Invoice;

use App\Helpers\SmsHelper;
use App\Helpers\SmsTemplateHelper;
use App\Models\Concerns\BelongsToLandlord;


class Payment extends Model
{
    //
    use HasFactory, BelongsToLandlord;

    protected $fillable = [
        'tenant_id', 'invoice_id',
        'amount_paid', 'balance',
        'payment_reference', 'payment_date',
        'note', 'payment_type',
        'payment_method', 'transaction_id', 'status',
        'landlord_id',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    //model listener for updating invoice status
    protected static function booted()
{
    static::creating(function ($payment) {
        if (!$payment->landlord_id && $payment->tenant_id) {
            $payment->landlord_id = \App\Models\Tenant::withoutGlobalScopes()->find($payment->tenant_id)?->landlord_id;
        }
    });

    static::created(function ($payment) {
        $invoice = $payment->invoice;
        $tenant = $payment->tenant;

        // 🔸 Sum all payments for this invoice
        $totalPaid = $invoice->payments()->sum('amount_paid');

        // 🔸 Calculate new balance for the invoice
        $invoiceBalance = $invoice->amount - $totalPaid;

        // 🔸 Update the invoice's balance field
        $invoice->balance = $invoiceBalance;

        // 🔸 Save the current payment's balance too
        $payment->balance = $invoiceBalance;
        $payment->save(); // Important to persist it

        // 🔸 Update the invoice status based on new balance
        if ($invoiceBalance <= 0) {
            $invoice->status = 'paid';
        } elseif ($invoiceBalance < $invoice->amount) {
            $invoice->status = 'partial';
        } else {
            $invoice->status = 'unpaid';
        }

        $invoice->save();

        // 🔸 Update tenant balance (overpaid = negative, underpaid = positive)
        // This value will affect the next invoice's expected amount
        $tenant->balance = $invoiceBalance;
        $tenant->save();

        // 🔸 Send SMS confirmation (supports {balance})
        $settings = \App\Models\Setting::forLandlord($payment->landlord_id);
        $template = $settings->payload['template_payment'] ?? 'Hi {tenant_name}, we\'ve received your payment of KES {amount_paid} for Invoice #{invoice_number}. Your remaining balance is KES {balance}. Thank you. - {app_name}';

        // Support multiple placeholder variants in stored templates (e.g. {amount}, {amount_paid})
        $placeholders = [
            '{tenant_name}',
            '{tenant}',
            '{amount_paid}',
            '{amount}',
            '{invoice_number}',
            '{invoice_no}',
            '{balance}',
            '{app_name}',
            '{property_name}',
        ];

        $replacements = [
            $tenant->tenant_name,
            $tenant->tenant_name,
            number_format($payment->amount_paid),
            number_format($payment->amount_paid),
            $invoice->invoice_number,
            $invoice->invoice_number,
            number_format($invoiceBalance),
            \App\Helpers\AppHelper::getAppName($payment->landlord_id),
            $tenant->house?->location?->location_name ?? '',
        ];

        $message = str_replace($placeholders, $replacements, $template);
        try {
            \App\Helpers\SmsHelper::sendSms($tenant->phone_number, $message, $payment->landlord_id);
        } catch (\Throwable $e) {
            // ignore SMS failures (e.g. gateway not configured)
        }
        // Also create database notification for admins and tenant user
        $admins = \App\Models\User::where('role', 'admin')->where('landlord_id', $payment->landlord_id)->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\DatabaseNotification(
                'Payment Received',
                "Payment of KES {$payment->amount_paid} received for Invoice {$invoice->invoice_number}",
                null
            ));
        }

        // Notify tenant user (if a linked user exists)
        if ($tenantUser = $tenant->user ?? null) {
            $tenantUser->notify(new \App\Notifications\DatabaseNotification(
                'Payment Confirmation',
                "We received your payment of KES {$payment->amount_paid}. Remaining balance: KES {$invoiceBalance}",
                null
            ));
        }

        // Record activity log for payment
        try {
            $actor = auth()->id() ?? null;
            $detail = "Payment of KES {$payment->amount_paid} for Invoice {$invoice->invoice_number} (Tenant: {$tenant->tenant_name})";
            \App\Helpers\ActivityLogger::log('record_payment', $actor, $detail);
        } catch (\Throwable $e) {
            // swallow
        }
    });
    /*static::created(function ($payment) {
        $invoice = $payment->invoice;
         $tenant = $payment->tenant;

        // Calculate total paid so far
        $totalPaid = $invoice->payments()->sum('amount_paid');

        $invoice->balance = $invoice->amount - $totalPaid;
        $balance = $invoice->amount - $totalPaid;
        // ✅ Save balance to the current payment
        $payment->balance = $balance;
        $payment->save(); // This is crucial

        if ($invoice->balance <= 0) {
            $invoice->status = 'paid';
        } elseif ($invoice->balance < $invoice->amount) {
            $invoice->status = 'partial';
        } else {
            $invoice->status = 'unpaid';
        }

        $invoice->save();

        // ✅ Send SMS confirmation
        $message = "Hi {$tenant->tenant_name}, we've received your payment of KES {$payment->amount_paid} for Invoice #{$invoice->invoice_number}. Thank you. - " . config('app.name');
        SmsHelper::sendSms($tenant->phone_number, $message);
    });*/
    
    static::deleted(function ($payment) {
        try {
            $actor = auth()->id() ?? null;
            $tenant = $payment->tenant;
            $invoice = $payment->invoice;
            $detail = "Payment deleted: KES {$payment->amount_paid} for Invoice {$invoice->invoice_number} (Tenant: {$tenant->tenant_name}, Ref: {$payment->payment_reference})";
            \App\Helpers\ActivityLogger::log('delete_payment', $actor, $detail);
        } catch (\Throwable $e) {
            // ignore
        }
    });
}

}
