<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A lightweight "ask a question" lead - deliberately NOT a Booking (no dates,
     * no calendar hold, no payment). Lets a guest who isn't ready to commit still
     * leave contact info instead of bouncing off the listing with no way to follow
     * up with them.
     */
    public function up(): void
    {
        Schema::create('house_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new'); // new|responded
            $table->foreignId('landlord_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_inquiries');
    }
};
