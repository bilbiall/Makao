<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A single charge line on a monthly Bill (replaces the old fixed water/electricity/
     * internet/trash columns). bill_type_id is restrictOnDelete - a type with historical
     * items can't be hard-deleted, only deactivated (BillType.is_active), so past bills
     * always keep their real breakdown.
     */
    public function up(): void
    {
        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->foreignId('bill_type_id')->constrained('bill_types')->restrictOnDelete();
            $table->decimal('amount', 8, 2);
            $table->timestamps();

            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};
