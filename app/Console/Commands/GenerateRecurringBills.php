<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\BillType;
use App\Models\Landlord;
use App\Models\Tenant;
use Illuminate\Console\Command;

class GenerateRecurringBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-recurring-bills';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-create this month\'s bill line for every recurring bill type, for every tenant it applies to';

    /**
     * Idempotent regardless of what day it runs (checks existence before creating), so
     * it's scheduled daily rather than on a per-landlord configured date like
     * SendAutoInvoices - a missed/late run just catches up next time, and running it
     * before that command each day guarantees the charge exists before that day's
     * invoice generation reads it (see Invoice::booted()/SendAutoInvoices).
     */
    public function handle(): int
    {
        $created = 0;
        $monthStart = now()->startOfMonth()->toDateString();

        foreach (Landlord::all() as $landlord) {
            $recurringTypes = BillType::where('landlord_id', $landlord->id)
                ->where('is_recurring', true)
                ->where('is_active', true)
                ->get();

            foreach ($recurringTypes as $type) {
                $tenants = Tenant::where('landlord_id', $landlord->id)
                    ->when($type->location_id, fn ($q) => $q->whereHas(
                        'house',
                        fn ($h) => $h->where('location_id', $type->location_id)
                    ))
                    ->get();

                foreach ($tenants as $tenant) {
                    try {
                        $bill = Bill::firstOrCreate(
                            ['tenant_id' => $tenant->id, 'bill_month' => $monthStart],
                            ['landlord_id' => $landlord->id]
                        );

                        $alreadyBilled = $bill->items()->where('bill_type_id', $type->id)->exists();

                        if ($alreadyBilled) {
                            continue;
                        }

                        $bill->items()->create([
                            'bill_type_id' => $type->id,
                            'amount' => $type->default_amount ?? 0,
                        ]);

                        $created++;
                    } catch (\Throwable $e) {
                        $this->error("Failed recurring bill for tenant {$tenant->id}, type {$type->id}: {$e->getMessage()}");
                    }
                }
            }
        }

        $this->info("Recurring bills: {$created} charge(s) created.");

        return 0;
    }
}
