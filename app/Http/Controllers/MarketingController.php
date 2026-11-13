<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\House;
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

        $cities = City::breakdown();
        $longTermCounts = House::availabilityCountsByArea('long_term');
        $shortTermCounts = House::availabilityCountsByArea('short_term');

        return view('marketing.home', [
            'featured' => $featured,
            'cities' => $cities,
            'longTermCounts' => $longTermCounts,
            'shortTermCounts' => $shortTermCounts,
        ]);
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

    /**
     * /sitemap.xml - the static public pages plus every publicly visible rental,
     * BnB stay and agent profile, so search engines find new listings without
     * crawling. Cached for an hour; a listing that goes private drops out on the
     * next rebuild.
     */
    public function sitemap()
    {
        $xml = \Illuminate\Support\Facades\Cache::remember('sitemap_xml', 3600, function () {
            $urls = collect([
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('listings.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => route('stays.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => route('for-landlords'), 'priority' => '0.8', 'changefreq' => 'monthly'],
                ['loc' => route('pricing'), 'priority' => '0.6', 'changefreq' => 'monthly'],
                ['loc' => route('get-started'), 'priority' => '0.6', 'changefreq' => 'monthly'],
                ['loc' => route('privacy'), 'priority' => '0.2', 'changefreq' => 'yearly'],
                ['loc' => route('terms'), 'priority' => '0.2', 'changefreq' => 'yearly'],
            ]);

            House::publiclyVisible()->get()->each(fn ($h) => $urls->push([
                'loc' => route('listings.show', $h), 'lastmod' => $h->updated_at?->toAtomString(), 'priority' => '0.7', 'changefreq' => 'weekly',
            ]));

            House::bnbVisible()->get()->each(fn ($h) => $urls->push([
                'loc' => route('stays.show', $h), 'lastmod' => $h->updated_at?->toAtomString(), 'priority' => '0.7', 'changefreq' => 'weekly',
            ]));

            \App\Models\User::where('role', 'agent')->whereNotNull('slug')->get()->each(fn ($u) => $urls->push([
                'loc' => route('agents.show', $u), 'lastmod' => $u->updated_at?->toAtomString(), 'priority' => '0.5', 'changefreq' => 'weekly',
            ]));

            return view('marketing.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** /robots.txt - dynamic so the Sitemap line always carries the real domain. */
    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /app/',
            'Disallow: /admin',
            'Disallow: /superadmin',
            'Disallow: /tenant/',
            'Disallow: /login',
            'Disallow: /join',
            'Disallow: /demo-login',
            'Disallow: /bookings/',
            'Disallow: /email/',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /investors',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
