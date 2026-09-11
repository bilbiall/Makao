<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\City;
use App\Models\House;
use App\Models\ViewingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Public house-discovery pages - deliberately separate from MarketingController,
 * which is the B2B landlord-facing landing page with a different audience/content
 * shape. A house's visibility here is derived (House::scopePubliclyVisible), not a
 * manual publish toggle - it tracks house_status automatically.
 */
class PropertyListingController extends Controller
{
    public function index(Request $request)
    {
        $query = House::publiclyVisible()->with(['location.area', 'photos']);

        // Filtered by area/neighbourhood (Location.geo_id, e.g. "Kilimani") or by an
        // entire city (e.g. "Mombasa", matching every area within it) - see
        // House::scopeInAreaOrCity(). That's how a tenant actually searches -
        // either "something in Kilimani" or more broadly "something in Mombasa".
        if ($request->filled('area')) {
            $query->inAreaOrCity($request->string('area')->toString());
        }

        if ($request->filled('house_type')) {
            $query->where('house_type', $request->string('house_type'));
        }

        if ($request->filled('max_rent')) {
            $query->where('rent_amount', '<=', $request->integer('max_rent'));
        }

        $houses = $query->orderBy('rent_amount')->paginate(12)->withQueryString();

        $cities = City::breakdown();
        $counts = House::availabilityCountsByArea('long_term');

        // Geocoded areas near the one searched, that also have availability - an
        // addition on top of the exact-name match above, never a replacement for
        // it. Empty whenever no area was searched, the searched area isn't a known
        // Area (e.g. a city name or free-typed geo_id), or it hasn't been geocoded.
        $nearbyAreas = collect();
        if ($request->filled('area')) {
            $searchedArea = Area::where('name', $request->string('area')->toString())->first();
            if ($searchedArea) {
                $nearbyAreas = $searchedArea->nearby()->filter(fn (Area $a) => ($counts[$a->name] ?? 0) > 0);
            }
        }

        $watchlistedIds = Auth::check() && Auth::user()->isUser()
            ? Auth::user()->watchlist()->pluck('houses.id')->all()
            : [];

        $pins = House::mapPins($houses->getCollection());

        return view('listings.index', compact('houses', 'cities', 'counts', 'nearbyAreas', 'watchlistedIds', 'pins'));
    }

    public function show(House $house)
    {
        $isPubliclyVisible = House::publiclyVisible()->whereKey($house->id)->exists();

        // A house that exists but is no longer publicly visible (occupied, unpublished,
        // etc) gets an "unavailable, notify me" page instead of a dead-end 404 - the
        // house might be sitting in someone's watchlist or bookmarks. Still let a user
        // view the full listing they've already requested a viewing on, even if it's
        // since gone vacant->occupied again, so their application history doesn't 404.
        if (! $isPubliclyVisible && ! (Auth::check() && $house->viewingRequests()->where('user_id', Auth::id())->exists())) {
            $house->load(['location', 'photos']);

            $hasAlert = Auth::check()
                && \App\Models\HouseAlert::where('user_id', Auth::id())->where('house_id', $house->id)->exists();

            return view('listings.unavailable', compact('house', 'hasAlert'));
        }

        $house->load(['location', 'photos']);

        $isWatchlisted = Auth::check() && Auth::user()->isUser()
            && Auth::user()->watchlist()->where('houses.id', $house->id)->exists();

        $pendingRequest = Auth::check() && Auth::user()->isUser()
            ? $house->viewingRequests()->where('user_id', Auth::id())->where('status', 'pending')->exists()
            : false;

        return view('listings.show', compact('house', 'isWatchlisted', 'pendingRequest'));
    }

    /** "Notify me when this specific listing is available again" - from the unavailable-listing page. */
    public function notifyWhenAvailable(House $house)
    {
        $user = Auth::user();
        abort_unless($user && $user->isUser(), 403, 'Only "looking for a house" accounts can request alerts.');

        \App\Models\HouseAlert::firstOrCreate([
            'user_id' => $user->id,
            'house_id' => $house->id,
        ]);

        return back()->with('status', "You'll get an email as soon as this listing is available again.");
    }

    public function toggleWatchlist(House $house)
    {
        $user = Auth::user();
        abort_unless($user && $user->isUser(), 403, 'Only "looking for a house" accounts can save listings.');

        if ($user->watchlist()->where('houses.id', $house->id)->exists()) {
            $user->watchlist()->detach($house->id);
        } else {
            $user->watchlist()->attach($house->id);
        }

        return back();
    }

    public function requestViewing(House $house)
    {
        $user = Auth::user();
        abort_unless($user && $user->isUser(), 403, 'Only "looking for a house" accounts can request a viewing.');

        if ($house->house_status !== 'Vacant') {
            return back()->withErrors(['viewing' => 'This house is no longer available.']);
        }

        if ($house->viewingRequests()->where('user_id', $user->id)->where('status', 'pending')->exists()) {
            return back()->with('status', 'You already have a pending request for this house.');
        }

        ViewingRequest::create([
            'user_id' => $user->id,
            'house_id' => $house->id,
        ]);

        return back()->with('status', 'Viewing requested. The landlord will be in touch to arrange a visit.');
    }
}
