<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Set only for a "notify me when this specific house is available
            // again" alert (created from the unavailable-listing page). Null
            // for a general criteria alert created from the Alerts page.
            $table->foreignId('house_id')->nullable()->constrained()->cascadeOnDelete();

            // General-criteria fields - all nullable/empty means "any". Arrays
            // so one alert can match several house types and/or several areas
            // at once ("bedsitter or 1 bedroom, in Kilimani or Westlands").
            $table->json('house_types')->nullable();
            $table->json('areas')->nullable();
            $table->unsignedInteger('max_rent')->nullable();
            $table->string('listing_mode')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_alerts');
    }
};
