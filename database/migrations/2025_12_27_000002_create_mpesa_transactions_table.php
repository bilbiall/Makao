<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('house_id')->constrained('houses')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('phone_number');
            $table->string('reference')->unique();
            $table->string('checkout_request_id')->nullable()->unique();
            $table->enum('status', ['pending', 'completed', 'failed', 'timeout'])->default('pending');
            $table->string('response_code')->nullable();
            $table->text('response_message')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('result_code')->nullable();
            $table->text('result_desc')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['invoice_id']);
            $table->index(['reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};
