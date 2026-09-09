<?php

namespace App\Console\Commands;

use App\Models\Area;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * One-time (or occasional) backfill of Area.latitude/longitude via Komoot's free
 * Photon geocoder (OpenStreetMap data) - not a live/interactive geocoder, so this
 * is a batch command, not something called from a request. Only ever fills in
 * missing coordinates; never overwrites an area that already has them unless
 * --force is passed. An area that fails to geocode is skipped (logged), not
 * fatal - it just stays out of "nearby areas" suggestions until retried, same as
 * before this feature existed.
 *
 * Photon, not Nominatim: Nominatim's public instance 403s this app's requests
 * outright (an IP-level block per its usage policy, unrelated to rate or
 * User-Agent - confirmed by hand with curl), so Photon is the working free
 * alternative, not a preference.
 */
class GeocodeAreas extends Command
{
    protected $signature = 'areas:geocode {--force : Re-geocode areas that already have coordinates}';

    protected $description = 'Backfill Area.latitude/longitude from OpenStreetMap data (via Photon)';

    public function handle(): int
    {
        $areas = Area::with('city')
            ->when(! $this->option('force'), fn ($q) => $q->whereNull('latitude'))
            ->get();

        if ($areas->isEmpty()) {
            $this->info('Nothing to geocode.');

            return self::SUCCESS;
        }

        $geocoded = 0;
        $failed = 0;

        foreach ($areas->values() as $index => $area) {
            $query = trim($area->name.', '.($area->city->name ?? '').', Kenya');

            $response = Http::get('https://photon.komoot.io/api/', [
                'q' => $query,
                'limit' => 5,
                // Restrict to place/locality features (suburb, neighbourhood, town...)
                // so a search like "Karen" doesn't resolve to a landmark/POI named
                // Karen instead of the neighbourhood itself.
                'osm_tag' => 'place',
            ]);

            $features = $response->successful() ? ($response->json('features') ?? []) : [];

            // Prefer an exact (case-insensitive) name match over the first result,
            // since a query can return several nearby "Karen ..." style features.
            $match = collect($features)->first(
                fn ($f) => strcasecmp($f['properties']['name'] ?? '', $area->name) === 0
            ) ?? $features[0] ?? null;

            if ($match) {
                [$longitude, $latitude] = $match['geometry']['coordinates'];
                $area->update(['latitude' => $latitude, 'longitude' => $longitude]);
                $geocoded++;
                $this->line("  {$query} -> {$latitude}, {$longitude}");
            } else {
                $failed++;
                $this->warn("  {$query} -> no match");
            }

            // Be a polite, low-volume caller of a free shared public API.
            if ($index < $areas->count() - 1) {
                usleep(500_000);
            }
        }

        $this->info("Geocoded {$geocoded} area(s), {$failed} failed.");

        return self::SUCCESS;
    }
}
