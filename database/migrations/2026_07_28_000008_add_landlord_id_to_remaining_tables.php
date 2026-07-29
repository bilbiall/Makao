<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rather than mixing "direct landlord_id column" and "scope via a join to a parent
     * table" strategies across different models, every landlord-owned table gets its own
     * landlord_id column (denormalized from tenants.landlord_id, auto-stamped on create).
     * This keeps the LandlordScope implementation a single, uniform `where landlord_id = ?`
     * for every model it's applied to - no per-model join logic to get wrong.
     */
    public function up(): void
    {
        $tables = ['invoices', 'payments', 'bills', 'issues', 'notice_to_vacates', 'mpesa_transactions', 'pending_payments'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('landlord_id')->after('id')->constrained('landlords')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = ['invoices', 'payments', 'bills', 'issues', 'notice_to_vacates', 'mpesa_transactions', 'pending_payments'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('landlord_id');
            });
        }
    }
};
