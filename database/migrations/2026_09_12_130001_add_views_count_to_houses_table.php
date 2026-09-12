<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A plain counter, not a per-visit log - deliberately simple (no bot
     * filtering, no per-visitor dedup) since this only needs to answer "is
     * anyone actually looking at this listing", the top of the agent's
     * views -> inquiries -> bookings funnel (see AdminApp\Analytics).
     */
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->unsignedInteger('views_count')->default(0)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};
