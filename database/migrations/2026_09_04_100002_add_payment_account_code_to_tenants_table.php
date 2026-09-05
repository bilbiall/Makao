<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // What we ask the tenant to type as the M-Pesa Paybill Account Number -
            // matched against an inbound C2B payment's BillRefNumber. Defaults to the
            // tenant's unit identifier at creation (something they already know),
            // editable by the landlord. Unique per landlord, enforced in the form, not
            // here, since uniqueness is meaningful only within one landlord's tenants.
            $table->string('payment_account_code')->nullable()->after('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('payment_account_code');
        });
    }
};
