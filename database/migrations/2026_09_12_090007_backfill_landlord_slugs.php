<?php

use App\Models\Landlord;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Landlord::withTrashed()->whereNull('slug')->chunkById(100, function ($landlords) {
            foreach ($landlords as $landlord) {
                $landlord->slug = Landlord::generateUniqueSlug($landlord);
                $landlord->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        // Slugs are additive - nothing to revert here (dropping the column
        // itself is handled by the migration that created it).
    }
};
