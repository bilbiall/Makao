<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('method')->default('mpesa');
            $table->string('status')->default('pending'); // pending|completed|failed
            $table->string('checkout_request_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('landlord_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
