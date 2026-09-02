<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_assignments', function (Blueprint $table) {
            $table->foreignId('house_id')->nullable()->after('location_id')->constrained()->onDelete('cascade');
            // MySQL/MariaDB (and SQLite, used by the test suite) treat NULL as distinct
            // for uniqueness, so this only actually constrains duplicate (user, house,
            // role) grants for Agent rows - it doesn't interfere with the existing
            // (user, location, role) uniqueness for Manager/Caretaker.
            $table->unique(['user_id', 'house_id', 'role'], 'staff_assignments_user_house_role_unique');
        });

        // location_id must become nullable for house-scoped 'agent' rows (which fill
        // house_id instead). ->change() (via doctrine/dbal, already a transitive
        // dependency in this project) is the portable way to do this across both
        // MySQL (dev/prod) and SQLite (the test suite's in-memory DB) - a raw MySQL-
        // specific ALTER MODIFY statement would break `php artisan test` outright.
        Schema::table('staff_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('staff_assignments', function (Blueprint $table) {
            $table->dropForeign(['house_id']);
            $table->dropColumn('house_id');
        });

        Schema::table('staff_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable(false)->change();
        });
    }
};
