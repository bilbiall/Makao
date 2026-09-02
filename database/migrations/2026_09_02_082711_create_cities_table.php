<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Top-level grouping for the location picker (property owners adding a
 * property, and the public search widgets). Deliberately "city/town", not
 * Kenya's 47 administrative counties - real-estate search is done by town
 * (Eldoret, Malindi), not by the county that town sits in (Uasin Gishu,
 * Kilifi), and "Nairobi"/"Mombasa" here mean the metro area (e.g. Ruaka,
 * Rongai, Syokimau all group under Nairobi) matching how this app already
 * seeds/organizes demo data and how Kenyan property sites do it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
