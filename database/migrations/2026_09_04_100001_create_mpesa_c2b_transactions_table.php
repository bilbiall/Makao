<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_c2b_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mpesa_channel_id')->constrained('mpesa_channels')->onDelete('cascade');
            // Denormalized from the channel at creation - known immediately from the
            // inbound shortcode, before any tenant matching happens.
            $table->foreignId('landlord_id')->constrained('landlords')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('set null');

            // Populated only once matched.
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->foreignId('house_id')->nullable()->constrained('houses')->onDelete('set null');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');

            $table->string('trans_id')->unique();
            $table->timestamp('trans_time')->nullable();
            $table->decimal('trans_amount', 10, 2);
            $table->string('business_shortcode');
            $table->string('bill_ref_number')->nullable();
            $table->string('msisdn')->nullable();
            $table->string('payer_name')->nullable();

            $table->string('match_status')->default('needs_review');
            $table->text('match_reason')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index(['landlord_id', 'match_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_c2b_transactions');
    }
};
