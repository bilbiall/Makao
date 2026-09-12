<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A time-boxed discount an agent/landlord can toggle per price tier (see
     * AdminApp\Promotions) - e.g. "15% off this weekend". Deliberately separate
     * from just editing `price` directly so the original price stays visible
     * (struck through) and the discount expires on its own without anyone
     * needing to remember to change the price back.
     */
    public function up(): void
    {
        Schema::table('house_price_packages', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->nullable()->after('price');
            $table->string('discount_label')->nullable()->after('discount_percent');
            $table->timestamp('discount_starts_at')->nullable()->after('discount_label');
            $table->timestamp('discount_ends_at')->nullable()->after('discount_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('house_price_packages', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discount_label', 'discount_starts_at', 'discount_ends_at']);
        });
    }
};
