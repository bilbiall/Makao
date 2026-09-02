<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('role'); // 'manager' | 'caretaker'
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('landlord_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'location_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_assignments');
    }
};
