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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained()->cascadeOnDelete();
            // Operating costs, not billed to a tenant - a landlord/portfolio-wide
            // running cost per month, same categories DAKSrent tracked (the system
            // this replaces for at least one client), so an import from it lines up
            // directly. Day/1 of the month it applies to, for consistent sorting.
            $table->date('expense_month');
            $table->decimal('electricity', 10, 2)->default(0);
            $table->decimal('water', 10, 2)->default(0);
            $table->decimal('internet', 10, 2)->default(0);
            $table->decimal('maintenance', 10, 2)->default(0);
            $table->decimal('other', 10, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['landlord_id', 'expense_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
