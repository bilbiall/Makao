<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per broadcast (not one per recipient - no other "mass send" feature in
     * this app keeps a per-recipient delivery log either, e.g. mass invoice reminders),
     * with aggregate counts for each channel. location_id null means "all my
     * properties", same nullable-scope idea as MpesaChannel/BillType.
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landlord_id')->constrained('landlords')->cascadeOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('subject');
            $table->text('message');
            $table->boolean('send_sms')->default(false);
            $table->boolean('send_email')->default(false);
            $table->unsignedInteger('recipients_total')->default(0);
            $table->unsignedInteger('sms_sent')->default(0);
            $table->unsignedInteger('sms_failed')->default(0);
            $table->unsignedInteger('email_sent')->default(0);
            $table->unsignedInteger('email_failed')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['landlord_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
