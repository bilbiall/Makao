<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\House;
use App\Models\Location;
use App\Models\Package;

class MarketingController extends Controller
{
    /**
     * The consumer-facing homepage - search-first, matching how a renter/guest
     * actually wants to start (like Airbnb's own homepage), not the landlord SaaS
     * pitch. The landlord pitch itself moved to forLandlords() below, still fully
     * intact, just no longer the default "/" experience.
     */
    public function home()
    {
        $featured = House::publiclyVisible()->with(['location', 'photos'])
            ->latest()
            ->take(3)
            ->get()
            ->concat(
                House::bnbVisible()->with(['location', 'photos', 'pricePackages'])
                    ->latest()
                    ->take(3)
                    ->get()
            )
            ->shuffle();

        // Every seeded area (Nairobi, Mombasa, etc.) plus any custom geo_id typed
        // before that master list existed, so search suggestions aren't limited
        // to places that already happen to have a listing.
        $areas = Area::suggestionNames(Location::whereNotNull('geo_id')->distinct()->pluck('geo_id'));

        return view('marketing.home', ['featured' => $featured, 'areas' => $areas]);
    }

    public function forLandlords()
    {
        $packages = Package::where('is_active', true)->orderBy('sort_order')->take(3)->get();

        return view('marketing.for-landlords', ['packages' => $packages]);
    }

    public function pricing()
    {
        $packages = Package::where('is_active', true)->orderBy('sort_order')->get();

        return view('marketing.pricing', ['packages' => $packages]);
    }

    public function privacy()
    {
        return view('marketing.privacy');
    }

    public function terms()
    {
        return view('marketing.terms');
    }
}
