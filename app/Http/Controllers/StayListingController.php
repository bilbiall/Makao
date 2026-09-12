<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Booking;
use App\Models\City;
use App\Models\House;
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
        $query = House::bnbVisible()->with(['location.area', 'photos', 'pricePackages'])->withCount('reviews')->withAvg('reviews', 'rating');

        // Same area-or-city filter dimension as PropertyListingController - see
        // House::scopeInAreaOrCity().
        if ($request->filled('area')) {
            $query->inAreaOrCity($request->string('area')->toString());
        }

        if ($request->filled('check_in') && $request->filled('check_out')) {
            $checkIn = $request->date('check_in');
            $checkOut = $request->date('check_out');

            $query->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
                $q->blocking()->overlapping($checkIn, $checkOut);
            });
        }

        $houses = $query->paginate(12)->withQueryString();

        $cities = City::breakdown();
        $counts = House::availabilityCountsByArea('short_term');

        // See PropertyListingController::index() - same additive nearby-areas suggestion.
        $nearbyAreas = collect();
        if ($request->filled('area')) {
            $searchedArea = Area::where('name', $request->string('area')->toString())->first();
            if ($searchedArea) {
                $nearbyAreas = $searchedArea->nearby()->filter(fn (Area $a) => ($counts[$a->name] ?? 0) > 0);
            }
        }

        $pins = House::mapPins($houses->getCollection());

        return view('stays.index', compact('houses', 'cities', 'counts', 'nearbyAreas', 'pins'));
    }

    public function show(House $house)
    {
        abort_unless(House::bnbVisible()->whereKey($house->id)->exists(), 404);

        $house->increment('views_count');

        $house->load(['location', 'photos', 'pricePackages']);
        $house->load(['reviews' => fn ($q) => $q->latest()->limit(20)]);

        // The agent managing this specific property, if any - shown as a "Hosted
        // by" card linking to their public profile. Falls back to nothing (not
        // every short_term house has a dedicated agent assigned).
        $agent = \App\Models\User::where('role', 'agent')
            ->whereHas('assignedHouses', fn ($q) => $q->where('houses.id', $house->id))
            ->first();

        return view('stays.show', compact('house', 'agent'));
    }

    /** Lightweight "ask a question" lead - see HouseInquiry's docblock for why this is separate from a booking. */
    public function inquire(Request $request, House $house)
    {
        abort_unless($house->isShortTerm(), 404);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        \App\Models\HouseInquiry::create([
            'house_id' => $house->id,
            ...$data,
        ]);

        return back()->with('status', "Thanks {$data['name']} - the host will get back to you shortly.");
    }
}
