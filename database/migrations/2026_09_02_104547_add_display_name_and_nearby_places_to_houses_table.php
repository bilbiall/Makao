<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            // The internal/management name (house_name, e.g. "A1") often isn't what a
            // renter should see - this is the public listing title (e.g. "Spacious
            // Bedsitter"), optional and falls back to house_name when blank (see
            // House::publicName()).
            $table->string('display_name')->nullable()->after('house_name');

            // Keyed by House::NEARBY_CATEGORIES slug (e.g. 'school', 'mall') => minutes
            // away (int) - only categories the owner actually filled in are shown
            // publicly. A JSON column, not a separate table, for the same reason
            // `amenities` already is: a small, fixed set of per-house values with no
            // querying/reporting need of its own.
            $table->json('nearby_places')->nullable()->after('amenities');
        });
    }

    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'nearby_places']);
        });
    }
};
