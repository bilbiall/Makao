<?php

namespace App\Http\Controllers;

use App\Models\Package;

class MarketingController extends Controller
{
    public function home()
    {
        $packages = Package::where('is_active', true)->orderBy('sort_order')->take(3)->get();

        return view('marketing.home', ['packages' => $packages]);
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
