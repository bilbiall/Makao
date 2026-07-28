<?php

namespace App\Observers;

use App\Models\Tenant;
use App\Models\DeletedTenant;
use Carbon\Carbon;

class TenantObserver
{
    /**
     * Handle the Tenant "deleting" event.
     */
    public function deleting(Tenant $tenant): void
    {
        // Store deleted tenant data before deletion
        $invoices = $tenant->invoices()->with('payments')->get();
        $payments = $tenant->payments()->get();
        $issues = $tenant->issues()->get();

        // Calculate totals
        $totalInvoiced = $invoices->sum('amount');
        $totalPaid = $payments->sum('amount_paid');
        $outstandingBalance = max(0, $totalInvoiced - $totalPaid);
        $overpayment = max(0, $totalPaid - $totalInvoiced);

        // Invoice status counts
        $paidCount = $invoices->where('status', 'paid')->count();
        $unpaidCount = $invoices->where('status', 'unpaid')->count();
        $partialCount = $invoices->where('status', 'partial')->count();

        // Prepare invoice data for archival
        $invoicesData = $invoices->map(function ($invoice) {
            return [
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'status' => $invoice->status,
                'balance' => $invoice->balance,
                'invoice_date' => $invoice->invoice_date,
                'due_date' => $invoice->due_date,
                'payments_received' => $invoice->payments->sum('amount_paid'),
            ];
        })->toArray();

        // Prepare payment data for archival
        $paymentsData = $payments->map(function ($payment) {
            return [
                'amount_paid' => $payment->amount_paid,
                'payment_date' => $payment->payment_date,
                'reference' => $payment->reference,
                'invoice_number' => optional($payment->invoice)->invoice_number,
            ];
        })->toArray();

        // Prepare issues data for archival
        $issuesData = $issues->map(function ($issue) {
            return [
                'title' => $issue->title,
                'description' => $issue->description,
                'status' => $issue->status,
                'created_at' => $issue->created_at,
                'updated_at' => $issue->updated_at,
            ];
        })->toArray();

        // Get location information from house
        $house = $tenant->house;
        $locationId = null;
        $locationName = null;
        
        if ($house && $house->location) {
            $locationId = $house->location_id;
            $locationName = $house->location->location_name;
        }
        
        // Log tenant deletion
        try {
            $actor = auth()->id() ?? null;
            $houseName = $house ? $house->house_name : 'Unknown';
            \App\Helpers\ActivityLogger::log('delete_tenant', $actor, "Tenant {$tenant->tenant_name} deleted from {$houseName}. Total invoiced: KES {$totalInvoiced}, Total paid: KES {$totalPaid}");
        } catch (\Throwable $e) {
            // ignore
        }

        // Create deleted tenant record
        DeletedTenant::create([
            'tenant_name' => $tenant->tenant_name,
            'phone_number' => $tenant->phone_number,
            'email' => $tenant->email,
            'id_number' => $tenant->id_number,
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'outstanding_balance' => $outstandingBalance,
            'overpayment' => $overpayment,
            'previous_house' => optional($tenant->house)->house_name,
            'previous_house_id' => $tenant->house_id,
            'location_id' => $locationId,
            'location_name' => $locationName,
            'invoices_count' => $invoices->count(),
            'paid_invoices_count' => $paidCount,
            'unpaid_invoices_count' => $unpaidCount,
            'partial_invoices_count' => $partialCount,
            'invoices_data' => $invoicesData,
            'payments_data' => $paymentsData,
            'issues_data' => $issuesData,
            'issues_count' => $issues->count(),
            'deleted_at' => now(),
            'auto_delete_at' => now()->addDays(60),
        ]);
    }
}
