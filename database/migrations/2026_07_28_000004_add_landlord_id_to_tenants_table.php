<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Denormalized copy of houses.landlord_id, auto-stamped in Tenant::creating().
            $table->foreignId('landlord_id')->after('id')->constrained('landlords')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('landlord_id');
        });
    }
};
