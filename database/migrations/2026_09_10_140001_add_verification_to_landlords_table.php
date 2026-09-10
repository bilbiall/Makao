<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landlords', function (Blueprint $table) {
            // 'unverified' (default) | 'pending' | 'verified' | 'rejected'
            $table->string('verification_status')->default('unverified')->after('status');
            $table->timestamp('verification_requested_at')->nullable()->after('verification_status');
            $table->timestamp('verified_at')->nullable()->after('verification_requested_at');
            $table->foreignId('verified_by')->nullable()->after('verified_at')
                ->constrained('users')->nullOnDelete();
            $table->text('verification_notes')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('landlords', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['verification_status', 'verification_requested_at', 'verified_at', 'verification_notes']);
        });
    }
};
