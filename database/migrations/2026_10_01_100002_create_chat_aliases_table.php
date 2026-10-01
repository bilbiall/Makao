<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phrases the superadmin has taught the chat assistant, e.g. "Kasa" ->
        // Kasarani (area), "sawa" -> yes, "nipe mtu" -> asks for a human. Read
        // at runtime by ChatAliasService, so no deploy is needed to add one.
        Schema::create('chat_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16); // yes | no | area | house_type | human
            $table->string('phrase');
            $table->string('canonical')->nullable();
            $table->string('language', 8)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamps();
            $table->unique(['type', 'phrase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_aliases');
    }
};
