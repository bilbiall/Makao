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
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->string('house_name');
            $table->integer('number_of_rooms')->default(1);
            $table->double('rent_amount');

            // New relationship-based location field
            $table->foreignId('location_id')->constrained()->onDelete('cascade');


            $table->string('num_of_bedrooms', 50);
            $table->string('house_status', 50)->default('Vacant');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};
