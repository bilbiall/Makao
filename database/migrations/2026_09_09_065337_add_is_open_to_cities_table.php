<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            // Whether landlords can currently create a property/pick this city -
            // separate from whether it merely exists in the reference list.
            // Defaults false so a bulk-seeded county town doesn't silently become
            // pickable the moment it's added; a superadmin opts each one in.
            $table->boolean('is_open')->default(false)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('is_open');
        });
    }
};
