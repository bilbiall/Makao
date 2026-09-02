<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Owner-controlled visibility switch, independent of house_status/listing_mode -
     * default true so every unit that's already publicly visible today (Vacant +
     * photos, for long_term; has photos, for short_term) stays visible after this
     * migration runs, and an owner opts specific units *out* going forward.
     */
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->after('listing_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }
};
