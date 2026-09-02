<?php

namespace App\Http\Controllers;

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
        $query = House::bnbVisible()->with(['location', 'photos', 'pricePackages']);

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

        return view('stays.index', compact('houses', 'cities'));
    }

    public function show(House $house)
    {
        abort_unless(House::bnbVisible()->whereKey($house->id)->exists(), 404);

        $house->load(['location', 'photos', 'pricePackages']);

        return view('stays.show', compact('house'));
    }
}
