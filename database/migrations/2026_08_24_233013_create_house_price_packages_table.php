<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_price_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g. "Nightly", "Weekly", "Monthly"
            $table->decimal('price', 10, 2);
            $table->string('billing_unit')->default('night'); // night | week | month
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_price_packages');
    }
};
