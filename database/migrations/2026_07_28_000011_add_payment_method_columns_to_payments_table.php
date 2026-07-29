<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These three columns have been in Payment::$fillable and referenced throughout the
     * app (MpesaService, ViewTenant, ViewNoticeToVacate infolists) since those features
     * were built, but no migration ever actually created them - every M-Pesa payment
     * callback has been silently failing to record a Payment row as a result (the
     * failure is caught and logged, not surfaced, so it was easy to miss).
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('payment_date'); // mpesa | pesapal | cash | bank | recorded
            $table->string('transaction_id')->nullable()->after('payment_method');
            $table->string('status')->nullable()->default('completed')->after('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'transaction_id', 'status']);
        });
    }
};
