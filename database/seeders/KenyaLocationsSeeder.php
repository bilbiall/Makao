<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\City;
use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Reference data (not demo data) powering the location picker: a property
 * owner adding a property picks a city, then an area within it, instead of
 * free-typing a neighbourhood name. Starting with 6 major cities/towns -
 * expand this manifest as more are needed. Fully idempotent (firstOrCreate
 * throughout) - safe to re-run after adding more areas/cities here.
 */
class KenyaLocationsSeeder extends Seeder
{
    public function run(): void
    {
        $manifest = $this->manifest();

        foreach ($manifest as $cityName => $areaNames) {
            $city = City::firstOrCreate(['name' => $cityName]);

            foreach ($areaNames as $areaName) {
                Area::firstOrCreate(['city_id' => $city->id, 'name' => $areaName]);
            }
        }

        $this->backfillExistingLocations();
    }

    /**
     * Links any already-seeded Location (e.g. from DemoNairobiSeeder, created
     * before this master list existed) to its matching Area, purely so
     * existing demo data benefits from city-level grouping too. Only ever
     * touches locations with no area_id yet - never overwrites a deliberate
     * pick.
     */
    private function backfillExistingLocations(): void
    {
        Location::whereNull('area_id')->whereNotNull('geo_id')->get()->each(function (Location $location) {
            $area = Area::whereRaw('LOWER(name) = ?', [strtolower($location->geo_id)])->first();
            if ($area) {
                $location->area_id = $area->id;
                $location->saveQuietly();
            }
        });
    }

    private function manifest(): array
    {
        return [
            'Nairobi' => [
                'Kilimani', 'Lavington', 'Westlands', 'Parklands', 'Kileleshwa', 'Hurlingham', 'Upper Hill',
                'Karen', 'Langata', 'South B', 'South C', 'Nairobi West', 'Madaraka', 'Nyayo Estate',
                'Ngong Road', 'Dagoretti', 'Kawangware', 'Riruta', 'Uthiru', 'Kinoo', 'Kikuyu',
                'Rongai', 'Ngong', 'Kiserian', 'Kasarani', 'Roysambu', 'Zimmerman', 'Kahawa',
                'Kahawa Sukari', 'Kahawa Wendani', 'Githurai', 'Ruaka', 'Runda', 'Muthaiga', 'Gigiri',
                'Spring Valley', 'Loresho', 'Kitisuru', 'Embakasi', 'Donholm', 'Buruburu', 'Umoja',
                'Komarock', 'Kayole', 'Utawala', 'Ruai', 'Pipeline', 'Imara Daima', 'Syokimau',
                'Mlolongo', 'Athi River', 'Eastleigh', 'Pangani', 'Ngara', 'Jericho', 'Fedha', 'Tena',
            ],
            'Mombasa' => [
                'Nyali', 'Bamburi', 'Shanzu', 'Kizingo', 'Tudor', 'Tononoka', 'Likoni', 'Changamwe',
                'Kisauni', 'Mtwapa', 'Mikindani', 'Old Town', 'Ganjoni', 'Majengo', 'Kongowea',
            ],
            'Kisumu' => [
                'Milimani', 'Riat', 'Mountain View', 'Migosi', 'Lolwe', 'Tom Mboya', 'Nyalenda',
                'Mamboleo', 'Manyatta', 'Bandani', 'Kondele', 'Kibuye', 'Otonglo', 'Dunga',
            ],
            'Nakuru' => [
                'Milimani', 'Section 58', 'Naka', 'Bahati', 'Lanet', 'Kiamunyi', 'London', 'Langalanga',
                'Pangani', 'Shabab', 'Bangladesh', 'Free Area', 'Ngata', 'Afraha', 'White House',
            ],
            'Eldoret' => [
                'Elgon View', 'Pioneer', 'Langas', 'Kapseret', 'Huruma', 'Kapsoya', 'West Indies',
                'Kimumu', 'Jerusalem', 'Annex', 'Outspan', 'Racecourse', 'Kipkorgot', 'Kipkaren', 'Mwanzo',
            ],
            'Malindi' => [
                'Malindi Town', 'Casuarina', 'Park Marine', 'Mijomboni', 'Garashi', 'Mambrui', 'Watamu',
                'Shella', 'Sabaki', 'Ganda', 'Silversands', 'Maweni',
            ],
        ];
    }
}
