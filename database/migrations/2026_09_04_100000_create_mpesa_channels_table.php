<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mpesa_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('landlords')->onDelete('cascade');
            // Null = this landlord's default channel, used by any property that has
            // no more specific channel of its own. Set = only that property uses it.
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('cascade');
            $table->string('label')->nullable();
            // The webhook routing key - an inbound C2B confirmation only carries this,
            // so it must be globally unique and indexed for lookup.
            $table->string('business_shortcode')->unique();
            $table->text('consumer_key');
            $table->text('consumer_secret');
            $table->text('passkey')->nullable();
            $table->boolean('sandbox')->default(true);
            $table->boolean('stk_enabled')->default(true);
            $table->boolean('c2b_enabled')->default(false);
            $table->timestamp('c2b_registered_at')->nullable();
            $table->timestamps();

            $table->index(['landlord_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_channels');
    }
};
