<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('phone_number');
            $table->text('bio')->nullable()->after('avatar_path');

            // Only ever generated for a role with a public profile page (agent, for
            // now) - see User::ensureSlug(). Nullable/unique like House/Landlord's
            // own slug columns.
            $table->string('slug')->nullable()->unique()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['avatar_path', 'bio', 'slug']);
        });
    }
};
