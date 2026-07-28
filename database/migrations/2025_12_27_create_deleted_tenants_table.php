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
        Schema::create('deleted_tenants', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_name');
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('id_number')->nullable();
            $table->decimal('total_invoiced', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('outstanding_balance', 15, 2)->default(0);
            $table->decimal('overpayment', 15, 2)->default(0);
            $table->string('previous_house')->nullable();
            $table->unsignedBigInteger('previous_house_id')->nullable();
            $table->integer('invoices_count')->default(0);
            $table->integer('paid_invoices_count')->default(0);
            $table->integer('unpaid_invoices_count')->default(0);
            $table->integer('partial_invoices_count')->default(0);
            $table->json('invoices_data')->nullable();
            $table->json('payments_data')->nullable();
            $table->dateTime('deleted_at');
            $table->dateTime('auto_delete_at');
            $table->timestamps();
            
            $table->index('deleted_at');
            $table->index('auto_delete_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deleted_tenants');
    }
};
