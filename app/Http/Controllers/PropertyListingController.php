<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\House;
use App\Models\Location;
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
        $query = House::publiclyVisible()->with(['location', 'photos']);

        // Filtered by area/neighbourhood (Location.geo_id, e.g. "Kilimani") rather than
        // a specific Location (a single property/compound) - that's how a renter
        // actually searches ("something in Kilimani"), and geo_id already holds
        // exactly that neighbourhood-level value for every seeded property.
        if ($request->filled('area')) {
            $query->whereHas('location', fn ($q) => $q->where('geo_id', $request->string('area')));
        }

        if ($request->filled('house_type')) {
            $query->where('house_type', $request->string('house_type'));
        }

        if ($request->filled('max_rent')) {
            $query->where('rent_amount', '<=', $request->integer('max_rent'));
        }

        $houses = $query->orderBy('rent_amount')->paginate(12)->withQueryString();

        $areas = Area::suggestionNames(
            Location::whereHas('houses', fn ($q) => $q->publiclyVisible())
                ->whereNotNull('geo_id')
                ->distinct()
                ->pluck('geo_id')
        );

        $watchlistedIds = Auth::check() && Auth::user()->isUser()
            ? Auth::user()->watchlist()->pluck('houses.id')->all()
            : [];

        return view('listings.index', compact('houses', 'areas', 'watchlistedIds'));
    }

    public function show(House $house)
    {
        abort_unless(
            House::publiclyVisible()->whereKey($house->id)->exists()
                // Still let a user view a listing they've already requested a viewing on,
                // even if it's since gone vacant->occupied again, so their application
                // history doesn't 404.
                || (Auth::check() && $house->viewingRequests()->where('user_id', Auth::id())->exists()),
            404
        );

        $house->load(['location', 'photos']);

        $isWatchlisted = Auth::check() && Auth::user()->isUser()
            && Auth::user()->watchlist()->where('houses.id', $house->id)->exists();

        $pendingRequest = Auth::check() && Auth::user()->isUser()
            ? $house->viewingRequests()->where('user_id', Auth::id())->where('status', 'pending')->exists()
            : false;

        return view('listings.show', compact('house', 'isWatchlisted', 'pendingRequest'));
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
