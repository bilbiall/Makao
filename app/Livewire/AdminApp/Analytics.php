<?php

namespace App\Livewire\AdminApp;

use App\Models\House;
use App\Support\StaffScope;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Views -> inquiries -> bookings funnel per short-stay house - the agent
 * analytics dashboard from the BnB marketing brainstorm, scoped the same way
 * as Promotions/Inquiries (agent: their assigned houses; admin/landlord:
 * everything). Deliberately simple counts, not a charting dashboard - an
 * agent mainly needs "is this listing getting looked at, and is that turning
 * into anything."
 */
class Analytics extends Component
{
    public function mount(): void
    {
        abort_unless(StaffScope::isAgent() || in_array(Auth::user()->role, ['admin', 'landlord']), 403);
    }

    public function render()
    {
        $query = House::where('listing_mode', 'short_term')
            ->withCount(['bookings' => fn ($q) => $q->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])])
            ->with('location');

        if (StaffScope::isAgent()) {
            $query->whereIn('id', StaffScope::houseIds());
        }

        $houses = $query->get();

        // A single extra query for all houses' inquiry counts, rather than one
        // per house - houses.count() is small enough that this is still one
        // round trip either way, but this keeps it that way even if it grows.
        $inquiryCounts = \App\Models\HouseInquiry::whereIn('house_id', $houses->pluck('id'))
            ->selectRaw('house_id, count(*) as count')
            ->groupBy('house_id')
            ->pluck('count', 'house_id');

        $rows = $houses->map(fn (House $house) => [
            'house' => $house,
            'views' => $house->views_count,
            'inquiries' => $inquiryCounts[$house->id] ?? 0,
            'bookings' => $house->bookings_count,
        ])->sortByDesc('views')->values();

        $totals = [
            'views' => $rows->sum('views'),
            'inquiries' => $rows->sum('inquiries'),
            'bookings' => $rows->sum('bookings'),
        ];

        return view('livewire.admin-app.analytics', compact('rows', 'totals'))
            ->layout('components.layouts.app', ['title' => 'Analytics']);
    }
}
