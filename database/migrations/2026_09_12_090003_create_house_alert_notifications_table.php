<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A small dedup log - without it, a house cycling Vacant -> Occupied ->
     * Vacant again would re-notify the same still-open alert every single
     * time for the exact same listing. One row per (alert, house) pair that's
     * already been sent; a genuinely different matching house is unaffected.
     */
    public function up(): void
    {
        Schema::create('house_alert_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_alert_id')->constrained('house_alerts')->cascadeOnDelete();
            $table->foreignId('house_id')->constrained()->cascadeOnDelete();
            $table->timestamp('notified_at');

            $table->unique(['house_alert_id', 'house_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_alert_notifications');
    }
};
