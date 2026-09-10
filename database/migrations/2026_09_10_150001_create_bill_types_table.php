<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A landlord's own catalog of charge types (Water, Trash, ...) - replaces the old
     * hardcoded water/electricity/internet/trash columns on `bills`. location_id null
     * means "all my properties", set means "only that property" - same nullable-scope
     * idea as MpesaChannel.location_id.
     */
    public function up(): void
    {
        Schema::create('bill_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('landlords')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('name');
            $table->decimal('default_amount', 8, 2)->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['landlord_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_types');
    }
};
