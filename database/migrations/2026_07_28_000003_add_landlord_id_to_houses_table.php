<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            // Denormalized copy of locations.landlord_id, auto-stamped in House::creating() -
            // keeps every leaf-table scope query a single indexed column, not a join.
            $table->foreignId('landlord_id')->after('id')->constrained('landlords')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('landlord_id');
        });
    }
};
