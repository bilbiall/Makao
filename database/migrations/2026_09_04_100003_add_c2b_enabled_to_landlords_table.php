<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landlords', function (Blueprint $table) {
            // Founder-gated: a landlord may only register C2B (Paybill reconciliation)
            // once this is switched on for them from the superadmin panel - misrouted
            // real cash is a higher-stakes failure mode than a rejected STK push, so
            // this isn't self-serve the way STK setup is.
            $table->boolean('c2b_enabled')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('landlords', function (Blueprint $table) {
            $table->dropColumn('c2b_enabled');
        });
    }
};
