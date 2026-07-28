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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

            $table->decimal('water', 8, 2)->default(0);
            $table->decimal('electricity', 8, 2)->default(0);
            $table->decimal('internet', 8, 2)->default(0);
            $table->decimal('trash', 8, 2)->default(0);
            // virtualAs(...) works on MySQL 5.7+ and avoids having to store the total manually.
            $table->decimal('total', 10, 2)->virtualAs('water + electricity + internet + trash');

            $table->date('bill_month'); // e.g. 2025-04-01 means April bills
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
