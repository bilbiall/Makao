<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `rent_amount` was left NOT NULL from the table's original creation, but short_term
 * (BnB) units are priced via house_price_packages instead and every add-unit flow
 * (AdminApp\Properties, AdminApp\Units, Filament\HouseResource) already tries to save
 * `null` for them - meaning creating a BnB unit has been failing outright with a DB
 * integrity error since listing_mode/short_term was introduced. ->change() (via
 * doctrine/dbal, already a transitive dependency) for portability across MySQL and
 * the SQLite test suite, matching the pattern already used elsewhere in this project
 * (see 2026_08_25_060830_alter_staff_assignments_for_agent.php).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->double('rent_amount')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->double('rent_amount')->nullable(false)->change();
        });
    }
};
