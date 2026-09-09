<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

/**
 * Every one of Kenya's 47 county headquarters/major towns as a City row, so
 * the "locations we're open to" list (Superadmin > Locations) has the whole
 * country to choose from, not just the handful KenyaLocationsSeeder already
 * has real neighbourhood-level Area data for.
 *
 * Deliberately City-only here, no Area rows - there's no neighbourhood data
 * for most of these yet, and a landlord in a newly-opened city still has the
 * existing free-text "Area (free text)" fallback (Location.geo_id) to name
 * their own area until one gets added to the master Area list. Fully
 * idempotent (firstOrCreate) - safe to re-run.
 *
 * is_open: the handful already seeded by KenyaLocationsSeeder (with real
 * listings/demo data behind them) are marked open here since they're
 * genuinely already live; every other county town starts closed - a
 * superadmin opts each one in from Superadmin > Locations when the business
 * is actually ready to operate there.
 */
class KenyaCountyTownsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->alreadyOpenCities() as $name) {
            City::firstOrCreate(['name' => $name])->update(['is_open' => true]);
        }

        foreach ($this->remainingCountyTowns() as $name) {
            City::firstOrCreate(['name' => $name], ['is_open' => false]);
        }
    }

    /** Already seeded by KenyaLocationsSeeder with real Area/demo data behind them. */
    private function alreadyOpenCities(): array
    {
        return ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Malindi'];
    }

    /** The other 42 of Kenya's 47 county HQ/major towns (Nairobi, Mombasa, Kisumu, Nakuru and Uasin Gishu's Eldoret are above). */
    private function remainingCountyTowns(): array
    {
        return [
            'Kwale', 'Kilifi', 'Hola', 'Lamu', 'Voi', 'Garissa', 'Wajir', 'Mandera', 'Marsabit', 'Isiolo',
            'Meru', 'Chuka', 'Embu', 'Kitui', 'Machakos', 'Wote', 'Ol Kalou', 'Nyeri', 'Kerugoya', "Murang'a",
            'Kiambu', 'Lodwar', 'Kapenguria', 'Maralal', 'Kitale', 'Iten', 'Kapsabet', 'Kabarnet', 'Nanyuki',
            'Narok', 'Kajiado', 'Kericho', 'Bomet', 'Kakamega', 'Vihiga', 'Bungoma', 'Busia', 'Siaya',
            'Homa Bay', 'Migori', 'Kisii', 'Nyamira',
        ];
    }
}
