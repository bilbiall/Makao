<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;

class FixInvoiceBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:fix-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix invoice balances and statuses based on payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing invoice balances...');
        
        $invoices = Invoice::with('payments')->get();
        $fixed = 0;
        
        foreach ($invoices as $invoice) {
            $totalPaid = $invoice->payments->sum('amount_paid');
            $correctBalance = $invoice->amount - $totalPaid;
            
            // Determine correct status
            $correctStatus = 'unpaid';
            if ($correctBalance <= 0) {
                $correctStatus = 'paid';
            } elseif ($correctBalance < $invoice->amount) {
                $correctStatus = 'partial';
            }
            
            // Update if different
            if ($invoice->balance != $correctBalance || $invoice->status != $correctStatus) {
                $invoice->balance = $correctBalance;
                $invoice->status = $correctStatus;
                $invoice->save();
                $fixed++;
                
                $this->line("Fixed Invoice #{$invoice->invoice_number}: Balance = {$correctBalance}, Status = {$correctStatus}");
            }
        }
        
        $this->info("Fixed {$fixed} invoices out of {$invoices->count()} total.");
        
        return 0;
    }
}
