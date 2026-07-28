<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Bill;
use App\Models\Setting;
use App\Helpers\SmsHelper;
use App\Helpers\ActivityLogger;
use Carbon\Carbon;

class SendAutoInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-auto-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically send invoices on the configured date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get settings
        $settings = Setting::singleton();
        $payload = $settings->payload ?? [];
        
        // Check if auto-invoicing is enabled
        if (!($payload['auto_invoice_enabled'] ?? false)) {
            $this->info('Auto-invoicing is disabled.');
            return 0;
        }

        // Get the day of month for auto-invoicing
        $invoiceDay = null;
        if (!empty($payload['auto_invoice_date'])) {
            // Extract day from date string (format: YYYY-MM-DD)
            $invoiceDate = Carbon::parse($payload['auto_invoice_date']);
            $invoiceDay = $invoiceDate->day;
        }

        if (!$invoiceDay) {
            $this->warn('Auto invoice date not configured.');
            return 1;
        }

        // Check if today is the invoice day
        if (now()->day !== $invoiceDay) {
            $this->info("Today is not the invoice day (configured: {$invoiceDay})");
            return 0;
        }

        $this->info("Starting auto-invoice process for day {$invoiceDay}...");

        // Send mass invoices
        $tenants = Tenant::all();
        $invoiceCount = 0;
        $failedCount = 0;
        $today = now();
        $month = $today->format('m');
        $year = $today->format('Y');

        foreach ($tenants as $tenant) {
            try {
                // Skip if already invoiced this month
                $alreadyInvoiced = Invoice::where('tenant_id', $tenant->id)
                    ->whereMonth('invoice_date', $month)
                    ->whereYear('invoice_date', $year)
                    ->exists();

                if ($alreadyInvoiced) {
                    continue;
                }

                $house = $tenant->house;
                if (!$house) {
                    $failedCount++;
                    continue;
                }

                $rent = $house->rent_amount ?? 0;

                $bills = Bill::where('tenant_id', $tenant->id)
                    ->whereMonth('bill_month', $month)
                    ->whereYear('bill_month', $year)
                    ->first();

                $billTotal = $bills
                    ? ($bills->water + $bills->electricity + $bills->trash + $bills->internet)
                    : 0;

                $total = $rent + $billTotal;
                $nextInvoiceNumber = 'INV-' . (Invoice::max('id') + 1);

                $invoice = Invoice::create([
                    'tenant_id' => $tenant->id,
                    'invoice_number' => $nextInvoiceNumber,
                    'invoice_date' => $today,
                    'due_date' => $today->copy()->addDays(10),
                    'amount' => $total,
                    'comment' => 'Auto-generated invoice',
                    'status' => 'unpaid',
                ]);

                SmsHelper::sendSms($tenant->phone_number, "Hello {$tenant->tenant_name}, your invoice ({$invoice->invoice_number}) of KES {$total} is due by {$invoice->due_date->format('M d')}.");

                $invoiceCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $this->error("Failed to create invoice for tenant {$tenant->tenant_name}: {$e->getMessage()}");
            }
        }

        // Log the action
        try {
            $details = "Auto-invoicing completed. {$invoiceCount} invoices sent to tenants.";
            if ($failedCount > 0) {
                $details .= " ({$failedCount} failed)";
            }
            ActivityLogger::log('auto_invoice', null, $details);
        } catch (\Throwable $e) {
            $this->warn("Could not log activity: {$e->getMessage()}");
        }

        $this->info("Auto-invoicing completed. {$invoiceCount} invoices sent.");
        if ($failedCount > 0) {
            $this->warn("{$failedCount} invoices failed to send.");
        }

        return 0;
    }
}
