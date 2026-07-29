<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Stamped from the sender's landlord_id. Broadcast messages have no house_id,
            // so scoping via house is unreliable here - a direct column is needed instead.
            $table->foreignId('landlord_id')->after('id')->constrained('landlords')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('landlord_id');
        });
    }
};
