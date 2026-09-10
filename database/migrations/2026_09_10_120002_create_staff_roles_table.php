<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('landlords')->cascadeOnDelete();
            $table->string('name');
            // Determines whether this role's StaffAssignment rows use location_id
            // (like the built-in manager/caretaker) or house_id (like agent) -
            // StaffScope keys its row-narrowing off this, not the role name.
            $table->string('scope_type'); // 'location' | 'house'
            // Fixed catalog of permission slugs (see App\Support\StaffPermissions),
            // not a landlord-editable list, so a plain JSON column is enough -
            // no separate permissions-catalog table needed. MySQL/MariaDB refuse a
            // DEFAULT on JSON/BLOB/TEXT columns entirely (error 1101) - the app
            // always supplies this explicitly on create (see StaffRole's model
            // attribute default below), so no DB-level default is needed anyway.
            $table->json('permissions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_roles');
    }
};
