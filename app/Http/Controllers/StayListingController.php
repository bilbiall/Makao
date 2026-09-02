<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Booking;
use App\Models\House;
use App\Models\Location;
use Illuminate\Http\Request;

/**
 * Public short-stay (BnB) discovery - the "Stay" counterpart to
 * PropertyListingController's long-term "Homes" search. Never creates or reads
 * Tenant rows; occupancy here is the bookings calendar, not house_status.
 */
class StayListingController extends Controller
{
    public function index(Request $request)
    {
        $query = House::bnbVisible()->with(['location', 'photos', 'pricePackages']);

        // Same area/neighbourhood filter dimension as PropertyListingController -
        // Location.geo_id, not a specific Location.
        if ($request->filled('area')) {
            $query->whereHas('location', fn ($q) => $q->where('geo_id', $request->string('area')));
        }

        if ($request->filled('check_in') && $request->filled('check_out')) {
            $checkIn = $request->date('check_in');
            $checkOut = $request->date('check_out');

            $query->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
                $q->blocking()->overlapping($checkIn, $checkOut);
            });
        }

        $houses = $query->paginate(12)->withQueryString();

        $areas = Area::suggestionNames(
            Location::whereHas('houses', fn ($q) => $q->bnbVisible())
                ->whereNotNull('geo_id')
                ->distinct()
                ->pluck('geo_id')
        );

        return view('stays.index', compact('houses', 'areas'));
    }

    public function show(House $house)
    {
        abort_unless(House::bnbVisible()->whereKey($house->id)->exists(), 404);

        $house->load(['location', 'photos', 'pricePackages']);

        return view('stays.show', compact('house'));
    }
}
