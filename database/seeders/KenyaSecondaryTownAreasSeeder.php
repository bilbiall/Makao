<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\City;
use Illuminate\Database\Seeder;

/**
 * Real neighbourhood/estate names for a second tier of Kenyan county towns
 * (KenyaCountyTownsSeeder only added these as bare City rows, no areas) -
 * researched against Wikipedia, property-listing platforms and local
 * sources rather than guessed, same bar as KenyaLocationsSeeder. Deliberately
 * short lists for some towns (e.g. Machakos, Nanyuki) - only names that were
 * actually corroborated, not padded to look complete.
 *
 * Kericho is NOT included here - research turned up only 2 reliably
 * corroborated names, not enough to seed with confidence; needs a follow-up
 * pass before it gets a manifest entry. The remaining ~32 county towns are
 * likewise still City-only, to be added the same way once there's real
 * reason to open them (see CityResource's "Needs neighbourhoods added" filter
 * for the running list).
 *
 * Fully idempotent (firstOrCreate) - safe to re-run.
 */
class KenyaSecondaryTownAreasSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->manifest() as $cityName => $areaNames) {
            $city = City::firstOrCreate(['name' => $cityName]);

            foreach ($areaNames as $areaName) {
                Area::firstOrCreate(['city_id' => $city->id, 'name' => $areaName]);
            }
        }
    }

    private function manifest(): array
    {
        return [
            'Kiambu' => [
                'Ndumberi', "Ting'ang'a", 'Riabai', 'Kihingo', 'Ngegu', 'Kanunga', 'Kangoya', 'Kiamwangi', 'Tatu City',
            ],
            'Machakos' => [
                'Mua Hills', 'Maanzoni', 'Lukenya', 'Kalama', 'Ngelani',
            ],
            'Nyeri' => [
                'Kamakwa', "Ruring'u", 'Majengo', "King'ong'o", 'Outspan', 'Ring Road', 'Garden Estate',
                'Mountain View Estate', 'Kiganjo', 'Witemere',
            ],
            'Meru' => [
                'Makutano', 'Milimani', 'Gakoromone', 'Kaaga', 'Nkubu', 'Ntugi Market', 'Kooje Estate',
            ],
            'Kakamega' => [
                'Amalemba', 'Milimani', 'Sichirai', 'Shitao', 'Murram', 'Shinyalu Bar', 'Rosterman',
                'Ebambwa', 'Otiende', 'Nabongo',
            ],
            'Kisii' => [
                'Milimani', 'Nyanchwa', 'Nyamataro', 'Soko Mjinga', 'Daraja Mbili', 'Mwembe', 'Jogoo',
                'Nyamage', 'Nyakoe', 'Gesonso', 'Nyangena', 'Kebirigo',
            ],
            'Kitale' => [
                'Milimani', 'Bondeni', 'Kaloleni', 'Shauri Moyo', 'Section Six', 'Matato',
            ],
            'Bungoma' => [
                'Khalaba', 'Kanduyi', 'Muteremko', 'Sinoko', 'Ranje',
            ],
            'Nanyuki' => [
                'Muthaiga', 'Likki', 'Peacock Estate', 'Mountain View Estate',
            ],
        ];
    }
}
