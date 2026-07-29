<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deleted_tenants', function (Blueprint $table) {
            // Nullable: this is an archival snapshot, and the owning landlord could in
            // theory have been removed by the time an archived record is inspected.
            $table->foreignId('landlord_id')->nullable()->after('id')->constrained('landlords')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deleted_tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('landlord_id');
        });
    }
};
