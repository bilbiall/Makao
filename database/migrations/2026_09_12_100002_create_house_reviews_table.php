<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A review is tied to a specific completed Booking - not just "a user and a
     * house" - because the booking IS the proof this reviewer actually stayed
     * there (see HouseReview::eligibleFor()). One review per booking, enforced
     * by the unique constraint below, not just app-level validation.
     */
    public function up(): void
    {
        Schema::create('house_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();

            // Nullable - most guests book without an account (see Booking.user_id
            // itself being nullable). The booking's guest_name is what's shown
            // either way; this just links a review back to an account when one
            // happens to exist, for a future "your reviews" list.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->foreignId('landlord_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_reviews');
    }
};
