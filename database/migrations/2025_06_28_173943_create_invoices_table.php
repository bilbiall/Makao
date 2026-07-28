<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // Foreign key to the tenant
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            // Unique invoice number like INV-1, INV-2 etc.
            $table->string('invoice_number')->unique();

            // Dates
            $table->date('invoice_date');
            $table->date('due_date');

            // Amount to be paid
            $table->double('amount');

            // Optional comment
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
