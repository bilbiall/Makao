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
        Schema::table('messages', function (Blueprint $table) {
            // A message can reference one record from the tenant's own account -
            // an invoice, a payment, or a notice to vacate - e.g. "your rent is due"
            // linked straight to that invoice. Short morph-mapped type strings
            // ('invoice'/'payment'/'notice'), not FQCNs - see AppServiceProvider::boot().
            $table->string('attachment_type')->nullable()->after('issue_id');
            $table->unsignedBigInteger('attachment_id')->nullable()->after('attachment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_type', 'attachment_id']);
        });
    }
};
