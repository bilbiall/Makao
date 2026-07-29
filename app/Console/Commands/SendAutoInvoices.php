<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Landlord;
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
    protected $description = 'Automatically send invoices on each landlord\'s configured date';

    /**
     * Execute the console command.
     *
     * Auto-invoice enabled/date is per-landlord settings, not a platform-wide toggle -
     * this runs once per landlord, using that landlord's own configuration and only
     * touching that landlord's own tenants.
     */
    public function handle()
    {
        $totalInvoiced = 0;
        $totalFailed = 0;

        foreach (Landlord::all() as $landlord) {
            [$invoiced, $failed] = $this->processLandlord($landlord);
            $totalInvoiced += $invoiced;
            $totalFailed += $failed;
        }

        $this->info("Auto-invoicing completed. {$totalInvoiced} invoices sent across all landlords.");
        if ($totalFailed > 0) {
            $this->warn("{$totalFailed} invoices failed to send.");
        }

        return 0;
    }

    protected function processLandlord(Landlord $landlord): array
    {
        $settings = Setting::forLandlord($landlord->id);
        $payload = $settings->payload ?? [];

        if (!($payload['auto_invoice_enabled'] ?? false)) {
            return [0, 0];
        }

        $invoiceDay = null;
        if (!empty($payload['auto_invoice_date'])) {
            $invoiceDay = Carbon::parse($payload['auto_invoice_date'])->day;
        }

        if (!$invoiceDay || now()->day !== $invoiceDay) {
            return [0, 0];
        }

        $this->info("Starting auto-invoice process for {$landlord->name} (day {$invoiceDay})...");

        $tenants = Tenant::where('landlord_id', $landlord->id)->get();
        $invoiceCount = 0;
        $failedCount = 0;
        $today = now();
        $month = $today->format('m');
        $year = $today->format('Y');

        foreach ($tenants as $tenant) {
            try {
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

                // invoice_number is left unset - Invoice::creating() generates it using an
                // unscoped max(id), which stays unique-safe across landlords.
                $invoice = Invoice::create([
                    'tenant_id' => $tenant->id,
                    'invoice_date' => $today,
                    'due_date' => $today->copy()->addDays(10),
                    'amount' => $total,
                    'comment' => 'Auto-generated invoice',
                    'status' => 'unpaid',
                ]);

                // Wrapped separately from invoice creation above: an SMS failure (e.g.
                // gateway not configured) must not cause an already-created, already-
                // committed invoice to be misreported as "failed to create".
                try {
                    SmsHelper::sendSms(
                        $tenant->phone_number,
                        "Hello {$tenant->tenant_name}, your invoice ({$invoice->invoice_number}) of KES {$total} is due by {$invoice->due_date->format('M d')}.",
                        $landlord->id
                    );
                } catch (\Throwable $e) {
                    // ignore SMS failures - the invoice itself was created successfully
                }

                $invoiceCount++;
            } catch (\Throwable $e) {
                $failedCount++;
                $this->error("Failed to create invoice for tenant {$tenant->tenant_name}: {$e->getMessage()}");
            }
        }

        try {
            $details = "Auto-invoicing completed for {$landlord->name}. {$invoiceCount} invoices sent.";
            if ($failedCount > 0) {
                $details .= " ({$failedCount} failed)";
            }
            ActivityLogger::log('auto_invoice', null, $details);
        } catch (\Throwable $e) {
            $this->warn("Could not log activity: {$e->getMessage()}");
        }

        return [$invoiceCount, $failedCount];
    }
}
