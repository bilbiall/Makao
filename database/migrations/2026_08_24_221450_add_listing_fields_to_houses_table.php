<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->text('description')->nullable()->after('house_type');
            $table->string('size_label')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->dropColumn(['description', 'size_label']);
        });
    }
};
