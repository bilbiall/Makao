<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viewing_requests', function (Blueprint $table) {
            $table->timestamp('contacted_at')->nullable()->after('admin_notes');
            $table->foreignId('contacted_by')->nullable()->after('contacted_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('viewing_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contacted_by');
            $table->dropColumn('contacted_at');
        });
    }
};
