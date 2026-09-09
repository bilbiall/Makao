<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nullable: an unattributed/system action (no user_id) has no landlord to
        // scope to either, and stays visible only to superadmin (see ActivityLog's
        // BelongsToLandlord scope - no filter is applied at all for superadmin,
        // and `where landlord_id = X` never matches a NULL row for anyone else).
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('landlord_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        // Every activity_logs row until now was written before this column existed -
        // without this backfill, every one of them would be invisible to every
        // landlord (not just newly-hidden from other landlords) until a fresh log
        // entry is written, since a NULL landlord_id never matches a scoped query.
        DB::table('activity_logs')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $landlordId = DB::table('users')->where('id', $row->user_id)->value('landlord_id');

                    if ($landlordId) {
                        DB::table('activity_logs')->where('id', $row->id)->update(['landlord_id' => $landlordId]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('landlord_id');
        });
    }
};
