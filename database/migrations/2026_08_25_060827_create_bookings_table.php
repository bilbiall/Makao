<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_id')->constrained()->onDelete('cascade');
            // Nullable - a guest checkout never gets a User account linked. A registered
            // 'user' account's booking sets this for autofill/history, per the agreed
            // "guests stay users, never become tenants" design.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('price_package_id')->nullable()->constrained('house_price_packages')->nullOnDelete();

            $table->string('guest_name');
            $table->string('guest_phone');
            $table->string('guest_email')->nullable();

            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('nights');

            // Snapshotted from house_price_packages at booking time - never recompute
            // retroactively if the landlord changes prices later.
            $table->string('package_name')->nullable();
            $table->decimal('nightly_rate', 10, 2);
            $table->string('billing_unit')->default('night');
            $table->decimal('total_amount', 10, 2);

            $table->string('status')->default('pending'); // pending|confirmed|checked_in|checked_out|cancelled
            $table->string('payment_status')->default('unpaid'); // unpaid|deposit_paid|paid|refunded
            $table->timestamp('expires_at')->nullable(); // only set while status=pending

            $table->text('notes')->nullable();
            $table->foreignId('landlord_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
