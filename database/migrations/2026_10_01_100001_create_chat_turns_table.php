<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per visitor message + the assistant's answer, for the
        // superadmin "Chat insights" dashboard. user_text is stored already
        // masked (phone numbers/emails/long digit runs) - see ChatMasker - and
        // rows are pruned after 90 days (chat:prune-turns).
        Schema::create('chat_turns', function (Blueprint $table) {
            $table->id();
            $table->uuid('chat_id')->index();
            $table->text('user_text');
            $table->text('reply_text')->nullable();
            $table->string('language', 8)->nullable();
            $table->string('branch', 32)->nullable()->index();
            $table->json('filters')->nullable();
            $table->boolean('used_fallback')->default(false);
            $table->boolean('llm_failed')->default(false);
            $table->string('offer_type', 32)->nullable();
            $table->string('offer_result', 16)->nullable();
            $table->boolean('handoff_shown')->default(false);
            $table->string('status', 16)->nullable()->index();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_turns');
    }
};
