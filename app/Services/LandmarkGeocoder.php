<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * On-the-fly geocoding for a free-text landmark mentioned in a chat search
 * (e.g. "Yaya Centre", "JKIA") - the counterpart to GeocodeAreas, which only
 * ever backfills the fixed Area list. Same provider (Photon - see
 * GeocodeAreas's docblock for why Nominatim isn't used) and cached, since the
 * same landmark will come up repeatedly across different visitors' chats and
 * this is a free, low-volume public API best not hammered per request.
 */
class LandmarkGeocoder
{
    /** Null result (not found) is cached too, so a bad/unrecognized query isn't retried on every message. */
    public function geocode(string $query): ?array
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        $cacheKey = 'landmark_geocode:'.md5(mb_strtolower($query));

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($query) {
            try {
                $response = Http::timeout(5)->get('https://photon.komoot.io/api/', [
                    'q' => $query.', Kenya',
                    'limit' => 1,
                ]);

                $feature = $response->successful() ? $response->json('features.0') : null;

                if (! $feature) {
                    return null;
                }

                [$longitude, $latitude] = $feature['geometry']['coordinates'];

                return ['lat' => $latitude, 'lng' => $longitude];
            } catch (\Throwable $e) {
                Log::warning('Landmark geocoding failed', ['query' => $query, 'message' => $e->getMessage()]);

                return null;
            }
        });
    }
}
