<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Named "app_settings" (not "settings") to avoid colliding with the
        // tomatophp/filament-settings-hub package's own "settings" table
        // (a transitive dependency of tomatophp/filament-alerts, which itself
        // reads/writes that table via its own migrations) - two unrelated
        // "settings" tables with different schemas can't share one name.
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
