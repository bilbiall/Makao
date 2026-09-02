<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            // 'long_term' (default, today's behaviour) or 'short_term' (BnB). A
            // short_term house is never touched by Tenant::booted()'s occupancy
            // flips - it has no Tenant at all, only Phase 2's future bookings.
            $table->string('listing_mode')->default('long_term')->after('house_status');
        });
    }

    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->dropColumn('listing_mode');
        });
    }
};
