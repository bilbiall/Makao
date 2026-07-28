<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deleted_tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->after('previous_house_id');
            $table->string('location_name')->nullable()->after('location_id');
            $table->json('issues_data')->nullable()->after('payments_data');
            $table->integer('issues_count')->default(0)->after('issues_data');
            
            $table->index('location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deleted_tenants', function (Blueprint $table) {
            $table->dropIndex(['location_id']);
            $table->dropColumn(['location_id', 'location_name', 'issues_data', 'issues_count']);
        });
    }
};
