<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Reconstructs bill_types/bill_items from the old fixed water/electricity/internet/
     * trash columns so existing landlords see no disruption - each landlord that ever
     * used a column gets that one type (landlord-wide, not recurring), and every
     * historical non-zero value becomes a bill_item. The legacy columns themselves are
     * left untouched (not dropped) - safe to re-run reconciliation manually later if
     * ever needed. A brand-new landlord (no bills yet) gets no types at all, per the
     * "only show what the PM actually added" requirement.
     */
    public function up(): void
    {
        $legacyColumns = [
            'water' => 'Water',
            'electricity' => 'Electricity',
            'internet' => 'Internet',
            'trash' => 'Trash',
        ];

        $landlordIds = DB::table('bills')->select('landlord_id')->distinct()->pluck('landlord_id');

        foreach ($landlordIds as $landlordId) {
            try {
                $this->backfillLandlord($landlordId, $legacyColumns);
            } catch (\Throwable $e) {
                // One malformed landlord's data must not abort the whole migration -
                // the legacy columns stay intact either way as a fallback.
                Log::warning("Bill type backfill failed for landlord {$landlordId}: {$e->getMessage()}");
            }
        }
    }

    private function backfillLandlord(int $landlordId, array $legacyColumns): void
    {
        $typeIds = [];

        foreach ($legacyColumns as $column => $label) {
            $hasUsage = DB::table('bills')
                ->where('landlord_id', $landlordId)
                ->where($column, '>', 0)
                ->exists();

            if (!$hasUsage) {
                continue;
            }

            $typeIds[$column] = DB::table('bill_types')->insertGetId([
                'landlord_id' => $landlordId,
                'location_id' => null,
                'name' => $label,
                'default_amount' => null,
                'is_recurring' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (empty($typeIds)) {
            return;
        }

        $bills = DB::table('bills')
            ->where('landlord_id', $landlordId)
            ->get(['id', 'water', 'electricity', 'internet', 'trash']);

        $items = [];

        foreach ($bills as $bill) {
            foreach ($typeIds as $column => $typeId) {
                $amount = (float) $bill->{$column};

                if ($amount <= 0) {
                    continue;
                }

                $items[] = [
                    'bill_id' => $bill->id,
                    'bill_type_id' => $typeId,
                    'amount' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($items, 500) as $chunk) {
            DB::table('bill_items')->insert($chunk);
        }
    }

    public function down(): void
    {
        try {
            DB::table('bill_items')->truncate();
            DB::table('bill_types')->truncate();
        } catch (\Throwable $e) {
            // Tables may already be gone if the create-table migrations' own down()
            // already dropped them (normal rollback order) - safe to ignore.
        }
    }
};
