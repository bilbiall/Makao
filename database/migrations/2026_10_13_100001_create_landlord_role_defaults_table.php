<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-landlord override of a built-in role's (manager/caretaker/agent)
        // permission checkboxes. No row = the platform default from
        // StaffPermissions::defaultFor() applies. Only affects that landlord's staff.
        Schema::create('landlord_role_defaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('landlords')->cascadeOnDelete();
            $table->string('role'); // manager | caretaker | agent
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->unique(['landlord_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landlord_role_defaults');
    }
};
