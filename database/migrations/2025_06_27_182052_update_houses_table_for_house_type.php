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
        //
        Schema::table('houses', function (Blueprint $table) {
            // Remove the old column
            $table->dropColumn('number_of_rooms');

            // Rename the column
            $table->renameColumn('num_of_bedrooms', 'house_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('houses', function (Blueprint $table) {
            // Revert the changes if needed
            $table->integer('number_of_rooms')->default(1);
            $table->renameColumn('house_type', 'num_of_bedrooms');
        });
    }
};
