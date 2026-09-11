<?php

use App\Models\House;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        House::withoutGlobalScopes()->whereNull('slug')->with('location.area')->chunkById(100, function ($houses) {
            foreach ($houses as $house) {
                $house->slug = House::generateUniqueSlug($house);
                $house->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        // Slugs are additive - nothing to revert here (dropping the column
        // itself is handled by the migration that created it).
    }
};
