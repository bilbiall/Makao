<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('join_code', 12)->nullable()->unique()->after('user_id');
            $table->timestamp('join_code_expires_at')->nullable()->after('join_code');
        });

        // Email becomes optional now that admitting a tenant no longer creates a
        // User account up front - the tenant self-registers separately and later
        // connects to this row via the join code, so there's nothing that
        // requires an email address at admit-time.
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['join_code', 'join_code_expires_at']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
