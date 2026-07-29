<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * app_settings was a true global singleton (SMS/email templates, M-Pesa/Pesapal
     * credentials, app name) shared by every landlord - meaning one landlord editing
     * their M-Pesa till number changed it for every other landlord too. Each landlord
     * now gets their own settings row; landlord_id nullable = null represents a
     * system-level fallback row (used for superadmin's own notifications, since a
     * superadmin belongs to no landlord).
     */
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->foreignId('landlord_id')->nullable()->unique()->after('id')->constrained('landlords')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('landlord_id');
        });
    }
};
