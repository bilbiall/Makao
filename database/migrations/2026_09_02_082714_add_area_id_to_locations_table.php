<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nullable and additive - `geo_id` (a free-typed string) stays the column every
 * existing query filters/groups on; `area_id` just links a Location to the
 * canonical Area record it was picked from, so the picker UI has something
 * structured to bind to. Location::booted() keeps geo_id in sync from the
 * chosen Area's name, so nothing reading geo_id elsewhere needs to change. A
 * Location whose area isn't in our seeded list yet (most of Kenya, for now)
 * simply has area_id null and geo_id set directly, exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('geo_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
        });
    }
};
